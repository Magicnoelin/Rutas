<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  GENERADOR DE SITEMAP — Landings Long-Tail de Eventos Culturales
 *  rutasrurales.io/eventos-landing/generar-sitemap.php
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Genera SOLO las URLs con contenido real (≥1 evento activo y aprobado).
 *  Nunca incluye páginas vacías → evita soft-404 en Google Search Console.
 *
 *  USO:
 *    php eventos-landing/generar-sitemap.php
 *    → Escribe sitemap-eventos-landing.xml en la raíz del proyecto
 *
 *    Con parámetro GET (desde el servidor):
 *    https://rutasrurales.io/eventos-landing/generar-sitemap.php?token=TU_TOKEN
 *    → Devuelve el XML directamente (útil para auto-regeneración con cron)
 *
 *  ESTRATEGIA:
 *    1. Para cada PROVINCIA → verificar si hay eventos activos
 *    2. Para cada FILTRO INDIVIDUAL → verificar por provincia
 *    3. Para combinaciones (filtro+provincia) más frecuentes → verificar
 *    4. URLs de solo-filtro (sin provincia) → verificar globalmente
 *    Excluye: filtros con 'sitemap'=>false (ej: este-mes)
 *
 *  PRIORIDADES SEO:
 *    1.0 — Solo provincia (todas las categorías)
 *    0.8 — Filtro + provincia (long-tail)
 *    0.6 — Solo filtro (sin provincia)
 *
 *  FRECUENCIAS:
 *    Eventos → weekly (agenda cambia con frecuencia)
 * ════════════════════════════════════════════════════════════════════════════
 */

// ── Seguridad: token para llamadas HTTP ───────────────────────────────────────
$IS_CLI = (php_sapi_name() === 'cli');

if (!$IS_CLI) {
    $token   = $_GET['token'] ?? '';
    $envToken = getenv('SITEMAP_TOKEN') ?: 'rutasrurales_sitemap_2026';
    if (!hash_equals($envToken, $token)) {
        http_response_code(403);
        die('Forbidden');
    }
}

// ── Dependencias ──────────────────────────────────────────────────────────────
$ROOT = dirname(__DIR__);
require_once $ROOT . '/api/config.php';
require_once __DIR__ . '/config/filters.php';

// ── Configuración ─────────────────────────────────────────────────────────────
const SITEMAP_BASE_URL    = 'https://rutasrurales.io';
const SITEMAP_OUTPUT_FILE = '/sitemap-eventos-landing.xml';
const SITEMAP_LANGS       = ['es', 'en', 'fr', 'de', 'zh'];

// Idiomas para hreflang (prefijo en URL)
function buildEventoUrl(string $slug, string $lang): string
{
    $base = SITEMAP_BASE_URL;
    return $lang === 'es'
        ? "$base/eventos/$slug"
        : "$base/$lang/eventos/$slug";
}

// ── Conexión BD ───────────────────────────────────────────────────────────────
try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    die('[sitemap-eventos] BD no disponible: ' . $e->getMessage() . "\n");
}

// ── Función de verificación: ¿tiene esta combinación eventos reales? ──────────
function hasEventos(PDO $pdo, ?string $province_db, string $sql_condition = ''): bool
{
    $where  = ['e.is_active = 1', "e.moderation_status = 'approved'"];
    $params = [];

    // Solo eventos futuros o en curso
    $where[] = 'COALESCE(e.end_date, e.start_date) >= CURDATE()';

    if (!empty($province_db)) {
        $where[]           = 'e.province = :province';
        $params[':province'] = $province_db;
    }

    if (!empty(trim($sql_condition))) {
        $where[] = $sql_condition;
    }

    $sql = 'SELECT 1 FROM cultural_events e WHERE '
         . implode(' AND ', $where)
         . ' LIMIT 1';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('[sitemap-eventos] Query error: ' . $e->getMessage());
        return false;
    }
}

// ── Recopilación de URLs válidas ───────────────────────────────────────────────
$urls     = [];      // [['slug'=>..., 'priority'=>..., 'langs'=>[...]], ...]
$checked  = 0;
$included = 0;

$now = gmdate('Y-m-d');

/**
 * Registra una URL solo si hay eventos reales.
 * @param string      $slug      Ej: "musica-soria"
 * @param float       $priority  1.0 / 0.8 / 0.6
 * @param string|null $province_db
 * @param string      $sql_cond  Condición SQL del filtro (o vacío)
 */
function tryAddUrl(
    PDO    $pdo,
    array  &$urls,
    int    &$checked,
    int    &$included,
    string $slug,
    float  $priority,
    ?string $province_db,
    string $sql_cond = ''
): void {
    $checked++;
    if (hasEventos($pdo, $province_db, $sql_cond)) {
        $included++;
        $urls[] = [
            'slug'     => $slug,
            'priority' => $priority,
        ];
    }
}

// ── 1. URLs solo-provincia (/eventos/{provincia}) ─────────────────────────────
// P.ej: /eventos/soria → todos los eventos de Soria (sin filtro de categoría)
logMsg("Verificando provincias solas...");
foreach (EVENTOS_PROVINCIAS as $pkey => $pdata) {
    tryAddUrl($pdo, $urls, $checked, $included, $pkey, 1.0, $pdata['db']);
}

