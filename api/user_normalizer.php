<?php
/**
 * ============================================================
 * user_normalizer.php — Normalización y Unicidad de Usuario
 * Proyecto: Rutas Rurales (rutasrurales.io)
 * ============================================================
 *
 * Proporciona funciones para:
 *   1. Normalizar email (minúsculas + trim) sin corromper con htmlspecialchars
 *   2. Normalizar teléfono (solo dígitos + prefijo +)
 *   3. Verificar unicidad de email en BD (con opción de excluir userId actual)
 *   4. Verificar unicidad de teléfono en BD
 *   5. Interpretar PDOException código 23000 (clave duplicada) con mensajes amigables
 *
 * INCLUSIÓN: require_once 'user_normalizer.php'; en register.php,
 *            login.php y social_login.php.
 * ============================================================
 */

// ── 1. NORMALIZACIÓN DE EMAIL ─────────────────────────────────

/**
 * Normaliza un email: quita espacios y convierte a minúsculas.
 *
 * NO usa htmlspecialchars() porque eso corrompería emails con
 * caracteres especiales válidos (e.g. o'brien@domain.com).
 * La sanitización contra XSS del email se hace al mostrarlo en HTML,
 * no al almacenarlo.
 *
 * @param  string $email  Email recibido del formulario/API.
 * @return string         Email normalizado o cadena vacía si la entrada es inválida.
 */
function normalizeEmail(string $email): string
{
    return strtolower(trim($email));
}

/**
 * Valida y normaliza un email en un solo paso.
 *
 * @param  string      $email  Email a validar.
 * @return string|false        Email normalizado y válido, o false si es inválido.
 */
function validateAndNormalizeEmail(string $email): string|false
{
    $normalized = normalizeEmail($email);
    // filter_var también aplica trim internamente, pero lo hacemos antes
    return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : false;
}


// ── 2. NORMALIZACIÓN DE TELÉFONO ─────────────────────────────

/**
 * Normaliza un número de teléfono:
 *   - Elimina espacios, guiones, puntos y paréntesis.
 *   - Conserva el prefijo '+' al inicio si lo tiene.
 *   - Devuelve null si la entrada está vacía o es solo separadores.
 *
 * Ejemplos:
 *   "+34 605 249 696"   →  "+34605249696"
 *   "605-249-696"       →  "605249696"
 *   "605 24 96 96"      →  "605249696"
 *   "(+34) 605.249.696" →  "+34605249696"
 *   ""                  →  null
 *   null                →  null
 *
 * @param  string|null $phone  Teléfono recibido del formulario/API.
 * @return string|null         Teléfono normalizado o null.
 */
function normalizePhone(?string $phone): ?string
{
    if ($phone === null || trim($phone) === '') {
        return null;
    }

    $phone = trim($phone);

    // Conservar el '+' inicial si existe (prefijo internacional)
    $hasPlus = (str_starts_with($phone, '+'));

    // Eliminar todos los caracteres que no sean dígitos
    $digits = preg_replace('/[^\d]/', '', $phone);

    if ($digits === '' || $digits === null) {
        return null;
    }

    return $hasPlus ? '+' . $digits : $digits;
}


// ── 3. VERIFICACIÓN DE UNICIDAD DE EMAIL ─────────────────────

/**
 * Comprueba si el email ya existe en la tabla users.
 *
 * Devuelve un array con:
 *   - 'exists'      => bool   Si el email ya está registrado.
 *   - 'user'        => array|null  Datos del usuario existente (si existe).
 *   - 'is_social'   => bool   Si el usuario existente se registró vía OAuth.
 *
 * @param  PDO         $pdo            Conexión activa a la BD.
 * @param  string      $email          Email normalizado a verificar.
 * @param  int|null    $excludeUserId  ID de usuario a excluir (útil en actualizaciones de perfil).
 * @return array                       Resultado de la verificación.
 */
function checkEmailExists(PDO $pdo, string $email, ?int $excludeUserId = null): array
{
    $sql = "SELECT id, first_name, last_name, email, status, auth_provider,
                   google_id, facebook_id, user_type
            FROM users
            WHERE email = :email";

    $params = [':email' => $email];

    if ($excludeUserId !== null) {
        $sql .= " AND id <> :exclude_id";
        $params[':exclude_id'] = $excludeUserId;
    }

    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return [
            'exists'    => false,
            'user'      => null,
            'is_social' => false,
        ];
    }

    $isSocial = !empty($user['auth_provider'])
        && in_array($user['auth_provider'], ['google', 'facebook'], true);

    return [
        'exists'    => true,
        'user'      => $user,
        'is_social' => $isSocial,
    ];
}


// ── 4. VERIFICACIÓN DE UNICIDAD DE TELÉFONO ──────────────────

