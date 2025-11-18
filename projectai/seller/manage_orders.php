<?php
// ไฟล์: seller/manage_orders.php

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_login('seller', '../login.php');

$seller_id = $_SESSION['user_id'];
$success = '';

// --- Handle Order Status Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    
    // ตรวจสอบว่ามีรายการสินค้าใน order_items ที่เป็นของ seller นี้หรือไม่
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND seller_id = ?");
    $stmt_check->bind_param("ii", $order_id, $seller_id);
    $stmt_check->execute();
    $is_valid_order = $stmt_check->get_result()->fetch_row()[0] > 0;
    $stmt_check->close();

    if ($is_valid_order) {
        // อัปเดตสถานะของคำสั่งซื้อหลัก
        // NOTE: ระบบที่สมบูรณ์ควรจัดการสถานะย่อยของแต่ละ item ด้วย แต่ในที่นี้จะอัปเดตสถานะของ order หลักเลย
        $stmt_update = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt_update->bind_param("si", $new_status, $order_id);
        $stmt_update->execute();
        $stmt_update->close();
        $success = "อัปเดตสถานะคำสั่งซื้อ #$order_id เป็น " . ucfirst($new_status) . " สำเร็จ";
    } else {
        $error = "ไม่พบคำสั่งซื้อที่เกี่ยวข้องกับร้านค้าของคุณ";
    }
}

// --- Fetch Orders for this Seller ---
// ดึง Order ID ที่มีสินค้าของ Seller นี้
$sql = "SELECT DISTINCT o.order_id, o.order_date, o.order_status, o.total_amount, u.username, o.shipping_address
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN users u ON o.user_id = u.user_id
        WHERE oi.seller_id = ?
        ORDER BY o.order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$orders_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซื้อ - Seller</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .order-table th, .order-table td { font-size: 0.95rem; }
        .order-table select { padding: 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <?php include '../includes/header_nav.php'; ?>

    <main class="container">
        <a href="index.php" class="btn btn-secondary" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> กลับไป Dashboard</a>
        <h2><i class="fas fa-truck-moving"></i> คำสั่งซื้อที่เกี่ยวข้องกับร้านค้าของคุณ</h2>

        <?php 
        if ($error) echo create_alert('danger', $error);
        if ($success) echo create_alert('success', $success);
        ?>

        <table class="order-table">
            <thead>
                <tr>
                    <th># ID</th>
                    <th>ลูกค้า</th>
                    <th>วันที่</th>
                    <th>ยอดรวม</th>
                    <th>สถานะ</th>
                    <th>ที่อยู่จัดส่ง</th>
                    <th>อัปเดตสถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders_result->num_rows > 0): ?>
                    <?php while($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $order['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                            <td>฿<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo get_status_badge_seller($order['order_status']); ?></td>
                            <td><?php echo htmlspecialchars(substr($order['shipping_address'], 0, 30)) . '...'; ?></td>
                            <td>
                                <form method="POST" action="manage_orders.php">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <select name="new_status" onchange="this.form.submit()">
                                        <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">ยังไม่มีคำสั่งซื้อที่ต้องจัดการ</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
<?php $conn->close(); ?>