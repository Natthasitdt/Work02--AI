<?php
// ไฟล์: cart_handler.php

session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// ต้องเป็น user ถึงจะจัดการตะกร้าได้
check_login('user', 'login.php');

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

if ($action && $product_id > 0) {
    // 1. ตรวจสอบสินค้าและสต็อก
    $stmt_product = $conn->prepare("SELECT price, stock_quantity FROM products WHERE product_id = ? AND is_approved = 1");
    $stmt_product->bind_param("i", $product_id);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    
    if ($result_product->num_rows === 0) {
        $_SESSION['error'] = "ไม่พบสินค้าหรือสินค้านี้ยังไม่ได้รับการอนุมัติ";
        header("Location: index.php");
        exit();
    }
    
    $product = $result_product->fetch_assoc();
    $stock_quantity = $product['stock_quantity'];

    switch ($action) {
        case 'add':
            if ($quantity > 0 && $quantity <= $stock_quantity) {
                // ตรวจสอบว่าสินค้ามีในตะกร้าอยู่แล้วหรือไม่
                $stmt_cart = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt_cart->bind_param("ii", $user_id, $product_id);
                $stmt_cart->execute();
                $result_cart = $stmt_cart->get_result();

                if ($result_cart->num_rows > 0) {
                    // มีอยู่แล้ว ให้อัปเดต
                    $current_cart = $result_cart->fetch_assoc();
                    $new_quantity = $current_cart['quantity'] + $quantity;
                    
                    if ($new_quantity <= $stock_quantity) {
                        $stmt_update = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
                        $stmt_update->bind_param("ii", $new_quantity, $current_cart['cart_id']);
                        $stmt_update->execute();
                        $_SESSION['success'] = "เพิ่มจำนวนสินค้าในตะกร้าแล้ว";
                    } else {
                        $_SESSION['error'] = "จำนวนสินค้าในตะกร้าเกินจำนวนสินค้าคงเหลือ";
                    }
                } else {
                    // ไม่มี ให้อัปเดต
                    $stmt_insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                    $stmt_insert->bind_param("iii", $user_id, $product_id, $quantity);
                    $stmt_insert->execute();
                    $_SESSION['success'] = "เพิ่มสินค้าใส่ตะกร้าแล้ว";
                }
            } else {
                 $_SESSION['error'] = "จำนวนสินค้าไม่ถูกต้องหรือเกินสต็อก";
            }
            break;

        case 'update':
            if ($quantity > 0 && $quantity <= $stock_quantity) {
                 $stmt_update = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                 $stmt_update->bind_param("iii", $quantity, $user_id, $product_id);
                 $stmt_update->execute();
                 $_SESSION['success'] = "อัปเดตจำนวนสินค้าแล้ว";
            } elseif ($quantity <= 0) {
                // ถ้า quantity เป็น 0 หรือน้อยกว่า ให้ลบทิ้ง
                $stmt_delete = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt_delete->bind_param("ii", $user_id, $product_id);
                $stmt_delete->execute();
                $_SESSION['success'] = "ลบสินค้าออกจากตะกร้าแล้ว";
            } else {
                $_SESSION['error'] = "จำนวนสินค้าเกินสต็อก";
            }
            break;

        case 'remove':
            $stmt_delete = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt_delete->bind_param("ii", $user_id, $product_id);
            $stmt_delete->execute();
            $_SESSION['success'] = "ลบสินค้าออกจากตะกร้าแล้ว";
            break;
    }
}

// Redirect กลับไปหน้าเดิม
$redirect_to = $_SERVER['HTTP_REFERER'] ?? 'cart.php';
header("Location: " . $redirect_to);
exit();
?>