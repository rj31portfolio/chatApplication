<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? h($pageTitle) . ' - ' : ''; ?>AI Chatbot System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    
    <?php if (isset($extraCSS)): ?>
        <?php echo $extraCSS; ?>
    <?php endif; ?>
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="<?php echo BASE_URL; ?>">
            AI Chatbot System
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" 
                data-bs-toggle="collapse" data-bs-target="#sidebarMenu" 
                aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <?php if (isLoggedIn()): ?>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="<?php echo BASE_URL; ?>login.php?logout=1">
                    <i class="fas fa-sign-out-alt"></i> Sign out
                </a>
            </div>
        </div>
        <?php endif; ?>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php 
            if (isLoggedIn()) {
                if (isSuperAdmin()) {
                    include __DIR__ . '/sidebar-super-admin.php';
                } else {
                    include __DIR__ . '/sidebar-admin.php';
                }
            }
            ?>
            
            <main class="<?php echo isLoggedIn() ? 'col-md-9 ms-sm-auto col-lg-10 px-md-4' : 'col-md-12 px-4'; ?>">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo isset($pageTitle) ? h($pageTitle) : 'Dashboard'; ?></h1>
                    
                    <?php if (isset($pageActions)): ?>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php echo $pageActions; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                <?php endif; ?>
