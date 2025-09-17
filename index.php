<?php
session_start();

if (!isset($_SESSION['isLoggedIn'])) {
    header("Location: login.php");
    exit;
}

// ✅ Prevent browser from caching this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <meta charset="utf-8">
  <title>Disbursement Voucher</title>
  <link rel="icon" href="talogo.ico" type="image/x-icon">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .sidebar a.active { background-color: #1e40af; }
    /* Animations */
    .modal-backdrop { backdrop-filter: blur(4px); }
    .slide-in { animation: slideIn 0.3s ease-out; }
    .slide-out { animation: slideOut 0.3s ease-in; }
    @keyframes slideIn { from {opacity:0;transform:translateY(-20px) scale(0.95);} to {opacity:1;transform:translateY(0) scale(1);} }
    @keyframes slideOut { from {opacity:1;transform:translateY(0) scale(1);} to {opacity:0;transform:translateY(-20px) scale(0.95);} }

    .toast-enter { animation: toastEnter 0.4s ease-out; }
    .toast-exit { animation: toastExit 0.3s ease-in; }
    @keyframes toastEnter { from {opacity:0;transform:translateX(100%);} to {opacity:1;transform:translateX(0);} }
    @keyframes toastExit { from {opacity:1;transform:translateX(0);} to {opacity:0;transform:translateX(100%);} }

    /* Collapsed Sidebar */
    .sidebar.collapsed { width: 64px; }
    .sidebar.collapsed .sidebar-text,
    .sidebar.collapsed .sidebar-title { display: none; }
    .sidebar.collapsed #toggleIcon { transform: rotate(180deg); }

    /* --- DataTables Tailwind-Friendly Styling --- */
    table.dataTable thead th {
      background-color: #1e3a8a;  /* blue-900 */
      color: white;
      font-weight: 600;
      font-size: 0.875rem;         
      text-transform: uppercase;
      letter-spacing: 0.05em;      
      padding: 0.75rem 1rem;
    }
    table.dataTable tbody tr:hover {
      background-color: #eff6ff;  /* blue-50 */
    }
    .dataTables_wrapper .dataTables_filter input {
      border: 1px solid #d1d5db;   
      border-radius: 0.5rem;       
      padding: 0.5rem 0.75rem;     
      outline: none;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
      border-color: #3b82f6;       
      box-shadow: 0 0 0 2px #3b82f6;
    }
    .dataTables_wrapper .dataTables_length select {
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;     
      padding: 0.25rem 0.5rem;
      outline: none;
    }
    .dataTables_wrapper .dataTables_length select:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 0 2px #3b82f6;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      padding: 0.25rem 0.75rem;
      margin: 0 0.25rem;
      color: #374151;              
      background: #f3f4f6;         
      transition: all 0.2s;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: #3b82f6;         
      color: white !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: #2563eb;         
      color: white !important;
      border-color: #2563eb;
    }
    .dataTables_wrapper .dataTables_info {
      color: #4b5563; /* gray-600 */
      font-size: 0.875rem;
      margin-top: 0.5rem;
    }
  </style>
</head>
<body class="flex h-screen bg-gray-50">
  <!-- Sidebar -->
<div id="sidebar" class="sidebar w-56 bg-gray-900 text-white flex flex-col transition-all duration-300">
  <!-- Toggle Button -->
  <div class="flex justify-between items-center p-4 border-b border-gray-700">
    <span class="font-bold text-lg sidebar-title">Menu</span>
    <button onclick="toggleSidebar()" class="text-gray-400 hover:text-white">
      <i id="toggleIcon" class="fa-solid fa-angles-left"></i>
    </button>
  </div>
  <!-- Menu Items -->
  <a class="p-4 cursor-pointer hover:bg-gray-700 active flex items-center space-x-2"
     onclick="showPage('formPage',this)">
    <i class="fa-solid fa-file-circle-plus"></i>
    <span class="sidebar-text">New Voucher</span>
  </a>
  <a class="p-4 cursor-pointer hover:bg-gray-700 flex items-center space-x-2"
     onclick="showPage('tablePage',this)">
    <i class="fa-solid fa-table-list"></i>
    <span class="sidebar-text">View Records</span>
  </a>
<a class="p-4 cursor-pointer hover:bg-gray-700 flex items-center space-x-2" href="logout.php">
  <i class="fa-solid fa-right-from-bracket"></i>
  <span class="sidebar-text">Logout</span>
</a>
</div>
  <!-- Main Content -->
  <div class="content flex-1 p-6 overflow-auto">
    <!-- Form Page -->
    <div id="formPage">
      <form id="voucherForm" onsubmit="event.preventDefault(); submitForm();" class="max-w-6xl mx-auto">
        <input type="hidden" id="rowIndex">

        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-900 to-indigo-800 text-white p-6 rounded-t-lg shadow-lg">
          <div class="flex justify-between items-center">
            <div>
              <h1 class="text-3xl font-bold">DISBURSEMENT VOUCHER</h1>
            </div>
            <div class="text-right">
              <div class="bg-white bg-opacity-20 px-4 py-2 rounded-lg">
                <p class="text-sm font-medium">Voucher Date</p>
                <input type="date" id="date" class="bg-transparent border-b border-white text-white placeholder-blue-200 text-lg font-bold w-40 text-center">
              </div>
            </div>
          </div>
        </div>

        <!-- Form Body -->
        <div class="bg-white shadow-lg rounded-b-lg p-6 space-y-6">

          <!-- Basic Info -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
              <select id="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="" disabled selected>— Select —</option>
                <option>INCENTIVES</option>
                <option>PAYROLL</option>
                <option>MATERNITY</option>
                <option>MEALS (COMPANY PAID)</option>
                <option>RENTALS</option>
                <option>ELECTRIC & WATER</option>
                <option>COMMISSION</option>
                <option>REVOLVING FUND EXPENSES</option>
                <option>CREDIT CARD EXPENSES</option>
                <option>FINAL PAY</option>
                <option>WITHHOLDING TAX</option>
                <option>PERMITS AND RENEWALS</option>
                <option>HDMF REMITTANCES</option>
                <option>PHIC REMITTANCES</option>
                <option>SSS REMITTANCES</option>
                <option>INTERNET LINE</option>
                <option>PURIFIED WATER</option>
                <option>DATA/LEADS</option>
                <option>RECONSTRUCTION LABOR</option>
                <option>COMMUNICATION EXPENSE</option>
                <option>ACCOUNTING SERVICES</option>
                <option>TAX COMPLIANCE SERVICES</option>
                <option>LEGAL SERVICES</option>
                <option>IT REQUESTS</option>
                <option>MARKETING EXPENSE</option>
                <option>CASH ADVANCES</option>
                <option>DISTRO/DIVIDEND</option>
                <option>INSURANCES</option>
                <option>ELECTRICAL LABOR & MATERIALS</option>
                <option>COMPANY LOAN</option>
                <option>DISPUTE/RECONSIDERATION</option>
                <option>AIRCON CLEANING</option>
                <option>LICENSE RENEWAL</option>
                <option>REFUND</option>
                <option>TEAM BUILDING</option>
                <option>ACCOUNTING SERVICES (BIR 2316)</option>
                <option>SSS SICKNESS BENEFIT</option>
                <option>BUSINESS INTELLIGENCE SERVICES FEE</option>
                <option>FUND TRANSFER</option>
                <option>AIRCON LABOR & MATERIALS</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Check No.</label>
              <input type="text" id="checkNo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Invoice</label>
              <input type="text" id="invoice" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>
          </div>

          <!-- Payee Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Payee</label>
              <input type="text" id="payee" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Period Covered</label>
              <div class="flex gap-2">
                <input type="date" id="coverageStart" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <span class="self-center">to</span>
                <input type="date" id="coverageEnd" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
              </div>
            </div>
          </div>

          <!-- Descriptions -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">DV Description</label>
            <input type="text" id="dvDesc" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Particulars</label>
            <input type="text" id="particulars" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
          </div>

          <!-- Line Items -->
          <div class="bg-purple-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Line Items</h3>
            <div class="overflow-x-auto">
              <table id="itemsTable" class="w-full border border-gray-300 rounded-lg">
                <thead class="bg-gray-100">
                  <tr>
                    <th class="px-4 py-2 text-left">Description</th>
                    <th class="px-4 py-2 text-left">Amount</th>
                    <th class="px-4 py-2 text-center">Action</th>
                  </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
              </table>
            </div>
            <button type="button" onclick="addRow()" class="mt-3 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-semibold">Add Item</button>
            <div class="flex justify-end mt-4 space-x-4 items-center">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Currency</label>
                <select id="currency" class="px-4 py-2 border border-gray-300 rounded-lg">
                  <option value="PHP">PHP</option>
                  <option value="USD">USD</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Total</label>
                <input type="text" id="total" readonly class="px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 font-semibold">
              </div>
            </div>
          </div>

          <!-- Payment Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Date</label>
              <input type="date" id="paymentDate" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Account #</label>
              <select id="account" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                <option value="" disabled selected>— Select —</option>
                <option value="RCBC-JALANDONI">RCBC-JALANDONI</option>
                <option value="RCBC-LAPAZ">RCBC-LAPAZ</option>
                <option value="SEC BANK-GCAMP">SEC BANK-GCAMP</option>
                <option value="SEC BANK-TAX">SEC BANK-TAX</option>
                <option value="METROBANK">METROBANK</option>
                <option value="PAYPAL">PAYPAL</option>
                <option value="RCBC JLDNI-DOLLAR">RCBC JLDNI-DOLLAR</option>
                <option value="RCBC-LAPAZ-DOLLAR">RCBC-LAPAZ-DOLLAR</option>
                <option value="BDO-DOLLAR">BDO-DOLLAR</option>
                <option value="BDO-PESO">BDO-PESO</option>
              </select>
            </div>
          </div>

          <!-- Toolbar -->
          <div class="flex justify-end gap-4 pt-6">
            <button type="reset" onclick="resetForm()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-semibold">Reset</button>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold">Save</button>
          </div>
        </div>
      </form>
    </div>

 <!-- Table Page -->
