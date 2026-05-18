<?php
/**
 * API: Guardar consulta general de turista (sin destinatario específico)
 * POST /api/submit_inquiry.php
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método POST requerido', 405);
}

$userId = $_SESSION['user_id'];
$pdo    = getDBConnection();

// Crear tabla si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS general_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    zone VARCHAR(255) DEFAULT NULL,
    template_type VARCHAR(100) DEFAULT 'general',
    status ENUM('pending','read','replied') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$data = json_decode(file_get_contents('php://input'), true);
$message      = sanitizeInput($data['message']      ?? '');
$zone         = sanitizeInput($data['zone']         ?? '');
$templateType = sanitizeInput($data['template_type'] ?? 'general');

if (!$message) {
    jsonError('El mensaje no puede estar vacío', 400);
}

$stmt = $pdo->prepare("
    INSERT INTO general_inquiries (user_id, message, zone, template_type)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$userId, $message, $zone ?: null, $templateType]);

jsonSuccess(['inquiry_id' => $pdo->lastInsertId()], 'Consulta registrada correctamente');
?>
