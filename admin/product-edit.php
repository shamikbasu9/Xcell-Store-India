<?php
$pageTitle = 'Edit Product';
require_once 'includes/header.php';

$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
        $success = '';

if (!$productId) {
    header('Location: products.php');
    exit;
}

// Get product details
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Debug: Log the is_featured value
error_log("Product ID: {$productId}, is_featured value: " . ($product['is_featured'] ?? 'NULL') . " (type: " . gettype($product['is_featured']) . ")");

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get product images
$images = [];
$stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received - FILES: " . print_r($_FILES, true));
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $careInstructions = sanitize($_POST['care_instructions']);
    $price = floatval($_POST['price']);
    $discountPrice = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
    $categoryId = intval($_POST['category_id']);
    $stockQuantity = intval($_POST['stock_quantity']);
    $status = sanitize($_POST['status']);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Debug: Log the received is_featured value
    error_log("POST received - is_featured: " . (isset($_POST['is_featured']) ? 'checked' : 'not checked') . " (value: " . $isFeatured . ")");
    
    if (empty($title) || empty($price)) {
        $error = 'Please fill in all required fields';
    } else {
        $slug = generateSlug($title);
        
        // If category_id is 0, set it to NULL for the database
        $categoryIdForDb = $categoryId > 0 ? $categoryId : null;
        
        // Prepare the statement with the correct parameter types
        $stmt = $conn->prepare("UPDATE products SET title = ?, slug = ?, description = ?, care_instructions = ?, price = ?, discount_price = ?, category_id = ?, stock_quantity = ?, status = ?, is_featured = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        
        // Determine the parameter types based on whether category_id is NULL or not
        $paramTypes = $categoryIdForDb === null ? "ssssddisssi" : "ssssddissii";
        
        // Bind parameters
        if ($categoryIdForDb === null) {
            $stmt->bind_param($paramTypes, $title, $slug, $description, $careInstructions, $price, $discountPrice, $categoryIdForDb, $stockQuantity, $status, $isFeatured, $productId);
        } else {
            $stmt->bind_param($paramTypes, $title, $slug, $description, $careInstructions, $price, $discountPrice, $categoryId, $stockQuantity, $status, $isFeatured, $productId);
        }
        
        // Debug: Log the SQL and parameters
        error_log("SQL UPDATE - is_featured value being set: " . $isFeatured);
        
        if ($stmt->execute()) {
            // Handle new media uploads (images and videos)
            if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
                error_log("Media upload detected - files: " . print_r($_FILES['media'], true));
                
                // Debug: Count existing media before upload
                $existingCount = count($images);
                
                // Re-fetch media to ensure we have the latest state
                $currentMedia = [];
                $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $currentMedia[] = $row;
                }
                $stmt->close();
                
                // Debug: Log current media count
                error_log("Product ID: {$productId}, Existing media: {$existingCount}, Current media from DB: " . count($currentMedia));
                
                $maxOrder = 0;
                if (!empty($currentMedia)) {
                    $maxOrder = (int) max(array_column($currentMedia, 'display_order'));
                }
                
                $uploadedCount = 0;
                $newMediaCount = count(array_filter($_FILES['media']['name']));
                error_log("New files to upload: " . $newMediaCount);
                foreach ($_FILES['media']['tmp_name'] as $key => $tmpName) {
                    error_log("Processing file {$key}: error={$_FILES['media']['error'][$key]}, tmp_name={$tmpName}");
                    if ($_FILES['media']['error'][$key] === UPLOAD_ERR_OK) {
                        $mediaFile = [
                            'name' => $_FILES['media']['name'][$key],
                            'type' => $_FILES['media']['type'][$key],
                            'tmp_name' => $tmpName,
                            'error' => $_FILES['media']['error'][$key],
                            'size' => $_FILES['media']['size'][$key]
                        ];
                        
                        // Determine media type based on file extension
                        $fileExtension = strtolower(pathinfo($mediaFile['name'], PATHINFO_EXTENSION));
                        $isVideo = in_array($fileExtension, ['mp4', 'webm', 'ogg', 'mov']);
                        $mediaType = $isVideo ? 'video' : 'image';
                        
                        // Set appropriate upload directory based on media type
                        if ($isVideo) {
                            if (!defined('PRODUCT_VIDEOS_DIR')) {
                                define('PRODUCT_VIDEOS_DIR', UPLOAD_DIR . 'videos/');
                            }
                            $uploadDir = PRODUCT_VIDEOS_DIR;
                            // Create the videos directory if it doesn't exist
                            if (!file_exists($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }
                        } else {
                            $uploadDir = PRODUCT_IMAGES_DIR;
                        }
                        
                        error_log("Attempting to upload file: " . $mediaFile['name'] . " (type: {$mediaType})");
                        // Allow both image and video file types
                        $allowedTypes = $isVideo ? 
                            ['mp4', 'webm', 'ogg', 'mov'] : 
                            ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        $upload = uploadFile($mediaFile, $uploadDir, $allowedTypes);
                        error_log("Upload result: " . print_r($upload, true));
                        
                        if ($upload['success']) {
                            $isPrimary = (empty($currentMedia) && $uploadedCount === 0) ? 1 : 0;
                            $displayOrder = $maxOrder + $uploadedCount + 1; // Calculate display order
                            
                            $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, media_type, is_primary, display_order) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("issii", $productId, $upload['filename'], $mediaType, $isPrimary, $displayOrder);
                            
                            if ($stmt->execute()) {
                                $uploadedCount++;
                                error_log("Successfully inserted media: " . $upload['filename'] . " (type: {$mediaType}, display_order: {$displayOrder})");
                                
                                // If this is the first uploaded image and there were no images before, ensure it's set as primary
                                if ($isPrimary) {
                                    $imageId = $conn->insert_id;
                                    $updateStmt = $conn->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ? AND id != ?");
                                    $updateStmt->bind_param("ii", $productId, $imageId);
                                    $updateStmt->execute();
                                    $updateStmt->close();
                                }
                            } else {
                                error_log("Failed to insert media into database: " . $stmt->error);
                                // If the insert fails, delete the uploaded file
                                if (isset($upload['filename']) && file_exists($uploadDir . $upload['filename'])) {
                                    @unlink($uploadDir . $upload['filename']);
                                }
                            }
                            $stmt->close();
                        } else {
                            error_log("File upload failed: " . $upload['message']);
                        }
                    } else {
                        error_log("File upload error code: " . $_FILES['media']['error'][$key]);
                    }
                }
                
                // Debug: Log upload results
                error_log("Product ID: {$productId}, Uploaded {$uploadedCount} new images");
                
                // Verify final image count
                $finalImages = [];
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_images WHERE product_id = ?");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                $finalCount = $result->fetch_assoc()['count'];
                $stmt->close();
                error_log("Product ID: {$productId}, Final image count: {$finalCount}");
            }
            
            // Add success message if images were uploaded
            if (isset($uploadedCount) && $uploadedCount > 0) {
                $success = "Product updated successfully! {$uploadedCount} new image(s) added.";
            } else {
                $success = 'Product updated successfully!';
            }
        } else {
            $error = 'Failed to update product';
        }
        
        // Refresh images array to show updated count
        $images = [];
        $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        $stmt->close();
    }
}

