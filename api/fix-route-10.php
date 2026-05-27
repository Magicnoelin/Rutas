<?php
/**
 * SCRIPT DE DIAGNÓSTICO Y REPARACIÓN RÁPIDA - RUTA 10
 */
require_once 'config.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Diagnóstico de Integridad: Ruta 10</h1>";

try {
    $pdo = getDBConnection();
    
    // 1. Verificar Estructura
    echo "<h2>1. Verificando estructura de <code>route_items</code></h2>";
    $cols = $pdo->query("DESCRIBE route_items")->fetchAll(PDO::FETCH_ASSOC);
    $has_item_type = false;
    $has_resource_type = false;
    
    echo "<ul>";
    foreach ($cols as $c) {
        echo "<li>Columna encontrada: <strong>{$c['Field']}</strong> ({$c['Type']})</li>";
        if ($c['Field'] === 'item_type') $has_item_type = true;
        if ($c['Field'] === 'resource_type') $has_resource_type = true;
    }
    echo "</ul>";

    if (!$has_item_type && $has_resource_type) {
        echo "<p style='color:orange'>⚠️ <strong>AVISO CRÍTICO:</strong> Tu tabla usa <code>resource_type</code> pero el panel de administración probablemente intenta guardar en <code>item_type</code>. Esto causa que el guardado falle silenciosamente.</p>";
    }

    // 2. Verificar Datos actuales
    echo "<h2>2. Buscando items vinculados para Ruta ID 10</h2>";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM route_items WHERE route_id = 10");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        echo "<p style='color:red'>❌ ERROR: La tabla <code>route_items</code> está VACÍA para la ruta 10.</p>";
        echo "<p><strong>Recomendación:</strong> Revisa el archivo <code>/admin_tablas/rutas.php</code>. Asegúrate de que la sentencia SQL INSERT use los nombres de columna correctos detectados en el paso 1.</p>";
    } else {
        echo "<p style='color:green'>✅ Se encontraron $count items vinculados en la base de datos.</p>";
    }

    // 3. Verificación de Eventos
    echo "<h2>3. Verificación de Eventos vinculados</h2>";
    $sql = ($has_item_type) ? "SELECT * FROM route_items WHERE route_id = 10 AND item_type IN ('event','evento')" : "SELECT * FROM route_items WHERE route_id = 10 AND resource_type IN ('event','evento')";
    $stmt = $pdo->query($sql);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($events) > 0) {
        echo "<p>✅ Hay eventos vinculados manualmente. Si no ves las fotos, es debido al filtro de nombres de archivo que ya hemos corregido en el index.php.</p>";
    } else {
        echo "<p>ℹ️ No hay eventos vinculados manualmente. El sistema está usando la búsqueda automática por fechas.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}