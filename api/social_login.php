<?php
/**
 * API Endpoint: Social Login (Google/Facebook)
 * POST /api/social_login.php
 * Body: JSON con el token del proveedor social
 *
 * Cambios v2 (2026-01-08):
 *   - Email del proveedor normalizado a minúsculas antes de cualquier operación.
 *     Evita crear cuentas duplicadas cuando Google/Facebook devuelven el email
 *     con diferente capitalización (ej: "ElCampanario@Gmail.com" vs "elcampanario@gmail.com").
 *   - Verificación explícita de unicidad de email antes del INSERT.
 *   - Catch PDOException 23000 como red de seguridad final.
 *   - Mensajes de error internos nunca expuestos al frontend.
 */

require_once 'config.php';
require_once 'user_normalizer.php';

// Iniciar sesión al principio
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    // ── Leer y decodificar el body JSON ──────────────────────
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        jsonError('Datos JSON inválidos', 400);
    }

    // ── Validar provider y token ──────────────────────────────
    $provider    = sanitizeInput($data['provider'] ?? '');
    $idToken     = $data['idToken'] ?? '';      // Token de Google
    $accessToken = $data['accessToken'] ?? '';  // Token de Facebook

    if (empty($provider)) {
        jsonError('El proveedor (google/facebook) es requerido', 400);
    }

    if (!in_array($provider, ['google', 'facebook'], true)) {
        jsonError('Proveedor no válido. Usa: google o facebook', 400);
    }

    // ── Obtener información del usuario desde el proveedor ────
    $userInfo = null;

    if ($provider === 'google') {
        $userInfo = verifyGoogleToken($idToken);
    } elseif ($provider === 'facebook') {
        $userInfo = verifyFacebookToken($accessToken);
    }

    if (!$userInfo || empty($userInfo['email'])) {
        jsonError('No se pudo verificar la identidad con ' . ucfirst($provider), 401);
    }

    // ── NORMALIZAR EMAIL del proveedor ────────────────────────
    // Google/Facebook pueden devolver el email con capitalización variable
    // (ej: "ElCampanario.VUT.Villoria@Gmail.com"). Lo normalizamos SIEMPRE
    // antes de cualquier búsqueda o inserción para garantizar unicidad real.
    $emailNormalizado = validateAndNormalizeEmail($userInfo['email']);

    if ($emailNormalizado === false) {
        error_log("[social_login.php] Email inválido recibido de $provider: " . $userInfo['email']);
        jsonError('El email proporcionado por ' . ucfirst($provider) . ' no es válido.', 400);
    }

    // Sobreescribir el email en userInfo con la versión normalizada
    $userInfo['email'] = $emailNormalizado;

    // ── Conectar a la BD ──────────────────────────────────────
    $pdo = getDBConnection();

    $idColumn = $provider === 'google' ? 'google_id' : 'facebook_id';

    // ────────────────────────────────────────────────────────────
    // PASO 1: Buscar por provider_id (el caso más común tras el primer login)
    // Si el usuario ya inició sesión antes con Google/Facebook, lo encontramos
    // directamente por su ID de proveedor (más rápido y fiable que por email).
    // ────────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT * FROM users WHERE $idColumn = :provider_id LIMIT 1");
    $stmt->execute([':provider_id' => $userInfo['id']]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        // ── Usuario ya vinculado: iniciar sesión directamente ─
        $user = $existingUser;

        // Actualizar avatar si cambió en el proveedor
        if (!empty($userInfo['picture'])) {
            try {
                $pdo->prepare("UPDATE users SET avatar_url = :avatar, last_login = NOW() WHERE id = :id")
                    ->execute([':avatar' => $userInfo['picture'], ':id' => $user['id']]);
            } catch (PDOException $eUpd) {
                error_log("[social_login.php] No se pudo actualizar avatar: " . $eUpd->getMessage());
            }
        }

    } else {
        // ────────────────────────────────────────────────────────
        // PASO 2: Buscar por EMAIL normalizado
        // Cubre el caso: "elcampanario.vut.villoria@gmail.com" ya existe
        // en la BD con registro tradicional o con otro proveedor.
        // ────────────────────────────────────────────────────────
        $emailCheck = checkEmailExists($pdo, $emailNormalizado);

        if ($emailCheck['exists']) {
            $userByEmail = $emailCheck['user'];

            // ── Vincular el proveedor social a la cuenta existente ──
            // El usuario había creado su cuenta con email/contraseña
            // o con otro proveedor OAuth, y ahora intenta entrar con Google/FB.
            // Vinculamos el ID del proveedor para futuros logins directos.
            $updateStmt = $pdo->prepare("
                UPDATE users
                SET $idColumn   = :provider_id,
                    auth_provider = :provider,
                    avatar_url    = COALESCE(avatar_url, :avatar),
                    last_login    = NOW(),
                    updated_at    = NOW()
                WHERE id = :user_id
            ");
            $updateStmt->execute([
                ':provider_id' => $userInfo['id'],
                ':provider'    => $provider,
                ':avatar'      => $userInfo['picture'] ?? null,
                ':user_id'     => $userByEmail['id'],
            ]);

            error_log("[social_login.php] Cuenta existente vinculada a $provider: user_id={$userByEmail['id']}, email=$emailNormalizado");
            $user = $userByEmail;

        } else {
            // ────────────────────────────────────────────────────
            // PASO 3: Crear nuevo usuario vía OAuth
            // El email no existe en la BD: es un registro completamente nuevo.
            // ────────────────────────────────────────────────────

            $userData = [
                'first_name'   => $userInfo['first_name'] ?? explode(' ', $userInfo['name'] ?? 'Usuario')[0],
                'last_name'    => $userInfo['last_name']  ?? (explode(' ', $userInfo['name'] ?? 'Usuario')[1] ?? ''),
                'email'        => $emailNormalizado,  // Siempre normalizado
                'user_type'    => 'turista',
                'status'       => 'active',
                'avatar_url'   => $userInfo['picture'] ?? null,
                'auth_provider' => $provider,
                $idColumn      => $userInfo['id'],
            ];

            $columns      = array_keys($userData);
            $placeholders = array_map(fn($col) => ":$col", $columns);

            $sqlInsert = "INSERT INTO users (" . implode(', ', $columns) . ", created_social)
                          VALUES (" . implode(', ', $placeholders) . ", NOW())";

            $stmtInsert = $pdo->prepare($sqlInsert);
            foreach ($userData as $key => $value) {
                $stmtInsert->bindValue(":$key", $value);
            }
            $stmtInsert->execute();

            $userId = $pdo->lastInsertId();

            // Obtener el usuario recién creado
            $stmtNewUser = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmtNewUser->execute([':id' => $userId]);
            $user = $stmtNewUser->fetch(PDO::FETCH_ASSOC);

            error_log("[social_login.php] Nuevo usuario creado vía $provider: user_id=$userId, email=$emailNormalizado");

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
                error_log("[social_login.php] Aviso rol: " . $eRol->getMessage());
            }
        }
    }

    // ── Iniciar sesión ────────────────────────────────────────
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name']  = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
    $_SESSION['user_type']  = $user['user_type'] ?? 'turista';

    // ── Respuesta de éxito ────────────────────────────────────
    jsonSuccess([
        'user_id'       => $user['id'],
        'first_name'    => $user['first_name'] ?? '',
        'last_name'     => $user['last_name'] ?? '',
        'email'         => $user['email'],
        'avatar_url'    => $user['avatar_url'] ?? $userInfo['picture'] ?? null,
        'auth_provider' => $provider,
        'redirect'      => 'user-dashboard.html',
    ], 'Inicio de sesión con ' . ucfirst($provider) . ' exitoso');

} catch (PDOException $e) {
    // ── Red de seguridad: clave duplicada en INSERT (race condition) ──
    $dupInfo = handleDuplicateKeyException($e);

    if ($dupInfo['is_duplicate']) {
        error_log("[social_login.php] Duplicate key en social login: " . $e->getMessage());
        // En social login, si hay duplicado de email es porque ya existe la cuenta.
        // Intentamos loguear al usuario existente como fallback.
        jsonErrorTyped(
            'Ya existe una cuenta con este correo. Por favor inicia sesión con tu método habitual.',
            $dupInfo['error_type'],
            409,
            'redirect_login'
        );
    }

    error_log('[social_login.php] Database Error: ' . $e->getMessage());
    jsonError('Error al procesar el inicio de sesión social. Inténtalo de nuevo.', 500);

} catch (Exception $e) {
    error_log('[social_login.php] Error: ' . $e->getMessage());
    jsonError('Error al procesar la solicitud', 500);
}