<!-- Filter Bar -->

    <div id="tablePage" style="display:none">
      <div class="flex items-center gap-3 mb-4">
  <label for="monthFilter" class="text-sm font-semibold text-gray-700">Filter by month</label>
  <input type="month" id="monthFilter" class="px-3 py-2 border border-gray-300 rounded-lg">
  <button type="button" 
          onclick="applyMonthFilter()" 
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold">
    Load
  </button>
  <button type="button" 
          onclick="clearMonthFilter()" 
          class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-semibold">
    Current
  </button>
</div>
      <h2 class="text-2xl font-bold mb-4">Voucher Records</h2>
      <div class="bg-white shadow-lg rounded-2xl overflow-hidden p-6">
        <div class="overflow-x-auto">
          <table id="voucherTable" class="stripe hover w-full"></table>
        </div>
      </div>
    </div>
  </div>
  <!-- Delete Warning Modal -->
  <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 modal-backdrop hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 slide-in">
      <div class="p-6">
        <!-- Warning Icon -->
        <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full">
          <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <!-- Content -->
        <div class="text-center mb-6">
          <h3 class="text-xl font-semibold text-gray-900 mb-2">Delete Voucher?</h3>
          <p class="text-gray-600">This action cannot be undone. The record will be permanently removed.</p>
        </div>
        <!-- Buttons -->
        <div class="flex space-x-3">
          <button onclick="closeDeleteModal()" 
            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 py-3 px-4 rounded-lg font-medium">
            Cancel
          </button>
          <button id="confirmDeleteBtn"
            class="flex-1 bg-red-500 hover:bg-red-600 text-white py-3 px-4 rounded-lg font-medium">
            Delete
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>

     window.addEventListener("pageshow", function(event) {
    if (event.persisted) {
      // If the page was loaded from cache (back/forward button), force reload
      window.location.reload();
    }
  });

  function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const content = document.getElementById('mainContent');
  sidebar.classList.toggle('collapsed');
  
  if (sidebar.classList.contains('collapsed')) {
    content.classList.remove('lg:ml-56');
    content.classList.add('lg:ml-16');
  } else {
    content.classList.remove('lg:ml-16');
    content.classList.add('lg:ml-56');
  }
}

function toggleMobileSidebar() {
  const sidebar = document.getElementById('sidebar');
  if (sidebar.classList.contains('-translate-x-full')) {
    sidebar.classList.remove('-translate-x-full');
    sidebar.classList.add('translate-x-0');
  } else {
    sidebar.classList.remove('translate-x-0');
    sidebar.classList.add('-translate-x-full');
  }
}

let table=null;
let pendingDeleteRow=null;

function showPage(id, el){
  document.querySelectorAll('.content>div').forEach(p=>p.style.display='none');
  document.getElementById(id).style.display='block';
  document.querySelectorAll('.sidebar a').forEach(a=>a.classList.remove('active'));
  el.classList.add('active');
  if(id==='tablePage') {
    setMonthPickerToToday(); // ✅ set default once per navigation
    loadRecords();
  }
}


function resetForm(){
  document.getElementById('voucherForm').reset();
  document.getElementById('rowIndex').value="";
  document.getElementById('itemsBody').innerHTML="";
  addRow();
  updateTotal();
}

function addRow(desc='',amt=''){
  const tr=document.createElement('tr');
  tr.innerHTML=`<td><input type="text" value="${desc}" class="px-2 py-1 border rounded w-full"></td>
                <td><input type="number" step="0.01" value="${amt}" oninput="updateTotal()" class="px-2 py-1 border rounded w-full"></td>
                <td class="text-center"><button type="button" onclick="this.closest('tr').remove();updateTotal()" class="text-red-600 font-bold">✕</button></td>`;
  document.getElementById('itemsBody').appendChild(tr);
  updateTotal();
}

function getItems(){
  return [...document.querySelectorAll('#itemsBody tr')].map(r=>({
    desc:r.querySelector('td:nth-child(1) input').value,
    amount:r.querySelector('td:nth-child(2) input').value
  })).filter(x=>x.desc||x.amount);
}

function updateTotal(){
  const total=getItems().reduce((s,i)=>s+Number(i.amount||0),0);
  document.getElementById('total').value=total.toFixed(2);
}

function formatDateForCoverage(dateStr){
  if(!dateStr) return "";
  const d = new Date(dateStr);

  const month = d.toLocaleString("en-US", { month: "short" }); // Sep
  const day = String(d.getDate()).padStart(2, "0");            // 01
  const year = String(d.getFullYear()).slice(-2);              // 25

  return `${month}-${day}-${year}`;
}

