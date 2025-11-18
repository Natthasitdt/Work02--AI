<?php
// ไฟล์: logout.php

session_start();

// ล้างตัวแปร Session ทั้งหมด
$_SESSION = array();

// ทำลาย Session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect ไปหน้าแรก
header("Location: index.php");
exit;
?>