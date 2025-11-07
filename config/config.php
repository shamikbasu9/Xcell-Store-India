<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SITE_NAME', 'Xcell Store India');
define('SITE_URL', 'http://localhost/Xcell%20Store%20India');
define('ADMIN_EMAIL', 'admin@xcellstore.com');

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PRODUCT_IMAGES_DIR', UPLOAD_DIR . 'products/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);

if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!file_exists(PRODUCT_IMAGES_DIR)) mkdir(PRODUCT_IMAGES_DIR, 0755, true);

define('DEFAULT_CURRENCY', 'INR');
define('TAX_RATE', 18);
define('SHIPPING_CHARGE', 50);
define('FREE_SHIPPING_ABOVE', 500);

// Razorpay Configuration
define('RAZORPAY_TEST_MODE', true); // Set to false for live mode
define('RAZORPAY_TEST_KEY', 'rzp_test_RY172RjjkXU0xu'); // Replace with your test key
define('RAZORPAY_TEST_SECRET', '96RIPq2pU06S2pcyPVmE01w3'); // Replace with your test secret
define('RAZORPAY_LIVE_KEY', 'rzp_live_YOUR_LIVE_KEY'); // Replace with your live key
define('RAZORPAY_LIVE_SECRET', 'YOUR_LIVE_SECRET'); // Replace with your live secret

// Get active Razorpay credentials based on mode
define('RAZORPAY_KEY', RAZORPAY_TEST_MODE ? RAZORPAY_TEST_KEY : RAZORPAY_LIVE_KEY);
define('RAZORPAY_SECRET', RAZORPAY_TEST_MODE ? RAZORPAY_TEST_SECRET : RAZORPAY_LIVE_SECRET);

date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/database.php';
?>
