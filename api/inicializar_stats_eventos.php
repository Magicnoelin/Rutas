<?php
/**
 * Script para inicializar estadísticas de eventos
 * Ejecutar una sola vez: /api/inicializar_stats_eventos.php
 * 
 * Este script crea registros en resource_stats para todos los eventos activos
 * que aún no tienen estadísticas registradas.
 */

define('API_NO_HEADERS', true);
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicializar Estadísticas de Eventos</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        button { background: #2F5233; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #3d6b42; }
    </style>
</head>
<body>
    <h1>🔧 Inicializar Estadísticas de Eventos</h1>
    
    <p>Este script creará registros de estadísticas en la tabla <code>resource_stats</code> para todos los eventos activos que aún no tengan estadísticas.</p>
    
    <h2>¿Qué hará este script?</h2>
    <ul>
        <li>Buscará todos los eventos activos en <code>cultural_events</code></li>
        <li>Creará un registro en <code>resource_stats</code> para cada evento sin estadísticas</li>
        <li>Si el evento ya tiene una columna <code>views</code>, copiará ese valor</li>
        <li>Los likes se inicializarán en 0</li>
    </ul>
    
    <hr>
    
<?php
try {
    $pdo = getDBConnection();
    
    // Contar eventos totales
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM cultural_events WHERE is_active = 1");
    $totalEventos = $stmtTotal->fetchColumn();
    
    // Contar eventos con estadísticas
    $stmtConStats = $pdo->query("SELECT COUNT(*) FROM resource_stats WHERE resource_type = 'event'");
    $eventosConStats = $stmtConStats->fetchColumn();
    
    // Contar eventos sin estadísticas
    $sinStats = $totalEventos - $eventosConStats;
    
    echo "<div class='info'>";
    echo "<strong>Estado actual:</strong><br>";
    echo "Total de eventos activos: <strong>$totalEventos</strong><br>";
    echo "Eventos con estadísticas: <strong>$eventosConStats</strong><br>";
    echo "Eventos sin estadísticas: <strong>$sinStats</strong>";
    echo "</div>";
    
    if ($sinStats == 0) {
        echo "<div class='success'>✅ ¡Todos los eventos ya tienen estadísticas inicializadas!</div>";
        echo "<p>No es necesario ejecutar este script de nuevo.</p>";
    } else {
        // Ejecutar la inserción
        $sql = "
            INSERT IGNORE INTO resource_stats (resource_type, resource_id, views_count, favorites_count, interests_count, messages_count, created_at, updated_at)
            SELECT 
                'event' AS resource_type,
                e.id AS resource_id,
                COALESCE(e.views, 0) AS views_count,
                0 AS favorites_count,
                0 AS interests_count,
                0 AS messages_count,
                NOW() AS created_at,
                NOW() AS updated_at
            FROM cultural_events e
            WHERE e.is_active = 1
            AND NOT EXISTS (
                SELECT 1 FROM resource_stats rs 
                WHERE rs.resource_type = 'event' AND rs.resource_id = e.id
            )
        ";
        
        $stmtInsert = $pdo->prepare($sql);
        $stmtInsert->execute();
        $insertados = $stmtInsert->rowCount();
        
        if ($insertados > 0) {
            echo "<div class='success'>✅ Se inicializaron <strong>$insertados</strong> registros de estadísticas.</div>";
        } else {
            echo "<div class='info'>ℹ️ No se inseraron nuevos registros (puede que ya existan).</div>";
        }
        
        // Mostrar algunos ejemplos
        echo "<h3>📊 Primeros 10 eventos con estadísticas:</h3>";
        $stmtExamples = $pdo->query("
            SELECT rs.resource_id, rs.views_count, rs.favorites_count, e.name AS evento
            FROM resource_stats rs
            JOIN cultural_events e ON e.id = rs.resource_id
            WHERE rs.resource_type = 'event'
            ORDER BY rs.views_count DESC
            LIMIT 10
        ");
        $examples = $stmtExamples->fetchAll(PDO::FETCH_ASSOC);
        
        if ($examples) {
            echo "<pre>";
            foreach ($examples as $ex) {
                echo "ID: {$ex['resource_id']} | Vistas: {$ex['views_count']} | Likes: {$ex['favorites_count']} | {$ex['evento']}\n";
            }
            echo "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
    
    <hr>
    <h3>🔗 Archivos relacionados</h3>
    <ul>
        <li><a href="/api/evento-stats.php?slug=san-pedro-regalado-valladolid-2026">Probar API de estadísticas</a></li>
        <li><a href="/evento/san-pedro-regalado-valladolid-2026">Ver página del evento</a></li>
    </ul>
    
    <p><a href="/">← Volver al inicio</a></p>
</body>
</html>
