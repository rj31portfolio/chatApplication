<?php
/**
 * Chat API endpoint for the AI Chatbot Widget
 * 
 * This file handles incoming messages from the chat widget and returns AI-generated responses.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';
require_once __DIR__ . '/../includes/helpers.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Validate API key
if (!isset($data['api_key'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'API key is missing'
    ]);
    exit;
}

$apiKey = $data['api_key'];
$business = validateApiKey($apiKey);

if (!$business) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid API key'
    ]);
    exit;
}

// Extract message and session ID
$message = $data['message'] ?? '';
$sessionId = $data['session_id'] ?? '';

if (empty($message)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Message is required'
    ]);
    exit;
}

if (empty($sessionId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Session ID is required'
    ]);
    exit;
}

// Get or create chat session
$dbSessionId = getOrCreateChatSession($sessionId, $business['id']);

// Save user message
saveMessage($dbSessionId, $message, false);

// Generate AI response
$response = generateResponse($message, $business['id'], $business['business_type'], $sessionId);

// Save bot response
saveMessage($dbSessionId, $response, true);

// Return response
echo json_encode([
    'success' => true,
    'response' => $response,
    'session_id' => $sessionId
]);
