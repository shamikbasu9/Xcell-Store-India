<?php
$pageTitle = 'Order Success';
require_once 'includes/header.php';
requireUserLogin();

$orderId = isset($_GET['order']) ? intval($_GET['order']) : 0;

if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$order) {
    header('Location: orders.php');
    exit;
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card text-center">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                        <h2 class="fw-bold">Order Placed Successfully!</h2>
                        <p class="text-muted">Thank you for your purchase</p>
                    </div>
                    
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <h6 class="text-muted mb-1">Order Number</h6>
                                    <h5 class="fw-bold"><?php echo htmlspecialchars($order['order_number']); ?></h5>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Total Amount</h6>
                                    <h5 class="fw-bold text-success"><?php echo formatCurrency($order['total']); ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        You will receive an order confirmation email shortly.
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="orders.php" class="btn btn-success btn-lg">
                            <i class="fas fa-box"></i> View Orders
                        </a>
                        <a href="products.php" class="btn btn-outline-success btn-lg">
                            <i class="fas fa-seedling"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
