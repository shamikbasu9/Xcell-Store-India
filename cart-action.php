<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

switch ($action) {
    case 'add':
        $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        if ($productId <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
            exit;
        }
        
        // Verify product exists and is active
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, stock_quantity FROM products WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $conn->close();
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        if ($product['stock_quantity'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
            exit;
        }
        
        addToCart($productId, $quantity);
        echo json_encode(['success' => true, 'message' => 'Added to cart']);
        break;
        
    case 'update':
        $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
            exit;
        }
        
        if ($quantity <= 0) {
            removeFromCart($productId);
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
        
        echo json_encode(['success' => true, 'message' => 'Cart updated']);
        break;
        
    case 'remove':
        $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
            exit;
        }
        
        removeFromCart($productId);
        echo json_encode(['success' => true, 'message' => 'Removed from cart']);
        break;
        
    case 'clear':
        clearCart();
        echo json_encode(['success' => true, 'message' => 'Cart cleared']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
