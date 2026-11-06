<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการสินค้า Northwind</title>
    <!-- Bootstrap 5 ผ่าน CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h3 class="fw-bold">ระบบจัดการสินค้า (CRUD ผ่าน API)</h3>
    <p class="text-secondary">ค้นหา เพิ่ม แก้ไข และลบข้อมูลสินค้าในตาราง tb_products ด้วย PHP, PDO และ Prepared Statements</p>

    <!-- พื้นที่แจ้งผลการทำงาน -->
    <div id="alertArea"></div>

    <!-- ฟอร์มค้นหา -->
    <form id="searchForm" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="text" id="qName" class="form-control" placeholder="ค้นหาจากชื่อสินค้า">
        </div>
        <div class="col-md-4">
            <input type="number" step="0.01" min="0" id="qPrice" class="form-control" placeholder="ราคาไม่เกิน (please input price to filter)">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Submit</button>
            <button type="button" id="btnReset" class="btn btn-outline-secondary">ล้างค่า</button>
        </div>
        <div class="col text-end">
            <button type="button" id="btnAdd" class="btn btn-success">+ เพิ่มสินค้า</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
            <tr>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Unit</th>
                <th class="text-end">Price</th>
                <th class="text-center">จัดการ</th>
            </tr>
            </thead>
            <tbody id="tbody"></tbody>
            <tfoot>
            <tr class="table-light">
                <th colspan="5">จำนวนข้อมูลทั้งหมด :</th>
                <th class="text-end" id="totalRows">0 แถว</th>
                <th></th>
            </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Modal เพิ่ม/แก้ไข -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="productForm" class="modal-content" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">เพิ่มสินค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fId">
                <div class="mb-3">
                    <label class="form-label" for="fName">ชื่อสินค้า</label>
                    <input type="text" id="fName" class="form-control" maxlength="30" required>
                    <div class="invalid-feedback" data-for="name"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="fCategory">หมวดหมู่</label>
                    <select id="fCategory" class="form-select" required></select>
                    <div class="invalid-feedback" data-for="category_id"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="fSupplier">ผู้จัดจำหน่าย</label>
                    <select id="fSupplier" class="form-select" required></select>
                    <div class="invalid-feedback" data-for="supplier_id"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="fUnit">หน่วย / บรรจุภัณฑ์</label>
                    <input type="text" id="fUnit" class="form-control" maxlength="30" required>
                    <div class="invalid-feedback" data-for="unit"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="fPrice">ราคา</label>
                    <input type="number" id="fPrice" class="form-control" step="0.01" min="0" required>
                    <div class="invalid-feedback" data-for="price"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal ยืนยันการลบ -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">ต้องการลบสินค้า <strong id="delName"></strong> ใช่หรือไม่?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" id="btnConfirmDelete" class="btn btn-danger">ลบ</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="app.js"></script>
</body>
</html>
