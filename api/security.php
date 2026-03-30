<?php
/**
 * Security Configuration and Utilities
 * Provides security functions and configurations for the API
 */

// Security configuration
define('SECURITY_ENABLED', true);
define('API_BASE_URL', 'https://rutasrurales.io/api');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check if token has expired (24 hours)
    if (time() - $_SESSION['csrf_token_time'] > 86400) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require CSRF token validation for state-changing requests
 */
function requireCSRF() {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Only validate for POST, PUT, DELETE, PATCH requests
    if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST[CSRF_TOKEN_NAME] ?? '';
        
        if (empty($token) || !validateCSRFToken($token)) {
            jsonError('Token CSRF inválido o expirado', 403);
            exit;
        }
    }
}

/**
 * Secure session configuration
 */
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Session timeout check
        if (isset($_SESSION['LAST_ACTIVITY'])) {
            if (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT) {
                session_unset();
                session_destroy();
                jsonError('Sesión expirada. Por favor, inicia sesión nuevamente.', 401);
                exit;
            }
        }
        $_SESSION['LAST_ACTIVITY'] = time();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['CREATED'])) {
            $_SESSION['CREATED'] = time();
        } else if (time() - $_SESSION['CREATED'] > SESSION_TIMEOUT) {
            session_regenerate_id(true);
            $_SESSION['CREATED'] = time();
        }
    }
}

/**
 * Enhanced input sanitization
 */
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

/**
 * Validate and sanitize email
 */
function validateAndSanitizeEmail($email) {
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    return $email;
}

/**
 * Validate file upload
 */
function validateFileUpload($file) {
    $errors = [];
    
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error en la subida del archivo';
        return $errors;
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $errors[] = 'El archivo es demasiado grande. Tamaño máximo: ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB';
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        $errors[] = 'Tipo de archivo no permitido. Solo se permiten imágenes.';
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = 'Extensión de archivo no permitida';
    }
    
    return $errors;
}

/**
 * Secure file upload with random filename
 */
function secureFileUpload($file, $uploadDir = 'uploads/') {
    $validationErrors = validateFileUpload($file);
    
    if (!empty($validationErrors)) {
        return ['success' => false, 'errors' => $validationErrors];
    }
    
    // Generate random filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Ensure upload directory exists and is secure
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'errors' => ['Error al guardar el archivo']];
    }
}

/**
 * Enhanced database error handling
 */
function handleDatabaseError($e, $context = '') {
    // Log detailed error server-side
    $logMessage = sprintf(
        "Database Error in %s: %s (Code: %s) at %s:%d",
        $context,
        $e->getMessage(),
        $e->getCode(),
        $e->getFile(),
        $e->getLine()
    );
    error_log($logMessage);
    
    // Return generic error to client
    $genericErrors = [
        'Connection failed',
        'Query failed',
        'Database operation failed'
    ];
    
    $userMessage = 'Error interno del servidor. Por favor, inténtalo de nuevo más tarde.';
    
    // Use different messages based on error type for better debugging in development
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $userMessage = 'Error de base de datos. Contacta al administrador.';
    }
    
    jsonError($userMessage, 500);
}

/**
 * Rate limiting (basic implementation)
 */
function checkRateLimit($identifier, $maxAttempts = MAX_LOGIN_ATTEMPTS, $timeout = LOGIN_ATTEMPT_TIMEOUT) {
    $key = 'rate_limit_' . md5($identifier);
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => $now, 'last_attempt' => $now];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    // Reset if timeout period has passed
    if ($now - $data['first_attempt'] > $timeout) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => $now, 'last_attempt' => $now];
        return true;
    }
    
    // Check if limit exceeded
    if ($data['count'] >= $maxAttempts) {
        return false;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    $_SESSION[$key]['last_attempt'] = $now;
    
    return true;
}

/**
 * Add security headers
 */
function addSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data: https:;');
    
    // HSTS for HTTPS
    if (isset($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * Log security events
 */
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

/**
 * Require authentication with enhanced security
 */
function requireAuth() {
    initSecureSession();
    
    if (!isAuthenticated()) {
        logSecurityEvent('unauthorized_access_attempt', [
            'path' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD']
        ]);
        jsonError('No autenticado. Por favor, inicia sesión.', 401);
        exit;
    }
}

/**
 * Require CSRF protection for all state-changing operations
 */
function requireCSRFProtection() {
    requireCSRF();
}

/**
 * Initialize security measures
 */
function initSecurity() {
    // Start secure session
    initSecureSession();
    
    // Add security headers
    addSecurityHeaders();
    
    // Log request for monitoring
    logSecurityEvent('api_request', [
        'path' => $_SERVER['REQUEST_URI'],
        'method' => $_SERVER['REQUEST_METHOD'],
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);
}
?>
