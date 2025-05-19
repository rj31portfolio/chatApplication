<?php
/**
 * Super Admin - System Analytics
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require super admin privileges
requireSuperAdmin();

// Set date range for filtering
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get overall statistics
$totalBusinesses = dbGetValue("SELECT COUNT(*) FROM businesses");
$totalMessages = dbGetValue("
    SELECT COUNT(*) FROM messages m
    JOIN chat_sessions s ON m.session_id = s.id
    WHERE DATE(s.started_at) BETWEEN ? AND ?
", [$startDate, $endDate]);
$totalSessions = dbGetValue("
    SELECT COUNT(*) FROM chat_sessions
    WHERE DATE(started_at) BETWEEN ? AND ?
", [$startDate, $endDate]);
$avgMessagesPerSession = $totalSessions > 0 ? round($totalMessages / $totalSessions, 1) : 0;

// Get message data by date for chart
$messagesByDate = dbSelect("
    SELECT DATE(s.started_at) as date, COUNT(*) as count
    FROM messages m
    JOIN chat_sessions s ON m.session_id = s.id
    WHERE DATE(s.started_at) BETWEEN ? AND ?
    GROUP BY DATE(s.started_at)
    ORDER BY date
", [$startDate, $endDate]);

// Get business type distribution
$businessTypes = dbSelect("
    SELECT business_type, COUNT(*) as count 
    FROM businesses 
    GROUP BY business_type
    ORDER BY count DESC
");

// Get top businesses by chat volume
$topBusinesses = dbSelect("
    SELECT b.name, b.business_type, COUNT(s.id) as session_count, COUNT(m.id) as message_count
    FROM businesses b
    JOIN chat_sessions s ON b.id = s.business_id
    JOIN messages m ON s.id = m.session_id
    WHERE DATE(s.started_at) BETWEEN ? AND ?
    GROUP BY b.id
    ORDER BY message_count DESC
    LIMIT 10
", [$startDate, $endDate]);

$pageTitle = 'System Analytics';
$extraJS = '<script src="' . BASE_URL . 'assets/js/super-admin.js"></script>';
?>

<?php include __DIR__ . '/../includes/templates/header.php'; ?>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?php echo BASE_URL; ?>super-admin/analytics.php" class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="start_date" class="col-form-label">From:</label>
            </div>
            <div class="col-auto">
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo h($startDate); ?>">
            </div>
            <div class="col-auto">
                <label for="end_date" class="col-form-label">To:</label>
            </div>
            <div class="col-auto">
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo h($endDate); ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Apply</button>
            </div>
        </form>
    </div>
</div>

<!-- Overall Statistics -->
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
            <div class="stats-value"><?php echo h($totalSessions); ?></div>
            <div class="stats-label">Chat Sessions</div>
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
                <i class="fas fa-calculator"></i>
            </div>
            <div class="stats-value"><?php echo h($avgMessagesPerSession); ?></div>
            <div class="stats-label">Avg Messages/Chat</div>
        </div>
    </div>
</div>

<!-- Messages by Date Chart -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Message Activity
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
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-trophy"></i> Top Businesses by Chat Volume
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Business</th>
                                <th>Type</th>
                                <th>Sessions</th>
                                <th>Messages</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topBusinesses)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No data available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topBusinesses as $business): ?>
                                    <tr>
                                        <td><?php echo h($business['name']); ?></td>
                                        <td><?php echo h(BUSINESS_TYPES[$business['business_type']] ?? $business['business_type']); ?></td>
                                        <td><?php echo h($business['session_count']); ?></td>
                                        <td><?php echo h($business['message_count']); ?></td>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Convert PHP data to JavaScript for charts
        const messageData = <?php 
            $labels = [];
            $counts = [];
            foreach ($messagesByDate as $row) {
                $labels[] = (new DateTime($row['date']))->format('M j');
                $counts[] = $row['count'];
            }
            echo json_encode(['labels' => $labels, 'counts' => $counts]); 
        ?>;
        
        const businessTypeData = <?php 
            $labels = [];
            $counts = [];
            foreach ($businessTypes as $type) {
                $labels[] = BUSINESS_TYPES[$type['business_type']] ?? $type['business_type'];
                $counts[] = $type['count'];
            }
            echo json_encode(['labels' => $labels, 'counts' => $counts]); 
        ?>;
        
        // Messages Distribution Chart
        if (document.getElementById('messagesDistributionChart')) {
            const ctx = document.getElementById('messagesDistributionChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: messageData.labels,
                    datasets: [{
                        label: 'Messages',
                        data: messageData.counts,
                        backgroundColor: 'rgba(13, 110, 253, 0.2)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Business Type Chart
        if (document.getElementById('businessTypeChart')) {
            const ctx = document.getElementById('businessTypeChart').getContext('2d');
            const backgroundColors = [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(255, 159, 64, 0.2)',
                'rgba(199, 199, 199, 0.2)'
            ];
            const borderColors = backgroundColors.map(color => color.replace('0.2', '1'));
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: businessTypeData.labels,
                    datasets: [{
                        data: businessTypeData.counts,
                        backgroundColor: backgroundColors.slice(0, businessTypeData.labels.length),
                        borderColor: borderColors.slice(0, businessTypeData.labels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    });
</script>

<?php include __DIR__ . '/../includes/templates/footer.php'; ?>
