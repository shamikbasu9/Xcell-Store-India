<?php
require_once 'includes/header.php';

// Make these variables global so they're available in header.php
$product = [];
$images = [];

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

// Set the page title for the header
$pageTitle = $product ? $product['title'] : 'Product Not Found';

if (!$product) {
    header('Location: products.php');
    exit;
}

$pageTitle = $product['title'];

// Update views
$conn->query("UPDATE products SET views = views + 1 WHERE id = $productId");

// Get images
$result = $conn->query("SELECT * FROM product_images WHERE product_id = $productId ORDER BY is_primary DESC, display_order");
while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}

// Prepare social sharing data
$shareUrl = 'https://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?'); // Force HTTPS and remove existing query params
$shareUrl = $shareUrl . '?id=' . $productId; // Add product ID back
$shareTitle = htmlspecialchars($product['title']);
$shareDescription = htmlspecialchars(substr(strip_tags($product['description']), 0, 155));
$shareImage = !empty($images[0]['image_path']) ? 
    'https://' . $_SERVER['HTTP_HOST'] . '/uploads/products/' . $images[0]['image_path'] : 
    'https://' . $_SERVER['HTTP_HOST'] . '/assets/images/logo.jpg';
$sharePrice = formatCurrency(!empty($product['discount_price']) && $product['discount_price'] < $product['price'] ? $product['discount_price'] : $product['price']);
$siteName = defined('SITE_NAME') ? SITE_NAME : 'Plant Store';

// Generate cache-busting parameter
$cacheBuster = '&v=' . time(); // Use & instead of ? since we already have ?id=

// Set the page title
$pageTitle = $shareTitle . ' - ' . $siteName;
?>

<!-- Primary Meta Tags -->
<title><?php echo $pageTitle; ?></title>
<meta name="description" content="<?php echo $shareDescription; ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="product">
<meta property="og:url" content="<?php echo $shareUrl . $cacheBuster; ?>">
<meta property="og:title" content="<?php echo $shareTitle; ?>">
<meta property="og:description" content="<?php echo $shareDescription; ?>">
<meta property="og:image" content="<?php echo $shareImage . $cacheBuster; ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:site_name" content="<?php echo $siteName; ?>">
<meta property="product:price:amount" content="<?php echo !empty($product['discount_price']) ? $product['discount_price'] : $product['price']; ?>">
<meta property="product:price:currency" content="INR">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?php echo $shareUrl . $cacheBuster; ?>">
<meta name="twitter:title" content="<?php echo $shareTitle; ?>">
<meta name="twitter:description" content="<?php echo $shareDescription; ?>">
<meta name="twitter:image" content="<?php echo $shareImage . $cacheBuster; ?>">
<meta name="twitter:image:alt" content="<?php echo $shareTitle; ?>">

<!-- WhatsApp Specific -->
<meta property="og:image:secure_url" content="<?php echo $shareImage . $cacheBuster; ?>">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:alt" content="<?php echo $shareTitle; ?>">

<?php
// Get related products
$relatedProducts = [];
$query = "SELECT p.*, pi.image_path FROM products p 
          LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE 
          WHERE p.status = 'active' AND p.id != $productId";

// Add category filter only if category_id is not null
if (!empty($product['category_id'])) {
    $query .= " AND p.category_id = " . intval($product['category_id']);
}

// Complete the query with ordering and limit
$query .= " ORDER BY RAND() LIMIT 4";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $relatedProducts[] = $row;
    }
} else {
    // Log the error for debugging
    error_log("Error in related products query: " . $conn->error);
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
            <li class="breadcrumb-item active"><?php echo displayTitle($product['title']); ?></li>
        </ol>
    </nav>
    
    <!-- Share Options at Top -->
    <div class="card mb-4 border-success shadow-sm">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-share-alt text-success fs-5"></i>
                    <span class="fw-bold text-dark d-none d-sm-inline">Share:</span>
                </div>
                <div class="d-flex gap-2">
                    <?php
                    $whatsappText = "🌿 *" . $shareTitle . "*\n\n" . $shareDescription . "\n\n💰 Price: " . $sharePrice . "\n\n🔗 " . $shareUrl . $cacheBuster;
                    $encodedText = urlencode($whatsappText);
                    ?>
                    <!-- WhatsApp -->
                    <a href="https://api.whatsapp.com/send?text=<?php echo $encodedText; ?>" 
                       class="btn btn-success rounded-circle p-2 d-flex align-items-center justify-content-center" 
                       style="width: 40px; height: 40px;"
                       target="_blank" 
                       title="Share on WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    
                    <!-- Facebook -->
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($shareUrl . $cacheBuster); ?>" 
                       class="btn btn-primary rounded-circle p-2 d-flex align-items-center justify-content-center" 
                       style="width: 40px; height: 40px;"
                       target="_blank" 
                       title="Share on Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    
                    <!-- Twitter -->
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($shareUrl . $cacheBuster); ?>&text=<?php echo urlencode($shareTitle); ?>" 
                       class="btn btn-info rounded-circle p-2 d-flex align-items-center justify-content-center text-white" 
                       style="width: 40px; height: 40px; background-color: #1DA1F2;"
                       target="_blank" 
                       title="Share on Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    
                    <!-- Copy Link -->
                    <button onclick="copyToClipboard('<?php echo $shareUrl . $cacheBuster; ?>')"
                            class="btn btn-secondary rounded-circle p-2 d-flex align-items-center justify-content-center" 
                            style="width: 40px; height: 40px;"
                            title="Copy Link">
                        <i class="fas fa-link"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
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
                                             class="d-block w-100" style="height: 500px; object-fit: contain;" 
                                             alt="<?php echo displayTitle($product['title']); ?>">
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
                    
                    <h2 class="fw-bold mb-3"><?php echo displayTitle($product['title']); ?></h2>
                    
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
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars(html_entity_decode($product['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8', false)); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($product['care_instructions']): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-2"><i class="fas fa-info-circle text-success"></i> Care Instructions</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars(html_entity_decode($product['care_instructions'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8', false)); ?></p>
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
                                        <?php echo displayTitle($related['title']); ?>
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
// Copy to clipboard function with modern approach
function copyToClipboard(text) {
    const button = event.target.closest('button');
    
    navigator.clipboard.writeText(text).then(() => {
        // You can replace this with a toast notification if you have one
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.title = 'Copied!';
        
        // Revert back after 2 seconds
        setTimeout(() => {
            button.innerHTML = originalText;
            button.title = 'Copy Link';
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy text: ', err);
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.title = 'Copied!';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.title = 'Copy Link';
            }, 2000);
        } catch (err) {
            console.error('Fallback copy failed: ', err);
            alert('Failed to copy link. Please try again.');
        }
        
        document.body.removeChild(textArea);
    });
}

// Social Sharing Functions
function shareOnWhatsApp(text, url, imageUrl) {
    // Open WhatsApp with the text and URL
    const whatsappUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(text + '\n\n' + url)}`;
    window.open(whatsappUrl, '_blank');
}

// Initialize MDB carousel and sharing buttons
document.addEventListener('DOMContentLoaded', function() {
    // Initialize carousel if it exists
    const carouselElement = document.getElementById('productCarousel');
    if (carouselElement) {
        new mdb.Carousel(carouselElement, {
            interval: 5000,
            ride: 'carousel'
        });
    }
    
    // Initialize WhatsApp sharing
    document.querySelectorAll('.share-whatsapp').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const text = this.getAttribute('data-share-text');
            const url = this.getAttribute('data-share-url');
            const image = this.getAttribute('data-share-image');
            shareOnWhatsApp(text, url, image);
        });
    });
});

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