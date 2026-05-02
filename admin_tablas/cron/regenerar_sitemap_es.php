<?php
/**
 * Regenera sitemap-eventos.xml (URLs en español con hreflang completo)
 *
 * Puede ejecutarse:
 *   1. Desde cron
 *   2. Include desde el admin (define REGENERAR_SITEMAP_DESDE_ADMIN antes)
 *   3. Directamente desde el navegador
 */

$esCLI     = (php_sapi_name() === 'cli');
$esInclude = defined('REGENERAR_SITEMAP_DESDE_ADMIN');

$host    = 'localhost';
$dbname  = 'u412199647_Rutas';
$user    = 'u412199647_olgamarin';
$pass    = 'Rutas5Rurales7$';
$baseUrl = 'https://rutasrurales.io';
$today   = date('Y-m-d');
$now     = date('Y-m-d H:i:s');

$baseDir = dirname(__DIR__, 2);
$xmlPath = $baseDir . '/sitemap-eventos.xml';

$_pdo_backup_es = isset($pdo) ? $pdo : null;
$log = [];
$log[] = "[$now] Iniciando regeneración de sitemap-eventos.xml";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Eventos vigentes/futuros
    $stmt = $pdo->query("
        SELECT id, slug, COALESCE(updated_at, created_at) AS fecha_mod
        FROM cultural_events
        WHERE is_active = 1
          AND slug IS NOT NULL AND slug != ''
          AND (
              (end_date IS NULL     AND start_date >= CURDATE()) OR
              (end_date IS NOT NULL AND end_date   >= CURDATE())
          )
        ORDER BY COALESCE(updated_at, created_at) DESC
    ");
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Traducciones indexadas por event_id
    $stmtT = $pdo->query("
        SELECT event_id, language_code, slug
        FROM cultural_events_trads
        WHERE language_code IN ('en','fr','de','zh')
          AND slug IS NOT NULL AND slug != ''
    ");
    $tradsPorEvento = [];
    foreach ($stmtT->fetchAll(PDO::FETCH_ASSOC) as $tr) {
        $tradsPorEvento[$tr['event_id']][$tr['language_code']] = $tr['slug'];
    }

    // Construir XML
    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<!-- Sitemap eventos español — rutasrurales.io — ' . $now . ' -->' . "\n";
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
            $alts = ['es' => "{$baseUrl}/evento/{$slug}"];
            foreach ($langs as $lang => $st) {
                $alts[$lang] = "{$baseUrl}/{$lang}/evento/" . htmlspecialchars($st, ENT_XML1, 'UTF-8');
            }
            foreach ($alts as $hLang => $hUrl) {
                $hAttr = ($hLang === 'zh') ? 'zh-Hans' : $hLang;
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$hAttr}\" href=\"{$hUrl}\"/>\n";
            }
            $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$baseUrl}/evento/{$slug}\"/>\n";
            $conHreflang++;
        } else {
            $sinHreflang++;
        }

        $xml .= "  </url>\n";
    }

    $xml .= "\n</urlset>\n";

    $bytes = file_put_contents($xmlPath, $xml);
    if ($bytes === false) {
        throw new Exception("No se pudo escribir en {$xmlPath}");
    }

    $log[] = "OK: sitemap-eventos.xml generado ({$bytes} bytes) — " . count($eventos) . " eventos (" . $conHreflang . " con hreflang, " . $sinHreflang . " sin traducción)";

    // Actualizar lastmod en sitemap.xml índice
    $sitemapIndexPath = $baseDir . '/sitemap.xml';
    if (file_exists($sitemapIndexPath)) {
        $sitemapContent = file_get_contents($sitemapIndexPath);
        if (strpos($sitemapContent, 'sitemap-eventos.xml') === false) {
            $entry = "  <sitemap>\n    <loc>{$baseUrl}/sitemap-eventos.xml</loc>\n    <lastmod>{$today}</lastmod>\n  </sitemap>\n";
            $sitemapContent = str_replace('</sitemapindex>', $entry . '</sitemapindex>', $sitemapContent);
        } else {
            $sitemapContent = preg_replace(
                '/(sitemap-eventos\.xml<\/loc>\s*<lastmod>)\d{4}-\d{2}-\d{2}(<\/lastmod>)/',
                '${1}' . $today . '${2}',
                $sitemapContent
            );
        }
        file_put_contents($sitemapIndexPath, $sitemapContent);
        $log[] = "OK: Actualizado lastmod de sitemap-eventos.xml en el índice";
    }

} catch (PDOException $e) {
    $log[] = "ERROR BD: " . $e->getMessage();
} catch (Exception $e) {
    $log[] = "ERROR: " . $e->getMessage();
}

if ($_pdo_backup_es !== null) { $pdo = $_pdo_backup_es; }
unset($_pdo_backup_es);

// Log
$logPath = __DIR__ . '/cron.log';
file_put_contents($logPath, implode("\n", $log) . "\n---\n", FILE_APPEND);

if ($esCLI) {
    echo implode("\n", $log) . "\n";
} elseif (!$esInclude) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo implode("\n", $log) . "\n";
}
