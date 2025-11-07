<?php
$pageTitle = 'Settings';
require_once 'includes/header.php';

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            updateSetting($key, sanitize($value));
        }
    }
    $success = 'Settings saved successfully!';
}

$settings = [
    'site_name' => getSetting('site_name', 'Xcell Store India'),
    'currency' => getSetting('currency', 'INR'),
    'tax_rate' => getSetting('tax_rate', '18'),
    'shipping_charge' => getSetting('shipping_charge', '50'),
    'free_shipping_above' => getSetting('free_shipping_above', '500'),
    'payment_gateway' => getSetting('payment_gateway', 'razorpay'),
    'razorpay_key' => getSetting('razorpay_key', ''),
    'razorpay_secret' => getSetting('razorpay_secret', '')
];
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <h5 class="fw-bold mb-3">General Settings</h5>
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Site Name</label>
                    <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Currency</label>
                    <select class="form-select" name="currency">
                        <option value="INR" <?php echo $settings['currency'] === 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                        <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                        <option value="EUR" <?php echo $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                    </select>
                </div>
            </div>
            
            <h5 class="fw-bold mb-3">Pricing Settings</h5>
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" class="form-control" name="tax_rate" step="0.01" value="<?php echo $settings['tax_rate']; ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Shipping Charge</label>
                    <input type="number" class="form-control" name="shipping_charge" step="0.01" value="<?php echo $settings['shipping_charge']; ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Free Shipping Above</label>
                    <input type="number" class="form-control" name="free_shipping_above" step="0.01" value="<?php echo $settings['free_shipping_above']; ?>">
                </div>
            </div>
            
            <h5 class="fw-bold mb-3">Payment Gateway</h5>
            
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <label class="form-label">Active Gateway</label>
                    <select class="form-select" name="payment_gateway">
                        <option value="razorpay" <?php echo $settings['payment_gateway'] === 'razorpay' ? 'selected' : ''; ?>>Razorpay</option>
                        <option value="stripe" <?php echo $settings['payment_gateway'] === 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                        <option value="paypal" <?php echo $settings['payment_gateway'] === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Razorpay Key</label>
                    <input type="text" class="form-control" name="razorpay_key" value="<?php echo htmlspecialchars($settings['razorpay_key']); ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Razorpay Secret</label>
                    <input type="password" class="form-control" name="razorpay_secret" value="<?php echo htmlspecialchars($settings['razorpay_secret']); ?>">
                </div>
            </div>
            
            <button type="submit" name="submit" class="btn btn-success btn-lg">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
