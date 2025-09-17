let table = null;
let pendingDeleteRow = null;
let pendingDeleteSheet = null;

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

function showPage(id, el) {
  document.querySelectorAll('.content>div').forEach(p => p.style.display = 'none');
  document.getElementById(id).style.display = 'block';
  document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
  el.classList.add('active');
  if (id === 'tablePage') loadRecords();
}

function resetForm() {
  document.getElementById('voucherForm').reset();
  document.getElementById('rowIndex').value = "";
  document.getElementById('sheetName').value = "";
  document.getElementById('itemsBody').innerHTML = "";
  addRow();
  updateTotal();
}

function addRow(desc = '', amt = '') {
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><input type="text" value="${desc}" class="px-2 py-1 border rounded w-full"></td>
    <td><input type="number" step="0.01" value="${amt}" oninput="updateTotal()" class="px-2 py-1 border rounded w-full"></td>
    <td class="text-center"><button type="button" onclick="this.closest('tr').remove();updateTotal()" class="text-red-600 font-bold">✕</button></td>
  `;
  document.getElementById('itemsBody').appendChild(tr);
  updateTotal();
}

function getItems() {
  return [...document.querySelectorAll('#itemsBody tr')].map(r => ({
    desc: r.querySelector('td:nth-child(1) input').value,
    amount: r.querySelector('td:nth-child(2) input').value
  })).filter(x => x.desc || x.amount);
}

function updateTotal() {
  const total = getItems().reduce((s, i) => s + Number(i.amount || 0), 0);
  document.getElementById('total').value = total.toFixed(2);
}

function formatDateForCoverage(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  const month = d.toLocaleString("en-US", { month: "short" });
  const day = String(d.getDate()).padStart(2, "0");
  const year = String(d.getFullYear()).slice(-2);
  return `${month}-${day}-${year}`;
}

async function submitForm() {
  const coverageStart = document.getElementById('coverageStart').value;
  const coverageEnd = document.getElementById('coverageEnd').value;
  let coverage = "";
  if (coverageStart && coverageEnd) {
    coverage = `${formatDateForCoverage(coverageStart)} to ${formatDateForCoverage(coverageEnd)}`;
  } else if (coverageStart) {
    coverage = formatDateForCoverage(coverageStart);
  } else if (coverageEnd) {
    coverage = formatDateForCoverage(coverageEnd);
  }

  const data = {
    row: document.getElementById('rowIndex').value,
    sheet: document.getElementById('sheetName').value,
    category: category.value, date: date.value, checkNo: checkNo.value, invoice: invoice.value,
    payee: payee.value, dvDesc: dvDesc.value, particulars: particulars.value, items: getItems(),
    total: total.value, currency: currency.value, paymentDate: paymentDate.value, account: account.value,
    coverage: coverage
  };

  const action = data.row ? 'update' : 'insert';
  const res = await fetch('submitVoucher.php?action=' + action, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  const json = await res.json();
  if (json.ok) {
    showToast("Voucher saved successfully!", "success");
    resetForm();
    showPage('tablePage', document.querySelector('.sidebar a:nth-child(2)'));
    await loadRecords();
  } else {
    showToast("Error: " + json.error, "error");
  }
}

async function loadRecords() {
  const res = await fetch('submitVoucher.php?action=fetch');
  const json = await res.json();
  if (json.ok) {
    const headers = [
      "Category","Date","Check No","Invoice","Payee",
      "DV Desc","Particulars","PHP","USD","Account","Period","Action"
    ];
    const rows = json.data.map((r, idx) => {
      const rowIndex = r[11];
      const sheet = r[12];
      r[11] = `
        <div class="flex justify-center items-center space-x-2">
          <button onclick="editVoucher(${idx},'${sheet}',${rowIndex})" class="text-blue-600" title="Edit">
            <i class="fa-solid fa-pen-to-square"></i>
          </button>
          <button onclick="deleteVoucher('${sheet}',${rowIndex})" class="text-red-600" title="Delete">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      `;
      return r;
    });

    if (!table) {
      table = $('#voucherTable').DataTable({
        data: rows,
        columns: headers.map(h => ({ title: h })),
        columnDefs: [{ targets: -1, className: "text-center" }],
        responsive: true,
        pageLength: 10,
        order: [[1, "desc"]]
      });
    } else {
      table.clear().rows.add(rows).draw();
    }
  }
}

function editVoucher(idx, sheet, rowIndex) {
  const row = table.row(idx).data();
  showPage('formPage', document.querySelector('.sidebar a:first-child'));
  document.getElementById('rowIndex').value = rowIndex;
  document.getElementById('sheetName').value = sheet;
  category.value = row[0] || "";
  date.value = row[1] || "";
  checkNo.value = row[2] || "";
  invoice.value = row[3] || "";
  payee.value = row[4] || "";
  dvDesc.value = row[5] || "";
  particulars.value = row[6] || "";
  total.value = row[7] || row[8] || "";
  currency.value = row[7] ? "PHP" : (row[8] ? "USD" : "");
  account.value = row[9] || "";

  coverageStart.value = "";
  coverageEnd.value = "";
  if (row[10]) {
    const parts = row[10].split(" to ");
    if (parts[0]) coverageStart.value = new Date(parts[0]).toISOString().split("T")[0];
    if (parts[1]) coverageEnd.value = new Date(parts[1]).toISOString().split("T")[0];
  }

  itemsBody.innerHTML = "";
  addRow();
}

function deleteVoucher(sheet, rowIndex) {
  pendingDeleteRow = rowIndex;
  pendingDeleteSheet = sheet;
  document.getElementById('deleteModal').classList.remove('hidden');
  document.getElementById('deleteModal').classList.add('flex');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
  if (!pendingDeleteRow || !pendingDeleteSheet) {
    showToast("Error: No row/sheet specified", "error");
    return;
  }
  closeDeleteModal();
  const res = await fetch(`submitVoucher.php?action=delete&row=${pendingDeleteRow}&sheet=${encodeURIComponent(pendingDeleteSheet)}`);
  const json = await res.json();
  if (json.ok) {
    showToast("Voucher deleted successfully", "success");
    await loadRecords();
  } else {
    showToast("Error: " + json.error, "error");
  }
  pendingDeleteRow = null;
  pendingDeleteSheet = null;
});

function closeDeleteModal() {
  document.getElementById('deleteModal').classList.add('hidden');
  document.getElementById('deleteModal').classList.remove('flex');
}

function showToast(message, type = 'success') {
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = `toast-enter bg-white border-l-4 ${type === 'success' ? 'border-green-500' : 'border-red-500'} rounded-lg shadow-lg p-4 max-w-sm`;
  toast.innerHTML = `<p class="text-sm font-medium text-gray-900">${message}</p>`;
  container.appendChild(toast);
  setTimeout(() => { toast.remove(); }, 4000);
}

document.addEventListener('DOMContentLoaded', () => addRow());
