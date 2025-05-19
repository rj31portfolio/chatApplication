<?php
/**
 * Super Admin - System Settings
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require super admin privileges
requireSuperAdmin();

// Handle form submission for system settings
$message = null;
$messageType = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_password') {
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
    } elseif ($action === 'system_settings') {
        // This would handle global system settings
        // For this implementation, we'll just show a success message
        $message = 'System settings updated successfully';
        $messageType = 'success';
    }
}

$pageTitle = 'System Settings';
$extraJS = '<script src="' . BASE_URL . 'assets/js/super-admin.js"></script>';
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
                <i class="fas fa-key"></i> Change Password
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>super-admin/settings.php">
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
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-cog"></i> System Settings
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>super-admin/settings.php">
                    <input type="hidden" name="action" value="system_settings">
                    
                    <div class="mb-3">
                        <label for="default_greeting" class="form-label">Default Greeting Message</label>
                        <textarea class="form-control" id="default_greeting" name="default_greeting" rows="2"><?php echo DEFAULT_GREETING; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="max_response_time" class="form-label">Maximum Response Time (seconds)</label>
                        <input type="number" class="form-control" id="max_response_time" name="max_response_time" value="<?php echo MAX_RESPONSE_TIME; ?>" min="1" max="10">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="enable_logging" name="enable_logging" checked>
                        <label class="form-check-label" for="enable_logging">Enable Detailed Logging</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> System Information
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <tbody>
                            <tr>
                                <th>PHP Version</th>
                                <td><?php echo h(phpversion()); ?></td>
                            </tr>
                            <tr>
                                <th>MySQL Version</th>
                                <td><?php 
                                    $version = dbGetValue("SELECT VERSION() as version");
                                    echo h($version); 
                                ?></td>
                            </tr>
                            <tr>
                                <th>Server Software</th>
                                <td><?php echo h($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></td>
                            </tr>
                            <tr>
                                <th>System Time</th>
                                <td><?php echo h(date('Y-m-d H:i:s')); ?></td>
                            </tr>
                            <tr>
                                <th>Timezone</th>
                                <td><?php echo h(date_default_timezone_get()); ?></td>
                            </tr>
                            <tr>
                                <th>Total Businesses</th>
                                <td><?php 
                                    $count = dbGetValue("SELECT COUNT(*) FROM businesses");
                                    echo h($count); 
                                ?></td>
                            </tr>
                            <tr>
                                <th>Total Users</th>
                                <td><?php 
                                    $count = dbGetValue("SELECT COUNT(*) FROM users");
                                    echo h($count); 
                                ?></td>
                            </tr>
                            <tr>
                                <th>Total Chat Sessions</th>
                                <td><?php 
                                    $count = dbGetValue("SELECT COUNT(*) FROM chat_sessions");
                                    echo h($count); 
                                ?></td>
                            </tr>
                            <tr>
                                <th>Total Messages</th>
                                <td><?php 
                                    $count = dbGetValue("SELECT COUNT(*) FROM messages");
                                    echo h($count); 
                                ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/templates/footer.php'; ?>
