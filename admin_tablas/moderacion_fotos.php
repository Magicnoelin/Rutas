<?php
/**
 * Panel de Moderación de Fotos de Usuarios
 * Modera: entity_photos (permission_status = pending)
 * También gestiona: suggested_entities (status = pending)
 * 
 * NOTA: Este archivo está protegido por .htaccess a nivel de servidor.
 * No requiere verificación de sesión adicional.
 */

session_start();
require_once 'db.php';

// Este archivo está protegido por la autenticación básica de .htaccess
// (igual que el resto de admin_tablas)

// ── Acciones AJAX ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action  = $_POST['action'];
    $id      = (int)($_POST['id'] ?? 0);
    $reason  = trim($_POST['reason'] ?? '');

    try {
        if ($action === 'approve_photo') {
            // 1. Obtener datos de la foto
            $stmtGet = $pdo->prepare("SELECT * FROM entity_photos WHERE id=?");
            $stmtGet->execute([$id]);
            $photo = $stmtGet->fetch();

            if (!$photo) {
                echo json_encode(['success' => false, 'error' => 'Foto no encontrada']);
                exit;
            }

            // 2. Aprobar en entity_photos
            $stmt = $pdo->prepare("UPDATE entity_photos SET permission_status='approved', status='active' WHERE id=?");
            $stmt->execute([$id]);

            // 3. Mover el archivo a carpeta SEO y actualizar la tabla de la entidad
            $moveResult = movePhotoToSeoFolder($pdo, $photo);

            if ($moveResult['moved']) {
                $slotMsg = " → movida a {$moveResult['slot']} ({$moveResult['new_url']})";
                // Actualizar también file_url en entity_photos con la nueva URL
                $stmtUpd = $pdo->prepare("UPDATE entity_photos SET file_url=?, file_path=? WHERE id=?");
                $stmtUpd->execute([$moveResult['new_url'], $moveResult['new_url'], $id]);
            } else {
                $slotMsg = " (aviso al mover: {$moveResult['reason']})";
            }

            echo json_encode(['success' => true, 'message' => 'Foto aprobada' . $slotMsg]);

        } elseif ($action === 'reject_photo') {
            $stmt = $pdo->prepare("UPDATE entity_photos SET permission_status='revoked', status='hidden' WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Foto rechazada']);

        } elseif ($action === 'delete_photo') {
            // Obtener ruta del archivo antes de borrar
            $stmt = $pdo->prepare("SELECT file_path FROM entity_photos WHERE id=?");
            $stmt->execute([$id]);
            $photo = $stmt->fetch();
            if ($photo && !empty($photo['file_path']) && file_exists($photo['file_path'])) {
                @unlink($photo['file_path']);
            }
            $stmt = $pdo->prepare("DELETE FROM entity_photos WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Foto eliminada']);

        } elseif ($action === 'approve_suggestion') {
            // Obtener datos de la sugerencia
            $stmtGet = $pdo->prepare("SELECT * FROM suggested_entities WHERE id=?");
            $stmtGet->execute([$id]);
            $suggestion = $stmtGet->fetch();
            
            $newEntityId = 0;
            $entityTypeName = '';
            
            if ($suggestion) {
                // 1. Crear registro en la tabla correspondiente
                $entityType = $suggestion['entity_type'];
                $name = $suggestion['name'];
                $municipality = $suggestion['municipality'] ?? '';
                $province = $suggestion['province'] ?? '';
                $description = $suggestion['description'] ?? '';
                
                // Determinar tabla destino y configuraciones
                $tablesConfig = [
                    'places_of_interest' => [
                        'table' => 'places_of_interest',
                        'name_field' => 'name',
                        'slug_field' => 'slug',
                        'default_category' => 1, // Monumentos
                        'entity_type_name' => 'lugar'
                    ],
                    'accommodations' => [
                        'table' => 'accommodations',
                        'name_field' => 'name',
                        'slug_field' => 'slug',
                        'default_category' => 1,
                        'entity_type_name' => 'alojamiento'
                    ],
                    'cultural_events' => [
                        'table' => 'cultural_events',
                        'name_field' => 'title',
                        'slug_field' => 'slug',
                        'default_category' => 1,
                        'entity_type_name' => 'evento'
                    ],
                    'activities' => [
                        'table' => 'tourist_activities',
                        'name_field' => 'name',
                        'slug_field' => 'slug',
                        'default_category' => 1,
                        'entity_type_name' => 'actividad'
                    ]
                ];
                
                if (isset($tablesConfig[$entityType])) {
                    $config = $tablesConfig[$entityType];
                    $entityTypeName = $config['entity_type_name'];
                    
                    // Generar slug básico
                    $slug = strtolower(trim($name));
                    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
                    $slug = preg_replace('/-+/', '-', $slug);
                    
                    // Insertar en la tabla correspondiente
                    if ($entityType === 'places_of_interest') {
                        $insertSql = "INSERT INTO {$config['table']} 
                            (name, slug, description, municipality, province, category_id, is_active, moderation_status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, 0, 'draft', NOW())";
                        $stmtInsert = $pdo->prepare($insertSql);
                        $stmtInsert->execute([$name, $slug, $description, $municipality, $province, $config['default_category']]);
                        $newEntityId = $pdo->lastInsertId();
                        
                    } elseif ($entityType === 'accommodations') {
                        $insertSql = "INSERT INTO {$config['table']} 
                            (name, slug, description, municipality, province, category_id, is_active, moderation_status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, 0, 'draft', NOW())";
                        $stmtInsert = $pdo->prepare($insertSql);
                        $stmtInsert->execute([$name, $slug, $description, $municipality, $province, $config['default_category']]);
                        $newEntityId = $pdo->lastInsertId();
                        
                    } elseif ($entityType === 'cultural_events') {
                        $insertSql = "INSERT INTO {$config['table']} 
                            (title, slug, description, municipality, province, is_active, moderation_status, created_at) 
                            VALUES (?, ?, ?, ?, ?, 0, 'draft', NOW())";
                        $stmtInsert = $pdo->prepare($insertSql);
                        $stmtInsert->execute([$name, $slug, $description, $municipality, $province]);
                        $newEntityId = $pdo->lastInsertId();
                        
                    } elseif ($entityType === 'activities') {
                        $insertSql = "INSERT INTO {$config['table']} 
                            (name, slug, description, municipality, province, is_active, moderation_status, created_at) 
                            VALUES (?, ?, ?, ?, ?, 0, 'draft', NOW())";
                        $stmtInsert = $pdo->prepare($insertSql);
                        $stmtInsert->execute([$name, $slug, $description, $municipality, $province]);
                        $newEntityId = $pdo->lastInsertId();
                    }
                }
                
                // 2. Buscar fotos de esta sugerencia
                $stmtPhotos = $pdo->prepare("SELECT * FROM entity_photos WHERE suggested_entity_id=?");
                $stmtPhotos->execute([$id]);
                $photos = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);
                
                $photosMoved = 0;
                $webRoot = dirname(__DIR__);
                
                // 3. Actualizar entity_photos con el nuevo entity_id
                if ($newEntityId > 0 && count($photos) > 0) {
                    foreach ($photos as $photo) {
                        $stmtUpdatePhoto = $pdo->prepare("UPDATE entity_photos SET entity_id = ? WHERE id = ?");
                        $stmtUpdatePhoto->execute([$newEntityId, $photo['id']]);
                        $photosMoved++;
                    }
                }
                
                $msg = "✅ {$entityTypeName} aprobado y creado con ID: {$newEntityId} (is_active=0 - pendiente de revisión final)";
                if ($photosMoved > 0) {
                    $msg .= ". {$photosMoved} foto(s) asociada(s) al nuevo registro.";
                }
                
                // 4. Actualizar suggested_entities con el nuevo entity_id
                $updateNotes = ($reason ?? '') . " | Creado {$entityTypeName} ID: {$newEntityId}";
                $stmtUpdate = $pdo->prepare("UPDATE suggested_entities SET status='approved', reviewed_at=NOW(), admin_notes=?, linked_entity_id=? WHERE id=?");
                $stmtUpdate->execute([$updateNotes, $newEntityId, $id]);
                
            } else {
                $msg = "Sugerencia no encontrada";
                echo json_encode(['success' => false, 'error' => $msg]);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => $msg, 'new_entity_id' => $newEntityId, 'entity_type' => $entityType ?? '']);

        } elseif ($action === 'reject_suggestion') {
            $stmt = $pdo->prepare("UPDATE suggested_entities SET status='rejected', reviewed_at=NOW(), admin_notes=? WHERE id=?");
            $stmt->execute([$reason, $id]);
            echo json_encode(['success' => true, 'message' => 'Sugerencia rechazada']);

        } elseif ($action === 'remover_photo') {
            // Re-procesar una foto ya aprobada que no se movió a su carpeta SEO
            $stmtGet = $pdo->prepare("SELECT * FROM entity_photos WHERE id=?");
            $stmtGet->execute([$id]);
            $photo = $stmtGet->fetch();

            if (!$photo) {
                echo json_encode(['success' => false, 'error' => 'Foto no encontrada']);
                exit;
            }

            $moveResult = movePhotoToSeoFolder($pdo, $photo);

            if ($moveResult['moved']) {
                $stmtUpd = $pdo->prepare("UPDATE entity_photos SET file_url=?, file_path=? WHERE id=?");
                $stmtUpd->execute([$moveResult['new_url'], $moveResult['new_url'], $id]);
                echo json_encode(['success' => true, 'message' => "✅ Foto movida a {$moveResult['slot']} → {$moveResult['new_url']}"]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No se pudo mover: ' . $moveResult['reason']]);
            }

        } elseif ($action === 'feature_photo') {
            $stmt = $pdo->prepare("UPDATE entity_photos SET featured = NOT featured WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Destacado actualizado']);

        } elseif ($action === 'set_cover') {
            // Primero quitar portada anterior de esa entidad
            $stmtGet = $pdo->prepare("SELECT entity_type, entity_id FROM entity_photos WHERE id=?");
            $stmtGet->execute([$id]);
            $ep = $stmtGet->fetch();
            if ($ep) {
                $pdo->prepare("UPDATE entity_photos SET is_cover=0 WHERE entity_type=? AND entity_id=?")->execute([$ep['entity_type'], $ep['entity_id']]);
                $pdo->prepare("UPDATE entity_photos SET is_cover=1 WHERE id=?")->execute([$id]);
            }
            echo json_encode(['success' => true, 'message' => 'Portada establecida']);

        } else {
            echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── Función helper: convierte URL/ruta de foto a ruta física del servidor ────
function photoUrlToPhysicalPath(string $url, string $webRoot): ?string
{
    $url = trim($url);
    if (empty($url)) return null;

    // URL absoluta con dominio → extraer la parte de la ruta
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
        $parsed = parse_url($url);
        $path   = $parsed['path'] ?? '';
        if (empty($path)) return null;
        return $webRoot . $path;
    }

    // Ruta web relativa que empieza por /
    if (str_starts_with($url, '/')) {
        return $webRoot . $url;
    }

    // Ruta relativa sin / (ej: "uploads/eventos/foto.jpg") → probablemente no existe
    // Intentar desde webRoot
    $candidate = $webRoot . '/' . ltrim($url, '/');
    return $candidate;
}

// ── Función: mover foto a carpeta SEO al aprobar ─────────────────────────────
function movePhotoToSeoFolder(PDO $pdo, array $photo): array
{
    $entityType = $photo['entity_type'];
    $entityId   = (int)$photo['entity_id'];

    // Configuración por tipo de entidad
    $config = [
        'accommodations' => [
            'table'      => 'accommodations',
            'web_folder' => 'img/alojamientos',
            'max_photos' => 20,
        ],
        'places_of_interest' => [
            'table'      => 'places_of_interest',
            'web_folder' => 'img/lugares',
            'max_photos' => 4,
        ],
        'cultural_events' => [
            'table'      => 'cultural_events',
            'web_folder' => 'img/eventos-culturales',
            'max_photos' => 4,
        ],
        'activities' => [
            'table'      => 'tourist_activities',
            'web_folder' => 'img/actividades',
            'max_photos' => 4,
        ],
    ];

    if (!isset($config[$entityType])) {
        return ['moved' => false, 'reason' => 'Tipo de entidad no configurado'];
    }

    $cfg = $config[$entityType];

    // 1. Obtener slug de la entidad
    try {
        $stmt = $pdo->prepare("SELECT slug FROM `{$cfg['table']}` WHERE id = ? LIMIT 1");
        $stmt->execute([$entityId]);
        $row = $stmt->fetch();
        if (!$row || empty($row['slug'])) {
            return ['moved' => false, 'reason' => 'Entidad sin slug'];
        }
        $slug = $row['slug'];
    } catch (Exception $e) {
        return ['moved' => false, 'reason' => 'Error BD: ' . $e->getMessage()];
    }

    // 3. Raíz del servidor web (necesaria para pasos 2 y siguientes)
    $webRoot   = dirname(__DIR__);

    // 2. Encontrar el siguiente hueco libre (photo1, photo2, ...)
    // Un slot se considera libre si: está vacío O si la URL que contiene no corresponde
    // a un archivo que exista físicamente en el servidor (rutas antiguas/inválidas)
    $freeSlot = null;
    $freeCol  = null;
    try {
        $cols = [];
        for ($i = 1; $i <= $cfg['max_photos']; $i++) $cols[] = "photo$i";
        $colList = implode(', ', $cols);
        $stmt = $pdo->prepare("SELECT $colList FROM `{$cfg['table']}` WHERE id = ? LIMIT 1");
        $stmt->execute([$entityId]);
        $photos = $stmt->fetch();
        if ($photos) {
            for ($i = 1; $i <= $cfg['max_photos']; $i++) {
                $photoVal = trim($photos["photo$i"] ?? '');
                if (empty($photoVal)) {
                    // Slot vacío → libre
                    $freeSlot = $i;
                    $freeCol  = "photo$i";
                    break;
                }
                // Verificar si el archivo existe físicamente
                $physicalPath = photoUrlToPhysicalPath($photoVal, $webRoot);
                if ($physicalPath === null || !file_exists($physicalPath)) {
                    // La URL no apunta a un archivo real → slot libre (ruta antigua/inválida)
                    $freeSlot = $i;
                    $freeCol  = "photo$i";
                    break;
                }
            }
        }
    } catch (Exception $e) {
        return ['moved' => false, 'reason' => 'Error buscando hueco: ' . $e->getMessage()];
    }

    if (!$freeSlot) {
        return ['moved' => false, 'reason' => "No hay huecos libres (máx {$cfg['max_photos']} fotos)"];
    }

    // 4. Construir rutas de origen y destino (webRoot ya definido arriba)
    $destDir   = $webRoot . '/' . $cfg['web_folder'] . '/' . $slug . '/';
    $newFilename = $freeSlot . '.webp';
    $destPath  = $destDir . $newFilename;
    $newWebUrl = '/' . $cfg['web_folder'] . '/' . $slug . '/' . $newFilename;

    // Obtener ruta física del archivo origen
    $srcPath = $photo['file_path'] ?? '';

    // file_path puede ser:
    //   a) Ruta absoluta del servidor: /home/user/public_html/img/entity_photos/...  → usar directamente
    //   b) Ruta web relativa:          /img/entity_photos/...                        → prepend webRoot
    // Distinguir: si el path contiene $webRoot ya es absoluto; si no, es ruta web
    if (!empty($srcPath) && !str_starts_with($srcPath, $webRoot)) {
        // Es una ruta web (empieza por /img/ etc.) → convertir a ruta física
        $srcPath = $webRoot . '/' . ltrim($srcPath, '/');
    }

    if (!file_exists($srcPath)) {
        // Intentar con file_url como fallback
        $altPath = $photo['file_url'] ?? '';
        if (!empty($altPath) && !str_starts_with($altPath, $webRoot)) {
            $altPath = $webRoot . '/' . ltrim($altPath, '/');
        }
        if (!empty($altPath) && file_exists($altPath)) {
            $srcPath = $altPath;
        } else {
            return ['moved' => false, 'reason' => "Archivo origen no encontrado. file_path={$photo['file_path']} → $srcPath"];
        }
    }

    // 4. Crear directorio destino si no existe
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0755, true)) {
            return ['moved' => false, 'reason' => "No se pudo crear directorio: $destDir"];
        }
    }

    // 5. Mover el archivo
    if (!copy($srcPath, $destPath)) {
        return ['moved' => false, 'reason' => "Error al copiar archivo a $destPath"];
    }
    @unlink($srcPath); // Borrar el temporal

    // 6. Actualizar la columna photoN en la tabla de la entidad
    try {
        $stmt = $pdo->prepare("UPDATE `{$cfg['table']}` SET `$freeCol` = ? WHERE id = ?");
        $stmt->execute([$newWebUrl, $entityId]);
    } catch (Exception $e) {
        // No es crítico — la foto ya está en su sitio
        error_log("Error actualizando $freeCol en {$cfg['table']}: " . $e->getMessage());
    }

    return [
        'moved'    => true,
        'new_url'  => $newWebUrl,
        'slot'     => $freeCol,
        'slug'     => $slug,
    ];
}

