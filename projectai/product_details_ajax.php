<?php
// ไฟล์: product_details_ajax.php

require_once 'includes/db_connect.php';

if (isset($_GET['product_id']) && is_numeric($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    
    // ดึงข้อมูลสินค้า
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
        ?>
        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'assets/default_product.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; height: auto; border-radius: 5px;">
            </div>
            <div style="flex: 2;">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p style="color: var(--primary-color); font-size: 1.8rem; font-weight: 700; margin: 10px 0;">
                    ฿<?php echo number_format($product['price'], 2); ?>
                </p>
                <p><strong>รายละเอียด:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <p style="margin-top: 10px;"><strong>จำนวนคงเหลือ:</strong> <?php echo htmlspecialchars($product['stock_quantity']); ?></p>
                
                <form action="cart_handler.php" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" style="width: 80px; padding: 5px; border: 1px solid #ccc; border-radius: 3px;">
                    <button type="submit" class="btn btn-primary" <?php echo isset($_SESSION['user_id']) && $_SESSION['role'] == 'user' ? '' : 'disabled'; ?>>
                        <i class="fas fa-cart-plus"></i> เพิ่มในตะกร้า
                    </button>
                    <?php if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user'): ?>
                        <p style="color: #999; font-size: 0.9rem; margin-top: 5px;">ต้องเข้าสู่ระบบในฐานะผู้ใช้ทั่วไปเพื่อเพิ่มสินค้า</p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php
    } else {
        echo "<p>ไม่พบสินค้าที่ระบุ</p>";
    }
    $stmt->close();
} else {
    echo "<p>ไม่พบ Product ID</p>";
}

$conn->close();
?>