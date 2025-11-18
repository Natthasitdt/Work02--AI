<?php
// ไฟล์: includes/functions.php

/**
 * ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่ และบทบาทตรงกับที่กำหนดหรือไม่
 * @param string $required_role บทบาทที่จำเป็น ('user', 'seller', 'admin') หรือ 'any'
 * @param string $redirect_page หน้าที่จะ redirect ไปหากไม่ผ่านการตรวจสอบ
 * @return bool
 */
function check_login($required_role = 'any', $redirect_page = 'login.php') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . $redirect_page);
        exit();
    }
    
    if ($required_role !== 'any' && $_SESSION['role'] !== $required_role) {
        // หากสิทธิ์ไม่ตรง ให้ redirect ไปหน้าแรก หรือหน้า Dashboard ที่เหมาะสม
        if ($_SESSION['role'] === 'admin') {
            header("Location: admin/index.php");
        } elseif ($_SESSION['role'] === 'seller') {
            header("Location: seller/index.php");
        } else {
            header("Location: index.php");
        }
        exit();
    }
    return true;
}

/**
 * เข้ารหัสรหัสผ่าน
 * @param string $password รหัสผ่าน
 * @return string hash รหัสผ่านที่เข้ารหัสแล้ว
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * ตรวจสอบรหัสผ่าน
 * @param string $password รหัสผ่านที่ผู้ใช้ป้อน
 * @param string $hash รหัสผ่านที่เข้ารหัสในฐานข้อมูล
 * @return bool
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * สร้าง Alert ข้อความ
 * @param string $type ชนิดของ Alert ('success', 'danger', 'warning')
 * @param string $message ข้อความ
 * @return string
 */
function create_alert($type, $message) {
    return "<div class='alert alert-$type'>$message</div>";
}
?>