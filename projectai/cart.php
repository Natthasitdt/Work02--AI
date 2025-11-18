<?php
// ไฟล์: cart.php

session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// ต้องเป็น user ถึงจะเข้าหน้านี้ได้
check_login('user', 'login.php');

$user_id = $_SESSION['user_id'];
$total_cart_amount = 0;

// ดึงรายการสินค้าในตะกร้า
$sql = "SELECT c.product_id, p.name, p.price, p.image_url, c.quantity, p.stock_quantity 
        FROM cart c 
        JOIN products p ON c.product_id = p.product_id 
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .cart-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .cart-table th, .cart-table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        .cart-table th { background-color: var(--primary-color); color: white; }
        .cart-item-img { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; }
        .total-box { margin-top: 20px; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); text-align: right; }
        .total-box h3 { margin-bottom: 15px; }
        .quantity-input { width: 60px; text-align: center; padding: 5px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-update { background: #333; color: white; margin-left: 5px; }
        .btn-remove { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <?php include 'includes/header_nav.php'; // ใช้โค้ดจาก index.php ส่วน header/nav ?>
    
    <main class="container">
        <h2><i class="fas fa-shopping-cart"></i> ตะกร้าสินค้าของคุณ</h2>

        <?php 
        if (isset($_SESSION['success'])) { echo create_alert('success', $_SESSION['success']); unset($_SESSION['success']); }
        if (isset($_SESSION['error'])) { echo create_alert('danger', $_SESSION['error']); unset($_SESSION['error']); }
        ?>

        <?php if ($cart_result->num_rows > 0): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>สินค้า</th>
                        <th>ราคาต่อหน่วย</th>
                        <th>จำนวน</th>
                        <th>ราคารวม</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = $cart_result->fetch_assoc()): 
                        $subtotal = $item['price'] * $item['quantity'];
                        $total_cart_amount += $subtotal;
                    ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'assets/default_product.jpg'); ?>" class="cart-item-img" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </td>
                            <td>฿<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <form action="cart_handler.php" method="POST" style="display: flex; align-items: center;">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" class="quantity-input" onchange="this.form.submit()">
                                    <button type="submit" class="btn btn-update" style="display: none;">อัปเดต</button>
                                </form>
                            </td>
                            <td>฿<?php echo number_format($subtotal, 2); ?></td>
                            <td>
                                <form action="cart_handler.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="btn btn-remove">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="total-box">
                <h3>ยอดรวมตะกร้าสินค้า: ฿<?php echo number_format($total_cart_amount, 2); ?></h3>
                <a href="checkout.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 15px 30px;">
                    <i class="fas fa-money-check-alt"></i> ดำเนินการชำระเงิน
                </a>
            </div>

        <?php else: ?>
            <div class="alert alert-warning" style="margin-top: 20px;">
                <i class="fas fa-info-circle"></i> ตะกร้าสินค้าของคุณว่างเปล่า
            </div>
            <a href="index.php" class="btn btn-primary">กลับไปเลือกซื้อสินค้า</a>
        <?php endif; ?>
    </main>
</body>
</html>
<?php 
// โค้ดสำหรับ header_nav.php (เพื่อให้ index.php ไม่ซ้ำซ้อน)
/*
สร้างไฟล์ includes/header_nav.php และคัดลอกส่วน header จาก index.php มาไว้ที่นี่
*/
$conn->close(); 
?>