<?php
/**
 * API Endpoint: Registro de Usuario (versión simplificada)
 * POST /api/register_simple.php
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

    // Validar campos requeridos
    $camposRequeridos = ['userType', 'firstName', 'lastName', 'email', 'password'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
            jsonError("El campo '$campo' es requerido", 400);
        }
    }

    // Validar tipo de usuario
    $tiposValidos = ['turista', 'alojamiento', 'promotor_eventos', 'actividad_cultural'];
    if (!in_array($data['userType'], $tiposValidos)) {
        jsonError('Tipo de usuario inválido', 400);
    }

    // Validar campos de negocio si no es turista
    if ($data['userType'] !== 'turista') {
        if (empty(trim($data['businessName'])) || empty(trim($data['businessDescription']))) {
            jsonError('Los datos del negocio son requeridos para este tipo de usuario', 400);
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

    // Generar hash de contraseña
    $passwordHash = password_hash($datosLimpios['password'], PASSWORD_DEFAULT);

    // Generar token de verificación
    $verificationToken = bin2hex(random_bytes(32));

    // Determinar estado de verificación según tipo de usuario
    $verificationStatus = ($datosLimpios['userType'] === 'turista') ? 'verified' : 'pending';

    // Preparar datos para inserción
    $userData = [
        'user_type' => $datosLimpios['userType'],
        'business_name' => $datosLimpios['businessName'] ?? null,
        'business_description' => $datosLimpios['businessDescription'] ?? null,
        'verification_status' => $verificationStatus,
        'subscription_level' => 'basic',
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

    // Crear permisos según tipo de usuario (solo si la tabla existe)
    try {
        createUserPermissions($pdo, $userId, $datosLimpios['userType']);
    } catch (Exception $e) {
        // Si falla la creación de permisos, continuar sin ellos
        error_log('No se pudieron crear permisos: ' . $e->getMessage());
    }

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
        'redirect_to' => 'preferences.html'
    ];

    jsonSuccess($response, '¡Cuenta creada exitosamente! Ahora vamos a configurar tus preferencias.');

} catch (PDOException $e) {
    error_log('Register_simple.php - Database Error: ' . $e->getMessage());
    jsonError('Error al crear la cuenta: ' . $e->getMessage(), 500);
}

/**
 * Crear permisos para un usuario según su tipo
 */
function createUserPermissions($pdo, $userId, $userType) {
    $permissions = [];

    switch ($userType) {
        case 'turista':
            $permissions = [
                ['resource' => 'accommodations', 'create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ['resource' => 'events', 'create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ['resource' => 'places', 'create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ['resource' => 'activities', 'create' => false, 'read' => true, 'update' => false, 'delete' => false]
            ];
            break;

        case 'alojamiento':
            $permissions = [
                ['resource' => 'accommodations', 'create' => true, 'read' => true, 'update' => true, 'delete' => true],
                ['resource' => 'events', 'create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ['resource' => 'places', 'create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ['resource' => 'activities', 'create' => false, 'read' => true, 'update' => false, 'delete' => false]
            ];
            break;

        case 'promotor_eventos':
            $permissions = [
                ['resource' => 'accommodations', 'create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ['resource' => 'events', 'create' => true, 'read' => true, 'update' => true, 'delete' => true],
                ['resource' => 'places', 'create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ['resource' => 'activities', 'create' => false, 'read' => true, 'update' => false, 'delete' => false]
            ];
            break;

        case 'actividad_cultural':
            $permissions = [
                ['resource' => 'accommodations', 'create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ['resource' => 'events', 'create' => false, 'read' => true, 'update' => false, 'delete' => false],
                ['resource' => 'places', 'create' => true, 'read' => true, 'update' => true, 'delete' => true],
                ['resource' => 'activities', 'create' => true, 'read' => true, 'update' => true, 'delete' => true]
            ];
            break;
    }

    // Insertar permisos en la base de datos
    $sql = "INSERT INTO user_permissions (user_id, resource_type, can_create, can_read, can_update, can_delete)
            VALUES (:user_id, :resource_type, :can_create, :can_read, :can_update, :can_delete)";
    $stmt = $pdo->prepare($sql);

    foreach ($permissions as $perm) {
        $stmt->execute([
            ':user_id' => $userId,
            ':resource_type' => $perm['resource'],
            ':can_create' => $perm['create'] ? 1 : 0,
            ':can_read' => $perm['read'] ? 1 : 0,
            ':can_update' => $perm['update'] ? 1 : 0,
            ':can_delete' => $perm['delete'] ? 1 : 0
        ]);
    }
}