// ════════════════════════════════════════════════════════════════
// FUNCIONES DE VERIFICACIÓN DE TOKENS OAUTH
// ════════════════════════════════════════════════════════════════

/**
 * Verificar token de Google y obtener información del usuario.
 * Decodifica el JWT sin librería externa (solo para obtener payload).
 * En producción con composer, usar google/apiclient para validación completa.
 */
function verifyGoogleToken(string $idToken): ?array
{
    if (empty($idToken)) {
        return null;
    }

    $parts = explode('.', $idToken);
    if (count($parts) !== 3) {
        error_log('[social_login.php] Google token: formato inválido (no tiene 3 partes)');
        return null;
    }

    $payloadJson = base64UrlDecode($parts[1]);
    $payload     = json_decode($payloadJson, true);

    if (!$payload) {
        error_log('[social_login.php] Google token: payload no decodificable');
        return null;
    }

    if (!isset($payload['email'], $payload['sub'])) {
        error_log('[social_login.php] Google token: falta email o sub');
        return null;
    }

    error_log('[social_login.php] Google token decodificado para: ' . $payload['email']);

    return [
        'id'         => $payload['sub'],
        'email'      => $payload['email'],
        'name'       => $payload['name']         ?? null,
        'first_name' => $payload['given_name']   ?? null,
        'last_name'  => $payload['family_name']  ?? null,
        'picture'    => $payload['picture']       ?? null,
    ];
}

