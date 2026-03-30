<?php
/**
 * Página para asignar una foto a una entidad
 * Selecciona el evento/lugar/alojamiento y mueve la foto automáticamente
 */
session_start();
require_once 'db.php';

// Obtener parámetros
$photoId = isset($_GET['photo_id']) ? (int)$_GET['photo_id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'places_of_interest'; // Default a lugares

if (!$photoId) {
    die("Faltan parámetros");
}

// Configuración por tipo de entidad
$config = [
    'accommodations' => [
        'table' => 'accommodations',
        'name_field' => 'name',
        'slug_field' => 'slug',
        'label' => 'Alojamientos',
        'icon' => 'fa-bed',
        'edit_page' => 'editar.php',
    ],
    'places_of_interest' => [
        'table' => 'places_of_interest',
        'name_field' => 'name',
        'slug_field' => 'slug',
        'label' => 'Lugares de Interés',
        'icon' => 'fa-map-marker-alt',
        'edit_page' => 'lugares_editar.php',
    ],
    'cultural_events' => [
        'table' => 'cultural_events',
        'name_field' => 'name',
        'slug_field' => 'slug',
        'label' => 'Eventos Culturales',
        'icon' => 'fa-calendar-alt',
        'edit_page' => 'eventos_editar.php',
    ],
    'activities' => [
        'table' => 'activities',
        'name_field' => 'name',
        'slug_field' => 'slug',
        'label' => 'Actividades',
        'icon' => 'fa-hiking',
        'edit_page' => 'actividades_editar.php',
    ],
];

if (!isset($config[$type])) {
    $type = 'places_of_interest';
}

$cfg = $config[$type];

// Procesar asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entity_id'])) {
    $entityId = (int)$_POST['entity_id'];
    $newType = isset($_POST['new_type']) ? $_POST['new_type'] : $type;
    
    // Usar la configuración del tipo seleccionado
    $newCfg = $config[$newType];
    
    // Obtener datos de la foto
    $stmt = $pdo->prepare("SELECT * FROM entity_photos WHERE id = ?");
    $stmt->execute([$photoId]);
    $photo = $stmt->fetch();
    
    if (!$photo) {
        $error = "Foto no encontrada";
    } else {
        // Obtener datos de la entidad destino
        $stmt = $pdo->prepare("SELECT * FROM `{$newCfg['table']}` WHERE id = ?");
        $stmt->execute([$entityId]);
        $entity = $stmt->fetch();
        
        if (!$entity) {
            $error = "Entidad no encontrada";
        } else {
            $webRoot = dirname(__DIR__);
            $slug = $entity[$newCfg['slug_field']];
            
            // Determinar carpeta según tipo
            $folders = [
                'accommodations' => 'img/alojamientos',
                'places_of_interest' => 'img/lugares',
                'cultural_events' => 'img/eventos-culturales',
                'activities' => 'img/actividades',
            ];
            $webFolder = $folders[$newType];
            
            // Buscar hueco libre
            $maxPhotos = ($newType === 'accommodations') ? 20 : 4;
            $freeSlot = null;
            $freeCol = null;
            
            for ($i = 1; $i <= $maxPhotos; $i++) {
                $col = "photo$i";
                if (empty($entity[$col])) {
                    $freeSlot = $i;
                    $freeCol = $col;
                    break;
                }
            }
            
            if (!$freeSlot) {
                $error = "No hay huecos libres en esta entidad";
            } else {
                // Rutas
                $destDir = $webRoot . '/' . $webFolder . '/' . $slug . '/';
                $newFilename = $freeSlot . '.webp';
                $destPath = $destDir . $newFilename;
                $newWebUrl = '/' . $webFolder . '/' . $slug . '/' . $newFilename;
                
                // Obtener ruta origen
                $srcPath = $photo['file_path'] ?? '';
                if (str_starts_with($srcPath, '/')) {
                    $srcPath = $webRoot . $srcPath;
                }
                if (!file_exists($srcPath) && !empty($photo['file_url'])) {
                    $altPath = $photo['file_url'];
                    if (str_starts_with($altPath, '/')) {
                        $altPath = $webRoot . $altPath;
                    }
                    if (file_exists($altPath)) {
                        $srcPath = $altPath;
                    }
                }
                
                // Crear directorio y mover
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                
                if (file_exists($srcPath)) {
                    copy($srcPath, $destPath);
                    @unlink($srcPath);
                }
                
                // Actualizar entidad
                $stmt = $pdo->prepare("UPDATE `{$newCfg['table']}` SET `$freeCol` = ? WHERE id = ?");
                $stmt->execute([$newWebUrl, $entityId]);
                
                // Actualizar foto con el nuevo tipo
                $stmt = $pdo->prepare("UPDATE entity_photos SET entity_id = ?, entity_type = ?, file_url = ?, file_path = ? WHERE id = ?");
                $stmt->execute([$entityId, $newType, $newWebUrl, $newWebUrl, $photoId]);
                
                // Redirigir a la página de edición
                header("Location: " . $newCfg['edit_page'] . "?id=" . $entityId . "&msg=Foto asignada");
                exit;
            }
        }
    }
}