// ── 2. URLs filtro+provincia (/eventos/{filtro}-{provincia}) ─────────────────
// La clave long-tail: musica-soria, gratuitos-zamora, etc.
logMsg("Verificando combinaciones filtro+provincia...");
foreach (EVENTOS_FILTROS as $fkey => $fdata) {
    // Excluir filtros marcados como no-sitemap (ej: este-mes)
    if (!empty($fdata['sitemap']) && $fdata['sitemap'] === false) continue;

    foreach (EVENTOS_PROVINCIAS as $pkey => $pdata) {
        $slug     = $fkey . '-' . $pkey;
        $sql_cond = $fdata['sql'];
        tryAddUrl($pdo, $urls, $checked, $included, $slug, 0.8, $pdata['db'], $sql_cond);
    }
}

// ── 3. URLs solo-filtro (/eventos/{filtro}) — sin provincia ──────────────────
// P.ej: /eventos/gratuitos → todos los eventos gratuitos de España
logMsg("Verificando filtros globales (sin provincia)...");
foreach (EVENTOS_FILTROS as $fkey => $fdata) {
    if (!empty($fdata['sitemap']) && $fdata['sitemap'] === false) continue;
    $sql_cond = $fdata['sql'];
    tryAddUrl($pdo, $urls, $checked, $included, $fkey, 0.6, null, $sql_cond);
}

// ── 4. Combinaciones dobles: temporada+provincia ──────────────────────────────
// P.ej: /eventos/musica-verano-soria (filtro-categoría + filtro-temporada + provincia)
logMsg("Verificando combinaciones categoría+temporada+provincia...");
$categoria_keys = array_keys(array_filter(EVENTOS_FILTROS, fn($f) => $f['group'] === 'categoria'));
$temporada_keys = array_keys(array_filter(EVENTOS_FILTROS, fn($f) => $f['group'] === 'temporada' && (empty($f['sitemap']) || $f['sitemap'] !== false)));

foreach ($categoria_keys as $ckey) {
    foreach ($temporada_keys as $tkey) {
        foreach (EVENTOS_PROVINCIAS as $pkey => $pdata) {
            $slug     = $ckey . '-' . $tkey . '-' . $pkey;
            $sql_cond = EVENTOS_FILTROS[$ckey]['sql'] . ' AND ' . EVENTOS_FILTROS[$tkey]['sql'];
            tryAddUrl($pdo, $urls, $checked, $included, $slug, 0.7, $pdata['db'], $sql_cond);
        }
    }
}

// ── Generación del XML ────────────────────────────────────────────────────────
logMsg("Generando XML con $included URLs ($checked combinaciones verificadas)...");

$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<!-- Generado: ' . gmdate('Y-m-d H:i:s') . ' UTC -->' . "\n";
$xml .= '<!-- URLs incluidas: ' . $included . ' / ' . $checked . ' combinaciones verificadas -->' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
$xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

foreach ($urls as $entry) {
    $slug     = $entry['slug'];
    $priority = number_format($entry['priority'], 1);

    // URL canónica en español
    $canonical = buildEventoUrl($slug, 'es');

    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($canonical) . "</loc>\n";
    $xml .= "    <lastmod>$now</lastmod>\n";
    $xml .= "    <changefreq>weekly</changefreq>\n";
    $xml .= "    <priority>$priority</priority>\n";

    // Alternates hreflang
    foreach (SITEMAP_LANGS as $lang) {
        $altUrl = buildEventoUrl($slug, $lang);
        $langCode = $lang === 'zh' ? 'zh-CN' : $lang;
        $xml .= '    <xhtml:link rel="alternate"'
              . ' hreflang="' . $langCode . '"'
              . ' href="' . htmlspecialchars($altUrl) . '"/>' . "\n";
    }
    // x-default
    $xml .= '    <xhtml:link rel="alternate"'
          . ' hreflang="x-default"'
          . ' href="' . htmlspecialchars(buildEventoUrl($slug, 'es')) . '"/>' . "\n";

    $xml .= "  </url>\n";
}

$xml .= "</urlset>\n";

// ── Escritura o salida ────────────────────────────────────────────────────────
if ($IS_CLI) {
    // Escritura en fichero desde CLI
    $outputPath = $ROOT . SITEMAP_OUTPUT_FILE;
    file_put_contents($outputPath, $xml);
    logMsg("✅ Sitemap escrito en: $outputPath");
    logMsg("   Total URLs: $included");
    logMsg("   Combinaciones verificadas: $checked");
} else {
    // Salida HTTP directa
    header('Content-Type: application/xml; charset=UTF-8');
    header('Cache-Control: no-store');
    echo $xml;
}

// ── Helper de log ─────────────────────────────────────────────────────────────
function logMsg(string $msg): void
{
    if (php_sapi_name() === 'cli') {
        echo $msg . "\n";
    } else {
        error_log('[sitemap-eventos] ' . $msg);
    }
}
