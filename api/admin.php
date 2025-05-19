<?php
/**
 * Admin API endpoints for the AI Chatbot System
 * 
 * Handles AJAX requests from the admin panel
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';
require_once __DIR__ . '/../includes/helpers.php';

// Set content type to JSON
header('Content-Type: application/json');

// Verify admin authentication for API access
if (!isAdmin() && !isSuperAdmin()) {
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
        
    case 'add_response':
        addResponse();
        break;
        
    case 'delete_response':
        deleteResponse();
        break;
        
    case 'update_response':
        updateResponse();
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
    // Get business ID from session
    $businessId = $_SESSION['business_id'] ?? null;
    
    if (!$businessId) {
        echo json_encode([
            'success' => false,
            'message' => 'Business ID not found in session'
        ]);
        return;
    }
    
    // Get session ID from request
    $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
    
    if ($sessionId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid session ID'
        ]);
        return;
    }
    
    // Verify that the session belongs to this business
    $sessionExists = dbGetValue("
        SELECT COUNT(*) FROM chat_sessions 
        WHERE id = ? AND business_id = ?
    ", [$sessionId, $businessId]);
    
    if (!$sessionExists) {
        echo json_encode([
            'success' => false,
            'message' => 'Session not found or does not belong to your business'
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
 * Get analytics data for charts
 */
function getAnalyticsData() {
    // Get business ID from session
    $businessId = $_SESSION['business_id'] ?? null;
    
    if (!$businessId) {
        echo json_encode([
            'success' => false,
            'message' => 'Business ID not found in session'
        ]);
        return;
    }
    
    // Get date range from request
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Get daily message counts
    $messageActivity = dbSelect("
        SELECT DATE(s.started_at) as date, 
               SUM(CASE WHEN m.is_bot = 0 THEN 1 ELSE 0 END) as user_messages,
               SUM(CASE WHEN m.is_bot = 1 THEN 1 ELSE 0 END) as bot_messages
        FROM chat_sessions s
        JOIN messages m ON s.id = m.session_id
        WHERE s.business_id = ? AND DATE(s.started_at) BETWEEN ? AND ?
        GROUP BY DATE(s.started_at)
        ORDER BY date
    ", [$businessId, $startDate, $endDate]);
    
    // Get conversation lengths distribution
    $conversationLengths = dbSelect("
        SELECT 
            CASE 
                WHEN message_count <= 2 THEN 'Very Short (1-2)'
                WHEN message_count <= 5 THEN 'Short (3-5)'
                WHEN message_count <= 10 THEN 'Medium (6-10)'
                WHEN message_count <= 20 THEN 'Long (11-20)'
                ELSE 'Very Long (20+)'
            END as length_category,
            COUNT(*) as session_count
        FROM (
            SELECT s.id, COUNT(m.id) as message_count
            FROM chat_sessions s
            LEFT JOIN messages m ON s.id = m.session_id
            WHERE s.business_id = ? AND DATE(s.started_at) BETWEEN ? AND ?
            GROUP BY s.id
        ) as session_lengths
        GROUP BY length_category
        ORDER BY MIN(message_count)
    ", [$businessId, $startDate, $endDate]);
    
    // Format data for charts
    $chartData = [
        'messageActivity' => [],
        'conversationLengths' => [
            'labels' => [],
            'data' => []
        ]
    ];
    
    // Format message activity data
    foreach ($messageActivity as $day) {
        $formattedDate = (new DateTime($day['date']))->format('M j');
        $chartData['messageActivity'][] = [
            'date' => $formattedDate,
            'userMessages' => (int)$day['user_messages'],
            'botMessages' => (int)$day['bot_messages']
        ];
    }
    
    // Format conversation lengths data
    foreach ($conversationLengths as $length) {
        $chartData['conversationLengths']['labels'][] = $length['length_category'];
        $chartData['conversationLengths']['data'][] = (int)$length['session_count'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $chartData
    ]);
}

/**
 * Add a new response
 */
function addResponse() {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        return;
    }
    
    // Get business ID from session
    $businessId = $_SESSION['business_id'] ?? null;
    
    if (!$businessId) {
        echo json_encode([
            'success' => false,
            'message' => 'Business ID not found in session'
        ]);
        return;
    }
    
    // Get request body
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
    
    $intent = trim($data['intent'] ?? '');
    $pattern = trim($data['pattern'] ?? '');
    $response = trim($data['response'] ?? '');
    
    // Validate input
    if (empty($intent)) {
        echo json_encode([
            'success' => false,
            'message' => 'Intent is required'
        ]);
        return;
    }
    
    if (empty($pattern)) {
        echo json_encode([
            'success' => false,
            'message' => 'Pattern is required'
        ]);
        return;
    }
    
    if (empty($response)) {
        echo json_encode([
            'success' => false,
            'message' => 'Response is required'
        ]);
        return;
    }
    
    // Insert new response
    $result = dbExecute("
        INSERT INTO responses (business_id, intent, pattern, response)
        VALUES (?, ?, ?, ?)
    ", [$businessId, $intent, $pattern, $response]);
    
    if ($result !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Response added successfully',
            'response_id' => $result
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error adding response'
        ]);
    }
}

