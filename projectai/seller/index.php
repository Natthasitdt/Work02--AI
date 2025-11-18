<?php
// ไฟล์: seller/index.php

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// ตรวจสอบสิทธิ์: ต้องเป็น Seller เท่านั้น
check_login('seller', '../login.php');

$seller_id = $_SESSION['user_id'];

// ดึงข้อมูลสรุป
$total_products = $conn->query("SELECT COUNT(*) FROM products WHERE seller_id = $seller_id")->fetch_row()[0];
$pending_approval = $conn->query("SELECT COUNT(*) FROM products WHERE seller_id = $seller_id AND is_approved = 0")->fetch_row()[0];
$total_orders = $conn->query("SELECT COUNT(DISTINCT oi.order_id) 
                              FROM order_items oi 
                              WHERE oi.seller_id = $seller_id")->fetch_row()[0];

// ดึงรายการคำสั่งซื้อล่าสุด
$sql_orders = "SELECT o.order_id, o.order_date, o.order_status, SUM(oi.price_per_unit * oi.quantity) AS order_total
               FROM orders o
               JOIN order_items oi ON o.order_id = oi.order_id
               WHERE oi.seller_id = ?
               GROUP BY o.order_id
               ORDER BY o.order_date DESC
               LIMIT 5";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $seller_id);
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();

function get_status_badge_seller($status) {
    // นำฟังก์ชันจาก orders.php มาใช้
    $class = 'badge-secondary';
    if ($status === 'processing') $class = 'badge-warning';
    if ($status === 'shipped') $class = 'badge-info';
    if ($status === 'delivered') $class = 'badge-success';
    if ($status === 'cancelled') $class = 'badge-danger';
    return "<span class='order-badge $class'>" . ucfirst($status) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .dashboard-layout { display: flex; gap: 20px; margin-top: 20px; }
        .sidebar { width: 200px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .sidebar a { display: block; padding: 10px; margin-bottom: 10px; color: var(--text-color); text-decoration: none; border-radius: 4px; }
        .sidebar a:hover, .sidebar a.active { background-color: var(--primary-color); color: white; }
        .content { flex-grow: 1; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-left: 5px solid var(--primary-color); }
        .stat-card h4 { color: #555; font-weight: 400; }
        .stat-card p { font-size: 2.5rem; font-weight: 700; color: var(--primary-color); }
    </style>
</head>
<body>
    <?php include '../includes/header_nav.php'; ?>

    <main class="container">
        <h2><i class="fas fa-store"></i> Seller Dashboard</h2>

        <div class="dashboard-layout">
            <div class="sidebar">
                <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> ภาพรวม</a>
                <a href="manage_products.php"><i class="fas fa-box-open"></i> จัดการสินค้า</a>
                <a href="manage_orders.php"><i class="fas fa-truck-moving"></i> จัดการคำสั่งซื้อ</a>
                <a href="../index.php"><i class="fas fa-home"></i> กลับหน้าหลัก</a>
            </div>

            <div class="content">
                <div class="stat-grid">
                    <div class="stat-card">
                        <h4>สินค้าทั้งหมด</h4>
                        <p><?php echo number_format($total_products); ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #ffc107;">
                        <h4>รออนุมัติ</h4>
                        <p><?php echo number_format($pending_approval); ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #28a745;">
                        <h4>คำสั่งซื้อทั้งหมด</h4>
                        <p><?php echo number_format($total_orders); ?></p>
                    </div>
                </div>

                <h3><i class="fas fa-list-alt"></i> คำสั่งซื้อล่าสุดของร้านคุณ</h3>
                <table class="order-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th># Order ID</th>
                            <th>วันที่</th>
                            <th>ยอดรวม</th>
                            <th>สถานะ</th>
                            <th>ดูรายการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_result->num_rows > 0): ?>
                            <?php while($order = $orders_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $order['order_id']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                                    <td>฿<?php echo number_format($order['order_total'], 2); ?></td>
                                    <td><?php echo get_status_badge_seller($order['order_status']); ?></td>
                                    <td><a href="order_details.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-secondary">ดู</a></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5">ยังไม่มีคำสั่งซื้อสำหรับร้านค้าของคุณ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>