// --- Submit Form ---
async function submitForm() {
  const coverageStart=document.getElementById('coverageStart').value;
  const coverageEnd=document.getElementById('coverageEnd').value;

  let coverage="";
  if(coverageStart && coverageEnd){
    coverage = `${formatDateForCoverage(coverageStart)} to ${formatDateForCoverage(coverageEnd)}`;
  } else if(coverageStart){
    coverage = formatDateForCoverage(coverageStart);
  } else if(coverageEnd){
    coverage = formatDateForCoverage(coverageEnd);
  }

  const data = {
    row: document.getElementById('rowIndex').value,  // ✅ always send real row
    category: category.value,
    date: date.value,
    checkNo: checkNo.value,
    invoice: invoice.value,
    payee: payee.value,
    dvDesc: dvDesc.value,
    particulars: particulars.value,
    items: getItems(),
    total: Number(total.value.replace(/[^0-9.-]+/g,"")) || 0,
    currency: currency.value,
    paymentDate: paymentDate.value,
    account: account.value,
    coverage: coverage
  };

  const action = data.row ? 'update' : 'insert';
  const res = await fetch('submitVoucher.php?action='+action,{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(data)
  });
  const json=await res.json();
  if(json.ok){
    showToast("Voucher saved successfully!","success");
    resetForm();
    showPage('tablePage',document.querySelector('.sidebar a:nth-child(2)'));
    await loadRecords();
  } else {
    showToast("Error: " + json.error,"error");
  }
}

async function loadRecords(sheetName = "") {
  let url = 'submitVoucher.php?action=fetch';
  if (sheetName) {
    url += `&sheet=${sheetName}`;
  }

  const res = await fetch(url);
  const json = await res.json();

  if (json.ok) {
    const headers = [
      "Category", "Date", "Check No", "Invoice", "Payee",
      "DV Desc", "Particulars", "PHP", "USD", "Account", "Period", "Action"
    ];

    const rows = json.data.map((r, idx) => {
      const sheetRow = r[11];
      const sheet = r[12];
      return [
        r[0], r[1], r[2], r[3], r[4],
        r[5], r[6], r[7], r[8], r[9], r[10],
        `
          <button onclick="generatePDF(${idx})" class="text-purple-600 hover:text-purple-800" title="Generate PDF">
            <i class="fa-solid fa-file-pdf"></i>
          </button>
          <button onclick="deleteVoucher(${idx},${sheetRow},'${sheet}')" class="text-red-600 hover:text-red-800 ml-2" title="Delete">
            <i class="fa-solid fa-trash"></i>
          </button>
        `
      ];
    });

    if (!table) {
      table = $('#voucherTable').DataTable({
        data: rows,
        columns: headers.map(h => ({ title: h })),
        columnDefs: [{ targets: -1, className: "text-center" }],
        responsive: true,
        pageLength: 10,
        order: [[1, "desc"]],
        autoWidth: false,
        scrollX: true
      });
    } else {
      table.clear().rows.add(rows).draw();
    }
  }
}

function getSheetName() {
  const d = new Date();
  const year = String(d.getFullYear()).slice(-2); // "25"
  const month = d.toLocaleString("en-US", { month: "short" }); // "Sep"
  return `${year}-${month}`; // "25-Sep"
}

function deleteVoucher(tableIdx, sheetRow, sheetName) {
  pendingDeleteRow = sheetRow;    // ✅ actual sheet row index
  pendingDeleteSheet = sheetName; // ✅ actual sheet name
  console.log("Initiating delete for Sheet:", pendingDeleteSheet, "Row:", pendingDeleteRow);

  document.getElementById('deleteModal').classList.remove('hidden');
  document.getElementById('deleteModal').classList.add('flex');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
  if (!pendingDeleteRow || !pendingDeleteSheet) {
    showToast("Error: No row or sheet specified", "error");
    return;
  }
  console.log("Sending delete request for Sheet:", pendingDeleteSheet, "Row:", pendingDeleteRow); // Debug
  closeDeleteModal();

  try {
const res = await fetch(
  `submitVoucher.php?action=delete&row=${pendingDeleteRow}&sheet=${encodeURIComponent(pendingDeleteSheet)}`,
  { method: 'GET' }
);
const json = await res.json();

if (json.ok) {
  showToast("Voucher deleted successfully", "success");
  await loadRecords(); // Refresh table
} else {
  console.error("Delete error:", json.error);
  showToast(`Error: ${json.error}`, "error");
}

  } catch (err) {
    console.error("Fetch error:", err);
    showToast(`Network error: ${err.message}`, "error");
  }

  // Reset
  pendingDeleteRow = null;
  pendingDeleteSheet = null;
});

// Toast Functions
function showToast(message, type = 'success') {
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = `toast-enter bg-white border-l-4 ${type==='success'?'border-green-500':'border-red-500'} rounded-lg shadow-lg p-4 max-w-sm`;
  toast.innerHTML = `
    <div class="flex items-center">
      <div class="flex-shrink-0">
        ${type==='success'
          ? '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
          : '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>'}
      </div>
      <div class="ml-3"><p class="text-sm font-medium text-gray-900">${message}</p></div>
      <div class="ml-auto pl-3">
        <button onclick="removeToast(this)" class="text-gray-400 hover:text-gray-600">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>`;
  container.appendChild(toast);
  setTimeout(()=>{ if(toast.parentNode) removeToast(toast.querySelector('button')); },4000);
}

function removeToast(button){
  const toast=button.closest('.toast-enter');
  toast.classList.remove('toast-enter'); toast.classList.add('toast-exit');
  setTimeout(()=>{ if(toast.parentNode) toast.parentNode.removeChild(toast); },300);
}

