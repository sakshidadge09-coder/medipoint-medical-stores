/* =========================================================
   MEDIPOINT — APP LOGIC
   Expects window.MEDIPOINT_CUSTOMERS to be set by the page
   before this file loads.
   ========================================================= */

let customers = window.MEDIPOINT_CUSTOMERS || [];
let selectedCustomerId = null;

/* ---------- SECTION NAVIGATION ---------- */

function showSection(id) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));

  const section = document.getElementById(id);
  const navBtn = document.querySelector('.nav-item[data-target="' + id + '"]');
  if (section) section.classList.add('active');
  if (navBtn) navBtn.classList.add('active');

  const url = new URL(window.location);
  url.searchParams.set('tab', id);
  window.history.replaceState({}, '', url);
}

/* ---------- BILLING: MEDICINE SELECT ---------- */

function updatePrice() {
  const medicine = document.getElementById('bmedicine');
  const selected = medicine.options[medicine.selectedIndex];
  document.getElementById('bprice').value = selected.getAttribute('data-price') || '';
  document.getElementById('bstock').value = selected.getAttribute('data-stock') || '';
}

/* ---------- BILLING: CUSTOMER COMBOBOX ---------- */

function renderCustomerOptions(filterText) {
  const list = document.getElementById('customerList');
  const q = (filterText || '').trim().toLowerCase();

  const matches = q === ''
    ? customers
    : customers.filter(c => c.Name.toLowerCase().startsWith(q));

  let html = '';
  matches.slice(0, 8).forEach(c => {
    html += '<div class="combobox-option" data-id="' + c.Cust_ID + '" data-name="' +
      escapeHtml(c.Name) + '">' + escapeHtml(c.Name) + ' — ' + escapeHtml(c.Phone) + '</div>';
  });

  if (matches.length === 0) {
    html += '<div class="combobox-option" style="color:var(--ink-soft);">No matching customers</div>';
  }

  html += '<div class="combobox-option add-new" id="addNewCustomerOption">+ Add new customer</div>';

  list.innerHTML = html;

  list.querySelectorAll('.combobox-option[data-id]').forEach(opt => {
    opt.addEventListener('click', () => {
      selectedCustomerId = opt.getAttribute('data-id');
      document.getElementById('customerSearch').value = opt.getAttribute('data-name');
      document.getElementById('bcustomer_id').value = selectedCustomerId;
      closeCustomerList();
    });
  });

  const addNew = document.getElementById('addNewCustomerOption');
  if (addNew) {
    addNew.addEventListener('click', () => {
      closeCustomerList();
      openModal('addCustomerModal');
      document.getElementById('newCustomerName').value =
        document.getElementById('customerSearch').value.trim();
    });
  }
}

function openCustomerList() {
  document.getElementById('customerList').classList.add('open');
}
function closeCustomerList() {
  document.getElementById('customerList').classList.remove('open');
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

/* ---------- MODALS ---------- */

function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

/* ---------- ADD CUSTOMER (AJAX) ---------- */

function submitNewCustomer() {
  const name = document.getElementById('newCustomerName').value.trim();
  const phone = document.getElementById('newCustomerPhone').value.trim();
  const msgEl = document.getElementById('newCustomerMsg');

  if (!name || !phone) {
    msgEl.textContent = 'Please enter both name and phone.';
    return;
  }

  const formData = new FormData();
  formData.append('name', name);
  formData.append('phone', phone);

  fetch('add_customer.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        msgEl.textContent = data.message || 'Could not add customer.';
        return;
      }
      customers.push({ Cust_ID: data.cust_id, Name: data.name, Phone: data.phone });
      selectedCustomerId = data.cust_id;
      document.getElementById('customerSearch').value = data.name;
      document.getElementById('bcustomer_id').value = data.cust_id;
      document.getElementById('newCustomerName').value = '';
      document.getElementById('newCustomerPhone').value = '';
      msgEl.textContent = '';
      closeModal('addCustomerModal');
    })
    .catch(() => { msgEl.textContent = 'Network error — please try again.'; });
}

/* ---------- ADD SUPPLIER (AJAX) ---------- */

