<?php
/**
 * Test exacto de la petición POST que hace register.html
 */

// Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test POST - Register Simple</h1>";

// Simular exactamente los datos que envía el formulario
$jsonData = '{
    "userType": "turista",
    "businessName": null,
    "businessDescription": null,
    "firstName": "Test",
    "lastName": "User",
    "email": "test' . time() . '@example.com",
    "phone": "",
    "password": "password123",
    "confirmPassword": "password123",
    "terms": true
}';

echo "<h2>Datos JSON simulados:</h2>";
echo "<pre>$jsonData</pre>";

// Procesar como lo hace register_simple.php
try {
    echo "<h2>Procesando como register_simple.php...</h2>";
    echo "<pre>";

    // Paso 1: Decodificar JSON
    echo "1. Decodificando JSON...\n";
    $data = json_decode($jsonData, true);
    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }
    echo "✅ JSON decodificado correctamente\n";

    // Paso 2: Cargar config
    echo "2. Cargando config.php...\n";
    require_once 'config.php';
    echo "✅ config.php cargado\n";

    // Paso 3: Validaciones
    echo "3. Validando campos requeridos...\n";
    $camposRequeridos = ['userType', 'firstName', 'lastName', 'email', 'password'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
            throw new Exception("Campo requerido faltante: $campo");
        }
    }
    echo "✅ Campos requeridos OK\n";

    // Paso 4: Validar tipo de usuario
    echo "4. Validando tipo de usuario...\n";
    $tiposValidos = ['turista', 'alojamiento', 'promotor_eventos', 'actividad_cultural'];
    if (!in_array($data['userType'], $tiposValidos)) {
        throw new Exception('Tipo de usuario inválido');
    }
    echo "✅ Tipo de usuario válido\n";

    // Paso 5: Validar email
    echo "5. Validando email...\n";
    if (!isValidEmail($data['email'])) {
        throw new Exception('Email inválido');
    }
    echo "✅ Email válido\n";

    // Paso 6: Validar contraseña
    echo "6. Validando contraseña...\n";
    if (strlen($data['password']) < 8) {
        throw new Exception('Contraseña demasiado corta');
    }
    echo "✅ Contraseña válida\n";

    // Paso 7: Verificar contraseñas
    echo "7. Verificando contraseñas...\n";
    if ($data['password'] !== $data['confirmPassword']) {
        throw new Exception('Contraseñas no coinciden');
    }
    echo "✅ Contraseñas coinciden\n";

    // Paso 8: Verificar términos
    echo "8. Verificando términos...\n";
    if (!isset($data['terms']) || $data['terms'] !== true) {
        throw new Exception('Debes aceptar los términos');
    }
    echo "✅ Términos aceptados\n";

    // Paso 9: Sanitizar datos
    echo "9. Sanitizando datos...\n";
    $datosLimpios = [];
    foreach ($data as $key => $value) {
        $datosLimpios[$key] = sanitizeInput($value);
    }
    echo "✅ Datos sanitizados\n";

    // Paso 10: Conectar a BD
    echo "10. Conectando a base de datos...\n";
    $pdo = getDBConnection();
    echo "✅ Conexión exitosa\n";

    // Paso 11: Verificar email duplicado
    echo "11. Verificando email duplicado...\n";
    $sqlCheckEmail = "SELECT id FROM users WHERE email = :email";
    $stmtCheck = $pdo->prepare($sqlCheckEmail);
    $stmtCheck->bindValue(':email', $datosLimpios['email']);
    $stmtCheck->execute();

    if ($stmtCheck->rowCount() > 0) {
        throw new Exception('Ya existe una cuenta con este correo electrónico');
    }
    echo "✅ Email único\n";

    // Paso 12: Generar hash de contraseña
    echo "12. Generando hash de contraseña...\n";
    $passwordHash = password_hash($datosLimpios['password'], PASSWORD_DEFAULT);
    echo "✅ Hash generado\n";

    // Paso 13: Generar token
    echo "13. Generando token de verificación...\n";
    $verificationToken = bin2hex(random_bytes(32));
    echo "✅ Token generado\n";

    // Paso 14: Determinar estado de verificación
    echo "14. Determinando estado de verificación...\n";
    $verificationStatus = ($datosLimpios['userType'] === 'turista') ? 'verified' : 'pending';
    echo "✅ Estado: $verificationStatus\n";

    // Paso 15: Preparar datos para inserción
    echo "15. Preparando datos para inserción...\n";
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
    echo "✅ Datos preparados\n";

    // Paso 16: Insertar usuario
    echo "16. Insertando usuario...\n";
    $columnas = array_keys($userData);
    $placeholders = array_map(function($col) { return ":$col"; }, $columnas);
    $sql = "INSERT INTO users (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);

    foreach ($userData as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    $stmt->execute();
    $userId = $pdo->lastInsertId();
    echo "✅ Usuario insertado con ID: $userId\n";

    // Paso 17: Crear permisos
    echo "17. Creando permisos...\n";
    try {
        createUserPermissions($pdo, $userId, $datosLimpios['userType']);
        echo "✅ Permisos creados\n";
    } catch (Exception $e) {
        echo "⚠️ Error creando permisos: " . $e->getMessage() . "\n";
        echo "Continuando sin permisos...\n";
    }

    // Paso 18: Iniciar sesión
    echo "18. Iniciando sesión...\n";
    session_start();
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $userData['email'];
    $_SESSION['user_name'] = $userData['first_name'] . ' ' . $userData['last_name'];
    echo "✅ Sesión iniciada\n";

    echo "\n🎉 REGISTRO COMPLETADO EXITOSAMENTE\n";

} catch (Exception $e) {
    echo "\n❌ ERROR EN EL PASO ANTERIOR:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";

// Función de permisos (copiada de register_simple.php)
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
?>
