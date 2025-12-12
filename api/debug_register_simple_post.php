<?php
/**
 * Debug específico para register_simple.php con POST real
 */

// Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Register Simple - POST Request</h1>";
echo "<pre>";

// Solo procesar si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "=== RECIBIENDO PETICIÓN POST ===\n";

    try {
        // Paso 1: Obtener JSON
        echo "1. Leyendo JSON del body...\n";
        $json = file_get_contents('php://input');
        echo "JSON recibido: " . substr($json, 0, 200) . "...\n";

        $data = json_decode($json, true);
        if (!$data) {
            throw new Exception('JSON inválido: ' . json_last_error_msg());
        }
        echo "✅ JSON decodificado correctamente\n";

        // Paso 2: Cargar config
        echo "2. Cargando config.php...\n";
        require_once 'config.php';
        echo "✅ config.php cargado\n";

        // Paso 3: Validar campos requeridos
        echo "3. Validando campos requeridos...\n";
        $camposRequeridos = ['userType', 'firstName', 'lastName', 'email', 'password'];
        foreach ($camposRequeridos as $campo) {
            if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
                throw new Exception("Campo requerido faltante: $campo");
            }
        }
        echo "✅ Campos requeridos OK\n";

        // Paso 4: Conectar BD
        echo "4. Conectando a base de datos...\n";
        $pdo = getDBConnection();
        echo "✅ Conexión exitosa\n";

        // Paso 5: Verificar usuario existente
        echo "5. Verificando si usuario ya existe...\n";
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Usuario ya existe con este email');
        }
        echo "✅ Email único\n";

        // Paso 6: Preparar inserción
        echo "6. Preparando datos para inserción...\n";
        $userData = [
            'user_type' => $data['userType'],
            'business_name' => $data['businessName'] ?? null,
            'business_description' => $data['businessDescription'] ?? null,
            'verification_status' => ($data['userType'] === 'turista') ? 'verified' : 'pending',
            'subscription_level' => 'basic',
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'verification_token' => bin2hex(random_bytes(32)),
            'terms_accepted' => 1,
            'status' => 'active'
        ];
        echo "✅ Datos preparados\n";

        // Paso 7: Ejecutar inserción
        echo "7. Ejecutando INSERT...\n";
        $columnas = array_keys($userData);
        $placeholders = array_map(function($col) { return ":$col"; }, $columnas);
        $sql = "INSERT INTO users (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $pdo->prepare($sql);
        foreach ($userData as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $result = $stmt->execute();
        if (!$result) {
            throw new Exception('Error en la ejecución del INSERT');
        }

        $userId = $pdo->lastInsertId();
        echo "✅ Usuario insertado con ID: $userId\n";

        // Paso 8: Crear permisos
        echo "8. Creando permisos...\n";
        try {
            createUserPermissions($pdo, $userId, $data['userType']);
            echo "✅ Permisos creados\n";
        } catch (Exception $e) {
            echo "⚠️ Error creando permisos: " . $e->getMessage() . "\n";
        }

        echo "\n🎉 REGISTRO COMPLETADO EXITOSAMENTE\n";

        // Responder como API con información de debug
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Cuenta creada exitosamente',
            'user_id' => $userId,
            'redirect_to' => 'preferences.html',
            'debug_info' => 'Registro completado exitosamente'
        ]);
        exit();

    } catch (Exception $e) {
        echo "\n❌ ERROR: " . $e->getMessage() . "\n";
        echo "Línea: " . $e->getLine() . "\n";
        echo "Archivo: " . $e->getFile() . "\n";

        // Responder error como API con información de debug
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'debug_info' => [
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile(),
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]
        ]);
        exit();
    }

} else {
    echo "=== ESPERANDO PETICIÓN POST ===\n";
    echo "Este debug solo funciona con POST requests.\n";
    echo "Para probar, envía una petición POST con JSON desde register.html\n";
}

// Función de permisos
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

echo "</pre>";
?>
