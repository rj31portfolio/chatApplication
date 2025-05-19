 
<?php
/**
 * Main entry point for the AI Chatbot System
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Create database tables if they don't exist
createDatabaseTables();

// If user is logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isSuperAdmin()) {
        header('Location: ' . BASE_URL . 'super-admin/index.php');
        exit;
    } else {
        header('Location: ' . BASE_URL . 'admin/index.php');
        exit;
    }
}

$pageTitle = 'AI Chatbot System';
?>

<?php include __DIR__ . '/includes/templates/header.php'; ?>

<div class="container">
    <div class="row mt-5">
        <div class="col-lg-8 mx-auto text-center">
            <h1 class="display-4">AI Chatbot System</h1>
            <p class="lead">A smart, embeddable AI chatbot for your business website</p>
            <hr class="my-4">
            <p>
                Enhance customer support with AI-powered auto-replies tailored to your business needs.
                Easily embed the chatbot on your website with a simple code snippet.
            </p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary btn-lg px-4 gap-3">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-robot fa-3x mb-3 text-primary"></i>
                    <h3 class="card-title">Smart Auto-Replies</h3>
                    <p class="card-text">AI-powered responses tailored to your business type for better customer engagement.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-code fa-3x mb-3 text-primary"></i>
                    <h3 class="card-title">Easy Integration</h3>
                    <p class="card-text">Embed the chatbot on your website with a simple JavaScript snippet.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-3x mb-3 text-primary"></i>
                    <h3 class="card-title">Powerful Analytics</h3>
                    <p class="card-text">Gain insights from conversation logs and user interaction metrics.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5 mb-5">
        <div class="col-md-6">
            <h2>How It Works</h2>
            <ol class="mt-4">
                <li>Sign up for an account</li>
                <li>Configure your chatbot settings and responses</li>
                <li>Copy the embedding code to your website</li>
                <li>Start engaging with your visitors automatically</li>
                <li>Monitor conversations and analytics to improve responses</li>
            </ol>
        </div>
        <div class="col-md-6">
            <h2>Business Types Supported</h2>
            <div class="row mt-4">
                <?php foreach (BUSINESS_TYPES as $type => $label): ?>
                <div class="col-6 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <span><?php echo h($label); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/templates/footer.php'; ?>
