<?php
/**
 * Authentication API endpoints for the AI Chatbot System
 * 
 * Handles login, logout, and session validation for AJAX requests.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get request type from URL param
$action = $_GET['action'] ?? 'validate';

// Process the requested action
switch ($action) {
    case 'login':
        handleLogin();
        break;
        
    case 'logout':
        handleLogout();
        break;
        
    case 'validate':
    default:
        validateSession();
        break;
}

/**
 * Handle login requests
 */
function handleLogin() {
    // Only accept POST requests for login
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }
    
    // Get request body
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
    
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Username and password are required'
        ]);
        exit;
    }
    
    $user = authenticateUser($username, $password);
    
    if ($user) {
        createLoginSession($user);
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'redirect' => $user['role'] === 'super_admin' 
                ? BASE_URL . 'super-admin/index.php' 
                : BASE_URL . 'admin/index.php'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password'
        ]);
    }
}

/**
 * Handle logout requests
 */
function handleLogout() {
    logout();
    
    echo json_encode([
        'success' => true,
        'redirect' => BASE_URL . 'login.php'
    ]);
}

/**
 * Validate the current session
 */
function validateSession() {
    if (isLoggedIn()) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Not authenticated'
        ]);
    }
}
