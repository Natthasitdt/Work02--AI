<?php
// ไฟล์: admin/manage_users.php

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_login('admin', '../login.php');

$error = '';
$success = '';

// --- Handle User Actions (Update Role/Status) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'] ?? '';
    
    // ป้องกันการแก้ไข Admin ตัวเอง
    if ($user_id === $_SESSION['user_id']) {
        $error = "ไม่สามารถดำเนินการกับบัญชีผู้ดูแลระบบของคุณเองได้";
    } else {
        if ($action === 'update_role') {
            $new_role = $_POST['new_role'];
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_role, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = "อัปเดตบทบาทผู้ใช้ #$user_id เป็น " . ucfirst($new_role) . " สำเร็จ";
            
            // ถ้าเปลี่ยนเป็น seller ต้องมั่นใจว่ามีข้อมูลในตาราง sellers (อย่างง่าย)
            if ($new_role === 'seller') {
                $shop_name = 'Shop of User ' . $user_id;
                $conn->query("INSERT IGNORE INTO sellers (seller_id, shop_name) VALUES ($user_id, '$shop_name')");
            }
            
        } elseif ($action === 'toggle_status') {
            $new_status = intval($_POST['new_status']); // 0 หรือ 1
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $new_status, $user_id);
            $stmt->execute();
            $stmt->close();
            $status_text = $new_status == 1 ? 'ใช้งาน' : 'ระงับ';
            $success = "อัปเดตสถานะผู้ใช้ #$user_id เป็น $status_text สำเร็จ";
        }
    }
}

// --- Fetch All Users ---
$sql = "SELECT u.user_id, u.username, u.email, u.role, u.is_active, s.shop_name 
        FROM users u 
        LEFT JOIN sellers s ON u.user_id = s.seller_id 
        ORDER BY u.role, u.user_id DESC";
$users_result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .user-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        .user-table th, .user-table td { padding: 12px; border: 1px solid #ddd; text-align: left; font-size: 0.95rem; }
        .role-admin { color: var(--primary-color); font-weight: 700; }
        .role-seller { color: #17a2b8; font-weight: 600; }
        .role-user { color: #555; }
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
        .user-table select { padding: 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <?php include '../includes/header_nav.php'; ?>

    <main class="container">
        <div style="padding-top: 20px;">
            <a href="index.php" class="btn btn-secondary" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> กลับไป Dashboard</a>
        </div>
        <h2><i class="fas fa-users"></i> จัดการผู้ใช้และผู้ขาย</h2>

        <?php 
        if ($error) echo create_alert('danger', $error);
        if ($success) echo create_alert('success', $success);
        ?>

        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อผู้ใช้</th>
                    <th>อีเมล</th>
                    <th>บทบาท</th>
                    <th>ชื่อร้าน (ถ้ามี)</th>
                    <th>สถานะ</th>
                    <th>จัดการบทบาท/สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users_result->num_rows > 0): ?>
                    <?php while($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                            <td><?php echo htmlspecialchars($user['shop_name'] ?? '-'); ?></td>
                            <td>
                                <span class="status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['is_active'] ? 'ใช้งาน' : 'ระงับ'; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="manage_users.php" style="display:inline-block; margin-right: 10px;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <input type="hidden" name="action" value="update_role">
                                    <select name="new_role" onchange="this.form.submit()" <?php echo $user['role'] === 'admin' ? 'disabled' : ''; ?>>
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="seller" <?php echo $user['role'] === 'seller' ? 'selected' : ''; ?>>Seller</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </form>
                                
                                <form method="POST" action="manage_users.php" style="display:inline-block;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <?php if ($user['is_active']): ?>
                                        <input type="hidden" name="new_status" value="0">
                                        <button type="submit" class="btn btn-remove" style="background: #dc3545;" <?php echo $user['role'] === 'admin' ? 'disabled' : ''; ?>>ระงับ</button>
                                    <?php else: ?>
                                        <input type="hidden" name="new_status" value="1">
                                        <button type="submit" class="btn btn-primary" style="background: #28a745;" <?php echo $user['role'] === 'admin' ? 'disabled' : ''; ?>>เปิดใช้งาน</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">ไม่พบข้อมูลผู้ใช้</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
<?php $conn->close(); ?>