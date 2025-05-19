<?php
/**
 * Main entry point for the AI Chatbot System
 */

// Include required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-robot me-2"></i>
                AI Chatbot System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
                    <a href="<?php echo BASE_URL; ?>install_ai_chatbot.php" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="fas fa-cog"></i> Installation
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
                    <div class="col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Restaurant</span>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>E-commerce</span>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Service Provider</span>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Healthcare</span>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Education</span>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Finance</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> AI Chatbot System. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-decoration-none text-muted me-3">Privacy Policy</a>
                    <a href="#" class="text-decoration-none text-muted me-3">Terms of Service</a>
                    <a href="#" class="text-decoration-none text-muted">Contact</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>