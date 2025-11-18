<?php
// ไฟล์: admin/manage_products.php

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

check_login('admin', '../login.php');

$error = '';
$success = '';
$current_view = $_GET['view'] ?? 'pending'; // 'pending' หรือ 'all'

// --- Handle Admin Product Actions ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $product_id = intval($_GET['id']);
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE products SET is_approved = 1 WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
        $success = "อนุมัติสินค้า ID #$product_id แล้ว";
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE products SET is_approved = 0 WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
        $success = "ยกเลิกการอนุมัติสินค้า ID #$product_id แล้ว (ถูกซ่อนจากหน้าเว็บหลัก)";
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
        $success = "ลบสินค้า ID #$product_id ออกจากระบบแล้ว";
    }
    header("Location: manage_products.php?view=" . $current_view);
    exit();
}

// --- Fetch Products based on View ---
$base_sql = "SELECT p.product_id, p.name, p.price, p.stock_quantity, p.is_approved, s.shop_name 
             FROM products p 
             JOIN sellers s ON p.seller_id = s.seller_id";

if ($current_view === 'pending') {
    $sql = $base_sql . " WHERE p.is_approved = 0 ORDER BY p.created_at ASC";
} else { // 'all'
    $sql = $base_sql . " ORDER BY p.is_approved ASC, p.created_at DESC";
}
$products_result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติและจัดการสินค้า - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .product-table th, .product-table td { font-size: 0.95rem; }
        .view-toggle a { padding: 10px 15px; background: #eee; text-decoration: none; color: var(--text-color); border-radius: 5px; margin-right: 10px; }
        .view-toggle a.active { background: var(--primary-color); color: white; }
    </style>
</head>
<body>
    <?php include '../includes/header_nav.php'; ?>

    <main class="container">
        <div style="padding-top: 20px;">
            <a href="index.php" class="btn btn-secondary" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> กลับไป Dashboard</a>
        </div>
        <h2><i class="fas fa-clipboard-check"></i> จัดการและอนุมัติสินค้า</h2>

        <?php 
        if ($error) echo create_alert('danger', $error);
        if ($success) echo create_alert('success', $success);
        ?>

        <div class="view-toggle" style="margin-bottom: 20px;">
            <a href="manage_products.php?view=pending" class="<?php echo $current_view === 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> สินค้ารออนุมัติ
            </a>
            <a href="manage_products.php?view=all" class="<?php echo $current_view === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> สินค้าทั้งหมด
            </a>
        </div>

        <table class="product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>สินค้า</th>
                    <th>ร้านค้า</th>
                    <th>ราคา/สต็อก</th>
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
                            <td><?php echo htmlspecialchars($product['shop_name']); ?></td>
                            <td>฿<?php echo number_format($product['price'], 2); ?> (Stock: <?php echo $product['stock_quantity']; ?>)</td>
                            <td>
                                <?php if ($product['is_approved'] == 1): ?>
                                    <span class="status-1" style="color: #28a745;">อนุมัติแล้ว</span>
                                <?php else: ?>
                                    <span class="status-0" style="color: #ffc107;">รออนุมัติ</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['is_approved'] == 0): ?>
                                    <a href="manage_products.php?action=approve&id=<?php echo $product['product_id']; ?>" class="btn btn-primary" style="background: #28a745;">อนุมัติ</a>
                                <?php else: ?>
                                    <a href="manage_products.php?action=reject&id=<?php echo $product['product_id']; ?>" class="btn btn-secondary" style="background: #dc3545;">ยกเลิกอนุมัติ</a>
                                <?php endif; ?>
                                <a href="manage_products.php?action=delete&id=<?php echo $product['product_id']; ?>" class="btn btn-remove" 
                                    onclick="return confirm('แน่ใจที่จะลบสินค้า #<?php echo $product['product_id']; ?>?')"
                                    style="background: #333; margin-left: 5px;">ลบถาวร</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">
                        <?php echo $current_view === 'pending' ? 'ไม่มีสินค้ารอการอนุมัติ' : 'ไม่พบสินค้าในระบบ'; ?>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
<?php $conn->close(); ?>