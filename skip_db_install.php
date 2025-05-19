<?php
/**
 * Skip Database Installation Helper
 * This script helps initialize the admin user without database checks
 */

// Initial admin user credentials
$username = 'admin';
$password = 'admin123';
$email = 'admin@example.com';
$role = 'super_admin';

// Generate a password hash for the admin user
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Success message
$success = true;
$pageTitle = 'AI Chatbot Installation Helper';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        
        .install-container {
            max-width: 700px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 2rem;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        
        code {
            color: #d63384;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="logo">
                <i class="fas fa-robot fa-4x text-primary"></i>
                <h1 class="mt-3">AI Chatbot System</h1>
                <p class="text-muted">Manual Installation Helper</p>
            </div>
            
            <div class="alert alert-info">
                <h4 class="alert-heading"><i class="fas fa-info-circle"></i> Installation Step 3 Helper</h4>
                <p>This page helps you with the Step 3 of the installation process by providing the necessary credentials.</p>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-shield"></i> Admin User Credentials
                </div>
                <div class="card-body">
                    <p>Use these credentials to log in to the admin panel:</p>
                    
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Username:</span>
                            <strong><?php echo htmlspecialchars($username); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Password:</span>
                            <strong><?php echo htmlspecialchars($password); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Email:</span>
                            <strong><?php echo htmlspecialchars($email); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Role:</span>
                            <strong><?php echo htmlspecialchars($role); ?></strong>
                        </li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Make sure to change these default credentials after installation.
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-terminal"></i> Generated Password Hash
                </div>
                <div class="card-body">
                    <p>This is the hashed version of the password that would be stored in the database:</p>
                    
                    <pre><code><?php echo htmlspecialchars($hashedPassword); ?></code></pre>
                    
                    <p class="text-muted small">This uses PHP's password_hash function with BCRYPT algorithm and a cost of 12.</p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-code"></i> Manual Database Setup
                </div>
                <div class="card-body">
                    <p>To manually set up the database, you can use the SQL script provided in <code>mysql_database_setup.sql</code>.</p>
                    
                    <p>The script creates all necessary tables and initializes the admin user with the credentials shown above.</p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Once you've set up the database, you should be able to proceed to Step 4 of the installation process.
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <a href="install.php?step=4" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-right"></i> Continue to Step 4
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>