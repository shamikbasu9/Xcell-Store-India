<?php
$pageTitle = 'Users Management';
require_once 'includes/header.php';

$conn = getDBConnection();

// Handle block/unblock
if (isset($_GET['toggle_block'])) {
    $userId = intval($_GET['toggle_block']);
    $conn->query("UPDATE users SET is_blocked = NOT is_blocked WHERE id = $userId");
    header('Location: users.php?updated=1');
    exit;
}

// Get users
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$sql = "SELECT u.*, 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(CASE WHEN o.payment_status = 'completed' THEN o.total ELSE 0 END), 0) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id";

if ($search) {
    $sql .= " WHERE u.name LIKE '%$search%' OR u.email LIKE '%$search%'";
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

$users = [];
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$conn->close();
?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> User status updated
        <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="input-group">
            <input type="text" class="form-control" name="search" 
                   placeholder="Search by name or email..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-primary" type="submit">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No users found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo $user['total_orders']; ?></td>
                                <td><strong><?php echo formatCurrency($user['total_spent']); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['is_blocked'] ? 'danger' : 'success'; ?>">
                                        <?php echo $user['is_blocked'] ? 'Blocked' : 'Active'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="user-edit.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?toggle_block=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-outline-<?php echo $user['is_blocked'] ? 'success' : 'danger'; ?>"
                                       onclick="return confirm('Are you sure?')"
                                       title="<?php echo $user['is_blocked'] ? 'Unblock' : 'Block'; ?> User">
                                        <i class="fas fa-<?php echo $user['is_blocked'] ? 'unlock' : 'ban'; ?>"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
