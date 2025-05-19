<?php
/**
 * Admin - Manage Chatbot Responses
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

// Handle response creation/update
$message = null;
$messageType = null;
$response = null;

// Check if editing a response
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $response = dbGetRow("
        SELECT id, intent, pattern, response
        FROM responses 
        WHERE id = ? AND business_id = ?
    ", [$_GET['edit'], $businessId]);
    
    if (!$response) {
        $_SESSION['message'] = 'Response not found';
        $_SESSION['message_type'] = 'danger';
        header('Location: ' . BASE_URL . 'admin/responses.php');
        exit;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $responseId = $_POST['response_id'] ?? null;
    $intent = trim($_POST['intent'] ?? '');
    $pattern = trim($_POST['pattern'] ?? '');
    $responseText = trim($_POST['response'] ?? '');
    
    // Validate input
    if (empty($intent)) {
        $message = 'Intent is required';
        $messageType = 'danger';
    } elseif (empty($pattern)) {
        $message = 'Pattern is required';
        $messageType = 'danger';
    } elseif (empty($responseText)) {
        $message = 'Response is required';
        $messageType = 'danger';
    } else {
        if ($responseId) {
            // Update existing response
            $result = dbExecute("
                UPDATE responses 
                SET intent = ?, pattern = ?, response = ? 
                WHERE id = ? AND business_id = ?
            ", [$intent, $pattern, $responseText, $responseId, $businessId]);
            
            if ($result !== false) {
                $_SESSION['message'] = 'Response updated successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: ' . BASE_URL . 'admin/responses.php');
                exit;
            } else {
                $message = 'Error updating response';
                $messageType = 'danger';
            }
        } else {
            // Create new response
            $result = dbExecute("
                INSERT INTO responses (business_id, intent, pattern, response) 
                VALUES (?, ?, ?, ?)
            ", [$businessId, $intent, $pattern, $responseText]);
            
            if ($result !== false) {
                $_SESSION['message'] = 'Response created successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: ' . BASE_URL . 'admin/responses.php');
                exit;
            } else {
                $message = 'Error creating response';
                $messageType = 'danger';
            }
        }
    }
}

// Handle response deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $responseId = $_GET['delete'];
    
    // Check if response exists and belongs to this business
    $exists = dbGetValue("
        SELECT COUNT(*) FROM responses 
        WHERE id = ? AND business_id = ?
    ", [$responseId, $businessId]);
    
    if ($exists) {
        $result = dbExecute("
            DELETE FROM responses 
            WHERE id = ? AND business_id = ?
        ", [$responseId, $businessId]);
        
        if ($result !== false) {
            $_SESSION['message'] = 'Response deleted successfully';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error deleting response';
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = 'Response not found';
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: ' . BASE_URL . 'admin/responses.php');
    exit;
}

// Get responses list with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalResponses = dbGetValue("
    SELECT COUNT(*) FROM responses 
    WHERE business_id = ?
", [$businessId]);

$totalPages = ceil($totalResponses / $perPage);

$responses = dbSelect("
    SELECT id, intent, pattern, response, created_at
    FROM responses
    WHERE business_id = ?
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
", [$businessId, $perPage, $offset], 'iii');

$pageTitle = isset($_GET['edit']) ? 'Edit Response' : 'Manage Responses';
$pageActions = isset($_GET['edit']) ? '
    <a href="' . BASE_URL . 'admin/responses.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>' : '
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#responseModal">
        <i class="fas fa-plus"></i> Add Response
    </button>';

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

<?php if (isset($_GET['edit'])): ?>
    <!-- Edit Response Form -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit"></i> Edit Response
        </div>
        <div class="card-body">
            <form id="responseEditor" method="post" action="<?php echo BASE_URL; ?>admin/responses.php">
                <input type="hidden" name="response_id" value="<?php echo h($response['id']); ?>">
                
                <div class="mb-3">
                    <label for="intent" class="form-label">Intent</label>
                    <input type="text" class="form-control" id="intent" name="intent" value="<?php echo h($response['intent']); ?>" required>
                    <small class="text-muted">E.g., greeting, hours, location, products, etc.</small>
                </div>
                
                <div class="mb-3">
                    <label for="pattern" class="form-label">Pattern</label>
                    <textarea class="form-control" id="pattern" name="pattern" rows="3" required><?php echo h($response['pattern']); ?></textarea>
                    <small class="text-muted">Keywords or phrases separated by commas that will trigger this response.</small>
                </div>
                
                <div class="mb-3">
                    <label for="response" class="form-label">Response</label>
                    <textarea class="form-control" id="response" name="response" rows="5" required><?php echo h($response['response']); ?></textarea>
                    <small class="text-muted">The chatbot's response when the pattern is matched.</small>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="<?php echo BASE_URL; ?>admin/responses.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Response
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Response Templates Section -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> About Responses
        </div>
        <div class="card-body">
            <p>
                Responses allow you to customize how your chatbot replies to specific user inputs. 
                Each response has three components:
            </p>
            <ul>
                <li><strong>Intent:</strong> The purpose or category of the user's message (e.g., greeting, asking for hours, etc.)</li>
                <li><strong>Pattern:</strong> Keywords or phrases that trigger this response</li>
                <li><strong>Response:</strong> What the chatbot will say when the pattern is matched</li>
            </ul>
            <p>
                If no custom response matches, the chatbot will fall back to default responses based on your business type.
            </p>
        </div>
    </div>

    <!-- Responses List -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-comments"></i> Custom Responses
        </div>
        <div class="card-body">
            <?php if (empty($responses)): ?>
                <div class="alert alert-info">
                    You haven't created any custom responses yet. Click "Add Response" to create your first one.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($responses as $resp): ?>
                        <div class="col-12 mb-3">
                            <div class="response-item">
                                <div class="response-intent">
                                    <i class="fas fa-tag me-1"></i> <?php echo h($resp['intent']); ?>
                                </div>
                                <div class="response-pattern">
                                    <i class="fas fa-key me-1"></i> Pattern: <?php echo h($resp['pattern']); ?>
                                </div>
                                <div class="response-content">
                                    <i class="fas fa-comment me-1"></i> Response: <?php echo h($resp['response']); ?>
                                </div>
                                <div class="response-actions mt-2">
                                    <a href="<?php echo BASE_URL; ?>admin/responses.php?edit=<?php echo $resp['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-danger delete-response" data-id="<?php echo $resp['id']; ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <div class="mt-3">
                        <?php echo generatePagination($page, $totalPages, BASE_URL . 'admin/responses.php'); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Response Modal -->
    <div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="responseModalLabel">Add New Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="responseEditor" method="post" action="<?php echo BASE_URL; ?>admin/responses.php">
                        <div class="mb-3">
                            <label for="intent" class="form-label">Intent</label>
                            <input type="text" class="form-control" id="intent" name="intent" required>
                            <small class="text-muted">E.g., greeting, hours, location, products, etc.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="pattern" class="form-label">Pattern</label>
                            <textarea class="form-control" id="pattern" name="pattern" rows="3" required></textarea>
                            <small class="text-muted">Keywords or phrases separated by commas that will trigger this response.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="response" class="form-label">Response</label>
                            <textarea class="form-control" id="response" name="response" rows="4" required></textarea>
                            <small class="text-muted">The chatbot's response when the pattern is matched.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('responseEditor').submit();">
                        <i class="fas fa-plus"></i> Add Response
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
                    Are you sure you want to delete this response? This action cannot be undone.
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
            const deleteButtons = document.querySelectorAll('.delete-response');
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.dataset.id;
                    confirmDeleteBtn.href = `<?php echo BASE_URL; ?>admin/responses.php?delete=${id}`;
                    deleteModal.show();
                });
            });
        });
    </script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/templates/footer.php'; ?>
