<?php
/**
 * Script para regenerar sitemap-eventos.xml (archivo estático) con la lógica corregida
 * Acceder desde: https://rutasrurales.io/regenerar_sitemap_eventos_xml.php
 */

header('Content-Type: text/html; charset=UTF-8');
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerar sitemap-eventos.xml</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; max-width: 800px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .log { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; font-family: monospace; white-space: pre-wrap; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Regenerar sitemap-eventos.xml</h1>
        <p>Este script regenera el archivo estático <code>sitemap-eventos.xml</code> con la lógica corregida.</p>
        
        <div class="log">';

// Conexión a BD
$host   = 'localhost';
$dbname = 'u412199647_Rutas';
$user   = 'u412199647_olgamarin';
$pass   = 'Rutas5Rurales7$';
$today  = date('Y-m-d');
$now    = date('Y-m-d H:i:s');

$log = [];
$log[] = "[$now] Iniciando regeneración de sitemap-eventos.xml";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $log[] = "✅ Conexión a BD establecida";

    // Obtener eventos futuros/actuales con la lógica corregida
    $log[] = "🔍 Obteniendo eventos futuros/actuales...";
    
    $query = "SELECT 
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
    
    $stmt = $pdo->query($query);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $log[] = "✅ Eventos futuros/actuales encontrados: " . count($eventos);
    
    // Generar XML
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<!--' . "\n";
    $xml .= '  Sitemap estático de eventos culturales' . "\n";
    $xml .= '  GENERADO CON LÓGICA CORREGIDA: Solo eventos futuros/actuales' . "\n";
    $xml .= '  Fecha de generación: ' . $now . "\n";
    $xml .= '  Total eventos: ' . count($eventos) . "\n";
    $xml .= '  Lógica: (end_date IS NULL AND start_date >= CURDATE()) OR (end_date IS NOT NULL AND end_date >= CURDATE())' . "\n";
    $xml .= '  NOTA: Este archivo es una versión estática. Para versión dinámica usar sitemap-eventos.php' . "\n";
    $xml .= '-->' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    foreach ($eventos as $evento) {
        $slug = htmlspecialchars($evento['slug']);
        $fechaMod = !empty($evento['fecha_mod']) ? date('Y-m-d', strtotime($evento['fecha_mod'])) : $today;
        
        // Prioridad especial para eventos de eclipse
        $priority = (strpos($slug, 'eclipse') !== false) ? '0.9' : '0.8';
        
        $xml .= "  <url>\n";
        $xml .= "    <loc>https://rutasrurales.io/evento/" . $slug . "</loc>\n";
        $xml .= "    <lastmod>" . $fechaMod . "</lastmod>\n";
        $xml .= "    <changefreq>weekly</changefreq>\n";
        $xml .= "    <priority>" . $priority . "</priority>\n";
        $xml .= "  </url>\n";
    }
    
    $xml .= "</urlset>\n";
    
    // Guardar archivo
    $filePath = __DIR__ . '/sitemap-eventos.xml';
    $bytesWritten = file_put_contents($filePath, $xml);
    
    if ($bytesWritten !== false) {
        $log[] = "✅ Archivo sitemap-eventos.xml regenerado: " . $bytesWritten . " bytes";
        $log[] = "📊 Eventos incluidos: " . count($eventos);
        
        // Mostrar algunos eventos como ejemplo
        if (count($eventos) > 0) {
            $log[] = "📋 Primeros 3 eventos incluidos:";
            for ($i = 0; $i < min(3, count($eventos)); $i++) {
                $log[] = "   - " . $eventos[$i]['slug'] . " (start: " . $eventos[$i]['start_date'] . ")";
            }
        }
        
        // Actualizar sitemap.xml para cambiar lastmod de sitemap-eventos.xml
        $sitemapIndexPath = __DIR__ . '/sitemap.xml';
        if (file_exists($sitemapIndexPath)) {
            $sitemapContent = file_get_contents($sitemapIndexPath);
            
            // Verificar si sitemap-eventos.xml está en el índice
            if (strpos($sitemapContent, 'sitemap-eventos.xml') === false) {
                // No existe, agregarlo antes del cierre de </sitemapindex>
                $newEntry = "  <sitemap>\n    <loc>https://rutasrurales.io/sitemap-eventos.xml</loc>\n    <lastmod>{$today}</lastmod>\n  </sitemap>\n</sitemapindex>";
                $sitemapContent = str_replace('</sitemapindex>', $newEntry, $sitemapContent);
                $log[] = "✅ Agregado sitemap-eventos.xml al índice principal";
            } else {
                // Ya existe, actualizar solo la fecha
                $sitemapContent = preg_replace(
                    '/(sitemap-eventos\.xml<\/loc>\s*<lastmod>)\d{4}-\d{2}-\d{2}(<\/lastmod>)/',
                    '${1}' . $today . '${2}',
                    $sitemapContent
                );
                $log[] = "✅ Actualizado lastmod de sitemap-eventos.xml en sitemap.xml";
            }
            
            file_put_contents($sitemapIndexPath, $sitemapContent);
        }
        
        $log[] = "🎉 Regeneración completada con éxito!";
        
    } else {
        $log[] = "❌ Error al escribir en sitemap-eventos.xml (posible problema de permisos)";
    }
    
} catch (PDOException $e) {
    $log[] = "❌ Error de conexión a BD: " . $e->getMessage();
} catch (Exception $e) {
    $log[] = "❌ Error: " . $e->getMessage();
}

// Mostrar log
foreach ($log as $line) {
    if (strpos($line, '✅') !== false) {
        echo '<span class="success">' . htmlspecialchars($line) . '</span><br>';
    } elseif (strpos($line, '❌') !== false) {
        echo '<span class="error">' . htmlspecialchars($line) . '</span><br>';
    } elseif (strpos($line, '🔍') !== false || strpos($line, '📊') !== false || strpos($line, '📋') !== false) {
        echo '<span class="info">' . htmlspecialchars($line) . '</span><br>';
    } else {
        echo htmlspecialchars($line) . '<br>';
    }
}

echo '</div>
        <h2>📋 Verificación:</h2>
        <ul>
            <li><a href="/sitemap-eventos.xml" target="_blank">Ver sitemap-eventos.xml regenerado</a></li>
            <li><a href="/sitemap.xml" target="_blank">Ver índice principal (sitemap.xml)</a></li>
            <li><a href="/sitemap-eventos.php" target="_blank">Comparar con versión dinámica (sitemap-eventos.php)</a></li>
        </ul>
        
        <h2>🎯 ¿Qué hace este script?</h2>
        <ol>
            <li>Consulta la base de datos con la <strong>lógica corregida</strong> de fechas</li>
            <li>Genera un nuevo archivo <code>sitemap-eventos.xml</code> estático</li>
            <li>Actualiza la fecha en <code>sitemap.xml</code> (índice principal)</li>
            <li><strong>SOLO incluye eventos futuros/actuales</strong> (no eventos pasados)</li>
        </ol>
        
        <p class="success">✅ El archivo sitemap-eventos.xml ahora estará corregido y listo para Google.</p>
    </div>
</body>
</html>';