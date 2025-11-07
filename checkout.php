<?php
$pageTitle = 'Checkout';
require_once 'includes/header.php';
requireUserLogin();

$cartItems = getCartItems();
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

$subtotal = getCartTotal();
$shipping = calculateShipping($subtotal);
$tax = calculateTax($subtotal);
$discount = 0;
$couponCode = '';

// Handle coupon
if (isset($_POST['apply_coupon'])) {
    $couponCode = sanitize($_POST['coupon_code']);
    $validation = validateCoupon($couponCode, $subtotal);
    
    if ($validation['valid']) {
        $discount = $validation['discount'];
        $_SESSION['applied_coupon'] = $validation['coupon'];
    } else {
        $couponError = $validation['message'];
    }
}

if (isset($_SESSION['applied_coupon'])) {
    $discount = $_SESSION['applied_coupon']['discount_type'] === 'flat' 
        ? $_SESSION['applied_coupon']['discount_value'] 
        : ($subtotal * $_SESSION['applied_coupon']['discount_value']) / 100;
    
    if ($_SESSION['applied_coupon']['max_discount'] && $discount > $_SESSION['applied_coupon']['max_discount']) {
        $discount = $_SESSION['applied_coupon']['max_discount'];
    }
}

$total = $subtotal + $shipping + $tax - $discount;

// Get current user
$currentUser = getCurrentUser();

// Get user addresses
$conn = getDBConnection();
$userId = $_SESSION['user_id'];
$addresses = [];
$result = $conn->query("SELECT * FROM addresses WHERE user_id = $userId ORDER BY is_default DESC");
while ($row = $result->fetch_assoc()) {
    $addresses[] = $row;
}
$conn->close();
?>