function closeDeleteModal() {
  const modal = document.getElementById('deleteModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

// Close when clicking outside the modal box
document.getElementById('deleteModal').addEventListener('click', (e) => {
  if (e.target.id === 'deleteModal') {
    closeDeleteModal();
  }
});

// ✅ Helper: Convert number to words
function numberToWords(num) {
  if (num === 0) return "Zero";

  const ones = ["","One","Two","Three","Four","Five","Six","Seven","Eight","Nine"];
  const teens = ["Ten","Eleven","Twelve","Thirteen","Fourteen","Fifteen","Sixteen","Seventeen","Eighteen","Nineteen"];
  const tens = ["","","Twenty","Thirty","Forty","Fifty","Sixty","Seventy","Eighty","Ninety"];
  const thousands = ["","Thousand","Million","Billion"];

  function chunkToWords(n) {
    let str = "";
    if (n >= 100) {
      str += ones[Math.floor(n/100)] + " Hundred ";
      n %= 100;
    }
    if (n >= 20) {
      str += tens[Math.floor(n/10)] + " ";
      n %= 10;
    } else if (n >= 10) {
      str += teens[n-10] + " ";
      n = 0;
    }
    if (n > 0) {
      str += ones[n] + " ";
    }
    return str.trim();
  }

  let words = "";
  let chunkCount = 0;
  while (num > 0) {
    const chunk = num % 1000;
    if (chunk) {
      words = chunkToWords(chunk) + " " + thousands[chunkCount] + " " + words;
    }
    num = Math.floor(num / 1000);
    chunkCount++;
  }
  return words.trim();
}

// ✅ PDF Generation with Amount in Words
function generatePDF(idx){
  const row = table.row(idx).data();
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ unit: "pt", format: "letter" });
  const pageWidth = doc.internal.pageSize.getWidth();
  const margin = 50;

  // Set your logo (Base64 or image path)
let logo = "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAQ3BDcDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD6pooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiq9zfWlqu65uoIl9XkA/nWDf+PfCtgcXWvWCN6CUE/pQB01Fee33xk8DWakvrcUhHaJSxrnr39obwZBnyDe3BH92HH86pRb2QN23PY6K8CuP2l9CUn7PpN849WIFUZ/2nLEIfI0Gdm7bpQBTVKb6C5l3Pouivlmf9pzUix8jQbVR23Ssf6VWk/aY17nZpFgPqWP9ar2FTsJySPq+ivkd/wBpXxP203TR+DUg/aU8U/8AQP03/vlv8afsJ9he0ifXNFfJKftLeJv4tL00/wDfVWYv2mdbz8+j2LfRmFL2M+wc8T6tor5fi/acvBjzdAgP0mI/pV63/aeiJHn+HmA7lLgH+lJ0proUpJ9T6Sorwi2/aV8NsB9o0zUIz3xtP9a1rP8AaF8FT485723z/fgJ/lSdOS3QXT6nsNFedWXxo8C3eAutpGT2lRl/pW/YePPC1+B9l12wc+hlAP61LTW4zpqKr219aXKhre5glU9Cjg1YpAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUVT1LVLHTITLqN5BbRjq0rhR+tAFyivMPEPxx8FaOHVNQa9mX+C2Qn9eleXeI/2l7ty6aBpEcQ7SXDbj/3zxVxpylshNpbs+oKy9V8Q6RpKFtS1K0th1/eSgH8q+J/EXxg8aa5uWbV3t4T/wAs7cbB+Y5rhbu8ubyQyXU8sznq0jlj+taxw0n8WhDqq2h9t638bvBGlocap9rkH8Fupb9eleea5+03bIWXRtDkkHZ55MfoBXzBRWyw0V1IdV9j2nVv2ivF12CLOOzs17FU3H9a4vU/ij4z1It9o1+7AbqEbaP0FcVRWsaUeyREqj6Nly71S/u2LXV7czE9d8hNVCSTknJpKKrljEXNJhRRRVCCiiigQUUUUDCiiigQUUUUAFFFFABRRRQAUAkHIOKKKBly21O/tWBtry4iI6bJCK6fSvif4x0sr9l1+82r0WRy4/WuMoqHTg9Ghqck7pnt2i/tG+KbPA1G3s75B1O3Y1d1on7TGmTMq6vo1xb+rQvv/Q4r5XorOWHg9tC1Vl1PufRvjP4I1TaqaukDn+GdSmPx6V22m63pepoH0/ULW4U8/u5Qa/OKrFpfXVnIHtLmaFx0Mblf5Vk8KukilW7o/SWivg7Qvi3400Zl+z61cTRL/wAs5/nH6ivTvDv7S99Fsj13SYpx0MkL7W/755rOWHmvM0U4vqfUdFeXeH/jn4K1YxpJfPZTN/DcIQAf97pXounapY6nCsun3kFzG3IaJw1YNW0ZRcooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKRmVFLMQqjqScAUALRXn3jH4u+EvC4kSe/W6u1/5YW3znPoT0FeD+NP2ide1Nnh8PQx6ZbkYDt80p/w/KtIUpz+FCckt2fVmq6tp+k27T6neQWsK9WlcLXk/iv9oPwtpJeLSxNqk694xtTP1NfJWta9quuTmXVr+4unJz+8ckflWZXRHC2+NmTq3+FHs3ij9oPxVqodNN8nTIG4Hlrl8f7xrynVta1PV5ml1O+ubqRuplkLVn0VvGlTjsrGbnKQUUUVoQFFFFABRRmkoKFopKM0ALRSUZoAXNFMzRmpuA6im0UXAdRTaKLgOoptFFwHUU2ii4DqKbRmi4D6M0zNGaLgPopM0VQC0UlFAhaKSlzQMKKKKCQooooAKv6XrGpaVMJdNvri2cdDFIVqhRUyjGXxFRk47Hs3hL9oLxTpGyLVRFqkC8HzBtfH+9Xsvhj9oHwlqoRNRabTZzx+9XK5+or40orKeHg9lr5Gkar+0fo/pWr6fq9us+mXkF1EwyGicNV6vzk0TXtU0O5E+kX9xayDvG5H6V7J4J/aJ1zTCsHiOBNStxx5gwsg/wAfyrnlhpo0VSLPreivP/Bvxc8J+Kikdrfi2um/5YXPyH8D0P4V36srqGUhlPQg5rBq2jLFooopAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRWdruuaZoNm11q97DawqPvSNjP0HegDRqnquqWOk2rXOpXcNtAoyXlYKK+fviF+0XBEklr4NgMj9Ptcy8D3C/wCN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qtGbRqNb6n6P0V4R8I/jtZa2sGmeKWS01A4RLjP7uU+57GvdkZXQMjBlIyCOhrilFxdmbJ31QtFFFSMKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKK5jx/420jwRo73urTqHIIhgU/PK3oB/WhK4GzrWrWOiadNfapcx29rENzO5xXyf8X/jdfeJHl03w40lppIyGkBxJN9fb2rh/iV8RtZ8d6gz38pjslJ8q2Q/Ii+vua4iu2jh18UzGdT7MQJJJJOSaKKK6jEKKKSgAoptNY1Nxjs0xjQxrY8K+GtY8VammnaBZS3dyeuwfJGPVz90D61EpD5TFY16b8NPgz4j8aeVcyxNpmjvz9suE5kX/pmnV/rwPevdfhf8A9H8NGG/8SNFrGrDBCMn+jwn2B++fc/lXuSjArlnX7Gkafc4fwD8N/DvgW2C6LZK98y7ZL2f553/AOBfwj2GBXc0UVzt3NgooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAOH8ffDfw746tiutWSreKu2O9g+SdP8AgXcexyK+UfiV8GvEXgvzrqKFtT0ZOftdunMa/wDTROo+vI96+5qRhkVpCo4EOCZ+Zqmnqa+vvid8BNH8TGa/8OGPR9WPJQL/AKPMfcD7h/2h+VfK/inw3rHhTVX07X7KW0uV6bx8kg9UP3SPpXXCqpmTg0ZNOqMGnVtcgfRSUtUAUUUUEgCQQQcEV7l8G/jfdeHFi0rxKXu9LyFSbOXh9vda8NoqJQjNO5Sk42sfpFpWpWmrWMV5p06T20qhldDkEVbr4O+GHxM1jwLqKG3lefTWb97auflK+o9DX2d4G8Y6T4z0dL/SJw3H7yIn54z6EV59Sm6b1OmMlLY6OiiisygooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKK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/q4q76G1NDqKKKwNAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAr4q/ag/5K1ff9e8H/oAr7Vr4q/ag/5K1ff9e8H/AKAK3w/xmdTY8nooor0DmCiiigAooooAKKKKACiiigAr1j9l/wD5K1Y/9e0//oBryevWP2X/APkrVj/17T/+gGorfAyofEfatFFFeWdYUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAVzvjrQ08SeDtZ0dgpa8tXijz/C+Pkb8Gwa6KjtQB+ZTAo5RwQw4KtUi12Xxp0P8A4R74oa/ZImyF7g3MOOmyX5xj6Zx+FcWtelB80TlkSLTqYtPWtRBRRRQSd18E9cOhfErRrkttjklEMnphxj+eK+8wQQCOhr81reVoLiKVCQ0bBgR6g1+iHg3UhrHhXSb8EH7RbI5x67RmuHFRtLm7nRSatY2aKKK5jUKKKKACiiigAooooAKKKKACiiigAooooAK8f/aj1f8As74aPaq2Hvp1ix3wPmP8q9gr5e/a+1cvqmjaQp+WOMzsPcnj+VXTV5JCbsmz5zooor1DkBqY1OprVLGMamNT2qJqljR9C/sc6L5/ibXdZdPltLVLZC3rI2T+kf619YivF/2UdH/s74Wreuqq+pXclwD/ALC4jH/oB/Ovaa86o7yOmGwtFFFSMKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK+Kv2oP+StX3/XvB/6AK+1a+Kv2oP8AkrV9/wBe8H/oArfD/GZ1NjyeiiivQOYKKKKACiiigAooooAKKKKACvWP2X/+StWP/XtP/wCgGvJ69Y/Zf/5K1Y/9e0//AKAait8DKh8R9q0UUV5Z1hRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFF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UAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAlfK/wAVPAupePPjvdWGnIUt0t4DcXLD5Ik2D9favqmqyQxRyySIiq8hBcgct25pwnyO6E1cxPBXhTTfB2iQ6Zo8QSFOXc/fkfuzGukoopN3GFFFFADcUfhTGYIm5ugrkR4303+2/sBPH3fNz8u/0/8Ar1hUxFOnbndrmtOhUq35Fex2dFNVgy5WnVuZBRRRQBzfjTwppvi/Q5dM1iFXhflHH3437MDXz18KvAupeA/jra2Oooz27285t7lR8kybD+vtX1RVVoYpJY5HjRnjJKEjlO3FVGo0nElxu7luiiipKCiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAqGZfMhkT1BFTUjHAoA/MpaetMWnrXpQOWQ9afTFp9aCCiiimI9T/ZrfHxX00eqSD/AMcr7ar4i/Zt/wCSr6X9H/8AQTX27XBif4h00/hCiiiucsKKKKACiiigAooooAKKKKACiiigD4Z/aGXb8Wta92U/oK84r1T9pWLy/ixqLf30Q/8AjteV16dD4F6HLPST9QprU6mtWghrVE1StUTVmxoZX6Z27b4Yz6oDX5mNX6U6PJ5mj2Ev9+CM/morkr9DamaFFFFc5oFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAM9OKazBQSxxSuwVdxPFeV+PfGTO8un6XLx92WVT+grixeLhhYc8zrweDqYupyQDx94z8wvp+lthekkoP6CvOGpc00DFfB4vG1MVU55H6LgMup4OnyRPSvAHjPYyabq0nHSOZj+hr1AYYbhgqa+Z69I+H/jMx+XpurPx0imJ/Q17+U5ttRrP0Z83nOS8t69BeqPVqKarBlyvNOr6o+TCiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACobg7YJD6Iamqhq8nk6Tfy/3Ldz+QNAH5sLT1pi09a9KByyHrT6YtPrQQUUUUxHqf7NIz8VtN9kk/wDQa+2q+MP2XY9/xQgP923d/wBK+z68/EfGdUPhQUUUVgUFFFFABRRRQAUUUUAFFFFABRRRQB8Y/tSRbPihM39+3jNeQV7b+1nB5fxFtXH/AC0s1b8iRXiVelQ+BHLV+IKa1OprVqIa1RNUrVE1ZsaGNX6OeCJvtPgzQZh/y0sIH/OMV+cbV+g/whn+0fC7wpJ1xpkCf98oB/SuSvsbQOyooornNAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAGenFNZgoJbildgq7ieK8p8feMmdpbDS5ePuySoensK4sXi4YWHPM68Hg6mLqckB3j3xn5nmadpb4HSSUH9BXnBpc01Rivg8bjZ4ufNI/RcBgKeCp8sR1FFFcB6IUlLRVA1c9H+H/AIz2Mmm6tJx0jmY/oa9S4YblwwNfMv8AOvSPh/4zKeXp2rPx92OZj+hr6zKc22o1n6M+KznJeW9egvVHq9FNVgy5FOr6o+TCiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooASsPxtL9m8Ga9N/zzsJ3/KNq3K5D4uy/Z/hd4sfpnTJ0/76Qj+tC3Bn58rT1pi09a9KJysetPpi0+tBBRRRTJPav2To9/xHnP8Ads2b9RX2HXyT+yJAX8canN2jssfmwr62rzq/8RnXD4UFFFFYlBRRRQAUUUUAFFFFABRRRQAUUUUAfKf7X9vt8T6NPj71sV/In/Gvn6vp39sS0BtPD92ByryRn8dtfMVd+H+A5qvxBSUtJXQSMaomqVqjaoY0RtX3d+zvc/a/g14bfukckZ/4BM6/0r4RavtD9k27+1fClYs/8et9ND/J/wD2euWvsaQ3PaaKKK5TYKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAGenFNZlUMWpXYKNxIxXlPj/wAZF3fT9Nk4+7JKh/QVx4zGQwsOaR14PB1MXU5IDvH3jPzPM07S32gcSSj+Qrzg0f71IoxXwWNxs8XPmkfouAwFPBU+WI6iiiuA9EKKKKACiiigApKWiqBq56P8P/Gewpp2qPx0jmbt7GvUuGG4cqa+Zf516N4C8amLy9O1aT5Okcp7exr6zKc32o1n6M+LznJbXr0F6o9ZopiMHXctPr6o+SCiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigBK82/aHuPsnwb8SOOrxxxj/gcyL/WvSa8X/ayu/svwpaLP/H1fQw/zf8A9kpw3QnsfGS09aYtSrXpROZirT1pq06tBBRRRQSfSX7HVtm58RXWOixRg/nX03Xgv7IdoE8H6rdY5luwmf8AdUf4171Xl1fjZ2R2QUUUVAwooooAKKKKACiiigAooooAKKKKAPDv2trTzfAFlcgfNDeKPwYEf4V8h19xftFWAv8A4UatgZeApKv4MM/oTXw7XZhXdNdjGqtn3CmtTqSusyGNUbVK1RNUMaI2r6q/Yzvt2geJLDP+ouop8f76Ff8A2nXys1e/fsc3/leNNc0/OFuLBZvqyOB/7UNc9b4S4bn11RRRXGbhRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFADPTimsyqGLUrsFG4kYrynx/4yLvJp+mvx92SVD+grjxmMhhYc0jrweDqYupyQH+PfGfm+Zp+lvtHSSUH9BXm7etH+9SKMV8FjcbPFz5pH6LgMBTwVPliOooorgPRCiiigAooooAKKKKACiiigAoooqgPQvAHjLyCmnarJ+76RzMensfavVgQw3LhlNfM3869D8A+MzB5enaq/7vpHMe3sfavq8pza1qNZ+jPjM5yXevQXqj1yimIyuuV+7T6+rPkQooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACi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8P3OlanGGilHyP3jbsw9xV058juTKPMrH530ldH478KX/g3xDc6XqMRBjb924HyyJ2YVztelCamro5ZRcXZjGpjCpabQxkLCmMKmYUxhUtDRHX07+zl8YS/2bwl4puPn4i0+9kb73pC5/kfw9K+Y2FJ0NYzhzIuMrH6c0tfOP7Pnxl/tcQeF/Flx/wATMfu7O9kb/j59Ec/3/Rv4vr1+je1cck4uxsncWiiikMKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAEr5i/aN+MJQ3HhLwtcfPzHf3sbdP70KH+Z/D1rQ/aE+Mo0gT+F/CU3/EyO6K8vY2/49v7yIf7/q38P16fKn3j71vTp9WZzn0BRT1FCinqK6ooyYKKeooUU6tEiQpaKKoAr1j4C/DSXxpri39+hXRbNw0hP/LU9Qg/rXLfDLwTfeOvEcOn2ilLYHdcTY4jTv8AjX3V4Z0Kx8N6LbaZpcIitoFCjHVj3J9Sa5sRWsuSPU1hDW726GjDEkMSRRKEjQBVVRgAU+iiuE2CiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigDh/ix8PbDx9oJtp8RX8ILW1wByp9D7Gvh7xHol94c1m60zVIXhuoG2MvYjsR7Gv0ZrzH40/C+08daU1zbKsOtQIfKlA/1g/uN/Q9q2pVeTR7EShzHxBSVZ1GyuNOvZrS9iaG4iYq6MMEEVXr0I+8c8vdGMKYwqWmsKTQELCmMKmYUxhWbQ0MBIKlSQy9DX1X8AfjUNXEHhrxhcAal8sVpfSN/wAfP91HP9/0P8X16/KjCk6Gs5wUi4ysfpzS18wfAX44mRrbw542uMudsVpqMp6/3UmP8n/P1r6erjlFpm0XcWiiikMKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK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hSMKlptS0BCwprCpmFIwqWh3IcUlSMKawqLFFnStTvtIvo73S7yezuo/uSwSFHH4ivfPh/8AtJ6hZ+VaeNLP7fB937bagJMPqn3X/DH41884pMVDgnuNSsfon4P8aeH/ABfZ/aNA1KC7AGXjBxJH/vIeRXTV+Zthe3enXkV3p9xPa3MZ3JNC5R0b2Ir2vwL+0Z4i0fy7fxLDHrdmOPN4jnX8RwfxGfeud0X0NFM+yKK888EfFnwh4v8ALj07VFt75/8Al0vP3M270GeH/wCAk16HWTViwooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAEzS15543+LPhDwf5seo6otxfJ/wAuln++l3ehx8qf8CIr568c/tGeItYMkHhuFdGsz8vmf6ydvxPA/AZ96pU3ITkkfUPjDxp4f8IWn2jX9UgtARlI2OZH/wB1Bya+c/iB+0nqF4ZbTwTa/YIPu/bboB5T9E+6n45/Cvn+/vbvUbyW71C5nurqQ7nmmcu7t7k1DiuiFFIzcy1qmpX2r3sl7qV5PeXUn35p5C7n8TVXFKopyiteUzuNUU5RSgU5RWlhXBRSgU7FFOwgxTqKWrsAUVpeH9D1LxBqMdjpFpJc3LnAVB09yewr6h+F/wAANP0gQah4rYXt8MMLZf8AVIff+8f0rKpVjTVupUYuTv0PIfhT8HNY8Zypd3oew0YHJmkGHf2Qf1r658G+EdI8IaWljotqkSD78hGXc+pNbsUaRRrHEqoijAVRgAU6uGpVlPc3jFRCiiisygooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAOS8e/D/QfG1k0Or2q+eBiO5jAEifj3Hsa+UPiX8GNf8AB8klxbxtqWlDlZ4V5Uf7a9vrX25SMqupVwGU8EEZBrSnVlDYmUVI/NMjBwetJX2d8RfgT4f8S+bd6SBpWpNk5iH7tz7r2/Cvl7xv8P8AxB4Ounj1ewkEIPFxGu6Jx7N/jXZCvGe+hjKEltqchRin0mK2II9tMYVLRipsBCwpuKm20zbSsO5HikxUjCm4qbFDa7/wb8XvGPhMJFZ6q93ZJ/y63372Pb/dGfmH4EVwOKMVEocw1I+tvB/7S2hahsg8T2M+kzHgzw/v4frx84/I17P4d8SaN4htjPoWq2uoR9/IkDFPqOo/GvzhqeyvbvT7lLmwuJ7W5j+ZJoZCjj6EVjKiuhamfpnSV8Q+Fvj7420LZHd3cOsWw/gvky+P99drfnmvYPDP7S3hy/VYtesb3SZT1kX/AEiEfiMP/wCO1nKnJDU0e/0Vznh3xf4e8TRhtA1qxvWPPlxyjzF+qfeH4iujrMsKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigBuKWuc8Q+MPDvhmInX9bsbNgufLllHmH6IPmP4CvJPE37S3hvT1eHQLK91eZejv+4iP4nL/APjtNQb2FzJHv1YniLxJo3h62FxrmqWunx9vPkCF/oOp/CvjrxV8f/G2ub47S6h0e2PGyxjw+P8Afbc35Yry29vbu/uXub64nurl+XmmkLu31LVqqL6kuZ9YeMf2ldC0/fB4YsbjVphwJ5v3EP15+c/kK8H8ZfF7xj4s3xXmqPaWT/8ALrY/uo8f3Tj5j+JNcBilxW0aSRDm2JS4pcU7FXYkbinKKUCnba0SJuNApyil207FOwrjdtOp2KKdgDFLRXbeAvhp4i8aTIdNs3jtCebqVdsaj69/wonNQV2OMXLRHFKrOwVQWY8AAcmvY/hp8Cdb8TiK91rdpemNz84/eOPZe31Ne7fDf4L+H/CAjublBqWpgZ8+dcqp/wBla9TAAGAMAVyVMQ3pE1jTtqznfBng3RfB2nC00SzSEfxykZdz6k10VFFcrdzXYKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAqK7toLyB4LqGOaFxhkkUMCPpUtFAHgnxF/Z50zUkmu/CUgsLw/N9nkOYmP16ivm3xT4N17wtcPDrem3FuFP32XKN9GHBr9DarahYWmo2r21/bRXEDjDRyqGB/Ot6deUN9SJQufm3SYr648d/s76Nqnm3PhqdtNuTyIW+aJj/ADFfO/jL4deJvCMzrqumym3HSeJd8bD6jp+NdcK8J+RlKEl5nG0Yp9JitCCPbSbafRipsBFtpMVJtpNtKwyLFJipNtJipsFxmKSpMU3FKxQiEo4dCQw5BWu38O/Fbxt4f2Lp/iC9eFf+WV0fPTHph9238K4jFGKlw5h3PoXQP2n9Yg2Jr2iWd6Pul7WQwP8Akd4P6V6PoP7Rfgm/VEvjf6a56/aLfen5x7v5V8ZYoqHRTHzs/RDSPHXhbXFC6T4h0u4duka3CB/++Dz+ldTX5jVs6N4q8QaHj+x9b1KzUfwQXTov5A4rN0Cuc/SHFGK+FdJ+PHxB07aG1lLuJf4Lq3jf9VCn9a7DTP2n/EUWP7S0PSrkD/ni0kJP5l6j2Mh86PrnFGK+cdM/al0qQD+0vDV9Ae/2e4Sb/wBCCV0mn/tHeBrrHnNqtp/12tQf/QC1T7OXYq6Pa+KSvNLT43/D27A8vxHEjek1vNH/AOhJW1b/ABK8FXS/uvFmiL/10vET/wBCIpcrC52VFYcHizw7cf8AHvrukzf7l7G39auxanYy/wCqv7R/92ZT/WkMv0VEs8TdJEP0NP3DGc8UAOoqJp4l6yIPqaqy6nYxf62/tE/3plH9aAL+aM1hz+LPD1v/AMfGu6TD/v3ka/1rKuPiV4KtVzL4s0Rv+ud4j/8AoJNFmB2GKMV5pdfG/wCHlqDv8RxMfSO3mf8AklYV/wDtIeBbXPknVbv/AK42uP8A0MrT5H2FdHtFFfOGpftS6ZGD/Zvhq+nPbz7hIf8A0EPXK6n+1B4imz/Zmh6VbL/02aSYj8ilV7OQc6PrmivhbVvjx8QdR3BdZSzQ/wAFrbxp+rBj+tcRq/irxBreRrGtaleKf4Z7p3X8idtUqLJ5z731rxz4X0Lf/aviDS7Zl6xtcIX/AO+BzXnuvftGeCbBWSwa/wBScDj7Nb7F/OTH8q+MaMVcaCJ9ofQ2v/tP6xPuTw/oVnZD7oe6kac/kNgH615j4i+K3jbxDuTUPEF6kJ/5Y2p8hMemExu/GuIxRitVSiieZgxLuXckk8kmkxTsUYquUQmKXFOxS7aqwrjMU7FP20u2qsK4m2jbT8UU7CG7adinYop2AMUUtdX4P+H/AIk8WzIukabM8Lf8t3XZGv8AwI0SlGGshpN7HKV1PgzwH4h8XzrHomnyyRd5m+WNfqxr6P8AAH7PWj6T5V14ml/tO7GD5Q4iU/zNe3WNnbWFulvZQRQQoMKkahQB+Fc1TFfymipX+I8U+Hn7PmjaN5V34mcaperz5WMRKfp3r221t4bSBIbWJIYUGFRFCgD2AqWiuWUnJ3bNUktgoooqRhRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFMmijnjaOZFkjYYKsMg0+igDyfxr8CvCfiJZJbKA6VeNz5lt90n3Xp+WK+fvHHwN8V+G2kltYP7Usl5EtsMsB7p1/LNfbNFawrSjp0JcEz81JInicpIrI44IYYIplfoD4r+HfhbxSrnVtJt3mYf66MbJPzFeE+Mf2a72FpJvCmpR3EfUW918rj2DDg/pXVDERfkzF02vNHzpim4rpPEngrxD4amdNY0q6gCnHmFCUP0YcVztbRkpESjyjcU3bUmKMUWAi20bafijFKwEW2kxUm2jbSsO5DijFS7aNtFguQ4oxUm2kxU2HcZikxUmKMUWC5HiinYoxSsMbRTsUmKXKAlFLijFHKMSilxRijlASilxRijlASilxS4o5QG0Yp2KMU7CG0uKfijFOwDMUYp+KXbRYVxmKMU/bTttVYVxmKXbT9tG2iwDNtO20/FGKdhXG7adinYop2AbinYopcVQCYpa6Lwx4J8Q+JZkj0fSrmcMceZsIQfVjxXuHg39mt5FSfxZqRTPJt7UDI+rH/Cs51oR6lKEmfOMEMtxKscEbySMcBUGSa9P8HfA/wAXeIRHLPa/2baN/wAtLr5Wx/u9a+svCngLw34WjVdG0q3ikAwZWXc5/wCBHmuorlniW9EaRpJas8i8FfAbwtoAjm1KNtWvF53T/cB9l/xzXrFtbw2sKw20UcUSjCoihQB9BUtFc7k3uaWSCiiikMKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAI54YriJo540kjYYKuMg1514p+CvgzxAXkbTvsVw3/AC1tG2H8ulek0U02tUDVz5W8Vfs16pblpfDmpw3adRFcDY30z0P6V5F4k8A+J/DjuNW0e6iResipvT/vocV+g1NkRJFKyKrKezDIreOJmt9SHTiz80yMHmivvfxD8K/Buu7jeaJbJIf+WkA8ts+vFeV+I/2ZrKXc+gazLB6R3Kb/APx7/wCtW6xMXvoZOk1tqfLlNr1bxJ8CfGmjFnis01CEfx2jZP8A3z1rz7U9A1bSpCmo6dd2zDtLEy/0rRVITV0JxknZmVijFOoqrEke2jbUlGKLAR7aTbT6MUWAZto20+ilYCLbRtqXFN20WGM20bafto20WC4zbRtp+2jbRYLjNtG2n7aNtFguM20baftp2KLBcZto20+iiwhm2l207FGKLAN20bakxRTsA3FFOxRRYBtOpQuTgVraV4c1rVmC6bpV7ck/88oWYfyp8yQGRiivXND+APjXUWVrm2trCMjObiUE4+gzXo3h79mazj2vr2tSzesdsmwfmf8ACspV4x3dxxpt9LHy+FJOACSewrpvD3gDxR4hZP7K0W8lRukjIUT/AL6OBX2l4b+GHhDw9sbT9GtzMvIlmHmNn1yeldmiLGoVFVVHYDArKWK/lRqqXdnyt4W/Zr1W5KSeI9ShtI+pigG9/pnoP1r2Lwv8GPBmgBGXTRe3C/8ALW7O8n8OlekUVzyqyluy1FIjt4IbaJYreJIo1GAqKAAKkoorMoKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACobq0t7uMx3UEUyHgrIgYH86mooA4rWPhb4M1bJutAswx6tEvln/x3FcRrP7OXhO8ZmsZ76yY9FWTeo/OvbKKpTktmKyPlfWv2ZtViJOj61bXA7LOhjP6ZriNT+BfjuwLbdKS6Rf4oJgc/hnNfb9FaxxM1vqQ6UWfnhqPg3xHprML3RNQix1JgbH54rFlhliJEsboR2ZSK/ShkVxh1DD3Gazb/wAP6PqGft2mWc+f+ekKn+lWsS/tIXsl0Z+clFffF98K/BV7nzvD9kuevlps/lXMah+z74IumJhgu7XPaKYkf+PZq1iY9VYTpPoz4uoxX11cfs1eF3JMOo6nH7b1P9KzJ/2Y9LJ/ca9dr/vxKapYmHUn2cuh8sYor6Xl/ZgOD5XiUZ/2rX/7KqU37MWoj/Va/av/AL0JFUq8HuxOkz51or6Bb9mXXP4da08/8AemD9mXXs86zp//AHy9Ht4dw9nI8BoxX0Iv7MmsH72u2I/7Zv8A41On7MN+fv8AiG1X6QE/1pe3gP2bPnTFFfSyfswNgb/Ewz3xan/4qrlv+zDZA/6R4hnYf7EAH9aHiYrYFSb3Pl2ivru3/Zr8KoczX+pS+29R/Stq0+AHgaDHm2lzPj+/MR/LFT9Zh0Q/ZPufFdOSF5DhEZj7DNfddh8HvA1iwMWgwOR/z1Zn/ma6nTvDWiaaALDSrK3x/chUf0qXil0TK9ku58Caf4T8QaiR9i0XUJs91t2x+eK7DSvgh461Haf7IFsh/inlC/pnNfcCIiDCKqj2GKdWcsTJ7IaprqfKuh/sz6tKwOs6xbW691gBkP64rvtG/Zy8J2hRr+4vr5x94M4RT+AGf1r2yis3Wm+pSgkcdo3wy8HaPg2Xh+xDDo0ke8/mc11sEEUCBIIkjUDACKABUlFQ3fcq1gooopAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFAH//Z"; // example base64

const logoWidth = 40, logoHeight = 40;
const left = 160;         // how far from the left edge
const top = 12;          // top margin

// Logo at left
doc.addImage(logo, "JPEG", left, top, logoWidth, logoHeight);

// Title centered
doc.setFont("helvetica", "bold");
doc.setFontSize(18);
const textY = top + logoHeight * 0.75;           // tweak 0.70–0.85 as you like
doc.text("TRUALLIANT BPO INC", pageWidth/2, textY, { align: "center" });

// Optional address centered below
doc.setFont("helvetica", "normal");
doc.setFontSize(12);
doc.text(
  "3rd floor Fancom Bldg. Huervana St., Burgos, Lapaz, Iloilo City",
  pageWidth/2,
  top + logoHeight + 16,
  { align: "center" }
);

  doc.setFontSize(11);

// Right margin anchor
let rightX = pageWidth - margin;
let headerTop = 50;

// Fixed width for the label column
let labelWidth = 40; // adjust until "DV No.:" and "Date:" align nicely

// Date row
doc.text("Date:", rightX - labelWidth, headerTop, { align: "left" });
doc.text(`${row[1] || ''}`, rightX - labelWidth + 30, headerTop, { align: "left" });

// DV No. row
doc.text("DV No.:", rightX - labelWidth, headerTop + 12, { align: "left" });
doc.text("_______", rightX - labelWidth + 40, headerTop + 12, { align: "left" });



  doc.autoTable({
    startY: 80,
    theme: 'grid',
    styles: { fontSize: 11, cellPadding: 6 },
    headStyles: { fillColor: [240,240,240] },
    body: [
      [`Payee: ${row[4]||''}`, `Tin Number:`],
      [`Address:`, ``]
    ],
    columnStyles: { 0: { cellWidth: 350 }, 1: { cellWidth: 200 } },
    tableWidth: 550, 
  });

  // --- Particulars / Line Items ---
const particularsText = row[6] || "";
const lines = particularsText.split("\n").map(l=>l.trim()).filter(l=>l);
let bodyParticulars = [];

// Detect currency (row[7] = PHP column, row[8] = USD column)
let isUSD = !!(row[8] && !row[7]);

if (lines.length > 0) {
  bodyParticulars.push([ lines[0], "" ]);
}

if (lines.length > 1) {
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i];
    const match = line.match(/^(.*)\s+\(([A-Z]+)\s+([\d,]+(?:\.\d{1,2})?)\)$/);

    if (match) {
      const desc = match[1].trim();
      let amt = Number(match[3].replace(/,/g, "")).toLocaleString(undefined, { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
      });
      // only put $ for USD, PHP stays plain number
      bodyParticulars.push([ desc, isUSD ? `$ ${amt}` : amt ]);
    } else {
      bodyParticulars.push([ line, "" ]);
    }
  }
}

