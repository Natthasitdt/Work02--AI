<?php
// ไฟล์: index.php

session_start();
require_once 'includes/db_connect.php';

// ดึงสินค้าที่ 'is_approved' = 1 เพื่อแสดงบนหน้าหลัก
$sql = "SELECT product_id, name, price, description, image_url FROM products WHERE is_approved = 1 ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก - ร้านค้าออนไลน์ AI Project</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <header>
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">🛒 AI Shop</a>
                <div class="nav-links">
                    <a href="cart.php"><i class="fas fa-shopping-cart"></i> ตะกร้า</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="#"><i class="fas fa-user"></i> ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <a href="admin/index.php"><i class="fas fa-tools"></i> Admin Dashboard</a>
                        <?php elseif ($_SESSION['role'] == 'seller'): ?>
                            <a href="seller/index.php"><i class="fas fa-store"></i> Seller Dashboard</a>
                        <?php else: ?>
                            <a href="orders.php"><i class="fas fa-history"></i> ประวัติสั่งซื้อ</a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                    <?php else: ?>
                        <a href="login.php"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
                        <a href="register.php"><i class="fas fa-user-plus"></i> สมัครสมาชิก</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>🔥 สินค้าแนะนำ</h2>
        
        <div class="product-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($row['image_url'] ?: 'assets/default_product.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-image">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="product-price">฿<?php echo number_format($row['price'], 2); ?></p>
                            
                            <button class="btn btn-secondary quick-view-btn" data-id="<?php echo $row['product_id']; ?>">
                                <i class="fas fa-eye"></i> Quick View
                            </button>
                            
                            <form action="cart_handler.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn-primary" <?php echo isset($_SESSION['user_id']) && $_SESSION['role'] == 'user' ? '' : 'disabled'; ?>>
                                    <i class="fas fa-cart-plus"></i> เพิ่มในตะกร้า
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>ยังไม่มีสินค้าที่ได้รับการอนุมัติ</p>
            <?php endif; ?>
        </div>
    </main>

    <div id="quickViewModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <div class="modal-body" id="modalProductDetails">
                <p>กำลังโหลด...</p>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById("quickViewModal");
        const closeBtn = document.querySelector(".close-btn");
        const quickViewBtns = document.querySelectorAll(".quick-view-btn");
        const modalBody = document.getElementById("modalProductDetails");

        quickViewBtns.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                modal.style.display = "block";
                
                // โหลดรายละเอียดสินค้าด้วย AJAX
                fetch('product_details_ajax.php?product_id=' + productId)
                    .then(response => response.text())
                    .then(data => {
                        modalBody.innerHTML = data;
                    })
                    .catch(error => {
                        console.error('Error fetching product details:', error);
                        modalBody.innerHTML = '<p>ไม่สามารถโหลดรายละเอียดสินค้าได้</p>';
                    });
            });
        });

        // เมื่อคลิกที่ X หรือนอก Modal ให้ปิด
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    });
    </script>

</body>
</html>
<?php
$conn->close();
?>