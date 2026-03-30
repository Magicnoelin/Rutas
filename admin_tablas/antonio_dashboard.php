<?php
/**
 * Dashboard de Antonio - Análisis de conversaciones
 * Acceso protegido con .htpasswd (igual que el resto de admin_tablas)
 */
$logDir = dirname(__DIR__) . '/api/logs/antonio/';
$summaryFile = $logDir . 'resumen.json';

$resumen = [];
if (file_exists($summaryFile)) {
    $resumen = json_decode(file_get_contents($summaryFile), true) ?? [];
}

// Cargar logs del mes seleccionado
$mes = $_GET['mes'] ?? date('Y-m');
$logFile = $logDir . 'conversaciones_' . $mes . '.json';
$logs = [];
if (file_exists($logFile)) {
    $logs = json_decode(file_get_contents($logFile), true) ?? [];
}

// Listar meses disponibles
$archivos = glob($logDir . 'conversaciones_*.json');
$meses = [];
foreach ($archivos as $a) {
    preg_match('/conversaciones_(\d{4}-\d{2})\.json/', $a, $m);
    if ($m) $meses[] = $m[1];
}
rsort($meses);

$diasSemana = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Antonio - Rutas Rurales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f0; color: #333; }
        .header { background: linear-gradient(135deg, #2c5f2d, #4a8c4b); color: white; padding: 1.5rem 2rem; display: flex; align-items: center; gap: 1rem; }
        .header img { width: 50px; height: 50px; border-radius: 50%; border: 3px solid white; }
        .header h1 { font-size: 1.5rem; }
        .header p { font-size: 0.9rem; opacity: 0.8; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center; }
        .stat-card .number { font-size: 2.5rem; font-weight: bold; color: #2c5f2d; }
        .stat-card .label { color: #666; font-size: 0.9rem; margin-top: 0.3rem; }
        .stat-card .icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
        .card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .card h3 { color: #2c5f2d; margin-bottom: 1rem; font-size: 1.1rem; border-bottom: 2px solid #e8f5e9; padding-bottom: 0.5rem; }
        .keyword-cloud { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .keyword { background: #e8f5e9; color: #2c5f2d; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; }
        .keyword.big { font-size: 1rem; background: #2c5f2d; color: white; }
        .keyword.medium { font-size: 0.9rem; background: #4a8c4b; color: white; }
        .bar-chart { display: flex; flex-direction: column; gap: 0.5rem; }
        .bar-item { display: flex; align-items: center; gap: 0.5rem; }
        .bar-label { width: 80px; font-size: 0.85rem; color: #666; text-align: right; flex-shrink: 0; }
        .bar-track { flex: 1; background: #f0f4f0; border-radius: 4px; height: 24px; overflow: hidden; }
        .bar-fill { height: 100%; background: linear-gradient(90deg, #2c5f2d, #4a8c4b); border-radius: 4px; display: flex; align-items: center; padding-left: 8px; color: white; font-size: 0.8rem; min-width: 30px; transition: width 0.5s ease; }
        .logs-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .logs-table th { background: #2c5f2d; color: white; padding: 0.7rem 1rem; text-align: left; }
        .logs-table td { padding: 0.6rem 1rem; border-bottom: 1px solid #f0f4f0; vertical-align: top; }
        .logs-table tr:hover td { background: #f9fdf9; }
        .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: bold; }
        .badge-mobile { background: #e3f2fd; color: #1565c0; }
        .badge-desktop { background: #f3e5f5; color: #6a1b9a; }
        .badge-es { background: #fff3e0; color: #e65100; }
        .badge-en { background: #e8f5e9; color: #2e7d32; }
        .badge-fr { background: #fce4ec; color: #880e4f; }
        .badge-de { background: #e8eaf6; color: #283593; }
        .badge-zh { background: #fbe9e7; color: #bf360c; }
        .mes-selector { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .mes-btn { padding: 0.4rem 1rem; border-radius: 20px; border: 2px solid #2c5f2d; background: white; color: #2c5f2d; cursor: pointer; text-decoration: none; font-size: 0.9rem; }
        .mes-btn.active { background: #2c5f2d; color: white; }
        .empty { text-align: center; padding: 3rem; color: #999; }
        .empty i { font-size: 3rem; margin-bottom: 1rem; display: block; }
        .pregunta-text { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pregunta-text:hover { white-space: normal; overflow: visible; }
        .intereses-list { display: flex; flex-wrap: wrap; gap: 0.3rem; }
        .interes-tag { background: #e8f5e9; color: #2c5f2d; padding: 0.1rem 0.4rem; border-radius: 8px; font-size: 0.75rem; }
    </style>
</head>
<body>
<div class="header">
    <img src="../menu_images/antonio.jpg" alt="Antonio" onerror="this.src='../favicon.png'">
    <div>
        <h1>📊 Dashboard de Antonio</h1>
        <p>Análisis de conversaciones del asistente virtual · Rutas Rurales</p>
    </div>
</div>

<div class="container">

    <!-- Estadísticas globales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon">💬</div>
            <div class="number"><?= number_format($resumen['total_conversaciones'] ?? 0) ?></div>
            <div class="label">Conversaciones totales</div>
        </div>
        <div class="stat-card">
            <div class="icon">📱</div>
            <div class="number"><?= number_format($resumen['dispositivos']['mobile'] ?? 0) ?></div>
            <div class="label">Desde móvil</div>
        </div>
        <div class="stat-card">
            <div class="icon">🖥️</div>
            <div class="number"><?= number_format($resumen['dispositivos']['desktop'] ?? 0) ?></div>
            <div class="label">Desde escritorio</div>
        </div>
        <div class="stat-card">
            <div class="icon">🌍</div>
            <div class="number"><?= count($resumen['idiomas'] ?? []) ?></div>
            <div class="label">Idiomas detectados</div>
        </div>
        <div class="stat-card">
            <div class="icon">📅</div>
            <div class="number"><?= count($logs) ?></div>
            <div class="label">Conversaciones este mes</div>
        </div>
    </div>

    <!-- Selector de mes -->
    <?php if (!empty($meses)): ?>
    <div class="mes-selector">
        <span style="line-height: 2; color: #666; font-size: 0.9rem;">Ver mes:</span>
        <?php foreach ($meses as $m): ?>
            <a href="?mes=<?= $m ?>" class="mes-btn <?= $m === $mes ? 'active' : '' ?>"><?= $m ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="grid-2">
        <!-- Palabras clave más buscadas -->
        <div class="card">
            <h3><i class="fas fa-search"></i> Lo que más preguntan</h3>
            <?php if (!empty($resumen['palabras_clave'])): ?>
                <div class="keyword-cloud">
                    <?php
                    $maxVal = max($resumen['palabras_clave']);
                    foreach (array_slice($resumen['palabras_clave'], 0, 30, true) as $palabra => $count):
                        $ratio = $count / $maxVal;
                        $clase = $ratio > 0.6 ? 'big' : ($ratio > 0.3 ? 'medium' : '');
                    ?>
                        <span class="keyword <?= $clase ?>" title="<?= $count ?> veces"><?= htmlspecialchars($palabra) ?> <small>(<?= $count ?>)</small></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty"><i class="fas fa-cloud"></i>Sin datos aún</p>
            <?php endif; ?>
        </div>

        <!-- Intereses más populares -->
        <div class="card">
            <h3><i class="fas fa-heart"></i> Intereses más populares</h3>
            <?php if (!empty($resumen['intereses'])): ?>
                <?php
                arsort($resumen['intereses']);
                $maxInt = max($resumen['intereses']);
                $iconos = ['naturaleza'=>'🌲','cultura'=>'🏛️','relax'=>'🛋️','gastronomia'=>'🍷','aventura'=>'🧗','fotografia'=>'📸','familia'=>'👨‍👩‍👧','astronomia'=>'✨'];
                ?>
                <div class="bar-chart">
                    <?php foreach ($resumen['intereses'] as $interes => $count): ?>
                    <div class="bar-item">
                        <div class="bar-label"><?= ($iconos[$interes] ?? '•') . ' ' . ucfirst($interes) ?></div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= round(($count/$maxInt)*100) ?>%"><?= $count ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty"><i class="fas fa-chart-bar"></i>Sin datos aún</p>
            <?php endif; ?>
        </div>

        <!-- Actividad por hora -->
        <div class="card">
            <h3><i class="fas fa-clock"></i> Actividad por hora del día</h3>
            <?php if (!empty($resumen['por_hora'])): ?>
                <?php
                $maxHora = max($resumen['por_hora']);
                ksort($resumen['por_hora']);
                ?>
                <div class="bar-chart">
                    <?php foreach ($resumen['por_hora'] as $hora => $count): ?>
                    <div class="bar-item">
                        <div class="bar-label"><?= str_pad($hora, 2, '0', STR_PAD_LEFT) ?>:00</div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= round(($count/$maxHora)*100) ?>%"><?= $count ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty"><i class="fas fa-clock"></i>Sin datos aún</p>
            <?php endif; ?>
        </div>

        <!-- Actividad por día de la semana -->
        <div class="card">
            <h3><i class="fas fa-calendar-week"></i> Actividad por día de la semana</h3>
            <?php if (!empty($resumen['por_dia_semana'])): ?>
                <?php $maxDia = max($resumen['por_dia_semana']); ?>
                <div class="bar-chart">
                    <?php for ($d = 1; $d <= 7; $d++): ?>
                    <?php $count = $resumen['por_dia_semana'][$d] ?? 0; ?>
                    <div class="bar-item">
                        <div class="bar-label"><?= $diasSemana[$d] ?></div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $maxDia > 0 ? round(($count/$maxDia)*100) : 0 ?>%"><?= $count ?></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            <?php else: ?>
                <p class="empty"><i class="fas fa-calendar"></i>Sin datos aún</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabla de conversaciones recientes -->
    <div class="card">
        <h3><i class="fas fa-list"></i> Conversaciones recientes — <?= $mes ?> (<?= count($logs) ?> registros)</h3>
        <?php if (!empty($logs)): ?>
        <div style="overflow-x: auto; margin-top: 1rem;">
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Pregunta del usuario</th>
                        <th>Intereses</th>
                        <th>Días</th>
                        <th>Idioma</th>
                        <th>Dispositivo</th>
                        <th>Página</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($logs) as $log): ?>
                    <tr>
                        <td style="white-space: nowrap; color: #666; font-size: 0.8rem;"><?= htmlspecialchars($log['timestamp'] ?? '') ?></td>
                        <td>
                            <div class="pregunta-text" title="<?= htmlspecialchars($log['pregunta'] ?? '') ?>">
                                <?= htmlspecialchars($log['pregunta'] ?? '—') ?>
                            </div>
                        </td>
                        <td>
                            <div class="intereses-list">
                                <?php foreach (($log['intereses'] ?? []) as $i): ?>
                                    <span class="interes-tag"><?= htmlspecialchars($i) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td><?= $log['dias'] > 0 ? $log['dias'] . 'd' : '—' ?></td>
                        <td><span class="badge badge-<?= $log['idioma'] ?? 'es' ?>"><?= strtoupper($log['idioma'] ?? 'ES') ?></span></td>
                        <td><span class="badge badge-<?= $log['dispositivo'] ?? 'desktop' ?>"><?= ucfirst($log['dispositivo'] ?? 'desktop') ?></span></td>
                        <td style="font-size: 0.75rem; color: #999; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars(basename($log['pagina'] ?? '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty">
                <i class="fas fa-comments"></i>
                <p>No hay conversaciones registradas para <?= $mes ?></p>
                <p style="font-size: 0.85rem; margin-top: 0.5rem;">Las conversaciones aparecerán aquí cuando los usuarios interactúen con Antonio</p>
            </div>
        <?php endif; ?>
    </div>

    <p style="text-align: center; color: #999; font-size: 0.8rem; margin-top: 2rem;">
        Última actualización: <?= $resumen['ultima_actualizacion'] ?? 'Sin datos' ?> · 
        <a href="index.php" style="color: #2c5f2d;">← Volver al panel</a>
    </p>
</div>
</body>
</html>
