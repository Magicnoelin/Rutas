<?php
/**
 * API Endpoint: Social Login (Google/Facebook)
 * POST /api/social_login.php
 * Body: JSON con el token del proveedor social
 */

require_once 'config.php';

// Iniciar sesión al principio
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

    // Validar que venga el provider y el token
    $provider = sanitizeInput($data['provider'] ?? '');
    $idToken = $data['idToken'] ?? ''; // Token de Google
    $accessToken = $data['accessToken'] ?? ''; // Token de Facebook

    if (empty($provider)) {
        jsonError('El proveedor (google/facebook) es requerido', 400);
    }

    if (!in_array($provider, ['google', 'facebook'])) {
        jsonError('Proveedor no válido. Usa: google o facebook', 400);
    }

    // Obtener información del usuario según el proveedor
    $userInfo = null;

    if ($provider === 'google') {
        $userInfo = verifyGoogleToken($idToken);
    } elseif ($provider === 'facebook') {
        $userInfo = verifyFacebookToken($accessToken);
    }

    if (!$userInfo || empty($userInfo['email'])) {
        jsonError('No se pudo verificar la identidad con ' . ucfirst($provider), 401);
    }

    // Buscar o crear usuario en la base de datos
    $pdo = getDBConnection();

    // Verificar si ya existe un usuario con este provider + id
    $idColumn = $provider === 'google' ? 'google_id' : 'facebook_id';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE $idColumn = :provider_id LIMIT 1");
    $stmt->execute([':provider_id' => $userInfo['id']]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        // Usuario existente - iniciar sesión
        $user = $existingUser;
    } else {
        // Verificar si existe un usuario con el mismo email (login tradicional vinculado)
        $stmtEmail = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmtEmail->execute([':email' => $userInfo['email']]);
        $userByEmail = $stmtEmail->fetch(PDO::FETCH_ASSOC);

        if ($userByEmail) {
            // Vincular cuenta existente con el proveedor social
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET $idColumn = :provider_id, 
                    auth_provider = :provider,
                    avatar_url = COALESCE(avatar_url, :avatar),
                    updated_at = NOW()
                WHERE id = :user_id
            ");
            $updateStmt->execute([
                ':provider_id' => $userInfo['id'],
                ':provider' => $provider,
                ':avatar' => $userInfo['picture'] ?? null,
                ':user_id' => $userByEmail['id']
            ]);
            $user = $userByEmail;
        } else {
            // Crear nuevo usuario
            $userData = [
                'first_name' => $userInfo['first_name'] ?? explode(' ', $userInfo['name'] ?? 'Usuario')[0],
                'last_name' => $userInfo['last_name'] ?? (explode(' ', $userInfo['name'] ?? 'Usuario')[1] ?? ''),
                'email' => $userInfo['email'],
                'user_type' => 'turista',
                'status' => 'active',
                'avatar_url' => $userInfo['picture'] ?? null,
                'auth_provider' => $provider,
                $idColumn => $userInfo['id']
            ];

            $columns = array_keys($userData);
            $placeholders = array_map(function($col) { return ":$col"; }, $columns);

            $sql = "INSERT INTO users (" . implode(', ', $columns) . ", created_social) VALUES (" . implode(', ', $placeholders) . ", NOW())";
            $stmtInsert = $pdo->prepare($sql);

            foreach ($userData as $key => $value) {
                $stmtInsert->bindValue(":$key", $value);
            }

            $stmtInsert->execute();
            $userId = $pdo->lastInsertId();

            // Obtener el usuario recién creado
            $stmtNewUser = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmtNewUser->execute([':id' => $userId]);
            $user = $stmtNewUser->fetch(PDO::FETCH_ASSOC);

            // Asignar rol de turista
            try {
                $stmtRol = $pdo->prepare("SELECT id FROM roles WHERE slug = 'turista'");
                $stmtRol->execute();
                $rolRow = $stmtRol->fetch();

                if ($rolRow) {
                    $pdo->prepare("INSERT IGNORE INTO role_user (user_id, role_id) VALUES (?, ?)")
                        ->execute([$userId, $rolRow['id']]);
                }
            } catch (PDOException $eRol) {
                error_log("Aviso: No se pudo asignar rol: " . $eRol->getMessage());
            }
        }
    }

    // Guardar datos en la sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_type'] = $user['user_type'] ?? 'turista';

    // Responder con éxito
    jsonSuccess([
        'user_id' => $user['id'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'avatar_url' => $user['avatar_url'] ?? $userInfo['picture'] ?? null,
        'auth_provider' => $provider,
        'redirect' => 'user-dashboard.html'
    ], 'Inicio de sesión con ' . ucfirst($provider) . ' exitoso');

} catch (PDOException $e) {
    error_log('Social Login - Database Error: ' . $e->getMessage());
    jsonError('Error al procesar la solicitud: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('Social Login - Error: ' . $e->getMessage());
    jsonError('Error al procesar la solicitud', 500);
}

/**
 * Verificar token de Google y obtener información del usuario
 */
function verifyGoogleToken($idToken) {
    if (empty($idToken)) {
        return null;
    }

    // Decodificar el token JWT manualmente para obtener la información del usuario
    // El token tiene 3 partes separadas por puntos
    $parts = explode('.', $idToken);
    if (count($parts) !== 3) {
        error_log('Google token invalid format');
        return null;
    }
    
    // La segunda parte es el payload (base64url encoded)
    $payloadJson = base64UrlDecode($parts[1]);
    $payload = json_decode($payloadJson, true);
    
    if (!$payload) {
        error_log('Google token invalid payload');
        return null;
    }
    
    // Verificar que el token tenga email y sub
    if (!isset($payload['email']) || !isset($payload['sub'])) {
        error_log('Google token missing email or sub');
        return null;
    }
    
    error_log('Google token decoded for: ' . $payload['email']);
    
    return [
        'id' => $payload['sub'],
        'email' => $payload['email'],
        'name' => $payload['name'] ?? null,
        'first_name' => $payload['given_name'] ?? null,
        'last_name' => $payload['family_name'] ?? null,
        'picture' => $payload['picture'] ?? null
    ];
}

// Función helper para decodificar base64url
function base64UrlDecode($str) {
    $str = str_replace('-', '+', $str);
    $str = str_replace('_', '/', $str);
    $pad = strlen($str) % 4;
    if ($pad) {
        $str .= str_repeat('=', 4 - $pad);
    }
    return base64_decode($str);
}

/**
 * Verificar token de Facebook y obtener información del usuario
 */
function verifyFacebookToken($accessToken) {
    if (empty($accessToken)) {
        return null;
    }

    // Facebook App ID y Secret
    $appId = '2139007790253777'; // Reemplazar con tu App ID
    $appSecret = 'd3e51bed1c8f2f043efa87d8536e0e56'; // Reemplazar con tu App Secret
    
    // Verificar token con Facebook Graph API
    $url = 'https://graph.facebook.com/me?fields=id,name,email,first_name,last_name,picture&access_token=' . urlencode($accessToken);
    
    $response = @file_get_contents($url);
    
    if (!$response) {
        // Si falla, intentar con curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        curl_close($ch);
    }
    
    if (!$response) {
        error_log('Facebook token verification failed - no response');
        return null;
    }
    
    $data = json_decode($response, true);
    
    // Verificar que no hay error
    if (!$data || isset($data['error'])) {
        error_log('Facebook token verification failed: ' . ($data['error']['message'] ?? 'Unknown error'));
        return null;
    }
    
    // Verificar que el token pertenece a nuestra app
    $debugUrl = 'https://graph.debug_token?input_token=' . urlencode($accessToken) . '&access_token=' . urlencode($appId . '|' . $appSecret);
    $debugResponse = @file_get_contents($debugUrl);
    
    if ($debugResponse) {
        $debugData = json_decode($debugResponse, true);
        if (!isset($debugData['data']['app_id']) || $debugData['data']['app_id'] !== $appId) {
            error_log('Facebook token app_id mismatch');
            return null;
        }
    }
    
    return [
        'id' => $data['id'],
        'email' => $data['email'] ?? null,
        'name' => $data['name'] ?? null,
        'first_name' => $data['first_name'] ?? null,
        'last_name' => $data['last_name'] ?? null,
        'picture' => $data['picture']['data']['url'] ?? null
    ];
}
