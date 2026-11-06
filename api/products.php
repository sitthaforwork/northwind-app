<?php
/**
 * CRUD API สำหรับตาราง tb_products (ตอบกลับเป็น JSON)
 *
 * GET    api/products.php                      -> รายการสินค้า (ตัวกรอง: ?q=ชื่อ&max_price=ราคาไม่เกิน)
 * GET    api/products.php?id=9                 -> สินค้า 1 รายการ
 * GET    api/products.php?lookup=1             -> รายการ Category และ Supplier สำหรับ dropdown
 * POST   api/products.php        (JSON body)   -> เพิ่มสินค้า
 * PUT    api/products.php?id=9   (JSON body)   -> แก้ไขสินค้า
 * DELETE api/products.php?id=9                 -> ลบสินค้า
 */
require __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** ตรวจสอบข้อมูลสินค้า คืนค่า [ข้อมูลที่ทำความสะอาดแล้ว, รายการ error] */
function validateProduct(array $in): array
{
    $errors = [];

    $name = trim((string)($in['name'] ?? ''));
    if ($name === '') {
        $errors['name'] = 'กรุณากรอกชื่อสินค้า';
    } elseif (mb_strlen($name) > 30) {
        $errors['name'] = 'ชื่อสินค้าต้องไม่เกิน 30 ตัวอักษร';
    }

    $unit = trim((string)($in['unit'] ?? ''));
    if ($unit === '') {
        $errors['unit'] = 'กรุณากรอกหน่วย/บรรจุภัณฑ์';
    } elseif (mb_strlen($unit) > 30) {
        $errors['unit'] = 'หน่วยต้องไม่เกิน 30 ตัวอักษร';
    }

    $supplier = $in['supplier_id'] ?? '';
    if (filter_var($supplier, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $errors['supplier_id'] = 'กรุณาเลือกผู้จัดจำหน่าย';
    }

    $category = $in['category_id'] ?? '';
    if (filter_var($category, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $errors['category_id'] = 'กรุณาเลือกหมวดหมู่';
    }

    $price = $in['price'] ?? '';
    if (!is_numeric($price) || (float)$price < 0) {
        $errors['price'] = 'ราคาต้องเป็นตัวเลขตั้งแต่ 0 ขึ้นไป';
    }

    return [[
        'name'        => $name,
        'unit'        => $unit,
        'supplier_id' => (int)$supplier,
        'category_id' => (int)$category,
        'price'       => (float)$price,
    ], $errors];
}

function readJsonBody(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

try {
    $pdo    = getPDO();
    $method = $_SERVER['REQUEST_METHOD'];
    $id     = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

    $baseSql = "SELECT p.i_ProductID AS id, p.c_ProductName AS name, p.i_SupplierID AS supplier_id,
                       p.i_CategoryID AS category_id, p.c_Unit AS unit, p.i_Price AS price,
                       c.c_CategoryName AS category_name, s.c_SupplierName AS supplier_name
                FROM tb_products p
                LEFT JOIN tb_categories c ON c.i_CategoryID = p.i_CategoryID
                LEFT JOIN tb_suppliers  s ON s.i_SupplierID = p.i_SupplierID";

    switch ($method) {
        case 'GET':
            if (isset($_GET['lookup'])) {
                $cats = $pdo->query("SELECT i_CategoryID AS id, c_CategoryName AS name FROM tb_categories ORDER BY c_CategoryName")->fetchAll();
                $sups = $pdo->query("SELECT i_SupplierID AS id, c_SupplierName AS name FROM tb_suppliers ORDER BY c_SupplierName")->fetchAll();
                respond(200, ['categories' => $cats, 'suppliers' => $sups]);
            }

            if ($id !== null) {
                if ($id === false) respond(400, ['success' => false, 'message' => 'รหัสสินค้าไม่ถูกต้อง']);
                $stmt = $pdo->prepare($baseSql . " WHERE p.i_ProductID = ?");   // Question Mark
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if (!$row) respond(404, ['success' => false, 'message' => 'ไม่พบสินค้า']);
                respond(200, ['success' => true, 'data' => $row]);
            }

            // ค้นหา: ชื่อสินค้า (LIKE) และ/หรือ ราคาไม่เกิน  ใช้ Named Parameters
            $where  = [];
            $params = [];
            $q = trim((string)($_GET['q'] ?? ''));
            if ($q !== '') {
                $where[] = 'p.c_ProductName LIKE :q';
                $params[':q'] = '%' . $q . '%';
            }
            $maxPrice = trim((string)($_GET['max_price'] ?? ''));
            if ($maxPrice !== '') {
                if (!is_numeric($maxPrice)) {
                    respond(400, ['success' => false, 'message' => 'ราคาที่ใช้กรองต้องเป็นตัวเลข']);
                }
                $where[] = 'p.i_Price <= :max_price';
                $params[':max_price'] = (float)$maxPrice;
            }
            $sql = $baseSql . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY p.i_ProductID';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            respond(200, ['success' => true, 'count' => count($rows), 'data' => $rows]);

        case 'POST':
            [$d, $errors] = validateProduct(readJsonBody());
            if ($errors) respond(422, ['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง', 'errors' => $errors]);

            $stmt = $pdo->prepare(
                "INSERT INTO tb_products (c_ProductName, i_SupplierID, i_CategoryID, c_Unit, i_Price)
                 VALUES (:name, :supplier_id, :category_id, :unit, :price)"
            );
            $stmt->execute($d);
            respond(201, ['success' => true, 'message' => 'เพิ่มสินค้าสำเร็จ', 'id' => (int)$pdo->lastInsertId()]);

        case 'PUT':
            if (!$id) respond(400, ['success' => false, 'message' => 'ต้องระบุรหัสสินค้า (id)']);
            $check = $pdo->prepare("SELECT 1 FROM tb_products WHERE i_ProductID = ?");
            $check->execute([$id]);
            if (!$check->fetch()) respond(404, ['success' => false, 'message' => 'ไม่พบสินค้าที่ต้องการแก้ไข']);

            [$d, $errors] = validateProduct(readJsonBody());
            if ($errors) respond(422, ['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง', 'errors' => $errors]);

            $d['id'] = $id;
            $stmt = $pdo->prepare(
                "UPDATE tb_products
                 SET c_ProductName = :name, i_SupplierID = :supplier_id, i_CategoryID = :category_id,
                     c_Unit = :unit, i_Price = :price
                 WHERE i_ProductID = :id"
            );
            $stmt->execute($d);
            respond(200, ['success' => true, 'message' => 'แก้ไขสินค้าสำเร็จ']);

        case 'DELETE':
            if (!$id) respond(400, ['success' => false, 'message' => 'ต้องระบุรหัสสินค้า (id)']);
            $stmt = $pdo->prepare("DELETE FROM tb_products WHERE i_ProductID = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) respond(404, ['success' => false, 'message' => 'ไม่พบสินค้าที่ต้องการลบ']);
            respond(200, ['success' => true, 'message' => 'ลบสินค้าสำเร็จ']);

        default:
            respond(405, ['success' => false, 'message' => 'ไม่รองรับ method นี้']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    respond(500, ['success' => false, 'message' => 'เกิดข้อผิดพลาดของฐานข้อมูล']);
}