// Get categories
$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$conn->close();
?>

<div class="mb-3">
    <a href="products.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <!-- Temporary Debug Information -->
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES)): ?>
            <div class="alert alert-info">
                <strong>Debug Info:</strong><br>
                Files detected: <?php echo count($_FILES['images']['name']); ?><br>
                File names: <?php echo implode(', ', $_FILES['images']['name']); ?><br>
                Upload errors: <?php echo implode(', ', $_FILES['images']['error']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">Product Title *</label>
                        <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Care Instructions</label>
                        <textarea class="form-control" name="care_instructions" rows="4"><?php echo htmlspecialchars($product['care_instructions']); ?></textarea>
                    </div>
                    
                    <?php if (!empty($images)): ?>
                        <div class="mb-3">
                            <label class="form-label">Current Images (<?php echo count($images); ?> images)</label>
                            <style>
    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        width: 100%;
        padding: 10px 0;
    }
    .media-item {
        position: relative;
        width: 100%;
        padding-top: 100%; /* 1:1 Aspect Ratio */
        overflow: hidden;
        background: #f8f9fa;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .media-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .media-preview {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        transition: all 0.2s ease;
        background: #fff;
    }
    .media-preview:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .media-preview img,
    .media-preview video {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 10px;
        transition: transform 0.3s ease;
    }
    .media-preview:hover img,
    .media-preview:hover video {
        transform: scale(1.05);
    }
    .media-actions {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        display: flex;
        justify-content: center;
        gap: 10px;
        padding: 10px;
        background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.4) 70%, transparent 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }
    .media-item:hover .media-actions {
        opacity: 1;
    }
    .media-actions .btn {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 50%;
        transition: all 0.2s ease;
    }
    .media-actions .btn:hover {
        transform: scale(1.1);
    }
    .media-actions .btn i {
        font-size: 1rem;
    }
    .primary-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
