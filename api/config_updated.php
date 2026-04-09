<?php
/**
 * Configuración de Conexión a Base de Datos - VERSIÓN SEGURA
 * Rutas - Sistema de Gestión de Alojamientos Turísticos
 * VERSIÓN ACTUALIZADA CON MEJORAS DE SEGURIDAD
 */

// Configuración de la base de datos
// DESARROLLO LOCAL - Comenta estas líneas para usar producción
/*
define('DB_HOST', 'localhost');  // Tu servidor MySQL local
define('DB_NAME', 'u412199647_Rutas');  // Nombre de BD local
define('DB_USER', 'root');  // Usuario MySQL local (normalmente 'root')
define('DB_PASS', '');  // Contraseña MySQL local (vacía por defecto en XAMPP/WAMP)
define('DB_TABLE', 'accommodations'); // Nombre de tabla local
*/

// PRODUCCIÓN - Configuración activa para el servidor
define('DB_HOST', 'localhost');
define('DB_NAME', 'u412199647_Rutas');
define('DB_USER', 'u412199647_olgamarin');
define('DB_PASS', 'Rutas5Rurales7$');
define('DB_TABLE', 'accommodations');

// NUEVA CONFIGURACIÓN DE SEGURIDAD
define('SECURITY_ENABLED', true);
define('DEBUG_MODE', false); // Set to false in production
define('API_BASE_URL', 'https://rutasrurales.io/api'); // ¡API_BASE_URL AHORA DEFINIDA!
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes

// Configuración de reCAPTCHA v3
define('RECAPTCHA_SITE_KEY', '6LeHyRgsAAAAAPpK8PcEp2iuvMEE4wSoUpfpH89k');
define('RECAPTCHA_SECRET_KEY', '6LeHyRgsAAAAAHMWHsn2Som5LjQxDCFIsKqv0O2F');

// Configuración de CORS (permite que tu web acceda a la API)
$allowed_origins = [
    'https://rutasrurales.io',
    'https://rutasurales.io',
    'http://rutasrurales.io',
    'http://rutasurales.io'
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

// NUEVOS HEADERS DE SEGURIDAD
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función para conectar a la base de datos - CON MANEJO SEGURO DE ERRORES
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
        // Log error securely server-side
        error_log('Database Connection Error: ' . $e->getMessage());
        
        // Return GENERIC error to client (NO database details exposed!)
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor. Por favor, inténtalo de nuevo más tarde.'
        ]);
        exit();
    }
}

// NUEVA FUNCIÓN: Sanitización mejorada
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

// Función para sanitizar datos de entrada (compatibilidad con código existente)
function sanitizeInputLegacy($data) {
    if (is_array($data)) {
        return array_map('sanitizeInputLegacy', $data);
    }
    // Manejar null y valores no string
    if ($data === null) {
        return null;
    }
    return htmlspecialchars(strip_tags(trim((string)$data)), ENT_QUOTES, 'UTF-8');
}

// Alias para compatibilidad
function sanitizeInputOld($data) {
    return sanitizeInputLegacy($data);
}

// Función para validar email - MEJORADA
function isValidEmail($email) {
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para validar reCAPTCHA v3 - MEJORADA CON TIMEOUT
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
    // 1.0 = muy probablemente humano, 0.0 = muy probablemente bot
    if ($response['success'] && $response['score'] >= 0.5) {
        return ['success' => true, 'score' => $response['score']];
    }
    
    return [
        'success' => false, 
        'error' => 'Verificación de reCAPTCHA fallida. Score: ' . ($response['score'] ?? 'N/A'),
        'score' => $response['score'] ?? 0
    ];
}

// Función para respuesta JSON exitosa
function jsonSuccess($data, $message = '') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Función para respuesta JSON de error - MEJORADA
function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit();
}

// NUEVAS FUNCIONES DE SEGURIDAD

// Inicializar sesión segura
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

// Generar token CSRF
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

// Validar token CSRF
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

// Requerir validación CSRF para operaciones que cambian estado
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

// Validar y sanitizar email mejorado
function validateAndSanitizeEmail($email) {
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    return $email;
}

// Log de eventos de seguridad
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

// Inicializar medidas de seguridad
function initSecurity() {
    // Start secure session
    initSecureSession();
    
    // Log request for monitoring
    logSecurityEvent('api_request', [
        'path' => $_SERVER['REQUEST_URI'],
        'method' => $_SERVER['REQUEST_METHOD'],
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);
}
?>
