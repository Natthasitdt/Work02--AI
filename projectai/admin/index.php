<?php
// ไฟล์: admin/index.php

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// ตรวจสอบสิทธิ์: ต้องเป็น Admin เท่านั้น
check_login('admin', '../login.php');

// ดึงข้อมูลสรุป
$total_users = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$total_sellers = $conn->query("SELECT COUNT(*) FROM users WHERE role='seller'")->fetch_row()[0];
$products_pending = $conn->query("SELECT COUNT(*) FROM products WHERE is_approved = 0")->fetch_row()[0];
$total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$total_sales = $conn->query("SELECT SUM(total_amount) FROM orders WHERE order_status = 'delivered'")->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .dashboard-layout { display: flex; gap: 20px; margin-top: 20px; }
        .sidebar { width: 250px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .sidebar a { display: block; padding: 12px; margin-bottom: 8px; color: var(--text-color); text-decoration: none; border-radius: 4px; font-weight: 600; }
        .sidebar a:hover, .sidebar a.active { background-color: var(--primary-color); color: white; }
        .content { flex-grow: 1; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-left: 5px solid; }
        .stat-card h4 { color: #555; font-weight: 400; }
        .stat-card p { font-size: 2rem; font-weight: 700; color: #333; }
    </style>
</head>
<body>
    <?php include '../includes/header_nav.php'; ?>

    <main class="container">
        <h2><i class="fas fa-crown"></i> Admin Dashboard</h2>

        <div class="dashboard-layout">
            <div class="sidebar">
                <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> ภาพรวมระบบ</a>
                <a href="manage_users.php"><i class="fas fa-users"></i> จัดการผู้ใช้ & Seller</a>
                <a href="manage_products.php"><i class="fas fa-clipboard-check"></i> อนุมัติ & จัดการสินค้า</a>
                <a href="manage_orders.php"><i class="fas fa-shopping-basket"></i> จัดการคำสั่งซื้อทั้งหมด</a>
                <a href="../index.php"><i class="fas fa-home"></i> กลับหน้าหลัก</a>
            </div>

            <div class="content">
                <h3>ภาพรวมสถิติ</h3>
                <div class="stat-grid">
                    <div class="stat-card" style="border-left-color: #007bff;">
                        <h4>ผู้ใช้ทั้งหมด (User)</h4>
                        <p><?php echo number_format($total_users); ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #17a2b8;">
                        <h4>ผู้ขายทั้งหมด (Seller)</h4>
                        <p><?php echo number_format($total_sellers); ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #ffc107;">
                        <h4>สินค้ารออนุมัติ</h4>
                        <p><?php echo number_format($products_pending); ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #28a745;">
                        <h4>ยอดขายรวม (Delivered)</h4>
                        <p>฿<?php echo number_format($total_sales, 2); ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: var(--primary-color);">
                        <h4>คำสั่งซื้อทั้งหมด</h4>
                        <p><?php echo number_format($total_orders); ?></p>
                    </div>
                </div>

                <h3><i class="fas fa-clipboard-check"></i> สินค้ารอการอนุมัติล่าสุด</h3>
                <?php 
                $sql_pending = "SELECT p.product_id, p.name, s.shop_name FROM products p JOIN sellers s ON p.seller_id = s.seller_id WHERE p.is_approved = 0 ORDER BY p.created_at DESC LIMIT 5";
                $pending_result = $conn->query($sql_pending);
                ?>
                <table class="product-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>สินค้า</th>
                            <th>ร้านค้า</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_result->num_rows > 0): ?>
                            <?php while($p = $pending_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $p['product_id']; ?></td>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['shop_name']); ?></td>
                                    <td><a href="manage_products.php?action=approve&id=<?php echo $p['product_id']; ?>" class="btn btn-primary">อนุมัติ</a></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4">ไม่มีสินค้ารอการอนุมัติ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>