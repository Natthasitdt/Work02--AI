<?php
// ไฟล์: login.php

session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// ถ้า Login อยู่แล้ว ให้ redirect ไปหน้า Dashboard ที่เหมาะสม
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/index.php");
    } elseif ($_SESSION['role'] === 'seller') {
        header("Location: seller/index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']); // รับได้ทั้ง username หรือ email
    $password = $_POST['password'];

    if (empty($identifier) || empty($password)) {
        $error = "กรุณากรอกชื่อผู้ใช้/อีเมล และรหัสผ่าน";
    } else {
        // เตรียม SQL statement โดยค้นหาจาก username หรือ email
        $stmt = $conn->prepare("SELECT user_id, username, password_hash, role, is_active FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // ตรวจสอบสถานะการใช้งาน
            if ($user['is_active'] == 0) {
                $error = "บัญชีนี้ถูกระงับการใช้งานแล้ว";
            } elseif (verify_password($password, $user['password_hash'])) {
                // รหัสผ่านถูกต้อง
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect ตามสิทธิ์
                if ($user['role'] === 'admin') {
                    header("Location: admin/index.php");
                } elseif ($user['role'] === 'seller') {
                    header("Location: seller/index.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "ชื่อผู้ใช้/อีเมล หรือรหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $error = "ชื่อผู้ใช้/อีเมล หรือรหัสผ่านไม่ถูกต้อง";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-container { max-width: 400px; margin: 50px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: 600; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <?php include 'includes/header_nav.php'; // แก้ไขให้เรียกใช้ header_nav.php ที่ถูกต้อง ?>

    <div class="form-container">
        <h2>เข้าสู่ระบบ</h2>
        <?php if ($error) echo create_alert('danger', $error); ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="identifier">ชื่อผู้ใช้ / อีเมล:</label>
                <input type="text" id="identifier" name="identifier" required>
            </div>
            <div class="form-group">
                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">เข้าสู่ระบบ</button>
            <p style="text-align: center; margin-top: 15px;"><a href="register.php" style="color: var(--primary-color);">ยังไม่มีบัญชี? สมัครสมาชิก</a></p>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>