/**
 * Delete a response
 */
function deleteResponse() {
    // Get business ID from session
    $businessId = $_SESSION['business_id'] ?? null;
    
    if (!$businessId) {
        echo json_encode([
            'success' => false,
            'message' => 'Business ID not found in session'
        ]);
        return;
    }
    
    // Get response ID from request
    $responseId = isset($_GET['response_id']) ? (int)$_GET['response_id'] : 0;
    
    if ($responseId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid response ID'
        ]);
        return;
    }
    
    // Verify that the response belongs to this business
    $responseExists = dbGetValue("
        SELECT COUNT(*) FROM responses 
        WHERE id = ? AND business_id = ?
    ", [$responseId, $businessId]);
    
    if (!$responseExists) {
        echo json_encode([
            'success' => false,
            'message' => 'Response not found or does not belong to your business'
        ]);
        return;
    }
    
    // Delete the response
    $result = dbExecute("
        DELETE FROM responses 
        WHERE id = ? AND business_id = ?
    ", [$responseId, $businessId]);
    
    if ($result !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Response deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting response'
        ]);
    }
}

/**
 * Update an existing response
 */
function updateResponse() {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        return;
    }
    
    // Get business ID from session
    $businessId = $_SESSION['business_id'] ?? null;
    
    if (!$businessId) {
        echo json_encode([
            'success' => false,
            'message' => 'Business ID not found in session'
        ]);
        return;
    }
    
    // Get request body
    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);
    
    $responseId = (int)($data['response_id'] ?? 0);
    $intent = trim($data['intent'] ?? '');
    $pattern = trim($data['pattern'] ?? '');
    $response = trim($data['response'] ?? '');
    
    // Validate input
    if ($responseId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid response ID'
        ]);
        return;
    }
    
    if (empty($intent)) {
        echo json_encode([
            'success' => false,
            'message' => 'Intent is required'
        ]);
        return;
    }
    
    if (empty($pattern)) {
        echo json_encode([
            'success' => false,
            'message' => 'Pattern is required'
        ]);
        return;
    }
    
    if (empty($response)) {
        echo json_encode([
            'success' => false,
            'message' => 'Response is required'
        ]);
        return;
    }
    
    // Verify that the response belongs to this business
    $responseExists = dbGetValue("
        SELECT COUNT(*) FROM responses 
        WHERE id = ? AND business_id = ?
    ", [$responseId, $businessId]);
    
    if (!$responseExists) {
        echo json_encode([
            'success' => false,
            'message' => 'Response not found or does not belong to your business'
        ]);
        return;
    }
    
    // Update the response
    $result = dbExecute("
        UPDATE responses 
        SET intent = ?, pattern = ?, response = ? 
        WHERE id = ? AND business_id = ?
    ", [$intent, $pattern, $response, $responseId, $businessId]);
    
    if ($result !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Response updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating response'
        ]);
    }
}
