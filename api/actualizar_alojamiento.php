<?php
/**
 * API Endpoint: Actualizar Alojamiento (para propietarios)
 * POST /api/actualizar_alojamiento.php
 */

session_start();
require_once 'config.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

// Verificar que el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    jsonError('No has iniciado sesión', 401);
}

$userId = $_SESSION['user_id'];

try {
    // Obtener datos
    $data = $_POST;

    if (empty($data) || !isset($data['id'])) {
        jsonError('Faltan datos', 400);
    }

    $id = intval($data['id']);
    $submitAction = $data['submit_action'] ?? 'draft';

    $pdo = getDBConnection();

    // Verificar que el usuario es propietario
    $stmt = $pdo->prepare("SELECT * FROM accommodations WHERE id = ? AND created_by = ?");
    $stmt->execute([$id, $userId]);
    $accommodation = $stmt->fetch();

    if (!$accommodation) {
        jsonError('No tienes permiso para editar este alojamiento', 403);
    }

    // Determinar nuevo estado de moderación
    if ($submitAction === 'submit') {
        // Guardar valores anteriores para cambios pendientes
        $previousData = [
            'name' => $accommodation['name'],
            'description' => $accommodation['description'],
            'municipality' => $accommodation['municipality'],
            'province' => $accommodation['province'],
            'address' => $accommodation['address'],
            'capacity' => $accommodation['capacity'],
            'price_per_night' => $accommodation['price_per_night'],
            'phone' => $accommodation['phone'],
            'website' => $accommodation['website'],
            'photo1' => $accommodation['photo1'],
            'photo2' => $accommodation['photo2'],
            'photo3' => $accommodation['photo3'],
            'photo4' => $accommodation['photo4']
        ];

        // Si ya está aprobado, marcar como having pending changes
        if ($accommodation['moderation_status'] === 'approved') {
            $newStatus = 'approved';
            $hasPendingChanges = 1;
        } else {
            $newStatus = 'pending';
            $hasPendingChanges = 0;
        }
    } else {
        // Guardar como borrador
        $newStatus = 'draft';
        $hasPendingChanges = 0;
    }

    // Actualizar datos
    $sql = "UPDATE accommodations SET 
            name = :name,
            description = :description,
            municipality = :municipality,
            province = :province,
            address = :address,
            postal_code = :postal_code,
            capacity = :capacity,
            price_per_night = :price_per_night,
            phone = :phone,
            website = :website,
            latitude = :latitude,
            longitude = :longitude,
            photo1 = :photo1,
            photo2 = :photo2,
            photo3 = :photo3,
            photo4 = :photo4,
            moderation_status = :moderation_status,
            has_pending_changes = :has_pending_changes,
            last_submitted_at = :last_submitted_at
            WHERE id = :id AND created_by = :user_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $data['name'] ?? $accommodation['name'],
        ':description' => $data['description'] ?? $accommodation['description'],
        ':municipality' => $data['municipality'] ?? $accommodation['municipality'],
        ':province' => $data['province'] ?? $accommodation['province'],
        ':address' => $data['address'] ?? $accommodation['address'],
        ':postal_code' => $data['postal_code'] ?? $accommodation['postal_code'],
        ':capacity' => intval($data['capacity'] ?? $accommodation['capacity']),
        ':price_per_night' => floatval($data['price_per_night'] ?? $accommodation['price_per_night']),
        ':phone' => $data['phone'] ?? $accommodation['phone'],
        ':website' => $data['website'] ?? $accommodation['website'],
        ':latitude' => $data['latitude'] ?? $accommodation['latitude'],
        ':longitude' => $data['longitude'] ?? $accommodation['longitude'],
        ':photo1' => $data['photo1'] ?? $accommodation['photo1'],
        ':photo2' => $data['photo2'] ?? $accommodation['photo2'],
        ':photo3' => $data['photo3'] ?? $accommodation['photo3'],
        ':photo4' => $data['photo4'] ?? $accommodation['photo4'],
        ':moderation_status' => $newStatus,
        ':has_pending_changes' => $hasPendingChanges,
        ':last_submitted_at' => ($submitAction === 'submit') ? date('Y-m-d H:i:s') : $accommodation['last_submitted_at'],
        ':id' => $id,
        ':user_id' => $userId
    ]);

    // Registrar en historial
    try {
        $historyStmt = $pdo->prepare("
            INSERT INTO accommodation_moderation_history 
                (accommodation_id, action, performed_by, new_status, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        $historyAction = ($submitAction === 'submit') ? 'submitted' : 'updated';
        $historyStmt->execute([
            $id, 
            $historyAction, 
            $userId, 
            $newStatus,
            $submitAction === 'submit' ? 'Enviado para revisión desde página de usuario' : 'Guardado como borrador'
        ]);
    } catch (PDOException $e) {
        error_log('Error al registrar historial: ' . $e->getMessage());
    }

    $message = ($submitAction === 'submit') 
        ? 'Alojamiento enviado para revisión' 
        : 'Cambios guardados como borrador';

    jsonSuccess([
        'id' => $id,
        'moderation_status' => $newStatus,
        'has_pending_changes' => $hasPendingChanges
    ], $message);

} catch (PDOException $e) {
    error_log('Actualizar.php - Error: ' . $e->getMessage());
    jsonError('Error al guardar: ' . $e->getMessage(), 500);
}
