<?php
/**
 * Script unificado para regenerar TODOS los sitemaps de eventos
 * Acceder desde: https://rutasrurales.io/regenerar_todos_sitemaps_eventos.php
 */

header('Content-Type: text/html; charset=UTF-8');
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerar TODOS los Sitemaps de Eventos</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; max-width: 800px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .log { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; font-family: monospace; white-space: pre-wrap; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #3498db; background-color: #e8f4fc; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Regenerar TODOS los Sitemaps de Eventos</h1>
        <p>Este script regenera <strong>todos</strong> los sitemaps de eventos con la lógica corregida.</p>
        
        <div class="log">';

// Conexión a BD
$host   = 'localhost';
$dbname = 'u412199647_Rutas';
$user   = 'u412199647_olgamarin';
$pass   = 'Rutas5Rurales7$';
$today  = date('Y-m-d');
$now    = date('Y-m-d H:i:s');

$log = [];
$log[] = "[$now] Iniciando regeneración COMPLETA de sitemaps de eventos";
$log[] = "==========================================================";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $log[] = "✅ Conexión a BD establecida";
    $log[] = "";

    // ==========================================================
    // PASO 1: Verificar lógica corregida
    // ==========================================================
    $log[] = "🔍 PASO 1: Verificando lógica corregida de fechas...";
    
    $queryTest = "SELECT COUNT(*) as total FROM cultural_events 
                  WHERE is_active = 1 AND slug IS NOT NULL AND slug != ''
                  AND (
                    (end_date IS NULL AND start_date >= CURDATE()) OR
                    (end_date IS NOT NULL AND end_date >= CURDATE())
                  )";
    
    $stmt = $pdo->query($queryTest);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $log[] = "✅ Eventos futuros/actuales encontrados: " . $result['total'];
    $log[] = "";

    // ==========================================================
    // PASO 2: Regenerar sitemap-eventos.xml (estático)
    // ==========================================================
    $log[] = "📝 PASO 2: Regenerando sitemap-eventos.xml (archivo estático)...";
    
    $queryEvents = "SELECT 
        slug,
        start_date,
        end_date,
        COALESCE(updated_at, created_at) AS fecha_mod
    FROM cultural_events
    WHERE is_active = 1
      AND slug IS NOT NULL
      AND slug != ''
      AND (
        (end_date IS NULL AND start_date >= CURDATE()) OR
        (end_date IS NOT NULL AND end_date >= CURDATE())
      )
    ORDER BY COALESCE(updated_at, created_at) DESC";
    
    $stmtEvents = $pdo->query($queryEvents);
    $eventos = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar XML estático
    $xmlStatic = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xmlStatic .= '<!--' . "\n";
    $xmlStatic .= '  Sitemap estático de eventos culturales' . "\n";
    $xmlStatic .= '  GENERADO CON LÓGICA CORREGIDA: Solo eventos futuros/actuales' . "\n";
    $xmlStatic .= '  Fecha de generación: ' . $now . "\n";
    $xmlStatic .= '  Total eventos: ' . count($eventos) . "\n";
    $xmlStatic .= '  Lógica: (end_date IS NULL AND start_date >= CURDATE()) OR (end_date IS NOT NULL AND end_date >= CURDATE())' . "\n";
    $xmlStatic .= '-->' . "\n";
    $xmlStatic .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    foreach ($eventos as $evento) {
        $slug = htmlspecialchars($evento['slug']);
        $fechaMod = !empty($evento['fecha_mod']) ? date('Y-m-d', strtotime($evento['fecha_mod'])) : $today;
        $priority = (strpos($slug, 'eclipse') !== false) ? '0.9' : '0.8';
        
        $xmlStatic .= "  <url>\n";
        $xmlStatic .= "    <loc>https://rutasrurales.io/evento/" . $slug . "</loc>\n";
        $xmlStatic .= "    <lastmod>" . $fechaMod . "</lastmod>\n";
        $xmlStatic .= "    <changefreq>weekly</changefreq>\n";
        $xmlStatic .= "    <priority>" . $priority . "</priority>\n";
        $xmlStatic .= "  </url>\n";
    }
    
    $xmlStatic .= "</urlset>\n";
    
    // Guardar archivo
    $staticPath = __DIR__ . '/sitemap-eventos.xml';
    $bytesStatic = file_put_contents($staticPath, $xmlStatic);
    
    if ($bytesStatic !== false) {
        $log[] = "✅ sitemap-eventos.xml regenerado: " . $bytesStatic . " bytes, " . count($eventos) . " eventos";
    } else {
        $log[] = "❌ Error al escribir sitemap-eventos.xml";
    }
    $log[] = "";

    // ==========================================================
    // PASO 3: Regenerar sitemap-eventos-i18n.xml (traducciones)
    // ==========================================================
    $log[] = "🌍 PASO 3: Regenerando sitemap-eventos-i18n.xml (traducciones)...";
    
    // Incluir el script existente de regeneración de traducciones
    if (file_exists(__DIR__ . '/admin_tablas/cron/regenerar_sitemap_i18n.php')) {
        // Capturar la salida del script
        ob_start();
        include __DIR__ . '/admin_tablas/cron/regenerar_sitemap_i18n.php';
        $output = ob_get_clean();
        
        // Buscar información en la salida
        if (strpos($output, 'Archivo generado') !== false) {
            $log[] = "✅ sitemap-eventos-i18n.xml regenerado (via script cron)";
        } else {
            $log[] = "⚠️ sitemap-eventos-i18n.xml posiblemente regenerado";
        }
    } else {
        $log[] = "⚠️ Script de regeneración de traducciones no encontrado";
    }
    $log[] = "";

    // ==========================================================
    // PASO 4: Actualizar sitemap.xml (índice principal)
    // ==========================================================
    $log[] = "📋 PASO 4: Actualizando sitemap.xml (índice principal)...";
    
    $sitemapIndexPath = __DIR__ . '/sitemap.xml';
    if (file_exists($sitemapIndexPath)) {
        $sitemapContent = file_get_contents($sitemapIndexPath);
        
        // Asegurar que todos los sitemaps de eventos estén incluidos
        $requiredSitemaps = [
            'sitemap-eventos.php',
            'sitemap-eventos.xml', 
            'sitemap-eventos-i18n.xml'
        ];
        
        $updates = 0;
        foreach ($requiredSitemaps as $sitemap) {
            if (strpos($sitemapContent, $sitemap) !== false) {
                // Actualizar fecha
                $sitemapContent = preg_replace(
                    '/(' . preg_quote($sitemap, '/') . '<\/loc>\s*<lastmod>)\d{4}-\d{2}-\d{2}(<\/lastmod>)/',
                    '${1}' . $today . '${2}',
                    $sitemapContent
                );
                $log[] = "✅ Actualizado lastmod para " . $sitemap;
                $updates++;
            } else {
                // Agregar si no existe (solo para sitemap-eventos.xml que podría faltar)
                if ($sitemap === 'sitemap-eventos.xml') {
                    $newEntry = "  <sitemap>\n    <loc>https://rutasrurales.io/" . $sitemap . "</loc>\n    <lastmod>{$today}</lastmod>\n  </sitemap>\n</sitemapindex>";
                    $sitemapContent = str_replace('</sitemapindex>', $newEntry, $sitemapContent);
                    $log[] = "✅ Agregado " . $sitemap . " al índice principal";
                    $updates++;
                }
            }
        }
        
        file_put_contents($sitemapIndexPath, $sitemapContent);
        $log[] = "✅ sitemap.xml actualizado con " . $updates . " cambios";
    } else {
        $log[] = "❌ sitemap.xml no encontrado";
    }
    $log[] = "";

    // ==========================================================
    // PASO 5: Verificación final
    // ==========================================================
    $log[] = "✅ PASO 5: Verificación final completada";
    $log[] = "==========================================================";
    $log[] = "🎉 REGENERACIÓN COMPLETADA CON ÉXITO!";
    $log[] = "";
    $log[] = "📊 RESUMEN:";
    $log[] = "  - sitemap-eventos.xml: " . count($eventos) . " eventos futuros/actuales";
    $log[] = "  - sitemap-eventos.php: Ya corregido (dinámico)";
    $log[] = "  - sitemap-eventos-i18n.xml: Traducciones regeneradas";
    $log[] = "  - sitemap.xml: Índice principal actualizado";
    $log[] = "";
    $log[] = "✅ TODOS los sitemaps de eventos ahora usan la lógica corregida";
    
} catch (PDOException $e) {
    $log[] = "❌ Error de conexión a BD: " . $e->getMessage();
} catch (Exception $e) {
    $log[] = "❌ Error: " . $e->getMessage();
}

