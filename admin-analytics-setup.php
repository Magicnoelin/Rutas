<?php
/**
 * Script de Instalación y Validación del Sistema Unificado de Analytics
 * 
 * Este archivo debe ejecutarse una vez para configurar el sistema
 * Después puede usarse para validar el funcionamiento
 */

require_once 'api/unified-analytics.php';

session_start();

$analytics = new UnifiedAnalytics();
$output = [];
$errors = [];

// Función helper para mostrar resultados
function showResult($title, $success, $message, $data = null) {
    global $output;
    $status = $success ? '✅' : '❌';
    $output[] = [
        'title' => $title,
        'status' => $status,
        'message' => $message,
        'data' => $data
    ];
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Configuración Sistema Analytics</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .result { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow: auto; }
        .button { display: inline-block; padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .button:hover { background: #0056b3; }
        h1 { color: #2F5233; }
        h2 { color: #666; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔧 Configuración Sistema Unificado de Analytics</h1>
<p>Este sistema sincroniza los contadores de visitas internos con Google Search Console para eliminar discrepancias.</p>
";

// 1. Verificar conexión a base de datos
try {
    $pdo = getDBConnection();
    showResult("Conexión a Base de Datos", true, "Conectado correctamente");
} catch (Exception $e) {
    showResult("Conexión a Base de Datos", false, "Error: " . $e->getMessage());
    exit;
}

// 2. Crear tabla de analytics_log
try {
    $analytics->createAnalyticsLogTable();
    showResult("Tabla analytics_log", true, "Tabla creada/verificada correctamente");
} catch (Exception $e) {
    showResult("Tabla analytics_log", false, "Error al crear tabla: " . $e->getMessage());
}

// 3. Verificar existencia de tabla resource_stats
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'resource_stats'");
    if ($stmt->rowCount() > 0) {
        showResult("Tabla resource_stats", true, "Tabla existe correctamente");
        
        // Obtener estructura de la tabla
        $columns = $pdo->query("DESCRIBE resource_stats")->fetchAll();
        showResult("Columnas resource_stats", true, "Estructura verificada", $columns);
    } else {
        showResult("Tabla resource_stats", false, "Tabla no existe - debe crearse manualmente");
        echo "<div class='result error'>
        <strong>ACCIÓN REQUERIDA:</strong> Ejecutar en MySQL:
        <pre>CREATE TABLE resource_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type VARCHAR(50) NOT NULL,
    resource_id INT NOT NULL,
    views_count INT DEFAULT 0,
    interests_count INT DEFAULT 0,
    messages_count INT DEFAULT 0,
    favorites_count INT DEFAULT 0,
    last_view_at TIMESTAMP NULL,
    last_interest_at TIMESTAMP NULL,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_resource (resource_type, resource_id),
    INDEX idx_type (resource_type),
    INDEX idx_views (views_count),
    INDEX idx_updated (updated_at)
);</pre>
        </div>";
    }
} catch (Exception $e) {
    showResult("Tabla resource_stats", false, "Error al verificar: " . $e->getMessage());
}

// 4. Sincronizar contadores existentes
if (isset($_GET['sync'])) {
    try {
        $synced = $analytics->syncExistingCounters();
        showResult("Sincronización de Contadores", true, "Contadores sincronizados", $synced);
    } catch (Exception $e) {
        showResult("Sincronización de Contadores", false, "Error: " . $e->getMessage());
    }
}

// 5. Probar tracking de una vista (simulación)
if (isset($_GET['test'])) {
    try {
        $result = $analytics->trackView('event', 1, 'Test User Agent', '127.0.0.1');
        showResult("Test de Tracking", $result['success'], "Resultado del test", $result);
    } catch (Exception $e) {
        showResult("Test de Tracking", false, "Error: " . $e->getMessage());
    }
}

// 6. Mostrar reporte diario
try {
    $report = $analytics->getDailyReport();
    showResult("Reporte Diario", true, "Estadísticas del día", $report);
} catch (Exception $e) {
    showResult("Reporte Diario", false, "Error: " . $e->getMessage());
}

// 7. Verificar archivos JavaScript
$jsFile = __DIR__ . '/js/unified-analytics.js';
if (file_exists($jsFile)) {
    $jsSize = filesize($jsFile);
    showResult("Archivo JavaScript", true, "Archivo exists (" . number_format($jsSize) . " bytes)");
} else {
    showResult("Archivo JavaScript", false, "Archivo /js/unified-analytics.js no encontrado");
}

// 8. Verificar integración en header.php
$headerFile = __DIR__ . '/header.php';
if (file_exists($headerFile)) {
    $headerContent = file_get_contents($headerFile);
    if (strpos($headerContent, 'unified-analytics.js') !== false) {
        showResult("Integración Header", true, "Sistema integrado en header.php");
    } else {
        showResult("Integración Header", false, "Sistema NO integrado en header.php");
    }
} else {
    showResult("Integración Header", false, "header.php no encontrado");
}

// Mostrar resultados
foreach ($output as $result) {
    $class = str_contains($result['status'], '✅') ? 'success' : 'error';
    echo "<div class='result $class'>";
    echo "<strong>{$result['status']} {$result['title']}</strong><br>";
    echo $result['message'];
    if ($result['data']) {
        echo "<pre>" . print_r($result['data'], true) . "</pre>";
    }
    echo "</div>";
}

?>

<h2>🛠️ Acciones Disponibles</h2>
<p>
    <a href="?sync=1" class="button">🔄 Sincronizar Contadores Existentes</a>
    <a href="?test=1" class="button">🧪 Probar Sistema de Tracking</a>
    <a href="api/unified-analytics.php?info=1" class="button">📊 Ver Info del Sistema</a>
    <a href="?" class="button">🔄 Refrescar Validación</a>
</p>

<h2>📋 Instrucciones de Uso</h2>
<div class="result info">
    <strong>Para páginas individuales:</strong>
    <pre>&lt;div data-resource-type="event" data-resource-id="123"&gt;&lt;/div&gt;</pre>
    
    <strong>Para tracking manual:</strong>
    <pre>// JavaScript
window.rutasAnalytics.trackView('accommodation', 456);</pre>
    
    <strong>Para obtener estadísticas:</strong>
    <pre>// JavaScript
const stats = await window.rutasAnalytics.getStats('place', 789);</pre>
</div>

<h2>🎯 Comparación con Google Search Console</h2>
<div class="result warning">
    <strong>Para verificar sincronización:</strong><br>
    1. Ejecutar este script diariamente<br>
    2. Comparar "Reporte Diario" con datos de Google Search Console<br>
    3. Las diferencias menores (±10%) son normales debido a filtros de bots<br>
    4. Si hay discrepancias grandes, verificar implementación de gtag
</div>

<h2>📈 Estado del Sistema</h2>
<?php
// Resumen final
$successCount = count(array_filter($output, fn($r) => str_contains($r['status'], '✅')));
$totalChecks = count($output);
$percentage = round(($successCount / $totalChecks) * 100);

$statusClass = $percentage >= 80 ? 'success' : ($percentage >= 60 ? 'warning' : 'error');
?>
<div class="result <?php echo $statusClass; ?>">
    <strong>Estado General: <?php echo $successCount; ?>/<?php echo $totalChecks; ?> checks passed (<?php echo $percentage; ?>%)</strong><br>
    
    <?php if ($percentage >= 80): ?>
        ✅ Sistema funcionando correctamente
    <?php elseif ($percentage >= 60): ?>
        ⚠️ Sistema funcional con advertencias - revisar items marcados
    <?php else: ?>
        ❌ Sistema requiere configuración - resolver errores críticos
    <?php endif; ?>
</div>

<div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 4px; font-size: 12px; color: #666;">
    <strong>Nota Técnica:</strong> Este sistema unificado reemplaza los contadores dispersos del proyecto original, 
    centralizando todo el tracking en la tabla <code>resource_stats</code> y sincronizando con Google Analytics 
    para garantizar coherencia con Google Search Console.
</div>

</div>
</body>
</html>