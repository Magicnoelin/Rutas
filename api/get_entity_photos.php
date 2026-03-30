<?php
/**
 * API: Obtener fotos aprobadas de una entidad
 * GET /api/get_entity_photos.php?entity_type=places_of_interest&entity_id=42
 *
 * No requiere autenticación (fotos públicas aprobadas)
 * Devuelve fotos agrupadas por categoría
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$entityType = trim($_GET['entity_type'] ?? '');
$entityId   = (int)($_GET['entity_id'] ?? 0);

$validTypes = ['accommodations', 'places_of_interest', 'cultural_events', 'activities'];
if (!in_array($entityType, $validTypes) || $entityId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parámetros no válidos']);
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("
        SELECT 
            ep.id,
            ep.file_url,
            ep.file_path,
            ep.category,
            ep.caption,
            ep.alt_text,
            ep.author_name,
            ep.author_instagram,
            ep.featured,
            ep.is_cover,
            ep.taken_at,
            ep.uploaded_at,
            ep.permission_status,
            ep.source,
            u.first_name,
            u.last_name
        FROM entity_photos ep
        LEFT JOIN users u ON ep.author_id = u.id
        WHERE ep.entity_type = ?
          AND ep.entity_id = ?
          AND ep.permission_status = 'approved'
          AND ep.status = 'active'
        ORDER BY ep.featured DESC, ep.is_cover DESC, ep.uploaded_at DESC
    ");
    $stmt->execute([$entityType, $entityId]);
    $photos = $stmt->fetchAll();

    // Agrupar por categoría
    $byCategory = [];
    $coverPhoto = null;
    $featured   = [];

    foreach ($photos as $photo) {
        $cat = $photo['category'] ?? 'otro';

        // Construir URL pública si file_url está vacío
        if (empty($photo['file_url']) && !empty($photo['file_path'])) {
            // Convertir ruta absoluta a relativa
            $photo['file_url'] = '/' . ltrim(str_replace('\\', '/', $photo['file_path']), '/');
        }

        if ($photo['is_cover']) {
            $coverPhoto = $photo;
        }
        if ($photo['featured']) {
            $featured[] = $photo;
        }

        if (!isset($byCategory[$cat])) {
            $byCategory[$cat] = [];
        }
        $byCategory[$cat][] = $photo;
    }

    echo json_encode([
        'success'     => true,
        'data'        => [
            'total'       => count($photos),
            'cover'       => $coverPhoto,
            'featured'    => $featured,
            'by_category' => $byCategory,
            'all'         => $photos,
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
