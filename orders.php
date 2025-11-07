<?php
$pageTitle = 'My Orders';
require_once 'includes/header.php';
requireUserLogin();

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

$orders = [];
$result = $conn->query("SELECT * FROM orders WHERE user_id = $userId ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$conn->close();
?>

<div class="container my-4">
    <h2 class="fw-bold mb-4"><i class="fas fa-box"></i> My Orders</h2>
    
    <?php if (empty($orders)): ?>
        <div class="card text-center py-5">
            <div class="card-body">
                <i class="fas fa-box-open fa-5x text-muted mb-3"></i>
                <h4 class="text-muted mb-3">No orders yet</h4>
                <a href="products.php" class="btn btn-success btn-lg">
                    <i class="fas fa-seedling"></i> Start Shopping
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($orders as $order): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3 mb-3 mb-md-0">
                                    <h6 class="text-muted mb-1">Order Number</h6>
                                    <p class="fw-bold mb-0"><?php echo htmlspecialchars($order['order_number']); ?></p>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small>
                                </div>
                                
                                <div class="col-md-2 mb-3 mb-md-0">
                                    <h6 class="text-muted mb-1">Total</h6>
                                    <p class="fw-bold text-success mb-0"><?php echo formatCurrency($order['total']); ?></p>
                                </div>
                                
                                <div class="col-md-2 mb-3 mb-md-0">
                                    <h6 class="text-muted mb-1">Payment</h6>
                                    <span class="badge bg-<?php echo getPaymentStatusClass($order['payment_status']); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                                
                                <div class="col-md-2 mb-3 mb-md-0">
                                    <h6 class="text-muted mb-1">Status</h6>
                                    <span class="badge bg-<?php echo getOrderStatusClass($order['order_status']); ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </div>
                                
                                <div class="col-md-3 text-md-end">
                                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
