<?php
require_once 'config/config.php';

echo "<h2>Updating Category Icons</h2>";

try {
    $conn = getDBConnection();
    
    // Update flowering plants icon
    $sql = "UPDATE categories SET icon = 'fa-spa' WHERE slug = 'flowering-plants'";
    
    if ($conn->query($sql)) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;'>";
        echo "<strong>✓ SUCCESS!</strong><br>";
        echo "Flowering Plants category icon updated to 'fa-spa' (lotus/flower icon).<br><br>";
        
        // Show all categories with their icons
        echo "<h4>Current Categories:</h4>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Icon</th><th>Name</th><th>Description</th></tr>";
        
        $result = $conn->query("SELECT * FROM categories ORDER BY name");
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='text-align: center; font-size: 24px;'><i class='fas {$row['icon']}'></i></td>";
            echo "<td><strong>{$row['name']}</strong></td>";
            echo "<td>{$row['description']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        echo "<a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Homepage</a>";
        echo "</div>";
    } else {
        throw new Exception($conn->error);
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;'>";
    echo "✗ Error: " . $e->getMessage();
    echo "</div>";
}
?>

<!-- Font Awesome for icon display -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