function submitNewSupplier() {
  const name = document.getElementById('newSupplierName').value.trim();
  const contact = document.getElementById('newSupplierContact').value.trim();
  const address = document.getElementById('newSupplierAddress').value.trim();
  const msgEl = document.getElementById('newSupplierMsg');

  if (!name || !contact || !address) {
    msgEl.textContent = 'Please fill in name, contact, and address.';
    return;
  }

  const formData = new FormData();
  formData.append('name', name);
  formData.append('contact', contact);
  formData.append('address', address);

  fetch('add_supplier.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        msgEl.textContent = data.message || 'Could not add supplier.';
        return;
      }

      const table = document.getElementById('supplier-table');
      const emptyRow = table.querySelector('.empty-note');
      if (emptyRow) emptyRow.closest('tr').remove();

      const row = document.createElement('tr');
      row.innerHTML =
        '<td>' + escapeHtml(data.supp_id) + '</td>' +
        '<td>' + escapeHtml(data.name) + '</td>' +
        '<td>' + escapeHtml(data.contact) + '</td>' +
        '<td>' + escapeHtml(data.address) + '</td>';
      table.appendChild(row);

      document.getElementById('newSupplierName').value = '';
      document.getElementById('newSupplierContact').value = '';
      document.getElementById('newSupplierAddress').value = '';
      msgEl.textContent = '';
      closeModal('addSupplierModal');
    })
    .catch(() => { msgEl.textContent = 'Network error — please try again.'; });
}

/* ---------- GENERATE BILL (AJAX, NO PAGE RELOAD) ---------- */

function submitBill(event) {
  event.preventDefault();

  const errorEl = document.getElementById('bill-error');
  errorEl.textContent = '';

  if (!document.getElementById('bcustomer_id').value) {
    errorEl.textContent = 'Please select a customer first.';
    return;
  }

  const form = document.getElementById('billingForm');
  const formData = new FormData(form);
  const submitBtn = document.getElementById('bill-submit-btn');
  submitBtn.disabled = true;

  fetch('save_bill.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      submitBtn.disabled = false;

      if (!data.success) {
        errorEl.textContent = data.message || 'Could not generate the bill.';
        return;
      }

      const stub = document.getElementById('receiptStub');
      stub.innerHTML =
        '<div class="receipt-title">Bill generated</div>' +
        '<div class="receipt-bill-no">#' + data.bill_no + '</div>' +
        '<div class="receipt-line"><span>Customer</span><span>' + escapeHtml(data.customer_name) + '</span></div>' +
        '<div class="receipt-line"><span>Medicine</span><span>' + escapeHtml(data.medicine_name) + '</span></div>' +
        '<div class="receipt-line"><span>Quantity</span><span>' + data.quantity + '</span></div>' +
        '<div class="receipt-line"><span>Unit price</span><span>\u20B9' + Number(data.unit_price).toFixed(2) + '</span></div>' +
        '<div class="receipt-line"><span>Date</span><span>' + data.date + '</span></div>' +
        '<div class="receipt-line receipt-total"><span>Total</span><span>\u20B9' + Number(data.total).toFixed(2) + '</span></div>' +
        '<a class="btn btn-primary btn-block" style="margin-top:12px;" target="_blank" href="view_bill.php?bill_no=' + data.bill_no + '">Download bill</a>';
      stub.style.display = 'block';

      // Update the medicine dropdown's remaining stock so the next bill in
      // this session reflects the correct number without a page reload.
      const medSelect = document.getElementById('bmedicine');
      const opt = medSelect.querySelector('option[value="' + form.medicine_id.value + '"]');
      if (opt) opt.setAttribute('data-stock', data.remaining_stock);

      form.reset();
      document.getElementById('bcustomer_id').value = '';
      selectedCustomerId = null;
      document.getElementById('bprice').value = '';
      document.getElementById('bstock').value = '';
    })
    .catch(() => {
      submitBtn.disabled = false;
      errorEl.textContent = 'Network error — please try again.';
    });
}

/* ---------- MEDICINE STOCK SEARCH ---------- */

function searchMedicine(txt) {
  const filter = txt.toLowerCase();

  document.querySelectorAll('#stock-table tr').forEach(row => {
    const name = row.cells[0].textContent.toLowerCase();
    row.style.display = name.includes(filter) ? '' : 'none';
  });

  document.querySelectorAll('#stock-cards .med-card').forEach(card => {
    const name = card.getAttribute('data-name').toLowerCase();
    card.style.display = name.includes(filter) ? '' : 'none';
  });
}

/* ---------- INIT ---------- */

document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  showSection(params.get('tab') || 'dashboard');

  const searchInput = document.getElementById('customerSearch');
  if (searchInput) {
    searchInput.addEventListener('focus', () => renderCustomerOptions(searchInput.value));
    searchInput.addEventListener('input', () => {
      selectedCustomerId = null;
      document.getElementById('bcustomer_id').value = '';
      renderCustomerOptions(searchInput.value);
      openCustomerList();
    });
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.combobox')) closeCustomerList();
    });
  }

  const billingForm = document.getElementById('billingForm');
  if (billingForm) billingForm.addEventListener('submit', submitBill);
});
