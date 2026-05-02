<?php
/**
 * Regenerador de sitemap-eventos-i18n.xml
 *
 * Genera el XML estático con hreflang COMPLETO para Google Search Console.
 * Cada URL contiene TODOS los idiomas alternativos (es, en, fr, de, zh + x-default).
 *
 * Google recomienda que cada <url> liste TODAS las variantes de idioma,
 * no solo las dos implicadas. Este archivo sigue ese estándar.
 *
 * Se puede ejecutar:
 *   1. Desde cron (ej: cada hora o diariamente)
 *   2. Automáticamente al guardar un evento desde el admin
 *   3. Manualmente desde el navegador: /admin_tablas/cron/regenerar_sitemap_i18n.php
 *
 * El archivo generado: /sitemap-eventos-i18n.xml
 */

// Permitir ejecución desde CLI o desde include
$esCLI     = (php_sapi_name() === 'cli');
$esInclude = defined('REGENERAR_SITEMAP_DESDE_ADMIN');

// Conexión a BD
$host   = 'localhost';
$dbname = 'u412199647_Rutas';
$user   = 'u412199647_olgamarin';
$pass   = 'Rutas5Rurales7$';

$baseUrl = 'https://rutasrurales.io';
$today   = date('Y-m-d');
$now     = date('Y-m-d H:i:s');

// Guardar referencia a PDO existente para no perderla
$_pdo_backup = isset($pdo) ? $pdo : null;

// Ruta del archivo XML a generar (2 niveles arriba: cron -> admin_tablas -> raíz)
$baseDir = dirname(__DIR__, 2);
$xmlPath = $baseDir . '/sitemap-eventos-i18n.xml';

