<?php
/**
 * regenerar_sitemap_eventos_landing.php — rutasrurales.io
 * ─────────────────────────────────────────────────────────────────────────────
 * Genera el archivo estático sitemap-eventos-landing.xml con todas las landings
 * SEO long-tail de eventos culturales que tienen ≥1 resultado activo en BD.
 *
 * Uso:
 *   - Desde el navegador: https://rutasrurales.io/regenerar_sitemap_eventos_landing.php
 *   - Desde CLI:          php regenerar_sitemap_eventos_landing.php
 *   - Desde cron:         0 3 * * 1 php /ruta/regenerar_sitemap_eventos_landing.php
 *
 * Genera:   sitemap-eventos-landing.xml
 * Actualiza: sitemap.xml (índice maestro) → reemplaza .php → .xml
 * ─────────────────────────────────────────────────────────────────────────────
 */

$isCli   = (php_sapi_name() === 'cli');
$baseDir = __DIR__;
$baseUrl = 'https://rutasrurales.io';
$outputFile   = $baseDir . '/sitemap-eventos-landing.xml';
$sitemapIndex = $baseDir . '/sitemap.xml';

$log   = [];
$now   = date('Y-m-d H:i:s');
$today = date('Y-m-d');

$log[] = "[$now] Iniciando generación de sitemap-eventos-landing.xml";

