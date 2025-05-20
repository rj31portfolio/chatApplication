<?php
/**
 * Configuration settings for the AI Chatbot System
 */

// Session configuration
session_start();

// Database configuration
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', getenv('3306'));
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'ai_chatbot');
define('DB_TYPE', 'mysql'); // Using PostgreSQL instead of MySQL

// Application paths
define('BASE_URL', 'http://localhost/chatbot/');
define('API_URL', BASE_URL . 'api/');

// Chatbot configuration
define('DEFAULT_GREETING', 'Hello! How can I help you today?');
define('MAX_RESPONSE_TIME', 3); // seconds

// Business types
define('BUSINESS_TYPES', [
    'restaurant' => 'Restaurant',
    'ecommerce' => 'E-commerce',
    'service' => 'Service Provider',
    'healthcare' => 'Healthcare',
    'education' => 'Education',
    'finance' => 'Finance',
    'other' => 'Other'
]);

// Error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('UTC');

// Security settings
define('PASSWORD_HASH_COST', 12);
