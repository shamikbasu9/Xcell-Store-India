<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in and has admin access
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$imageId = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if (!$imageId || !$productId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, set all images for this product as not primary
        $stmt = $conn->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        
        // Then set the selected image as primary
        $stmt = $conn->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?");
        $stmt->bind_param("ii", $imageId, $productId);
        $result = $stmt->execute();
        
        if ($result && $conn->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Failed to update primary image');
        }
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while updating the primary image',
        'error' => $e->getMessage()
    ]);
}
?>
