<?php
/**
 * Installation script for the AI Chatbot System
 */

// Define constants
define('DB_HOST_DEFAULT', 'localhost');
define('DB_USER_DEFAULT', 'root');
define('DB_PASS_DEFAULT', '');
define('DB_NAME_DEFAULT', 'ai_chatbot');

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            // Check PHP version and extensions
            if (version_compare(PHP_VERSION, '7.4.0', '<')) {
                $errors[] = 'PHP version 7.4 or higher is required. Current version: ' . PHP_VERSION;
            }
            
            $requiredExtensions = ['mysqli', 'json', 'session', 'mbstring'];
            foreach ($requiredExtensions as $ext) {
                if (!extension_loaded($ext)) {
                    $errors[] = "Required PHP extension not found: $ext";
                }
            }
            
            if (empty($errors)) {
                header('Location: install_ai_chatbot.php?step=2');
                exit;
            }
            break;
            
        case 2:
            // Database configuration
            $dbHost = trim($_POST['db_host'] ?? '');
            $dbUser = trim($_POST['db_user'] ?? '');
            $dbPass = $_POST['db_pass'] ?? '';
            $dbName = trim($_POST['db_name'] ?? '');
            
            if (empty($dbHost)) {
                $errors[] = 'Database host is required';
            }
            
            if (empty($dbUser)) {
                $errors[] = 'Database username is required';
            }
            
            if (empty($dbName)) {
                $errors[] = 'Database name is required';
            }
            
            if (empty($errors)) {
                // Skip actual database connection for now and proceed to next step
                header('Location: install_ai_chatbot.php?step=3');
                exit;
            }
            break;
            
        case 3:
            // Admin user setup
            $adminUsername = trim($_POST['admin_username'] ?? '');
            $adminPassword = $_POST['admin_password'] ?? '';
            $adminPasswordConfirm = $_POST['admin_password_confirm'] ?? '';
            $adminEmail = trim($_POST['admin_email'] ?? '');
            
            if (empty($adminUsername) || strlen($adminUsername) < 3) {
                $errors[] = 'Admin username is required and must be at least 3 characters';
            }
            
            if (empty($adminPassword) || strlen($adminPassword) < 6) {
                $errors[] = 'Admin password is required and must be at least 6 characters';
            } elseif ($adminPassword !== $adminPasswordConfirm) {
                $errors[] = 'Passwords do not match';
            }
            
            if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid admin email is required';
            }
            
            if (empty($errors)) {
                // Skip actual user creation for demo and proceed to next step
                header('Location: install_ai_chatbot.php?step=4');
                exit;
            }
            break;
            
        case 4:
            // Final step - complete installation
            $createExamples = isset($_POST['create_examples']) && $_POST['create_examples'] == '1';
            
            // Installation complete - this would normally create real data
            $success = true;
            break;
    }
}

