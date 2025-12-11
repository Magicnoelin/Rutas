<?php
/**
 * API Endpoint: Registro de Usuario
 * POST /api/register.php
 * Body: JSON con los datos del usuario
 */

require_once 'config.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    // Obtener datos del body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        jsonError('Datos JSON inválidos', 400);
    }

    // Validar reCAPTCHA (temporalmente deshabilitado para debugging)
    /*
    if (!isset($data['recaptchaToken'])) {
        jsonError('Token de reCAPTCHA no proporcionado', 400);
    }

    $recaptchaResult = validateRecaptcha($data['recaptchaToken']);
    if (!$recaptchaResult['success']) {
        jsonError($recaptchaResult['error'], 403);
    }
    */
    $recaptchaResult = ['success' => true, 'score' => 1.0]; // Simulado

    // Validar campos requeridos
    $camposRequeridos = ['firstName', 'lastName', 'email', 'password'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
            jsonError("El campo '$campo' es requerido", 400);
        }
    }

    // Validar email
    if (!isValidEmail($data['email'])) {
        jsonError('Email inválido', 400);
    }

    // Validar contraseña
    if (strlen($data['password']) < 8) {
        jsonError('La contraseña debe tener al menos 8 caracteres', 400);
    }

    // Verificar que las contraseñas coincidan
    if ($data['password'] !== $data['confirmPassword']) {
        jsonError('Las contraseñas no coinciden', 400);
    }

    // Verificar aceptación de términos
    if (!isset($data['terms']) || $data['terms'] !== true) {
        jsonError('Debes aceptar los términos y condiciones', 400);
    }

    // Sanitizar todos los datos
    $datosLimpios = [];
    foreach ($data as $key => $value) {
        $datosLimpios[$key] = sanitizeInput($value);
    }

    $pdo = getDBConnection();

    // Verificar si el email ya existe
    $sqlCheckEmail = "SELECT id FROM users WHERE email = :email";
    $stmtCheck = $pdo->prepare($sqlCheckEmail);
    $stmtCheck->bindValue(':email', $datosLimpios['email']);
    $stmtCheck->execute();

    if ($stmtCheck->rowCount() > 0) {
        jsonError('Ya existe una cuenta con este correo electrónico', 409);
    }

    // Crear tabla users si no existe
    $sqlCheckTable = "SHOW TABLES LIKE 'users'";
    $result = $pdo->query($sqlCheckTable);
    $tableExists = $result->rowCount() > 0;

    if (!$tableExists) {
        $sqlCreateTable = "
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                phone VARCHAR(50),
                password_hash VARCHAR(255) NOT NULL,
                email_verified TINYINT(1) DEFAULT 0,
                verification_token VARCHAR(255),
                terms_accepted TINYINT(1) DEFAULT 1,
                status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                INDEX idx_email (email),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sqlCreateTable);
    }

    // Generar hash de contraseña
    $passwordHash = password_hash($datosLimpios['password'], PASSWORD_DEFAULT);

    // Generar token de verificación
    $verificationToken = bin2hex(random_bytes(32));

    // Preparar datos para inserción
    $userData = [
        'first_name' => $datosLimpios['firstName'],
        'last_name' => $datosLimpios['lastName'],
        'email' => $datosLimpios['email'],
        'phone' => $datosLimpios['phone'] ?? null,
        'password_hash' => $passwordHash,
        'verification_token' => $verificationToken,
        'terms_accepted' => 1,
        'status' => 'active'
    ];

    // Insertar usuario
    $columnas = array_keys($userData);
    $placeholders = array_map(function($col) { return ":$col"; }, $columnas);

    $sql = "INSERT INTO users (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);

    foreach ($userData as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    $stmt->execute();
    $userId = $pdo->lastInsertId();

    // Aquí se podría enviar email de verificación
    // sendVerificationEmail($datosLimpios['email'], $verificationToken);

    // Iniciar sesión para el nuevo usuario
    session_start();
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $userData['email'];
    $_SESSION['user_name'] = $userData['first_name'] . ' ' . $userData['last_name'];

    $response = [
        'user_id' => $userId,
        'first_name' => $userData['first_name'],
        'last_name' => $userData['last_name'],
        'email' => $userData['email'],
        'email_verified' => false,
        'status' => $userData['status'],
        'recaptcha_score' => $recaptchaResult['score'],
        'redirect_to' => 'preferences.html'
    ];

    jsonSuccess($response, '¡Cuenta creada exitosamente! Ahora vamos a configurar tus preferencias.');

} catch (PDOException $e) {
    error_log('Register.php - Database Error: ' . $e->getMessage());
    jsonError('Error al crear la cuenta: ' . $e->getMessage(), 500);
}
