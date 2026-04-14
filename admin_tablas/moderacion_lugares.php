<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Moderación Unificado - Rutas Rurales</title>
    <?php include 'sidebar.php'; ?>
    <style>
        .moderation-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .pending-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 20px;
        }
        .item-image {
            width: 200px;
            height: 150px;
            border-radius: 8px;
            object-fit: cover;
            background: #f0f0f0;
        }
        .item-content {
            flex: 1;
        }
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .item-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .item-badge {
            background: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .item-details {
            color: #666;
            margin-bottom: 10px;
        }
        .item-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-approve {
            background: #27ae60;
            color: white;
        }
        .btn-reject {
            background: #e74c3c;
            color: white;
        }
        .btn-edit {
            background: #3498db;
            color: white;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter-group label {
            font-weight: bold;
            color: #2c3e50;
        }
        .filter-group select {
            padding: 8px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            background: white;
        }
        .no-items {
            text-align: center;
            padding: 50px;
            color: #7f8c8d;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .no-items i {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <h1><i class="fas fa-clipboard-check"></i> Panel de Moderación Unificado</h1>
        <p>Modera: Alojamientos, Eventos, Actividades y Lugares de Interés</p>

<?php
require_once 'db.php';
$pdo = getDBConnection();

// Obtener filtros
$contentType = $_GET['type'] ?? 'all';
$status = $_GET['status'] ?? 'pending';

// Construir query según el tipo de contenido
$items = [];

try {
    if ($contentType === 'all' || $contentType === 'accommodation') {
        $stmt = $pdo->prepare("
            SELECT 
                'accommodation' as content_type,
                a.id,
                a.name,
                a.municipality,
                a.province,
                a.moderation_status,
                a.last_submitted_at,
                a.created_at,
                CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                u.email as user_email,
                a.photo1 as photo
            FROM accommodations a
            LEFT JOIN users u ON a.created_by = u.id
            WHERE a.moderation_status = ?
            ORDER BY a.last_submitted_at DESC
        ");
        $stmt->execute([$status]);
        $items = array_merge($items, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if ($contentType === 'all' || $contentType === 'event') {
        $stmt = $pdo->prepare("
            SELECT 
                'event' as content_type,
                e.id,
                e.name,
                COALESCE(e.municipality, '') as municipality,
                COALESCE(e.province, '') as province,
                COALESCE(e.moderation_status, 'draft') as moderation_status,
                e.last_submitted_at,
                e.created_at,
                CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                u.email as user_email,
                NULL as photo
            FROM cultural_events e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE COALESCE(e.moderation_status, 'draft') = ?
            ORDER BY e.last_submitted_at DESC
        ");
        $stmt->execute([$status]);
        $items = array_merge($items, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if ($contentType === 'all' || $contentType === 'activity') {
        $stmt = $pdo->prepare("
            SELECT 
                'activity' as content_type,
                ac.id,
                ac.name,
                ac.municipality,
                ac.province,
                ac.moderation_status,
                ac.last_submitted_at,
                ac.created_at,
                CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                u.email as user_email,
                ac.photo1 as photo
            FROM tourist_activities ac
            LEFT JOIN users u ON ac.created_by = u.id
            WHERE ac.moderation_status = ?
            ORDER BY ac.last_submitted_at DESC
        ");
        $stmt->execute([$status]);
        $items = array_merge($items, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if ($contentType === 'all' || $contentType === 'place') {
        $stmt = $pdo->prepare("
            SELECT 
                'place' as content_type,
                p.id,
                p.name,
                p.municipality,
                p.province,
                p.moderation_status,
                p.last_submitted_at,
                p.created_at,
                CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                u.email as user_email,
                p.photo1 as photo
            FROM places_of_interest p
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.moderation_status = ?
            ORDER BY p.last_submitted_at DESC
        ");
        $stmt->execute([$status]);
        $items = array_merge($items, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Ordenar por fecha
    usort($items, function($a, $b) {
        return strtotime($b['last_submitted_at']) - strtotime($a['last_submitted_at']);
    });

} catch (PDOException $e) {
    $error = "Error al cargar contenido: " . $e->getMessage();
}

// Obtener estadísticas
$stats = [
    'accommodation_pending' => 0,
    'event_pending' => 0,
    'activity_pending' => 0,
    'place_pending' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM accommodations WHERE moderation_status = 'pending'");
    $stats['accommodation_pending'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM cultural_events WHERE moderation_status = 'pending'");
    $stats['event_pending'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM tourist_activities WHERE moderation_status = 'pending'");
    $stats['activity_pending'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM places_of_interest WHERE moderation_status = 'pending'");
    $stats['place_pending'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Ignorar errores de estadísticas
}

$totalPending = array_sum($stats);

// Función para obtener el icono según el tipo
function getContentIcon($type) {
    $icons = [
        'accommodation' => 'fa-bed',
        'event' => 'fa-calendar-alt',
        'activity' => 'fa-hiking',
        'place' => 'fa-map-marker-alt'
    ];
    return $icons[$type] ?? 'fa-file';
}

// Función para obtener el nombre del tipo
function getContentTypeName($type) {
    $names = [
        'accommodation' => 'Alojamiento',
        'event' => 'Evento',
        'activity' => 'Actividad',
        'place' => 'Lugar'
    ];
    return $names[$type] ?? $type;
}
?>

        <!-- Estadísticas -->
        <div class="moderation-stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['accommodation_pending'] ?></div>
                <div class="stat-label">Alojamientos Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['event_pending'] ?></div>
                <div class="stat-label">Eventos Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['activity_pending'] ?></div>
                <div class="stat-label">Actividades Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['place_pending'] ?></div>
                <div class="stat-label">Lugares Pendientes</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <div class="filter-group">
                <label for="contentType">Tipo de Contenido:</label>
                <select id="contentType" onchange="applyFilters()">
                    <option value="all" <?= $contentType === 'all' ? 'selected' : '' ?>>Todos</option>
                    <option value="accommodation" <?= $contentType === 'accommodation' ? 'selected' : '' ?>>Alojamientos</option>
                    <option value="event" <?= $contentType === 'event' ? 'selected' : '' ?>>Eventos</option>
                    <option value="activity" <?= $contentType === 'activity' ? 'selected' : '' ?>>Actividades</option>
                    <option value="place" <?= $contentType === 'place' ? 'selected' : '' ?>>Lugares</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="status">Estado:</label>
                <select id="status" onchange="applyFilters()">
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pendientes</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Aprobados</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rechazados</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Borradores</option>
                </select>
            </div>
        </div>

        <!-- Lista de Contenido -->
        <?php if (empty($items)): ?>
            <div class="no-items">
                <i class="fas fa-inbox"></i>
                <h3>No hay contenido para moderar</h3>
                <p>No se encontró contenido con los filtros seleccionados</p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <div class="pending-item">
                    <?php if ($item['photo']): ?>
                        <img src="/<?= htmlspecialchars($item['photo']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="item-image">
                    <?php else: ?>
                        <div class="item-image" style="display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 2rem;">
                            <i class="fas <?= getContentIcon($item['content_type']) ?>"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="item-content">
                        <div class="item-header">
                            <div class="item-title"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="item-badge">
                                <i class="fas <?= getContentIcon($item['content_type']) ?>"></i>
                                <?= getContentTypeName($item['content_type']) ?>
                            </div>
                        </div>
                        
                        <div class="item-details">
                            <div><strong>Ubicación:</strong> <?= htmlspecialchars($item['municipality']) ?>, <?= htmlspecialchars($item['province']) ?></div>
                            <div><strong>Enviado por:</strong> <?= htmlspecialchars($item['created_by_name'] ?? 'Usuario desconocido') ?> 
                                <?php if ($item['user_email']): ?>
                                    (<?= htmlspecialchars($item['user_email']) ?>)
                                <?php endif; ?>
                            </div>
                            <div><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($item['last_submitted_at'] ?? $item['created_at'])) ?></div>
                            <div><strong>Estado:</strong> <?= htmlspecialchars($item['moderation_status']) ?></div>
                        </div>
                        
                        <div class="item-actions">
                            <button class="btn btn-edit" onclick="viewDetails('<?= $item['content_type'] ?>', <?= $item['id'] ?>)">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </button>
                            <?php if ($status === 'pending'): ?>
                                <button class="btn btn-approve" onclick="moderateContent('<?= $item['content_type'] ?>', <?= $item['id'] ?>, 'approve')">
                                    <i class="fas fa-check"></i> Aprobar
                                </button>
                                <button class="btn btn-reject" onclick="moderateContent('<?= $item['content_type'] ?>', <?= $item['id'] ?>, 'reject')">
                                    <i class="fas fa-times"></i> Rechazar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function applyFilters() {
            const type = document.getElementById('contentType').value;
            const status = document.getElementById('status').value;
            window.location.href = `?type=${type}&status=${status}`;
        }

        function viewDetails(type, id) {
            const urls = {
                'accommodation': `/alojamiento-detalle.html?id=${id}`,
                'event': `/evento-detalle.html?id=${id}`,
                'activity': `/actividad.html?id=${id}`,
                'place': `/lugar-interes.html?id=${id}`
            };
            window.open(urls[type] || '#', '_blank');
        }

        async function moderateContent(type, id, action) {
            if (!confirm(`¿Estás seguro de ${action === 'approve' ? 'aprobar' : 'rechazar'} este contenido?`)) {
                return;
            }

            const reason = action === 'reject' ? prompt('Motivo del rechazo (opcional):') : null;

            try {
                const response = await fetch(`/api/moderation/${action}.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        content_type: type,
                        content_id: id,
                        rejection_reason: reason
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert(`✅ Contenido ${action === 'approve' ? 'aprobado' : 'rechazado'} correctamente`);
                    location.reload();
                } else {
                    alert(`❌ Error: ${result.error}`);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error de conexión');
            }
        }
    </script>
</body>
</html>
           