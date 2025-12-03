<?php
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateSlug($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function formatCurrency($amount) {
    $amount = $amount ?? 0;
    return '₹' . number_format((float)$amount, 2);
}

function calculateDiscountPercentage($originalPrice, $discountPrice) {
    if ($originalPrice <= 0 || $discountPrice >= $originalPrice) {
        return 0;
    }
    return round((($originalPrice - $discountPrice) / $originalPrice) * 100);
}

function getProductPrice($product) {
    return !empty($product['discount_price']) && $product['discount_price'] < $product['price'] 
        ? $product['discount_price'] 
        : $product['price'];
}

function calculateShipping($subtotal) {
    return $subtotal >= FREE_SHIPPING_ABOVE ? 0 : SHIPPING_CHARGE;
}

function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireUserLogin() {
    if (!isUserLoggedIn()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isUserLoggedIn()) return null;
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, name, email, phone FROM users WHERE id = ? AND is_blocked = FALSE");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $user;
}

function getCurrentAdmin() {
    if (!isAdminLoggedIn()) return null;
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, name, email, role FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $admin;
}

function getSetting($key, $default = '') {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $value = $result->num_rows > 0 ? $result->fetch_assoc()['setting_value'] : $default;
    $stmt->close();
    $conn->close();
    return $value;
}

function updateSetting($key, $value) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}

function getCartItems() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) return [];
    $conn = getDBConnection();
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT p.*, pi.image_path FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE WHERE p.id IN ($placeholders) AND p.status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['quantity'] = $_SESSION['cart'][$row['id']];
        $items[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $items;
}

function getCartTotal() {
    $total = 0;
    foreach (getCartItems() as $item) {
        $price = getProductPrice($item);
        $total += $price * $item['quantity'];
    }
    return $total;
}

function addToCart($productId, $quantity = 1) {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
}

function removeFromCart($productId) {
    unset($_SESSION['cart'][$productId]);
}

function clearCart() {
    $_SESSION['cart'] = [];
}

function uploadFile($file, $directory, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error'];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Create directory if it doesn't exist
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
        chmod($directory, 0777);
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $directory . $filename;
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    }
    return ['success' => false, 'message' => 'Upload failed'];
}

function validateCoupon($code, $subtotal) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = TRUE");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $coupon = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if (!$coupon) return ['valid' => false, 'message' => 'Invalid coupon'];
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
        return ['valid' => false, 'message' => 'Coupon expired'];
    }
    if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
        return ['valid' => false, 'message' => 'Usage limit reached'];
    }
    if ($coupon['min_purchase'] > $subtotal) {
        return ['valid' => false, 'message' => 'Minimum purchase not met'];
    }
    
    $discount = $coupon['discount_type'] === 'flat' ? $coupon['discount_value'] : ($subtotal * $coupon['discount_value']) / 100;
    if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
        $discount = $coupon['max_discount'];
    }
    
    return ['valid' => true, 'coupon' => $coupon, 'discount' => $discount];
}

function getOrderStatusClass($status) {
    $classes = ['processing' => 'warning', 'confirmed' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger'];
    return $classes[$status] ?? 'secondary';
}

function getPaymentStatusClass($status) {
    $classes = ['pending' => 'warning', 'completed' => 'success', 'failed' => 'danger', 'refunded' => 'info'];
    return $classes[$status] ?? 'secondary';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    $units = array('second' => 60, 'minute' => 60, 'hour' => 24, 'day' => 7, 'week' => 4.35, 'month' => 12);
    foreach ($units as $unit => $value) {
        if ($time < $value) return $time . ' ' . $unit . ($time == 1 ? '' : 's') . ' ago';
        $time = round($time / $value);
    }
    return $time . ' years ago';
}

function displayTitle($title) {
    return htmlspecialchars(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
// No closing PHP tag to prevent whitespace issues
