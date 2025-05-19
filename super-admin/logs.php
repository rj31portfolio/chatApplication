<?php
/**
 * Super Admin - System Logs
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require super admin privileges
requireSuperAdmin();

// Set up filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$businessId = isset($_GET['business_id']) ? (int)$_GET['business_id'] : null;

// Get all businesses for filter dropdown
$businesses = dbSelect("SELECT id, name FROM businesses ORDER BY name");

// Fetch chat sessions with filters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
$types = 'ss';
$businessFilter = '';

if ($businessId) {
    $businessFilter = "AND s.business_id = ?";
    $params[] = $businessId;
    $types .= 'i';
}

$totalSessions = dbGetValue("
    SELECT COUNT(*) 
    FROM chat_sessions s
    WHERE s.started_at BETWEEN ? AND ? $businessFilter
", $params, $types);

$totalPages = ceil($totalSessions / $perPage);

// Add pagination parameters
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$sessions = dbSelect("
    SELECT s.id, s.session_id, s.started_at, s.ended_at, s.visitor_ip, s.user_agent,
           b.name as business_name, b.business_type,
           COUNT(m.id) as message_count
    FROM chat_sessions s
    JOIN businesses b ON s.business_id = b.id
    LEFT JOIN messages m ON s.id = m.session_id
    WHERE s.started_at BETWEEN ? AND ? $businessFilter
    GROUP BY s.id
    ORDER BY s.started_at DESC
    LIMIT ? OFFSET ?
", $params, $types);

$pageTitle = 'System Logs';
$extraJS = '<script src="' . BASE_URL . 'assets/js/super-admin.js"></script>';
?>

<?php include __DIR__ . '/../includes/templates/header.php'; ?>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form id="logFilterForm" method="get" action="<?php echo BASE_URL; ?>super-admin/logs.php" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo h($startDate); ?>">
            </div>
            
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo h($endDate); ?>">
            </div>
            
            <div class="col-md-4">
                <label for="business_id" class="form-label">Business</label>
                <select class="form-select" id="business_id" name="business_id">
                    <option value="">All Businesses</option>
                    <?php foreach ($businesses as $business): ?>
                        <option value="<?php echo h($business['id']); ?>" <?php echo $businessId == $business['id'] ? 'selected' : ''; ?>>
                            <?php echo h($business['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Chat Sessions -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-clipboard-list"></i> Chat Sessions
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Business</th>
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
                            <td colspan="7" class="text-center">No chat sessions found for the selected criteria</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?php echo h(substr($session['session_id'], 0, 8)); ?>...</td>
                                <td><?php echo h($session['business_name']); ?></td>
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
                                            data-business="<?php echo h($session['business_name']); ?>"
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
                <?php echo generatePagination($page, $totalPages, BASE_URL . 'super-admin/logs.php?' . http_build_query([
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'business_id' => $businessId
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
                    <div class="mb-3">
                        <strong>Business:</strong> <span id="sessionBusiness"></span>
                    </div>
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
        // Handle session view button clicks
        const viewButtons = document.querySelectorAll('.view-session');
        const sessionModal = document.getElementById('sessionModal');
        const chatHistory = document.getElementById('chatHistory');
        const sessionMessages = document.getElementById('sessionMessages');
        const sessionBusiness = document.getElementById('sessionBusiness');
        
        sessionModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const sessionId = button.dataset.id;
            const businessName = button.dataset.business;
            
            // Reset modal content
            chatHistory.classList.add('d-none');
            sessionMessages.innerHTML = '';
            sessionBusiness.textContent = businessName;
            
            // Fetch session messages
            fetch(`<?php echo BASE_URL; ?>api/super-admin.php?action=get_session_messages&session_id=${sessionId}`)
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
