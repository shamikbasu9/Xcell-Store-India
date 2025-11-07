<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

echo "<h2>Admin Login Debug</h2>";

$email = 'admin@xcellstore.com';
$password = 'admin123';

echo "<strong>Testing with:</strong><br>";
echo "Email: $email<br>";
echo "Password: $password<br><br>";

// Test database connection
try {
    $conn = getDBConnection();
    echo "✓ Database connection: <span style='color:green'>SUCCESS</span><br><br>";
    
    // Check if admins table exists
    $result = $conn->query("SHOW TABLES LIKE 'admins'");
    if ($result->num_rows > 0) {
        echo "✓ Admins table: <span style='color:green'>EXISTS</span><br><br>";
        
        // Count admins
        $count = $conn->query("SELECT COUNT(*) as count FROM admins")->fetch_assoc()['count'];
        echo "Total admins in database: <strong>$count</strong><br><br>";
        
        // Try to fetch the admin
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        
        if ($admin) {
            echo "✓ Admin found: <span style='color:green'>YES</span><br>";
            echo "Admin ID: {$admin['id']}<br>";
            echo "Admin Name: {$admin['name']}<br>";
            echo "Admin Email: {$admin['email']}<br>";
            echo "Admin Role: {$admin['role']}<br>";
            echo "Stored Hash: {$admin['password']}<br><br>";
            
            // Test password verification
            if (password_verify($password, $admin['password'])) {
                echo "✓ Password verification: <span style='color:green'>SUCCESS</span><br>";
                echo "<h3 style='color:green'>Login should work!</h3>";
            } else {
                echo "✗ Password verification: <span style='color:red'>FAILED</span><br>";
                echo "<h3 style='color:red'>Password hash doesn't match!</h3>";
                echo "<p>Generate a new hash and update the database.</p>";
            }
        } else {
            echo "✗ Admin found: <span style='color:red'>NO</span><br>";
            echo "<h3 style='color:red'>Admin record doesn't exist in database!</h3>";
            echo "<p><strong>Solution:</strong> Run the installer at <a href='../install.php'>install.php</a> or manually insert the admin record.</p>";
        }
        
        $stmt->close();
    } else {
        echo "✗ Admins table: <span style='color:red'>DOES NOT EXIST</span><br>";
        echo "<h3 style='color:red'>Database not set up!</h3>";
        echo "<p><strong>Solution:</strong> Run the installer at <a href='../install.php'>install.php</a></p>";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "✗ Database connection: <span style='color:red'>FAILED</span><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<p>Check your database configuration in config/database.php</p>";
}
?>
