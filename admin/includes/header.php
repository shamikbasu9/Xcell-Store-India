<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdminLogin();

$admin = getCurrentAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Panel'; ?> - Xcell Store India</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .top-bar {
            background: white;
            padding: 1rem 2rem;
            margin: -2rem -2rem 2rem -2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4 text-center border-bottom border-white border-opacity-25">
            <i class="fas fa-leaf fa-2x mb-2"></i>
            <h5 class="fw-bold mb-0">Xcell Store</h5>
            <small>Admin Panel</small>
        </div>
        
        <nav class="nav flex-column py-3">
            <a href="index.php" class="nav-link <?php echo $currentPage == 'index' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a href="products.php" class="nav-link <?php echo $currentPage == 'products' ? 'active' : ''; ?>">
                <i class="fas fa-seedling me-2"></i> Products
            </a>
            <a href="categories.php" class="nav-link <?php echo $currentPage == 'categories' ? 'active' : ''; ?>">
                <i class="fas fa-tags me-2"></i> Categories
            </a>
            <a href="orders.php" class="nav-link <?php echo $currentPage == 'orders' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart me-2"></i> Orders
            </a>
            <a href="users.php" class="nav-link <?php echo $currentPage == 'users' ? 'active' : ''; ?>">
                <i class="fas fa-users me-2"></i> Users
            </a>
            <a href="coupons.php" class="nav-link <?php echo $currentPage == 'coupons' ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt me-2"></i> Coupons
            </a>
            <a href="support.php" class="nav-link <?php echo $currentPage == 'support' ? 'active' : ''; ?>">
                <i class="fas fa-headset me-2"></i> Support
            </a>
            <a href="settings.php" class="nav-link <?php echo $currentPage == 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog me-2"></i> Settings
            </a>
            <hr class="border-white border-opacity-25">
            <a href="<?php echo SITE_URL; ?>" class="nav-link" target="_blank">
                <i class="fas fa-external-link-alt me-2"></i> View Website
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold"><?php echo $pageTitle ?? 'Dashboard'; ?></h4>
                <div>
                    <span class="me-3">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($admin['name']); ?>
                    </span>
                    <span class="badge bg-primary"><?php echo ucfirst($admin['role']); ?></span>
                </div>
            </div>
        </div>
