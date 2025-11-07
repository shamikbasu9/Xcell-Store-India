<?php
echo "<h2>Creating Upload Directories</h2>";

$baseDir = __DIR__;
$directories = [
    'uploads',
    'uploads/products',
    'uploads/categories',
    'uploads/temp'
];

echo "<ul style='list-style: none; padding: 0;'>";

foreach ($directories as $dir) {
    $fullPath = $baseDir . '/' . $dir;
    
    echo "<li style='padding: 10px; margin: 5px 0; background: #f8f9fa; border-left: 4px solid #28a745;'>";
    echo "<strong>Directory:</strong> $dir<br>";
    echo "<strong>Full Path:</strong> $fullPath<br>";
    
    if (file_exists($fullPath)) {
        echo "<span style='color: blue;'>✓ Already exists</span><br>";
        
        // Try to set permissions
        if (@chmod($fullPath, 0777)) {
            echo "<span style='color: green;'>✓ Permissions updated to 0777</span>";
        } else {
            echo "<span style='color: orange;'>⚠ Could not update permissions (may need manual fix)</span>";
        }
    } else {
        if (@mkdir($fullPath, 0777, true)) {
            echo "<span style='color: green;'>✓ Created successfully</span><br>";
            @chmod($fullPath, 0777);
            echo "<span style='color: green;'>✓ Permissions set to 0777</span>";
        } else {
            echo "<span style='color: red;'>✗ Failed to create</span><br>";
            echo "<span style='color: red;'>Error: Permission denied</span><br>";
            echo "<strong style='color: red;'>MANUAL FIX REQUIRED:</strong><br>";
            echo "Run this command in Terminal:<br>";
            echo "<code style='background: #000; color: #0f0; padding: 5px; display: block; margin: 5px 0;'>";
            echo "sudo mkdir -p \"$fullPath\" && sudo chmod -R 777 \"$fullPath\"";
            echo "</code>";
        }
    }
    echo "</li>";
}

echo "</ul>";

// Check if all directories exist
$allExist = true;
foreach ($directories as $dir) {
    if (!file_exists($baseDir . '/' . $dir)) {
        $allExist = false;
        break;
    }
}

if ($allExist) {
    echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724; margin-top: 20px;'>";
    echo "<h3>✓ All directories are ready!</h3>";
    echo "<p>You can now upload product images.</p>";
    echo "<a href='admin/product-add.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Add Product</a>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24; margin-top: 20px;'>";
    echo "<h3>⚠ Manual Action Required</h3>";
    echo "<p>Some directories could not be created automatically due to permission restrictions.</p>";
    echo "<p><strong>Please run these commands in your Terminal:</strong></p>";
    echo "<code style='background: #000; color: #0f0; padding: 10px; display: block; margin: 10px 0;'>";
    echo "cd \"" . $baseDir . "\"<br>";
    echo "sudo mkdir -p uploads/products uploads/categories uploads/temp<br>";
    echo "sudo chmod -R 777 uploads";
    echo "</code>";
    echo "<p>Or use Finder to create the folders manually and set permissions to Read & Write for everyone.</p>";
    echo "</div>";
}
?>
