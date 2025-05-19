<?php
/**
 * AI logic for the chatbot
 */

require_once __DIR__ . '/db.php';

/**
 * Generate a response based on user input
 * 
 * @param string $message User message
 * @param int $businessId Business ID
 * @param string $businessType Business type
 * @param string $sessionId Session ID
 * @return string Generated response
 */
function generateResponse($message, $businessId, $businessType, $sessionId) {
    // Trim and lowercase the message for processing
    $message = trim(strtolower($message));
    
    // Check for custom responses based on intents
    $customResponse = getCustomResponse($message, $businessId);
    if ($customResponse) {
        return $customResponse;
    }
    
    // If no custom response, use the template response based on intent
    $intent = detectIntent($message);
    $templateResponse = getTemplateResponse($intent, $businessType);
    
    return $templateResponse;
}

/**
 * Get custom response configured by the business
 * 
 * @param string $message User message
 * @param int $businessId Business ID
 * @return string|null Response or null if not found
 */
function getCustomResponse($message, $businessId) {
    $responses = dbSelect(
        "SELECT pattern, response FROM responses WHERE business_id = ?",
        [$businessId]
    );
    
    if (!$responses) {
        return null;
    }
    
    foreach ($responses as $resp) {
        // Simple pattern matching - can be enhanced with regex
        $pattern = strtolower($resp['pattern']);
        $keywords = explode(',', $pattern);
        
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (!empty($keyword) && strpos($message, $keyword) !== false) {
                return $resp['response'];
            }
        }
    }
    
    return null;
}

/**
 * Detect the intent of a user message
 * 
 * @param string $message User message
 * @return string Detected intent
 */
function detectIntent($message) {
    // Define intent patterns - basic keyword matching
    $intents = [
        'greeting' => ['hello', 'hi', 'hey', 'greetings', 'good morning', 'good afternoon', 'good evening'],
        'goodbye' => ['bye', 'goodbye', 'see you', 'later', 'good night', 'thanks bye'],
        'hours' => ['hours', 'open', 'close', 'opening', 'closing', 'schedule', 'when are you open'],
        'location' => ['where', 'location', 'address', 'directions', 'find you', 'get there'],
        'products' => ['products', 'items', 'sell', 'offer', 'catalog', 'menu'],
        'price' => ['price', 'cost', 'how much', 'pricing', 'fee', 'rates'],
        'booking' => ['book', 'reserve', 'appointment', 'reservation', 'schedule'],
        'contact' => ['contact', 'email', 'phone', 'call', 'reach'],
        'help' => ['help', 'support', 'assistance', 'guide', 'confused', 'problem'],
        'thanks' => ['thank', 'thanks', 'appreciate', 'grateful'],
    ];
    
    foreach ($intents as $intent => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return $intent;
            }
        }
    }
    
    // Default intent if nothing matches
    return 'unknown';
}

/**
 * Get template response based on intent and business type
 * 
 * @param string $intent Detected intent
 * @param string $businessType Type of business
 * @return string Response
 */
