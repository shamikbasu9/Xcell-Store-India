<?php
// Start output buffering at the very beginning
if (!headers_sent()) {
    ob_start();
}

// Include configuration and functions
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// Process any session or user-related logic
$currentUser = getCurrentUser();
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// If we need to redirect, do it before any output
if (strpos($_SERVER['PHP_SELF'], 'admin/') !== false && !isAdminLoggedIn()) {
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Xcell Store India'; ?> - Plant Store</title>
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo isset($product['title']) ? htmlspecialchars($product['title']) : 'Xcell Store India - Premium Plants'; ?>">
    <meta property="og:description" content="<?php 
        if (isset($product['description'])) {
            // Clean up the description for meta tags
            $desc = strip_tags($product['description']);
            $desc = str_replace(["\r", "\n"], ' ', $desc);
            echo htmlspecialchars(substr($desc, 0, 200) . (strlen($desc) > 200 ? '...' : ''));
        } else {
            echo 'Discover beautiful plants at Xcell Store India. High-quality plants delivered to your doorstep.';
        }
    ?>">
    <meta property="og:site_name" content="Xcell Store India">
    
    <?php if (isset($product) && !empty($images[0]['image_path'])): ?>
        <meta property="og:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/uploads/products/' . $images[0]['image_path']; ?>">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
    <?php else: ?>
        <meta property="og:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/assets/images/logo.jpg">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:title" content="<?php echo isset($product['title']) ? htmlspecialchars($product['title']) : 'Xcell Store India - Premium Plants'; ?>">
    <meta name="twitter:description" content="<?php 
        if (isset($product['description'])) {
            $desc = strip_tags($product['description']);
            $desc = str_replace(["\r", "\n"], ' ', $desc);
            echo htmlspecialchars(substr($desc, 0, 200) . (strlen($desc) > 200 ? '...' : ''));
        } else {
            echo 'Discover beautiful plants at Xcell Store India. High-quality plants delivered to your doorstep.';
        }
    ?>">
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-green: #2e7d32;
            --light-green: #66bb6a;
            --dark-green: #1b5e20;
            --accent-green: #4caf50;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: #f8f9fa;
        }
        
        /* Desktop Navbar */
        .desktop-nav {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .desktop-nav .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white !important;
        }
        
        .desktop-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: all 0.3s;
        }
        
        .desktop-nav .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .desktop-nav .nav-link.active {
            color: white !important;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
        }
        
        /* Mobile Bottom Navigation */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }
        
        .mobile-bottom-nav .nav-item {
            flex: 1;
            text-align: center;
        }
        
        .mobile-bottom-nav .nav-link {
            color: #666;
            padding: 0.75rem 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.75rem;
            transition: all 0.3s;
        }
        
        .mobile-bottom-nav .nav-link i {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        
        .mobile-bottom-nav .nav-link.active {
            color: var(--primary-green);
        }
        
        .mobile-bottom-nav .badge {
            position: absolute;
            top: 0.5rem;
            right: 50%;
            transform: translateX(10px);
        }
        
        /* Mobile AppBar */
        .mobile-appbar {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            color: white;
            padding: 1rem;
            display: none;
        }
        
        .mobile-appbar h1 {
            font-size: 1.25rem;
            margin: 0;
            font-weight: 700;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .desktop-nav {
                display: none !important;
            }
            .mobile-appbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .mobile-bottom-nav {
                display: flex;
            }
            body {
                padding-bottom: 70px;
            }
        }
        
        @media (min-width: 769px) {
            .desktop-nav {
                display: flex !important;
            }
        }
        
        /* Cards */
        .product-card {
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .product-card img {
            height: 250px;
            object-fit: cover;
            width: 100%
        }
        
        /* Futuristic Carousel Arrows */
        .carousel-control-prev,
        .carousel-control-next {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(10px);
            width: 50px;
            height: 50px;
            border-radius: 12px;
            top: 50%;
            transform: translateY(-50%) scale(1);
            opacity: 0.9;
            margin: 0 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease-in-out;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1), 
                        0 0 0 1px rgba(255, 255, 255, 0.1) inset,
                        0 0 15px rgba(46, 125, 50, 0.3);
        }
        
        .carousel-control-prev {
            left: 0;
        }
        
        .carousel-control-next {
            right: 0;
        }
        
        .carousel-control-prev:hover,
        /* Social sharing buttons */
        .btn-share {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 50% !important;
            transition: all 0.2s;
            border: 1px solid transparent !important;
        }
        .btn-share:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn-share i {
            font-size: 1.1rem;
        }
        .btn-share.whatsapp {
            background-color: #25D366 !important;
            color: white !important;
            border-color: #25D366 !important;
        }
        .btn-share.facebook {
            background-color: #3b5998 !important;
            color: white !important;
            border-color: #3b5998 !important;
        }
        .btn-share.instagram {
            background: linear-gradient(45deg, #405DE6, #5851DB, #833AB4, #C13584, #E1306C, #FD1D1D) !important;
            color: white !important;
            border: none !important;
        }
        .carousel-control-next:hover {
            transform: translateY(-50%) scale(1.1);
            background: rgba(255, 255, 255, 0.15) !important;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15), 
                        0 0 0 1px rgba(255, 255, 255, 0.2) inset,
                        0 0 20px rgba(46, 125, 50, 0.5);
        }
        
        .carousel-control-prev:active,
        .carousel-control-next:active {
            transform: translateY(-50%) scale(0.95);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1), 
                        0 0 0 1px rgba(255, 255, 255, 0.1) inset;
        }
        
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-image: none;
            position: relative;
            width: 1.5rem;
            height: 1.5rem;
            filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.2));
        }

        .carousel-control-prev-icon:after,
        .carousel-control-next-icon:after {
            display: none;
        }
        
        .carousel-control-prev-icon:before,
        .carousel-control-next-icon:before {
            content: '';
            position: absolute;
            width: 10px;
            height: 10px;
            border-top: 3px solid #2e7d32;
            border-right: 3px solid #2e7d32;
            top: 50%;
            left: 50%;
            transform: translate(-30%, -50%) rotate(-135deg);
            transition: all 0.3s ease;
        }
        
        .carousel-control-next-icon:before {
            transform: translate(-70%, -50%) rotate(45deg);
        }
        
        /* Dark Mode Adjustments */
        [data-mdb-theme="dark"] .carousel-control-prev,
        [data-mdb-theme="dark"] .carousel-control-next {
            background: rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2), 
                        0 0 0 1px rgba(255, 255, 255, 0.05) inset,
                        0 0 15px rgba(46, 125, 50, 0.4);
        }
        
        [data-mdb-theme="dark"] .carousel-control-prev:hover,
        [data-mdb-theme="dark"] .carousel-control-next:hover {
            background: rgba(0, 0, 0, 0.4) !important;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3), 
                        0 0 0 1px rgba(255, 255, 255, 0.1) inset,
                        0 0 20px rgba(46, 125, 50, 0.6);
        }
        
        [data-mdb-theme="dark"] .carousel-control-prev-icon:before,
        [data-mdb-theme="dark"] .carousel-control-next-icon:before {
            border-color: #66bb6a;
            filter: drop-shadow(0 0 3px rgba(102, 187, 106, 0.5));
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            color: white;
            padding: 4rem 0;
            border-radius: 0 0 30px 30px;
        }
        
        /* Dark Mode */
        [data-mdb-theme="dark"] {
            --primary-green: #4caf50;
            --light-green: #81c784;
        }
        
        [data-mdb-theme="dark"] body {
            background: #1a1a1a;
        }
        
        [data-mdb-theme="dark"] .product-card {
            background: #2d2d2d;
        }
        
        [data-mdb-theme="dark"] .mobile-bottom-nav {
            background: #2d2d2d;
        }
        
        /* Fix muted text in dark mode */
        [data-mdb-theme="dark"] .text-muted {
            color: #adb5bd !important;
        }
        
        /* Fix background containers in dark mode */
        [data-mdb-theme="dark"] .container {
            background-color: transparent !important;
        }
        
        [data-mdb-theme="dark"] .card {
            background-color: #2d2d2d !important;
        }
        
        /* Dropdown Styles */
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: none;
            margin-top: 0.5rem;
        }
        
        .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            padding-left: 1.75rem;
        }
        
        .dropdown-item i {
            width: 20px;
        }
    </style>
