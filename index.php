<?php
$pageTitle = 'Home';
require_once 'includes/header.php';

$conn = getDBConnection();

// Get featured products
$featuredProducts = [];
$result = $conn->query("SELECT p.*, pi.image_path FROM products p 
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE 
                        WHERE p.is_featured = TRUE AND p.status = 'active' 
                        ORDER BY p.sales_count DESC LIMIT 6");
while ($row = $result->fetch_assoc()) {
    $featuredProducts[] = $row;
}

// Get categories
$categories = [];
$result = $conn->query("SELECT * FROM categories LIMIT 6");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

$conn->close();
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1 class="display-4 fw-bold mb-3">Bring Nature Home</h1>
                <p class="lead mb-4">Discover beautiful, healthy plants delivered to your doorstep. Transform your space with our curated collection.</p>
                <div class="d-flex gap-3">
                    <a href="products.php" class="btn btn-light btn-lg">
                        <i class="fas fa-seedling"></i> Explore Plants
                    </a>
                    <a href="#featured" class="btn btn-outline-light btn-lg">
                        Learn More
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <i class="fas fa-leaf fa-10x opacity-25"></i>
            </div>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center fw-bold mb-4">Shop by Category</h2>
        <div class="row g-4">
            <?php foreach ($categories as $category): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="products.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                        <div class="card text-center product-card h-100">
                            <div class="card-body">
                                <i class="fas <?php echo $category['icon']; ?> fa-3x text-success mb-3"></i>
                                <h6 class="fw-bold"><?php echo htmlspecialchars($category['name']); ?></h6>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-5 bg-light" id="featured">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Featured Plants</h2>
            <a href="products.php" class="btn btn-outline-success">View All</a>
        </div>
        
        <div class="row g-4">
            <?php if (empty($featuredProducts)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-seedling fa-4x text-muted mb-3"></i>
                    <p class="text-muted">No featured products yet. Check back soon!</p>
                </div>
            <?php else: ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card product-card h-100">
                            <a href="product-detail.php?id=<?php echo $product['id']; ?>">
                                <?php if ($product['image_path']): ?>
                                    <img src="uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
                                <?php else: ?>
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 250px;">
                                        <i class="fas fa-leaf fa-4x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </a>
                            <div class="card-body">
                                <h6 class="card-title fw-bold">
                                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($product['title']); ?>
                                    </a>
                                </h6>
                                <?php if (!empty($product['discount_price']) && $product['discount_price'] < $product['price']): ?>
                                    <?php $discount = calculateDiscountPercentage($product['price'], $product['discount_price']); ?>
                                    <span class="badge bg-danger mb-1"><?php echo $discount; ?>% OFF</span>
                                    <div class="mb-2">
                                        <span class="text-success fw-bold"><?php echo formatCurrency($product['discount_price']); ?></span>
                                        <span class="text-muted text-decoration-line-through small ms-1"><?php echo formatCurrency($product['price']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <p class="text-success fw-bold mb-2"><?php echo formatCurrency($product['price']); ?></p>
                                <?php endif; ?>
                                <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-success btn-sm w-100">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Features -->
<section class="py-5">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-3">
                <i class="fas fa-truck fa-3x text-success mb-3"></i>
                <h5 class="fw-bold">Free Shipping</h5>
                <p class="text-muted">On orders above ₹500</p>
            </div>
            <div class="col-md-3">
                <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                <h5 class="fw-bold">Quality Assured</h5>
                <p class="text-muted">Healthy plants guaranteed</p>
            </div>
            <div class="col-md-3">
                <i class="fas fa-headset fa-3x text-success mb-3"></i>
                <h5 class="fw-bold">24/7 Support</h5>
                <p class="text-muted">We're here to help</p>
            </div>
            <div class="col-md-3">
                <i class="fas fa-undo fa-3x text-success mb-3"></i>
                <h5 class="fw-bold">Easy Returns</h5>
                <p class="text-muted">7-day return policy</p>
            </div>
        </div>
    </div>
</section>

<script>
function addToCart(productId) {
    fetch('cart-action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=add&product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to add to cart');
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