function getTemplateResponse($intent, $businessType) {
    // Template responses by business type and intent
    $templates = [
        'restaurant' => [
            'greeting' => 'Hello! Welcome to our restaurant. How can I assist you today? Would you like to see our menu or make a reservation?',
            'goodbye' => 'Thank you for chatting with us! We hope to serve you delicious food soon. Have a great day!',
            'hours' => 'Our restaurant is open Monday to Friday from 11 AM to 10 PM, and weekends from 10 AM to 11 PM.',
            'location' => 'We\'re located at 123 Main Street. You can find directions on our Contact page.',
            'products' => 'Our menu features a variety of dishes including appetizers, main courses, desserts, and beverages. Would you like me to recommend something?',
            'price' => 'Our menu prices range from $10 for appetizers to $30 for specialty entrees. We also offer lunch specials from $15.',
            'booking' => 'I\'d be happy to help you make a reservation. Please provide your preferred date, time, and party size, or visit our website for online reservations.',
            'contact' => 'You can reach us at (555) 123-4567 or email us at info@restaurant.com.',
            'help' => 'I can help with menu questions, reservations, hours, location, or special events. What would you like to know?',
            'thanks' => 'You\'re welcome! It\'s our pleasure to assist you.',
            'unknown' => 'I\'m not sure I understand. Would you like to know about our menu, make a reservation, or check our hours?'
        ],
        'ecommerce' => [
            'greeting' => 'Welcome to our online store! How can I help you today? Looking for a specific product?',
            'goodbye' => 'Thank you for shopping with us! If you need anything else, don\'t hesitate to reach out.',
            'hours' => 'Our online store is available 24/7. Customer service is available Monday to Friday from 9 AM to 6 PM.',
            'location' => 'We\'re an online store, but our headquarters is located at 456 Commerce Ave. We ship worldwide!',
            'products' => 'We offer a wide range of products including electronics, clothing, home goods, and more. What are you shopping for today?',
            'price' => 'Our products vary in price depending on the category. We offer free shipping on orders over $50.',
            'booking' => 'We don\'t take bookings, but you can place an order through our website anytime.',
            'contact' => 'Our customer service team is available at support@ecommerce.com or call us at (555) 987-6543.',
            'help' => 'I can help you find products, check order status, understand our shipping policies, or connect you with customer service. What do you need?',
            'thanks' => 'You\'re welcome! Happy shopping!',
            'unknown' => 'I\'m not sure what you\'re looking for. Can I help you find a product or answer questions about shipping or returns?'
        ],
        'service' => [
            'greeting' => 'Hello! Welcome to our service. How can I assist you today?',
            'goodbye' => 'Thank you for reaching out! If you need our services in the future, we\'re just a message away.',
            'hours' => 'Our service hours are Monday to Friday, 8 AM to 7 PM. Weekend appointments are available upon request.',
            'location' => 'We\'re located at 789 Service Road. We also offer on-site services depending on your location.',
            'products' => 'We offer various services including consultation, installation, maintenance, and training. Which service are you interested in?',
            'price' => 'Our service rates start at $75 per hour. We also offer package deals for ongoing services.',
            'booking' => 'I can help you schedule an appointment. Please provide your preferred date and time, and I\'ll check our availability.',
            'contact' => 'You can contact our service team at info@service.com or call (555) 765-4321.',
            'help' => 'I can assist with scheduling, service information, pricing, or general inquiries. What would you like to know?',
            'thanks' => 'You\'re welcome! We\'re here to help whenever you need our services.',
            'unknown' => 'I\'m not sure I understand your needs. Would you like information about our services or to schedule an appointment?'
        ],
        'healthcare' => [
            'greeting' => 'Welcome to our healthcare service. How can I assist you with your health needs today?',
            'goodbye' => 'Take care and stay healthy! Don\'t hesitate to reach out if you have more health questions.',
            'hours' => 'Our clinic is open Monday to Friday from 8 AM to 8 PM, and Saturday from 9 AM to 5 PM.',
            'location' => 'Our healthcare facility is located at 321 Health Avenue. We have wheelchair access and reserved parking.',
            'products' => 'We offer various healthcare services including general consultations, specialized care, preventive medicine, and diagnostics.',
            'price' => 'Consultation fees start at $100. Many insurance plans are accepted. Please contact us for specific pricing.',
            'booking' => 'I can help you schedule an appointment with one of our healthcare providers. What type of care are you seeking?',
            'contact' => 'For medical questions or appointments, please call (555) 432-1098 or email health@healthcare.com.',
            'help' => 'I can provide information about our services, help schedule appointments, or answer general health questions. How can I assist you?',
            'thanks' => 'You\'re welcome! Your health is our priority.',
            'unknown' => 'I\'m not sure I understand your health concern. Would you like to schedule an appointment or learn about our services?'
        ],
        'education' => [
            'greeting' => 'Welcome to our educational platform! How can I help you with your learning journey today?',
            'goodbye' => 'Thank you for chatting with us! Happy learning and feel free to return if you have more questions.',
            'hours' => 'Our administrative offices are open Monday to Friday from 9 AM to 5 PM. Online resources are available 24/7.',
            'location' => 'Our campus is located at 654 Learning Lane. Virtual learning options are also available.',
            'products' => 'We offer various courses and programs including certificate programs, degree courses, and professional development.',
            'price' => 'Course fees vary by program. We offer financial aid and payment plans. Contact us for specific course pricing.',
            'booking' => 'Would you like to schedule a tour, an advisor meeting, or register for a course? I can help you with that.',
            'contact' => 'For enrollment questions, contact admissions@education.com or call (555) 321-0987.',
            'help' => 'I can provide information about courses, enrollment processes, schedules, or connect you with an academic advisor. What do you need?',
            'thanks' => 'You\'re welcome! We\'re here to support your educational goals.',
            'unknown' => 'I\'m not sure I understand your question. Would you like information about our programs or enrollment assistance?'
        ],
        'finance' => [
            'greeting' => 'Welcome to our financial services. How can I assist you with your financial needs today?',
            'goodbye' => 'Thank you for discussing your financial matters with us. We\'re here to help whenever you need financial guidance.',
            'hours' => 'Our financial advisors are available Monday to Friday from 9 AM to 6 PM. Online banking is available 24/7.',
            'location' => 'Our main branch is located at 987 Finance Street. We also have digital services available online.',
            'products' => 'We offer various financial services including personal banking, investments, loans, insurance, and retirement planning.',
            'price' => 'Our service fees vary based on the account type and services used. Please contact us for specific fee information.',
            'booking' => 'Would you like to schedule a consultation with a financial advisor? I can check their availability for you.',
            'contact' => 'For financial inquiries, please contact support@finance.com or call (555) 210-9876.',
            'help' => 'I can provide information about our financial products, services, or help you connect with a financial advisor. What can I assist with?',
            'thanks' => 'You\'re welcome! We\'re dedicated to helping you achieve your financial goals.',
            'unknown' => 'I\'m not sure I understand your financial question. Would you like information about our services or to speak with an advisor?'
        ],
        'other' => [
            'greeting' => 'Hello! Welcome to our business. How can I assist you today?',
            'goodbye' => 'Thank you for chatting with us! Have a wonderful day!',
            'hours' => 'Our business hours are Monday to Friday from 9 AM to 6 PM.',
            'location' => 'We\'re located at 123 Business Avenue. Feel free to visit us!',
            'products' => 'We offer a variety of products and services. Could you specify what you\'re looking for?',
            'price' => 'Our prices vary based on specific products and services. Please let me know what you\'re interested in for pricing details.',
            'booking' => 'I\'d be happy to help you make a booking. What date and time works best for you?',
            'contact' => 'You can reach us at info@business.com or call (555) 123-4567.',
            'help' => 'I\'m here to help! Please let me know what you need assistance with.',
            'thanks' => 'You\'re welcome! It\'s our pleasure to assist you.',
            'unknown' => 'I\'m not sure I understand. Could you please rephrase your question or let me know how I can help you?'
        ]
    ];
    
    // Default to 'other' if business type doesn't match
    if (!isset($templates[$businessType])) {
        $businessType = 'other';
    }
    
    // Get the response for the intent, or use the unknown intent response
    if (isset($templates[$businessType][$intent])) {
        return $templates[$businessType][$intent];
    } else {
        return $templates[$businessType]['unknown'];
    }
}

