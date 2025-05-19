<?php
/**
 * Super Admin API endpoints for the AI Chatbot System
 * 
 * Handles AJAX requests from the super admin panel
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';
require_once __DIR__ . '/../includes/helpers.php';

// Set content type to JSON
header('Content-Type: application/json');

// Verify super admin authentication for API access
if (!isSuperAdmin()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get the requested action
$action = $_GET['action'] ?? '';

// Process the requested action
switch ($action) {
    case 'get_session_messages':
        getSessionMessages();
        break;
        
    case 'get_analytics_data':
        getAnalyticsData();
        break;
        
    case 'add_business':
        addBusiness();
        break;
        
    case 'delete_business':
        deleteBusiness();
        break;
        
    case 'update_business':
        updateBusiness();
        break;
        
    case 'add_user':
        addUser();
        break;
        
    case 'delete_user':
        deleteUser();
        break;
        
    case 'update_user':
        updateUser();
        break;
        
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified'
        ]);
        break;
}

/**
 * Get messages for a specific chat session
 */
function getSessionMessages() {
    // Get session ID from request
    $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
    
    if ($sessionId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid session ID'
        ]);
        return;
    }
    
    // Get messages for this session
    $messages = dbSelect("
        SELECT m.message, m.is_bot, m.created_at
        FROM messages m
        WHERE m.session_id = ?
        ORDER BY m.created_at ASC
    ", [$sessionId]);
    
    // Format dates for display
    foreach ($messages as &$msg) {
        $msg['created_at'] = formatDateTime($msg['created_at']);
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
}

/**
 * Get analytics data for super admin dashboard
 */
function getAnalyticsData() {
    // Get date range from request
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Get overall statistics
    $totalBusinesses = dbGetValue("SELECT COUNT(*) FROM businesses");
    $totalUsers = dbGetValue("SELECT COUNT(*) FROM users");
    $totalSessions = dbGetValue("
        SELECT COUNT(*) FROM chat_sessions
        WHERE DATE(started_at) BETWEEN ? AND ?
    ", [$startDate, $endDate]);
    $totalMessages = dbGetValue("
        SELECT COUNT(*) FROM messages m
        JOIN chat_sessions s ON m.session_id = s.id
        WHERE DATE(s.started_at) BETWEEN ? AND ?
    ", [$startDate, $endDate]);
    
    // Get business growth by month
    $businessGrowth = dbSelect("
        SELECT DATE_FORMAT(created_at, '%Y-%m-01') as month, COUNT(*) as count
        FROM businesses
        GROUP BY month
        ORDER BY month ASC
        LIMIT 12
    ");
    
    // Get message activity by day
    $messageActivity = dbSelect("
        SELECT DATE(s.started_at) as date, COUNT(*) as count
        FROM messages m
        JOIN chat_sessions s ON m.session_id = s.id
        WHERE DATE(s.started_at) BETWEEN ? AND ?
        GROUP BY date
        ORDER BY date ASC
    ", [$startDate, $endDate]);
    
    // Get business type distribution
    $businessTypes = dbSelect("
        SELECT business_type, COUNT(*) as count
        FROM businesses
        GROUP BY business_type
        ORDER BY count DESC
    ");
    
    // Format data for charts
    $chartData = [
        'statistics' => [
            'totalBusinesses' => (int)$totalBusinesses,
            'totalUsers' => (int)$totalUsers,
            'totalSessions' => (int)$totalSessions,
            'totalMessages' => (int)$totalMessages
        ],
        'businessGrowth' => [
            'labels' => [],
            'data' => []
        ],
        'messageActivity' => [
            'labels' => [],
            'data' => []
        ],
        'businessTypes' => [
            'labels' => [],
            'data' => []
        ]
    ];
    
    // Format business growth data
    foreach ($businessGrowth as $month) {
        $chartData['businessGrowth']['labels'][] = date('M Y', strtotime($month['month']));
        $chartData['businessGrowth']['data'][] = (int)$month['count'];
    }
    
    // Format message activity data
    foreach ($messageActivity as $day) {
        $chartData['messageActivity']['labels'][] = date('M j', strtotime($day['date']));
        $chartData['messageActivity']['data'][] = (int)$day['count'];
    }
    
    // Format business type data
    foreach ($businessTypes as $type) {
        $label = BUSINESS_TYPES[$type['business_type']] ?? $type['business_type'];
        $chartData['businessTypes']['labels'][] = $label;
        $chartData['businessTypes']['data'][] = (int)$type['count'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $chartData
    ]);
}

/**
 * Add a new business
 */
function addBusiness() {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        return;
    }
    
    // Get request body
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
    
    $name = trim($data['name'] ?? '');
    $businessType = trim($data['business_type'] ?? '');
    $adminId = !empty($data['admin_id']) ? (int)$data['admin_id'] : null;
    
    // Validate input
    if (empty($name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Business name is required'
        ]);
        return;
    }
    
    if (empty($businessType)) {
        echo json_encode([
            'success' => false,
            'message' => 'Business type is required'
        ]);
        return;
    }
    
    // Validate business type
    if (!array_key_exists($businessType, BUSINESS_TYPES)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid business type'
        ]);
        return;
    }
    
    // Validate admin ID if provided
    if ($adminId !== null) {
        $adminExists = dbGetValue("
            SELECT COUNT(*) FROM users 
            WHERE id = ? AND role = 'admin'
        ", [$adminId]);
        
        if (!$adminExists) {
            echo json_encode([
                'success' => false,
                'message' => 'Admin user not found'
            ]);
            return;
        }
    }
    
    // Generate API key
    $apiKey = generateApiKey();
    
    // Insert new business
    $result = dbExecute("
        INSERT INTO businesses (name, business_type, api_key, admin_id)
        VALUES (?, ?, ?, ?)
    ", [$name, $businessType, $apiKey, $adminId]);
    
    if ($result !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Business added successfully',
            'business_id' => $result,
            'api_key' => $apiKey
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error adding business'
        ]);
    }
}

/**
 * Delete a business
 */
function deleteBusiness() {
    // Get business ID from request
    $businessId = isset($_GET['business_id']) ? (int)$_GET['business_id'] : 0;
    
    if ($businessId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid business ID'
        ]);
        return;
    }
    
    // Check if business exists
    $businessExists = dbGetValue("
        SELECT COUNT(*) FROM businesses WHERE id = ?
    ", [$businessId]);
    
    if (!$businessExists) {
        echo json_encode([
            'success' => false,
            'message' => 'Business not found'
        ]);
        return;
    }
    
    // Delete the business
    $result = dbExecute("DELETE FROM businesses WHERE id = ?", [$businessId]);
    
    if ($result !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Business deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting business'
        ]);
    }
}

