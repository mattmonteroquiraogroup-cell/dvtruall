<?php
session_start();
if (!isset($_SESSION['isLoggedIn'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["ok" => false, "error" => "Not authenticated"]);
    exit;
}

header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;   // 👈 make sure this is correct

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();



use Google\Client;
use Google\Service\Sheets;

// ✅ Build Google credentials dynamically from .env
$googleCredentials = [
    "type"                        => $_ENV['GOOGLE_TYPE'],
    "project_id"                  => $_ENV['GOOGLE_PROJECT_ID'],
    "private_key_id"              => $_ENV['GOOGLE_PRIVATE_KEY_ID'],
    "private_key"                 => $_ENV['GOOGLE_PRIVATE_KEY'],
    "client_email"                => $_ENV['GOOGLE_CLIENT_EMAIL'],
    "client_id"                   => $_ENV['GOOGLE_CLIENT_ID'],
    "auth_uri"                    => $_ENV['GOOGLE_AUTH_URI'],
    "token_uri"                   => $_ENV['GOOGLE_TOKEN_URI'],
    "auth_provider_x509_cert_url" => $_ENV['GOOGLE_AUTH_PROVIDER_CERT_URL'],
    "client_x509_cert_url"        => $_ENV['GOOGLE_CLIENT_CERT_URL']
];

// ✅ Write temp credentials file (ignored by Git)
$tempJson = __DIR__ . '/google-credentials.json';
file_put_contents($tempJson, json_encode($googleCredentials));

// ✅ Setup Google Client
$client = new Client();
$client->setAuthConfig($tempJson);
$client->addScope(Sheets::SPREADSHEETS);

$service = new Sheets($client);

// ✅ your real spreadsheet ID
$spreadsheetId = '1DgWfyS0eF72Imdg9lh6wzYeeBzx8DslS3RgJweKo0w0';

// ✅ Ensure sheet for current month exists
function ensureMonthSheet($service, $spreadsheetId) {
    $sheetName = date('y-M'); // e.g. "25-Sep"
    $spreadsheet = $service->spreadsheets->get($spreadsheetId);

    foreach ($spreadsheet->getSheets() as $sh) {
        if ($sh->getProperties()->getTitle() === $sheetName) {
            return $sheetName;
        }
    }

    // Create sheet if missing
    $requests = [
        new Sheets\Request([
            'addSheet' => ['properties' => ['title' => $sheetName]]
        ])
    ];
    $service->spreadsheets->batchUpdate(
        $spreadsheetId,
        new Sheets\BatchUpdateSpreadsheetRequest(['requests' => $requests])
    );

    // Add headers
    $headers = [[
        'CATEGORY','DV/CHECK DATE','CHECK NO.','INVOICE','PAYEE',
        'DV DESCRIPTION','PARTICULARS','(PHP) AMOUNT','(USD) AMOUNT',
        'ACCOUNT #','PERIOD COVERED'
    ]];
    $body = new Sheets\ValueRange(['values' => $headers]);
    $service->spreadsheets_values->update(
        $spreadsheetId,
        $sheetName . '!A1:K1',
        $body,
        ['valueInputOption' => 'RAW']
    );

    return $sheetName;
}

try {
    $action = $_GET['action'] ?? 'insert';

    // === FETCH DATA ===
if ($action === 'fetch') {
    $sheetName = $_GET['sheet'] ?? ensureMonthSheet($service, $spreadsheetId);

    // ✅ Check if sheet exists
    $spreadsheet = $service->spreadsheets->get($spreadsheetId);
    $sheetExists = false;
    foreach ($spreadsheet->getSheets() as $sh) {
        if ($sh->getProperties()->getTitle() === $sheetName) {
            $sheetExists = true;
            break;
        }
    }

    if (!$sheetExists) {
        echo json_encode(["ok" => true, "data" => []]);
        exit;
    }

    $range = $sheetName . '!A2:K';

    $values = [];
    try {
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues() ?? [];
    } catch (Exception $e) {
        $values = [];
    }

    $data = [];
    foreach ($values as $i => $row) {
        $rowIndex = $i + 2;
        while (count($row) < 11) {
            $row[] = '';
        }
        $row[] = $rowIndex;   // [11] Row index
        $row[] = $sheetName;  // [12] Sheet name
        $data[] = $row;
    }

    echo json_encode(["ok" => true, "data" => $data]);
    exit;
}

   // === INSERT OR UPDATE ===
if ($action === 'insert' || $action === 'update') {
    $data = json_decode(file_get_contents("php://input"), true);
    $sheetName = ensureMonthSheet($service, $spreadsheetId);

    $currency = $data['currency'] ?? 'PHP';
    $total    = isset($data['total']) ? (float)$data['total'] : 0;
    $phpAmt   = ($currency === 'PHP') ? $total : '';
    $usdAmt   = ($currency === 'USD') ? $total : '';

    // Merge particulars + items
    $particularsMerged = $data['particulars'] ?? '';
    if (!empty($data['items'])) {
        $itemsList = array_map(function($i) use ($currency) {
            $desc   = $i['desc']   ?? '';
            $amount = isset($i['amount']) ? number_format((float)$i['amount'], 2) : '0.00';
            return "$desc ($currency $amount)";
        }, $data['items']);

        $particularsMerged = $particularsMerged
            ? $particularsMerged . "\n" . implode("\n", $itemsList)
            : implode("\n", $itemsList);
    }

    $row = [
        $data['category']   ?? '',
        $data['date']       ?? '',
        $data['checkNo']    ?? '',
        $data['invoice']    ?? '',
        $data['payee']      ?? '',
        $data['dvDesc']     ?? '',
        $particularsMerged,
        $phpAmt,
        $usdAmt,
        $data['account']    ?? '',
        $data['coverage']   ?? ''
    ];

    if ($action === 'insert') {
        $range = $sheetName . '!A:K';
        $body = new Sheets\ValueRange(['values' => [$row]]);
        $service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    } else {
        $rowIndex = intval($data['row'] ?? 0);

        // ✅ Validate row index
        if ($rowIndex < 2) {
            echo json_encode(["ok" => false, "error" => "Invalid row index: " . $rowIndex]);
            exit;
        }

        $range = $sheetName . "!A{$rowIndex}:K{$rowIndex}";
        $body  = new Sheets\ValueRange(['values' => [$row]]);
        $service->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    echo json_encode(["ok" => true]);
    exit;
}

// === DELETE ROW ===
if ($action === 'delete') {
    $row = intval($_GET['row'] ?? 0);
    $sheetName = $_GET['sheet'] ?? ensureMonthSheet($service, $spreadsheetId);

    if ($row < 2) {
        echo json_encode(["ok" => false, "error" => "Invalid row index: $row"]);
        exit;
    }

    $sheets = $service->spreadsheets->get($spreadsheetId)->getSheets();
    $sheetId = null;
    foreach ($sheets as $sh) {
        if ($sh->getProperties()->getTitle() === $sheetName) {
            $sheetId = $sh->getProperties()->getSheetId();
            break;
        }
    }

    if (!$sheetId) {
        echo json_encode(["ok" => false, "error" => "Sheet not found: $sheetName"]);
        exit;
    }

    $requests = [
        new Sheets\Request([
            'deleteDimension' => [
                'range' => [
                    'sheetId' => $sheetId,
                    'dimension' => 'ROWS',
                    'startIndex' => $row - 1, // Google Sheets API is 0-based
                    'endIndex'   => $row
                ]
            ]
        ])
    ];

    $service->spreadsheets->batchUpdate(
        $spreadsheetId,
        new Sheets\BatchUpdateSpreadsheetRequest(['requests' => $requests])
    );

    echo json_encode(["ok" => true, "message" => "Row $row deleted from $sheetName"]);
    exit;
}


} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}


