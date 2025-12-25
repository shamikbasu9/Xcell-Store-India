<?php
$pageTitle = 'Add Product';
require_once 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $careInstructions = sanitize($_POST['care_instructions']);
    $price = floatval($_POST['price']);
    $discountPrice = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
    $categoryId = intval($_POST['category_id']);
    $stockQuantity = intval($_POST['stock_quantity']);
    $status = sanitize($_POST['status']);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    if (empty($title) || empty($price)) {
        $error = 'Please fill in all required fields';
    } else {
        $conn = getDBConnection();
        $slug = generateSlug($title);
        
        // If category_id is 0, set it to NULL for the database
        $categoryIdForDb = $categoryId > 0 ? $categoryId : null;
        
        // Prepare the statement with the correct parameter types
        $stmt = $conn->prepare("INSERT INTO products (title, slug, description, care_instructions, price, discount_price, category_id, stock_quantity, status, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Determine the parameter types based on whether category_id is NULL or not
        $paramTypes = $categoryIdForDb === null ? "ssssddisss" : "ssssddissi";
        
        // Bind parameters
        if ($categoryIdForDb === null) {
            $stmt->bind_param($paramTypes, $title, $slug, $description, $careInstructions, $price, $discountPrice, $categoryIdForDb, $stockQuantity, $status, $isFeatured);
        } else {
            $stmt->bind_param($paramTypes, $title, $slug, $description, $careInstructions, $price, $discountPrice, $categoryId, $stockQuantity, $status, $isFeatured);
        }
        
        if ($stmt->execute()) {
            $productId = $conn->insert_id;
            
            // Handle media uploads (images and videos)
            if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
                $isPrimary = true;
                foreach ($_FILES['media']['tmp_name'] as $key => $tmpName) {
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
                        $uploadDir = $isVideo ? (defined('PRODUCT_VIDEOS_DIR') ? PRODUCT_VIDEOS_DIR : PRODUCT_IMAGES_DIR) : PRODUCT_IMAGES_DIR;
                        
                        $upload = uploadFile($mediaFile, $uploadDir);
                        if ($upload['success']) {
                            $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, media_type, is_primary, display_order) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("issii", $productId, $upload['filename'], $mediaType, $isPrimary, $key);
                            $stmt->execute();
                            $isPrimary = false;
                        }
                    }
                }
            }
            
            header('Location: products.php?success=1');
            exit;
        } else {
            $error = 'Failed to add product';
        }
        
        $stmt->close();
        $conn->close();
    }
}

// Get categories
$conn = getDBConnection();
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
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">Product Title *</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Care Instructions</label>
                        <textarea class="form-control" name="care_instructions" rows="4"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Product Media (Images & Videos)</label>
                        <input type="file" class="form-control" name="media[]" accept="image/*,video/*" multiple>
                        <small class="text-muted">First file will be the primary media. Supported formats: JPG, PNG, GIF, MP4, WebM, OGG, MOV</small>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Original Price (₹) *</label>
                        <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Discount Price (₹)</label>
                        <input type="number" class="form-control" name="discount_price" step="0.01" min="0">
                        <small class="text-muted">Leave empty if no discount</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id">
                            <option value="0">No Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" class="form-control" name="stock_quantity" value="0" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="is_featured" id="featured">
                        <label class="form-check-label" for="featured">Featured Product</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Add Product
                        </button>
                        <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