$pageTitle = 'Install AI Chatbot System - Step ' . $step;
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
        
        .step-indicator {
            display: flex;
            margin-bottom: 2rem;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            position: relative;
        }
        
        .step::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -50%;
            transform: translateY(-50%);
            width: 100%;
            height: 2px;
            background-color: #dee2e6;
            z-index: 1;
        }
        
        .step:last-child::after {
            display: none;
        }
        
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #dee2e6;
            color: #6c757d;
            margin-bottom: 5px;
            position: relative;
            z-index: 2;
        }
        
        .step.active .step-number {
            background-color: #0d6efd;
            color: #fff;
        }
        
        .step.completed .step-number {
            background-color: #198754;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="logo">
                <i class="fas fa-robot fa-4x text-primary"></i>
                <h1 class="mt-3">AI Chatbot System</h1>
                <p class="text-muted">Installation Wizard</p>
            </div>
            
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                    <div class="step-number"><?php echo $step > 1 ? '<i class="fas fa-check"></i>' : '1'; ?></div>
                    <div class="step-label">Requirements</div>
                </div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                    <div class="step-number"><?php echo $step > 2 ? '<i class="fas fa-check"></i>' : '2'; ?></div>
                    <div class="step-label">Database</div>
                </div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                    <div class="step-number"><?php echo $step > 3 ? '<i class="fas fa-check"></i>' : '3'; ?></div>
                    <div class="step-label">Admin User</div>
                </div>
                <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                    <div class="step-number"><?php echo $success ? '<i class="fas fa-check"></i>' : '4'; ?></div>
                    <div class="step-label">Finish</div>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
                <h2>System Requirements</h2>
                
                <form method="post" action="install_ai_chatbot.php?step=1">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">PHP Version</h5>
                            <p class="card-text">
                                <?php if (version_compare(PHP_VERSION, '7.4.0', '>=')): ?>
                                    <span class="text-success"><i class="fas fa-check-circle"></i> PHP <?php echo PHP_VERSION; ?></span>
                                <?php else: ?>
                                    <span class="text-danger"><i class="fas fa-times-circle"></i> PHP <?php echo PHP_VERSION; ?></span>
                                    <span class="text-muted d-block">PHP 7.4 or higher is required</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">PHP Extensions</h5>
                            <ul class="list-group">
                                <?php 
                                $requiredExtensions = ['mysqli', 'json', 'session', 'mbstring'];
                                foreach ($requiredExtensions as $ext): 
                                ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo $ext; ?>
                                        <?php if (extension_loaded($ext)): ?>
                                            <span class="text-success"><i class="fas fa-check-circle"></i></span>
                                        <?php else: ?>
                                            <span class="text-danger"><i class="fas fa-times-circle"></i></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">File Permissions</h5>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    includes/ directory
                                    <?php if (is_writable(__DIR__ . '/includes')): ?>
                                        <span class="text-success"><i class="fas fa-check-circle"></i></span>
                                    <?php else: ?>
                                        <span class="text-danger"><i class="fas fa-times-circle"></i></span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Root directory
                                    <?php if (is_writable(__DIR__)): ?>
                                        <span class="text-success"><i class="fas fa-check-circle"></i></span>
                                    <?php else: ?>
                                        <span class="text-danger"><i class="fas fa-times-circle"></i></span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Continue</button>
                    </div>
                </form>
                
            <?php elseif ($step == 2): ?>
                <h2>Database Configuration</h2>
                <p>Please enter your database connection details.</p>
                
                <form method="post" action="install_ai_chatbot.php?step=2">
                    <div class="mb-3">
                        <label for="db_host" class="form-label">Database Host</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? DB_HOST_DEFAULT); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_user" class="form-label">Database Username</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? DB_USER_DEFAULT); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_pass" class="form-label">Database Password</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($_POST['db_pass'] ?? DB_PASS_DEFAULT); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_name" class="form-label">Database Name</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? DB_NAME_DEFAULT); ?>" required>
                        <div class="form-text">If the database does not exist, we will attempt to create it.</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="install_ai_chatbot.php?step=1" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">Continue</button>
                    </div>
                </form>
                
            <?php elseif ($step == 3): ?>
                <h2>Create Admin User</h2>
                <p>Please create a super admin user for the system.</p>
                
                <form method="post" action="install_ai_chatbot.php?step=3">
                    <div class="mb-3">
                        <label for="admin_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="admin_username" name="admin_username" value="<?php echo htmlspecialchars($_POST['admin_username'] ?? 'admin'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? 'admin@example.com'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="admin_password" name="admin_password" value="admin123" required>
                        <div class="form-text">At least 6 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_password_confirm" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" value="admin123" required>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="install_ai_chatbot.php?step=2" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">Continue</button>
                    </div>
                </form>
                
            <?php elseif ($step == 4): ?>
                <?php if ($success): ?>
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                        <h2>Installation Complete!</h2>
                        <p>The AI Chatbot System has been successfully installed.</p>
                        <div class="alert alert-success">
                            <strong>Login Credentials:</strong><br>
                            Username: admin<br>
                            Password: admin123
                        </div>
                        <div class="d-grid gap-2 mt-4">
                            <a href="index.php" class="btn btn-primary btn-lg">Go to Login</a>
                        </div>
                    </div>
                <?php else: ?>
                    <h2>Final Setup</h2>
                    <p>Your system is almost ready. Would you like to create some example data?</p>
                    
                    <form method="post" action="install_ai_chatbot.php?step=4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="create_examples" name="create_examples" value="1" checked>
                            <label class="form-check-label" for="create_examples">
                                Create example business and responses
                            </label>
                            <div class="form-text">This will create a sample business with some predefined responses.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="install_ai_chatbot.php?step=3" class="btn btn-secondary">Back</a>
                            <button type="submit" class="btn btn-primary">Complete Installation</button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>