</head>
<body>

<!-- Desktop Navigation -->
<nav class="navbar navbar-expand-lg desktop-nav">
    <div class="container">
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
            <i class="fas fa-leaf"></i> Xcell Store India
        </a>
        
        <button class="navbar-toggler text-white" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarNav">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'index' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'products' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/products.php">
                        <i class="fas fa-seedling"></i> Plants
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'faq' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/faq.php">
                        <i class="fas fa-question-circle"></i> FAQ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'contact' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/contact.php">
                        <i class="fas fa-envelope"></i> Contact
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/cart.php">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <?php if ($cartCount > 0): ?>
                            <span class="badge rounded-pill badge-notification bg-danger"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if ($currentUser): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/orders.php"><i class="fas fa-box me-2"></i> Orders</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/cart.php"><i class="fas fa-shopping-cart me-2"></i> Cart</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light btn-sm ms-2" href="<?php echo SITE_URL; ?>/auth/signup.php">
                            Sign Up
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item ms-2">
                    <button class="btn btn-sm btn-outline-light" onclick="toggleTheme()">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Mobile AppBar -->
<div class="mobile-appbar">
    <h1><i class="fas fa-leaf"></i> Xcell Store</h1>
    <button class="btn btn-sm btn-light" onclick="toggleTheme()">
        <i class="fas fa-moon" id="themeIconMobile"></i>
    </button>
</div>

<!-- Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav">
    <ul class="nav nav-justified w-100">
        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage == 'index' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $currentPage == 'products' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/products.php">
                <i class="fas fa-seedling"></i>
                <span>Plants</span>
            </a>
        </li>
        <li class="nav-item position-relative">
            <a class="nav-link <?php echo $currentPage == 'cart' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/cart.php">
                <i class="fas fa-shopping-cart"></i>
                <span>Cart</span>
                <?php if ($cartCount > 0): ?>
                    <span class="badge rounded-pill badge-notification bg-danger"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo in_array($currentPage, ['profile', 'orders']) ? 'active' : ''; ?>" 
               href="<?php echo $currentUser ? SITE_URL . '/profile.php' : SITE_URL . '/auth/login.php'; ?>">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </li>
    </ul>
</nav>

<script>
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-mdb-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-mdb-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const icons = document.querySelectorAll('#themeIcon, #themeIconMobile');
    icons.forEach(icon => {
        icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
}

// Load saved theme
const savedTheme = localStorage.getItem('theme') || 'light';
document.documentElement.setAttribute('data-mdb-theme', savedTheme);
updateThemeIcon(savedTheme);

// Initialize dropdowns when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Simple dropdown toggle without MDBootstrap dependency
    const dropdownToggle = document.getElementById('userDropdown');
    if (dropdownToggle) {
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdownMenu = this.nextElementSibling;
            const isShown = dropdownMenu.classList.contains('show');
            
            // Close all dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            
            // Toggle current dropdown
            if (!isShown) {
                dropdownMenu.classList.add('show');
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
    }
});
</script>
