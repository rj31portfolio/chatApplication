<?php
/**
 * Admin - Business Settings
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require admin privileges
requireAdmin();

// Get business ID from session
$businessId = $_SESSION['business_id'] ?? null;

if (!$businessId) {
    $_SESSION['message'] = 'No business associated with your account. Please contact a super admin.';
    $_SESSION['message_type'] = 'danger';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Get business details
$business = dbGetRow("
    SELECT id, name, business_type, api_key 
    FROM businesses 
    WHERE id = ?
", [$businessId]);

if (!$business) {
    $_SESSION['message'] = 'Business not found';
    $_SESSION['message_type'] = 'danger';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Handle form submission for account settings
$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Validate input
        if (empty($username)) {
            $message = 'Username is required';
            $messageType = 'danger';
        } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Valid email is required';
            $messageType = 'danger';
        } else {
            // Check if username or email is already taken by another user
            $userId = $_SESSION['user_id'];
            $exists = dbGetValue("
                SELECT COUNT(*) FROM users 
                WHERE (username = ? OR email = ?) AND id != ?
            ", [$username, $email, $userId]);
            
            if ($exists) {
                $message = 'Username or email already in use';
                $messageType = 'danger';
            } else {
                // Update user profile
                $result = dbExecute("
                    UPDATE users 
                    SET username = ?, email = ? 
                    WHERE id = ?
                ", [$username, $email, $userId]);
                
                if ($result !== false) {
                    // Update session data
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    
                    $message = 'Profile updated successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating profile';
                    $messageType = 'danger';
                }
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate input
        if (empty($currentPassword)) {
            $message = 'Current password is required';
            $messageType = 'danger';
        } elseif (empty($newPassword)) {
            $message = 'New password is required';
            $messageType = 'danger';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Passwords do not match';
            $messageType = 'danger';
        } else {
            // Verify current password
            $userId = $_SESSION['user_id'];
            $userPassword = dbGetValue("SELECT password FROM users WHERE id = ?", [$userId]);
            
            if (password_verify($currentPassword, $userPassword)) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
                $result = dbExecute("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $userId]);
                
                if ($result !== false) {
                    $message = 'Password changed successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Error changing password';
                    $messageType = 'danger';
                }
            } else {
                $message = 'Current password is incorrect';
                $messageType = 'danger';
            }
        }
    } elseif ($action === 'chatbot_settings') {
        // In a real app, this would store chatbot-specific settings
        // Here we just display a success message
        $message = 'Chatbot settings updated successfully';
        $messageType = 'success';
    }
}

$pageTitle = 'Business Settings';
$extraJS = '<script src="' . BASE_URL . 'assets/js/admin.js"></script>';
?>

<?php include __DIR__ . '/../includes/templates/header.php'; ?>

<div id="alerts-container">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo h($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user"></i> Account Settings
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>admin/settings.php">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo h($_SESSION['username']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo h($_SESSION['email']); ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-key"></i> Change Password
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>admin/settings.php">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-building"></i> Business Information
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Business Name</label>
                    <input type="text" class="form-control" value="<?php echo h($business['name']); ?>" readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Business Type</label>
                    <input type="text" class="form-control" value="<?php echo h(BUSINESS_TYPES[$business['business_type']] ?? $business['business_type']); ?>" readonly>
                    <small class="text-muted">The chatbot uses this to provide relevant default responses</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">API Key</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo h($business['api_key']); ?>" id="apiKeyField" readonly>
                        <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard="<?php echo h($business['api_key']); ?>">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <small class="text-muted">Used for widget integration. Keep this private.</small>
                </div>
                
                <p class="text-muted">
                    <small>To change business information, please contact a system administrator.</small>
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-cog"></i> Chatbot Settings
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>admin/settings.php">
                    <input type="hidden" name="action" value="chatbot_settings">
                    
                    <div class="mb-3">
                        <label for="greeting_message" class="form-label">Greeting Message</label>
                        <textarea class="form-control" id="greeting_message" name="greeting_message" rows="2">Hello! How can I help you today?</textarea>
                        <small class="text-muted">First message sent by the chatbot when a conversation starts</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="chatbot_name" class="form-label">Chatbot Name</label>
                        <input type="text" class="form-control" id="chatbot_name" name="chatbot_name" value="Support Bot">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="active" name="active" checked>
                        <label class="form-check-label" for="active">Chatbot Active</label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Widget Theme Color</label>
                        <input type="color" class="form-control form-control-color" id="theme_color" name="theme_color" value="#0d6efd">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/templates/footer.php'; ?>
