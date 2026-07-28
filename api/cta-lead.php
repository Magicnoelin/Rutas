<?php
/**
 * api/cta-lead.php — Captura de leads desde el CTA de lugar de interés
 *
 * Recibe: { email, provincia, municipio, lugar, llegada, salida, personas, ref }
 * Guarda el lead en la tabla `cta_leads` (la crea si no existe).
 * No requiere autenticación — es un endpoint público ligero.
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Leer JSON del body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$email = trim($data['email'] ?? '');

// Validar email mínimamente
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Email inválido']);
    exit;
}

// Sanitizar el resto de campos
$provincia = mb_substr(trim($data['provincia'] ?? ''), 0, 100);
$municipio = mb_substr(trim($data['municipio'] ?? ''), 0, 100);
$lugar     = mb_substr(trim($data['lugar']     ?? ''), 0, 200);
$llegada   = trim($data['llegada']  ?? '');
$salida    = trim($data['salida']   ?? '');
$personas  = (int)($data['personas'] ?? 2);
$ref       = mb_substr(trim($data['ref'] ?? 'cta'), 0, 50);
$ip        = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ip        = mb_substr(explode(',', $ip)[0], 0, 45); // Solo la primera IP, IPv6 max 45 chars

// Validar fechas
$llegadaDate = null;
$salidaDate  = null;
if ($llegada && preg_match('/^\d{4}-\d{2}-\d{2}$/', $llegada)) $llegadaDate = $llegada;
if ($salida  && preg_match('/^\d{4}-\d{2}-\d{2}$/', $salida))  $salidaDate  = $salida;

try {
    require_once __DIR__ . '/config.php';
    $pdo = getDBConnection();

    // Crear tabla si no existe (primera vez)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cta_leads (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email        VARCHAR(254) NOT NULL,
            provincia    VARCHAR(100) DEFAULT NULL,
            municipio    VARCHAR(100) DEFAULT NULL,
            lugar        VARCHAR(200) DEFAULT NULL,
            llegada      DATE         DEFAULT NULL,
            salida       DATE         DEFAULT NULL,
            personas     TINYINT UNSIGNED DEFAULT 2,
            ref          VARCHAR(50)  DEFAULT 'cta',
            ip           VARCHAR(45)  DEFAULT NULL,
            created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_provincia (provincia),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Insertar — si el mismo email ya existe hoy para el mismo lugar, actualizar en lugar de duplicar
    $stmt = $pdo->prepare("
        INSERT INTO cta_leads (email, provincia, municipio, lugar, llegada, salida, personas, ref, ip)
        VALUES (:email, :provincia, :municipio, :lugar, :llegada, :salida, :personas, :ref, :ip)
        ON DUPLICATE KEY UPDATE
            llegada    = VALUES(llegada),
            salida     = VALUES(salida),
            personas   = VALUES(personas),
            created_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        ':email'     => $email,
        ':provincia' => $provincia ?: null,
        ':municipio' => $municipio ?: null,
        ':lugar'     => $lugar     ?: null,
        ':llegada'   => $llegadaDate,
        ':salida'    => $salidaDate,
        ':personas'  => $personas,
        ':ref'       => $ref,
        ':ip'        => $ip ?: null,
    ]);

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    // Registrar error en log del servidor, no exponer detalles al cliente
    error_log('[cta-lead] ' . $e->getMessage());
    // Devolver ok igualmente — no queremos bloquear la redirección del usuario
    echo json_encode(['ok' => true, 'note' => 'saved_locally']);
}
