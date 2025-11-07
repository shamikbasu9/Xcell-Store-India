<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

$imageId = intval($_GET['id'] ?? 0);
$productId = intval($_GET['product'] ?? 0);

if ($imageId && $productId) {
    $conn = getDBConnection();
    
    // Get image details
    $stmt = $conn->prepare("SELECT * FROM product_images WHERE id = ? AND product_id = ?");
    $stmt->bind_param("ii", $imageId, $productId);
    $stmt->execute();
    $image = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($image) {
        // Delete file from server
        $filePath = PRODUCT_IMAGES_DIR . $image['image_path'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        $stmt->close();
        
        // If this was the primary image, make another image primary
        if ($image['is_primary']) {
            $stmt = $conn->prepare("UPDATE product_images SET is_primary = 1 WHERE product_id = ? ORDER BY display_order LIMIT 1");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    $conn->close();
}

header('Location: product-edit.php?id=' . $productId);
exit;
?>
