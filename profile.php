<?php
$pageTitle = 'My Profile';
require_once 'includes/header.php';
requireUserLogin();

$user = getCurrentUser();
$success = '';
$error = '';

// Get user statistics
$conn = getDBConnection();
$userId = $_SESSION['user_id'];

$stats = [];
$stmt = $conn->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_spent FROM orders WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user addresses
$addresses = [];
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $addresses[] = $row;
}
$stmt->close();
$conn->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    $conn = getDBConnection();
    $userId = $_SESSION['user_id'];
    
    if (!empty($newPassword)) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!password_verify($currentPassword, $result['password'])) {
            $error = 'Current password is incorrect';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $email, $phone, $hashedPassword, $userId);
            $stmt->execute();
            $success = 'Profile updated successfully!';
        }
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $phone, $userId);
        $stmt->execute();
        $success = 'Profile updated successfully!';
    }
    
    $stmt->close();
    $conn->close();
    
    if ($success) {
        $_SESSION['user_name'] = $name;
        $user = getCurrentUser();
    }
}
?>

<div class="container my-4">
    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="position-relative d-inline-block">
                            <i class="fas fa-user-circle fa-5x text-success"></i>
                        </div>
                        <h5 class="fw-bold mt-2"><?php echo htmlspecialchars($user['name']); ?></h5>
                        <p class="text-muted mb-0 small"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="badge bg-success mt-2">Active Member</span>
                    </div>
                    
                    <div class="bg-light rounded p-3 mb-3">
                        <div class="row text-center">
                            <div class="col-6 border-end">
                                <h4 class="fw-bold text-success mb-0"><?php echo $stats['total_orders']; ?></h4>
                                <small class="text-muted">Orders</small>
                            </div>
                            <div class="col-6">
                                <h4 class="fw-bold text-success mb-0"><?php echo formatCurrency($stats['total_spent']); ?></h4>
                                <small class="text-muted">Total Spent</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="#profile" class="text-decoration-none text-success fw-bold" onclick="showTab('profile')">
                                <i class="fas fa-user"></i> Profile Info
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#addresses" class="text-decoration-none text-dark" onclick="showTab('addresses')">
                                <i class="fas fa-map-marker-alt"></i> Addresses
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="orders.php" class="text-decoration-none text-dark">
                                <i class="fas fa-box"></i> My Orders
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="cart.php" class="text-decoration-none text-dark">
                                <i class="fas fa-shopping-cart"></i> Shopping Cart
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="auth/logout.php" class="text-decoration-none text-danger">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Profile Content -->
        <div class="col-lg-9">
            <!-- Profile Info Tab -->
            <div id="profile-tab" class="tab-content">
                <div class="card">
                    <div class="card-body">
                        <h4 class="fw-bold mb-4"><i class="fas fa-user-edit"></i> Edit Profile</h4>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-12">
                                <hr class="my-4">
                                <h5 class="fw-bold mb-3">Change Password (Optional)</h5>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" minlength="6">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password">
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            </div>
            
            <!-- Addresses Tab -->
            <div id="addresses-tab" class="tab-content" style="display: none;">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold mb-0"><i class="fas fa-map-marker-alt"></i> My Addresses</h4>
                            <a href="checkout.php" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Add New Address
                            </a>
                        </div>
                        
                        <?php if (empty($addresses)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-map-marked-alt fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No addresses saved</h5>
                                <p class="text-muted">Add an address during checkout</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($addresses as $address): ?>
                                    <div class="col-md-6">
                                        <div class="card h-100 <?php echo $address['is_default'] ? 'border-success' : ''; ?>">
                                            <div class="card-body">
                                                <?php if ($address['is_default']): ?>
                                                    <span class="badge bg-success mb-2">Default</span>
                                                <?php endif; ?>
                                                <h6 class="fw-bold"><?php echo htmlspecialchars($address['full_name']); ?></h6>
                                                <p class="mb-1"><?php echo htmlspecialchars($address['address_line1']); ?></p>
                                                <?php if ($address['address_line2']): ?>
                                                    <p class="mb-1"><?php echo htmlspecialchars($address['address_line2']); ?></p>
                                                <?php endif; ?>
                                                <p class="mb-1"><?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> - <?php echo htmlspecialchars($address['pincode']); ?></p>
                                                <p class="mb-0"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($address['phone']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tab) {
    event.preventDefault();
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.getElementById(tab + '-tab').style.display = 'block';
    
    // Update active link
    document.querySelectorAll('.col-lg-3 a').forEach(link => {
        link.classList.remove('text-success', 'fw-bold');
        link.classList.add('text-dark');
    });
    event.target.classList.remove('text-dark');
    event.target.classList.add('text-success', 'fw-bold');
}
</script>

<?php require_once 'includes/footer.php'; ?>
