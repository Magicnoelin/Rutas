<?php
/**
 * Dashboard Administrativo - Sistema Unificado de Analytics
 * 
 * Panel de control para administradores que permite:
 * 1. Ver estado del sistema de contadores
 * 2. Comparar con Google Search Console (opcional)
 * 3. Configurar credenciales de API
 * 4. Generar reportes
 */

session_start();
require_once '../api/config.php';

// Verificar si es administrador (ajustar según tu sistema de autenticación)
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: /login.html');
    exit;
}

// Verificar si existe la clase UnifiedAnalytics
if (file_exists('../api/unified-analytics.php')) {
    require_once '../api/unified-analytics.php';
    $analytics = new UnifiedAnalytics();
} else {
    $analytics = null;
}

// Configuración
$credentialsPath = __DIR__ . '/search-console-credentials.json';
$hasCredentials = file_exists($credentialsPath);

// Procesar acciones
$message = '';
$error = '';

if ($_POST) {
    try {
        if (isset($_POST['upload_credentials'])) {
            // Subir credenciales de Search Console
            if (isset($_FILES['credentials_file']) && $_FILES['credentials_file']['error'] === UPLOAD_ERR_OK) {
                $uploadedFile = $_FILES['credentials_file']['tmp_name'];
                $content = file_get_contents($uploadedFile);
                $json = json_decode($content, true);
                
                if ($json && isset($json['client_email']) && isset($json['private_key'])) {
                    file_put_contents($credentialsPath, $content);
                    $message = "✅ Credenciales de Search Console subidas correctamente";
                    $hasCredentials = true;
                } else {
                    $error = "❌ El archivo no es un JSON válido de Service Account";
                }
            } else {
                $error = "❌ Error subiendo el archivo";
            }
        }
        
        if (isset($_POST['test_system'])) {
            if ($analytics) {
                $result = $analytics->trackView('event', 999, 'Test Admin', $_SERVER['REMOTE_ADDR']);
                $message = "✅ Test completado: " . json_encode($result);
            }
        }
        
        if (isset($_POST['sync_counters'])) {
            if ($analytics) {
                $synced = $analytics->syncExistingCounters();
                $message = "✅ Contadores sincronizados: " . count($synced) . " registros actualizados";
            }
        }
        
        if (isset($_POST['clear_credentials'])) {
            if (file_exists($credentialsPath)) {
                unlink($credentialsPath);
                $hasCredentials = false;
                $message = "🗑️ Credenciales eliminadas";
            }
        }
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Obtener estadísticas del sistema
$systemStats = [];
if ($analytics) {
    try {
        $systemStats = $analytics->getDailyReport();
    } catch (Exception $e) {
        $error = "Error obteniendo estadísticas: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Analytics Dashboard - Admin</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            margin: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header { 
            background: linear-gradient(45deg, #2F5233, #4a7c59);
            color: white; 
            padding: 30px; 
            text-align: center;
        }
        .header h1 { margin: 0; font-size: 2.2em; font-weight: 300; }
        .content { padding: 30px; }
        .grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 25px; 
            margin: 20px 0;
        }
        .card { 
            background: #f8f9fa; 
            border-radius: 12px; 
            padding: 25px; 
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .card h3 { margin-top: 0; color: #2F5233; font-size: 1.3em; }
        .status-good { color: #28a745; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(45deg, #2F5233, #4a7c59);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 5px;
        }
        .button:hover {
            background: linear-gradient(45deg, #4a7c59, #2F5233);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(47, 82, 51, 0.4);
        }
        .button-danger { background: linear-gradient(45deg, #dc3545, #c82333); }
        .button-danger:hover { background: linear-gradient(45deg, #c82333, #bd2130); }
        .message { 
            padding: 15px; 
            margin: 20px 0; 
            border-radius: 8px; 
            font-weight: 500;
        }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .stats-table th, .stats-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .stats-table th {
            background: linear-gradient(45deg, #2F5233, #4a7c59);
            color: white;
            font-weight: 600;
        }
        .upload-area {
            border: 2px dashed #2F5233;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            margin: 20px 0;
            transition: all 0.3s;
        }
        .upload-area:hover { 
            border-color: #4a7c59; 
            background: #e9f7ef; 
        }
        .upload-area input[type="file"] {
            margin: 15px 0;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ced4da;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            overflow: auto;
            font-size: 13px;
            line-height: 1.4;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            color: #6c757d;
            font-weight: 500;
        }
        .tab.active {
            color: #2F5233;
            border-bottom-color: #2F5233;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Analytics Dashboard</h1>
            <p>Sistema Unificado de Contadores - Panel de Administración</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="tabs">
                <div class="tab active" onclick="showTab('overview')">🏠 Resumen</div>
                <div class="tab" onclick="showTab('search-console')">🔍 Search Console</div>
                <div class="tab" onclick="showTab('reports')">📈 Reportes</div>
                <div class="tab" onclick="showTab('config')">⚙️ Configuración</div>
            </div>
            
            <!-- Tab: Resumen -->
            <div id="overview" class="tab-content active">
                <div class="grid">
                    <div class="card">
                        <h3>🎯 Estado del Sistema</h3>
                        <?php if ($analytics): ?>
                            <p class="status-good">✅ Sistema Unificado Activo</p>
                            <p>• Contadores sincronizados con Google Analytics</p>
                            <p>• Tracking automático funcionando</p>
                            <p>• Base de datos conectada</p>
                        <?php else: ?>
                            <p class="status-error">❌ Sistema No Disponible</p>
                            <p>El archivo unified-analytics.php no se encuentra</p>
                        <?php endif; ?>
                        
                        <form method="POST" style="margin-top: 15px;">
                            <button type="submit" name="test_system" class="button">🧪 Probar Sistema</button>
                            <button type="submit" name="sync_counters" class="button">🔄 Sincronizar Contadores</button>
                        </form>
                    </div>
                    
                    <div class="card">
                        <h3>📊 Estadísticas de Hoy</h3>
                        <?php if (!empty($systemStats)): ?>
                            <table class="stats-table">
                                <tr>
                                    <th>Métrica</th>
                                    <th>Valor</th>
                                </tr>
                                <tr>
                                    <td>Total Vistas</td>
                                    <td><?php echo $systemStats['total_views'] ?? 0; ?></td>
                                </tr>
                                <tr>
                                    <td>Páginas Únicas</td>
                                    <td><?php echo $systemStats['unique_pages'] ?? 0; ?></td>
                                </tr>
                                <tr>
                                    <td>Eventos Registrados</td>
                                    <td><?php echo $systemStats['total_events'] ?? 0; ?></td>
                                </tr>
                            </table>
                        <?php else: ?>
                            <p class="status-warning">⚠️ No hay datos disponibles para hoy</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tab: Search Console -->
            <div id="search-console" class="tab-content">
                <div class="card">
                    <h3>🔍 Integración Google Search Console</h3>
                    
                    <?php if ($hasCredentials): ?>
                        <p class="status-good">✅ Credenciales configuradas</p>
                        <p>Puedes comparar tus datos internos con Search Console.</p>
                        
                        <div style="margin: 20px 0;">
                            <a href="../api/search-console-sync.php" class="button" target="_blank">
                                📊 Ver Comparación
                            </a>
                            <a href="../api/search-console-sync.php?start_date=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&end_date=<?php echo date('Y-m-d', strtotime('-1 day')); ?>" class="button" target="_blank">
                                📅 Últimos 7 Días
                            </a>
                        </div>
                        
                        <form method="POST" style="margin-top: 20px;">
                            <button type="submit" name="clear_credentials" class="button button-danger" 
                                    onclick="return confirm('¿Eliminar credenciales de Search Console?')">
                                🗑️ Eliminar Credenciales
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="status-warning">⚠️ Credenciales no configuradas</p>
                        <p>Para comparar con Search Console, necesitas configurar las credenciales de API.</p>
                        
                        <div class="upload-area">
                            <h4>📤 Subir Credenciales de Service Account</h4>
                            <p>Archivo JSON descargado de Google Cloud Console</p>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <input type="file" name="credentials_file" accept=".json" required>
                                <br>
                                <button type="submit" name="upload_credentials" class="button">
                                    📤 Subir Credenciales
                                </button>
                            </form>
                        </div>
                        
                        <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 8px; border: 1px solid #bee5eb;">
                            <h4>📝 Instrucciones:</h4>
                            <ol>
                                <li>Ir a <a href="https://console.developers.google.com/" target="_blank">Google Cloud Console</a></li>
                                <li>Crear proyecto y habilitar "Search Console API"</li>
                                <li>Crear Service Account y descargar JSON</li>
                                <li>En Search Console, agregar el email del Service Account</li>
                                <li>Subir el archivo JSON aquí</li>
                            </ol>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tab: Reportes -->
            <div id="reports" class="tab-content">
                <div class="card">
                    <h3>📈 Reportes Disponibles</h3>
                    <p>Enlaces directos a los reportes del sistema:</p>
                    
                    <div style="margin: 20px 0;">
                        <a href="../admin-analytics-setup.php" class="button" target="_blank">
                            🔧 Validación del Sistema
                        </a>
                        <a href="../ejemplo-uso-analytics.php" class="button" target="_blank">
                            📝 Ejemplo de Uso
                        </a>
                        <a href="../api/unified-analytics.php?info=1" class="button" target="_blank">
                            ℹ️ Info del Sistema
                        </a>
                    </div>
                    
                    <?php if ($analytics): ?>
                        <h4>📊 Estadísticas Recientes</h4>
                        <div id="recent-stats">Cargando...</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tab: Configuración -->
            <div id="config" class="tab-content">
                <div class="card">
                    <h3>⚙️ Configuración del Sistema</h3>
                    
                    <h4>🔧 Estado de Archivos</h4>
                    <table class="stats-table">
                        <tr>
                            <th>Archivo</th>
                            <th>Estado</th>
                        </tr>
                        <tr>
                            <td>unified-analytics.php</td>
                            <td class="<?php echo file_exists('../api/unified-analytics.php') ? 'status-good' : 'status-error'; ?>">
                                <?php echo file_exists('../api/unified-analytics.php') ? '✅ Existe' : '❌ No existe'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>unified-analytics.js</td>
                            <td class="<?php echo file_exists('../js/unified-analytics.js') ? 'status-good' : 'status-error'; ?>">
                                <?php echo file_exists('../js/unified-analytics.js') ? '✅ Existe' : '❌ No existe'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>search-console-credentials.json</td>
                            <td class="<?php echo $hasCredentials ? 'status-good' : 'status-warning'; ?>">
                                <?php echo $hasCredentials ? '✅ Configurado' : '⚠️ No configurado'; ?>
                            </td>
                        </tr>
                    </table>
                    
                    <h4>💡 Información Important</h4>
                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #ffeaa7;">
                        <p><strong>¿Por qué pueden diferir los contadores?</strong></p>
                        <ul>
                            <li><strong>Google Search Console:</strong> Solo clics desde búsquedas orgánicas</li>
                            <li><strong>Tu sistema:</strong> Todas las visitas (directas, redes sociales, etc.)</li>
                            <li><strong>Google Analytics:</strong> Igual que tu sistema (sincronizados)</li>
                        </ul>
                        <p>✅ <strong>Es normal</strong> que tu contador sea mayor que Search Console.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Ocultar todas las tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar tab seleccionada
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        // Cargar estadísticas recientes
        <?php if ($analytics): ?>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('../api/unified-analytics.php?action=daily_report')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('recent-stats');
                    if (data && !data.error) {
                        container.innerHTML = `
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        `;
                    } else {
                        container.innerHTML = '<p class="status-warning">⚠️ No hay datos disponibles</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('recent-stats').innerHTML = 
                        '<p class="status-error">❌ Error cargando datos: ' + error.message + '</p>';
                });
        });
        <?php endif; ?>
    </script>
</body>
</html>