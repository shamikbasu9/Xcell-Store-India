<?php
$pageTitle = 'Edit Product';
require_once 'includes/header.php';

$error = '';
$success = '';
$productId = intval($_GET['id'] ?? 0);

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
        $slug = generateSlug($title);
        
        $stmt = $conn->prepare("UPDATE products SET title = ?, slug = ?, description = ?, care_instructions = ?, price = ?, discount_price = ?, category_id = ?, stock_quantity = ?, status = ?, is_featured = ? WHERE id = ?");
        $stmt->bind_param("ssssddissii", $title, $slug, $description, $careInstructions, $price, $discountPrice, $categoryId, $stockQuantity, $status, $isFeatured, $productId);
        
        if ($stmt->execute()) {
            // Handle new image uploads
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $maxOrder = 0;
                if (!empty($images)) {
                    $maxOrder = max(array_column($images, 'display_order'));
                }
                
                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $imageFile = [
                            'name' => $_FILES['images']['name'][$key],
                            'type' => $_FILES['images']['type'][$key],
                            'tmp_name' => $tmpName,
                            'error' => $_FILES['images']['error'][$key],
                            'size' => $_FILES['images']['size'][$key]
                        ];
                        
                        $upload = uploadFile($imageFile, PRODUCT_IMAGES_DIR);
                        if ($upload['success']) {
                            $isPrimary = empty($images) ? 1 : 0;
                            $maxOrder++;
                            $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, is_primary, display_order) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("isii", $productId, $upload['filename'], $isPrimary, $maxOrder);
                            $stmt->execute();
                        }
                    }
                }
            }
            
            header('Location: products.php?updated=1');
            exit;
        } else {
            $error = 'Failed to update product';
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
                            <label class="form-label">Current Images</label>
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
                               <?php echo $product['is_featured'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="featured">Featured Product</label>
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

<?php require_once 'includes/footer.php'; ?>
