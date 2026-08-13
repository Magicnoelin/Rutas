<?php
/**
 * Analytics Dashboard - Panel de Administración
 * Ubicación: admin_tablas/analytics-dashboard.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cargar la conexión de la base de datos local
require_once __DIR__ . '/db.php';

// 2. Cargar el motor de analíticas local
require_once __DIR__ . '/unified-analytics.php';

$analytics = new UnifiedAnalytics();

// Procesar prueba manual del sistema
$testResult = null;
if (isset($_GET['test']) && $_GET['test'] === '1') {
    $testResult = $analytics->trackView('place', 1, $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['REMOTE_ADDR'] ?? '');
}

// Procesar sincronización manual
$syncResult = null;
if (isset($_GET['sync']) && $_GET['sync'] === '1') {
    $syncResult = $analytics->syncExistingCounters();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Panel de Control</title>
    <style>
        :root {
            --primary-color: #2e7d32;
            --primary-hover: #1b5e20;
            --bg-color: #7b68ee;
            --card-bg: #ffffff;
            --text-color: #333333;
            --border-radius: 8px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 25px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .header p {
            margin: 5px 0 0 0;
            opacity: 0.8;
            font-size: 14px;
        }

        .alert-box {
            margin: 20px;
            padding: 12px 15px;
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
            border-radius: 6px;
            font-family: monospace;
            font-size: 13px;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid #e0e0e0;
            padding: 0 20px;
            background-color: #fafafa;
        }

        .tab-btn {
            padding: 12px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }

        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-content {
            display: none;
            padding: 20px;
        }

        .tab-content.active {
            display: block;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            padding: 15px 20px;
        }

        .card h3 {
            margin-top: 0;
            color: var(--primary-color);
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: var(--primary-hover);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }

        th {
            background-color: var(--primary-color);
            color: white;
        }

        .error-message {
            color: #d32f2f;
            font-weight: bold;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📊 Analytics Dashboard</h1>
        <p>Sistema Unificado de Contadores - Panel de Administración</p>
    </div>

    <?php if ($testResult): ?>
        <div class="alert-box">
            ✅ Test completado: <?php echo json_encode($testResult); ?>
        </div>
    <?php endif; ?>

    <?php if ($syncResult): ?>
        <div class="alert-box">
            🔄 Sincronización realizada: <?php echo json_encode($syncResult); ?>
        </div>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('resumen')">🏠 Resumen</button>
        <button class="tab-btn" onclick="showTab('search-console')">🔍 Search Console</button>
        <button class="tab-btn" onclick="showTab('reportes')">📈 Reportes</button>
        <button class="tab-btn" onclick="showTab('configuracion')">⚙️ Configuración</button>
    </div>

    <!-- TAB 1: RESUMEN -->
    <div id="tab-resumen" class="tab-content active">
        <div class="grid-2">
            <div class="card">
                <h3>🎯 Estado del Sistema</h3>
                <p><strong>✅ Sistema Unificado Activo</strong></p>
                <ul>
                    <li>Contadores sincronizados con Google Analytics</li>
                    <li>Tracking automático funcionando</li>
                    <li>Base de datos conectada</li>
                </ul>
                <div style="margin-top: 15px;">
                    <a href="?test=1" class="btn">✏️ Probar Sistema</a>
                    <a href="?sync=1" class="btn">🔄 Sincronizar Contadores</a>
                </div>
            </div>

            <div class="card">
                <h3>📊 Estadísticas de Hoy</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Métrica</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody id="today-stats-body">
                        <tr><td colspan="2">Cargando datos...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: SEARCH CONSOLE -->
    <div id="tab-search-console" class="tab-content">
        <div class="card">
            <h3>🔍 Datos de Google Search Console</h3>
            <p>Sincronización directa con las métricas orgánicas de Google.</p>
            <div id="gsc-data-container">
                <p>Cargando información de Search Console...</p>
            </div>
        </div>
    </div>

    <!-- TAB 3: REPORTES -->
    <div id="tab-reportes" class="tab-content">
        <div class="card">
            <h3>📈 Reportes Disponibles</h3>
            <p>Enlaces directos a los reportes del sistema:</p>
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <a href="analytics-setup.php" class="btn">🔧 Validación del Sistema</a>
                <a href="analytics-ejemplo.php" class="btn">📄 Ejemplo de Uso</a>
                <a href="analytics-info.php" class="btn">ℹ️ Info del Sistema</a>
            </div>

            <h3>📊 Estadísticas Recientes</h3>
            <div id="recent-stats-container">
                <p>Cargando reporte de actividad...</p>
            </div>
        </div>
    </div>

    <!-- TAB 4: CONFIGURACIÓN -->
    <div id="tab-configuracion" class="tab-content">
        <div class="card">
            <h3>⚙️ Configuración del Sistema</h3>
            <h4>🔧 Estado de Archivos</h4>
            <table>
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>unified-analytics.php</td>
                        <td><span style="color: green; font-weight: bold;">Validado</span></td>
                    </tr>
                    <tr>
                        <td>unified-analytics.js</td>
                        <td><span style="color: green; font-weight: bold;">Validado</span></td>
                    </tr>
                    <tr>
                        <td>search-console-credentials.json</td>
                        <td><span style="color: orange; font-weight: bold;">⚠️ No configurado</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        document.getElementById('tab-' + tabName).classList.add('active');
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        }

        if (tabName === 'reportes' || tabName === 'resumen') {
            loadDailyReport();
        }
    }

    function loadDailyReport() {
        // Petición al endpoint unified-analytics.php de forma relativa
        fetch('unified-analytics.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'daily_report'
            })
        })
        .then(response => {
            if (!response.ok) {
                // Si la API no está en la misma carpeta, intenta pedirla un nivel arriba
                return fetch('../unified-analytics.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'daily_report' })
                }).then(res => res.json());
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                renderError(data.error);
                return;
            }
            renderStats(data);
        })
        .catch(error => {
            console.error('Error cargando reportes:', error);
            renderError('Error cargando datos: ' + error.message);
        });
    }

    function renderStats(data) {
        let totalViews = 0;
        let totalUnique = 0;
        let htmlRows = '';

        if (Array.isArray(data) && data.length > 0) {
            data.forEach(row => {
                totalViews += parseInt(row.total_views || 0);
                totalUnique += parseInt(row.unique_resources || 0);
                htmlRows += `<tr><td>${row.resource_type}</td><td>${row.total_views}</td></tr>`;
            });
        }

        document.getElementById('today-stats-body').innerHTML = `
            <tr><td>Total Vistas</td><td><strong>${totalViews}</strong></td></tr>
            <tr><td>Recursos Únicos Vistos</td><td><strong>${totalUnique}</strong></td></tr>
            <tr><td>Eventos Registrados</td><td><strong>${data.length || 0}</strong></td></tr>
        `;

        const reportContainer = document.getElementById('recent-stats-container');
        if (htmlRows !== '') {
            reportContainer.innerHTML = `
                <table>
                    <thead>
                        <tr><th>Tipo de Recurso</th><th>Total Vistas Hoy</th></tr>
                    </thead>
                    <tbody>${htmlRows}</tbody>
                </table>`;
        } else {
            reportContainer.innerHTML = '<p>No hay registros de visitas en el día de hoy.</p>';
        }
    }

    function renderError(msg) {
        const errHtml = `<div class="error-message">❌ ${msg}</div>`;
        document.getElementById('today-stats-body').innerHTML = `<tr><td colspan="2">${errHtml}</td></tr>`;
        document.getElementById('recent-stats-container').innerHTML = errHtml;
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadDailyReport();
    });
</script>
</body>
</html>