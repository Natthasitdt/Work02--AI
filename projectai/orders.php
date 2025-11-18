<?php
// ไฟล์: orders.php

session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

check_login('user', 'login.php');

$user_id = $_SESSION['user_id'];

// ดึงรายการคำสั่งซื้อของ User นี้
$sql = "SELECT order_id, total_amount, order_status, order_date, shipping_address 
        FROM orders 
        WHERE user_id = ? 
        ORDER BY order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

function get_status_badge($status) {
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
    <title>ประวัติคำสั่งซื้อ</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .order-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .order-table th, .order-table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        .order-table th { background-color: var(--primary-color); color: white; }
        .order-badge { padding: 5px 10px; border-radius: 5px; color: white; font-weight: 600; display: inline-block; }
        .badge-warning { background-color: #ffc107; color: #333; }
        .badge-info { background-color: #17a2b8; }
        .badge-success { background-color: #28a745; }
        .badge-danger { background-color: #dc3545; }
        .order-detail-btn { background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'includes/header_nav.php'; ?>
    
    <main class="container">
        <h2><i class="fas fa-history"></i> ประวัติคำสั่งซื้อ</h2>

        <?php if (isset($_SESSION['success'])) { echo create_alert('success', $_SESSION['success']); unset($_SESSION['success']); } ?>

        <?php if ($orders_result->num_rows > 0): ?>
            <table class="order-table">
                <thead>
                    <tr>
                        <th># Order ID</th>
                        <th>วันที่สั่งซื้อ</th>
                        <th>ยอดรวม</th>
                        <th>สถานะ</th>
                        <th>รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $order['order_id']; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                            <td>฿<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo get_status_badge($order['order_status']); ?></td>
                            <td>
                                <button class="order-detail-btn" onclick="window.location.href='order_details.php?order_id=<?php echo $order['order_id']; ?>'">ดูรายละเอียด</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning" style="margin-top: 20px;">
                <i class="fas fa-info-circle"></i> คุณยังไม่มีคำสั่งซื้อ
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
<?php $conn->close(); ?>