<?php
/**
 * Super Admin - Manage Users
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require super admin privileges
requireSuperAdmin();

// Handle user creation/update
$message = null;
$messageType = null;
$user = null;

// Check if editing a user
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $user = dbGetRow("
        SELECT id, username, email, role 
        FROM users 
        WHERE id = ?
    ", [$_GET['edit']]);
    
    if (!$user) {
        $_SESSION['message'] = 'User not found';
        $_SESSION['message_type'] = 'danger';
        header('Location: ' . BASE_URL . 'super-admin/users.php');
        exit;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    // Validate input
    if (empty($username)) {
        $message = 'Username is required';
        $messageType = 'danger';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Valid email is required';
        $messageType = 'danger';
    } elseif (empty($role)) {
        $message = 'Role is required';
        $messageType = 'danger';
    } elseif (!$userId && empty($password)) {
        $message = 'Password is required for new users';
        $messageType = 'danger';
    } elseif (!empty($password) && $password !== $confirmPassword) {
        $message = 'Passwords do not match';
        $messageType = 'danger';
    } else {
        if ($userId) {
            // Update existing user
            if (!empty($password)) {
                // Update with password
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
                $result = dbExecute("
                    UPDATE users 
                    SET username = ?, email = ?, role = ?, password = ? 
                    WHERE id = ?
                ", [$username, $email, $role, $hashedPassword, $userId]);
            } else {
                // Update without password
                $result = dbExecute("
                    UPDATE users 
                    SET username = ?, email = ?, role = ? 
                    WHERE id = ?
                ", [$username, $email, $role, $userId]);
            }
            
            if ($result !== false) {
                $_SESSION['message'] = 'User updated successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: ' . BASE_URL . 'super-admin/users.php');
                exit;
            } else {
                $message = 'Error updating user';
                $messageType = 'danger';
            }
        } else {
            // Create new user
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
            
            // Check if username or email already exists
            $exists = dbGetValue("
                SELECT COUNT(*) FROM users 
                WHERE username = ? OR email = ?
            ", [$username, $email]);
            
            if ($exists) {
                $message = 'Username or email already exists';
                $messageType = 'danger';
            } else {
                $result = createUser($username, $password, $email, $role);
                
                if ($result !== false) {
                    $_SESSION['message'] = 'User created successfully';
                    $_SESSION['message_type'] = 'success';
                    header('Location: ' . BASE_URL . 'super-admin/users.php');
                    exit;
                } else {
                    $message = 'Error creating user';
                    $messageType = 'danger';
                }
            }
        }
    }
}

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = $_GET['delete'];
    
    // Prevent self-deletion
    if ($userId == $_SESSION['user_id']) {
        $_SESSION['message'] = 'You cannot delete your own account';
        $_SESSION['message_type'] = 'danger';
        header('Location: ' . BASE_URL . 'super-admin/users.php');
        exit;
    }
    
    // Check if user exists
    $exists = dbGetValue("SELECT COUNT(*) FROM users WHERE id = ?", [$userId]);
    
    if ($exists) {
        // Check if user is a business admin
        $hasBusinesses = dbGetValue("SELECT COUNT(*) FROM businesses WHERE admin_id = ?", [$userId]);
        
        if ($hasBusinesses) {
            // Update businesses to remove association
            dbExecute("UPDATE businesses SET admin_id = NULL WHERE admin_id = ?", [$userId]);
        }
        
        $result = dbExecute("DELETE FROM users WHERE id = ?", [$userId]);
        
        if ($result !== false) {
            $_SESSION['message'] = 'User deleted successfully';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error deleting user';
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = 'User not found';
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: ' . BASE_URL . 'super-admin/users.php');
    exit;
}

// Get users list with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalUsers = dbGetValue("SELECT COUNT(*) FROM users");
$totalPages = ceil($totalUsers / $perPage);

$users = dbSelect("
    SELECT u.id, u.username, u.email, u.role, u.created_at, 
           COUNT(b.id) as business_count
    FROM users u
    LEFT JOIN businesses b ON u.id = b.admin_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
", [$perPage, $offset], 'ii');

$pageTitle = isset($_GET['edit']) ? 'Edit User' : 'Manage Users';
$pageActions = isset($_GET['edit']) ? '
    <a href="' . BASE_URL . 'super-admin/users.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>' : '
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
        <i class="fas fa-plus"></i> Add User
    </button>';

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

<?php if (isset($_GET['edit'])): ?>
    <!-- Edit User Form -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit"></i> Edit User
        </div>
        <div class="card-body">
            <form id="userForm" method="post" action="<?php echo BASE_URL; ?>super-admin/users.php">
                <input type="hidden" name="user_id" value="<?php echo h($user['id']); ?>">
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo h($user['username']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo h($user['email']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="super_admin" <?php echo $user['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password">
                    <small class="text-muted">Leave blank to keep current password</small>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="<?php echo BASE_URL; ?>super-admin/users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Users List -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users"></i> Users
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Businesses</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo h($user['username']); ?></td>
                                    <td><?php echo h($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'super_admin'): ?>
                                            <span class="badge bg-danger">Super Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo h($user['business_count']); ?></td>
                                    <td><?php echo formatDateTime($user['created_at']); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>super-admin/users.php?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="#" class="btn btn-sm btn-outline-danger delete-user" data-id="<?php echo $user['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="mt-3">
                    <?php echo generatePagination($page, $totalPages, BASE_URL . 'super-admin/users.php'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm" method="post" action="<?php echo BASE_URL; ?>super-admin/users.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('userForm').submit();">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this user? Any businesses associated with this user will have their admin removed.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" class="btn btn-danger" id="confirmDelete">Delete</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle delete button clicks
            const deleteButtons = document.querySelectorAll('.delete-user');
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.dataset.id;
                    confirmDeleteBtn.href = `<?php echo BASE_URL; ?>super-admin/users.php?delete=${id}`;
                    deleteModal.show();
                });
            });
        });
    </script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/templates/footer.php'; ?>
