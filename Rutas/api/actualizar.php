<?php
/**
 * API Endpoint: Actualizar Alojamiento
 * POST /api/actualizar.php
 * Body: JSON con los datos actualizados del alojamiento
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

    if (!$data || !isset($data['id'])) {
        jsonError('Datos inválidos o ID faltante', 400);
    }

    $pdo = getDBConnection();

    // Verificar que el alojamiento existe
    $stmt = $pdo->prepare("SELECT id FROM accommodations WHERE id = ?");
    $stmt->execute([$data['id']]);
    if (!$stmt->fetch()) {
        jsonError('Alojamiento no encontrado', 404);
    }

    // Preparar datos para actualizar
    $updateData = [];
    $params = [];

    // Campos que se pueden actualizar
    $camposPermitidos = [
        'name' => 'name',
        'accommodation_type' => 'accommodation_type',
        'capacity' => 'capacity',
        'province' => 'province',
        'municipality' => 'municipality',
        'address' => 'address',
        'price_per_night' => 'price_per_night',
        'phone' => 'phone',
        'email' => 'email',
        'description' => 'description'
    ];

    foreach ($camposPermitidos as $campoJson => $campoDB) {
        if (isset($data[$campoJson])) {
            $updateData[] = "$campoDB = ?";
            $params[] = $data[$campoJson];
        }
    }

    // Manejar array de fotos si se proporciona
    if (isset($data['photos']) && is_array($data['photos'])) {
        // Limpiar campos de fotos existentes
        for ($i = 1; $i <= 4; $i++) {
            $updateData[] = "photo$i = ?";
            $params[] = null; // NULL para limpiar
        }

        // Asignar nuevas fotos a los primeros campos disponibles
        foreach ($data['photos'] as $index => $photoUrl) {
            if ($index < 4 && !empty($photoUrl)) {
                $photoIndex = $index + 1;
                // Reemplazar el NULL con la URL real
                $params[count($params) - (4 - $index)] = $photoUrl;
            }
        }
    }

    // Agregar timestamp de actualización
    $updateData[] = "updated_at = CURRENT_TIMESTAMP";
    $params[] = $data['id']; // ID al final para el WHERE

    if (empty($updateData)) {
        jsonError('No hay campos para actualizar', 400);
    }

    // Construir y ejecutar query de actualización
    $sql = "UPDATE accommodations SET " . implode(', ', $updateData) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($params)) {
        jsonSuccess(['id' => $data['id']], 'Alojamiento actualizado correctamente');
    } else {
        jsonError('Error al actualizar alojamiento', 500);
    }

} catch (PDOException $e) {
    jsonError('Error al actualizar alojamiento: ' . $e->getMessage(), 500);
}
