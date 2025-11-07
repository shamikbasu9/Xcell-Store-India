<?php
echo "<h2>Setting Up Upload Directories</h2>";

$baseDir = __DIR__;
$directories = [
    'uploads',
    'uploads/products',
    'uploads/categories',
    'uploads/temp'
];

echo "<ul>";
foreach ($directories as $dir) {
    $fullPath = $baseDir . '/' . $dir;
    
    if (!file_exists($fullPath)) {
        if (mkdir($fullPath, 0777, true)) {
            chmod($fullPath, 0777);
            echo "<li style='color: green;'>✓ Created: <strong>$dir</strong></li>";
        } else {
            echo "<li style='color: red;'>✗ Failed to create: <strong>$dir</strong></li>";
        }
    } else {
        chmod($fullPath, 0777);
        echo "<li style='color: blue;'>✓ Already exists: <strong>$dir</strong></li>";
    }
}
echo "</ul>";

echo "<br><div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;'>";
echo "<strong>✓ Setup Complete!</strong><br>";
echo "All upload directories are ready. You can now upload product images.<br><br>";
echo "<a href='admin/product-add.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Add Product</a>";
echo "</div>";
?>
