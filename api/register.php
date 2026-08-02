<?php
/**
 * API Endpoint: Registro de Usuario
 * POST /api/register.php
 * Body: JSON con los datos del usuario
 *
 * Cambios v2 (2026-01-08):
 *   - Email normalizado a minúsculas (evita duplicados por variación de case)
 *   - Teléfono normalizado (elimina espacios, guiones, puntos, paréntesis)
 *   - Verificación explícita de unicidad de teléfono antes del INSERT
 *   - Respuestas JSON enriquecidas con error_type para el frontend
 *   - Catch PDOException 23000 como red de seguridad final
 */

require_once 'config.php';
require_once 'user_normalizer.php';

// Iniciar sesión al principio
session_start();

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

    // ── Validar reCAPTCHA (temporalmente deshabilitado para debugging) ──
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

    // ── Validar campos requeridos ─────────────────────────────
    $camposRequeridos = ['firstName', 'lastName', 'email', 'password'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
            jsonError("El campo '$campo' es requerido", 400);
        }
    }

    // ── Normalizar y validar email ────────────────────────────
    // IMPORTANTE: usamos normalizeEmail() en lugar de sanitizeInput() para el email.
    // sanitizeInput() aplica htmlspecialchars(), que corrompería emails con
    // caracteres especiales válidos. El email solo necesita trim + strtolower.
    $emailNormalizado = validateAndNormalizeEmail($data['email']);
    if ($emailNormalizado === false) {
        jsonError('El formato del email no es válido', 400);
    }

    // ── Validar contraseña ────────────────────────────────────
    if (strlen($data['password']) < 8) {
        jsonError('La contraseña debe tener al menos 8 caracteres', 400);
    }

    if ($data['password'] !== ($data['confirmPassword'] ?? '')) {
        jsonError('Las contraseñas no coinciden', 400);
    }

    // ── Verificar aceptación de términos ──────────────────────
    if (!isset($data['terms']) || $data['terms'] !== true) {
        jsonError('Debes aceptar los términos y condiciones', 400);
    }

    // ── Normalizar teléfono (opcional) ────────────────────────
    $telefonoNormalizado = normalizePhone($data['phone'] ?? null);

    // ── Sanitizar el resto de campos de texto ─────────────────
    // Para nombre y apellido sí usamos sanitizeInput() (puede tener HTML malicioso)
    $firstName = sanitizeInput($data['firstName']);
    $lastName  = sanitizeInput($data['lastName']);
    $userType  = sanitizeInput($data['userType'] ?? 'turista');

    // ── Conectar a la BD ──────────────────────────────────────
    $pdo = getDBConnection();

    if (!$pdo) {
        jsonError('Error de conexión a la base de datos', 500);
    }

    // ── Verificar que la tabla users existe ───────────────────
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() === 0) {
        jsonError('La tabla users no existe en la base de datos', 500);
    }

    // ────────────────────────────────────────────────────────────
    // CHECK 1: Unicidad del EMAIL
    // Comprobamos ANTES del INSERT para dar un mensaje diferenciado:
    //   - Si el email existe y es registro normal → error "ya tienes cuenta"
    //   - Si el email existe y tiene OAuth vinculado → informamos del método de login
    // ────────────────────────────────────────────────────────────
    $emailCheck = checkEmailExists($pdo, $emailNormalizado);

    if ($emailCheck['exists']) {
        $usuarioExistente = $emailCheck['user'];

        // Determinar mensaje según si tiene OAuth vinculado
        if ($emailCheck['is_social']) {
            $proveedorNombre = ucfirst($usuarioExistente['auth_provider']);
            jsonErrorTyped(
                "Ya tienes una cuenta registrada con este correo a través de $proveedorNombre. " .
                "Por favor, inicia sesión con $proveedorNombre.",
                'email_exists_social',
                409,
                'redirect_login'
            );
        } else {
            jsonErrorTyped(
                'Ya existe una cuenta con este correo electrónico. ' .
                '¿Olvidaste tu contraseña? Puedes iniciar sesión o recuperarla.',
                'email_exists',
                409,
                'redirect_login'
            );
        }
    }

    // ────────────────────────────────────────────────────────────
    // CHECK 2: Unicidad del TELÉFONO (solo si se proporcionó)
    // Si el teléfono ya pertenece a OTRA cuenta, bloqueamos el registro.
    // Si es null, lo ignoramos (teléfono es opcional).
    // ────────────────────────────────────────────────────────────
    if ($telefonoNormalizado !== null && checkPhoneExists($pdo, $telefonoNormalizado)) {
        jsonErrorTyped(
            'Este número de teléfono ya está asociado a otra cuenta. ' .
            'Por favor, usa un número diferente o contacta con soporte.',
            'phone_exists',
            409
        );
    }

    // ── Generar hash de contraseña ────────────────────────────
    $passwordHash = password_hash(trim($data['password']), PASSWORD_DEFAULT);

    // ── Generar token de verificación único ───────────────────
    $verificationToken = bin2hex(random_bytes(32));

    // ── Preparar datos para inserción ────────────────────────
    // user_type validado contra lista blanca
    $tiposValidos = ['turista', 'alojamiento', 'promotor_eventos', 'actividad_cultural'];
    $tipoFinal = in_array($userType, $tiposValidos, true) ? $userType : 'turista';

    $userData = [
        'first_name'         => $firstName,
        'last_name'          => $lastName,
        'email'              => $emailNormalizado,   // Normalizado: minúsculas + trim
        'phone'              => $telefonoNormalizado, // Normalizado o NULL
        'user_type'          => $tipoFinal,
        'password_hash'      => $passwordHash,
        'verification_token' => $verificationToken,
        'terms_accepted'     => 1,
        'status'             => 'active',
    ];

    // ── Construir y ejecutar el INSERT ────────────────────────
    $columnas      = array_keys($userData);
    $placeholders  = array_map(fn($col) => ":$col", $columnas);
    $sql           = "INSERT INTO users (" . implode(', ', $columnas) . ")
                      VALUES (" . implode(', ', $placeholders) . ")";

    error_log('[register.php] SQL: ' . $sql);
    error_log('[register.php] Data: ' . json_encode(array_diff_key($userData, ['password_hash' => ''])));

    $stmt = $pdo->prepare($sql);
    foreach ($userData as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    $result = $stmt->execute();

    if (!$result) {
        jsonError('Error al crear la cuenta. Por favor, inténtalo de nuevo.', 500);
    }

    $userId = $pdo->lastInsertId();

    if (!$userId) {
        // Fallback para obtener el último ID
        $userId = $pdo->query("SELECT LAST_INSERT_ID() as id")->fetchColumn();
        if (!$userId) {
            jsonError('Error al obtener el identificador del nuevo usuario.', 500);
        }
    }

    // ── Forzar commit si hay una transacción abierta ──────────
    if ($pdo->inTransaction()) {
        $pdo->commit();
        error_log('[register.php] Transacción confirmada manualmente.');
    }

    // ── Verificar que el usuario se guardó realmente ──────────
    $stmtVerify = $pdo->prepare("SELECT id, email, user_type FROM users WHERE id = :id");
    $stmtVerify->execute([':id' => $userId]);
    $userVerify = $stmtVerify->fetch(PDO::FETCH_ASSOC);

    if (!$userVerify) {
        error_log('[register.php] ERROR: Usuario no encontrado tras INSERT. ID: ' . $userId);
        jsonError('El usuario no se guardó correctamente. Contacta con soporte.', 500);
    }

    error_log('[register.php] Usuario creado: ' . json_encode($userVerify));

    // ── Aquí se enviaría el email de verificación (pendiente) ─
    // sendVerificationEmail($emailNormalizado, $verificationToken);

    // ── Asignar rol en role_user (sistema de roles) ───────────
    try {
        $checkRoles = $pdo->query("SHOW TABLES LIKE 'roles'");
        if ($checkRoles->rowCount() > 0) {
            $stmtRol = $pdo->prepare("SELECT id FROM roles WHERE slug = ?");
            $stmtRol->execute([$tipoFinal]);
            $rolRow = $stmtRol->fetch();

            if ($rolRow) {
                $pdo->prepare("INSERT IGNORE INTO role_user (user_id, role_id) VALUES (?, ?)")
                    ->execute([$userId, $rolRow['id']]);
                error_log("[register.php] Rol '$tipoFinal' asignado al usuario $userId");
            }
        }
    } catch (PDOException $eRol) {
        // No bloquear el registro si el sistema de roles no está instalado
        error_log('[register.php] Aviso rol: ' . $eRol->getMessage());
    }

    // ── Iniciar sesión del nuevo usuario ──────────────────────
    session_regenerate_id(true);
    $_SESSION['user_id']    = $userId;
    $_SESSION['user_email'] = $emailNormalizado;
    $_SESSION['user_name']  = $firstName . ' ' . $lastName;
    $_SESSION['user_type']  = $tipoFinal;

    // ── Respuesta de éxito ────────────────────────────────────
    jsonSuccess([
        'user_id'         => $userId,
        'first_name'      => $firstName,
        'last_name'       => $lastName,
        'email'           => $emailNormalizado,
        'email_verified'  => false,
        'status'          => 'active',
        'recaptcha_score' => $recaptchaResult['score'],
        'redirect_to'     => 'user-dashboard.html',
    ], '¡Cuenta creada exitosamente! Ahora vamos a configurar tus preferencias.');

} catch (PDOException $e) {
    // ────────────────────────────────────────────────────────────
    // RED DE SEGURIDAD: captura errores de clave duplicada (23000)
    // que puedan escapar a los checks anteriores por race condition.
    // ────────────────────────────────────────────────────────────
    $dupInfo = handleDuplicateKeyException($e);

    if ($dupInfo['is_duplicate']) {
        // Error controlado: clave duplicada detectada en la BD
        error_log('[register.php] Duplicate key interceptado: ' . $e->getMessage());
        jsonErrorTyped(
            $dupInfo['message'],
            $dupInfo['error_type'],
            409,
            $dupInfo['field'] === 'email' ? 'redirect_login' : null
        );
    }

    // Error de BD no relacionado con duplicados: log interno, mensaje genérico al usuario
    error_log('[register.php] Database Error: ' . $e->getMessage());
    jsonError('Error al crear la cuenta. Por favor, inténtalo de nuevo más tarde.', 500);
}
