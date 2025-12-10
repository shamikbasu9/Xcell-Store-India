<?php
$pageTitle = 'Products';
require_once 'includes/header.php';

$conn = getDBConnection();

// Get filters
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 10000;
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';

// Build query
$sql = "SELECT p.*, pi.image_path, c.name as category_name 
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active'";

if ($categoryId > 0) {
    $sql .= " AND p.category_id = $categoryId";
}
if ($search) {
    $sql .= " AND (p.title LIKE '%$search%' OR p.description LIKE '%$search%')";
}
if ($minPrice > 0) {
    $sql .= " AND p.price >= $minPrice";
}
if ($maxPrice < 10000) {
    $sql .= " AND p.price <= $maxPrice";
}

// Sorting
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.stock_quantity > 0 DESC, p.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.stock_quantity > 0 DESC, p.price DESC";
        break;
    case 'popular':
        $sql .= " ORDER BY p.stock_quantity > 0 DESC, p.sales_count DESC";
        break;
    default:
        $sql .= " ORDER BY p.stock_quantity > 0 DESC, p.created_at DESC";
}

$result = $conn->query($sql);
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Get categories for filter
$categories = [];
$catResult = $conn->query("SELECT * FROM categories ORDER BY name");
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row;
}

$conn->close();
?>

<div class="container my-4">
    <div class="row">
        <!-- Filters Sidebar (Desktop) -->
        <div class="col-lg-3 mb-4 d-none d-lg-block">
            <div class="card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Filters</h5>
                    
                    <form method="GET" action="">
                        <!-- Search -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Search</label>
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search plants..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <!-- Category -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <select class="form-select" name="category">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Price Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control" name="min_price" 
                                           placeholder="Min" value="<?php echo $minPrice; ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" name="max_price" 
                                           placeholder="Max" value="<?php echo $maxPrice; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sort -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Sort By</label>
                            <select class="form-select" name="sort">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                                <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Popular</option>
                                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">Apply Filters</button>
                        <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Clear</a>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="col-lg-9">
            <!-- Mobile Filters -->
            <div class="d-lg-none mb-3">
                <div class="row g-2">
                    <div class="col-6">
                        <input type="text" class="form-control" placeholder="Search..." 
                               onchange="window.location.href='?search=' + this.value">
                    </div>
                    <div class="col-6">
                        <select class="form-select" onchange="window.location.href='?sort=' + this.value">
                            <option value="newest">Newest</option>
                            <option value="popular">Popular</option>
                            <option value="price_low">Price ↑</option>
                            <option value="price_high">Price ↓</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">
                    <?php echo $categoryId > 0 ? htmlspecialchars($categories[array_search($categoryId, array_column($categories, 'id'))]['name'] ?? 'Products') : 'All Plants'; ?>
                </h4>
                <span class="text-muted"><?php echo count($products); ?> products</span>
            </div>
            
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-seedling fa-5x text-muted mb-3"></i>
                    <h5 class="text-muted">No products found</h5>
                    <p class="text-muted">Try adjusting your filters</p>
                </div>
            <?php else: ?>
                <div class="row g-3 g-md-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card product-card h-100">
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>">
                                    <?php if ($product['image_path']): ?>
                                        <img src="uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>" 
                                             alt="<?php echo displayTitle($product['title']); ?>">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 250px;">
                                            <i class="fas fa-leaf fa-4x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                
                                <div class="card-body">
                                    <?php if ($product['category_name']): ?>
                                        <span class="badge bg-success mb-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    <?php endif; ?>
                                    
                                    <h6 class="card-title fw-bold">
                                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo displayTitle($product['title']); ?>
                                        </a>
                                    </h6>
                                    
                                    <div class="mb-2">
                                        <?php if (!empty($product['discount_price']) && $product['discount_price'] < $product['price']): ?>
                                            <?php $discount = calculateDiscountPercentage($product['price'], $product['discount_price']); ?>
                                            <span class="badge bg-danger mb-1"><?php echo $discount; ?>% OFF</span>
                                            <div>
                                                <span class="text-success fw-bold fs-5"><?php echo formatCurrency($product['discount_price']); ?></span>
                                                <span class="text-muted text-decoration-line-through ms-2"><?php echo formatCurrency($product['price']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-success fw-bold mb-0"><?php echo formatCurrency($product['price']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                                                class="btn btn-success btn-sm w-100">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm w-100" disabled>
                                            Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

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
            alert('Added to cart!')
            location.reload();
        } else {
            alert(data.message || 'Failed to add to cart');
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
