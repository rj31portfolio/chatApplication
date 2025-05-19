<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo getActiveClass('index.php'); ?>" href="<?php echo BASE_URL; ?>admin/index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo getActiveClass('responses.php'); ?>" href="<?php echo BASE_URL; ?>admin/responses.php">
                    <i class="fas fa-comments"></i>
                    Manage Responses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo getActiveClass('training.php'); ?>" href="<?php echo BASE_URL; ?>admin/training.php">
                    <i class="fas fa-brain"></i>
                    Train Chatbot
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo getActiveClass('analytics.php'); ?>" href="<?php echo BASE_URL; ?>admin/analytics.php">
                    <i class="fas fa-chart-bar"></i>
                    Analytics
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Business Settings</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo getActiveClass('settings.php'); ?>" href="<?php echo BASE_URL; ?>admin/settings.php">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo getActiveClass('installation.php'); ?>" href="<?php echo BASE_URL; ?>admin/installation.php">
                    <i class="fas fa-code"></i>
                    Installation Code
                </a>
            </li>
        </ul>
        
        <?php if (isset($_SESSION['business_name'])): ?>
        <div class="px-3 mt-4 mb-3 text-muted">
            <small>
                <strong>Business:</strong> <?php echo h($_SESSION['business_name']); ?><br>
                <strong>Type:</strong> <?php echo h(BUSINESS_TYPES[$_SESSION['business_type']] ?? $_SESSION['business_type']); ?>
            </small>
        </div>
        <?php endif; ?>
    </div>
</nav>