/**
 * Helper: decodificar base64url (formato JWT).
 */
function base64UrlDecode(string $str): string
{
    $str = str_replace(['-', '_'], ['+', '/'], $str);
    $pad = strlen($str) % 4;
    if ($pad) {
        $str .= str_repeat('=', 4 - $pad);
    }
    return base64_decode($str);
}

/**
 * Verificar token de Facebook y obtener información del usuario.
 */
function verifyFacebookToken(string $accessToken): ?array
{
    if (empty($accessToken)) {
        return null;
    }

    $appId     = '2139007790253777';
    $appSecret = 'd3e51bed1c8f2f043efa87d8536e0e56';

    $url      = 'https://graph.facebook.com/me?fields=id,name,email,first_name,last_name,picture&access_token=' . urlencode($accessToken);
    $response = @file_get_contents($url);

    if (!$response) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    if (!$response) {
        error_log('[social_login.php] Facebook: sin respuesta del API');
        return null;
    }

    $fbData = json_decode($response, true);

    if (!$fbData || isset($fbData['error'])) {
        error_log('[social_login.php] Facebook error: ' . ($fbData['error']['message'] ?? 'desconocido'));
        return null;
    }

    // Verificar que el token pertenece a nuestra app
    $debugUrl = 'https://graph.facebook.com/debug_token?input_token='
        . urlencode($accessToken)
        . '&access_token=' . urlencode($appId . '|' . $appSecret);

    $debugResponse = @file_get_contents($debugUrl);
    if ($debugResponse) {
        $debugData = json_decode($debugResponse, true);
        if (isset($debugData['data']['app_id']) && $debugData['data']['app_id'] !== $appId) {
            error_log('[social_login.php] Facebook: app_id no coincide');
            return null;
        }
    }

    return [
        'id'         => $fbData['id'],
        'email'      => $fbData['email']                      ?? null,
        'name'       => $fbData['name']                       ?? null,
        'first_name' => $fbData['first_name']                 ?? null,
        'last_name'  => $fbData['last_name']                  ?? null,
        'picture'    => $fbData['picture']['data']['url']     ?? null,
    ];
}
