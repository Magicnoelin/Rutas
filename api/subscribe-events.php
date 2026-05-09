<?php
/**
 * API Endpoint: Suscripción a newsletter de eventos similares
 * POST /api/subscribe-events.php
 * 
 * Body (JSON):
 *   - email: string (obligatorio)
 *   - categoria: string (opcional)
 *   - province: string (opcional)
 *   - source_slug: string (slug del evento donde se suscribió)
 *   - source_event_id: int (ID del evento, opcional)
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

    if (!$data || empty($data['email'])) {
        jsonError('Email es obligatorio', 400);
    }

    $email = trim($data['email']);

    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Email no válido', 400);
    }

    $categoria     = $data['categoria'] ?? null;
    $province      = $data['province'] ?? null;
    $source_slug   = $data['source_slug'] ?? null;
    $source_event_id = isset($data['source_event_id']) ? intval($data['source_event_id']) : null;

    $pdo = getDBConnection();

    // Crear la tabla si no existe (por si acaso)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS event_newsletter_subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            categoria VARCHAR(100) NULL,
            province VARCHAR(100) NULL,
            source_slug VARCHAR(255) NULL,
            source_event_id INT NULL,
            subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT(1) DEFAULT 1,
            unsubscribed_at TIMESTAMP NULL,
            INDEX idx_email (email),
            INDEX idx_categoria (categoria),
            INDEX idx_province (province),
            INDEX idx_source_slug (source_slug),
            INDEX idx_is_active (is_active),
            UNIQUE KEY uk_email_event (email, source_slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Verificar si ya existe una suscripción activa para este email + slug
    $stmtCheck = $pdo->prepare("
        SELECT id, is_active FROM event_newsletter_subscribers
        WHERE email = ? AND source_slug = ?
    ");
    $stmtCheck->execute([$email, $source_slug]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['is_active'] == 1) {
            // Ya está suscrito a este evento, devolver éxito silencioso
            jsonSuccess([
                'email' => $email,
                'already_subscribed' => true,
                'source_slug' => $source_slug,
            ], 'Ya estás suscrito a eventos similares a este');
        } else {
            // Reactivar suscripción
            $stmtReactivate = $pdo->prepare("
                UPDATE event_newsletter_subscribers
                SET is_active = 1, unsubscribed_at = NULL, subscribed_at = NOW()
                WHERE id = ?
            ");
            $stmtReactivate->execute([$existing['id']]);

            jsonSuccess([
                'email' => $email,
                'reactivated' => true,
                'source_slug' => $source_slug,
            ], 'Suscripción reactivada correctamente');
        }
    } else {
        // Insertar nueva suscripción
        $stmtInsert = $pdo->prepare("
            INSERT INTO event_newsletter_subscribers
                (email, categoria, province, source_slug, source_event_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$email, $categoria, $province, $source_slug, $source_event_id]);

        jsonSuccess([
            'email' => $email,
            'subscribed' => true,
            'source_slug' => $source_slug,
        ], 'Suscripción confirmada correctamente');
    }

} catch (PDOException $e) {
    error_log('subscribe-events.php - Database Error: ' . $e->getMessage());
    jsonError('Error al procesar la suscripción', 500);
} catch (Exception $e) {
    error_log('subscribe-events.php - Error: ' . $e->getMessage());
    jsonError('Error interno del servidor', 500);
}
