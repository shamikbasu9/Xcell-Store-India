<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');
requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userId = $_SESSION['user_id'];
$fullName = sanitize($_POST['full_name']);
$phone = sanitize($_POST['phone']);
$addressLine1 = sanitize($_POST['address_line1']);
$addressLine2 = sanitize($_POST['address_line2'] ?? '');
$city = sanitize($_POST['city']);
$state = sanitize($_POST['state']);
$pincode = sanitize($_POST['pincode']);
$isDefault = isset($_POST['is_default']) ? 1 : 0;

if (empty($fullName) || empty($phone) || empty($addressLine1) || empty($city) || empty($state) || empty($pincode)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // If this is set as default, unset other defaults
    if ($isDefault) {
        $conn->query("UPDATE addresses SET is_default = 0 WHERE user_id = $userId");
    }
    
    $stmt = $conn->prepare("INSERT INTO addresses (user_id, full_name, phone, address_line1, address_line2, city, state, pincode, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssi", $userId, $fullName, $phone, $addressLine1, $addressLine2, $city, $state, $pincode, $isDefault);
    
    if ($stmt->execute()) {
        $addressId = $conn->insert_id;
        echo json_encode(['success' => true, 'address_id' => $addressId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save address']);
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