</style>

<div class="media-grid">
    <?php foreach ($images as $image): ?>
        <div class="media-item">
            <div class="media-preview">
                <?php if ($image['media_type'] === 'video'): ?>
                    <video>
                        <source src="<?php echo getProductImageUrl($image['image_path']); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="play-icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                <?php else: ?>
                    <img src="<?php echo getProductImageUrl($image['image_path']); ?>" alt="Product Image">
                <?php endif; ?>
                <div class="media-actions">
                    <button type="button" class="btn btn-sm btn-outline-light btn-set-primary" data-id="<?php echo $image['id']; ?>" <?php echo $image['is_primary'] ? 'disabled' : ''; ?> title="Set as Primary">
                        <i class="fas fa-star"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light btn-delete-image" data-id="<?php echo $image['id']; ?>" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <?php if ($image['is_primary']): ?>
                    <span class="badge bg-primary primary-badge">Primary</span>
                <?php endif; ?>
            </div>
        </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Add More Media</label>
                        <input type="file" class="form-control" name="media[]" accept="image/*,video/*" multiple>
                        <small class="text-muted">Upload additional images or videos</small>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Original Price (₹) *</label>
                        <input type="number" class="form-control" name="price" step="0.01" min="0" 
                               value="<?php echo $product['price']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Discount Price (₹)</label>
                        <input type="number" class="form-control" name="discount_price" step="0.01" min="0"
                               value="<?php echo $product['discount_price']; ?>">
                        <small class="text-muted">Leave empty if no discount</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id">
                            <option value="0">No Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" class="form-control" name="stock_quantity" 
                               value="<?php echo $product['stock_quantity']; ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?php echo $product['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $product['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="is_featured" id="featured"
                               value="1"
                               <?php echo (isset($product['is_featured']) && $product['is_featured']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="featured">Featured Product</label>
                        <!-- Debug info -->
                        <small class="text-muted d-block">Current value: <?php echo $product['is_featured'] ?? 'NULL'; ?></small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                        <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productForm = document.querySelector('form[method="POST"]');
    
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;
            
            // Send FormData directly (not URL-encoded) to handle file uploads
            fetch(window.location.href, {
                method: 'POST',
                body: formData  // Send FormData directly, not as URL-encoded string
            })
            .then(response => response.text())
            .then(html => {
                // Create a temporary div to parse the response
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // Look for success or error messages in the response
                const successAlert = tempDiv.querySelector('.alert-success');
                const errorAlert = tempDiv.querySelector('.alert-danger');
                const debugInfo = tempDiv.querySelector('.alert-info');
                
                // Show debug info if present
                if (debugInfo) {
                    console.log('Debug info:', debugInfo.innerHTML);
                }
                
                if (successAlert) {
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
                    alertDiv.innerHTML = successAlert.innerHTML + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    productForm.appendChild(alertDiv);
                    
                    // Always reload after successful update to show new images
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else if (errorAlert) {
                    // Show error message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                    alertDiv.innerHTML = errorAlert.innerHTML + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    productForm.appendChild(alertDiv);
                }
                
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating product. Please try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
    
    // Handle delete image button click
    document.addEventListener('click', function(e) {
        // Delete image
        if (e.target.closest('.btn-delete-image')) {
            const button = e.target.closest('.btn-delete-image');
            const imageId = button.getAttribute('data-id');
            const productId = <?php echo $productId; ?>;
            
            if (confirm('Are you sure you want to delete this image?')) {
                fetch(`delete-image.php?id=${imageId}&product=${productId}`, {
                    method: 'GET'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the image container from the DOM
                        button.closest('.col-4').remove();
                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
                        alertDiv.innerHTML = 'Image deleted successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        document.querySelector('.card-body').prepend(alertDiv);
                        // Reload after a short delay
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert(data.message || 'Failed to delete image');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the image');
                });
            }
        }
        
        // Set primary image
        if (e.target.closest('.btn-set-primary')) {
            const button = e.target.closest('.btn-set-primary');
            const imageId = button.getAttribute('data-id');
            const productId = <?php echo $productId; ?>;
            
            // Show loading state
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            fetch('set-primary-image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `image_id=${imageId}&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show updated primary image
                    location.reload();
                } else {
                    alert(data.message || 'Failed to set primary image');
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while setting the primary image');
                button.innerHTML = originalHTML;
                button.disabled = false;
            });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