// Cargar lista de entidades según el tipo seleccionado
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];
if ($search) {
    $where = "WHERE " . $cfg['name_field'] . " LIKE ?";
    $params[] = '%' . $search . '%';
}
$where .= " ORDER BY " . $cfg['name_field'] . " ASC LIMIT 50";

$stmt = $pdo->prepare("SELECT id, " . $cfg['name_field'] . " as name, municipality FROM `{$cfg['table']}` $where");
$stmt->execute($params);
$entities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Foto - <?= $cfg['label'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .type-tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .type-tab {
            padding: 0.5rem 1rem;
            border: 2px solid #dee2e6;
            border-radius: 25px;
            background: white;
            color: #555;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .type-tab:hover { background: #f8f9fa; }
        .type-tab.active {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-link-45deg"></i> Asignar Foto</h4>
                        <small>Selecciona el tipo de entidad y luego el elemento</small>
                    </div>
                    <div class="card-body">
                        <!-- Tabs para cambiar tipo de entidad -->
                        <div class="type-tabs">
                            <?php foreach ($config as $key => $c): ?>
                                <a href="?photo_id=<?= $photoId ?>&type=<?= $key ?>" class="type-tab <?= $type === $key ? 'active' : '' ?>">
                                    <i class="fas <?= $c['icon'] ?>"></i> <?= $c['label'] ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <form method="GET">
                            <input type="hidden" name="photo_id" value="<?= $photoId ?>">
                            <input type="hidden" name="type" value="<?= $type ?>">
                            
                            <div class="mb-3">
                                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <button type="submit" class="btn btn-outline-secondary mb-3"><i class="bi bi-search"></i> Buscar</button>
                        </form>
                        
                        <form method="POST">
                            <input type="hidden" name="new_type" value="<?= $type ?>">
                            
                            <?php if (empty($entities)): ?>
                                <div class="alert alert-warning">No se encontraron <?= $cfg['label'] ?></div>
                            <?php else: ?>
                                <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($entities as $e): ?>
                                        <label class="list-group-item list-group-item-action">
                                            <div class="form-check">
                                                <input type="radio" name="entity_id" value="<?= $e['id'] ?>" class="form-check-input" required>
                                                <span class="fw-bold"><?= htmlspecialchars($e['name']) ?></span>
                                                <?php if (!empty($e['municipality'])): ?>
                                                    <small class="text-muted"> - <?= htmlspecialchars($e['municipality']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-3 d-grid">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="bi bi-check-circle"></i> Asignar y mover foto
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="card-footer">
                        <a href="moderacion_fotos.php?tab=photos&status=approved" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a moderación
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
