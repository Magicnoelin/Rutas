<?php
/**
 * Secure Configuration File
 * Enhanced version with security improvements
 */

// PRODUCCIÓN - Configuración activa para el servidor
define('DB_HOST', 'localhost');
define('DB_NAME', 'u412199647_Rutas');
define('DB_USER', 'u412199647_olgamarin');
define('DB_PASS', 'Rutas5Rurales7$');
define('DB_TABLE', 'accommodations');

// Security configuration
define('SECURITY_ENABLED', true);
define('DEBUG_MODE', false); // Set to false in production
define('API_BASE_URL', 'https://rutasrurales.io/api');

// Configuración de reCAPTCHA v3
define('RECAPTCHA_SITE_KEY', '6LeHyRgsAAAAAPpK8PcEp2iuvMEE4wSoUpfpH89k');
define('RECAPTCHA_SECRET_KEY', '6LeHyRgsAAAAAHMWHsn2Som5LjQxDCFIsKqv0O2F');

// CORS configuration
$allowed_origins = [
    'https://rutasrurales.io',
    'https://www.rutasrurales.io',
    'http://rutasrurales.io',
    'http://www.rutasrurales.io'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: https://rutasrurales.io');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enhanced database connection with proper error handling
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log error securely
        error_log('Database Connection Error: ' . $e->getMessage());
        
        // Return generic error to client
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor. Por favor, inténtalo de nuevo más tarde.'
        ]);
        exit();
    }
}

// Enhanced sanitization function
function sanitizeInput($data, $options = []) {
    if (is_array($data)) {
        return array_map(function($item) use ($options) {
            return sanitizeInput($item, $options);
        }, $data);
    }
    
    if ($data === null || $data === '') {
        return $data;
    }
    
    // Default options
    $defaultOptions = [
        'strip_tags' => true,
        'trim' => true,
        'encode_special_chars' => true,
        'remove_dangerous' => true
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    if ($options['strip_tags']) {
        $data = strip_tags($data);
    }
    
    if ($options['trim']) {
        $data = trim($data);
    }
    
    if ($options['remove_dangerous']) {
        // Remove potentially dangerous patterns
        $data = preg_replace('/javascript:/i', '', $data);
        $data = preg_replace('/on\w+\s*=/i', '', $data);
        $data = preg_replace('/data:/i', '', $data);
    }
    
    if ($options['encode_special_chars']) {
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    return $data;
}

// Validate email
function isValidEmail($email) {
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Enhanced reCAPTCHA validation
function validateRecaptcha($token) {
    if (empty($token)) {
        return ['success' => false, 'error' => 'Token de reCAPTCHA no proporcionado'];
    }
    
    $secretKey = RECAPTCHA_SECRET_KEY;
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    
    $data = [
        'secret' => $secretKey,
        'response' => $token
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        return ['success' => false, 'error' => 'Error al verificar reCAPTCHA'];
    }
    
    $response = json_decode($result, true);
    
    // reCAPTCHA v3 devuelve un score de 0.0 a 1.0
    if ($response['success'] && $response['score'] >= 0.5) {
        return ['success' => true, 'score' => $response['score']];
    }
    
    return [
        'success' => false, 
        'error' => 'Verificación de reCAPTCHA fallida. Score: ' . ($response['score'] ?? 'N/A'),
        'score' => $response['score'] ?? 0
    ];
}

// Success JSON response
function jsonSuccess($data, $message = '') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Error JSON response with proper error codes
function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit();
}

// Initialize security measures
function initSecurity() {
    // Start secure session
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        session_start();
    }
}

// Log security events
function logSecurityEvent($event, $details = []) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ];
    
    error_log('SECURITY: ' . json_encode($logData));
}
?>
