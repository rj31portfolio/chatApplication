<?php
/**
 * Login page for the AI Chatbot System
 */

// Include required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

// Demo mode - hardcoded credentials for demonstration
 $demo_users = [
    'admin' => [
        'password' => 'admin123',
        'role' => 'super_admin',
        'email' => 'admin@example.com'
    ],
    'business_admin' => [
        'password' => 'password123',
        'role' => 'admin',
        'email' => 'business@example.com'
    ]
]; 

// Process logout
if (isset($_GET['logout'])) {
    // Simple session cleanup for logout
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Process login form
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Demo authentication
        if (array_key_exists($username, $demo_users) && $demo_users[$username]['password'] === $password) {
            // Set session variables
            $_SESSION['user_id'] = 1; // Demo user ID
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $demo_users[$username]['email'];
            $_SESSION['role'] = $demo_users[$username]['role'];
            
            // For admin users, set up a demo business
            if ($demo_users[$username]['role'] === 'admin') {
                $_SESSION['business_id'] = 1; // Demo business ID
                $_SESSION['business_name'] = 'Example Restaurant';
                $_SESSION['business_type'] = 'restaurant';
                $_SESSION['api_key'] = 'demo_api_key_' . md5($username);
            }
            
            // Redirect based on role
            if ($demo_users[$username]['role'] === 'super_admin') {
                header('Location: ' . BASE_URL . 'super-admin/index.php');
            } else {
                header('Location: ' . BASE_URL . 'admin/index.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}

$pageTitle = 'Login';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - AI Chatbot System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container text-center">
        <div class="row">
            <div class="col-md-6 offset-md-3 col-lg-4 offset-lg-4 mt-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form class="form-signin" method="post" action="<?php echo BASE_URL; ?>login.php">
                            <i class="fas fa-robot fa-4x mb-3 text-primary"></i>
                            <h1 class="h3 mb-3 fw-normal">AI Chatbot System</h1>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required autofocus>
                                <label for="username">Username</label>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <label for="password">Password</label>
                            </div>
                            
                            <div class="d-grid">
                                <button class="btn btn-primary btn-lg" type="submit">
                                    <i class="fas fa-sign-in-alt"></i> Sign in
                                </button>
                            </div>
                            
                            <div class="alert alert-info mt-4">
                                <p class="mb-0"><strong>Demo Credentials:</strong></p>
                                <p class="mb-1 mt-2"><strong>Super Admin:</strong> admin / admin123</p>
                                <p class="mb-1"><strong>Business Admin:</strong> business_admin / password123</p>
                            </div>
                            
                            <p class="mt-3 mb-1 text-muted">&copy; <?php echo date('Y'); ?> AI Chatbot System</p>
                            <a href="<?php echo BASE_URL; ?>" class="btn btn-sm btn-link text-muted">Return to Home</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