<div class="container my-4">
    <h2 class="fw-bold mb-4"><i class="fas fa-lock"></i> Secure Checkout</h2>
    
    <div class="row g-4">
        <!-- Checkout Form -->
        <div class="col-lg-8">
            <!-- Shipping Address -->
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Shipping Address</h5>
                    
                    <?php if (empty($addresses)): ?>
                        <form id="addressForm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="full_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone *</label>
                                    <input type="tel" class="form-control" name="phone" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address Line 1 *</label>
                                    <input type="text" class="form-control" name="address_line1" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address Line 2</label>
                                    <input type="text" class="form-control" name="address_line2">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">City *</label>
                                    <input type="text" class="form-control" name="city" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">State *</label>
                                    <input type="text" class="form-control" name="state" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Pincode *</label>
                                    <input type="text" class="form-control" name="pincode" required>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <select class="form-select" id="addressSelect">
                            <?php foreach ($addresses as $address): ?>
                                <option value="<?php echo $address['id']; ?>" <?php echo $address['is_default'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($address['full_name']); ?> - 
                                    <?php echo htmlspecialchars($address['address_line1']); ?>, 
                                    <?php echo htmlspecialchars($address['city']); ?>, 
                                    <?php echo htmlspecialchars($address['state']); ?> - 
                                    <?php echo htmlspecialchars($address['pincode']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payment Method -->
            <div class="card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Payment Method</h5>
                    
                    <?php if (RAZORPAY_TEST_MODE): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-flask"></i> <strong>Test Mode Active</strong> - Use test cards for payment
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="razorpay" value="razorpay" checked>
                        <label class="form-check-label" for="razorpay">
                            <i class="fas fa-mobile-alt text-primary"></i> UPI Payment (Google Pay, PhonePe, Paytm)
                            <?php if (RAZORPAY_TEST_MODE): ?>
                                <span class="badge bg-warning text-dark">TEST</span>
                            <?php endif; ?>
                        </label>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod">
                        <label class="form-check-label" for="cod">
                            <i class="fas fa-money-bill-wave text-success"></i> Cash on Delivery
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Order Summary</h5>
                    
                    <!-- Cart Items -->
                    <div class="mb-3" style="max-height: 200px; overflow-y: auto;">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <small><?php echo htmlspecialchars($item['title']); ?> x<?php echo $item['quantity']; ?></small>
                                <small><?php echo formatCurrency($item['price'] * $item['quantity']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr>
                    
                    <!-- Coupon -->
                    <form method="POST" class="mb-3">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" name="coupon_code" placeholder="Coupon code" 
                                   value="<?php echo isset($_SESSION['applied_coupon']) ? $_SESSION['applied_coupon']['code'] : ''; ?>">
                            <button type="submit" name="apply_coupon" class="btn btn-outline-success">Apply</button>
                        </div>
                        <?php if (isset($couponError)): ?>
                            <small class="text-danger"><?php echo $couponError; ?></small>
                        <?php endif; ?>
                    </form>
                    
                    <!-- Totals -->
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span><?php echo formatCurrency($subtotal); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping</span>
                        <span><?php echo $shipping > 0 ? formatCurrency($shipping) : 'FREE'; ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax (<?php echo TAX_RATE; ?>%)</span>
                        <span><?php echo formatCurrency($tax); ?></span>
                    </div>
                    
                    <?php if ($discount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Discount</span>
                            <span>-<?php echo formatCurrency($discount); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total</strong>
                        <strong class="text-success"><?php echo formatCurrency($total); ?></strong>
                    </div>
                    
                    <button onclick="placeOrder()" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-check"></i> Place Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Razorpay Checkout Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
async function placeOrder() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    
    if (!paymentMethod) {
        alert('Please select a payment method');
        return;
    }
    
    // Disable button to prevent double submission
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    let addressId = document.getElementById('addressSelect')?.value;
    
    // If no saved address, create one from the form
    if (!addressId) {
        const addressForm = document.getElementById('addressForm');
        if (!addressForm.checkValidity()) {
            alert('Please fill in all required address fields');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Place Order';
            return;
        }
        
        const formData = new FormData(addressForm);
        formData.append('is_default', '1');
        
        try {
            const response = await fetch('save-address.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (!result.success) {
                alert('Failed to save address');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Place Order';
                return;
            }
            
            addressId = result.address_id;
        } catch (error) {
            alert('Error saving address');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Place Order';
            return;
        }
    }
    
    // Create order
    fetch('process-order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `payment_method=${paymentMethod.value}&address_id=${addressId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (paymentMethod.value === 'cod') {
                window.location.href = 'order-success.php?order=' + data.order_id;
            } else if (paymentMethod.value === 'razorpay') {
                // Create Razorpay order first
                createRazorpayOrder(data.order_id, data.order_number, <?php echo $total; ?>);
            }
        } else {
            alert(data.message || 'Failed to place order');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Place Order';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Place Order';
    });
}

function createRazorpayOrder(orderId, orderNumber, amount) {
    fetch('create-razorpay-order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `order_id=${orderId}&amount=${amount}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            initiateRazorpayPayment(orderId, orderNumber, data.razorpay_order_id, data.amount);
        } else {
            alert(data.message || 'Failed to initialize payment');
            const btn = document.querySelector('button[onclick*="placeOrder"]');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Place Order';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to initialize payment. Please try again.');
        const btn = document.querySelector('button[onclick*="placeOrder"]');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Place Order';
        }
    });
}

function initiateRazorpayPayment(orderId, orderNumber, razorpayOrderId, amount) {
    const options = {
        key: '<?php echo RAZORPAY_KEY; ?>',
        amount: amount, // Amount in paise
        currency: 'INR',
        name: '<?php echo SITE_NAME; ?>',
        description: 'Order #' + orderNumber,
        order_id: razorpayOrderId,
        prefill: {
            name: '<?php echo htmlspecialchars($currentUser['name']); ?>',
            email: '<?php echo htmlspecialchars($currentUser['email']); ?>',
            contact: '<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>'
        },
        config: {
            display: {
                blocks: {
                    upi: {
                        name: 'Pay using UPI',
                        instruments: [
                            {
                                method: 'upi'
                            }
                        ]
                    }
                },
                sequence: ['block.upi'],
                preferences: {
                    show_default_blocks: false
                }
            }
        },
        theme: {
            color: '#2e7d32'
        },
        handler: function(response) {
            // Payment successful
            verifyPayment(orderId, response.razorpay_payment_id, response.razorpay_signature);
        },
        modal: {
            ondismiss: function() {
                // Payment cancelled
                alert('Payment cancelled. Your order has been saved. You can complete payment later from orders page.');
                window.location.href = 'orders.php';
            }
        }
    };
    
    const rzp = new Razorpay(options);
    rzp.open();
}

function verifyPayment(orderId, paymentId, signature) {
    fetch('verify-payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `order_id=${orderId}&payment_id=${paymentId}&signature=${signature}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'order-success.php?order=' + orderId;
        } else {
            alert('Payment verification failed. Please contact support.');
            window.location.href = 'orders.php';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