/**
 * Update an existing business
 */
function updateBusiness() {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        return;
    }
    
    // Get request body
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
    
    $businessId = (int)($data['business_id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $businessType = trim($data['business_type'] ?? '');
    $adminId = isset($data['admin_id']) && $data['admin_id'] !== '' ? (int)$data['admin_id'] : null;
    
    // Validate input
    if ($businessId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid business ID'
        ]);
        return;
    }
    
    if (empty($name)) {
        echo json_encode([
            'success' => false,
            'message' => 'Business name is required'
        ]);
        return;
    }
    
    if (empty($businessType)) {
        echo json_encode([
            'success' => false,
            'message' => 'Business type is required'
        ]);
        return;
    }
    
    // Check if business exists
    $businessExists = dbGetValue("
        SELECT COUNT(*) FROM businesses WHERE id = ?
    ", [$businessId]);
    
    if (!$businessExists) {
        echo json_encode([
            'success' => false,
            'message' => 'Business not found'
        ]);
        return;
    }
    
    // Validate admin ID if provided
    if ($adminId !== null) {
        $adminExists = dbGetValue("
            SELECT COUNT(*) FROM users 
            WHERE id = ? AND role = 'admin'
        ", [$adminId]);
        
        if (!$adminExists) {
            echo json_encode([
                'success' => false,
                'message' => 'Admin user not found'
            ]);
            return;
        }
    }
    
    // Update the business
    $result = dbExecute("
        UPDATE businesses 
        SET name = ?, business_type = ?, admin_id = ? 
        WHERE id = ?
    ", [$name, $businessType, $adminId, $businessId]);
    
    if ($result !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Business updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating business'
        ]);
    }
}

