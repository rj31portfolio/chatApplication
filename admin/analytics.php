<?php
/**
 * Admin - Analytics and Chat Reports
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

// Set date range for filtering
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get overall statistics
$totalSessions = dbGetValue("
    SELECT COUNT(*) FROM chat_sessions 
    WHERE business_id = ? AND DATE(started_at) BETWEEN ? AND ?
", [$businessId, $startDate, $endDate]);

$totalMessages = dbGetValue("
    SELECT COUNT(*) FROM messages m
    JOIN chat_sessions s ON m.session_id = s.id
    WHERE s.business_id = ? AND DATE(s.started_at) BETWEEN ? AND ?
", [$businessId, $startDate, $endDate]);

$userMessages = dbGetValue("
    SELECT COUNT(*) FROM messages m
    JOIN chat_sessions s ON m.session_id = s.id
    WHERE s.business_id = ? AND m.is_bot = 0 AND DATE(s.started_at) BETWEEN ? AND ?
", [$businessId, $startDate, $endDate]);

$botMessages = dbGetValue("
    SELECT COUNT(*) FROM messages m
    JOIN chat_sessions s ON m.session_id = s.id
    WHERE s.business_id = ? AND m.is_bot = 1 AND DATE(s.started_at) BETWEEN ? AND ?
", [$businessId, $startDate, $endDate]);

// Get daily message counts for chart
$dailyMessages = dbSelect("
    SELECT DATE(s.started_at) as date, 
           COUNT(CASE WHEN m.is_bot = 0 THEN 1 END) as user_messages,
           COUNT(CASE WHEN m.is_bot = 1 THEN 1 END) as bot_messages
    FROM chat_sessions s
    JOIN messages m ON s.id = m.session_id
    WHERE s.business_id = ? AND DATE(s.started_at) BETWEEN ? AND ?
    GROUP BY DATE(s.started_at)
    ORDER BY date
", [$businessId, $startDate, $endDate]);

// Get popular questions/patterns
$popularQuestions = dbSelect("
    SELECT m.message, COUNT(*) as count
    FROM messages m
    JOIN chat_sessions s ON m.session_id = s.id
    WHERE s.business_id = ? AND m.is_bot = 0 AND DATE(s.started_at) BETWEEN ? AND ?
    GROUP BY m.message
    ORDER BY count DESC
    LIMIT 10
", [$businessId, $startDate, $endDate]);

// Get all chat sessions with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$totalSessionsCount = dbGetValue("
    SELECT COUNT(*) FROM chat_sessions 
    WHERE business_id = ? AND DATE(started_at) BETWEEN ? AND ?
", [$businessId, $startDate, $endDate]);

$totalPages = ceil($totalSessionsCount / $perPage);

$sessions = dbSelect("
    SELECT s.id, s.session_id, s.started_at, s.ended_at, s.visitor_ip, s.user_agent,
           COUNT(m.id) as message_count
    FROM chat_sessions s
    LEFT JOIN messages m ON s.id = m.session_id
    WHERE s.business_id = ? AND DATE(s.started_at) BETWEEN ? AND ?
    GROUP BY s.id
    ORDER BY s.started_at DESC
    LIMIT ? OFFSET ?
", [$businessId, $startDate, $endDate, $perPage, $offset], 'ssiii');

// Calculate average response time (simulated for demo)
$avgResponseTime = rand(1, 3); // In seconds, would actually be calculated from database

// Calculate conversation lengths
$conversationLengths = dbSelect("
    SELECT 
        CASE 
            WHEN message_count <= 2 THEN 'Very Short (1-2 messages)'
            WHEN message_count <= 5 THEN 'Short (3-5 messages)'
            WHEN message_count <= 10 THEN 'Medium (6-10 messages)'
            WHEN message_count <= 20 THEN 'Long (11-20 messages)'
            ELSE 'Very Long (20+ messages)'
        END as length_category,
        COUNT(*) as session_count
    FROM (
        SELECT s.id, COUNT(m.id) as message_count
        FROM chat_sessions s
        LEFT JOIN messages m ON s.id = m.session_id
        WHERE s.business_id = ? AND DATE(s.started_at) BETWEEN ? AND ?
        GROUP BY s.id
    ) as session_lengths
    GROUP BY length_category
    ORDER BY session_count DESC
", [$businessId, $startDate, $endDate]);

$pageTitle = 'Analytics';
$extraJS = '<script src="' . BASE_URL . 'assets/js/admin.js"></script>';
?>

<?php include __DIR__ . '/../includes/templates/header.php'; ?>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?php echo BASE_URL; ?>admin/analytics.php" class="row g-3 align-items-center">
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
                <i class="fas fa-comments"></i>
            </div>
            <div class="stats-value"><?php echo h($totalSessions); ?></div>
            <div class="stats-label">Total Chat Sessions</div>
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
            <div class="stats-icon text-success">
                <i class="fas fa-user"></i>
            </div>
            <div class="stats-value"><?php echo h($userMessages); ?></div>
            <div class="stats-label">User Messages</div>
        </div>
    </div>
    
    <div class="col-12 col-md-6 col-lg-3 mb-4">
        <div class="card stats-card">
            <div class="stats-icon text-warning">
                <i class="fas fa-robot"></i>
            </div>
            <div class="stats-value"><?php echo h($botMessages); ?></div>
            <div class="stats-label">Bot Responses</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line"></i> Message Activity
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="messagesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Conversation Lengths
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="conversationsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Response Times & Top Questions Row -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-clock"></i> Response Times
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="responseTimesChart"></canvas>
                </div>
                <div class="text-center mt-3">
                    <p class="mb-0">Average Response Time</p>
                    <h3 class="text-primary"><?php echo h($avgResponseTime); ?> seconds</h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-question-circle"></i> Popular Questions
            </div>
            <div class="card-body">
                <?php if (empty($popularQuestions)): ?>
                    <div class="alert alert-info">No questions recorded yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popularQuestions as $question): ?>
                                    <tr>
                                        <td><?php echo h(strlen($question['message']) > 80 ? substr($question['message'], 0, 80) . '...' : $question['message']); ?></td>
                                        <td><?php echo h($question['count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chat Sessions List -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-list"></i> Chat Sessions
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Session ID</th>
                        <th>Started</th>
                        <th>Duration</th>
                        <th>Messages</th>
                        <th>Visitor IP</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No chat sessions for this period</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?php echo h(substr($session['session_id'], 0, 8)); ?>...</td>
                                <td><?php echo formatDateTime($session['started_at']); ?></td>
                                <td>
                                    <?php 
                                        if ($session['ended_at']) {
                                            $start = new DateTime($session['started_at']);
                                            $end = new DateTime($session['ended_at']);
                                            $duration = $start->diff($end);
                                            echo $duration->format('%i min %s sec');
                                        } else {
                                            echo '<span class="badge bg-success">Active</span>';
                                        }
                                    ?>
                                </td>
                                <td><?php echo h($session['message_count']); ?></td>
                                <td><?php echo h($session['visitor_ip']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary view-session" 
                                            data-id="<?php echo $session['id']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#sessionModal">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="mt-3">
                <?php echo generatePagination($page, $totalPages, BASE_URL . 'admin/analytics.php?' . http_build_query([
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ])); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Session Detail Modal -->
<div class="modal fade" id="sessionModal" tabindex="-1" aria-labelledby="sessionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sessionModalLabel">Chat Session Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading chat history...</p>
                </div>
                <div id="chatHistory" class="d-none">
                    <div id="sessionMessages"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Daily messages chart data
        const messageData = <?php 
            $dates = [];
            $userMessages = [];
            $botMessages = [];
            
            foreach ($dailyMessages as $daily) {
                $dates[] = (new DateTime($daily['date']))->format('M j');
                $userMessages[] = (int)$daily['user_messages'];
                $botMessages[] = (int)$daily['bot_messages'];
            }
            
            echo json_encode([
                'dates' => $dates,
                'userMessages' => $userMessages,
                'botMessages' => $botMessages
            ]);
        ?>;
        
        // Conversation length data
        const conversationLengthData = <?php 
            $categories = [];
            $counts = [];
            
            foreach ($conversationLengths as $length) {
                $categories[] = $length['length_category'];
                $counts[] = (int)$length['session_count'];
            }
            
            echo json_encode([
                'categories' => $categories,
                'counts' => $counts
            ]);
        ?>;
        
        // Initialize messages chart
        if (document.getElementById('messagesChart')) {
            const ctx = document.getElementById('messagesChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: messageData.dates,
                    datasets: [
                        {
                            label: 'User Messages',
                            data: messageData.userMessages,
                            backgroundColor: 'rgba(13, 110, 253, 0.2)',
                            borderColor: 'rgba(13, 110, 253, 1)',
                            borderWidth: 1,
                            tension: 0.4
                        },
                        {
                            label: 'Bot Messages',
                            data: messageData.botMessages,
                            backgroundColor: 'rgba(25, 135, 84, 0.2)',
                            borderColor: 'rgba(25, 135, 84, 1)',
                            borderWidth: 1,
                            tension: 0.4
                        }
                    ]
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
        
        // Initialize conversation lengths chart
        if (document.getElementById('conversationsChart')) {
            const ctx = document.getElementById('conversationsChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: conversationLengthData.categories,
                    datasets: [{
                        data: conversationLengthData.counts,
                        backgroundColor: [
                            'rgba(13, 110, 253, 0.7)',
                            'rgba(25, 135, 84, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(220, 53, 69, 0.7)',
                            'rgba(108, 117, 125, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 15
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize response times chart
        if (document.getElementById('responseTimesChart')) {
            const ctx = document.getElementById('responseTimesChart').getContext('2d');
            
            // Sample data - in a real app, this would be from database
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['< 1s', '1-2s', '2-3s', '> 3s'],
                    datasets: [{
                        label: 'Response Time',
                        data: [30, 40, 20, 10],
                        backgroundColor: [
                            'rgba(25, 135, 84, 0.7)',
                            'rgba(13, 110, 253, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(220, 53, 69, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 15
                            }
                        }
                    }
                }
            });
        }
        
        // Handle session view button clicks
        const viewButtons = document.querySelectorAll('.view-session');
        const sessionModal = document.getElementById('sessionModal');
        const chatHistory = document.getElementById('chatHistory');
        const sessionMessages = document.getElementById('sessionMessages');
        
        sessionModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const sessionId = button.dataset.id;
            
            // Reset modal content
            chatHistory.classList.add('d-none');
            sessionMessages.innerHTML = '';
            
            // Fetch session messages
            fetch(`<?php echo BASE_URL; ?>api/admin.php?action=get_session_messages&session_id=${sessionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Create messages HTML
                        let messagesHtml = '';
                        data.messages.forEach(message => {
                            const messageClass = message.is_bot ? 'bg-light text-dark' : 'bg-primary text-white';
                            const sender = message.is_bot ? 'Bot' : 'User';
                            
                            messagesHtml += `
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <strong>${sender}</strong> 
                                        <small class="text-muted">${message.created_at}</small>
                                    </div>
                                    <div class="card-body ${messageClass}">
                                        ${message.message}
                                    </div>
                                </div>
                            `;
                        });
                        
                        if (messagesHtml === '') {
                            messagesHtml = '<div class="alert alert-info">No messages in this session</div>';
                        }
                        
                        sessionMessages.innerHTML = messagesHtml;
                        chatHistory.classList.remove('d-none');
                    } else {
                        sessionMessages.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        chatHistory.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    sessionMessages.innerHTML = '<div class="alert alert-danger">Error loading chat history</div>';
                    chatHistory.classList.remove('d-none');
                });
        });
    });
</script>

<?php include __DIR__ . '/../includes/templates/footer.php'; ?>