/**
 * Comprueba si el teléfono ya está asignado a OTRA cuenta.
 *
 * Devuelve true si el teléfono está ocupado por un usuario distinto.
 * Los valores NULL no se comprueban (teléfono es opcional).
 *
 * @param  PDO         $pdo            Conexión activa a la BD.
 * @param  string|null $phone          Teléfono normalizado a verificar.
 * @param  int|null    $excludeUserId  ID del usuario a excluir (propio usuario en actualizaciones).
 * @return bool                        true si el teléfono está ocupado por otro usuario.
 */
function checkPhoneExists(PDO $pdo, ?string $phone, ?int $excludeUserId = null): bool
{
    // Si el teléfono es null o vacío, no hay conflicto
    if ($phone === null || $phone === '') {
        return false;
    }

    $sql = "SELECT id FROM users WHERE phone = :phone";
    $params = [':phone' => $phone];

    if ($excludeUserId !== null) {
        $sql .= " AND id <> :exclude_id";
        $params[':exclude_id'] = $excludeUserId;
    }

    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch() !== false;
}


// ── 5. MANEJO DE PDOException 23000 (CLAVE DUPLICADA) ────────

/**
 * Interpreta una PDOException de clave duplicada (SQLSTATE 23000)
 * y determina qué campo causó el conflicto.
 *
 * Actúa como red de seguridad cuando los checks previos fallan por
 * condición de carrera (race condition) o cualquier otra causa.
 *
 * @param  PDOException $e  La excepción capturada.
 * @return array {
 *     'is_duplicate' => bool,
 *     'field'        => 'email'|'phone'|'unknown',
 *     'message'      => string   Mensaje amigable para el frontend.
 *     'error_type'   => string   Clave para que el JS identifique el error.
 * }
 */
function handleDuplicateKeyException(PDOException $e): array
{
    $sqlState  = $e->getCode();         // '23000' para integridad referencial/única
    $errorCode = $e->errorInfo[1] ?? 0; // 1062 = Duplicate entry en MySQL

    // Solo manejamos errores de clave duplicada
    $isDuplicate = ($sqlState === '23000' || $errorCode === 1062);

    if (!$isDuplicate) {
        return [
            'is_duplicate' => false,
            'field'        => 'unknown',
            'message'      => 'Error interno en la base de datos.',
            'error_type'   => 'db_error',
        ];
    }

    // Intentar detectar qué campo causó el duplicado
    // El mensaje de MySQL incluye el nombre del índice: "Duplicate entry 'X' for key 'uq_users_email'"
    $mysqlMessage = $e->getMessage();

    if (stripos($mysqlMessage, 'uq_users_email') !== false
        || stripos($mysqlMessage, "'email'") !== false
    ) {
        return [
            'is_duplicate' => true,
            'field'        => 'email',
            'message'      => 'Este correo electrónico ya está registrado. ¿Quieres iniciar sesión?',
            'error_type'   => 'email_exists',
        ];
    }

    if (stripos($mysqlMessage, 'uq_users_phone') !== false
        || stripos($mysqlMessage, "'phone'") !== false
    ) {
        return [
            'is_duplicate' => true,
            'field'        => 'phone',
            'message'      => 'Este número de teléfono ya está asociado a otra cuenta.',
            'error_type'   => 'phone_exists',
        ];
    }

    // Duplicado en campo desconocido
    return [
        'is_duplicate' => true,
        'field'        => 'unknown',
        'message'      => 'Ya existe un registro con estos datos. Por favor revisa el formulario.',
        'error_type'   => 'duplicate_entry',
    ];
}


// ── 6. RESPUESTAS JSON ENRIQUECIDAS PARA EL FRONTEND ─────────

/**
 * Emite un error JSON enriquecido con error_type para que el
 * frontend (JS/AJAX) pueda diferenciar el tipo de error y
 * mostrar el mensaje y la acción adecuados.
 *
 * Estructura de respuesta:
 * {
 *   "success": false,
 *   "error": "Mensaje legible para el usuario",
 *   "error_type": "email_exists" | "phone_exists" | "validation_error" | ...,
 *   "action": "redirect_login" | null   (opcional, guía al frontend)
 * }
 *
 * @param  string      $message    Mensaje de error para el usuario.
 * @param  string      $errorType  Clave de tipo de error.
 * @param  int         $httpCode   Código HTTP de respuesta.
 * @param  string|null $action     Acción sugerida al frontend (opcional).
 * @return never                   Termina la ejecución con la respuesta JSON.
 */
function jsonErrorTyped(
    string $message,
    string $errorType,
    int $httpCode = 400,
    ?string $action = null
): never {
    http_response_code($httpCode);
    $response = [
        'success'    => false,
        'error'      => $message,
        'error_type' => $errorType,
    ];
    if ($action !== null) {
        $response['action'] = $action;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}
