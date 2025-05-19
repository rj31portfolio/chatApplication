<?php
/**
 * Helper functions for the application
 */

/**
 * Display error message
 * 
 * @param string $message Error message
 * @return string HTML for error message
 */
function displayError($message) {
    return '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

/**
 * Display success message
 * 
 * @param string $message Success message
 * @return string HTML for success message
 */
function displaySuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

/**
 * Sanitize output for HTML
 * 
 * @param string $text Text to sanitize
 * @return string Sanitized text
 */
function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Format date/time
 * 
 * @param string $datetime Date/time string
 * @param string $format Format string
 * @return string Formatted date/time
 */
function formatDateTime($datetime, $format = 'M j, Y g:i A') {
    $dt = new DateTime($datetime);
    return $dt->format($format);
}

/**
 * Get current page name
 * 
 * @return string Current page name
 */
function getCurrentPage() {
    $path = $_SERVER['PHP_SELF'];
    $parts = explode('/', $path);
    return end($parts);
}

/**
 * Is the current page active?
 * 
 * @param string $page Page name to check
 * @return bool Is active
 */
function isActivePage($page) {
    return getCurrentPage() === $page;
}

/**
 * Get active class for navigation
 * 
 * @param string $page Page name to check
 * @return string Active class or empty string
 */
function getActiveClass($page) {
    return isActivePage($page) ? 'active' : '';
}

/**
 * Generate pagination HTML
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $url Base URL for pagination links
 * @return string Pagination HTML
 */
function generatePagination($currentPage, $totalPages, $url) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($currentPage - 1) . '">&laquo; Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">&laquo; Previous</a></li>';
    }
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=1">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($currentPage + 1) . '">Next &raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">Next &raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Format bytes to human-readable size
 * 
 * @param int $bytes Number of bytes
 * @param int $precision Decimal precision
 * @return string Formatted size
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Get the embedding code for the chatbot widget
 * 
 * @param string $apiKey Business API key
 * @return string Embedding code
 */
function getEmbeddingCode($apiKey) {
    $baseUrl = BASE_URL;
    
    return <<<HTML
<script>
    (function(w,d,s,o,f,js,fjs){
        w['AIChatWidget']=o;w[o]=w[o]||function(){(w[o].q=w[o].q||[]).push(arguments)};
        js=d.createElement(s),fjs=d.getElementsByTagName(s)[0];
        js.id=o;js.src=f;js.async=1;fjs.parentNode.insertBefore(js,fjs);
    }(window,document,'script','aiChat','{$baseUrl}assets/js/widget.js'));
    aiChat('init', '{$apiKey}');
</script>
HTML;
}

/**
 * Get business types as options for select element
 * 
 * @param string $selected Selected business type
 * @return string HTML options
 */
function getBusinessTypeOptions($selected = '') {
    $options = '';
    
    foreach (BUSINESS_TYPES as $value => $label) {
        $selectedAttr = ($value === $selected) ? ' selected' : '';
        $options .= '<option value="' . h($value) . '"' . $selectedAttr . '>' . h($label) . '</option>';
    }
    
    return $options;
}

/**
 * Check if AJAX request
 * 
 * @return bool Is AJAX request
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Send JSON response
 * 
 * @param array $data Response data
 * @param int $status HTTP status code
 */
function sendJsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get the visitor's unique ID for sessions
 * 
 * @return string Visitor ID
 */
function getVisitorId() {
    if (!isset($_COOKIE['visitor_id'])) {
        $visitorId = bin2hex(random_bytes(16));
        setcookie('visitor_id', $visitorId, time() + 60*60*24*365, '/');
        return $visitorId;
    }
    
    return $_COOKIE['visitor_id'];
}
