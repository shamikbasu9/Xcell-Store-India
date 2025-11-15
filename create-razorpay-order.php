<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Log the request
file_put_contents('razorpay_debug.log', "[" . date('Y-m-d H:i:s') . "] New request: " . print_r($_POST, true) . "\n", FILE_APPEND);

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = 'Invalid request method. Expected POST, got ' . $_SERVER['REQUEST_METHOD'];
    file_put_contents('razorpay_debug.log', "[" . date('Y-m-d H:i:s') . "] $error\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

$orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

if (!$orderId || $amount <= 0) {
    $error = 'Missing or invalid parameters. Order ID: ' . $orderId . ', Amount: ' . $amount;
    file_put_contents('razorpay_debug.log', "[" . date('Y-m-d H:i:s') . "] $error\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters']);
    exit;
}

try {
    try {
        $conn = getDBConnection();
        
        // Verify order belongs to user
        $stmt = $conn->prepare("SELECT id, order_number, total, order_status as status FROM orders WHERE id = ? AND user_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        $stmt->bind_param("ii", $orderId, $_SESSION['user_id']);
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute statement: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if (!$order) {
            throw new Exception('Order not found or does not belong to user. Order ID: ' . $orderId . ', User ID: ' . $_SESSION['user_id']);
        }
        
        // Verify order amount matches (compare with 2 decimal precision)
        $expectedAmount = number_format((float)$order['total'], 2, '.', '');
        $receivedAmount = number_format((float)$amount, 2, '.', '');
        
        // if ($expectedAmount !== $receivedAmount) {
        //     throw new Exception('Order amount mismatch. Expected: ' . $expectedAmount . ', Got: ' . $receivedAmount);
        // }
        
        // Verify order status is valid for payment
        $allowedStatuses = ['pending', 'processing'];
        // if (!in_array($order['status'], $allowedStatuses, true)) {
        //     throw new Exception('Order status is not eligible for payment. Current status: ' . $order['status']);
        // }
    } catch (Exception $e) {
        file_put_contents('razorpay_debug.log', "[" . date('Y-m-d H:i:s') . "] Order validation error: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
    
    try {
        // Create Razorpay order
        $api_key = RAZORPAY_KEY;
        $api_secret = RAZORPAY_SECRET;
        
        if (empty($api_key) || empty($api_secret)) {
            throw new Exception('Razorpay API credentials are not properly configured');
        }
        
        // Ensure amount is properly formatted to 2 decimal places before converting to paise
        $amount = number_format((float)$amount, 2, '.', '');
        $amount_in_paise = (int)round($amount * 100); // Convert to paise and ensure it's an integer
        
        $orderData = [
            'receipt' => $order['order_number'],
            'amount' => $amount_in_paise, // Must be in paise (smallest currency unit)
            'currency' => 'INR',
            'payment_capture' => 1, // Auto-capture payment
            'notes' => [
                'order_id' => $order['id'],
                'order_number' => $order['order_number']
            ]
        ];
        
        file_put_contents('razorpay_debug.log', "[" . date('Y-m-d H:i:s') . "] Creating Razorpay order with data: " . print_r($orderData, true) . "\n", FILE_APPEND);
        
        $ch = curl_init('https://api.razorpay.com/v1/orders');
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_USERPWD => $api_key . ':' . $api_secret,
            CURLOPT_POSTFIELDS => json_encode($orderData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Razorpay-Account: ' . $api_key
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        curl_close($ch);
        
        file_put_contents('razorpay_debug.log', "[" . date('Y-m-d H:i:s') . "] Razorpay API Response - Status: $httpCode, Response: $result\n", FILE_APPEND);
        
        if ($curlError) {
            throw new Exception('cURL Error #' . $curlErrno . ': ' . $curlError);
        }
        
        $response = json_decode($result, true);
        
        if ($httpCode === 200 && isset($response['id'])) {
            // Update order with Razorpay order ID in the database
            $conn = getDBConnection();
            $stmt = $conn->prepare("UPDATE orders SET payment_gateway_order_id = ? WHERE id = ?");
            $stmt->bind_param("si", $response['id'], $orderId);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            
            file_put_contents('razorpay_debug.log', "[" . date('Y-m-d H:i:s') . "] Successfully created Razorpay order: " . $response['id'] . "\n", FILE_APPEND);
            
            echo json_encode([
                'success' => true,
                'razorpay_order_id' => $response['id'],
                'amount' => $response['amount'],
                'currency' => $response['currency']
            ]);
        } else {
            $errorMsg = isset($response['error']['description']) 
                ? $response['error']['description'] 
                : (isset($response['error']['message']) 
                    ? $response['error']['message'] 
                    : 'Unknown error occurred');
                    
            throw new Exception('Razorpay API Error: ' . $errorMsg . ' (HTTP ' . $httpCode . ')');
        }
    } catch (Exception $e) {
        $errorMsg = 'Failed to create payment order: ' . $e->getMessage();
        file_put_contents('razorpay_debug.log', "[" . date('Y-m-d H:i:s') . "] $errorMsg\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    }
    
} catch (Exception $e) {
    $errorMsg = 'Unexpected error: ' . $e->getMessage();
    file_put_contents('razorpay_debug.log', "[" . date('Y-m-d H:i:s') . "] $errorMsg\nStack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $errorMsg]);
}
?>
