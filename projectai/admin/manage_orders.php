<?php
// ไฟล์: admin/manage_orders.php

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_login('admin', '../login.php');

$error = '';
$success = '';

// --- Handle Order Status Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    
    $stmt_update = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt_update->bind_param("si", $new_status, $order_id);
    $stmt_update->execute();
    $stmt_update->close();
    $success = "อัปเดตสถานะคำสั่งซื้อ #$order_id เป็น " . ucfirst($new_status) . " สำเร็จ";
}

// --- Fetch All Orders ---
$sql = "SELECT o.order_id, o.order_date, o.order_status, o.total_amount, u.username
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        ORDER BY o.order_date DESC";
$orders_result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซื้อทั้งหมด - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .order-table th, .order-table td { font-size: 0.9rem; }
        .order-table select { padding: 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <?php include '../includes/header_nav.php'; ?>

    <main class="container">
        <div style="padding-top: 20px;">
            <a href="index.php" class="btn btn-secondary" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> กลับไป Dashboard</a>
        </div>
        <h2><i class="fas fa-shopping-basket"></i> จัดการคำสั่งซื้อทั้งหมด</h2>

        <?php 
        if ($error) echo create_alert('danger', $error);
        if ($success) echo create_alert('success', $success);
        ?>

        <table class="order-table">
            <thead>
                <tr>
                    <th># Order ID</th>
                    <th>ลูกค้า</th>
                    <th>วันที่</th>
                    <th>ยอดรวม</th>
                    <th>สถานะ</th>
                    <th>อัปเดตสถานะ</th>
                    <th>รายละเอียด</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders_result->num_rows > 0): ?>
                    <?php while($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $order['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                            <td>฿<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo get_status_badge_seller($order['order_status']); ?></td>
                            <td>
                                <form method="POST" action="manage_orders.php" style="display:inline-block;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <select name="new_status" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <a href="../order_details.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-secondary" style="padding: 5px 10px;">ดูรายการ</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">ไม่พบคำสั่งซื้อในระบบ</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
<?php $conn->close(); ?>