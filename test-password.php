<?php
// Test password hash generator
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Password: $password<br>";
echo "Hash: $hash<br><br>";

// Test verification
$storedHash = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYzpLhJ5.3e';
echo "Stored hash: $storedHash<br>";
echo "Verification: " . (password_verify($password, $storedHash) ? 'SUCCESS' : 'FAILED') . "<br><br>";

// Generate new hash
echo "New hash for admin123: " . password_hash('admin123', PASSWORD_BCRYPT);
?>
