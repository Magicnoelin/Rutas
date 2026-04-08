<?php
/**
 * Script para regenerar manualmente los sitemaps de eventos
 * Se puede ejecutar desde el navegador: https://rutasrurales.io/regenerar_sitemaps_ahora.php
 */

header('Content-Type: text/html; charset=UTF-8');
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerar Sitemaps de Eventos</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; max-width: 800px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .log { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; font-family: monospace; white-space: pre-wrap; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background-color: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Regenerar Sitemaps de Eventos</h1>
        <p>Este script regenera manualmente los sitemaps de eventos con la lógica corregida.</p>
        
        <div class="log">';

// Conexión a BD
$host   = 'localhost';
$dbname = 'u412199647_Rutas';
$user   = 'u412199647_olgamarin';
$pass   = 'Rutas5Rurales7$';
$today  = date('Y-m-d');
$now    = date('Y-m-d H:i:s');

$log = [];
$log[] = "[$now] Iniciando regeneración manual de sitemaps";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $log[] = "✅ Conexión a BD establecida";

    // 1. Primero, probar la nueva lógica de consulta
    $log[] = "🔍 Probando nueva lógica de consulta para eventos...";
    
    $queryTest = "SELECT COUNT(*) as total FROM cultural_events 
                  WHERE is_active = 1 AND slug IS NOT NULL AND slug != ''
                  AND (
                    (end_date IS NULL AND start_date >= CURDATE()) OR
                    (end_date IS NOT NULL AND end_date >= CURDATE())
                  )";
    
    $stmt = $pdo->query($queryTest);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $log[] = "✅ Eventos futuros/actuales encontrados: " . $result['total'];
    
    // 2. También probar para traducciones
    $queryTestTrads = "SELECT COUNT(*) as total FROM cultural_events_trads t
                       INNER JOIN cultural_events e ON e.id = t.event_id
                       WHERE t.language_code != 'es'
                         AND e.is_active = 1
                         AND t.slug IS NOT NULL
                         AND t.slug != ''
                         AND (
                           (e.end_date IS NULL AND e.start_date >= CURDATE()) OR
                           (e.end_date IS NOT NULL AND e.end_date >= CURDATE())
                         )";
    
    $stmt = $pdo->query($queryTestTrads);
    $resultTrads = $stmt->fetch(PDO::FETCH_ASSOC);
    $log[] = "✅ Traducciones de eventos futuros/actuales encontradas: " . $resultTrads['total'];
    
    // 3. Actualizar sitemap.xml para cambiar lastmod de sitemap-eventos.php
    $sitemapIndexPath = __DIR__ . '/sitemap.xml';
    if (file_exists($sitemapIndexPath)) {
        $sitemapContent = file_get_contents($sitemapIndexPath);
        // Actualizar la fecha del sitemap-eventos.php
        $sitemapContent = preg_replace(
            '/(sitemap-eventos\.php<\/loc>\s*<lastmod>)\d{4}-\d{2}-\d{2}(<\/lastmod>)/',
            '${1}' . $today . '${2}',
            $sitemapContent
        );
        
        if (file_put_contents($sitemapIndexPath, $sitemapContent) !== false) {
            $log[] = "✅ Actualizado lastmod en sitemap.xml para sitemap-eventos.php";
        } else {
            $log[] = "⚠️ No se pudo actualizar sitemap.xml (posible problema de permisos)";
        }
    }
    
    // 4. Crear un archivo sitemap-eventos.xml estático como copia de seguridad
    $log[] = "📝 Creando sitemap-eventos.xml estático...";
    
    // Primero obtener los eventos con la nueva lógica
    $queryEvents = "SELECT 
        id,
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
    
    // Obtener traducciones
    $queryTrads = "SELECT 
        t.event_id,
        t.language_code,
        t.slug AS slug_trad,
        e.start_date,
        e.end_date,
        COALESCE(e.updated_at, e.created_at) AS fecha_mod_trad
    FROM cultural_events_trads t
    INNER JOIN cultural_events e ON e.id = t.event_id
    WHERE t.language_code != 'es'
      AND e.is_active = 1
      AND t.slug IS NOT NULL
      AND t.slug != ''
      AND (
        (e.end_date IS NULL AND e.start_date >= CURDATE()) OR
        (e.end_date IS NOT NULL AND e.end_date >= CURDATE())
      )
    ORDER BY t.event_id, t.language_code";
    
    $stmtTrads = $pdo->query($queryTrads);
    $traducciones = $stmtTrads->fetchAll(PDO::FETCH_ASSOC);
    
    // Indexar traducciones
    $tradsByEventId = [];
    foreach ($traducciones as $trad) {
        $tradsByEventId[$trad['event_id']][$trad['language_code']] = [
            'slug' => $trad['slug_trad'],
            'fecha_mod' => $trad['fecha_mod_trad'],
        ];
    }
    
    // Generar XML estático
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<!--' . "\n";
    $xml .= '  Sitemap estático de eventos culturales' . "\n";
    $xml .= '  Generado manualmente el: ' . $now . "\n";
    $xml .= '  Lógica corregida: Solo eventos futuros/actuales' . "\n";
    $xml .= '  Eventos en español: ' . count($eventos) . "\n";
    $xml .= '  Traducciones: ' . count($traducciones) . "\n";
    $xml .= '  NOTA: Este archivo es una copia estática de sitemap-eventos.php' . "\n";
    $xml .= '-->' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
    
    // URLs en español
    foreach ($eventos as $evento) {
        $id = $evento['id'];
        $slugEs = htmlspecialchars($evento['slug']);
        $fechaMod = !empty($evento['fecha_mod']) ? date('Y-m-d', strtotime($evento['fecha_mod'])) : $today;
        $trads = $tradsByEventId[$id] ?? [];
        
        $priority = (strpos($slugEs, 'eclipse') !== false) ? '0.9' : '0.8';
        
        $xml .= "\n  <url>\n";
        $xml .= "    <loc>https://rutasrurales.io/evento/" . $slugEs . "</loc>\n";
        $xml .= "    <lastmod>" . $fechaMod . "</lastmod>\n";
        $xml .= "    <changefreq>weekly</changefreq>\n";
        $xml .= "    <priority>" . $priority . "</priority>\n";
        $xml .= "    <!-- x-default: versión por defecto -->\n";
        $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"https://rutasrurales.io/evento/" . $slugEs . "\"/>\n";
        $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"es\" href=\"https://rutasrurales.io/evento/" . $slugEs . "\"/>\n";
        
        foreach ($trads as $lang => $tradData) {
            $slugTrad = htmlspecialchars($tradData['slug']);
            $langCode = htmlspecialchars($lang);
            $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"" . $langCode . "\" href=\"https://rutasrurales.io/" . $langCode . "/evento/" . $slugTrad . "\"/>\n";
        }
        
        $xml .= "  </url>\n";
    }
    
    // URLs en otros idiomas
    foreach ($traducciones as $trad) {
        $eventId = $trad['event_id'];
        $lang = htmlspecialchars($trad['language_code']);
        $slugTrad = htmlspecialchars($trad['slug_trad']);
        $fechaMod = !empty($trad['fecha_mod_trad']) ? date('Y-m-d', strtotime($trad['fecha_mod_trad'])) : $today;
        
        // Buscar evento padre
        $eventoEs = null;
        foreach ($eventos as $e) {
            if ($e['id'] == $eventId) { 
                $eventoEs = $e; 
                break; 
            }
        }
        if (!$eventoEs) continue;
        
        $slugEs = htmlspecialchars($eventoEs['slug']);
        $trads = $tradsByEventId[$eventId] ?? [];
        
        $priority = (strpos($slugTrad, 'eclipse') !== false || strpos($slugTrad, 'sonnenfinsternis') !== false) ? '0.9' : '0.7';
        
        $xml .= "\n  <url>\n";
        $xml .= "    <loc>https://rutasrurales.io/" . $lang . "/evento/" . $slugTrad . "</loc>\n";
        $xml .= "    <lastmod>" . $fechaMod . "</lastmod>\n";
        $xml .= "    <changefreq>weekly</changefreq>\n";
        $xml .= "    <priority>" . $priority . "</priority>\n";
        $xml .= "    <!-- x-default apunta a la versión española -->\n";
        $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"https://rutasrurales.io/evento/" . $slugEs . "\"/>\n";
        $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"es\" href=\"https://rutasrurales.io/evento/" . $slugEs . "\"/>\n";
        $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"" . $lang . "\" href=\"https://rutasrurales.io/" . $lang . "/evento/" . $slugTrad . "\"/>\n";
        
        foreach ($trads as $otroLang => $otraTrad) {
            if ($otroLang === $trad['language_code']) continue;
            $otroSlug = htmlspecialchars($otraTrad['slug']);
            $otroLangCode = htmlspecialchars($otroLang);
            $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"" . $otroLangCode . "\" href=\"https://rutasrurales.io/" . $otroLangCode . "/evento/" . $otroSlug . "\"/>\n";
        }
        
        $xml .= "  </url>\n";
    }
    
    $xml .= "</urlset>\n";
    
    // Guardar archivo
    $staticPath = __DIR__ . '/sitemap-eventos.xml';
    if (file_put_contents($staticPath, $xml) !== false) {
        $log[] = "✅ Archivo sitemap-eventos.xml estático creado: " . filesize($staticPath) . " bytes";
        $log[] = "📊 Resumen: " . count($eventos) . " eventos + " . count($traducciones) . " traducciones";
    } else {
        $log[] = "❌ Error al crear sitemap-eventos.xml estático";
    }
    
    $log[] = "🎉 Regeneración completada con éxito!";
    
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
    } elseif (strpos($line, '🔍') !== false || strpos($line, '📝') !== false || strpos($line, '📊') !== false) {
        echo '<span class="info">' . htmlspecialchars($line) . '</span><br>';
    } else {
        echo htmlspecialchars($line) . '<br>';
    }
}

echo '</div>
        <p><strong>Pasos para Google Search Console:</strong></p>
        <ol>
            <li>Acceder a <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
            <li>Seleccionar la propiedad "rutasrurales.io"</li>
            <li>Ir a "Sitemaps" en el menú lateral</li>
            <li>Eliminar el sitemap antiguo si existe algún error</li>
            <li>Agregar el nuevo sitemap: <code>https://rutasrurales.io/sitemap-eventos.php</code></li>
            <li>Opcional: también agregar <code>https://rutasrurales.io/sitemap-eventos.xml</code> (versión estática)</li>
        </ol>
        
        <p><strong>Archivos disponibles:</strong></p>
        <ul>
            <li><a href="/sitemap-eventos.php" target="_blank">sitemap-eventos.php</a> (dinámico, ya corregido)</li>
            <li><a href="/sitemap-eventos.xml"