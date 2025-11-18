<?php
// ไฟล์: includes/db_connect.php

$servername = "localhost";
$username = "root"; // เปลี่ยนเป็นชื่อผู้ใช้ MySQL ของคุณ
$password = "";     // เปลี่ยนเป็นรหัสผ่าน MySQL ของคุณ
$dbname = "ecommerce_ai_project"; // ต้องสร้างฐานข้อมูลนี้ด้วย SQL ด้านบน

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่า Charset เป็น utf8mb4
$conn->set_charset("utf8mb4");
?>