<?php
// ไฟล์: checkout.php

session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

check_login('user', 'login.php');

$user_id = $_SESSION['user_id'];
$total_cart_amount = 0;
$error = '';

// 1. ดึงรายการสินค้าในตะกร้า (เหมือน cart.php)
$sql = "SELECT c.product_id, p.name, p.price, p.seller_id, c.quantity, p.stock_quantity 
        FROM cart c 
        JOIN products p ON c.product_id = p.product_id 
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

$cart_items = [];
while ($item = $cart_result->fetch_assoc()) {
    $cart_items[] = $item;
    $total_cart_amount += $item['price'] * $item['quantity'];
}
$stmt->close();

if (empty($cart_items)) {
    $_SESSION['error'] = "ตะกร้าสินค้าว่างเปล่า ไม่สามารถชำระเงินได้";
    header("Location: cart.php");
    exit();
}

// 2. การประมวลผลการสั่งซื้อ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim($_POST['shipping_address']);

    if (empty($shipping_address)) {
        $error = "กรุณาระบุที่อยู่ในการจัดส่ง";
    } else {
        // เริ่ม Transaction
        $conn->begin_transaction();
        $is_success = true;

        try {
            // A. Insert เข้าตาราง orders
            $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, order_status) VALUES (?, ?, ?, 'processing')");
            $stmt_order->bind_param("ids", $user_id, $total_cart_amount, $shipping_address);
            $stmt_order->execute();
            $order_id = $stmt_order->insert_id;
            $stmt_order->close();

            // B. Insert เข้าตาราง order_items และอัปเดต stock
            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, seller_id, quantity, price_per_unit) VALUES (?, ?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?");

            foreach ($cart_items as $item) {
                // 1. Insert order_items
                $price_per_unit = $item['price'];
                $seller_id = $item['seller_id'];
                $quantity = $item['quantity'];
                
                $stmt_item->bind_param("iiiid", $order_id, $item['product_id'], $seller_id, $quantity, $price_per_unit);
                $stmt_item->execute();
                
                // 2. Update stock (ตรวจสอบอีกครั้งว่า stock พอหรือไม่)
                $stmt_stock->bind_param("iii", $quantity, $item['product_id'], $quantity);
                $stmt_stock->execute();
                
                if ($stmt_stock->affected_rows === 0) {
                    throw new Exception("สินค้า " . $item['name'] . " มีสต็อกไม่เพียงพอ");
                }
            }
            $stmt_item->close();
            $stmt_stock->close();

            // C. ล้างตะกร้าสินค้า
            $stmt_clear = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt_clear->bind_param("i", $user_id);
            $stmt_clear->execute();
            $stmt_clear->close();

            // Commit Transaction
            $conn->commit();
            $_SESSION['success'] = "สั่งซื้อสินค้าสำเร็จแล้ว! หมายเลขคำสั่งซื้อคือ #" . $order_id;
            header("Location: orders.php"); // ไปหน้าประวัติคำสั่งซื้อ
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = "เกิดข้อผิดพลาดในการสั่งซื้อ: " . $e->getMessage();
            $is_success = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .checkout-layout { display: flex; gap: 30px; margin-top: 20px; }
        .checkout-form { flex: 2; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .order-summary { flex: 1; background: #f9f9f9; padding: 25px; border-radius: 8px; border: 1px solid #eee; }
        .order-summary ul { list-style: none; padding: 0; }
        .order-summary li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dotted #ccc; }
        .order-summary h4 { border-top: 2px solid var(--primary-color); padding-top: 10px; margin-top: 15px; }
    </style>
</head>
<body>
    <?php include 'includes/header_nav.php'; ?>
    
    <main class="container">
        <h2><i class="fas fa-money-check-alt"></i> หน้าชำระเงิน</h2>
        
        <?php if ($error) echo create_alert('danger', $error); ?>

        <div class="checkout-layout">
            <div class="checkout-form">
                <h3>1. ที่อยู่ในการจัดส่ง</h3>
                <form method="POST" action="checkout.php">
                    <div class="form-group">
                        <label for="shipping_address">ที่อยู่จัดส่ง:</label>
                        <textarea id="shipping_address" name="shipping_address" rows="5" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                    </div>
                    
                    <h3>2. วิธีการชำระเงิน (จำลอง)</h3>
                    <p>ระบบนี้รองรับการโอนเงิน/เก็บเงินปลายทางเท่านั้น (จำลองการรับคำสั่งซื้อ)</p>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.2rem; padding: 15px;">
                        ยืนยันการสั่งซื้อ
                    </button>
                </form>
            </div>

            <div class="order-summary">
                <h3>สรุปคำสั่งซื้อ</h3>
                <ul>
                    <?php foreach ($cart_items as $item): ?>
                        <li>
                            <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                            <span>฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <h4>
                    <span>ยอดรวมทั้งหมด:</span>
                    <span>฿<?php echo number_format($total_cart_amount, 2); ?></span>
                </h4>
            </div>
        </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>