<?php
/**
 * API Endpoint: Registro de Usuario
 * POST /api/register.php
 * Body: JSON con los datos del usuario
 */

require_once 'config.php';

// Iniciar sesión al principio
session_start();

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

    // Sanitizar todos los datos (excepto la contraseña que ya se hashea)
    $datosLimpios = [];
    foreach ($data as $key => $value) {
        if ($key === 'password') {
            $datosLimpios[$key] = trim($value); // Solo trim para la contraseña
        } else {
            $datosLimpios[$key] = sanitizeInput($value);
        }
    }

    $pdo = getDBConnection();

    // Verificar conexión a BD
    if (!$pdo) {
        jsonError('Error de conexión a la base de datos', 500);
    }

    // Verificar si la tabla users existe
    try {
        $result = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($result->rowCount() === 0) {
            jsonError('La tabla users no existe', 500);
        }
    } catch (PDOException $e) {
        jsonError('Error al verificar tabla users: ' . $e->getMessage(), 500);
    }

    // Verificar si el email ya existe
    $sqlCheckEmail = "SELECT id FROM users WHERE email = :email";
    $stmtCheck = $pdo->prepare($sqlCheckEmail);
    $stmtCheck->bindValue(':email', $datosLimpios['email']);
    $stmtCheck->execute();

    if ($stmtCheck->rowCount() > 0) {
        jsonError('Ya existe una cuenta con este correo electrónico', 409);
    }

    // Verificar que la columna user_type existe
    try {
        $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'user_type'");
        if ($result->rowCount() === 0) {
            jsonError('La columna user_type no existe en la tabla users', 500);
        }
    } catch (PDOException $e) {
        jsonError('Error al verificar columna user_type: ' . $e->getMessage(), 500);
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
        'user_type' => 'turista', // Por defecto, el usuario es un turista
        'password_hash' => $passwordHash,
        'verification_token' => $verificationToken,
        'terms_accepted' => 1,
        'status' => 'active'
    ];

    // Insertar usuario
    $columnas = array_keys($userData);
    $placeholders = array_map(function($col) { return ":$col"; }, $columnas);

    $sql = "INSERT INTO users (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";
    error_log('SQL Query: ' . $sql);
    error_log('User Data: ' . json_encode($userData));

    $stmt = $pdo->prepare($sql);

    foreach ($userData as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    // Verificar si estamos en una transacción antes de ejecutar
    $wasInTransaction = $pdo->inTransaction();
    error_log('Estado de transacción antes del INSERT: ' . ($wasInTransaction ? 'SÍ' : 'NO'));

    $result = $stmt->execute();

    if (!$result) {
        jsonError('Error al ejecutar la inserción en la base de datos', 500);
    }

    $userId = $pdo->lastInsertId();
    error_log('ID obtenido después del INSERT: ' . $userId);

    if (!$userId) {
        // Intentar obtener el último ID de otra manera
        $stmtLastId = $pdo->query("SELECT LAST_INSERT_ID() as id");
        $lastIdResult = $stmtLastId->fetch();
        $userId = $lastIdResult['id'];
        error_log('ID obtenido con LAST_INSERT_ID(): ' . $userId);

        if (!$userId) {
            jsonError('Error al obtener el ID del usuario creado', 500);
        }
    }

    // Forzar commit si hay una transacción abierta
    if ($pdo->inTransaction()) {
        $pdo->commit();
        error_log('Transacción confirmada manualmente');
    }

    // Verificar que el usuario realmente se guardó
    $stmtVerify = $pdo->prepare("SELECT id, email, user_type FROM users WHERE id = :id");
    $stmtVerify->bindValue(':id', $userId);
    $stmtVerify->execute();
    $userVerify = $stmtVerify->fetch();

    if (!$userVerify) {
        error_log('ERROR: Usuario no encontrado después del INSERT. ID: ' . $userId);
        jsonError('Usuario no se guardó correctamente en la base de datos', 500);
    }

    error_log('Usuario verificado exitosamente: ' . json_encode($userVerify));
    error_log('Usuario creado exitosamente con ID: ' . $userId);

    // Aquí se podría enviar email de verificación
    // sendVerificationEmail($datosLimpios['email'], $verificationToken);

    // ── ASIGNAR ROL EN role_user (nuevo sistema de roles) ──────────────────
    // Determinar el rol a asignar (por defecto 'turista')
    $rolSlug = 'turista';
    if (!empty($datosLimpios['userType']) && in_array($datosLimpios['userType'], ['turista', 'alojamiento', 'promotor_eventos', 'actividad_cultural'])) {
        $rolSlug = $datosLimpios['userType'];
    }

    try {
        // Verificar que la tabla roles existe antes de intentar insertar
        $checkRoles = $pdo->query("SHOW TABLES LIKE 'roles'");
        if ($checkRoles->rowCount() > 0) {
            $stmtRol = $pdo->prepare("SELECT id FROM roles WHERE slug = ?");
            $stmtRol->execute([$rolSlug]);
            $rolRow = $stmtRol->fetch();

            if ($rolRow) {
                $pdo->prepare("INSERT IGNORE INTO role_user (user_id, role_id) VALUES (?, ?)")
                    ->execute([$userId, $rolRow['id']]);
                error_log("Rol '$rolSlug' asignado al usuario $userId en role_user");
            }
        }
    } catch (PDOException $eRol) {
        // No bloquear el registro si el sistema de roles aún no está instalado
        error_log("Aviso: No se pudo asignar rol en role_user: " . $eRol->getMessage());
    }
    // ───────────────────────────────────────────────────────────────────────

    // Guardar datos en la sesión para el nuevo usuario
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
        'redirect_to' => 'user-dashboard.html'
    ];

    jsonSuccess($response, '¡Cuenta creada exitosamente! Ahora vamos a configurar tus preferencias.');

} catch (PDOException $e) {
    error_log('Register.php - Database Error: ' . $e->getMessage());
    jsonError('Error al crear la cuenta: ' . $e->getMessage(), 500);
}
