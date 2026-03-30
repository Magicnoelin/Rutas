<?php
/**
 * API: Crear Oferta Pública
 * POST /api/create_public_offer.php
 * Body: {
 *   accommodation_id: int,
 *   subject: string,
 *   start_date: 'YYYY-MM-DD',
 *   end_date: 'YYYY-MM-DD',
 *   price: float,
 *   message: string,
 *   currency: string,
 *   province: string,
 *   municipality: string
 * }
 */

session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión para publicar ofertas', 401);
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        jsonError('Datos JSON inválidos', 400);
    }

    // Validar campos requeridos
    $requiredFields = ['accommodation_id', 'subject', 'start_date', 'end_date', 'price', 'message', 'province', 'municipality'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            jsonError("El campo '$field' es requerido", 400);
        }
    }

    $userId = $_SESSION['user_id'];
    $accommodationId = (int)$data['accommodation_id'];
    $subject = sanitizeInput($data['subject']);
    $startDate = sanitizeInput($data['start_date']);
    $endDate = sanitizeInput($data['end_date']);
    $price = (float)$data['price'];
    $message = sanitizeInput($data['message']);
    $currency = isset($data['currency']) ? sanitizeInput($data['currency']) : 'EUR';
    $province = sanitizeInput($data['province']);
    $municipality = sanitizeInput($data['municipality']);

    // Validar fechas
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        jsonError('Formato de fecha inválido. Usa YYYY-MM-DD', 400);
    }

    if (strtotime($startDate) > strtotime($endDate)) {
        jsonError('La fecha de inicio no puede ser posterior a la fecha de fin', 400);
    }

    if (strtotime($startDate) < strtotime(date('Y-m-d'))) {
        jsonError('La fecha de inicio no puede ser en el pasado', 400);
    }

    if ($price <= 0) {
        jsonError('El precio debe ser mayor que cero', 400);
    }

    $pdo = getDBConnection();

    // Verificar que el usuario es propietario del alojamiento
    $stmtCheck = $pdo->prepare("
        SELECT a.id, a.name, a.slug, a.province, a.municipality
        FROM accommodations a
        JOIN user_resources ur ON a.id = ur.resource_id
        WHERE a.id = ?
        AND ur.user_id = ?
        AND ur.resource_type = 'accommodation'
        AND ur.role = 'owner'
    ");
    $stmtCheck->execute([$accommodationId, $userId]);
    $accommodation = $stmtCheck->fetch();

    if (!$accommodation) {
        jsonError('No tienes permisos para publicar ofertas con este alojamiento', 403);
    }

    // Verificar permisos para publicar ofertas
    $stmtPerm = $pdo->prepare("
        SELECT can_send_offers
        FROM chat_permissions
        WHERE initiator_type = 'gestor'
        AND initiator_membership = (SELECT membership_type FROM users WHERE id = ?)
        AND recipient_type = 'turista'
        AND (recipient_membership = 'any' OR recipient_membership = 'free' OR recipient_membership = 'premium')
        AND is_active = TRUE
        LIMIT 1
    ");
    $stmtPerm->execute([$userId]);
    $permission = $stmtPerm->fetch();

    if (!$permission || !$permission['can_send_offers']) {
        jsonError('Tu membresía no permite publicar ofertas', 403);
    }

    // Crear tabla de ofertas públicas si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS public_offers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        accommodation_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        currency CHAR(3) DEFAULT 'EUR',
        message TEXT,
        province VARCHAR(100) NOT NULL,
        municipality VARCHAR(100) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE,
        INDEX (province),
        INDEX (municipality),
        INDEX (is_active),
        INDEX (start_date),
        INDEX (end_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Insertar oferta pública
    $stmtInsert = $pdo->prepare("
        INSERT INTO public_offers
        (user_id, accommodation_id, subject, start_date, end_date, price, currency, message, province, municipality)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([
        $userId, $accommodationId, $subject, $startDate, $endDate, $price, $currency, $message, $province, $municipality
    ]);

    $offerId = $pdo->lastInsertId();

    // Obtener la oferta creada
    $stmtGet = $pdo->prepare("
        SELECT po.*, a.name as accommodation_name, a.slug as accommodation_slug
        FROM public_offers po
        JOIN accommodations a ON po.accommodation_id = a.id
        WHERE po.id = ?
    ");
    $stmtGet->execute([$offerId]);
    $offer = $stmtGet->fetch();

    // Respuesta exitosa
    jsonSuccess([
        'offer_id' => $offerId,
        'accommodation' => [
            'id' => $offer['accommodation_id'],
            'name' => $offer['accommodation_name'],
            'slug' => $offer['accommodation_slug']
        ],
        'offer_details' => [
            'subject' => $offer['subject'],
            'start_date' => $offer['start_date'],
            'end_date' => $offer['end_date'],
            'price' => $offer['price'],
            'currency' => $offer['currency'],
            'message' => $offer['message'],
            'province' => $offer['province'],
            'municipality' => $offer['municipality'],
            'is_active' => (bool)$offer['is_active']
        ],
        'message' => 'Oferta pública creada correctamente. Los turistas podrán verla en el sistema.'
    ], 'Oferta pública creada con éxito');

} catch (PDOException $e) {
    error_log('create_public_offer.php Error: ' . $e->getMessage());
    jsonError('Error al crear la oferta pública: ' . $e->getMessage(), 500);
}