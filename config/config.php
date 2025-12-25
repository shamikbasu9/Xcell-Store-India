<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SITE_NAME', 'Xcell Store India');
define('SITE_URL', 'https://xcellstoreindia.in');
define('ADMIN_EMAIL', 'admin@xcellstore.com');

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PRODUCT_IMAGES_DIR', UPLOAD_DIR . 'products/');
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB

if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!file_exists(PRODUCT_IMAGES_DIR)) mkdir(PRODUCT_IMAGES_DIR, 0755, true);

define('DEFAULT_CURRENCY', 'INR');
define('SHIPPING_CHARGE', 50);
define('FREE_SHIPPING_ABOVE', 500);

// Razorpay Configuration
define('RAZORPAY_TEST_MODE', false); // Set to false for live mode
define('RAZORPAY_TEST_KEY', 'rzp_test_RfHfYmR81bfCdh'); // Replace with your test key
define('RAZORPAY_TEST_SECRET', '53Y0UUKKSQ3KHyVguef1wHLV'); // Replace with your test secret
define('RAZORPAY_LIVE_KEY', 'rzp_live_Rg2KdPzw6UAzaT'); // Replace with your live key
define('RAZORPAY_LIVE_SECRET', 'ZRdwSI4CkgbdSqQpZIYlRUMF'); // Replace with your live secret

// Get active Razorpay credentials based on mode
define('RAZORPAY_KEY', RAZORPAY_TEST_MODE ? RAZORPAY_TEST_KEY : RAZORPAY_LIVE_KEY);
define('RAZORPAY_SECRET', RAZORPAY_TEST_MODE ? RAZORPAY_TEST_SECRET : RAZORPAY_LIVE_SECRET);

date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/database.php';
// No closing PHP tag to prevent whitespace issues