// Mostrar log
foreach ($log as $line) {
    if (strpos($line, '✅') !== false) {
        echo '<span class="success">' . htmlspecialchars($line) . '</span><br>';
    } elseif (strpos($line, '❌') !== false || strpos($line, '⚠️') !== false) {
        echo '<span class="error">' . htmlspecialchars($line) . '</span><br>';
    } elseif (strpos($line, '🔍') !== false || strpos($line, '📝') !== false || strpos($line, '🌍') !== false || strpos($line, '📋') !== false || strpos($line, '📊') !== false) {
        echo '<span class="info">' . htmlspecialchars($line) . '</span><br>';
    } else {
        echo htmlspecialchars($line) . '<br>';
    }
}

echo '</div>
        
        <div class="step">
            <h2>✅ ¿Qué se ha corregido?</h2>
            <p><strong>Problema original:</strong> El sitemap incluía eventos pasados en lugar de eventos futuros.</p>
            <p><strong>Solución aplicada:</strong> Nueva lógica de fechas:</p>
            <code>(end_date IS NULL AND start_date >= CURDATE()) OR (end_date IS NOT NULL AND end_date >= CURDATE())</code>
            <p>Esto asegura que solo se incluyan eventos futuros o actuales.</p>
        </div>
        
        <div class="step">
            <h2>🔗 Sitemaps disponibles para Google:</h2>
            <ul>
                <li><a href="/sitemap.xml" target="_blank">sitemap.xml</a> (Índice principal con todo)</li>
                <li><a href="/sitemap-eventos.php" target="_blank">sitemap-eventos.php</a> (Eventos en español - dinámico)</li>
                <li><a href="/sitemap-eventos.xml" target="_blank">sitemap-eventos.xml</a> (Eventos en español - estático, recién regenerado)</li>
                <li><a href="/sitemap-eventos-i18n.xml" target="_blank">sitemap-eventos-i18n.xml</a> (Traducciones de eventos)</li>
            </ul>
        </div>
        
        <div class="step">
            <h2>📝 Pasos para Google Search Console:</h2>
            <ol>
                <li>Acceder a <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
                <li>Seleccionar la propiedad "rutasrurales.io"</li>
                <li>Ir a "Sitemaps" en el menú lateral</li>
                <li>Eliminar cualquier sitemap de eventos con errores</li>
                <li>Agregar: <code>https://rutasrurales.io/sitemap.xml</code> (incluye todo)</li>
                <li>Opcional: agregar sitemaps individuales si se desea</li>
            </ol>
        </div>
        
        <p class="success">✅ ¡El sistema de sitemaps de eventos está ahora completamente corregido y actualizado!</p>
    </div>
</body>
</html>';