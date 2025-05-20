<?php
/**
 * Admin Dashboard
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

try {
    // Get dashboard statistics
    $totalSessions = dbGetValue("
        SELECT COUNT(*) FROM chat_sessions 
        WHERE business_id = ?
    ", [$businessId]);

    $activeSessions = dbGetValue("
        SELECT COUNT(*) FROM chat_sessions 
        WHERE business_id = ? AND ended_at IS NULL
    ", [$businessId]);

    $totalMessages = dbGetValue("
        SELECT COUNT(*) FROM messages m
        JOIN chat_sessions s ON m.session_id = s.id
        WHERE s.business_id = ?
    ", [$businessId]);

    $avgMessagesPerSession = $totalSessions > 0 
        ? dbGetValue("
            SELECT ROUND(AVG(message_count), 1) FROM (
                SELECT COUNT(m.id) as message_count
                FROM chat_sessions s
                JOIN messages m ON s.id = m.session_id
                WHERE s.business_id = ?
                GROUP BY s.id
            ) as message_counts
        ", [$businessId])
        : 0;

    // Get recent sessions
    $recentSessions = dbSelect("
        SELECT s.id, s.session_id, s.started_at, s.ended_at, s.visitor_ip,
               COUNT(m.id) as message_count
        FROM chat_sessions s
        LEFT JOIN messages m ON s.id = m.session_id
        WHERE s.business_id = ?
        GROUP BY s.id
        ORDER BY s.started_at DESC
        LIMIT 5
    ", [$businessId]);

    // Get message activity data for chart
    $messageActivity = dbSelect("
        SELECT DATE(s.started_at) as date, 
               SUM(CASE WHEN m.is_bot = 0 THEN 1 ELSE 0 END) as user_messages,
               SUM(CASE WHEN m.is_bot = 1 THEN 1 ELSE 0 END) as bot_messages
        FROM chat_sessions s
        JOIN messages m ON s.id = m.session_id
        WHERE s.business_id = ? AND DATE(s.started_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(s.started_at)
        ORDER BY date
    ", [$businessId]);

    // Get common questions/intents
    $commonQuestions = dbSelect("
        SELECT m.message, COUNT(*) as count
        FROM messages m
        JOIN chat_sessions s ON m.session_id = s.id
        WHERE s.business_id = ? AND m.is_bot = 0
        GROUP BY m.message
        ORDER BY count DESC
        LIMIT 5
    ", [$businessId]);

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $_SESSION['message'] = 'Error loading dashboard data. Please try again.';
    $_SESSION['message_type'] = 'danger';
    header('Location: ' . BASE_URL . 'admin/');
    exit;
}

$pageTitle = 'Admin Dashboard';
$extraJS = '<script src="' . BASE_URL . 'assets/js/admin.js"></script>';
?>

<?php include __DIR__ . '/../includes/templates/header.php'; ?>

<div id="alerts-container">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo h($_SESSION['message_type']); ?> alert-dismissible fade show" role="alert">
            <?php echo h($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>
</div>

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
            <div class="stats-icon text-success">
                <i class="fas fa-comment-dots"></i>
            </div>
            <div class="stats-value"><?php echo h($activeSessions); ?></div>
            <div class="stats-label">Active Sessions</div>
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

<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Message Activity (Last 7 Days)
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height: 300px;">
                    <canvas id="messagesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-question-circle"></i> Common Questions
            </div>
            <div class="card-body">
                <?php if (empty($commonQuestions)): ?>
                    <div class="alert alert-info">No questions recorded yet.</div>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($commonQuestions as $question): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo h(strlen($question['message']) > 50 ? substr($question['message'], 0, 50) . '...' : $question['message']); ?>
                                <span class="badge bg-primary rounded-pill"><?php echo h($question['count']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Recent Chat Sessions
                <div class="float-end">
                    <a href="<?php echo BASE_URL; ?>admin/analytics.php" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
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
                            <?php if (empty($recentSessions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No chat sessions yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentSessions as $session): ?>
                                    <tr>
                                        <td><?php echo h(substr($session['session_id'], 0, 8)); ?>...</td>
                                        <td><?php echo formatDateTime($session['started_at']); ?></td>
                                        <td>
                                            <?php 
                                                if ($session['ended_at']) {
                                                    try {
                                                        $start = new DateTime($session['started_at']);
                                                        $end = new DateTime($session['ended_at']);
                                                        $duration = $start->diff($end);
                                                        echo $duration->format('%i min %s sec');
                                                    } catch (Exception $e) {
                                                        echo 'N/A';
                                                    }
                                                } else {
                                                    echo '<span class="badge bg-success">Active</span>';
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo h($session['message_count']); ?></td>
                                        <td><?php echo h($session['visitor_ip']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary view-session" 
                                                    data-id="<?php echo h($session['id']); ?>"
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
            </div>
        </div>
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
        // Message chart data
        const messageData = <?php 
            $dates = [];
            $userMessages = [];
            $botMessages = [];
            
            // Get last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $dateFormatted = date('M j', strtotime($date));
                $dates[] = $dateFormatted;
                
                // Find data for this date
                $userCount = 0;
                $botCount = 0;
                foreach ($messageActivity as $activity) {
                    if ($activity['date'] == $date) {
                        $userCount = (int)$activity['user_messages'];
                        $botCount = (int)$activity['bot_messages'];
                        break;
                    }
                }
                
                $userMessages[] = $userCount;
                $botMessages[] = $botCount;
            }
            
            echo json_encode([
                'dates' => $dates,
                'userMessages' => $userMessages,
                'botMessages' => $botMessages
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        ?>;
        
        // Initialize messages chart
        if (document.getElementById('messagesChart')) {
            const ctx = document.getElementById('messagesChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: messageData.dates,
                    datasets: [
                        {
                            label: 'User Messages',
                            data: messageData.userMessages,
                            backgroundColor: 'rgba(13, 110, 253, 0.2)',
                            borderColor: 'rgba(13, 110, 253, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Bot Messages',
                            data: messageData.botMessages,
                            backgroundColor: 'rgba(25, 135, 84, 0.2)',
                            borderColor: 'rgba(25, 135, 84, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
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
        
        if (sessionModal) {
            sessionModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const sessionId = button.dataset.id;
                
                // Reset modal content
                chatHistory.classList.add('d-none');
                sessionMessages.innerHTML = '';
                
                // Fetch session messages
                fetch(`<?php echo BASE_URL; ?>api/admin.php?action=get_session_messages&session_id=${sessionId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
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
                            sessionMessages.innerHTML = `<div class="alert alert-danger">${data.message || 'Error loading session data'}</div>`;
                            chatHistory.classList.remove('d-none');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        sessionMessages.innerHTML = '<div class="alert alert-danger">Error loading chat history</div>';
                        chatHistory.classList.remove('d-none');
                    });
            });
        }
    });
</script>

<?php include __DIR__ . '/../includes/templates/footer.php'; ?>