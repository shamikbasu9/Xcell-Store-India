<?php
require_once 'config/config.php';
initializeDatabase();
$conn = getDBConnection();

$schema = file_get_contents(__DIR__ . '/database/schema.sql');
$statements = array_filter(array_map('trim', explode(';', $schema)));

$errors = [];
$success = 0;

foreach ($statements as $statement) {
    if (!empty($statement)) {
        if ($conn->query($statement) === FALSE) {
            $errors[] = $conn->error;
        } else {
            $success++;
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Xcell Store India</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2e7d32 0%, #66bb6a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-leaf fa-5x text-success mb-3"></i>
                            <h1 class="fw-bold">Installation Complete!</h1>
                        </div>
                        
                        <?php if (empty($errors)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Database created successfully! (<?php echo $success; ?> statements)
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Some errors occurred (check if already installed)
                            </div>
                        <?php endif; ?>
                        
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3"><i class="fas fa-user-shield"></i> Admin Credentials</h5>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Email:</strong><br>
                                        <code>admin@xcellstore.com</code>
                                    </div>
                                    <div class="col-6">
                                        <strong>Password:</strong><br>
                                        <code>admin123</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="index.php" class="btn btn-success btn-lg">
                                <i class="fas fa-home"></i> Go to Home
                            </a>
                            <a href="admin/login.php" class="btn btn-outline-success btn-lg">
                                <i class="fas fa-shield-alt"></i> Admin Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
