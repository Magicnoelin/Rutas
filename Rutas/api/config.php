<?php
/**
 * Configuración de Conexión a Base de Datos
 * Rutas - Sistema de Gestión de Alojamientos Turísticos
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');  // o 127.0.0.1
define('DB_NAME', 'u412199647_Alojamientos');
define('DB_USER', 'TU_USUARIO_MYSQL');  // ← REEMPLAZA CON TU USUARIO
define('DB_PASS', 'TU_PASSWORD_MYSQL'); // ← REEMPLAZA CON TU CONTRASEÑA
define('DB_TABLE', 'alojamientos');     // ← CONFIRMA EL NOMBRE DE TU TABLA

// Configuración de CORS (permite que tu web acceda a la API)
header('Access-Control-Allow-Origin: https://rutasrurales.io');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
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
