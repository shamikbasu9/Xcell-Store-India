<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

echo "<h2>Fix Admin Password</h2>";

// Generate correct hash for admin123
$password = 'admin123';
$newHash = password_hash($password, PASSWORD_BCRYPT);

echo "Password: <strong>$password</strong><br>";
echo "New Hash: <code>$newHash</code><br><br>";

// Update the admin password in database
try {
    $conn = getDBConnection();
    
    $email = 'admin@xcellstore.com';
    $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $newHash, $email);
    
    if ($stmt->execute()) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;'>";
        echo "<strong>✓ SUCCESS!</strong><br>";
        echo "Admin password has been updated successfully.<br><br>";
        echo "You can now login with:<br>";
        echo "Email: <strong>admin@xcellstore.com</strong><br>";
        echo "Password: <strong>admin123</strong><br><br>";
        echo "<a href='admin/login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Login</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;'>";
        echo "✗ Failed to update password: " . $stmt->error;
        echo "</div>";
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;'>";
    echo "✗ Error: " . $e->getMessage();
    echo "</div>";
}
?>
