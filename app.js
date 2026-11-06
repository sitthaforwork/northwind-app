const API = 'api/products.php';

const tbody = document.getElementById('tbody');
const totalRows = document.getElementById('totalRows');
const alertArea = document.getElementById('alertArea');
const productModal = new bootstrap.Modal(document.getElementById('productModal'));
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
const form = document.getElementById('productForm');
let deleteId = null;

// ป้องกัน XSS ตอนแสดงข้อมูลลงตาราง
function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
}

function showAlert(message, type = 'success') {
    alertArea.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${esc(message)}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
    window.scrollTo({top: 0, behavior: 'smooth'});
}

async function request(url, options = {}) {
    const res = await fetch(url, {headers: {'Content-Type': 'application/json'}, ...options});
    const data = await res.json().catch(() => ({success: false, message: 'ข้อมูลตอบกลับไม่ถูกต้อง'}));
    return {ok: res.ok, status: res.status, data};
}

// ---------- READ ----------
async function loadProducts() {
    const params = new URLSearchParams();
    const q = document.getElementById('qName').value.trim();
    const price = document.getElementById('qPrice').value.trim();
    if (q) params.set('q', q);
    if (price) params.set('max_price', price);

    const {ok, data} = await request(`${API}?${params}`);
    if (!ok) {
        showAlert(data.message || 'โหลดข้อมูลไม่สำเร็จ', 'danger');
        return;
    }
    tbody.innerHTML = data.data.map(p => `
        <tr>
            <td>${p.id}</td>
            <td>${esc(p.name)}</td>
            <td>${esc(p.category_name ?? '-')}</td>
            <td>${esc(p.supplier_name ?? '-')}</td>
            <td>${esc(p.unit)}</td>
            <td class="text-end">${Number(p.price).toLocaleString()}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-warning" onclick="openEdit(${p.id})">แก้ไข</button>
                <button class="btn btn-sm btn-danger" onclick="openDelete(${p.id}, '${esc(p.name).replace(/'/g, "\\'")}')">ลบ</button>
            </td>
        </tr>`).join('') || '<tr><td colspan="7" class="text-center text-secondary">ไม่พบข้อมูล</td></tr>';
    totalRows.textContent = `${data.count} แถว`;
}

async function loadLookups() {
    const {data} = await request(`${API}?lookup=1`);
    const opt = (list, ph) => `<option value="">${ph}</option>` +
        list.map(i => `<option value="${i.id}">${esc(i.name)}</option>`).join('');
    document.getElementById('fCategory').innerHTML = opt(data.categories, '-- เลือกหมวดหมู่ --');
    document.getElementById('fSupplier').innerHTML = opt(data.suppliers, '-- เลือกผู้จัดจำหน่าย --');
}

// ---------- Validation ฝั่งหน้าเว็บ ----------
function clearErrors() {
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
}

function setError(field, msg) {
    const fb = form.querySelector(`.invalid-feedback[data-for="${field}"]`);
    fb.textContent = msg;
    fb.previousElementSibling.classList.add('is-invalid');
}

function readForm() {
    return {
        name: document.getElementById('fName').value.trim(),
        category_id: document.getElementById('fCategory').value,
        supplier_id: document.getElementById('fSupplier').value,
        unit: document.getElementById('fUnit').value.trim(),
        price: document.getElementById('fPrice').value,
    };
}

function validateClient(d) {
    let valid = true;
    if (!d.name) { setError('name', 'กรุณากรอกชื่อสินค้า'); valid = false; }
    else if (d.name.length > 30) { setError('name', 'ชื่อสินค้าต้องไม่เกิน 30 ตัวอักษร'); valid = false; }
    if (!d.category_id) { setError('category_id', 'กรุณาเลือกหมวดหมู่'); valid = false; }
    if (!d.supplier_id) { setError('supplier_id', 'กรุณาเลือกผู้จัดจำหน่าย'); valid = false; }
    if (!d.unit) { setError('unit', 'กรุณากรอกหน่วย/บรรจุภัณฑ์'); valid = false; }
    if (d.price === '' || isNaN(d.price) || Number(d.price) < 0) { setError('price', 'ราคาต้องเป็นตัวเลขตั้งแต่ 0 ขึ้นไป'); valid = false; }
    return valid;
}

// ---------- CREATE / UPDATE ----------
document.getElementById('btnAdd').addEventListener('click', () => {
    clearErrors();
    form.reset();
    document.getElementById('fId').value = '';
    document.getElementById('modalTitle').textContent = 'เพิ่มสินค้า';
    productModal.show();
});

async function openEdit(id) {
    clearErrors();
    const {ok, data} = await request(`${API}?id=${id}`);
    if (!ok) return showAlert(data.message || 'โหลดข้อมูลไม่สำเร็จ', 'danger');
    const p = data.data;
    document.getElementById('fId').value = p.id;
    document.getElementById('fName').value = p.name;
    document.getElementById('fCategory').value = p.category_id;
    document.getElementById('fSupplier').value = p.supplier_id;
    document.getElementById('fUnit').value = p.unit;
    document.getElementById('fPrice').value = p.price;
    document.getElementById('modalTitle').textContent = `แก้ไขสินค้า #${p.id}`;
    productModal.show();
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();
    const d = readForm();
    if (!validateClient(d)) return;

    const id = document.getElementById('fId').value;
    const {ok, status, data} = await request(id ? `${API}?id=${id}` : API, {
        method: id ? 'PUT' : 'POST',
        body: JSON.stringify(d),
    });

    if (ok) {
        productModal.hide();
        showAlert(data.message, 'success');
        loadProducts();
    } else if (status === 422 && data.errors) {
        Object.entries(data.errors).forEach(([f, m]) => setError(f, m));   // error จาก server
    } else {
        showAlert(data.message || 'บันทึกไม่สำเร็จ', 'danger');
    }
});

// ---------- DELETE ----------
function openDelete(id, name) {
    deleteId = id;
    document.getElementById('delName').textContent = name;
    deleteModal.show();
}

document.getElementById('btnConfirmDelete').addEventListener('click', async () => {
    const {ok, data} = await request(`${API}?id=${deleteId}`, {method: 'DELETE'});
    deleteModal.hide();
    showAlert(data.message, ok ? 'success' : 'danger');
    if (ok) loadProducts();
});

// ---------- ค้นหา ----------
document.getElementById('searchForm').addEventListener('submit', (e) => {
    e.preventDefault();
    loadProducts();
});
document.getElementById('btnReset').addEventListener('click', () => {
    document.getElementById('qName').value = '';
    document.getElementById('qPrice').value = '';
    loadProducts();
});

loadLookups();
loadProducts();