<?php
/**
 * เชื่อมต่อฐานข้อมูลด้วย PDO
 * - บน Railway: อ่านค่าจาก Environment Variables (MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD)
 * - ในเครื่อง (MAMP): ใช้ค่า default ด้านล่าง (MAMP ใช้พอร์ต 8889, user root, password root)
 *   ถ้าใช้ MAMP ให้ตั้ง DB_PORT=8889 และ DB_PASS=root หรือแก้ค่า default ตรงนี้
 */
function getPDO(): PDO
{
    $host = getenv('MYSQLHOST') ?: 'localhost';
    $port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: '3306');
    $user = getenv('MYSQLUSER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD');
    if ($pass === false) {
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
    }
    // ชื่อฐานข้อมูลตามไฟล์ dbNorthwind.sql คือ db_northwind
    $db = getenv('DB_NAME') ?: 'db_northwind';

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    return new PDO($dsn, $user, $pass, $options);
}