// ✅ Force TOTAL to always have commas and 0.00 precision
let totalVal = row[7] || row[8] || "0";
let numericTotal = 0;
if (totalVal) {
  numericTotal = Number(totalVal.toString().replace(/,/g, ""));
  totalVal = numericTotal.toLocaleString(undefined, { 
    minimumFractionDigits: 2, 
    maximumFractionDigits: 2 
  });
}

// ✅ Build "Amount in Words"
let pesos = Math.floor(numericTotal);
let cents = Math.round((numericTotal - pesos) * 100);
let totalInWords = numberToWords(pesos) + (isUSD ? " Dollars" : " Pesos");
if (cents > 0) totalInWords += " and " + numberToWords(cents) + " Cents";
totalInWords += " Only";

// Push TOTAL row
bodyParticulars.push([
  { content: "TOTAL AMOUNT", styles: { fontStyle: "bold" } },
  { content: isUSD ? `$ ${totalVal}` : totalVal, styles: { fontStyle: "bold", halign: "right" } }
]);

// ✅ Push Amount in Words row (spans both columns)
bodyParticulars.push([
  { content: `Amount in Words: ${totalInWords}`, colSpan: 2, styles: { fontStyle: "italic", halign: "left" } }
]);

// --- Particulars Table ---
doc.autoTable({
  startY: doc.lastAutoTable ? doc.lastAutoTable.finalY + 15 : 150,
  head: [['PARTICULARS','AMOUNT']],
  body: bodyParticulars,
  styles: { fontSize: 11, cellPadding: 6 },
  headStyles: { fillColor: [200,200,200], textColor: 20, halign:'center' },
  columnStyles: { 0: { cellWidth: 400 }, 1: { halign:'right', cellWidth: 150 } },
  tableWidth: 550
});


  // Example signature image
  const sigImg = "data:image/png;base64,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";