/**
 * Save a message to the database
 * 
 * @param int $sessionId Chat session ID
 * @param string $message Message content
 * @param bool $isBot Whether the message is from the bot
 * @return int|false Message ID or false on failure
 */
function saveMessage($sessionId, $message, $isBot = false) {
    return dbExecute(
        "INSERT INTO messages (session_id, message, is_bot) VALUES (?, ?, ?)",
        [$sessionId, $message, $isBot ? 1 : 0]
    );
}

/**
 * Get or create a chat session
 * 
 * @param string $sessionId Client session ID
 * @param int $businessId Business ID
 * @return int Database session ID
 */
function getOrCreateChatSession($sessionId, $businessId) {
    // Check if session exists
    $session = dbGetRow(
        "SELECT id FROM chat_sessions WHERE session_id = ? AND business_id = ? AND ended_at IS NULL",
        [$sessionId, $businessId]
    );
    
    if ($session) {
        return $session['id'];
    }
    
    // Create new session
    $visitorIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $dbSessionId = dbExecute(
        "INSERT INTO chat_sessions (session_id, business_id, visitor_ip, user_agent) VALUES (?, ?, ?, ?)",
        [$sessionId, $businessId, $visitorIp, $userAgent]
    );
    
    return $dbSessionId;
}

/**
 * End a chat session
 * 
 * @param string $sessionId Client session ID
 * @param int $businessId Business ID
 * @return bool Success status
 */
function endChatSession($sessionId, $businessId) {
    return dbExecute(
        "UPDATE chat_sessions SET ended_at = CURRENT_TIMESTAMP WHERE session_id = ? AND business_id = ? AND ended_at IS NULL",
        [$sessionId, $businessId]
    );
}

/**
 * Get chat history for a session
 * 
 * @param int $dbSessionId Database session ID
 * @return array Messages
 */
function getChatHistory($dbSessionId) {
    return dbSelect(
        "SELECT message, is_bot, created_at FROM messages WHERE session_id = ? ORDER BY created_at ASC",
        [$dbSessionId]
    );
}
