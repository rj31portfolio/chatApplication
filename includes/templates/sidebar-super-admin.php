<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo getActiveClass('index.php'); ?>" href="<?php echo BASE_URL; ?>super-admin/index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo getActiveClass('businesses.php'); ?>" href="<?php echo BASE_URL; ?>super-admin/businesses.php">
                    <i class="fas fa-building"></i>
                    Manage Businesses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo getActiveClass('analytics.php'); ?>" href="<?php echo BASE_URL; ?>super-admin/analytics.php">
                    <i class="fas fa-chart-bar"></i>
                    System Analytics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo getActiveClass('users.php'); ?>" href="<?php echo BASE_URL; ?>super-admin/users.php">
                    <i class="fas fa-users"></i>
                    Manage Users
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>System Settings</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo getActiveClass('settings.php'); ?>" href="<?php echo BASE_URL; ?>super-admin/settings.php">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo getActiveClass('logs.php'); ?>" href="<?php echo BASE_URL; ?>super-admin/logs.php">
                    <i class="fas fa-clipboard-list"></i>
                    System Logs
                </a>
            </li>
        </ul>
        
        <div class="px-3 mt-4 mb-3 text-muted">
            <small>
                <strong>Role:</strong> Super Administrator
            </small>
        </div>
    </div>
</nav>
