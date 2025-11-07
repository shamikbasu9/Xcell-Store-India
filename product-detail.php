<?php
require_once 'includes/header.php';

$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    header('Location: products.php');
    exit;
}

$conn = getDBConnection();

// Get product details
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        WHERE p.id = ? AND p.status = 'active'");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: products.php');
    exit;
}

$pageTitle = $product['title'];

// Update views
$conn->query("UPDATE products SET views = views + 1 WHERE id = $productId");

// Get images
$images = [];
$result = $conn->query("SELECT * FROM product_images WHERE product_id = $productId ORDER BY is_primary DESC, display_order");
while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}

// Get related products
$relatedProducts = [];
$result = $conn->query("SELECT p.*, pi.image_path FROM products p 
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE 
                        WHERE p.category_id = {$product['category_id']} AND p.id != $productId AND p.status = 'active' 
                        ORDER BY RAND() LIMIT 4");
while ($row = $result->fetch_assoc()) {
    $relatedProducts[] = $row;
}

$conn->close();
?>

<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php">Plants</a></li>
            <?php if ($product['category_name']): ?>
                <li class="breadcrumb-item"><a href="products.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['title']); ?></li>
        </ol>
    </nav>
    
    <div class="row g-4">
        <!-- Product Images -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body p-0">
                    <?php if (!empty($images)): ?>
                        <div id="productCarousel" class="carousel slide" data-mdb-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="uploads/products/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                             class="d-block w-100" style="height: 500px; object-fit: cover;" 
                                             alt="<?php echo htmlspecialchars($product['title']); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($images) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-mdb-target="#productCarousel" data-mdb-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-mdb-target="#productCarousel" data-mdb-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 500px;">
                            <i class="fas fa-leaf fa-10x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Product Info -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <?php if ($product['category_name']): ?>
                        <span class="badge bg-success mb-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <?php endif; ?>
                    
                    <h2 class="fw-bold mb-3"><?php echo htmlspecialchars($product['title']); ?></h2>
                    
                    <div class="mb-3">
                        <?php if (!empty($product['discount_price']) && $product['discount_price'] < $product['price']): ?>
                            <?php $discount = calculateDiscountPercentage($product['price'], $product['discount_price']); ?>
                            <span class="badge bg-danger fs-6 mb-2"><?php echo $discount; ?>% OFF</span>
                            <div class="d-flex align-items-center gap-3">
                                <h3 class="text-success fw-bold mb-0"><?php echo formatCurrency($product['discount_price']); ?></h3>
                                <h5 class="text-muted text-decoration-line-through mb-0"><?php echo formatCurrency($product['price']); ?></h5>
                                <span class="text-muted"><i class="fas fa-eye"></i> <?php echo $product['views']; ?> views</span>
                            </div>
                            <p class="text-success mb-0">You save <?php echo formatCurrency($product['price'] - $product['discount_price']); ?>!</p>
                        <?php else: ?>
                            <div class="d-flex align-items-center gap-3">
                                <h3 class="text-success fw-bold mb-0"><?php echo formatCurrency($product['price']); ?></h3>
                                <span class="text-muted"><i class="fas fa-eye"></i> <?php echo $product['views']; ?> views</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($product['description']): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-2">Description</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($product['care_instructions']): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-2"><i class="fas fa-info-circle text-success"></i> Care Instructions</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['care_instructions'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantity</label>
                        <input type="number" id="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" style="max-width: 120px;">
                    </div>
                    
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <div class="d-grid gap-2">
                            <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-success btn-lg">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                            <button onclick="buyNow(<?php echo $product['id']; ?>)" class="btn btn-outline-success btn-lg">
                                <i class="fas fa-bolt"></i> Buy Now
                            </button>
                        </div>
                        <p class="text-muted mt-2 mb-0"><i class="fas fa-check-circle text-success"></i> In Stock (<?php echo $product['stock_quantity']; ?> available)</p>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg w-100" disabled>
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="mt-5">
            <h3 class="fw-bold mb-4">Related Plants</h3>
            <div class="row g-4">
                <?php foreach ($relatedProducts as $related): ?>
                    <div class="col-6 col-md-3">
                        <div class="card product-card h-100">
                            <a href="product-detail.php?id=<?php echo $related['id']; ?>">
                                <?php if ($related['image_path']): ?>
                                    <img src="uploads/products/<?php echo htmlspecialchars($related['image_path']); ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($related['title']); ?>">
                                <?php else: ?>
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 250px;">
                                        <i class="fas fa-leaf fa-4x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </a>
                            <div class="card-body">
                                <h6 class="card-title fw-bold">
                                    <a href="product-detail.php?id=<?php echo $related['id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                </h6>
                                <?php if (!empty($related['discount_price']) && $related['discount_price'] < $related['price']): ?>
                                    <?php $relDiscount = calculateDiscountPercentage($related['price'], $related['discount_price']); ?>
                                    <span class="badge bg-danger mb-1"><?php echo $relDiscount; ?>% OFF</span>
                                    <div>
                                        <span class="text-success fw-bold"><?php echo formatCurrency($related['discount_price']); ?></span>
                                        <span class="text-muted text-decoration-line-through small ms-1"><?php echo formatCurrency($related['price']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <p class="text-success fw-bold mb-0"><?php echo formatCurrency($related['price']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    fetch('cart-action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=add&product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Added to cart!');
            location.reload();
        } else {
            alert(data.message || 'Failed to add to cart');
        }
    });
}

function buyNow(productId) {
    const quantity = document.getElementById('quantity').value;
    fetch('cart-action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=add&product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'checkout.php';
        } else {
            alert(data.message || 'Failed to add to cart');
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
