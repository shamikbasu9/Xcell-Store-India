<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');
requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$cartItems = getCartItems();
if (empty($cartItems)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

$userId = $_SESSION['user_id'];
$paymentMethod = sanitize($_POST['payment_method']);
$addressId = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;

$subtotal = getCartTotal();
$shipping = calculateShipping($subtotal);
$tax = calculateTax($subtotal);
$discount = 0;
$couponId = null;

if (isset($_SESSION['applied_coupon'])) {
    $coupon = $_SESSION['applied_coupon'];
    $couponId = $coupon['id'];
    $discount = $coupon['discount_type'] === 'flat' 
        ? $coupon['discount_value'] 
        : ($subtotal * $coupon['discount_value']) / 100;
    
    if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
        $discount = $coupon['max_discount'];
    }
}

$total = $subtotal + $shipping + $tax - $discount;
$orderNumber = generateOrderNumber();

$conn = getDBConnection();
$conn->begin_transaction();

try {
    // Create order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, subtotal, tax, shipping_charge, discount, total, coupon_id, shipping_address_id, payment_method, payment_status, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'processing')");
    $stmt->bind_param("isddddiiis", $userId, $orderNumber, $subtotal, $tax, $shipping, $discount, $total, $couponId, $addressId, $paymentMethod);
    $stmt->execute();
    $orderId = $conn->insert_id;
    $stmt->close();
    
    // Add order items
    foreach ($cartItems as $item) {
        // Use discount price if available
        $itemPrice = getProductPrice($item);
        
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_title, price, quantity) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisdi", $orderId, $item['id'], $item['title'], $itemPrice, $item['quantity']);
        $stmt->execute();
        $stmt->close();
        
        // Update stock
        $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ?, sales_count = sales_count + ? WHERE id = ?");
        $stmt->bind_param("iii", $item['quantity'], $item['quantity'], $item['id']);
        $stmt->execute();
        $stmt->close();
    }
    
    // Update coupon usage
    if ($couponId) {
        $conn->query("UPDATE coupons SET used_count = used_count + 1 WHERE id = $couponId");
        unset($_SESSION['applied_coupon']);
    }
    
    // Mark as completed for COD
    if ($paymentMethod === 'cod') {
        $conn->query("UPDATE orders SET payment_status = 'completed' WHERE id = $orderId");
    }
    
    $conn->commit();
    
    // Clear cart
    clearCart();
    
    echo json_encode(['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Order creation failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to create order: ' . $e->getMessage()]);
}

$conn->close();
?>
