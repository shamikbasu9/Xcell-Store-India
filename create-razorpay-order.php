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
$amount = floatval($_POST['amount']);

if (!$orderId || !$amount) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Verify order belongs to user
    $stmt = $conn->prepare("SELECT order_number FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $orderId, $_SESSION['user_id']);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Create Razorpay order
    $api_key = RAZORPAY_KEY;
    $api_secret = RAZORPAY_SECRET;
    
    $orderData = [
        'receipt' => $order['order_number'],
        'amount' => $amount * 100, // Convert to paise
        'currency' => 'INR',
        'payment_capture' => 1
    ];
    
    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_USERPWD, $api_key . ':' . $api_secret);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $razorpayOrder = json_decode($result, true);
        echo json_encode([
            'success' => true,
            'razorpay_order_id' => $razorpayOrder['id'],
            'amount' => $razorpayOrder['amount'],
            'currency' => $razorpayOrder['currency']
        ]);
    } else {
        error_log("Razorpay order creation failed: " . $result);
        echo json_encode(['success' => false, 'message' => 'Failed to create payment order']);
    }
    
} catch (Exception $e) {
    error_log("Razorpay error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
