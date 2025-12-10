<?php
/**
 * Configuración de Conexión a Base de Datos
 * Rutas - Sistema de Gestión de Alojamientos Turísticos
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
define('DB_USER', 'u412199647_rutasrurales');
define('DB_PASS', 'Rutas5Rurales7$');
define('DB_TABLE', 'accommodations');

// Configuración de reCAPTCHA v3
define('RECAPTCHA_SITE_KEY', '6LeHyRgsAAAAAPpK8PcEp2iuvMEE4wSoUpfpH89k');
define('RECAPTCHA_SECRET_KEY', '6LeHyRgsAAAAAHMWHsn2Som5LjQxDCFIsKqv0O2F');

// Configuración de CORS (permite que tu web acceda a la API)
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
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función para conectar a la base de datos
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
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error de conexión a la base de datos',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

// Función para sanitizar datos de entrada
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Función para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para validar reCAPTCHA v3
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
            'content' => http_build_query($data)
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

// Función para respuesta JSON de error
function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit();
}
