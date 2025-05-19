 
<?php
/**
 * Login page for the AI Chatbot System
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Create database tables if they don't exist
createDatabaseTables();

// Check for super admin account and create if necessary
$superAdminExists = dbGetValue("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
if (!$superAdminExists) {
    // Create default super admin account
    createUser('admin', 'admin123', 'admin@example.com', 'super_admin');
}

// Process logout
if (isset($_GET['logout'])) {
    logout();
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
        $user = authenticateUser($username, $password);
        
        if ($user) {
            createLoginSession($user);
            
            // Redirect based on role
            if ($user['role'] === 'super_admin') {
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

<?php include __DIR__ . '/includes/templates/header.php'; ?>

<div class="container text-center">
    <div class="row">
        <div class="col-md-6 offset-md-3 col-lg-4 offset-lg-4 mt-5">
            <form class="form-signin" method="post" action="<?php echo BASE_URL; ?>login.php">
                <i class="fas fa-robot fa-4x mb-3 text-primary"></i>
                <h1 class="h3 mb-3 fw-normal">AI Chatbot System</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo h($error); ?></div>
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
                
                <p class="mt-4 text-muted">
                    <small>Default Super Admin: admin / admin123<br>
                    <span class="text-danger">Please change this after first login!</span></small>
                </p>
                
                <p class="mt-3 mb-3 text-muted">&copy; <?php echo date('Y'); ?> AI Chatbot System</p>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/templates/footer.php'; ?>
