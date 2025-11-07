<?php
require_once 'config/config.php';

echo "<h2>Adding Discount Price Feature</h2>";

try {
    $conn = getDBConnection();
    
    // Add discount_price column to products table
    $sql = "ALTER TABLE products ADD COLUMN discount_price DECIMAL(10, 2) NULL AFTER price";
    
    if ($conn->query($sql)) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;'>";
        echo "<strong>✓ SUCCESS!</strong><br>";
        echo "Discount price column added to products table.<br><br>";
        echo "You can now add discounted prices to your products!<br><br>";
        echo "<a href='admin/products.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Products</a>";
        echo "</div>";
    } else {
        // Check if column already exists
        if (strpos($conn->error, 'Duplicate column') !== false) {
            echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; color: #0c5460;'>";
            echo "<strong>ℹ INFO:</strong><br>";
            echo "Discount price column already exists. No changes needed.<br><br>";
            echo "<a href='admin/products.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Products</a>";
            echo "</div>";
        } else {
            throw new Exception($conn->error);
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;'>";
    echo "✗ Error: " . $e->getMessage();
    echo "</div>";
}
?>
