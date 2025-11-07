<?php
$pageTitle = 'Order Details';
require_once 'includes/header.php';
requireUserLogin();

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user_id'];

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT o.*, a.* FROM orders o 
                        LEFT JOIN addresses a ON o.shipping_address_id = a.id 
                        WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: orders.php');
    exit;
}

$items = [];
$result = $conn->query("SELECT * FROM order_items WHERE order_id = $orderId");
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$conn->close();
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0"><i class="fas fa-receipt"></i> Order Details</h2>
        <a href="orders.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>
    
    <div class="row g-4">
        <!-- Order Info -->
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted mb-1">Order Number</h6>
                            <p class="fw-bold mb-0"><?php echo htmlspecialchars($order['order_number']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted mb-1">Order Date</h6>
                            <p class="mb-0"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted mb-1">Payment Status</h6>
                            <span class="badge bg-<?php echo getPaymentStatusClass($order['payment_status']); ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted mb-1">Order Status</h6>
                            <span class="badge bg-<?php echo getOrderStatusClass($order['order_status']); ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Order Items</h5>
                    <?php foreach ($items as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['product_title']); ?></h6>
                                <p class="text-muted mb-0">Quantity: <?php echo $item['quantity']; ?> × <?php echo formatCurrency($item['price']); ?></p>
                            </div>
                            <p class="fw-bold mb-0"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Shipping Address -->
            <?php if ($order['full_name']): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Shipping Address</h5>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></p>
                        <p class="mb-1"><?php echo htmlspecialchars($order['address_line1']); ?></p>
                        <?php if ($order['address_line2']): ?>
                            <p class="mb-1"><?php echo htmlspecialchars($order['address_line2']); ?></p>
                        <?php endif; ?>
                        <p class="mb-1"><?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> - <?php echo htmlspecialchars($order['pincode']); ?></p>
                        <p class="mb-0">Phone: <?php echo htmlspecialchars($order['phone']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Order Summary</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span><?php echo formatCurrency($order['subtotal']); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping</span>
                        <span><?php echo formatCurrency($order['shipping_charge']); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax</span>
                        <span><?php echo formatCurrency($order['tax']); ?></span>
                    </div>
                    
                    <?php if ($order['discount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Discount</span>
                            <span>-<?php echo formatCurrency($order['discount']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total</strong>
                        <strong class="text-success"><?php echo formatCurrency($order['total']); ?></strong>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-success" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Invoice
                        </button>
                        <?php if ($order['order_status'] === 'processing'): ?>
                            <button class="btn btn-outline-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                <i class="fas fa-times"></i> Cancel Order
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order?')) return;
    
    // Implementation for order cancellation
    alert('Order cancellation feature will be implemented');
}
</script>

<?php require_once 'includes/footer.php'; ?>
