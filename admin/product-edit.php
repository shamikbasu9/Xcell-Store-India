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
        
        $stmt = $conn->prepare("UPDATE products SET title = ?, slug = ?, description = ?, care_instructions = ?, price = ?, discount_price = ?, category_id = ?, stock_quantity = ?, status = ?, is_featured = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ssssddissii", $title, $slug, $description, $careInstructions, $price, $discountPrice, $categoryId, $stockQuantity, $status, $isFeatured, $productId);
        
        // Debug: Log the SQL and parameters
        error_log("SQL UPDATE - is_featured value being set: " . $isFeatured);
        
        if ($stmt->execute()) {
            // Handle new image uploads
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                error_log("Image upload detected - files: " . print_r($_FILES['images'], true));
                
                // Debug: Count existing images before upload
                $existingCount = count($images);
                
                // Re-fetch images to ensure we have the latest state
                $currentImages = [];
                $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $currentImages[] = $row;
                }
                $stmt->close();
                
                // Debug: Log current image count
                error_log("Product ID: {$productId}, Existing images: {$existingCount}, Current images from DB: " . count($currentImages));
                
                $maxOrder = 0;
                if (!empty($currentImages)) {
                    $maxOrder = max(array_column($currentImages, 'display_order'));
                }
                
                $uploadedCount = 0;
                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    error_log("Processing file {$key}: error={$_FILES['images']['error'][$key]}, tmp_name={$tmpName}");
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $imageFile = [
                            'name' => $_FILES['images']['name'][$key],
                            'type' => $_FILES['images']['type'][$key],
                            'tmp_name' => $tmpName,
                            'error' => $_FILES['images']['error'][$key],
                            'size' => $_FILES['images']['size'][$key]
                        ];
                        
                        error_log("Attempting to upload file: " . $imageFile['name']);
                        $upload = uploadFile($imageFile, PRODUCT_IMAGES_DIR);
                        error_log("Upload result: " . print_r($upload, true));
                        
                        if ($upload['success']) {
                            $isPrimary = empty($currentImages) && $uploadedCount === 0 ? 1 : 0;
                            $maxOrder++;
                            $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, is_primary, display_order) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("isii", $productId, $upload['filename'], $isPrimary, $maxOrder);
                            if ($stmt->execute()) {
                                $uploadedCount++;
                                error_log("Successfully inserted image: " . $upload['filename']);
                            } else {
                                error_log("Failed to insert image into database: " . $stmt->error);
                            }
                            $stmt->close();
                        } else {
                            error_log("File upload failed: " . $upload['message']);
                        }
                    } else {
                        error_log("File upload error code: " . $_FILES['images']['error'][$key]);
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
                            <div class="row g-2">
                                <?php foreach ($images as $image): ?>
                                    <div class="col-4">
                                        <div class="position-relative">
                                            <img src="../uploads/products/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                 class="img-fluid rounded" alt="Product image">
                                            <?php if ($image['is_primary']): ?>
                                                <span class="badge bg-success position-absolute top-0 start-0 m-2">Primary</span>
                                            <?php endif; ?>
                                            <a href="delete-image.php?id=<?php echo $image['id']; ?>&product=<?php echo $productId; ?>" 
                                               class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2"
                                               onclick="return confirm('Delete this image?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Add More Images</label>
                        <input type="file" class="form-control" name="images[]" accept="image/*" multiple>
                        <small class="text-muted">Upload additional images</small>
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
});
</script>

<?php require_once 'includes/footer.php'; ?>
