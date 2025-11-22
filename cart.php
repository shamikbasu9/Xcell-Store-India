<?php
$pageTitle = 'Shopping Cart';
require_once 'includes/header.php';

$cartItems = getCartItems();
$subtotal = getCartTotal();
$shipping = calculateShipping($subtotal);
$total = $subtotal + $shipping;
?>

<div class="container my-4">
    <h2 class="fw-bold mb-4"><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>
    
    <?php if (empty($cartItems)): ?>
        <div class="card text-center py-5">
            <div class="card-body">
                <i class="fas fa-shopping-cart fa-5x text-muted mb-3"></i>
                <h4 class="text-muted mb-3">Your cart is empty</h4>
                <a href="products.php" class="btn btn-success btn-lg">
                    <i class="fas fa-seedling"></i> Start Shopping
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="row align-items-center mb-3 pb-3 border-bottom">
                                <div class="col-3 col-md-2">
                                    <?php if ($item['image_path']): ?>
                                        <img src="uploads/products/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                             class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 80px;">
                                            <i class="fas fa-leaf fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-5 col-md-4">
                                    <h6 class="fw-bold mb-1">
                                        <a href="product-detail.php?id=<?php echo $item['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </a>
                                    </h6>
                                    <?php if (!empty($item['discount_price']) && $item['discount_price'] < $item['price']): ?>
                                        <div>
                                            <span class="text-success fw-bold"><?php echo formatCurrency($item['discount_price']); ?></span>
                                            <span class="text-muted text-decoration-line-through small ms-1"><?php echo formatCurrency($item['price']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-success fw-bold mb-0"><?php echo formatCurrency($item['price']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-4 col-md-3">
                                    <div class="input-group input-group-sm">
                                        <button class="btn btn-outline-secondary" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" class="form-control text-center" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['stock_quantity']; ?>" readonly>
                                        <button class="btn btn-outline-secondary" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-3 text-end mt-2 mt-md-0">
                                    <p class="fw-bold mb-2"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></p>
                                    <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Order Summary</h5>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span><?php echo formatCurrency($subtotal); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span><?php echo $shipping > 0 ? formatCurrency($shipping) : 'FREE'; ?></span>
                        </div>
                        
                        <?php if ($subtotal < FREE_SHIPPING_ABOVE): ?>
                            <div class="alert alert-info py-2 px-3 mb-2">
                                <small>Add <?php echo formatCurrency(FREE_SHIPPING_ABOVE - $subtotal); ?> more for free shipping!</small>
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong class="text-success"><?php echo formatCurrency($total); ?></strong>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="checkout.php" class="btn btn-success btn-lg">
                                <i class="fas fa-lock"></i> Proceed to Checkout
                            </a>
                            <a href="products.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(productId, quantity) {
    if (quantity < 1) {
        if (!confirm('Remove this item from cart?')) return;
        quantity = 0;
    }
    
    fetch('cart-action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=update&product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update cart');
        }
    });
}

function removeFromCart(productId) {
    if (!confirm('Remove this item from cart?')) return;
    
    fetch('cart-action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=remove&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to remove item');
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