$log = [];
$log[] = "[$now] Iniciando regeneración de sitemap-eventos-i18n.xml";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ── CONSULTA: eventos vigentes/futuros con al menos una traducción ──────────
    // Agrupa por evento para construir el mapa completo de alternativas.
    $stmt = $pdo->query("
        SELECT
            e.id,
            e.slug          AS slug_es,
            e.start_date,
            e.end_date,
            COALESCE(e.updated_at, e.created_at, NOW()) AS fecha_mod,
            t.language_code AS lang,
            t.slug          AS slug_trad
        FROM cultural_events e
        INNER JOIN cultural_events_trads t ON t.event_id = e.id
        WHERE e.is_active = 1
          AND e.slug IS NOT NULL
          AND e.slug != ''
          AND t.slug IS NOT NULL
          AND t.slug != ''
          AND t.language_code IN ('en', 'fr', 'de', 'zh')
          AND (
              (e.end_date IS NULL     AND e.start_date >= CURDATE()) OR
              (e.end_date IS NOT NULL AND e.end_date   >= CURDATE())
          )
        ORDER BY e.id ASC, t.language_code ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $log[] = "Filas de traducciones encontradas: " . count($rows);

    // ── AGRUPAR POR EVENTO ───────────────────────────────────────────────────────
    // $eventos[id] = ['slug_es'=>..., 'fecha_mod'=>..., 'langs'=>[lang=>slug, ...]]
    $eventos = [];
    foreach ($rows as $row) {
        $id = $row['id'];
        if (!isset($eventos[$id])) {
            $eventos[$id] = [
                'slug_es'   => $row['slug_es'],
                'fecha_mod' => $row['fecha_mod'],
                'langs'     => [],
            ];
        }
        $eventos[$id]['langs'][$row['lang']] = $row['slug_trad'];
    }

    $totalEventos = count($eventos);
    $totalUrls    = 0;
    $log[] = "Eventos con traducciones: $totalEventos";

    // ── CONSTRUCCIÓN DEL XML ─────────────────────────────────────────────────────
    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<!--' . "\n";
    $xml .= '  SITEMAP i18n: Eventos Culturales — rutasrurales.io' . "\n";
    $xml .= '  Incluye URLs en español + todas las versiones traducidas.' . "\n";
    $xml .= '  Cada <url> contiene hreflang COMPLETO (todos los idiomas) según' . "\n";
    $xml .= '  las directrices de Google Search Console.' . "\n";
    $xml .= '' . "\n";
    $xml .= '  Idiomas: es (español), en, fr, de, zh (zh-Hans)' . "\n";
    $xml .= '  URL española:   /evento/{slug}' . "\n";
    $xml .= '  URL traducida:  /{lang}/evento/{slug-traducido}' . "\n";
    $xml .= '' . "\n";
    $xml .= '  GENERADO AUTOMÁTICAMENTE — NO EDITAR MANUALMENTE' . "\n";
    $xml .= '  Última regeneración: ' . $now . "\n";
    $xml .= '  Eventos procesados: ' . $totalEventos . "\n";
    $xml .= '-->' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml"' . "\n";
    $xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
    $xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
    $xml .= '          http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

    foreach ($eventos as $id => $ev) {
        $slugEs  = htmlspecialchars($ev['slug_es'], ENT_XML1, 'UTF-8');
        $lastmod = !empty($ev['fecha_mod'])
            ? date('Y-m-d', strtotime($ev['fecha_mod']))
            : $today;

        // Mapa completo de alternativas: es + todas las traducciones disponibles
        $alternativas = ['es' => "{$baseUrl}/evento/{$slugEs}"];
        foreach ($ev['langs'] as $lang => $slugTrad) {
            $slugTradEsc = htmlspecialchars($slugTrad, ENT_XML1, 'UTF-8');
            $alternativas[$lang] = "{$baseUrl}/{$lang}/evento/{$slugTradEsc}";
        }

        // ── Entrada para la URL en español ──────────────────────────────────────
        $xml .= "\n  <url>\n";
        $xml .= "    <loc>{$baseUrl}/evento/{$slugEs}</loc>\n";
        $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
        $xml .= "    <changefreq>weekly</changefreq>\n";
        $xml .= "    <priority>0.8</priority>\n";
        foreach ($alternativas as $hLang => $hUrl) {
            $hLangAttr = ($hLang === 'zh') ? 'zh-Hans' : $hLang;
            $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$hLangAttr}\" href=\"{$hUrl}\"/>\n";
        }
        $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$baseUrl}/evento/{$slugEs}\"/>\n";
        $xml .= "  </url>\n";
        $totalUrls++;

        // ── Entrada para cada URL traducida ─────────────────────────────────────
        foreach ($ev['langs'] as $lang => $slugTrad) {
            $slugTradEsc = htmlspecialchars($slugTrad, ENT_XML1, 'UTF-8');
            $hLangAttr   = ($lang === 'zh') ? 'zh-Hans' : $lang;

            $xml .= "\n  <url>\n";
            $xml .= "    <loc>{$baseUrl}/{$lang}/evento/{$slugTradEsc}</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            foreach ($alternativas as $hLang2 => $hUrl2) {
                $hLangAttr2 = ($hLang2 === 'zh') ? 'zh-Hans' : $hLang2;
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$hLangAttr2}\" href=\"{$hUrl2}\"/>\n";
            }
            $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$baseUrl}/evento/{$slugEs}\"/>\n";
            $xml .= "  </url>\n";
            $totalUrls++;
        }
    }

    $xml .= "\n</urlset>\n";

    // ── ESCRITURA DEL ARCHIVO ────────────────────────────────────────────────────
    $bytesWritten = file_put_contents($xmlPath, $xml);

    if ($bytesWritten === false) {
        $log[] = "ERROR: No se pudo escribir en {$xmlPath}";
        throw new Exception("No se pudo escribir el XML en {$xmlPath}");
    }

    $log[] = "OK: Archivo generado ({$bytesWritten} bytes) — {$totalEventos} eventos, {$totalUrls} URLs";

    // ── ACTUALIZAR sitemap.xml (índice maestro) ──────────────────────────────────
    $sitemapIndexPath = $baseDir . '/sitemap.xml';
    if (file_exists($sitemapIndexPath)) {
        $sitemapContent = file_get_contents($sitemapIndexPath);

        if (strpos($sitemapContent, 'sitemap-eventos-i18n.xml') === false) {
            // No existe la entrada: agregarla
            $newEntry  = "  <sitemap>\n";
            $newEntry .= "    <loc>{$baseUrl}/sitemap-eventos-i18n.xml</loc>\n";
            $newEntry .= "    <lastmod>{$today}</lastmod>\n";
            $newEntry .= "  </sitemap>\n";
            $sitemapContent = str_replace('</sitemapindex>', $newEntry . '</sitemapindex>', $sitemapContent);
            $log[] = "OK: Agregado sitemap-eventos-i18n.xml al índice principal";
        } else {
            // Ya existe: actualizar solo la fecha
            $sitemapContent = preg_replace(
                '/(sitemap-eventos-i18n\.xml<\/loc>\s*<lastmod>)\d{4}-\d{2}-\d{2}(<\/lastmod>)/',
                '${1}' . $today . '${2}',
                $sitemapContent
            );
            $log[] = "OK: Actualizado lastmod de sitemap-eventos-i18n.xml en el índice";
        }

        file_put_contents($sitemapIndexPath, $sitemapContent);
    }

    // ── PING A GOOGLE ────────────────────────────────────────────────────────────
    @file_get_contents("https://www.google.com/ping?sitemap=" . urlencode("{$baseUrl}/sitemap-eventos-i18n.xml"));
    $log[] = "OK: Ping enviado a Google";

} catch (PDOException $e) {
    $log[] = "ERROR BD: " . $e->getMessage();
} catch (Exception $e) {
    $log[] = "ERROR: " . $e->getMessage();
}

// Restaurar la conexión PDO original si existía
if ($_pdo_backup !== null) {
    $pdo = $_pdo_backup;
}
unset($_pdo_backup);

// Guardar log
$logPath  = __DIR__ . '/cron.log';
$logEntry = implode("\n", $log) . "\n---\n";
file_put_contents($logPath, $logEntry, FILE_APPEND);

// Salida según contexto
if ($esCLI) {
    echo implode("\n", $log) . "\n";
} elseif (!$esInclude) {
    // Acceso directo por navegador
    header('Content-Type: text/plain; charset=UTF-8');
    echo implode("\n", $log) . "\n";
}
// Si es include desde el admin (REGENERAR_SITEMAP_DESDE_ADMIN), no imprime nada
