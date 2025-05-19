<?php
/**
 * Super Admin - Manage Businesses
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require super admin privileges
requireSuperAdmin();

// Handle business creation/update
$message = null;
$messageType = null;
$business = null;

// Get all admin users for dropdown
$adminUsers = dbSelect("SELECT id, username, email FROM users WHERE role = 'admin'");

// Check if editing a business
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $business = dbGetRow("
        SELECT id, name, business_type, api_key, admin_id 
        FROM businesses 
        WHERE id = ?
    ", [$_GET['edit']]);
    
    if (!$business) {
        $_SESSION['message'] = 'Business not found';
        $_SESSION['message_type'] = 'danger';
        header('Location: ' . BASE_URL . 'super-admin/businesses.php');
        exit;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $businessId = $_POST['business_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $businessType = $_POST['business_type'] ?? '';
    $adminId = !empty($_POST['admin_id']) ? (int)$_POST['admin_id'] : null;
    
    // Validate input
    if (empty($name)) {
        $message = 'Business name is required';
        $messageType = 'danger';
    } elseif (empty($businessType)) {
        $message = 'Business type is required';
        $messageType = 'danger';
    } else {
        if ($businessId) {
            // Update existing business
            $result = dbExecute("
                UPDATE businesses 
                SET name = ?, business_type = ?, admin_id = ? 
                WHERE id = ?
            ", [$name, $businessType, $adminId, $businessId]);
            
            if ($result !== false) {
                $_SESSION['message'] = 'Business updated successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: ' . BASE_URL . 'super-admin/businesses.php');
                exit;
            } else {
                $message = 'Error updating business';
                $messageType = 'danger';
            }
        } else {
            // Create new business
            $apiKey = generateApiKey();
            
            $result = dbExecute("
                INSERT INTO businesses (name, business_type, api_key, admin_id) 
                VALUES (?, ?, ?, ?)
            ", [$name, $businessType, $apiKey, $adminId]);
            
            if ($result !== false) {
                $_SESSION['message'] = 'Business created successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: ' . BASE_URL . 'super-admin/businesses.php');
                exit;
            } else {
                $message = 'Error creating business';
                $messageType = 'danger';
            }
        }
    }
}

// Handle business deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $businessId = $_GET['delete'];
    
    // Check if business exists
    $exists = dbGetValue("SELECT COUNT(*) FROM businesses WHERE id = ?", [$businessId]);
    
    if ($exists) {
        $result = dbExecute("DELETE FROM businesses WHERE id = ?", [$businessId]);
        
        if ($result !== false) {
            $_SESSION['message'] = 'Business deleted successfully';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error deleting business';
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = 'Business not found';
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: ' . BASE_URL . 'super-admin/businesses.php');
    exit;
}

// Get businesses list with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalBusinesses = dbGetValue("SELECT COUNT(*) FROM businesses");
$totalPages = ceil($totalBusinesses / $perPage);

$businesses = dbSelect("
    SELECT b.id, b.name, b.business_type, b.api_key, b.created_at, u.username as admin
    FROM businesses b
    LEFT JOIN users u ON b.admin_id = u.id
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
", [$perPage, $offset], 'ii');

$pageTitle = isset($_GET['edit']) ? 'Edit Business' : 'Manage Businesses';
$pageActions = isset($_GET['edit']) ? '
    <a href="' . BASE_URL . 'super-admin/businesses.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>' : '
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#businessModal">
        <i class="fas fa-plus"></i> Add Business
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
    <!-- Edit Business Form -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit"></i> Edit Business
        </div>
        <div class="card-body">
            <form id="businessForm" method="post" action="<?php echo BASE_URL; ?>super-admin/businesses.php">
                <input type="hidden" name="business_id" value="<?php echo h($business['id']); ?>">
                
                <div class="mb-3">
                    <label for="name" class="form-label">Business Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo h($business['name']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="business_type" class="form-label">Business Type</label>
                    <select class="form-select" id="business_type" name="business_type" required>
                        <option value="">Select a business type</option>
                        <?php echo getBusinessTypeOptions($business['business_type']); ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="admin_id" class="form-label">Admin User</label>
                    <select class="form-select" id="admin_id" name="admin_id">
                        <option value="">No Admin</option>
                        <?php foreach ($adminUsers as $admin): ?>
                            <option value="<?php echo h($admin['id']); ?>" <?php echo ($admin['id'] == $business['admin_id']) ? 'selected' : ''; ?>>
                                <?php echo h($admin['username']); ?> (<?php echo h($admin['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">API Key</label>
                    <input type="text" class="form-control" value="<?php echo h($business['api_key']); ?>" readonly>
                    <small class="text-muted">This is used for widget integration</small>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="<?php echo BASE_URL; ?>super-admin/businesses.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Business
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Businesses List -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-building"></i> Businesses
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Admin</th>
                            <th>API Key</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($businesses)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No businesses yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($businesses as $business): ?>
                                <tr>
                                    <td><?php echo h($business['name']); ?></td>
                                    <td><?php echo h(BUSINESS_TYPES[$business['business_type']] ?? $business['business_type']); ?></td>
                                    <td><?php echo h($business['admin'] ?? 'None'); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <code class="small"><?php echo substr(h($business['api_key']), 0, 16); ?>...</code>
                                            <button class="btn btn-sm ms-2 copy-btn" data-clipboard="<?php echo h($business['api_key']); ?>">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td><?php echo formatDateTime($business['created_at']); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>super-admin/businesses.php?edit=<?php echo $business['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-danger delete-business" data-id="<?php echo $business['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="mt-3">
                    <?php echo generatePagination($page, $totalPages, BASE_URL . 'super-admin/businesses.php'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Business Modal -->
    <div class="modal fade" id="businessModal" tabindex="-1" aria-labelledby="businessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="businessModalLabel">Add New Business</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="businessForm" method="post" action="<?php echo BASE_URL; ?>super-admin/businesses.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Business Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="business_type" class="form-label">Business Type</label>
                            <select class="form-select" id="business_type" name="business_type" required>
                                <option value="">Select a business type</option>
                                <?php echo getBusinessTypeOptions(); ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_id" class="form-label">Admin User</label>
                            <select class="form-select" id="admin_id" name="admin_id">
                                <option value="">No Admin</option>
                                <?php foreach ($adminUsers as $admin): ?>
                                    <option value="<?php echo h($admin['id']); ?>">
                                        <?php echo h($admin['username']); ?> (<?php echo h($admin['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('businessForm').submit();">
                        <i class="fas fa-plus"></i> Add Business
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
                    Are you sure you want to delete this business? This action cannot be undone.
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
            const deleteButtons = document.querySelectorAll('.delete-business');
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.dataset.id;
                    confirmDeleteBtn.href = `<?php echo BASE_URL; ?>super-admin/businesses.php?delete=${id}`;
                    deleteModal.show();
                });
            });
        });
    </script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/templates/footer.php'; ?>
