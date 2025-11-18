<?php
// ไฟล์: seller/manage_products.php

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_login('seller', '../login.php');

$seller_id = $_SESSION['user_id'];
$error = '';
$success = '';

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $product_id = intval($_POST['product_id'] ?? 0);
        $image_url = trim($_POST['image_url'] ?? ''); // ใช้ URL ชั่วคราว

        if (empty($name) || $price <= 0 || $stock < 0) {
            $error = "กรุณากรอกชื่อสินค้า, ราคา, และจำนวนคงเหลือให้ถูกต้อง";
        } else {
            // ในการเพิ่ม/แก้ไขสินค้า จะตั้งค่า is_approved = 0 (รออนุมัติ) เสมอ
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO products (seller_id, name, description, price, stock_quantity, image_url, is_approved) VALUES (?, ?, ?, ?, ?, ?, 0)");
                $stmt->bind_param("isssis", $seller_id, $name, $description, $price, $stock, $image_url);
                if ($stmt->execute()) {
                    $success = "เพิ่มสินค้าใหม่สำเร็จ! สินค้าจะแสดงบนหน้าเว็บหลักเมื่อได้รับการอนุมัติจาก Admin";
                } else {
                    $error = "เกิดข้อผิดพลาดในการเพิ่มสินค้า: " . $conn->error;
                }
            } elseif ($action === 'edit' && $product_id > 0) {
                // ต้องตรวจสอบว่าเป็นสินค้าของ seller นี้จริง
                $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, stock_quantity=?, image_url=?, is_approved=0 WHERE product_id=? AND seller_id=?");
                $stmt->bind_param("sssisii", $name, $description, $price, $stock, $image_url, $product_id, $seller_id);
                if ($stmt->execute()) {
                    $success = "อัปเดตสินค้าสำเร็จ! สินค้าจะถูกส่งไปรออนุมัติอีกครั้ง";
                } else {
                    $error = "เกิดข้อผิดพลาดในการอัปเดตสินค้า: " . $conn->error;
                }
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $product_id = intval($_POST['product_id']);
        // ลบเฉพาะสินค้าของ Seller นี้เท่านั้น
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ? AND seller_id = ?");
        $stmt->bind_param("ii", $product_id, $seller_id);
        $stmt->execute();
        $stmt->close();
        $success = "ลบสินค้าสำเร็จ";
    }
}

// --- Fetch Products ---
$sql = "SELECT product_id, name, price, stock_quantity, is_approved FROM products WHERE seller_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$products_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - Seller</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .product-form { max-width: 600px; margin: 20px 0; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .product-form input, .product-form textarea { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .product-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        .product-table th, .product-table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        .status-0 { color: #ffc107; font-weight: bold; }
        .status-1 { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <?php include '../includes/header_nav.php'; ?>

    <main class="container">
        <a href="index.php" class="btn btn-secondary" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> กลับไป Dashboard</a>
        <h2><i class="fas fa-box-open"></i> จัดการสินค้าของคุณ</h2>

        <?php 
        if ($error) echo create_alert('danger', $error);
        if ($success) echo create_alert('success', $success);
        ?>
        
        <h3>เพิ่มสินค้าใหม่</h3>
        <div class="product-form">
            <form method="POST" action="manage_products.php">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="name">ชื่อสินค้า:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="price">ราคา:</label>
                    <input type="number" id="price" name="price" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="stock">จำนวนคงเหลือ:</label>
                    <input type="number" id="stock" name="stock" min="0" required>
                </div>
                <div class="form-group">
                    <label for="image_url">URL รูปภาพ (จำลอง):</label>
                    <input type="text" id="image_url" name="image_url" placeholder="เช่น: ../assets/product_1.jpg">
                </div>
                <div class="form-group">
                    <label for="description">รายละเอียดสินค้า:</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> เพิ่มสินค้า</button>
            </form>
        </div>
        
        <h3>รายการสินค้าทั้งหมด</h3>
        <table class="product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อสินค้า</th>
                    <th>ราคา</th>
                    <th>สต็อก</th>
                    <th>สถานะอนุมัติ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($products_result->num_rows > 0): ?>
                    <?php while($product = $products_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $product['product_id']; ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>฿<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock_quantity']; ?></td>
                            <td>
                                <?php if ($product['is_approved'] == 1): ?>
                                    <span class="status-1">อนุมัติแล้ว (แสดงผล)</span>
                                <?php else: ?>
                                    <span class="status-0">รออนุมัติ (ไม่แสดงผล)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-secondary" onclick="alert('ฟังก์ชันแก้ไขยังไม่สมบูรณ์: ID <?php echo $product['product_id']; ?>')">แก้ไข</button>
                                <form method="POST" action="manage_products.php" style="display:inline-block;" onsubmit="return confirm('คุณแน่ใจที่จะลบสินค้า <?php echo htmlspecialchars($product['name']); ?>?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <button type="submit" class="btn btn-remove" style="background: #dc3545;">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">ยังไม่มีสินค้าในร้านค้าของคุณ</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
<?php $conn->close(); ?>