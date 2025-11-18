<?php
// ไฟล์: order_details.php

session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

check_login('user', 'login.php');

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['order_id'] ?? 0);
$error = '';

if ($order_id === 0) {
    $error = "ไม่พบหมายเลขคำสั่งซื้อ";
} else {
    // ดึงข้อมูลคำสั่งซื้อหลัก
    $sql_order = "SELECT * FROM orders WHERE order_id = ? AND user_id = ?";
    $stmt_order = $conn->prepare($sql_order);
    $stmt_order->bind_param("ii", $order_id, $user_id);
    $stmt_order->execute();
    $order_result = $stmt_order->get_result();

    if ($order_result->num_rows === 0) {
        $error = "ไม่พบคำสั่งซื้อ หรือคุณไม่มีสิทธิ์เข้าถึงคำสั่งซื้อนี้";
    } else {
        $order = $order_result->fetch_assoc();

        // ดึงรายการสินค้าในคำสั่งซื้อ
        $sql_items = "SELECT oi.*, p.name, p.image_url 
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.product_id
                      WHERE oi.order_id = ?";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดคำสั่งซื้อ #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .detail-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
        .item-list { list-style: none; padding: 0; }
        .item-list li { display: flex; align-items: center; border-bottom: 1px dashed #ccc; padding: 10px 0; }
        .item-list img { width: 50px; height: 50px; object-fit: cover; margin-right: 15px; border-radius: 4px; }
        .item-info { flex-grow: 1; }
        .item-price-qty { text-align: right; font-weight: 600; }
    </style>
</head>
<body>
    <?php include 'includes/header_nav.php'; ?>
    
    <main class="container">
        <a href="orders.php" class="btn btn-secondary" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> ย้อนกลับ</a>
        <h2><i class="fas fa-receipt"></i> รายละเอียดคำสั่งซื้อ #<?php echo $order_id; ?></h2>

        <?php if ($error): ?>
            <?php echo create_alert('danger', $error); ?>
        <?php else: ?>
            <div class="detail-card">
                <h3>ข้อมูลคำสั่งซื้อ</h3>
                <p><strong>วันที่สั่งซื้อ:</strong> <?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></p>
                <p><strong>สถานะ:</strong> <?php echo get_status_badge($order['order_status']); ?></p>
                <p><strong>ที่อยู่จัดส่ง:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                <p><strong>ยอดรวม:</strong> ฿<?php echo number_format($order['total_amount'], 2); ?></p>
            </div>
            
            <div class="detail-card">
                <h3>รายการสินค้า</h3>
                <ul class="item-list">
                    <?php while($item = $items_result->fetch_assoc()): ?>
                        <li>
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?: '../assets/default_product.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="item-info">
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                <small>ราคาต่อหน่วย: ฿<?php echo number_format($item['price_per_unit'], 2); ?></small>
                            </div>
                            <div class="item-price-qty">
                                x <?php echo $item['quantity']; ?> <br>
                                ฿<?php echo number_format($item['price_per_unit'] * $item['quantity'], 2); ?>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
<?php $conn->close(); ?>