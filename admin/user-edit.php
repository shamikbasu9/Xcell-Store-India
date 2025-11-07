<?php
$pageTitle = 'Edit User';
require_once 'includes/header.php';

$error = '';
$success = '';
$userId = intval($_GET['id'] ?? 0);

if (!$userId) {
    header('Location: users.php');
    exit;
}

// Get user details
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: users.php');
    exit;
}

// Get user statistics
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

// Get recent orders
$recentOrders = [];
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recentOrders[] = $row;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $isBlocked = isset($_POST['is_blocked']) ? 1 : 0;
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } else {
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, is_blocked = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssisi", $name, $email, $phone, $isBlocked, $hashedPassword, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, is_blocked = ? WHERE id = ?");
            $stmt->bind_param("sssii", $name, $email, $phone, $isBlocked, $userId);
        }
        
        if ($stmt->execute()) {
            $success = 'User updated successfully';
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Failed to update user';
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<div class="mb-3">
    <a href="users.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Users
    </a>
</div>

<div class="row g-4">
    <!-- User Info -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h5 class="fw-bold mb-4">User Information</h5>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" 
                                   placeholder="Leave blank to keep current password">
                            <small class="text-muted">Only fill if you want to change password</small>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_blocked" id="blocked"
                                       <?php echo $user['is_blocked'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="blocked">
                                    Block this user (prevent login and orders)
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update User
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Addresses -->
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Saved Addresses</h5>
                
                <?php if (empty($addresses)): ?>
                    <p class="text-muted">No addresses saved</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($addresses as $address): ?>
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <?php if ($address['is_default']): ?>
                                        <span class="badge bg-success mb-2">Default</span>
                                    <?php endif; ?>
                                    <h6 class="fw-bold"><?php echo htmlspecialchars($address['full_name']); ?></h6>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($address['address_line1']); ?></p>
                                    <?php if ($address['address_line2']): ?>
                                        <p class="mb-1 small"><?php echo htmlspecialchars($address['address_line2']); ?></p>
                                    <?php endif; ?>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> - <?php echo htmlspecialchars($address['pincode']); ?></p>
                                    <p class="mb-0 small"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($address['phone']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Recent Orders</h5>
                
                <?php if (empty($recentOrders)): ?>
                    <p class="text-muted">No orders yet</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><a href="order-detail.php?id=<?php echo $order['id']; ?>"><?php echo $order['order_number']; ?></a></td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo formatCurrency($order['total']); ?></td>
                                        <td><span class="badge bg-<?php echo $order['payment_status'] == 'completed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                                        <td><span class="badge bg-info"><?php echo ucfirst($order['order_status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="orders.php?user_id=<?php echo $userId; ?>" class="btn btn-sm btn-outline-primary">View All Orders</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Stats -->
        <div class="card">
            <div class="card-body">
                <h5 class="fw-bold mb-3">User Statistics</h5>
                
                <div class="mb-3">
                    <small class="text-muted">Member Since</small>
                    <h6><?php echo date('F d, Y', strtotime($user['created_at'])); ?></h6>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Total Orders</small>
                    <h6><?php echo $stats['total_orders']; ?></h6>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Total Spent</small>
                    <h6><?php echo formatCurrency($stats['total_spent']); ?></h6>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Account Status</small>
                    <h6>
                        <span class="badge bg-<?php echo $user['is_blocked'] ? 'danger' : 'success'; ?>">
                            <?php echo $user['is_blocked'] ? 'Blocked' : 'Active'; ?>
                        </span>
                    </h6>
                </div>
                
                <div class="mb-0">
                    <small class="text-muted">Last Updated</small>
                    <h6><?php echo date('M d, Y H:i', strtotime($user['updated_at'])); ?></h6>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Quick Actions</h5>
                
                <div class="d-grid gap-2">
                    <a href="orders.php?user_id=<?php echo $userId; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-box"></i> View All Orders
                    </a>
                    <a href="?toggle_block=<?php echo $userId; ?>" 
                       class="btn btn-outline-<?php echo $user['is_blocked'] ? 'success' : 'danger'; ?> btn-sm"
                       onclick="return confirm('Are you sure?')">
                        <i class="fas fa-<?php echo $user['is_blocked'] ? 'unlock' : 'ban'; ?>"></i> 
                        <?php echo $user['is_blocked'] ? 'Unblock' : 'Block'; ?> User
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
