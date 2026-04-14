<?php
/**
 * Panel de Moderación Unificado
 * Modera: Alojamientos, Eventos, Actividades y Lugares de Interés
 */

session_start();
require_once 'db.php';

// Verificar que el usuario es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.html');
    exit;
}

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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderación de Contenido - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        
        .header {
            background: linear-gradient(135deg, #2f5233 0%, #4a7c4e 100%);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 { font-size: 1.8rem; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 0.95rem; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: #e8f5e9;
            color: #2f5233;
        }
        
        .stat-info h3 { font-size: 2rem; color: #2f5233; margin-bottom: 0.25rem; }
        .stat-info p { color: #666; font-size: 0.9rem; }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-size: 0.85rem;
            color: #666;
            font-weight: 600;
        }
        
        .filter-group select {
            padding: 0.6rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: #2f5233;
        }
        
        .content-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .content-item {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            gap: 1.5rem;
            align-items: start;
            transition: background 0.2s;
        }
        
        .content-item:hover { background: #f9f9f9; }
        .content-item:last-child { border-bottom: none; }
        
        .content-photo {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
            background: #f0f0f0;
            flex-shrink: 0;
        }
        
        .content-info { flex-grow: 1; }
        
        .content-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .content-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .badge-accommodation { background: #e3f2fd; color: #1976d2; }
        .badge-event { background: #fff3e0; color: #f57c00; }
        .badge-activity { background: #e8f5e9; color: #388e3c; }
        .badge-place { background: #fce4ec; color: #c2185b; }
        
        .content-title {
            font-size: 1.1rem;
            color: #333;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .content-meta {
            display: flex;
            gap: 1.5rem;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }
        
        .content-meta i { color: #2f5233; }
        
        .content-user {
            background: #f5f5f5;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #666;
        }
        
        .content-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        
        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-approve {
            background: #4caf50;
            color: white;
        }
        
        .btn-approve:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
        }
        
        .btn-reject {
            background: #f44336;
            color: white;
        }
        
        .btn-reject:hover {
            background: #da190b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
        }
        
        .btn-view {
            background: #2196f3;
            color: white;
        }
        
        .btn-view:hover {
            background: #0b7dda;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
            color: #ddd;
        }
        
        @media (max-width: 768px) {
            .content-item {
                flex-direction: column;
            }
            
            .content-actions {
                width: 100%;
                justify-content: stretch;
            }
            
            .btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-tasks"></i> Moderación de Contenido</h1>
        <p>Gestiona y modera todo el contenido enviado por los usuarios</p>
    </div>

    <div class="container">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-bed"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['accommodation_pending'] ?></h3>
                    <p>Alojamientos Pendientes</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['event_pending'] ?></h3>
                    <p>Eventos Pendientes</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-hiking"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['activity_pending'] ?></h3>
                    <p>Actividades Pendientes</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['place_pending'] ?></h3>
                    <p>Lugares Pendientes</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <div class="filter-group">
                <label>Tipo de Contenido</label>
                <select id="contentTypeFilter" onchange="applyFilters()">
                    <option value="all" <?= $contentType === 'all' ? 'selected' : '' ?>>Todos</option>
                    <option value="accommodation" <?= $contentType === 'accommodation' ? 'selected' : '' ?>>Alojamientos</option>
                    <option value="event" <?= $contentType === 'event' ? 'selected' : '' ?>>Eventos</option>
                    <option value="activity" <?= $contentType === 'activity' ? 'selected' : '' ?>>Actividades</option>
                    <option value="place" <?= $contentType === 'place' ? 'selected' : '' ?>>Lugares</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Estado</label>
                <select id="statusFilter" onchange="applyFilters()">
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pendientes</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Aprobados</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rechazados</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Borradores</option>
                </select>
            </div>
        </div>

        <!-- Lista de Contenido -->
        <div class="content-list">
            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No hay contenido para moderar</h3>
                    <p>No se encontró contenido con los filtros seleccionados</p>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <div class="content-item">
                        <?php if ($item['photo']): ?>
                            <img src="/<?= htmlspecialchars($item['photo']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="content-photo">
                        <?php else: ?>
                            <div class="content-photo" style="display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 2rem;">
                                <i class="fas <?= getContentIcon($item['content_type']) ?>"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="content-info">
                            <div class="content-header">
                                <span class="content-type-badge badge-<?= $item['content_type'] ?>">
                                    <i class="fas <?= getContentIcon($item['content_type']) ?>"></i>
                                    <?= getContentTypeName($item['content_type']) ?>
                                </span>
                            </div>
                            
                            <div class="content-title"><?= htmlspecialchars($item['name']) ?></div>
                            
                            <div class="content-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($item['municipality']) ?>, <?= htmlspecialchars($item['province']) ?></span>
                                <span><i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($item['last_submitted_at'] ?? $item['created_at'])) ?></span>
                            </div>
                            
                            <div class="content-user">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($item['created_by_name'] ?? 'Usuario desconocido') ?>
                                <?php if ($item['user_email']): ?>
                                    (<?= htmlspecialchars($item['user_email']) ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="content-actions">
                            <button class="btn btn-view" onclick="viewDetails('<?= $item['content_type'] ?>', <?= $item['id'] ?>)">
                                <i class="fas fa-eye"></i> Ver
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
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function applyFilters() {
            const type = document.getElementById('contentTypeFilter').value;
            const status = document.getElementById('statusFilter').value;
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
