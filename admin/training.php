<?php
/**
 * Admin - Train Chatbot
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

// Handle form submission
$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userMessages = $_POST['user_messages'] ?? [];
    $botResponses = $_POST['bot_responses'] ?? [];
    $intents = $_POST['intents'] ?? [];
    
    // Validate input
    if (empty($userMessages) || empty($botResponses)) {
        $message = 'Please provide at least one training example';
        $messageType = 'danger';
    } else {
        // Process training data
        $success = true;
        $addedCount = 0;
        
        for ($i = 0; $i < count($userMessages); $i++) {
            $userMessage = trim($userMessages[$i]);
            $botResponse = trim($botResponses[$i]);
            $intent = !empty($intents[$i]) ? trim($intents[$i]) : 'custom';
            
            if (!empty($userMessage) && !empty($botResponse)) {
                // Create a response pattern from the user message
                // We're using the message directly as a pattern for simplicity
                // In a more sophisticated system, this would extract keywords or use ML
                $pattern = $userMessage;
                
                // Add to responses table
                $result = dbExecute("
                    INSERT INTO responses (business_id, intent, pattern, response)
                    VALUES (?, ?, ?, ?)
                ", [$businessId, $intent, $pattern, $botResponse]);
                
                if ($result !== false) {
                    $addedCount++;
                } else {
                    $success = false;
                }
            }
        }
        
        if ($success && $addedCount > 0) {
            $_SESSION['message'] = "Successfully added $addedCount training examples";
            $_SESSION['message_type'] = 'success';
            header('Location: ' . BASE_URL . 'admin/training.php');
            exit;
        } else {
            $message = 'Error adding training examples';
            $messageType = 'danger';
        }
    }
}

// Get existing responses
$responses = dbSelect("
    SELECT id, intent, pattern, response 
    FROM responses 
    WHERE business_id = ?
    ORDER BY intent, created_at DESC
", [$businessId]);

// Group responses by intent
$groupedResponses = [];
foreach ($responses as $response) {
    $intent = $response['intent'];
    if (!isset($groupedResponses[$intent])) {
        $groupedResponses[$intent] = [];
    }
    $groupedResponses[$intent][] = $response;
}

$pageTitle = 'Train Chatbot';
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

<!-- Training Introduction -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-info-circle"></i> About Chatbot Training
    </div>
    <div class="card-body">
        <p>
            Training your chatbot helps it learn to respond appropriately to your customers' messages.
            Add examples of user messages and the responses your chatbot should give.
        </p>
        <p>
            Each example should include:
        </p>
        <ul>
            <li><strong>User Message:</strong> What a customer might say or ask</li>
            <li><strong>Bot Response:</strong> How your chatbot should reply</li>
            <li><strong>Intent (optional):</strong> The purpose or category of the message</li>
        </ul>
        <p>
            The more examples you provide, the better your chatbot will understand your customers.
        </p>
    </div>
</div>

<!-- Training Form -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-brain"></i> Add Training Examples
    </div>
    <div class="card-body">
        <form id="trainingForm" method="post" action="<?php echo BASE_URL; ?>admin/training.php">
            <div id="examplesContainer" class="training-container">
                <!-- First example (required) -->
                <div class="training-pair">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="userMessage0" class="form-label">User Message</label>
                            <textarea class="form-control" id="userMessage0" name="user_messages[]" rows="2" placeholder="e.g., What are your opening hours?" required></textarea>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label for="botResponse0" class="form-label">Bot Response</label>
                            <textarea class="form-control" id="botResponse0" name="bot_responses[]" rows="2" placeholder="e.g., We're open Monday to Friday, 9am to 5pm." required></textarea>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="intent0" class="form-label">Intent (optional)</label>
                            <input type="text" class="form-control" id="intent0" name="intents[]" placeholder="e.g., hours">
                        </div>
                    </div>
                </div>
                
                <!-- Additional examples will be added here dynamically -->
            </div>
            
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" id="addExampleButton" class="btn btn-outline-primary">
                    <i class="fas fa-plus"></i> Add Another Example
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Training Examples
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Existing Responses -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-list"></i> Existing Training Examples
    </div>
    <div class="card-body">
        <?php if (empty($responses)): ?>
            <div class="alert alert-info">
                No training examples yet. Add some examples above to train your chatbot.
            </div>
        <?php else: ?>
            <div class="accordion" id="responsesAccordion">
                <?php foreach ($groupedResponses as $intent => $intentResponses): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo h(md5($intent)); ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapse<?php echo h(md5($intent)); ?>" 
                                    aria-expanded="false" aria-controls="collapse<?php echo h(md5($intent)); ?>">
                                Intent: <?php echo h($intent); ?> (<?php echo count($intentResponses); ?> examples)
                            </button>
                        </h2>
                        <div id="collapse<?php echo h(md5($intent)); ?>" class="accordion-collapse collapse" 
                             aria-labelledby="heading<?php echo h(md5($intent)); ?>" data-bs-parent="#responsesAccordion">
                            <div class="accordion-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>User Message (Pattern)</th>
                                                <th>Bot Response</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($intentResponses as $resp): ?>
                                                <tr>
                                                    <td><?php echo h($resp['pattern']); ?></td>
                                                    <td><?php echo h($resp['response']); ?></td>
                                                    <td>
                                                        <a href="<?php echo BASE_URL; ?>admin/responses.php?edit=<?php echo $resp['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-outline-danger delete-response" data-id="<?php echo $resp['id']; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
                Are you sure you want to delete this training example? This action cannot be undone.
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
        // Add example button functionality
        const addExampleButton = document.getElementById('addExampleButton');
        const examplesContainer = document.getElementById('examplesContainer');
        
        addExampleButton.addEventListener('click', function() {
            const exampleCount = document.querySelectorAll('.training-pair').length;
            
            const newExample = document.createElement('div');
            newExample.className = 'training-pair';
            newExample.innerHTML = `
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label for="userMessage${exampleCount}" class="form-label">User Message</label>
                        <textarea class="form-control" id="userMessage${exampleCount}" name="user_messages[]" rows="2" placeholder="e.g., What are your opening hours?" required></textarea>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="botResponse${exampleCount}" class="form-label">Bot Response</label>
                        <textarea class="form-control" id="botResponse${exampleCount}" name="bot_responses[]" rows="2" placeholder="e.g., We're open Monday to Friday, 9am to 5pm." required></textarea>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="d-flex flex-column h-100">
                            <label for="intent${exampleCount}" class="form-label">Intent (optional)</label>
                            <input type="text" class="form-control mb-2" id="intent${exampleCount}" name="intents[]" placeholder="e.g., hours">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-example mt-auto">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            examplesContainer.appendChild(newExample);
            
            // Add event listener to the new remove button
            const removeButtons = newExample.querySelectorAll('.remove-example');
            removeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    newExample.remove();
                });
            });
        });
        
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

<?php include __DIR__ . '/../includes/templates/footer.php'; ?>