/**
 * Add a new user
 */
function addUser() {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        return;
    }
    
    // Get request body
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
    
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');
    $role = trim($data['role'] ?? '');
    
    // Validate input
    if (empty($username)) {
        echo json_encode([
            'success' => false,
            'message' => 'Username is required'
        ]);
        return;
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Valid email is required'
        ]);
        return;
    }
    
    if (empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Password is required'
        ]);
        return;
    }
    
    if (empty($role) || !in_array($role, ['admin', 'super_admin'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Valid role is required'
        ]);
        return;
    }
    
    // Check if username or email already exists
    $exists = dbGetValue("
        SELECT COUNT(*) FROM users 
        WHERE username = ? OR email = ?
    ", [$username, $email]);
    
    if ($exists) {
        echo json_encode([
            'success' => false,
            'message' => 'Username or email already exists'
        ]);
        return;
    }
    
    // Create new user
    $userId = createUser($username, $password, $email, $role);
    
    if ($userId !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user_id' => $userId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error creating user'
        ]);
    }
}

/**
 * Delete a user
 */
function deleteUser() {
    // Get user ID from request
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    if ($userId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid user ID'
        ]);
        return;
    }
    
    // Prevent self-deletion
    if ($userId == $_SESSION['user_id']) {
        echo json_encode([
            'success' => false,
            'message' => 'You cannot delete your own account'
        ]);
        return;
    }
    
    // Check if user exists
    $userExists = dbGetValue("SELECT COUNT(*) FROM users WHERE id = ?", [$userId]);
    
    if (!$userExists) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        return;
    }
    
    // Check if user is a business admin
    $hasBusinesses = dbGetValue("SELECT COUNT(*) FROM businesses WHERE admin_id = ?", [$userId]);
    
    if ($hasBusinesses) {
        // Update businesses to remove association
        dbExecute("UPDATE businesses SET admin_id = NULL WHERE admin_id = ?", [$userId]);
    }
    
    // Delete the user
    $result = dbExecute("DELETE FROM users WHERE id = ?", [$userId]);
    
    if ($result !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting user'
        ]);
    }
}

/**
 * Update an existing user
 */
function updateUser() {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        return;
    }
    
    // Get request body
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
    
    $userId = (int)($data['user_id'] ?? 0);
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $role = trim($data['role'] ?? '');
    $password = isset($data['password']) ? trim($data['password']) : null;
    
    // Validate input
    if ($userId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid user ID'
        ]);
        return;
    }
    
    if (empty($username)) {
        echo json_encode([
            'success' => false,
            'message' => 'Username is required'
        ]);
        return;
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Valid email is required'
        ]);
        return;
    }
    
    if (empty($role) || !in_array($role, ['admin', 'super_admin'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Valid role is required'
        ]);
        return;
    }
    
    // Check if user exists
    $userExists = dbGetValue("SELECT COUNT(*) FROM users WHERE id = ?", [$userId]);
    
    if (!$userExists) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        return;
    }
    
    // Check if username or email is already taken by another user
    $exists = dbGetValue("
        SELECT COUNT(*) FROM users 
        WHERE (username = ? OR email = ?) AND id != ?
    ", [$username, $email, $userId]);
    
    if ($exists) {
        echo json_encode([
            'success' => false,
            'message' => 'Username or email already in use'
        ]);
        return;
    }
    
    // Update the user
    if ($password !== null && !empty($password)) {
        // Update with password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
        $result = dbExecute("
            UPDATE users 
            SET username = ?, email = ?, role = ?, password = ? 
            WHERE id = ?
        ", [$username, $email, $role, $hashedPassword, $userId]);
    } else {
        // Update without password
        $result = dbExecute("
            UPDATE users 
            SET username = ?, email = ?, role = ? 
            WHERE id = ?
        ", [$username, $email, $role, $userId]);
    }
    
    if ($result !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating user'
        ]);
    }
}
