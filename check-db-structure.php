<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Only allow this script to run in development environment
if (strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
    die('This script can only be run on localhost');
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if we should apply changes
$applyChanges = isset($_GET['apply']) && $_GET['apply'] === 'true';

$conn = getDBConnection();

// Get table structure
$result = $conn->query("SHOW CREATE TABLE orders");

if (!$result) {
    die("Error getting table structure: " . $conn->error);
}

$tableInfo = $result->fetch_assoc();
$createTableSql = $tableInfo['Create Table'];

// Get current table structure
$result = $conn->query("DESCRIBE orders");

if (!$result) {
    die("Error describing table: " . $conn->error);
}

echo "<div class='container mt-4'>";
echo "<h2>Orders Table Structure</h2>";
echo "<div class='table-responsive mb-4'>";
echo "<table class='table table-bordered table-striped'>";
echo "<thead class='table-dark'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead><tbody>";

$existingColumns = [];
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . (is_null($row['Default']) ? 'NULL' : htmlspecialchars($row['Default'])) . "</td>";
    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
    echo "</tr>";
    $existingColumns[strtolower($row['Field'])] = true;
}

echo "</tbody></table></div>";

// Check if we need to add any columns
$requiredColumns = [
    'payment_gateway_order_id' => [
        'type' => 'VARCHAR(255)',
        'null' => 'YES',
        'default' => 'NULL',
        'after' => 'id',
        'sql' => 'ADD COLUMN `payment_gateway_order_id` VARCHAR(255) NULL AFTER `id`',
        'description' => 'Stores the Razorpay order ID'
    ],
    'order_status' => [
        'type' => 'VARCHAR(50)',
        'null' => 'NO',
        'default' => 'pending',
        'after' => 'total',
        'sql' => 'ADD COLUMN `order_status` VARCHAR(50) NOT NULL DEFAULT "pending" AFTER `total`',
        'description' => 'Tracks the order status (pending, processing, completed, etc.)'
    ]
];

$alterStatements = [];
$missingColumns = [];

foreach ($requiredColumns as $column => $details) {
    if (!isset($existingColumns[strtolower($column)])) {
        $missingColumns[$column] = $details;
        $alterStatements[] = $details['sql'];
    }
}

