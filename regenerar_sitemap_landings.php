<?php
/**
 * regenerar_sitemap_landings.php — rutasrurales.io
 * ─────────────────────────────────────────────────────────────────────────────
 * Genera el archivo estático sitemap-landings.xml con todas las landings
 * SEO long-tail de alojamientos que tienen al menos 1 resultado activo en BD.
 *
 * Uso:
 *   - Desde el navegador: https://rutasrurales.io/regenerar_sitemap_landings.php
 *   - Desde CLI:          php regenerar_sitemap_landings.php
 *   - Desde cron:         0 3 * * 1 php /ruta/regenerar_sitemap_landings.php
 *
 * Genera: sitemap-landings.xml
 * Actualiza: sitemap.xml (índice maestro) con la entrada + lastmod
 * ─────────────────────────────────────────────────────────────────────────────
 */

$isCli = (php_sapi_name() === 'cli');
$baseDir = __DIR__;
$baseUrl = 'https://rutasrurales.io';
$outputFile = $baseDir . '/sitemap-landings.xml';
$sitemapIndex = $baseDir . '/sitemap.xml';

$log = [];
$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');

$log[] = "[$now] Iniciando generación de sitemap-landings.xml";

// ── HTML header (solo si se accede desde navegador) ──────────────────────────
if (!$isCli) {
    echo '<!DOCTYPE html><html lang="es"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerar sitemap-landings.xml</title>
    <style>
        body { font-family: monospace; background:#1a1a2e; color:#e0e0e0; padding:20px; }
        h1 { color:#00d4aa; }
        .log { background:#0d0d1a; border:1px solid #333; padding:15px; border-radius:6px; white-space:pre-wrap; }
        .ok  { color:#00d4aa; }
        .err { color:#ff6b6b; }
        .info{ color:#ffd93d; }
        .links { margin-top:20px; }
        .links a { color:#00d4aa; margin-right:15px; }
    </style></head><body>
    <h1>🗺️ Regenerar sitemap-landings.xml</h1>
    <div class="log">';
}

// ── Dependencias ──────────────────────────────────────────────────────────────
require_once $baseDir . '/api/config.php';
require_once $baseDir . '/alojamientos-landing/config/filters.php';

// ── Conexión a BD ─────────────────────────────────────────────────────────────
try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $log[] = "✅ Conexión a base de datos: OK";
} catch (Throwable $e) {
    $log[] = "❌ Error de conexión: " . $e->getMessage();
    outputLog($log, $isCli);
    exit(1);
}

// ── Idiomas soportados ────────────────────────────────────────────────────────
$idiomas = [
    ''     => 'es',
    'en/'  => 'en',
    'fr/'  => 'fr',
    'de/'  => 'de',
    'zh/'  => 'zh',
];

// ── Construir XML ─────────────────────────────────────────────────────────────
$urlsIncluidas = 0;
$urlsOmitidas  = 0;
$combinaciones = 0;

$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<!--' . "\n";
$xml .= '  sitemap-landings.xml — rutasrurales.io' . "\n";
$xml .= '  Landings SEO long-tail: /alojamientos/{filtro}-{provincia}' . "\n";
$xml .= '  Solo incluye combinaciones con al menos 1 alojamiento activo.' . "\n";
$xml .= '  Incluye hreflang para 5 idiomas (es/en/fr/de/zh).' . "\n";
$xml .= '  GENERADO AUTOMÁTICAMENTE — NO EDITAR MANUALMENTE' . "\n";
$xml .= '  Generado: ' . $now . "\n";
$xml .= '-->' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
$xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

foreach (LANDING_FILTROS as $filter_slug => $filter_data) {

    // Saltar filtros excluidos del sitemap (ej: alias duplicados con sitemap => false)
    if (isset($filter_data['sitemap']) && $filter_data['sitemap'] === false) {
        continue;
    }

    $sql_filter_condition = $filter_data['sql'];

    foreach (LANDING_PROVINCIAS as $prov_slug => $prov_data) {

        $provincia_db = $prov_data['db'];
        $combinaciones++;

        // ── Validar: ¿hay al menos 1 alojamiento activo? ─────────────────────
        $query = "
            SELECT 1
            FROM accommodations a
            LEFT JOIN categories_accommodations c ON a.category_id = c.id
            WHERE a.is_active = 1
              AND a.province = :province
              AND ({$sql_filter_condition})
            LIMIT 1
        ";

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute([':province' => $provincia_db]);
            $tieneContenido = ($stmt->fetch(PDO::FETCH_COLUMN) !== false);
        } catch (PDOException $e) {
            error_log("[regenerar_sitemap_landings] Error filtro={$filter_slug} prov={$prov_slug}: " . $e->getMessage());
            $tieneContenido = false;
        }

        if (!$tieneContenido) {
            $urlsOmitidas++;
            continue;
        }

        // ── EMITIR URLs PARA LOS 5 IDIOMAS ───────────────────────────────────
        // Cada versión lingüística tiene su propia entrada <url> con hreflang completo.
        // Google necesita <loc> explícitos para indexar cada idioma.
        $urlSlug    = $filter_slug . '-' . $prov_slug;
        $canonicUrl = $baseUrl . '/alojamientos/' . $urlSlug;

        $xml .= "\n\n  <!-- {$urlSlug} -->";

        foreach ($idiomas as $langPrefix => $langCode) {
            $url = $baseUrl . '/' . $langPrefix . 'alojamientos/' . $urlSlug;

            $xml .= "\n  <url>";
            $xml .= "\n    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>";
            $xml .= "\n    <lastmod>" . $today . "</lastmod>";
            $xml .= "\n    <changefreq>weekly</changefreq>";
            $xml .= "\n    <priority>" . ($langCode === 'es' ? '0.8' : '0.7') . "</priority>";

            // hreflang completo: todas las variantes lingüísticas
            foreach ($idiomas as $hlPrefix => $hlCode) {
                $hlUrl = $baseUrl . '/' . $hlPrefix . 'alojamientos/' . $urlSlug;
                $xml .= "\n    <xhtml:link rel=\"alternate\" hreflang=\"{$hlCode}\" href=\"" . htmlspecialchars($hlUrl, ENT_XML1) . "\"/>";
            }
            // x-default apunta a la versión española
            $xml .= "\n    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($canonicUrl, ENT_XML1) . "\"/>";

            $xml .= "\n  </url>";
        }

        $urlsIncluidas += count($idiomas);
    }
}

$xml .= "\n</urlset>\n";

$log[] = "📊 Combinaciones evaluadas: {$combinaciones}";
$log[] = "✅ URLs incluidas: {$urlsIncluidas} entradas ({$urlsOmitidas} combinaciones sin contenido, " . count($idiomas) . " idiomas por landing)";
$log[] = "⏭️  Combinaciones omitidas (sin contenido): {$urlsOmitidas}";

// ── Guardar sitemap-landings.xml ──────────────────────────────────────────────
$bytesWritten = file_put_contents($outputFile, $xml);

if ($bytesWritten !== false) {
    $log[] = "✅ Archivo sitemap-landings.xml generado: " . number_format($bytesWritten) . " bytes";
} else {
    $log[] = "❌ Error al escribir sitemap-landings.xml (verifica permisos)";
}

// ── Actualizar sitemap.xml (índice maestro) ───────────────────────────────────
if (file_exists($sitemapIndex)) {
    $indexContent = file_get_contents($sitemapIndex);

    if (strpos($indexContent, 'sitemap-landings.xml') === false) {
        // No existe la entrada: agregarla antes del cierre
        $newEntry  = "  <sitemap>\n";
        $newEntry .= "    <loc>{$baseUrl}/sitemap-landings.xml</loc>\n";
        $newEntry .= "    <lastmod>{$today}</lastmod>\n";
        $newEntry .= "  </sitemap>\n";
        $indexContent = str_replace('</sitemapindex>', $newEntry . '</sitemapindex>', $indexContent);
        $log[] = "✅ Entrada sitemap-landings.xml añadida al índice maestro (sitemap.xml)";
    } else {
        // Ya existe: actualizar solo la fecha
        $indexContent = preg_replace(
            '/(sitemap-landings\.xml<\/loc>\s*<lastmod>)\d{4}-\d{2}-\d{2}(<\/lastmod>)/',
            '${1}' . $today . '${2}',
            $indexContent
        );
        $log[] = "✅ Lastmod de sitemap-landings.xml actualizado en el índice maestro";
    }

    if (file_put_contents($sitemapIndex, $indexContent) !== false) {
        $log[] = "✅ sitemap.xml guardado correctamente";
    } else {
        $log[] = "❌ Error al escribir sitemap.xml (verifica permisos)";
    }
} else {
    $log[] = "⚠️  No se encontró sitemap.xml. Añade manualmente: <loc>{$baseUrl}/sitemap-landings.xml</loc>";
}

// ── Ping a Google ─────────────────────────────────────────────────────────────
$pingUrl = "https://www.google.com/ping?sitemap=" . urlencode("{$baseUrl}/sitemap.xml");
@file_get_contents($pingUrl);
$log[] = "📢 Ping enviado a Google Search Console";

// ── Output final ──────────────────────────────────────────────────────────────
outputLog($log, $isCli);

if (!$isCli) {
    echo '</div>';
    echo '<div class="links">';
    echo '<h2 style="color:#00d4aa">🔗 Verificar resultados</h2>';
    echo '<p><a href="/sitemap-landings.xml" target="_blank">📄 Ver sitemap-landings.xml</a></p>';
    echo '<p><a href="/sitemap.xml" target="_blank">📋 Ver sitemap.xml (índice maestro)</a></p>';
    echo '<p><a href="regenerar_sitemap_landings.php">🔄 Regenerar de nuevo</a></p>';
    echo '<h2 style="color:#ffd93d; margin-top:20px">📋 Cómo registrar en Google Search Console</h2>';
    echo '<ol style="font-family:sans-serif; line-height:1.8">';
    echo '<li>Ve a <strong>Google Search Console → Sitemaps</strong></li>';
    echo '<li>Añade: <code style="background:#0d0d1a; padding:3px 8px; border-radius:3px;">sitemap.xml</code> (el índice maestro incluye todo)</li>';
    echo '<li>O añade directamente: <code style="background:#0d0d1a; padding:3px 8px; border-radius:3px;">sitemap-landings.xml</code></li>';
    echo '</ol>';
    echo '</div></body></html>';
}

// ── Función helper ────────────────────────────────────────────────────────────
function outputLog(array $log, bool $isCli): void {
    foreach ($log as $line) {
        if ($isCli) {
            echo $line . "\n";
        } else {
            $class = 'info';
            if (str_starts_with($line, '✅')) $class = 'ok';
            if (str_starts_with($line, '❌')) $class = 'err';
            echo '<span class="' . $class . '">' . htmlspecialchars($line) . '</span>' . "\n";
        }
    }
}
