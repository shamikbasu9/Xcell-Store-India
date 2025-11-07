<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');
requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$orderId = intval($_POST['order_id']);
$paymentId = sanitize($_POST['payment_id']);
$signature = sanitize($_POST['signature']);

if (!$orderId || !$paymentId || !$signature) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Get order details
    $stmt = $conn->prepare("SELECT order_number, total FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $orderId, $_SESSION['user_id']);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Verify signature
    $generatedSignature = hash_hmac('sha256', $order['order_number'] . '|' . $paymentId, RAZORPAY_SECRET);
    
    if ($generatedSignature === $signature) {
        // Update order payment status
        $stmt = $conn->prepare("UPDATE orders SET payment_status = 'completed', transaction_id = ? WHERE id = ?");
        $stmt->bind_param("si", $paymentId, $orderId);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Payment verified successfully']);
    } else {
        // Payment verification failed
        $stmt = $conn->prepare("UPDATE orders SET payment_status = 'failed', transaction_id = ? WHERE id = ?");
        $stmt->bind_param("si", $paymentId, $orderId);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
    }
    
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
