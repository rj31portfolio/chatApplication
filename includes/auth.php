<?php
/**
 * Authentication related functions
 */

require_once __DIR__ . '/db.php';

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if logged in user is super admin
 * 
 * @return bool
 */
function isSuperAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'super_admin';
}

/**
 * Check if logged in user is admin
 * 
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Authenticate user by username and password
 * 
 * @param string $username
 * @param string $password
 * @return array|false User data or false on failure
 */
function authenticateUser($username, $password) {
    $user = dbGetRow("SELECT id, username, password, email, role FROM users WHERE username = ?", [$username]);
    
    if ($user && password_verify($password, $user['password'])) {
        // Remove password from array before returning
        unset($user['password']);
        return $user;
    }
    
    return false;
}

/**
 * Create a login session for user
 * 
 * @param array $user User data
 */
function createLoginSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    
    // If admin, get associated business
    if ($user['role'] === 'admin') {
        $business = dbGetRow("SELECT id, name, business_type, api_key FROM businesses WHERE admin_id = ?", [$user['id']]);
        
        if ($business) {
            $_SESSION['business_id'] = $business['id'];
            $_SESSION['business_name'] = $business['name'];
            $_SESSION['business_type'] = $business['business_type'];
            $_SESSION['api_key'] = $business['api_key'];
        }
    }
}

/**
 * Logout current user
 */
function logout() {
    session_unset();
    session_destroy();
}

/**
 * Redirect if user is not logged in
 * 
 * @param string $redirect URL to redirect to
 */
function requireLogin($redirect = '/login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Redirect if user is not a super admin
 * 
 * @param string $redirect URL to redirect to
 */
function requireSuperAdmin($redirect = '/login.php') {
    requireLogin($redirect);
    
    if (!isSuperAdmin()) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Redirect if user is not an admin
 * 
 * @param string $redirect URL to redirect to
 */
function requireAdmin($redirect = '/login.php') {
    requireLogin($redirect);
    
    if (!isAdmin() && !isSuperAdmin()) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Create a new user
 * 
 * @param string $username
 * @param string $password
 * @param string $email
 * @param string $role
 * @return int|false User ID or false on failure
 */
function createUser($username, $password, $email, $role) {
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
    
    return dbExecute(
        "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)",
        [$username, $hashedPassword, $email, $role]
    );
}

/**
 * Generate a unique API key for a business
 * 
 * @return string API key
 */
function generateApiKey() {
    return bin2hex(random_bytes(32));
}

/**
 * Validate API key from request
 * 
 * @param string $apiKey
 * @return array|false Business data or false if invalid
 */
function validateApiKey($apiKey) {
    return dbGetRow(
        "SELECT id, name, business_type FROM businesses WHERE api_key = ?", 
        [$apiKey]
    );
}
