<?php
/**
 * Regenera sitemap-eventos.xml con hreflang para eventos con traducciones.
 *
 * - Eventos SIN traducciones → entrada simple (solo <loc>)
 * - Eventos CON traducciones → entrada con xhtml:link hreflang completo
 *   apuntando a todas las versiones de idioma (complementa sitemap-eventos-i18n.xml)
 *
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
        <p>Genera el sitemap de eventos en español con hreflang para los que tienen traducción.</p>
        <div class="log">';

// Conexión a BD
$host    = 'localhost';
$dbname  = 'u412199647_Rutas';
$user    = 'u412199647_olgamarin';
$pass    = 'Rutas5Rurales7$';
$baseUrl = 'https://rutasrurales.io';
$today   = date('Y-m-d');
$now     = date('Y-m-d H:i:s');

$log = [];
$log[] = "[$now] Iniciando regeneración de sitemap-eventos.xml";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $log[] = "✅ Conexión a BD establecida";

    // ── Paso 1: Obtener eventos futuros/actuales ─────────────────────────────────
    $log[] = "🔍 Obteniendo eventos futuros/actuales...";

    $stmtEventos = $pdo->query("
        SELECT
            e.id,
            e.slug,
            e.start_date,
            e.end_date,
            COALESCE(e.updated_at, e.created_at) AS fecha_mod
        FROM cultural_events e
        WHERE e.is_active = 1
          AND e.slug IS NOT NULL
          AND e.slug != ''
          AND (
              (e.end_date IS NULL     AND e.start_date >= CURDATE()) OR
              (e.end_date IS NOT NULL AND e.end_date   >= CURDATE())
          )
        ORDER BY COALESCE(e.updated_at, e.created_at) DESC
    ");
    $eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
    $log[] = "✅ Eventos futuros/actuales encontrados: " . count($eventos);

    // ── Paso 2: Cargar traducciones disponibles por evento ───────────────────────
    $stmtTrads = $pdo->query("
        SELECT event_id, language_code, slug
        FROM cultural_events_trads
        WHERE language_code IN ('en', 'fr', 'de', 'zh')
          AND slug IS NOT NULL
          AND slug != ''
    ");
    $tradsRows = $stmtTrads->fetchAll(PDO::FETCH_ASSOC);

    // Indexar por event_id → [lang => slug_trad]
    $tradsPorEvento = [];
    foreach ($tradsRows as $tr) {
        $tradsPorEvento[$tr['event_id']][$tr['language_code']] = $tr['slug'];
    }
    $log[] = "✅ Traducciones cargadas para " . count($tradsPorEvento) . " eventos";

    // ── Paso 3: Generar XML ──────────────────────────────────────────────────────
    // Usamos namespace xhtml para poder añadir hreflang donde haga falta
    $hasTrads = false; // si algún evento tiene traducciones, necesitamos el namespace
    foreach ($eventos as $ev) {
        if (!empty($tradsPorEvento[$ev['id']])) { $hasTrads = true; break; }
    }

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<!--' . "\n";
    $xml .= '  Sitemap de Eventos Culturales (español) — rutasrurales.io' . "\n";
    $xml .= '  Solo eventos vigentes y futuros.' . "\n";
    $xml .= '  Eventos con traducciones incluyen hreflang completo.' . "\n";
    $xml .= '  Las URLs traducidas (en/fr/de/zh) están en sitemap-eventos-i18n.xml' . "\n";
    $xml .= '  GENERADO AUTOMÁTICAMENTE — NO EDITAR MANUALMENTE' . "\n";
    $xml .= '  Última regeneración: ' . $now . "\n";
    $xml .= '  Total eventos: ' . count($eventos) . "\n";
    $xml .= '-->' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    $conHreflang = 0;
    $sinHreflang = 0;

    foreach ($eventos as $ev) {
        $slug    = htmlspecialchars($ev['slug'], ENT_XML1, 'UTF-8');
        $lastmod = !empty($ev['fecha_mod']) ? date('Y-m-d', strtotime($ev['fecha_mod'])) : $today;
        $langs   = $tradsPorEvento[$ev['id']] ?? [];

        $xml .= "\n  <url>\n";
        $xml .= "    <loc>{$baseUrl}/evento/{$slug}</loc>\n";
        $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
        $xml .= "    <changefreq>weekly</changefreq>\n";
        $xml .= "    <priority>0.8</priority>\n";

        if (!empty($langs)) {
            // Construir mapa completo de alternativas
            $alternativas = ['es' => "{$baseUrl}/evento/{$slug}"];
            foreach ($langs as $lang => $slugTrad) {
                $slugTradEsc = htmlspecialchars($slugTrad, ENT_XML1, 'UTF-8');
                $alternativas[$lang] = "{$baseUrl}/{$lang}/evento/{$slugTradEsc}";
            }
            // Añadir hreflang para todos los idiomas
            foreach ($alternativas as $hLang => $hUrl) {
                $hLangAttr = ($hLang === 'zh') ? 'zh-Hans' : $hLang;
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$hLangAttr}\" href=\"{$hUrl}\"/>\n";
            }
            $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$baseUrl}/evento/{$slug}\"/>\n";
            $conHreflang++;
        } else {
            $sinHreflang++;
        }

        $xml .= "  </url>\n";
    }

    $xml .= "\n</urlset>\n";

    // ── Paso 4: Guardar archivo ──────────────────────────────────────────────────
    $filePath     = __DIR__ . '/sitemap-eventos.xml';
    $bytesWritten = file_put_contents($filePath, $xml);

    if ($bytesWritten !== false) {
        $log[] = "✅ Archivo sitemap-eventos.xml regenerado: " . $bytesWritten . " bytes";
        $log[] = "📊 Total eventos: " . count($eventos) . " (" . $conHreflang . " con hreflang, " . $sinHreflang . " sin traducción)";

        // Actualizar lastmod en sitemap.xml (índice)
        $sitemapIndexPath = __DIR__ . '/sitemap.xml';
        if (file_exists($sitemapIndexPath)) {
            $sitemapContent = file_get_contents($sitemapIndexPath);

            if (strpos($sitemapContent, 'sitemap-eventos.xml') === false) {
                $newEntry = "  <sitemap>\n    <loc>{$baseUrl}/sitemap-eventos.xml</loc>\n    <lastmod>{$today}</lastmod>\n  </sitemap>\n</sitemapindex>";
                $sitemapContent = str_replace('</sitemapindex>', $newEntry, $sitemapContent);
                $log[] = "✅ Agregado sitemap-eventos.xml al índice principal";
            } else {
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
    if (strpos($line, '✅') !== false || strpos($line, '🎉') !== false) {
        echo '<span class="success">' . htmlspecialchars($line) . '</span><br>';
    } elseif (strpos($line, '❌') !== false) {
        echo '<span class="error">' . htmlspecialchars($line) . '</span><br>';
    } elseif (strpos($line, '🔍') !== false || strpos($line, '📊') !== false) {
        echo '<span class="info">' . htmlspecialchars($line) . '</span><br>';
    } else {
        echo htmlspecialchars($line) . '<br>';
    }
}

echo '</div>
        <h2>📋 Verificación:</h2>
        <ul>
            <li><a href="/sitemap-eventos.xml" target="_blank">Ver sitemap-eventos.xml (español con hreflang)</a></li>
            <li><a href="/sitemap-eventos-i18n.xml" target="_blank">Ver sitemap-eventos-i18n.xml (traducciones)</a></li>
            <li><a href="/sitemap.xml" target="_blank">Ver índice principal (sitemap.xml)</a></li>
        </ul>

        <h2>🎯 Estructura de sitemaps de eventos:</h2>
        <ul>
            <li><strong>sitemap-eventos.xml</strong> → URLs en español (/evento/slug) con hreflang si hay traducciones</li>
            <li><strong>sitemap-eventos-i18n.xml</strong> → URLs traducidas (/en/evento/slug, /fr/..., /de/..., /zh/...) con hreflang completo</li>
            <li>Cada URL aparece en UN SOLO sitemap → sin duplicados</li>
        </ul>

        <p class="success">✅ El archivo sitemap-eventos.xml está listo para Google Search Console.</p>
    </div>
</body>
</html>';
