<?php
$pageTitle = 'Coupons Management';
require_once 'includes/header.php';

$conn = getDBConnection();
$error = '';
$success = '';

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(sanitize($_POST['code']));
    $discountType = sanitize($_POST['discount_type']);
    $discountValue = floatval($_POST['discount_value']);
    $minPurchase = floatval($_POST['min_purchase']);
    $maxDiscount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
    $usageLimit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
    $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if (empty($code) || empty($discountValue)) {
        $error = 'Code and discount value are required';
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE coupons SET code = ?, discount_type = ?, discount_value = ?, min_purchase = ?, max_discount = ?, usage_limit = ?, expires_at = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssdddisii", $code, $discountType, $discountValue, $minPurchase, $maxDiscount, $usageLimit, $expiresAt, $isActive, $id);
            $success = 'Coupon updated';
        } else {
            $stmt = $conn->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_purchase, max_discount, usage_limit, expires_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdddisi", $code, $discountType, $discountValue, $minPurchase, $maxDiscount, $usageLimit, $expiresAt, $isActive);
            $success = 'Coupon added';
        }
        
        if ($stmt->execute()) {
            header('Location: coupons.php?success=1');
            exit;
        } else {
            $error = 'Failed to save coupon';
        }
        $stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM coupons WHERE id = $id");
    header('Location: coupons.php?deleted=1');
    exit;
}

// Get coupons
$coupons = [];
$result = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $coupons[] = $row;
}

// Get coupon for editing
$editCoupon = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editCoupon = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> Coupon saved successfully
        <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="fw-bold mb-0"><?php echo $editCoupon ? 'Edit' : 'Add'; ?> Coupon</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($editCoupon): ?>
                        <input type="hidden" name="id" value="<?php echo $editCoupon['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Code *</label>
                        <input type="text" class="form-control text-uppercase" name="code" required
                               value="<?php echo $editCoupon ? htmlspecialchars($editCoupon['code']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="discount_type">
                            <option value="flat" <?php echo ($editCoupon && $editCoupon['discount_type'] === 'flat') ? 'selected' : ''; ?>>Flat Amount</option>
                            <option value="percentage" <?php echo ($editCoupon && $editCoupon['discount_type'] === 'percentage') ? 'selected' : ''; ?>>Percentage</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Value *</label>
                        <input type="number" class="form-control" name="discount_value" step="0.01" required
                               value="<?php echo $editCoupon ? $editCoupon['discount_value'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Min Purchase</label>
                        <input type="number" class="form-control" name="min_purchase" step="0.01"
                               value="<?php echo $editCoupon ? $editCoupon['min_purchase'] : '0'; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Max Discount</label>
                        <input type="number" class="form-control" name="max_discount" step="0.01"
                               value="<?php echo $editCoupon ? $editCoupon['max_discount'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Usage Limit</label>
                        <input type="number" class="form-control" name="usage_limit"
                               value="<?php echo $editCoupon ? $editCoupon['usage_limit'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Expires At</label>
                        <input type="datetime-local" class="form-control" name="expires_at"
                               value="<?php echo $editCoupon && $editCoupon['expires_at'] ? date('Y-m-d\TH:i', strtotime($editCoupon['expires_at'])) : ''; ?>">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" id="active"
                               <?php echo (!$editCoupon || $editCoupon['is_active']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="active">Active</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> <?php echo $editCoupon ? 'Update' : 'Add'; ?>
                        </button>
                        <?php if ($editCoupon): ?>
                            <a href="coupons.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Discount</th>
                                <th>Usage</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No coupons yet</td></tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $coupon): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                        <td>
                                            <?php echo $coupon['discount_type'] === 'flat' 
                                                ? formatCurrency($coupon['discount_value']) 
                                                : $coupon['discount_value'] . '%'; ?>
                                        </td>
                                        <td><?php echo $coupon['used_count']; ?><?php echo $coupon['usage_limit'] ? '/' . $coupon['usage_limit'] : ''; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $coupon['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $coupon['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?php echo $coupon['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $coupon['id']; ?>" class="btn btn-outline-danger"
                                                   onclick="return confirm('Delete?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
