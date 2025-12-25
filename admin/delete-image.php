<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in and has admin access
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$imageId = intval($_GET['id'] ?? 0);
$productId = intval($_GET['product'] ?? 0);

if (!$imageId || !$productId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Get image details
    $stmt = $conn->prepare("SELECT * FROM product_images WHERE id = ? AND product_id = ?");
    $stmt->bind_param("ii", $imageId, $productId);
    $stmt->execute();
    $image = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$image) {
        throw new Exception('Image not found');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete file from server
        $filePath = PRODUCT_IMAGES_DIR . $image['image_path'];
        if (file_exists($filePath)) {
            if (!@unlink($filePath)) {
                throw new Exception('Failed to delete image file');
            }
        }
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
        $stmt->bind_param("i", $imageId);
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete image record');
        }
        
        // If this was the primary image, make another image primary
        if ($image['is_primary']) {
            $stmt = $conn->prepare("UPDATE product_images SET is_primary = 1 WHERE product_id = ? ORDER BY display_order LIMIT 1");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
        
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
        'message' => 'An error occurred while deleting the image',
        'error' => $e->getMessage()
    ]);
}
