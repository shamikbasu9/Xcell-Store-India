<?php
$pageTitle = 'Print Bill';
// Don't include header for print view
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$orderId = intval($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: orders.php');
    exit;
}

$conn = getDBConnection();

// Get order details
$stmt = $conn->prepare("SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
                        a.full_name, a.phone as shipping_phone, a.address_line1, a.address_line2, 
                        a.city, a.state, a.pincode
                        FROM orders o 
                        LEFT JOIN users u ON o.user_id = u.id 
                        LEFT JOIN addresses a ON o.shipping_address_id = a.id 
                        WHERE o.id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Order Not Found</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error { color: #dc3545; }
            .back-btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 20px; }
        </style>
    </head>
    <body>
        <h1 class="error">Order Not Found</h1>
        <p>The order with ID <?php echo $orderId; ?> could not be found.</p>
        <a href="orders.php" class="back-btn">Back to Orders</a>
    </body>
    </html>
    <?php
    exit;
}

// Get order items
$stmt = $conn->prepare("SELECT oi.*, p.title as product_title, 
                        (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_path
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Get store info (you can customize these)
$storeName = "Xcell Store India";
$storeAddress = "Your Store Address\nCity, State - PINCODE\nPhone: +91 XXXXX XXXXX\nEmail: info@xcellstore.com";
$storeGST = "GSTIN: XXXXXXXXXX";

// Calculate shipping if not already calculated
$shippingAmount = $order['shipping_charge'] ?? ($order['subtotal'] >= FREE_SHIPPING_ABOVE ? 0 : SHIPPING_CHARGE);
$discountAmount = $order['discount'] ?? 0;
$grandTotal = $order['total'] ?? ($order['subtotal'] + $shippingAmount - $discountAmount);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bill - Order #<?php echo htmlspecialchars($order['order_number']); ?></title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 15px; }
            .bill-container { width: 100%; max-width: none; }
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .bill-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .bill-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .bill-header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 28px;
        }
        
        .store-info {
            color: #666;
            font-size: 14px;
            white-space: pre-line;
            margin-top: 10px;
        }
        
        .bill-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .bill-info-left, .bill-info-right {
            flex: 1;
        }
        
        .bill-info h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 16px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .bill-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table th {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .items-table td {
            border: 1px solid #ddd;
            padding: 10px 12px;
            vertical-align: top;
        }
        
        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .item-name {
            font-weight: 500;
            color: #333;
        }
        
        .item-details {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .totals {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        
        .totals-row.grand-total {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 15px;
        }
        
        .bill-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        .print-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px auto;
            display: block;
        }
        
        .print-btn:hover {
            background: #218838;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-paid { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #e2e3e5; color: #383d41; }
        .status-delivered { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="bill-container">
        <!-- Bill Header -->
        <div class="bill-header">
            <h1><?php echo htmlspecialchars($storeName); ?></h1>
            <div class="store-info"><?php echo htmlspecialchars($storeAddress); ?></div>
            <div style="margin-top: 10px; font-weight: bold;"><?php echo htmlspecialchars($storeGST); ?></div>
        </div>
        
        <!-- Bill Information -->
        <div class="bill-info">
            <div class="bill-info-left">
                <h3>BILL TO</h3>
                <p><strong><?php echo htmlspecialchars($order['full_name'] ?: $order['user_name']); ?></strong></p>
                <?php if ($order['address_line1']): ?>
                    <p>
                        <?php echo htmlspecialchars($order['address_line1']); ?><br>
                        <?php if ($order['address_line2']) echo htmlspecialchars($order['address_line2']) . '<br>'; ?>
                        <?php echo htmlspecialchars($order['city'] . ', ' . $order['state'] . ' - ' . $order['pincode']); ?>
                    </p>
                <?php endif; ?>
                <p>Phone: <?php echo htmlspecialchars($order['shipping_phone'] ?: $order['user_phone']); ?></p>
                <p>Email: <?php echo htmlspecialchars($order['user_email']); ?></p>
            </div>
            
            <div class="bill-info-right text-right">
                <h3>INVOICE DETAILS</h3>
                <p><strong>Invoice #:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                <p><strong>Date:</strong> <?php echo date('d M, Y', strtotime($order['created_at'])); ?></p>
                <p><strong>Payment:</strong> 
                    <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </p>
                <p><strong>Order Status:</strong> 
                    <span class="status-badge status-<?php echo $order['order_status']; ?>">
                        <?php echo ucfirst($order['order_status']); ?>
                    </span>
                </p>
            </div>
        </div>
        
        <!-- Order Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="40%">Product</th>
                    <th width="15%">Price</th>
                    <th width="10%">Qty</th>
                    <th width="15%">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $sr = 1; foreach ($orderItems as $item): ?>
                    <tr>
                        <td><?php echo $sr++; ?></td>
                        <td>
                            <div class="item-name"><?php echo htmlspecialchars($item['product_title']); ?></div>
                            <?php if ($item['variation']): ?>
                                <div class="item-details">Variation: <?php echo htmlspecialchars($item['variation']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?php echo formatCurrency($item['price']); ?></td>
                        <td class="text-right"><?php echo $item['quantity']; ?></td>
                        <td class="text-right"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="totals">
            <div class="totals-row">
                <span>Subtotal:</span>
                <span><?php echo formatCurrency($order['subtotal']); ?></span>
            </div>
            <?php if ($discountAmount > 0): ?>
                <div class="totals-row">
                    <span>Discount:</span>
                    <span>-<?php echo formatCurrency($discountAmount); ?></span>
                </div>
            <?php endif; ?>
            <div class="totals-row">
                <span>Shipping:</span>
                <span><?php echo formatCurrency($shippingAmount); ?></span>
                <?php if ($shippingAmount == 0 && $order['subtotal'] >= FREE_SHIPPING_ABOVE): ?>
                    <small style="color: green; margin-left: 10px;">(Free)</small>
                <?php endif; ?>
            </div>
            <div class="totals-row grand-total">
                <span>Grand Total:</span>
                <span><?php echo formatCurrency($grandTotal); ?></span>
            </div>
        </div>
        
        <!-- Bill Footer -->
        <div class="bill-footer">
            <p><strong>Thank you for your business!</strong></p>
            <p>This is a computer generated invoice and does not require signature.</p>
            <p>For any queries, please contact us at: <?php echo htmlspecialchars($storeName); ?></p>
        </div>
    </div>
    
    <!-- Print Button (hidden when printing) -->
    <button class="print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print Bill
    </button>
</body>
</html>
