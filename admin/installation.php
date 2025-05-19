<?php
/**
 * Admin - Widget Installation Code
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

// Get business API key
$apiKey = dbGetValue("SELECT api_key FROM businesses WHERE id = ?", [$businessId]);

if (!$apiKey) {
    $_SESSION['message'] = 'Could not retrieve API key. Please contact a super admin.';
    $_SESSION['message_type'] = 'danger';
    header('Location: ' . BASE_URL . 'admin/index.php');
    exit;
}

// Generate embedding code
$embedCode = getEmbeddingCode($apiKey);

$pageTitle = 'Widget Installation';
$extraJS = '<script src="' . BASE_URL . 'assets/js/admin.js"></script>';
?>

<?php include __DIR__ . '/../includes/templates/header.php'; ?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-code"></i> Widget Installation
            </div>
            <div class="card-body">
                <p>
                    To add the chatbot to your website, copy the code below and paste it into your website's HTML,
                    just before the closing <code>&lt;/body&gt;</code> tag.
                </p>
                
                <div class="position-relative">
                    <pre class="code-embed bg-light p-3 rounded">
<code><?php echo h($embedCode); ?></code>
                    </pre>
                    <button class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2 copy-btn" 
                            data-clipboard="<?php echo h($embedCode); ?>">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                
                <h4 class="mt-4">Installation Instructions</h4>
                <ol>
                    <li>Copy the code snippet above</li>
                    <li>Log in to your website's content management system</li>
                    <li>Access the template or theme editor</li>
                    <li>Find the main template file (often <code>footer.php</code>, <code>default.php</code>, or <code>theme.html</code>)</li>
                    <li>Paste the code just before the closing <code>&lt;/body&gt;</code> tag</li>
                    <li>Save the changes and refresh your website</li>
                </ol>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i> The chatbot will appear as a chat button in the bottom-right corner of your website.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-puzzle-piece"></i> Widget Customization (Optional)
            </div>
            <div class="card-body">
                <p>
                    You can customize the appearance and behavior of the chat widget by adding the following code
                    before the initialization:
                </p>
                
                <div class="position-relative">
                    <pre class="code-embed bg-light p-3 rounded">
<code>&lt;script&gt;
    // Add this before the widget script
    window.chatbotSettings = {
        buttonColor: '#0d6efd',    // Chat button color
        headerColor: '#0d6efd',    // Chat window header color
        title: 'Chat Support',     // Chat window title
        greeting: 'Hello! How can I help you today?'
    };
&lt;/script&gt;</code>
                    </pre>
                    <button class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2 copy-btn" 
                            data-clipboard="<script>
    // Add this before the widget script
    window.chatbotSettings = {
        buttonColor: '#0d6efd',    // Chat button color
        headerColor: '#0d6efd',    // Chat window header color
        title: 'Chat Support',     // Chat window title
        greeting: 'Hello! How can I help you today?'
    };
</script>">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-laptop-code"></i> Advanced Integration
            </div>
            <div class="card-body">
                <p>
                    The widget provides a JavaScript API for programmatic control:
                </p>
                
                <div class="position-relative">
                    <pre class="code-embed bg-light p-3 rounded">
<code>
// Open the chat window
aiChat('open');

// Close the chat window
aiChat('close');

// Send a message programmatically
aiChat('sendMessage', 'Hello, I need help with an order');

// Listen for events
window.addEventListener('aichat.ready', function() {
    console.log('Chat widget is ready');
});

window.addEventListener('aichat.messageReceived', function(e) {
    console.log('Message received:', e.detail);
});</code>
                    </pre>
                    <button class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2 copy-btn" 
                            data-clipboard="// Open the chat window
aiChat('open');

// Close the chat window
aiChat('close');

// Send a message programmatically
aiChat('sendMessage', 'Hello, I need help with an order');

// Listen for events
window.addEventListener('aichat.ready', function() {
    console.log('Chat widget is ready');
});

window.addEventListener('aichat.messageReceived', function(e) {
    console.log('Message received:', e.detail);
});">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle"></i> Advanced integration options should only be used by developers familiar with JavaScript.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-question-circle"></i> Testing Your Integration
            </div>
            <div class="card-body">
                <p>
                    After adding the chat widget to your website, you should test it to ensure it's working correctly:
                </p>
                
                <ol>
                    <li>Visit your website in an incognito/private browser window</li>
                    <li>Verify that the chat button appears in the bottom-right corner</li>
                    <li>Click the button to open the chat window</li>
                    <li>Test the chatbot by sending a few messages</li>
                    <li>Check that the chatbot responds appropriately</li>
                    <li>Test on different devices (desktop, tablet, mobile)</li>
                </ol>
                
                <p>
                    If you encounter any issues, please check:
                </p>
                
                <ul>
                    <li>That the code is correctly placed before the closing <code>&lt;/body&gt;</code> tag</li>
                    <li>That your website allows third-party scripts (check for Content Security Policy restrictions)</li>
                    <li>That your internet connection is stable</li>
                </ul>
                
                <p>
                    If you continue to experience problems, please contact support.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/templates/footer.php'; ?>
