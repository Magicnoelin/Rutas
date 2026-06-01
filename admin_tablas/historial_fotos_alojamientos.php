<?php
/**
 * Panel de Historial de Actividad de Fotos de Alojamientos
 * Muestra un registro de subidas y eliminaciones de fotos hechas por los alojamientos
 * SIN necesidad de moderar — solo informativo
 */

session_start();
require_once 'db.php';

// ── Filtros ──────────────────────────────────────────────────────────────────
$actionFilter = $_GET['action'] ?? 'all';
$accommodationFilter = trim($_GET['accommodation'] ?? '');
$daysFilter = (int)($_GET['days'] ?? 30);
if ($daysFilter < 1) $daysFilter = 30;

// ── Obtener datos ────────────────────────────────────────────────────────────
$logs = [];
$totalUploads = 0;
$totalDeletes = 0;
$accommodationsList = [];

try {
    // Crear tabla si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS accommodation_photo_activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            accommodation_id INT NOT NULL,
            accommodation_name VARCHAR(255) DEFAULT NULL,
            accommodation_slug VARCHAR(255) DEFAULT NULL,
            user_id INT DEFAULT NULL,
            user_name VARCHAR(255) DEFAULT NULL,
            action_type VARCHAR(50) NOT NULL COMMENT 'upload / delete',
            category VARCHAR(100) DEFAULT NULL,
            filename VARCHAR(255) DEFAULT NULL,
            file_url VARCHAR(500) DEFAULT NULL,
            details TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_accommodation (accommodation_id),
            INDEX idx_action_type (action_type),
            INDEX idx_created_at (created_at),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Obtener lista de alojamientos con actividad
    $stmtAcc = $pdo->query("
        SELECT DISTINCT accommodation_id, accommodation_name, accommodation_slug 
        FROM accommodation_photo_activity_log 
        ORDER BY accommodation_name ASC
    ");
    $accommodationsList = $stmtAcc->fetchAll(PDO::FETCH_ASSOC);

    // Construir WHERE dinámico
    $where = [];
    $params = [];

    // Filtro por días
    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $params[] = $daysFilter;

    // Filtro por tipo de acción
    if ($actionFilter !== 'all') {
        $where[] = "action_type = ?";
        $params[] = $actionFilter;
    }

    // Filtro por alojamiento
    if (!empty($accommodationFilter)) {
        $where[] = "(accommodation_id = ? OR accommodation_slug LIKE ? OR accommodation_name LIKE ?)";
        $params[] = (int)$accommodationFilter;
        $params[] = "%{$accommodationFilter}%";
        $params[] = "%{$accommodationFilter}%";
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Obtener logs
    $stmt = $pdo->prepare("
        SELECT * FROM accommodation_photo_activity_log 
        {$whereClause}
        ORDER BY created_at DESC 
        LIMIT 500
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas
    $totalUploads = $pdo->query("
        SELECT COUNT(*) FROM accommodation_photo_activity_log 
        WHERE action_type = 'upload' AND created_at >= DATE_SUB(NOW(), INTERVAL {$daysFilter} DAY)
    ")->fetchColumn();

    $totalDeletes = $pdo->query("
        SELECT COUNT(*) FROM accommodation_photo_activity_log 
        WHERE action_type = 'delete' AND created_at >= DATE_SUB(NOW(), INTERVAL {$daysFilter} DAY)
    ")->fetchColumn();

} catch (Exception $e) {
    $error = $e->getMessage();
}

// ── Iconos por acción ──
$actionIcons = [
    'upload' => 'fa-upload',
    'delete' => 'fa-trash-alt',
];
$actionColors = [
    'upload' => '#28a745',
    'delete' => '#dc3545',
];
$actionLabels = [
    'upload' => 'Subida',
    'delete' => 'Eliminación',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Fotos - Admin</title>
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
        .header .header-sub {
            font-size: .82rem;
            opacity: .7;
            margin-top: .3rem;
        }

        .container { max-width: 1400px; margin: 0 auto; padding: 1.5rem; }

        /* Stats */
        .stats { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .stat-card {
            background: white; border-radius: 10px; padding: 1rem 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,.08); display: flex; align-items: center; gap: 1rem;
            min-width: 180px;
        }
        .stat-icon { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .stat-icon.green { background: #e8f5e9; color: #2f5233; }
        .stat-icon.red { background: #fbe9e7; color: #c62828; }
        .stat-icon.blue { background: #e3f2fd; color: #1565c0; }
        .stat-num { font-size: 1.8rem; font-weight: 700; color: #2f5233; }
        .stat-label { font-size: .82rem; color: #888; }

        /* Filters */
        .filters {
            background: white; padding: 1rem 1.5rem; border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1.5rem;
            display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; gap: .3rem; }
        .filter-group label { font-size: .78rem; color: #666; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .filter-group select,
        .filter-group input {
            padding: .5rem .8rem; border: 2px solid #eee; border-radius: 8px;
            font-size: .9rem; cursor: pointer;
        }
        .filter-group select:focus,
        .filter-group input:focus { outline: none; border-color: #2f5233; }
        .btn-filter {
            padding: .5rem 1.2rem; background: #2f5233; color: white;
            border: none; border-radius: 8px; font-size: .9rem; font-weight: 600;
            cursor: pointer; transition: all .2s;
        }
        .btn-filter:hover { background: #3d6b42; }

        /* Timeline */
        .timeline-info {
            background: #fff8e1; border: 1px solid #ffe082; border-radius: 10px;
            padding: 1rem 1.5rem; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: .8rem;
            font-size: .9rem; color: #6d4c00;
        }
        .timeline-info i { font-size: 1.3rem; }

        .log-list { display: flex; flex-direction: column; gap: .6rem; }

        .log-item {
            background: white; border-radius: 10px; padding: 1rem 1.2rem;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
            display: flex; align-items: center; gap: 1rem;
            transition: all .2s;
            border-left: 4px solid #ddd;
        }
        .log-item:hover { box-shadow: 0 3px 12px rgba(0,0,0,.1); }
        .log-item.type-upload { border-left-color: #28a745; }
        .log-item.type-delete { border-left-color: #dc3545; }

        .log-icon {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0;
        }
        .log-icon.icon-upload { background: #e8f5e9; color: #28a745; }
        .log-icon.icon-delete { background: #fbe9e7; color: #dc3545; }

        .log-body { flex: 1; min-width: 0; }
        .log-action {
            font-size: .82rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .5px; margin-bottom: .2rem;
        }
        .log-action.action-upload { color: #28a745; }
        .log-action.action-delete { color: #dc3545; }
        .log-accommodation {
            font-size: .95rem; font-weight: 700; color: #333;
            margin-bottom: .2rem;
        }
        .log-accommodation a { color: #2f5233; text-decoration: none; }
        .log-accommodation a:hover { text-decoration: underline; }
        .log-meta {
            font-size: .82rem; color: #888;
            display: flex; flex-wrap: wrap; gap: .8rem;
        }
        .log-meta span { display: inline-flex; align-items: center; gap: .3rem; }

        .log-time {
            font-size: .78rem; color: #aaa; white-space: nowrap;
            text-align: right; flex-shrink: 0;
        }

        .log-thumb {
            width: 60px; height: 45px; border-radius: 6px; overflow: hidden;
            flex-shrink: 0; background: #f0f0f0;
            display: flex; align-items: center; justify-content: center;
        }
        .log-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .log-thumb .no-img { color: #ccc; font-size: 1.2rem; }

        .empty-state {
            text-align: center; padding: 4rem; color: #aaa;
            background: white; border-radius: 12px;
        }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; display: block; }

        .back-link {
            display: inline-flex; align-items: center; gap: .4rem;
            color: #2f5233; text-decoration: none; font-weight: 600;
            font-size: .9rem; margin-bottom: 1rem;
        }
        .back-link:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .log-item { flex-wrap: wrap; }
            .log-time { width: 100%; text-align: left; margin-top: .3rem; }
        }
    </style>
</head>
<body>

<div class="header">
    <h1><i class="fas fa-history"></i> Historial de Fotos de Alojamientos</h1>
    <p>Registro de subidas y eliminaciones de fotos realizadas por los alojamientos</p>
    <div class="header-sub">
        <i class="fas fa-info-circle"></i> 
        Solo informativo — no requiere moderación. Las fotos se muestran directamente en la web.
    </div>
</div>

<div class="container">

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-upload"></i></div>
            <div>
                <div class="stat-num"><?= $totalUploads ?></div>
                <div class="stat-label">Subidas (últ. <?= $daysFilter ?> días)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-trash-alt"></i></div>
            <div>
                <div class="stat-num"><?= $totalDeletes ?></div>
                <div class="stat-label">Eliminaciones (últ. <?= $daysFilter ?> días)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-list"></i></div>
            <div>
                <div class="stat-num"><?= count($logs) ?></div>
                <div class="stat-label">Registros mostrados</div>
            </div>
        </div>
        <div class="stat-card" style="margin-left:auto;">
            <a href="menu.php" style="color:#2f5233;text-decoration:none;font-size:.9rem;font-weight:600;">
                <i class="fas fa-arrow-left"></i> Volver al panel
            </a>
        </div>
    </div>

    <!-- Filters -->
    <form class="filters" method="GET">
        <div class="filter-group">
            <label>Acción</label>
            <select name="action">
                <option value="all" <?= $actionFilter === 'all' ? 'selected' : '' ?>>Todas</option>
                <option value="upload" <?= $actionFilter === 'upload' ? 'selected' : '' ?>>Subidas</option>
                <option value="delete" <?= $actionFilter === 'delete' ? 'selected' : '' ?>>Eliminaciones</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Alojamiento</label>
            <input type="text" name="accommodation" placeholder="Nombre, slug o ID..." 
                   value="<?= htmlspecialchars($accommodationFilter) ?>">
        </div>
        <div class="filter-group">
            <label>Últimos</label>
            <select name="days">
                <option value="7" <?= $daysFilter === 7 ? 'selected' : '' ?>>7 días</option>
                <option value="15" <?= $daysFilter === 15 ? 'selected' : '' ?>>15 días</option>
                <option value="30" <?= $daysFilter === 30 ? 'selected' : '' ?>>30 días</option>
                <option value="60" <?= $daysFilter === 60 ? 'selected' : '' ?>>60 días</option>
                <option value="90" <?= $daysFilter === 90 ? 'selected' : '' ?>>90 días</option>
                <option value="365" <?= $daysFilter === 365 ? 'selected' : '' ?>>1 año</option>
                <option value="9999" <?= $daysFilter === 9999 ? 'selected' : '' ?>>Todo</option>
            </select>
        </div>
        <div class="filter-group">
            <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filtrar</button>
        </div>
    </form>

    <!-- Info -->
    <div class="timeline-info">
        <i class="fas fa-clock"></i>
        <span>
            Mostrando actividad de los últimos <strong><?= $daysFilter === 9999 ? 'todos' : $daysFilter . ' días' ?></strong>.
            Los registros se crean automáticamente cuando un alojamiento sube o elimina fotos.
        </span>
    </div>

    <!-- Log list -->
    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <i class="fas fa-check-circle" style="color:#28a745;"></i>
            <h3>Sin actividad registrada</h3>
            <p>No hay movimientos de fotos en el período seleccionado.</p>
            <?php if (!empty($error)): ?>
                <p style="color:#dc3545;font-size:.85rem;margin-top:.5rem;">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="log-list">
            <?php foreach ($logs as $log): 
                $actionType = $log['action_type'];
                $icon = $actionIcons[$actionType] ?? 'fa-question';
                $color = $actionColors[$actionType] ?? '#888';
                $label = $actionLabels[$actionType] ?? $actionType;
                $imgUrl = $log['file_url'] ?? '';
                $hasImg = !empty($imgUrl) && (str_starts_with($imgUrl, '/') || str_starts_with($imgUrl, 'http'));
            ?>
                <div class="log-item type-<?= $actionType ?>">
                    <div class="log-icon icon-<?= $actionType ?>">
                        <i class="fas <?= $icon ?>"></i>
                    </div>

                    <?php if ($hasImg): ?>
                    <div class="log-thumb">
                        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="" loading="lazy"
                             onerror="this.parentElement.innerHTML='<div class=\'no-img\'><i class=\'fas fa-image\'></i></div>'">
                    </div>
                    <?php else: ?>
                    <div class="log-thumb">
                        <div class="no-img"><i class="fas fa-image"></i></div>
                    </div>
                    <?php endif; ?>

                    <div class="log-body">
                        <div class="log-action action-<?= $actionType ?>">
                            <i class="fas <?= $icon ?>"></i> <?= $label ?>
                        </div>
                        <div class="log-accommodation">
                            <a href="https://rutasrurales.io/alojamiento/<?= htmlspecialchars($log['accommodation_slug'] ?? '') ?>" target="_blank">
                                <?= htmlspecialchars($log['accommodation_name'] ?? 'Alojamiento #' . $log['accommodation_id']) ?>
                            </a>
                        </div>
                        <div class="log-meta">
                            <?php if (!empty($log['category'])): ?>
                                <span><i class="fas fa-tag"></i> <?= htmlspecialchars($log['category']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($log['filename'])): ?>
                                <span><i class="fas fa-file"></i> <?= htmlspecialchars($log['filename']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($log['user_name'])): ?>
                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($log['user_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($log['details'])): ?>
                                <span><i class="fas fa-comment"></i> <?= htmlspecialchars($log['details']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="log-time">
                        <i class="fas fa-clock"></i>
                        <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