doc.autoTable({
    startY: doc.lastAutoTable.finalY + 15,
    body: [
      [
        { content:'C. Certified', colSpan:2, styles:{ fontStyle:'bold'} },
        { content:'D. Approved Payment', colSpan:2, styles:{ fontStyle:'bold'} }
      ],
      ['Signature','','Signature',''],
      ['Printed Name','DIOGILYN QUIRAO','Printed Name','QUIRAO, JOHN MAR'],
      ['Position','FINANCE MANAGER','Position','CHIEF EXECUTIVE OFFICER'],
      ['Date','','Date','']
    ],
    styles: { fontSize: 11, cellPadding: 3, halign:'center' },
    theme: 'grid',
    tableWidth: 550,
    didDrawCell: function (data) {
      // Target: row 2 (Printed Name), column 1 ("DIOGILYN QUIRAO")
      if (data.row.index === 2 && data.column.index === 1 && sigImg) {
        const imgWidth = 120;
        const imgHeight = 60;
        const x = data.cell.x + (data.cell.width - imgWidth) / 2;
        const y = data.cell.y - imgHeight + data.cell.height;
        doc.addImage(sigImg, "PNG", x, y, imgWidth, imgHeight);
      }
    }
  });

  doc.autoTable({
    startY: doc.lastAutoTable.finalY + 15,
    body: [
      [{ content:'E. Receipt of Payment', colSpan:2, styles:{ fontStyle:'bold'} }],
      [`Check No: ${row[2]||''}`,`Bank Name & Account Number: ${row[9]||''}`],
      [{content:'PRINTED NAME AND SIGNATURE:', colSpan:2}]
    ],
    styles: { fontSize: 11, cellPadding: 6, halign:'left' },
    theme: 'grid',
    tableWidth: 550
  });

  doc.save(`Voucher_${row[4]||'record'}.pdf`);
}

document.addEventListener('DOMContentLoaded',()=>addRow());

// Convert <input type="month"> value ("YYYY-MM") -> "yy-Mon" (e.g., "25-Sep")
function monthInputToSheetName(val) {
  if (!val) return "";
  const [yyyy, mm] = val.split("-");
  const d = new Date(Number(yyyy), Number(mm) - 1, 1);
  const year = String(d.getFullYear()).slice(-2);
  const mon = d.toLocaleString("en-US", { month: "short" }); // "Sep"
  return `${year}-${mon}`;
}

// Set default to today once per navigation to table
function setMonthPickerToToday() {
  const el = document.getElementById('monthFilter');
  const d = new Date();
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  el.value = `${yyyy}-${mm}`;
}

async function applyMonthFilter() {
  const val = document.getElementById('monthFilter').value;
  const sheet = monthInputToSheetName(val);
  await loadRecords(sheet);
}

async function clearMonthFilter() {
  setMonthPickerToToday();
  await loadRecords(""); // default current
}

</script>
</body>
</html>

