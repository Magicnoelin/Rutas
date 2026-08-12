<?php
session_start();
include 'db.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

try {
    // 1. Visitas e interacciones de HOY
    $stmtHoy = $pdo->query("
        SELECT 
            resource_type, 
            COUNT(*) as total_visitas,
            COUNT(DISTINCT ip_address) as visitantes_unicos
        FROM analytics_log 
        WHERE DATE(created_at) = CURDATE()
        GROUP BY resource_type
    ");
    $statsHoy = $stmtHoy->fetchAll(PDO::FETCH_ASSOC);

    // 2. TOP 10 Recursos con NOMBRES REALES
    // Usamos LEFT JOIN con comprobaciones de tablas habituales en singular/plural
    $sqlTop = "
        SELECT 
            rs.resource_type, 
            rs.resource_id, 
            rs.views_count, 
            rs.last_view_at,
            COALESCE(
                -- Si existe columna nombre o titulo
                e.nombre, e.titulo, e.title,
                e_pl.nombre, e_pl.titulo,
                a.nombre, a.titulo,
                a_pl.nombre, a_pl.titulo,
                act.nombre, act.titulo,
                p.nombre, p.titulo,
                l.nombre, l.titulo,
                CONCAT('Recurso #', rs.resource_id)
            ) AS nombre_recurso
        FROM resource_stats rs
        
        -- Eventos (probando tabla evento o events)
        LEFT JOIN evento e ON (rs.resource_type IN ('event', 'evento') AND rs.resource_id = e.id)
        LEFT JOIN events e_pl ON (rs.resource_type IN ('event', 'evento') AND rs.resource_id = e_pl.id)
        
        -- Alojamientos (probando tabla alojamiento o alojamientos)
        LEFT JOIN alojamiento a ON (rs.resource_type IN ('accommodation', 'alojamiento') AND rs.resource_id = a.id)
        LEFT JOIN alojamientos a_pl ON (rs.resource_type IN ('accommodation', 'alojamiento') AND rs.resource_id = a_pl.id)
        
        -- Actividades (probando actividad o actividades)
        LEFT JOIN actividad act ON (rs.resource_type IN ('activity', 'actividad') AND rs.resource_id = act.id)
        
        -- Pueblos / Lugares (probando pueblo o lugares)
        LEFT JOIN pueblo p ON (rs.resource_type IN ('place', 'pueblo', 'lugar') AND rs.resource_id = p.id)
        LEFT JOIN lugares l ON (rs.resource_type IN ('place', 'pueblo', 'lugar') AND rs.resource_id = l.id)
        
        ORDER BY rs.views_count DESC 
        LIMIT 10
    ";
    
    $stmtTop = $pdo->query($sqlTop);
    $topRecursos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

    // 3. Totales acumulados por tipo de contenido
    $stmtTotales = $pdo->query("
        SELECT 
            resource_type, 
            SUM(views_count) as total_views,
            SUM(interests_count) as total_intereses,
            SUM(messages_count) as total_mensajes
        FROM resource_stats 
        GROUP BY resource_type
    ");
    $totales = $stmtTotales->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si alguna tabla opcional de JOIN aún no existe, ejecutamos un fallback limpio sin JOINs para que la página cargue siempre
    $stmtTop = $pdo->query("
        SELECT 
            resource_type, 
            resource_id, 
            views_count, 
            last_view_at,
            CONCAT('Recurso #', resource_id) AS nombre_recurso
        FROM resource_stats 
        ORDER BY views_count DESC 
        LIMIT 10
    ");
    $topRecursos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

    $stmtHoy = $pdo->query("SELECT resource_type, COUNT(*) as total_visitas, COUNT(DISTINCT ip_address) as visitantes_unicos FROM analytics_log WHERE DATE(created_at) = CURDATE() GROUP BY resource_type");
    $statsHoy = $stmtHoy->fetchAll(PDO::FETCH_ASSOC);

    $stmtTotales = $pdo->query("SELECT resource_type, SUM(views_count) as total_views, SUM(interests_count) as total_intereses, SUM(messages_count) as total_mensajes FROM resource_stats GROUP BY resource_type");
    $totales = $stmtTotales->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>📊 Dashboard Diario - Rutas Rurales</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f4f6f8; padding: 20px; color: #333; }
        .container { max-width: 950px; margin: 0 auto; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
        h1 { margin-top: 0; color: #2F5233; font-size: 1.8rem; }
        h2 { font-size: 1.1rem; border-bottom: 2px solid #f0f0f0; padding-bottom: 8px; margin-top: 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .stat-box { background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #2F5233; }
        .stat-number { font-size: 1.8rem; font-weight: bold; color: #111; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #eee; }
        th { background: #fafafa; color: #666; font-size: 0.85rem; text-transform: uppercase; }
        .badge { background: #e8f5e9; color: #2e7d32; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Dashboard Diario de Tráfico</h1>
        <p style="color: #666;">Estado al día: <strong><?= date('d/m/Y H:i') ?></strong></p>

        <!-- RESUMEN DE HOY -->
        <div class="card">
            <h2>🔥 Actividad de Hoy (<?= date('d/m/Y') ?>)</h2>
            <?php if (empty($statsHoy)): ?>
                <p style="color: #777;">Aún no hay visitas registradas hoy en el nuevo sistema log.</p>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($statsHoy as $hoy): ?>
                        <div class="stat-box">
                            <small style="color:#666; font-weight:bold;"><?= strtoupper($hoy['resource_type']) ?></small>
                            <div class="stat-number"><?= number_format($hoy['total_visitas']) ?> <span style="font-size:0.9rem; font-weight:normal; color:#555;">páginas vistas</span></div>
                            <small style="color:#777;"> de <strong><?= $hoy['visitantes_unicos'] ?></strong> personas/IPs distintas</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- TOP 10 MÁS VISTOS CON NOMBRES REALES -->
      <!-- TOP 10 MÁS VISTOS CON NOMBRES REALES -->
        <div class="card">
            <h2>🏆 TOP 10 Contenidos Más Vistos (Histórico)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre / Título</th>
                        <th>Total Visitas</th>
                        <th>Última Visita</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topRecursos)): ?>
                        <tr><td colspan="4">Sin datos registrados en resource_stats.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topRecursos as $top): ?>
                            <tr>
                                <td><span class="badge"><?= ucfirst($top['resource_type']) ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($top['nombre_recurso']) ?></strong> 
                                    <small style="color:#999;">(#<?= $top['resource_id'] ?>)</small>
                                </td>
                                <td><?= number_format($top['views_count'] ?? 0) ?></td>
                                <td><?= $top['last_view_at'] ? date('d/m/Y H:i', strtotime($top['last_view_at'])) : 'Sin registro' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- TOTALES GLOBALES -->
        <div class="card">
            <h2>📈 Resumen Global Acumulado</h2>
            <table>
                <thead>
                    <tr>
                        <th>Sección</th>
                        <th>Visitas Totales</th>
                        <th>Intereses / Clics</th>
                        <th>Mensajes / Leads</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($totales)): ?>
                        <tr><td colspan="4">Sin datos acumulados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($totales as $tot): ?>
                            <tr>
                                <td><strong><?= ucfirst($tot['resource_type']) ?></strong></td>
                                <td><?= number_format($tot['total_views'] ?? 0) ?></td>
                                <td><?= number_format($tot['total_intereses'] ?? 0) ?></td>
                                <td><?= number_format($tot['total_mensajes'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>