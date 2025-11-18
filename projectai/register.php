<?php
// ไฟล์: register.php

session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $shop_name = isset($_POST['shop_name']) ? trim($_POST['shop_name']) : null;

    if (empty($username) || empty($email) || empty($password)) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "รูปแบบอีเมลไม่ถูกต้อง";
    } elseif (strlen($password) < 6) {
        $error = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
    } else {
        $password_hash = hash_password($password);

        // 1. ตรวจสอบ Username และ Email ซ้ำ
        $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows > 0) {
            $error = "ชื่อผู้ใช้หรืออีเมลนี้มีคนใช้แล้ว";
        } else {
            // 2. Insert เข้าตาราง users
            $stmt_user = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt_user->bind_param("ssss", $username, $email, $password_hash, $role);
            
            if ($stmt_user->execute()) {
                $new_user_id = $stmt_user->insert_id;

                // 3. ถ้าเป็น Seller ต้อง Insert เข้าตาราง sellers ด้วย
                if ($role === 'seller' && !empty($shop_name)) {
                    $stmt_seller = $conn->prepare("INSERT INTO sellers (seller_id, shop_name) VALUES (?, ?)");
                    $stmt_seller->bind_param("is", $new_user_id, $shop_name);
                    $stmt_seller->execute();
                    $stmt_seller->close();
                }

                $success = "สมัครสมาชิกสำเร็จ! คุณสามารถเข้าสู่ระบบได้เลย";
                // Redirect ไปหน้า Login หลังจากสำเร็จ
                header("Refresh: 2; url=login.php");
            } else {
                $error = "เกิดข้อผิดพลาดในการสมัคร: " . $conn->error;
            }
            $stmt_user->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-container { max-width: 400px; margin: 50px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: 600; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <?php include 'includes/header_nav.php'; // แก้ไขให้เรียกใช้ header_nav.php ที่ถูกต้อง ?>
    
    <div class="form-container">
        <h2>สมัครสมาชิก</h2>
        <?php 
        if ($error) echo create_alert('danger', $error);
        if ($success) echo create_alert('success', $success);
        ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้:</label>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">อีเมล:</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">คุณต้องการสมัครเป็น:</label>
                <select id="role" name="role" required onchange="toggleShopName()">
                    <option value="user">ผู้ใช้ทั่วไป</option>
                    <option value="seller">ผู้ขาย (Seller)</option>
                    </select>
            </div>
            <div class="form-group" id="shopNameGroup" style="display:none;">
                <label for="shop_name">ชื่อร้านค้า:</label>
                <input type="text" id="shop_name" name="shop_name" placeholder="กรุณาระบุชื่อร้านค้า">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">สมัครสมาชิก</button>
            <p style="text-align: center; margin-top: 15px;"><a href="login.php" style="color: var(--primary-color);">มีบัญชีอยู่แล้ว? เข้าสู่ระบบ</a></p>
        </form>
    </div>

    <script>
        function toggleShopName() {
            const role = document.getElementById('role').value;
            const shopNameGroup = document.getElementById('shopNameGroup');
            const shopNameInput = document.getElementById('shop_name');
            
            if (role === 'seller') {
                shopNameGroup.style.display = 'block';
                shopNameInput.setAttribute('required', 'required');
            } else {
                shopNameGroup.style.display = 'none';
                shopNameInput.removeAttribute('required');
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>