// ── Filtros ──────────────────────────────────────────────────────────────────
$tab         = $_GET['tab'] ?? 'photos';
$statusFilter= $_GET['status'] ?? 'pending';
$typeFilter  = $_GET['type'] ?? 'all';

// ── Cargar fotos pendientes ──────────────────────────────────────────────────
$photos = [];
$suggestions = [];

try {
    if ($tab === 'photos') {
        $whereStatus = $statusFilter === 'all' ? "1=1" : "ep.permission_status = '$statusFilter'";
        $whereType   = $typeFilter === 'all' ? "1=1" : "ep.entity_type = '$typeFilter'";

        $stmt = $pdo->query("
            SELECT 
                ep.*,
                u.first_name, u.last_name, u.email
            FROM entity_photos ep
            LEFT JOIN users u ON ep.author_id = u.id
            WHERE $whereStatus AND $whereType
            ORDER BY ep.uploaded_at DESC
            LIMIT 100
        ");
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($tab === 'suggestions') {
        $whereStatus = $statusFilter === 'all' ? "1=1" : "se.status = '$statusFilter'";
        $stmt = $pdo->query("
            SELECT 
                se.*,
                u.first_name, u.last_name, u.email,
                (SELECT 
                    ep2.file_url
                 FROM entity_photos ep2 
                 WHERE ep2.suggested_entity_id = se.id 
                 LIMIT 1) AS photo_url
            FROM suggested_entities se
            LEFT JOIN users u ON se.suggested_by = u.id
            WHERE $whereStatus
            ORDER BY se.created_at DESC
            LIMIT 100
        ");
        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Post-procesar para limpiar URLs inválidas
        foreach ($suggestions as &$s) {
            $rawUrl = $s['photo_url'] ?? '';
            // Si file_url está vacío o no es una URL web válida, probar file_path
            if (empty($rawUrl) || (!str_starts_with($rawUrl, '/') && !str_starts_with($rawUrl, 'http'))) {
                // No mostrar nada, se mostrará el icono
                $s['photo_url'] = '';
            }
        }
        unset($s);
    }

    // Estadísticas
    $statPhotos      = $pdo->query("SELECT COUNT(*) FROM entity_photos WHERE permission_status='pending'")->fetchColumn();
    $statSuggestions = 0;
    try {
        $statSuggestions = $pdo->query("SELECT COUNT(*) FROM suggested_entities WHERE status='pending'")->fetchColumn();
    } catch (Exception $e) {}

} catch (Exception $e) {
    $error = $e->getMessage();
}

$typeLabels = [
    'accommodations'     => 'Alojamiento',
    'places_of_interest' => 'Lugar de Interés',
    'cultural_events'    => 'Evento Cultural',
    'activities'         => 'Actividad',
];
$typeIcons = [
    'accommodations'     => 'fa-bed',
    'places_of_interest' => 'fa-map-marker-alt',
    'cultural_events'    => 'fa-calendar-alt',
    'activities'         => 'fa-hiking',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderación de Fotos - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }

        .header {
            background: linear-gradient(135deg, #2f5233, #4a7c4e);
            color: white; padding: 1.5rem 2rem;
        }
        .header h1 { font-size: 1.6rem; margin-bottom: .3rem; }
        .header p { opacity: .85; font-size: .9rem; }

        .container { max-width: 1400px; margin: 0 auto; padding: 1.5rem; }

        /* Stats */
        .stats { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .stat-card {
            background: white; border-radius: 10px; padding: 1rem 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,.08); display: flex; align-items: center; gap: 1rem;
            min-width: 180px;
        }
        .stat-icon { width: 44px; height: 44px; border-radius: 50%; background: #e8f5e9; color: #2f5233; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .stat-num { font-size: 1.8rem; font-weight: 700; color: #2f5233; }
        .stat-label { font-size: .82rem; color: #888; }

        /* Tabs */
        .tabs { display: flex; gap: .5rem; margin-bottom: 1.5rem; }
        .tab-btn {
            padding: .6rem 1.4rem; border: 2px solid #e0e0e0; border-radius: 25px;
            background: white; cursor: pointer; font-size: .9rem; font-weight: 600;
            color: #555; transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
        }
        .tab-btn.active { background: #2f5233; color: white; border-color: #2f5233; }
        .tab-btn .badge { background: #e74c3c; color: white; border-radius: 10px; padding: 1px 7px; font-size: .75rem; }

        /* Filters */
        .filters { background: white; padding: 1rem 1.5rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
        .filters label { font-size: .85rem; color: #666; font-weight: 600; }
        .filters select { padding: .5rem .8rem; border: 2px solid #eee; border-radius: 8px; font-size: .9rem; cursor: pointer; }
        .filters select:focus { outline: none; border-color: #2f5233; }

        /* Photo grid */
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.2rem; }

        .photo-card {
            background: white; border-radius: 12px; overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,.08); transition: transform .2s;
        }
        .photo-card:hover { transform: translateY(-3px); }

        .photo-img-wrap { position: relative; aspect-ratio: 4/3; background: #f0f0f0; overflow: hidden; }
        .photo-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .photo-img-wrap .no-img { display: flex; align-items: center; justify-content: center; height: 100%; color: #ccc; font-size: 3rem; }

        .photo-status-badge {
            position: absolute; top: .5rem; left: .5rem;
            padding: .25rem .7rem; border-radius: 12px; font-size: .75rem; font-weight: 700;
        }
        .badge-pending  { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-revoked  { background: #f8d7da; color: #721c24; }

        .photo-body { padding: 1rem; }
        .photo-type { font-size: .75rem; color: #888; margin-bottom: .3rem; display: flex; align-items: center; gap: .3rem; }
        .photo-entity { font-weight: 700; color: #333; margin-bottom: .4rem; font-size: .95rem; }
        .photo-meta { font-size: .82rem; color: #666; margin-bottom: .3rem; }
        .photo-author { font-size: .82rem; color: #555; background: #f5f5f5; padding: .3rem .6rem; border-radius: 6px; margin-bottom: .8rem; }

        .photo-actions { display: flex; gap: .4rem; flex-wrap: wrap; }
        .btn { padding: .45rem .9rem; border: none; border-radius: 6px; cursor: pointer; font-size: .82rem; font-weight: 600; transition: all .2s; display: inline-flex; align-items: center; gap: .3rem; }
        .btn-approve { background: #28a745; color: white; }
        .btn-approve:hover { background: #218838; }
        .btn-reject  { background: #dc3545; color: white; }
        .btn-reject:hover  { background: #c82333; }
        .btn-delete  { background: #6c757d; color: white; }
        .btn-delete:hover  { background: #545b62; }
        .btn-feature { background: #fd7e14; color: white; }
        .btn-feature:hover { background: #e96b00; }
        .btn-cover   { background: #6f42c1; color: white; }
        .btn-cover:hover   { background: #5a32a3; }
        .btn-view    { background: #17a2b8; color: white; }
        .btn-view:hover    { background: #138496; }

        /* Suggestion cards */
        .suggestion-list { display: flex; flex-direction: column; gap: 1rem; }
        .suggestion-card {
            background: white; border-radius: 12px; padding: 1.2rem 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,.08); display: flex; gap: 1.2rem; align-items: flex-start;
        }
        .suggestion-thumb { width: 100px; height: 80px; border-radius: 8px; object-fit: cover; background: #f0f0f0; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 2rem; overflow: hidden; }
        .suggestion-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .suggestion-info { flex: 1; }
        .suggestion-name { font-size: 1.05rem; font-weight: 700; color: #333; margin-bottom: .3rem; }
        .suggestion-meta { font-size: .85rem; color: #666; margin-bottom: .5rem; }
        .suggestion-desc { font-size: .85rem; color: #555; margin-bottom: .8rem; background: #f9f9f9; padding: .5rem; border-radius: 6px; }
        .suggestion-user { font-size: .82rem; color: #888; }

        .empty-state { text-align: center; padding: 4rem; color: #aaa; background: white; border-radius: 12px; }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; display: block; }

        /* Toast */
        #toast { position: fixed; bottom: 2rem; right: 2rem; background: #333; color: white; padding: .8rem 1.5rem; border-radius: 8px; font-size: .9rem; display: none; z-index: 9999; box-shadow: 0 4px 15px rgba(0,0,0,.2); }
        #toast.show { display: block; animation: fadeInUp .3s ease; }
        #toast.success { background: #28a745; }
        #toast.error   { background: #dc3545; }
        @keyframes fadeInUp { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="header">
    <h1><i class="fas fa-images"></i> Moderación General</h1>
    <p>Gestiona fotos de usuarios, lugares sugeridos y contenido pendiente</p>
</div>

<div class="container">

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div>
                <div class="stat-num"><?= $statPhotos ?></div>
                <div class="stat-label">Fotos pendientes</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-lightbulb"></i></div>
            <div>
                <div class="stat-num"><?= $statSuggestions ?></div>
                <div class="stat-label">Sugerencias pendientes</div>
            </div>
        </div>
        <div class="stat-card" style="margin-left:auto;">
            <a href="index.php" style="color:#2f5233;text-decoration:none;font-size:.9rem;font-weight:600;"><i class="fas fa-arrow-left"></i> Volver al panel</a>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <a href="?tab=photos&status=<?= $statusFilter ?>" class="tab-btn <?= $tab==='photos'?'active':'' ?>">
            <i class="fas fa-images"></i> Fotos
            <?php if ($statPhotos > 0): ?><span class="badge"><?= $statPhotos ?></span><?php endif; ?>
        </a>
        <a href="?tab=suggestions&status=<?= $statusFilter ?>" class="tab-btn <?= $tab==='suggestions'?'active':'' ?>">
            <i class="fas fa-lightbulb"></i> Lugares Sugeridos
            <?php if ($statSuggestions > 0): ?><span class="badge"><?= $statSuggestions ?></span><?php endif; ?>
        </a>
    </div>

    <!-- Filters -->
    <div class="filters">
        <div>
            <label>Estado</label><br>
            <select onchange="applyFilter('status', this.value)">
                <option value="pending"  <?= $statusFilter==='pending' ?'selected':'' ?>>Pendientes</option>
                <option value="approved" <?= $statusFilter==='approved'?'selected':'' ?>>Aprobadas</option>
                <option value="revoked"  <?= $statusFilter==='revoked' ?'selected':'' ?>>Rechazadas</option>
                <option value="all"      <?= $statusFilter==='all'     ?'selected':'' ?>>Todas</option>
            </select>
        </div>
        <?php if ($tab === 'photos'): ?>
        <div>
            <label>Tipo de entidad</label><br>
            <select onchange="applyFilter('type', this.value)">
                <option value="all"                <?= $typeFilter==='all'?'selected':'' ?>>Todos los tipos</option>
                <option value="accommodations"     <?= $typeFilter==='accommodations'?'selected':'' ?>>Alojamientos</option>
                <option value="places_of_interest" <?= $typeFilter==='places_of_interest'?'selected':'' ?>>Lugares de Interés</option>
                <option value="cultural_events"    <?= $typeFilter==='cultural_events'?'selected':'' ?>>Eventos Culturales</option>
                <option value="activities"         <?= $typeFilter==='activities'?'selected':'' ?>>Actividades</option>
            </select>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── TAB: FOTOS ──────────────────────────────────────────────────────── -->
    <?php if ($tab === 'photos'): ?>
        <?php if (empty($photos)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle" style="color:#28a745;"></i>
                <h3>¡Todo al día!</h3>
                <p>No hay fotos con el estado seleccionado.</p>
            </div>
        <?php else: ?>
            <div class="photo-grid">
                <?php foreach ($photos as $p):
                    $entityLabel = $typeLabels[$p['entity_type']] ?? $p['entity_type'];
                    $entityIcon  = $typeIcons[$p['entity_type']] ?? 'fa-image';
                    // file_url puede estar corrupto (bug anterior). Validar que sea una URL/ruta real.
                    $rawUrl  = $p['file_url'] ?? '';
                    $rawPath = $p['file_path'] ?? '';
                    // Si file_url empieza por / o http es válido; si no, ignorarlo
                    $urlValid = !empty($rawUrl) && (str_starts_with($rawUrl, '/') || str_starts_with($rawUrl, 'http'));
                    if ($urlValid) {
                        $imgUrl = $rawUrl;
                    } elseif (!empty($rawPath)) {
                        // file_path puede ser ruta absoluta del servidor o relativa web
                        $cleanPath = str_replace('\\', '/', $rawPath);
                        // Si empieza por / y contiene img/ es ruta web directa
                        if (str_starts_with($cleanPath, '/img/') || str_starts_with($cleanPath, '/accommodations/')) {
                            $imgUrl = $cleanPath;
                        } elseif (str_starts_with($cleanPath, '/')) {
                            // Ruta absoluta del servidor — extraer la parte web desde /img/
                            $pos = strpos($cleanPath, '/img/');
                            $imgUrl = $pos !== false ? substr($cleanPath, $pos) : '';
                        } else {
                            // Ruta relativa como "alojamientos/casa-rural/3.webp"
                            $imgUrl = '/img/' . ltrim($cleanPath, '/');
                        }
                    } else {
                        $imgUrl = '';
                    }
                    $statusClass = ['pending'=>'badge-pending','approved'=>'badge-approved','revoked'=>'badge-revoked'][$p['permission_status']] ?? 'badge-pending';
                    $statusLabel = ['pending'=>'Pendiente','approved'=>'Aprobada','revoked'=>'Rechazada'][$p['permission_status']] ?? $p['permission_status'];
                ?>
                <div class="photo-card" id="card-<?= $p['id'] ?>">
                    <div class="photo-img-wrap">
                        <?php if ($imgUrl): ?>
                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Foto" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'no-img\'><i class=\'fas fa-image\'></i></div>'">
                        <?php else: ?>
                            <div class="no-img"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                        <span class="photo-status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                        <?php if ($p['featured']): ?><span class="photo-status-badge" style="top:.5rem;right:.5rem;background:#fd7e14;color:white;">⭐ Destacada</span><?php endif; ?>
                    </div>
                    <div class="photo-body">
                        <div class="photo-type"><i class="fas <?= $entityIcon ?>"></i> <?= $entityLabel ?> #<?= $p['entity_id'] ?></div>
                        <div class="photo-entity"><?= htmlspecialchars($p['caption'] ?: 'Sin descripción') ?></div>
                        <div class="photo-meta">
                            <i class="fas fa-tag"></i> <?= htmlspecialchars($p['category'] ?? 'otro') ?>
                            &nbsp;·&nbsp;
                            <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($p['uploaded_at'])) ?>
                        </div>
                        <div class="photo-author">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($p['author_name'] ?? ($p['first_name'].' '.$p['last_name'])) ?>
                            <?php if ($p['author_instagram']): ?> · <i class="fab fa-instagram"></i> <?= htmlspecialchars($p['author_instagram']) ?><?php endif; ?>
                            <?php if ($p['email']): ?><br><small style="color:#aaa;"><?= htmlspecialchars($p['email']) ?></small><?php endif; ?>
                        </div>
                        <div class="photo-actions">
                            <?php if ($p['permission_status'] === 'pending'): ?>
                                <button class="btn btn-approve" onclick="moderatePhoto(<?= $p['id'] ?>, 'approve_photo')"><i class="fas fa-check"></i> Aprobar</button>
                                <button class="btn btn-reject"  onclick="moderatePhoto(<?= $p['id'] ?>, 'reject_photo')"><i class="fas fa-times"></i> Rechazar</button>
                            <?php endif; ?>
                            <?php if ($p['permission_status'] === 'approved'): ?>
                                <?php 
                                    // Generar URL según el tipo de entidad
                                    if ($p['entity_id'] > 0) {
                                        // Entidad existente - ir a editarla
                                        $editUrl = '#';
                                        if ($p['entity_type'] === 'accommodations') {
                                            $editUrl = 'editar.php?id=' . $p['entity_id'];
                                        } elseif ($p['entity_type'] === 'places_of_interest') {
                                            $editUrl = 'lugares_editar.php?id=' . $p['entity_id'];
                                        } elseif ($p['entity_type'] === 'cultural_events') {
                                            $editUrl = 'eventos_editar.php?id=' . $p['entity_id'];
                                        } elseif ($p['entity_type'] === 'activities') {
                                            $editUrl = 'actividades_editar.php?id=' . $p['entity_id'];
                                        }
                                        ?>
                                        <a href="<?= $editUrl ?>" target="_blank" class="btn" style="background:#17a2b8;color:white;"><i class="fas fa-external-link-alt"></i> Ir a entidad</a>
                                        <?php
                                    } else {
                                        // Sin entidad asignada - ir a asignar
                                        ?>
                                        <a href="asignar_foto.php?photo_id=<?= $p['id'] ?>&type=<?= $p['entity_type'] ?>" target="_blank" class="btn" style="background:#fd7e14;color:white;"><i class="fas fa-link"></i> Asignar entidad</a>
                                        <?php
                                    }
                                ?>
                                <button class="btn" style="background:#20c997;color:white;" onclick="removerFoto(<?= $p['id'] ?>)" title="Mover/re-mover a carpeta SEO y actualizar ficha del evento/entidad"><i class="fas fa-folder-open"></i> Mover</button>
                                <button class="btn btn-feature" onclick="moderatePhoto(<?= $p['id'] ?>, 'feature_photo')" title="Destacar/Quitar destacado"><i class="fas fa-star"></i></button>
                                <button class="btn btn-cover"   onclick="moderatePhoto(<?= $p['id'] ?>, 'set_cover')" title="Establecer como portada"><i class="fas fa-image"></i> Portada</button>
                            <?php endif; ?>
                            <?php if ($imgUrl): ?>
                                <a href="<?= htmlspecialchars($imgUrl) ?>" target="_blank" class="btn btn-view"><i class="fas fa-eye"></i></a>
                            <?php endif; ?>
                            <button class="btn btn-delete" onclick="if(confirm('¿Eliminar esta foto permanentemente?')) moderatePhoto(<?= $p['id'] ?>, 'delete_photo')"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- ── TAB: SUGERENCIAS ───────────────────────────────────────────────── -->
    <?php elseif ($tab === 'suggestions'): ?>
        <?php if (empty($suggestions)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle" style="color:#28a745;"></i>
                <h3>¡Sin sugerencias pendientes!</h3>
                <p>No hay lugares sugeridos con el estado seleccionado.</p>
            </div>
        <?php else: ?>
            <div class="suggestion-list">
                <?php foreach ($suggestions as $s):
                    $entityLabel = $typeLabels[$s['entity_type']] ?? $s['entity_type'];
                    $entityIcon  = $typeIcons[$s['entity_type']] ?? 'fa-map-marker-alt';
                    $statusClass = ['pending'=>'badge-pending','approved'=>'badge-approved','rejected'=>'badge-revoked','merged'=>'badge-approved'][$s['status']] ?? 'badge-pending';
                    $statusLabel = ['pending'=>'Pendiente','approved'=>'Aprobada','rejected'=>'Rechazada','merged'=>'Integrada'][$s['status']] ?? $s['status'];
                ?>
                <div class="suggestion-card" id="sug-<?= $s['id'] ?>">
                    <div class="suggestion-thumb">
                        <?php if (!empty($s['photo_url'])): ?>
                            <img src="<?= htmlspecialchars($s['photo_url']) ?>" alt="Foto sugerencia" onerror="this.parentElement.innerHTML='<i class=\'fas <?= $entityIcon ?>\'></i>'">
                        <?php else: ?>
                            <i class="fas <?= $entityIcon ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="suggestion-info">
                        <div class="suggestion-name">
                            <?= htmlspecialchars($s['name']) ?>
                            <span class="photo-status-badge <?= $statusClass ?>" style="position:static;margin-left:.5rem;"><?= $statusLabel ?></span>
                        </div>
                        <div class="suggestion-meta">
                            <i class="fas <?= $entityIcon ?>"></i> <?= $entityLabel ?>
                            <?php if ($s['municipality']): ?> · <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($s['municipality']) ?><?php if ($s['province']): ?>, <?= htmlspecialchars($s['province']) ?><?php endif; ?><?php endif; ?>
                            · <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($s['created_at'])) ?>
                        </div>
                        <?php if ($s['description']): ?>
                            <div class="suggestion-desc"><?= nl2br(htmlspecialchars($s['description'])) ?></div>
                        <?php endif; ?>
                        <div class="suggestion-user">
                            <i class="fas fa-user"></i> <?= htmlspecialchars(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?>
                            <?php if ($s['email']): ?>(<?= htmlspecialchars($s['email']) ?>)<?php endif; ?>
                        </div>
                        <?php if ($s['admin_notes']): ?>
                            <div style="margin-top:.5rem;font-size:.82rem;color:#888;"><i class="fas fa-sticky-note"></i> <?= htmlspecialchars($s['admin_notes']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:.4rem;flex-shrink:0;">
                        <?php if ($s['status'] === 'pending'): ?>
                            <?php
                            // Determinar el tipo de creación según entity_type
                            $createLabels = [
                                'places_of_interest' => 'Crear lugar',
                                'cultural_events' => 'Crear evento',
                                'activities' => 'Crear actividad',
                                'accommodations' => 'Crear alojamiento'
                            ];
                            $createIcons = [
                                'places_of_interest' => 'fa-map-marker-alt',
                                'cultural_events' => 'fa-calendar-alt',
                                'activities' => 'fa-hiking',
                                'accommodations' => 'fa-bed'
                            ];
                            $createUrls = [
                                'places_of_interest' => 'lugares_editar.php',
                                'cultural_events' => 'eventos_editar.php',
                                'activities' => 'actividades_editar.php',
                                'accommodations' => 'editar.php'
                            ];
                            $entityType = $s['entity_type'] ?? 'places_of_interest';
                            $createLabel = $createLabels[$entityType] ?? 'Crear contenido';
                            $createIcon = $createIcons[$entityType] ?? 'fa-plus';
                            $createUrl = $createUrls[$entityType] ?? 'lugares_editar.php';
                            ?>
                            <a href="<?= $createUrl ?>?id=0&from_suggested=<?= $s['id'] ?>&name=<?= urlencode($s['name']) ?>&desc=<?= urlencode($s['description'] ?? '') ?>&municipality=<?= urlencode($s['municipality'] ?? '') ?>&province=<?= urlencode($s['province'] ?? '') ?>" class="btn" style="background:#6f42c1;color:white;" target="_blank" title="Crear este contenido como registro oficial en la web"><i class="fas <?= $createIcon ?>"></i> <?= $createLabel ?></a>
                            <button class="btn btn-approve" onclick="moderateSuggestion(<?= $s['id'] ?>, 'approve_suggestion')" title="Marcar como revisada (no crea registro)"><i class="fas fa-check"></i> Aprobar</button>
                            <button class="btn btn-reject"  onclick="moderateSuggestion(<?= $s['id'] ?>, 'reject_suggestion')"><i class="fas fa-times"></i> Rechazar</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<div id="toast"></div>

<script>
function applyFilter(key, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, value);
    window.location.href = url.toString();
}

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'show ' + type;
    setTimeout(() => t.className = '', 3000);
}

async function moderatePhoto(id, action) {
    try {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('id', id);

        const r = await fetch('', { method: 'POST', body: fd });
        const data = await r.json();

        if (data.success) {
            showToast(data.message, 'success');
            // Quitar la tarjeta si es aprobación/rechazo/eliminación
            if (['approve_photo','reject_photo','delete_photo'].includes(action)) {
                const card = document.getElementById('card-' + id);
                if (card) card.style.opacity = '0.3';
                setTimeout(() => { if (card) card.remove(); }, 600);
            }
        } else {
            showToast('Error: ' + data.error, 'error');
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
    }
}

async function removerFoto(id) {
    try {
        const fd = new FormData();
        fd.append('action', 'remover_photo');
        fd.append('id', id);

        const r = await fetch('', { method: 'POST', body: fd });
        const data = await r.json();

        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast('Error: ' + data.error, 'error');
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
    }
}

async function moderateSuggestion(id, action) {
    let reason = '';
    if (action === 'reject_suggestion') {
        reason = prompt('Motivo del rechazo (opcional):') || '';
    }

    try {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('id', id);
        fd.append('reason', reason);

        const r = await fetch('', { method: 'POST', body: fd });
        const data = await r.json();

        if (data.success) {
            showToast(data.message, 'success');
            const card = document.getElementById('sug-' + id);
            if (card) card.style.opacity = '0.3';
            setTimeout(() => { if (card) card.remove(); }, 600);
        } else {
            showToast('Error: ' + data.error, 'error');
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
    }
}
</script>
</body>
</html>
