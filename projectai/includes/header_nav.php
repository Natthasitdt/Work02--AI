<?php 
// ไฟล์: includes/header_nav.php
// ใช้โค้ด Navbar จาก index.php เพื่อให้ทุกหน้าเรียกใช้ได้ง่าย

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
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