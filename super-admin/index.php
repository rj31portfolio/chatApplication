<?php
/**
 * Super Admin Dashboard
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require super admin privileges
requireSuperAdmin();

// Get dashboard statistics
$totalBusinesses = dbGetValue("SELECT COUNT(*) FROM businesses");
$activeChats = dbGetValue("
    SELECT COUNT(*) FROM chat_sessions 
    WHERE ended_at IS NULL AND started_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$totalMessages = dbGetValue("SELECT COUNT(*) FROM messages");
$newBusinesses = dbGetValue("
    SELECT COUNT(*) FROM businesses 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
");

// Get business type distribution
$businessTypes = dbSelect("
    SELECT business_type, COUNT(*) as count 
    FROM businesses 
    GROUP BY business_type
    ORDER BY count DESC
");

// Get recent businesses
$recentBusinesses = dbSelect("
    SELECT b.id, b.name, b.business_type, b.created_at, u.username as admin
    FROM businesses b
    LEFT JOIN users u ON b.admin_id = u.id
    ORDER BY b.created_at DESC
    LIMIT 5
");

$pageTitle = 'Super Admin Dashboard';
$extraJS = '<script src="' . BASE_URL . 'assets/js/super-admin.js"></script>';
?>

<?php include __DIR__ . '/../includes/templates/header.php'; ?>

<div id="alerts-container"></div>

<div class="row">
    <div class="col-12 col-md-6 col-lg-3 mb-4">
        <div class="card stats-card">
            <div class="stats-icon text-primary">
                <i class="fas fa-building"></i>
            </div>
            <div class="stats-value"><?php echo h($totalBusinesses); ?></div>
            <div class="stats-label">Total Businesses</div>
        </div>
    </div>
    
    <div class="col-12 col-md-6 col-lg-3 mb-4">
        <div class="card stats-card">
            <div class="stats-icon text-success">
                <i class="fas fa-comments"></i>
            </div>
            <div class="stats-value"><?php echo h($activeChats); ?></div>
            <div class="stats-label">Active Chats (24h)</div>
        </div>
    </div>
    
    <div class="col-12 col-md-6 col-lg-3 mb-4">
        <div class="card stats-card">
            <div class="stats-icon text-info">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stats-value"><?php echo h($totalMessages); ?></div>
            <div class="stats-label">Total Messages</div>
        </div>
    </div>
    
    <div class="col-12 col-md-6 col-lg-3 mb-4">
        <div class="card stats-card">
            <div class="stats-icon text-warning">
                <i class="fas fa-plus-circle"></i>
            </div>
            <div class="stats-value"><?php echo h($newBusinesses); ?></div>
            <div class="stats-label">New Businesses (7d)</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line"></i> Businesses Growth
                <div class="float-end">
                    <input type="date" class="form-control form-control-sm date-range-picker" data-chart="businessesChart">
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="businessesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Business Types
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="businessTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Message Distribution
                <div class="float-end">
                    <input type="date" class="form-control form-control-sm date-range-picker" data-chart="messagesDistributionChart">
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="messagesDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-building"></i> Recent Businesses
                <div class="float-end">
                    <a href="<?php echo BASE_URL; ?>super-admin/businesses.php" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Admin</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentBusinesses)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No businesses yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentBusinesses as $business): ?>
                                    <tr>
                                        <td><?php echo h($business['name']); ?></td>
                                        <td><?php echo h(BUSINESS_TYPES[$business['business_type']] ?? $business['business_type']); ?></td>
                                        <td><?php echo h($business['admin'] ?? 'None'); ?></td>
                                        <td><?php echo formatDateTime($business['created_at']); ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>super-admin/businesses.php?edit=<?php echo $business['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/templates/footer.php'; ?>