// Display missing columns
if (!empty($missingColumns)) {
    echo "<div class='alert alert-warning'>";
    echo "<h4>Missing Columns</h4>";
    echo "<p>The following columns are required for proper payment processing:</p>";
    
    echo "<table class='table table-bordered'>";
    echo "<thead><tr><th>Column</th><th>Type</th><th>Description</th></tr></thead><tbody>";
    
    foreach ($missingColumns as $column => $details) {
        echo "<tr>";
        echo "<td><code>$column</code></td>";
        echo "<td><code>{$details['type']} " . 
             ($details['null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
             (isset($details['default']) ? " DEFAULT '{$details['default']}'" : '') . "</code></td>";
        echo "<td>{$details['description']}</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    // Show SQL to fix
    $sql = "ALTER TABLE `orders` " . implode(", \n    ", $alterStatements) . ";";
    
    echo "<h5 class='mt-4'>SQL to fix:</h5>";
    echo "<pre class='bg-light p-3' style='border-radius: 5px;'><code>" . htmlspecialchars($sql) . "</code></pre>";
    
    // Add button to apply changes
    if ($applyChanges) {
        echo "<div class='alert alert-info'>";
        echo "<h5>Applying changes...</h5>";
        
        try {
            $conn->begin_transaction();
            
            foreach ($alterStatements as $sql) {
                $alterSql = "ALTER TABLE `orders` $sql;";
                echo "<p>Executing: <code>" . htmlspecialchars($alterSql) . "</code></p>";
                
                if ($conn->query($alterSql) === TRUE) {
                    echo "<div class='text-success'>✓ Successfully executed</div>";
                } else {
                    throw new Exception("Error: " . $conn->error);
                }
            }
            
            $conn->commit();
            echo "<div class='alert alert-success mt-3'>✅ Database schema updated successfully!</div>";
            echo "<a href='check-db-structure.php' class='btn btn-primary'>Refresh Page</a>";
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "<div class='alert alert-danger mt-3'>❌ Error updating database: " . $e->getMessage() . "</div>";
            echo "<p>Changes have been rolled back.</p>";
            echo "<a href='check-db-structure.php' class='btn btn-secondary'>Try Again</a>";
        }
        
        echo "</div>";
    } else {
        echo "<a href='check-db-structure.php?apply=true' class='btn btn-primary'>Apply These Changes</a>";
        echo "<p class='text-muted mt-2'>This will add the missing columns to your orders table.</p>";
    }
    
    echo "</div>";
} else {
    echo "<div class='alert alert-success'>";
    echo "✅ All required columns are present in the orders table.";
    echo "</div>";
}

// Check for any data type issues
echo "<h3 class='mt-4'>Data Type Verification</h3>";

// Check if total column is DECIMAL(10,2)
$checkDecimal = $conn->query("SELECT COLUMN_NAME, COLUMN_TYPE, NUMERIC_SCALE 
                            FROM INFORMATION_SCHEMA.COLUMNS 
                            WHERE TABLE_NAME = 'orders' 
                            AND COLUMN_NAME = 'total'");

if ($checkRow = $checkDecimal->fetch_assoc()) {
    if (strpos(strtolower($checkRow['COLUMN_TYPE']), 'decimal') === false || $checkRow['NUMERIC_SCALE'] != 2) {
        echo "<div class='alert alert-warning'>";
        echo "⚠ The 'total' column should be DECIMAL(10,2) for accurate currency handling. Current type: " . $checkRow['COLUMN_TYPE'];
        echo "<br><br><a href='check-db-structure.php?alter=total' class='btn btn-sm btn-warning'>Fix This</a>";
        echo "</div>";
        
        if (isset($_GET['alter']) && $_GET['alter'] === 'total') {
            $alterSql = "ALTER TABLE `orders` MODIFY COLUMN `total` DECIMAL(10,2) NOT NULL;";
            if ($conn->query($alterSql)) {
                echo "<div class='alert alert-success'>✅ Successfully updated 'total' column to DECIMAL(10,2)</div>";
                echo "<a href='check-db-structure.php' class='btn btn-primary'>Refresh Page</a>";
            } else {
                echo "<div class='alert alert-danger'>❌ Error updating column: " . $conn->error . "</div>";
            }
        }
    } else {
        echo "<div class='alert alert-success'>";
        echo "✅ The 'total' column is properly defined as " . $checkRow['COLUMN_TYPE'];
        echo "</div>";
    }
}

// Verify Razorpay API configuration
echo "<h3 class='mt-4'>Razorpay Configuration</h3>";

$configStatus = [];

// Check if constants are defined
$configStatus['RAZORPAY_KEY'] = [
    'status' => defined('RAZORPAY_KEY') && !empty(RAZORPAY_KEY) ? '✅' : '❌',
    'value' => defined('RAZORPAY_KEY') ? (RAZORPAY_KEY ? 'Set' : 'Empty') : 'Not defined'
];

$configStatus['RAZORPAY_SECRET'] = [
    'status' => defined('RAZORPAY_SECRET') && !empty(RAZORPAY_SECRET) ? '✅' : '❌',
    'value' => defined('RAZORPAY_SECRET') ? (RAZORPAY_SECRET ? 'Set' : 'Empty') : 'Not defined'
];

// Display configuration status
echo "<table class='table table-bordered'>";
echo "<thead><tr><th>Setting</th><th>Status</th><th>Value</th></tr></thead><tbody>";

foreach ($configStatus as $key => $status) {
    echo "<tr>";
    echo "<td><code>$key</code></td>";
    echo "<td>{$status['status']}</td>";
    echo "<td>" . ($key === 'RAZORPAY_SECRET' ? '••••••••••••••••••••' : htmlspecialchars($status['value'])) . "</td>";
    echo "</tr>";
}

echo "</tbody></table>";

// Check if we can connect to Razorpay API
if (defined('RAZORPAY_KEY') && !empty(RAZORPAY_KEY) && defined('RAZORPAY_SECRET') && !empty(RAZORPAY_SECRET)) {
    echo "<div class='mt-3'>";
    echo "<h5>Razorpay API Connection Test</h5>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY . ':' . RAZORPAY_SECRET);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo "<div class='alert alert-danger'>❌ cURL Error: " . curl_error($ch) . "</div>";
    } else {
        if ($httpCode === 200) {
            echo "<div class='alert alert-success'>✅ Successfully connected to Razorpay API</div>";
        } else {
            $error = json_decode($response, true);
            echo "<div class='alert alert-warning'>";
            echo "⚠ API Connection Issue (HTTP $httpCode)";
            if (isset($error['error']['description'])) {
                echo "<br>" . htmlspecialchars($error['error']['description']);
            }
            echo "</div>";
        }
    }
    
    curl_close($ch);
    echo "</div>";
}

echo "</div>"; // Close container

// Add some basic styling
echo "<style>
    .table th { background-color: #f8f9fa; }
    pre { background-color: #f8f9fa; padding: 10px; border-radius: 4px; }
    code { background-color: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
</style>";

$conn->close();
?>