// ── HTML header (solo navegador) ─────────────────────────────────────────────
if (!$isCli) {
    echo '<!DOCTYPE html><html lang="es"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerar sitemap-eventos-landing.xml</title>
    <style>
        body { font-family:monospace; background:#1a1a2e; color:#e0e0e0; padding:20px; }
        h1   { color:#00d4aa; }
        .log { background:#0d0d1a; border:1px solid #333; padding:15px; border-radius:6px; white-space:pre-wrap; }
        .ok  { color:#00d4aa; }
        .err { color:#ff6b6b; }
        .info{ color:#ffd93d; }
        .links a { color:#00d4aa; margin-right:15px; }
    </style></head><body>
    <h1>🗓️ Regenerar sitemap-eventos-landing.xml</h1>
    <div class="log">';
}

// ── Dependencias ──────────────────────────────────────────────────────────────
require_once $baseDir . '/api/config.php';
require_once $baseDir . '/eventos-landing/config/filters.php';

// ── Conexión a BD ─────────────────────────────────────────────────────────────
try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $log[] = "✅ Conexión a base de datos: OK";
} catch (Throwable $e) {
    $log[] = "❌ Error de conexión: " . $e->getMessage();
    outputEvLog($log, $isCli);
    exit(1);
}

// ── Idiomas soportados ────────────────────────────────────────────────────────
$idiomas = [
    ''    => 'es',
    'en/' => 'en',
    'fr/' => 'fr',
    'de/' => 'de',
    'zh/' => 'zh',
];

// ── Función de verificación ───────────────────────────────────────────────────
function tieneEventosDB(PDO $pdo, ?string $province_db, string $sql_cond = ''): bool
{
    $where  = [
        'e.is_active = 1',
        "e.moderation_status = 'approved'",
        'COALESCE(e.end_date, e.start_date) >= CURDATE()',
    ];
    $params = [];

    if (!empty($province_db)) {
        $where[]             = 'e.province = :province';
        $params[':province'] = $province_db;
    }
    if (!empty(trim($sql_cond))) {
        $where[] = '(' . $sql_cond . ')';
    }

    $sql = 'SELECT 1 FROM cultural_events e WHERE ' . implode(' AND ', $where) . ' LIMIT 1';
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return ($stmt->fetchColumn() !== false);
    } catch (PDOException $ex) {
        error_log('[regenerar_sitemap_eventos] SQL error: ' . $ex->getMessage());
        return false;
    }
}

// ── Helper: emite <url> para los 5 idiomas con hreflang completo ─────────────
function emitUrlSet(
    string $urlSlug,
    string $baseUrl,
    array  $idiomas,
    string $today,
    string $esPriority,
    string $otherPriority
): string {
    $xml        = '';
    $canonicUrl = $baseUrl . '/eventos/' . $urlSlug;
    $xml .= "\n\n  <!-- {$urlSlug} -->";

    foreach ($idiomas as $langPrefix => $langCode) {
        $url = $baseUrl . '/' . $langPrefix . 'eventos/' . $urlSlug;
        $xml .= "\n  <url>";
        $xml .= "\n    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>";
        $xml .= "\n    <lastmod>{$today}</lastmod>";
        $xml .= "\n    <changefreq>weekly</changefreq>";
        $xml .= "\n    <priority>" . ($langCode === 'es' ? $esPrice = $esPriority : $otherPriority) . "</priority>";
        foreach ($idiomas as $hlPrefix => $hlCode) {
            $hlUrl = $baseUrl . '/' . $hlPrefix . 'eventos/' . $urlSlug;
            $xml .= "\n    <xhtml:link rel=\"alternate\" hreflang=\"{$hlCode}\" href=\"" . htmlspecialchars($hlUrl, ENT_XML1) . "\"/>";
        }
        $xml .= "\n    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($canonicUrl, ENT_XML1) . "\"/>";
        $xml .= "\n  </url>";
    }
    return $xml;
}

// ── Construir XML ─────────────────────────────────────────────────────────────
$urlsIncluidas = 0;
$urlsOmitidas  = 0;

$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<!--' . "\n";
$xml .= '  sitemap-eventos-landing.xml — rutasrurales.io' . "\n";
$xml .= '  Landings long-tail: /eventos/{filtro}-{provincia}' . "\n";
$xml .= '  Solo combinaciones con ≥1 evento activo y aprobado.' . "\n";
$xml .= '  5 entradas <url> por landing (es/en/fr/de/zh) con hreflang completo.' . "\n";
$xml .= '  GENERADO AUTOMÁTICAMENTE — NO EDITAR MANUALMENTE' . "\n";
$xml .= '  Generado: ' . $now . "\n";
$xml .= '-->' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
$xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

// ── BLOQUE 1: Solo provincia (/eventos/{provincia}) — prioridad 1.0 ──────────
$log[] = "📋 Bloque 1: Verificando provincias solas...";
foreach (EVENTOS_PROVINCIAS as $prov_slug => $prov_data) {
    $provincia_db = $prov_data['db'];

    $sql = "SELECT 1 FROM cultural_events e
            WHERE e.is_active = 1
              AND e.moderation_status = 'approved'
              AND COALESCE(e.end_date, e.start_date) >= CURDATE()
              AND e.province = :province
            LIMIT 1";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':province' => $provincia_db]);
        $tiene = ($stmt->fetchColumn() !== false);
    } catch (PDOException $e) {
        $tiene = false;
    }

    if (!$tiene) {
        $urlsOmitidas++;
        continue;
    }

    $xml .= emitUrlSet($prov_slug, $baseUrl, $idiomas, $today, '1.0', '0.9');
    $urlsIncluidas += count($idiomas);
}
$log[] = "   → " . ($urlsIncluidas / count($idiomas)) . " provincias con eventos";

// ── BLOQUE 2: Filtro + provincia (/eventos/{filtro}-{provincia}) — pr. 0.8 ───
$log[] = "📋 Bloque 2: Verificando combinaciones filtro+provincia...";
$b2Antes = $urlsIncluidas;
foreach (EVENTOS_FILTROS as $filter_slug => $filter_data) {
    if (isset($filter_data['sitemap']) && $filter_data['sitemap'] === false) continue;

    foreach (EVENTOS_PROVINCIAS as $prov_slug => $prov_data) {
        if (!tieneEventosDB($pdo, $prov_data['db'], $filter_data['sql'])) {
            $urlsOmitidas++;
            continue;
        }

        $urlSlug = $filter_slug . '-' . $prov_slug;
        $xml    .= emitUrlSet($urlSlug, $baseUrl, $idiomas, $today, '0.8', '0.7');
        $urlsIncluidas += count($idiomas);
    }
}
$log[] = "   → " . (($urlsIncluidas - $b2Antes) / count($idiomas)) . " combinaciones filtro+provincia";

// ── BLOQUE 3: Categoría + temporada + provincia — prioridad 0.7 ──────────────
$log[] = "📋 Bloque 3: Verificando categoría+temporada+provincia...";
$b3Antes       = $urlsIncluidas;
$categoria_keys = array_keys(array_filter(EVENTOS_FILTROS, fn($f) => $f['group'] === 'categoria'));
$temporada_keys = array_keys(array_filter(EVENTOS_FILTROS,
    fn($f) => $f['group'] === 'temporada' && !(isset($f['sitemap']) && $f['sitemap'] === false)
));

foreach ($categoria_keys as $ckey) {
    foreach ($temporada_keys as $tkey) {
        $sql_combinado = EVENTOS_FILTROS[$ckey]['sql'] . ' AND (' . EVENTOS_FILTROS[$tkey]['sql'] . ')';

        foreach (EVENTOS_PROVINCIAS as $prov_slug => $prov_data) {
            if (!tieneEventosDB($pdo, $prov_data['db'], $sql_combinado)) {
                $urlsOmitidas++;
                continue;
            }

            $urlSlug = $ckey . '-' . $tkey . '-' . $prov_slug;
            $xml    .= emitUrlSet($urlSlug, $baseUrl, $idiomas, $today, '0.7', '0.6');
            $urlsIncluidas += count($idiomas);
        }
    }
}
$log[] = "   → " . (($urlsIncluidas - $b3Antes) / count($idiomas)) . " combinaciones cat+temp+prov";

$xml .= "\n</urlset>\n";

$log[] = "📊 Total URLs incluidas: {$urlsIncluidas} (" . count($idiomas) . " idiomas × " . ($urlsIncluidas / count($idiomas)) . " landings)";
$log[] = "⏭️  Combinaciones omitidas (sin contenido): {$urlsOmitidas}";

// ── Guardar sitemap-eventos-landing.xml ───────────────────────────────────────
$bytesWritten = file_put_contents($outputFile, $xml);
if ($bytesWritten !== false) {
    $log[] = "✅ Archivo sitemap-eventos-landing.xml generado: " . number_format($bytesWritten) . " bytes";
} else {
    $log[] = "❌ Error al escribir sitemap-eventos-landing.xml (verifica permisos de escritura)";
}

// ── Actualizar sitemap.xml (índice maestro) ────────────────────────────────────
if (file_exists($sitemapIndex)) {
    $indexContent = file_get_contents($sitemapIndex);

    // Reemplazar referencia al .php por .xml (si existe la entrada .php)
    $indexContent = str_replace(
        $baseUrl . '/sitemap-eventos-landing.php',
        $baseUrl . '/sitemap-eventos-landing.xml',
        $indexContent
    );

    if (strpos($indexContent, 'sitemap-eventos-landing.xml') === false) {
        // No existe ninguna entrada: añadir antes del cierre
        $newEntry  = "\n  <sitemap>\n";
        $newEntry .= "    <loc>{$baseUrl}/sitemap-eventos-landing.xml</loc>\n";
        $newEntry .= "    <lastmod>{$today}</lastmod>\n";
        $newEntry .= "  </sitemap>\n";
        $indexContent = str_replace('</sitemapindex>', $newEntry . '</sitemapindex>', $indexContent);
        $log[] = "✅ Entrada sitemap-eventos-landing.xml añadida al índice maestro";
    } else {
        // Ya existe .xml: actualizar solo la fecha
        $indexContent = preg_replace(
            '/(sitemap-eventos-landing\.xml<\/loc>\s*<lastmod>)\d{4}-\d{2}-\d{2}(<\/lastmod>)/',
            '${1}' . $today . '${2}',
            $indexContent
        );
        $log[] = "✅ Lastmod de sitemap-eventos-landing.xml actualizado en el índice maestro";
    }

    if (file_put_contents($sitemapIndex, $indexContent) !== false) {
        $log[] = "✅ sitemap.xml guardado correctamente";
    } else {
        $log[] = "❌ Error al escribir sitemap.xml (verifica permisos)";
    }
} else {
    $log[] = "⚠️  No se encontró sitemap.xml en la raíz";
}

// ── Ping a Google y Bing ─────────────────────────────────────────────────────
$sitemapEnc = urlencode("{$baseUrl}/sitemap.xml");
@file_get_contents("https://www.google.com/ping?sitemap={$sitemapEnc}");
@file_get_contents("https://www.bing.com/ping?sitemap={$sitemapEnc}");
$log[] = "📢 Ping enviado a Google y Bing Search";

// ── Output final ─────────────────────────────────────────────────────────────
outputEvLog($log, $isCli);

if (!$isCli) {
    echo '</div>';
    echo '<div class="links" style="margin-top:20px;font-family:monospace">';
    echo '<h2 style="color:#00d4aa">🔗 Verificar resultados</h2>';
    echo '<p><a href="/sitemap-eventos-landing.xml" target="_blank">📄 Ver sitemap-eventos-landing.xml</a></p>';
    echo '<p><a href="/sitemap.xml" target="_blank">📋 Ver sitemap.xml (índice maestro)</a></p>';
    echo '<p><a href="regenerar_sitemap_eventos_landing.php">🔄 Regenerar de nuevo</a></p>';
    echo '<p style="margin-top:12px"><a href="regenerar_sitemap_landings.php" style="color:#ffd93d">🏨 Regenerar también alojamientos-landing</a></p>';
    echo '</div></body></html>';
}

// ── Función helper ────────────────────────────────────────────────────────────
function outputEvLog(array $log, bool $isCli): void
{
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
