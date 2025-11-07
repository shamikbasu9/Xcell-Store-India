<?php
$pageTitle = 'Order Details';
require_once 'includes/header.php';

$orderId = intval($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: orders.php');
    exit;
}

$conn = getDBConnection();

// Get order details
$stmt = $conn->prepare("SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
                        a.full_name, a.phone as shipping_phone, a.address_line1, a.address_line2, 
                        a.city, a.state, a.pincode
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id
                        LEFT JOIN addresses a ON o.shipping_address_id = a.id
                        WHERE o.id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get order items
$orderItems = [];
$stmt = $conn->prepare("SELECT oi.*, p.slug, pi.image_path 
                        FROM order_items oi
                        LEFT JOIN products p ON oi.product_id = p.id
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
                        WHERE oi.order_id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orderItems[] = $row;
}
$stmt->close();
$conn->close();
?>

<div class="mb-3">
    <a href="orders.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Orders
    </a>
</div>

<div class="row g-4">
    <!-- Order Details -->
    <div class="col-lg-8">
        <!-- Order Info -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <small class="text-muted">Order Date</small>
                        <h6><?php echo date('F d, Y H:i', strtotime($order['created_at'])); ?></h6>
                    </div>
                    <div class="col-md-6 mb-3">
                        <small class="text-muted">Order Status</small>
                        <h6>
                            <span class="badge bg-<?php echo getOrderStatusClass($order['order_status']); ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </h6>
                    </div>
                    <div class="col-md-6 mb-3">
                        <small class="text-muted">Payment Method</small>
                        <h6><?php echo strtoupper($order['payment_method']); ?></h6>
                    </div>
                    <div class="col-md-6 mb-3">
                        <small class="text-muted">Payment Status</small>
                        <h6>
                            <span class="badge bg-<?php echo getPaymentStatusClass($order['payment_status']); ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </h6>
                    </div>
                    <?php if ($order['transaction_id']): ?>
                        <div class="col-12 mb-3">
                            <small class="text-muted">Transaction ID</small>
                            <h6><?php echo htmlspecialchars($order['transaction_id']); ?></h6>
                        </div>
                    <?php endif; ?>
                    <?php if ($order['tracking_number']): ?>
                        <div class="col-12 mb-3">
                            <small class="text-muted">Tracking Number</small>
                            <h6><?php echo htmlspecialchars($order['tracking_number']); ?></h6>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Order Items</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($item['image_path']): ?>
                                                <img src="../uploads/products/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                                     alt="Product" class="rounded me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($item['product_title']); ?></strong>
                                                <?php if ($item['slug']): ?>
                                                    <br><small><a href="../product-detail.php?slug=<?php echo $item['slug']; ?>" target="_blank">View Product</a></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo formatCurrency($item['price']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><strong><?php echo formatCurrency($item['price'] * $item['quantity']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Shipping Address -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Shipping Address</h5>
            </div>
            <div class="card-body">
                <?php if ($order['full_name']): ?>
                    <h6><?php echo htmlspecialchars($order['full_name']); ?></h6>
                    <p class="mb-1"><?php echo htmlspecialchars($order['address_line1']); ?></p>
                    <?php if ($order['address_line2']): ?>
                        <p class="mb-1"><?php echo htmlspecialchars($order['address_line2']); ?></p>
                    <?php endif; ?>
                    <p class="mb-1"><?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> - <?php echo htmlspecialchars($order['pincode']); ?></p>
                    <p class="mb-0"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                <?php else: ?>
                    <p class="text-muted">No shipping address</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Customer Info -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Customer</h5>
            </div>
            <div class="card-body">
                <h6><?php echo htmlspecialchars($order['user_name']); ?></h6>
                <p class="mb-1"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['user_email']); ?></p>
                <?php if ($order['user_phone']): ?>
                    <p class="mb-0"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['user_phone']); ?></p>
                <?php endif; ?>
                <hr>
                <a href="user-edit.php?id=<?php echo $order['user_id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                    View Customer Profile
                </a>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Order Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <strong><?php echo formatCurrency($order['subtotal']); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Shipping:</span>
                    <strong><?php echo formatCurrency($order['shipping_charge']); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Tax:</span>
                    <strong><?php echo formatCurrency($order['tax']); ?></strong>
                </div>
                <?php if ($order['discount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Discount:</span>
                        <strong>-<?php echo formatCurrency($order['discount']); ?></strong>
                    </div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between">
                    <strong>Total:</strong>
                    <h5 class="text-success mb-0"><?php echo formatCurrency($order['total']); ?></h5>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Actions</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="orders.php">
                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Order Status</label>
                        <select class="form-select" name="order_status">
                            <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="confirmed" <?php echo $order['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Status</label>
                        <select class="form-select" name="payment_status">
                            <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $order['payment_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="update_status" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
