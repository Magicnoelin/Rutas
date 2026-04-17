<?php
/**
 * GENERADOR DE SITEMAP i18n DE EVENTOS CULTURALES
 * generar-sitemap-eventos-i18n.php — rutasrurales.io
 *
 * Genera sitemap-eventos-i18n.xml con las URLs traducidas de eventos
 * SOLO para eventos vigentes y futuros (end_date >= HOY o start_date >= HOY).
 *
 * Idiomas: es (español), en, fr, de, zh
 * URL española:  /evento/{slug}
 * URL traducida: /{lang}/evento/{slug_traducido}
 *
 * Incluye etiquetas xhtml:link hreflang para SEO internacional correcto.
 *
 * Uso: Ejecutar manualmente o via cron tras insertar nuevas traducciones.
 */

chdir(__DIR__);

// ── CONFIGURACIÓN ────────────────────────────────────────────────────────────
$host       = 'localhost';
$dbname     = 'u412199647_Rutas';
$username   = 'u412199647_olgamarin';
$password   = 'Rutas5Rurales7$';
$baseUrl    = 'https://rutasrurales.io';
$outputFile = __DIR__ . '/sitemap-eventos-i18n.xml';
$isCli      = (php_sapi_name() === 'cli');

function out($msg, $isCli) {
    echo $isCli ? strip_tags($msg) . "\n" : "<p>$msg</p>";
}

// ── CONEXIÓN ─────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    out("❌ Error de conexión: " . $e->getMessage(), $isCli);
    exit(1);
}

// ── CONSULTA PRINCIPAL ────────────────────────────────────────────────────────
// Obtener todos los eventos vigentes/futuros que tienen al menos una traducción.
// Filtramos por fecha igual que en el sitemap de eventos en español.
try {
    $stmt = $pdo->query("
        SELECT
            e.id,
            e.slug          AS slug_es,
            e.updated_at,
            e.start_date,
            e.end_date,
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
} catch (PDOException $e) {
    out("❌ Error en la consulta: " . $e->getMessage(), $isCli);
    exit(1);
}

// ── AGRUPAR POR EVENTO ────────────────────────────────────────────────────────
// Estructura: $eventos[event_id] = ['slug_es'=>..., 'updated_at'=>..., 'langs'=>[lang=>slug,...]]
$eventos = [];
foreach ($rows as $row) {
    $id = $row['id'];
    if (!isset($eventos[$id])) {
        $eventos[$id] = [
            'slug_es'    => $row['slug_es'],
            'updated_at' => $row['updated_at'],
            'start_date' => $row['start_date'],
            'langs'      => [],
        ];
    }
    $eventos[$id]['langs'][$row['lang']] = $row['slug_trad'];
}

// ── GENERACIÓN DEL XML ────────────────────────────────────────────────────────
// Usamos el namespace xhtml para las etiquetas hreflang (estándar Google).
$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<!-- Sitemap i18n de Eventos Culturales — rutasrurales.io -->' . "\n";
$xml .= '<!-- Solo eventos vigentes y futuros con traducciones -->' . "\n";
$xml .= '<!-- Generado: ' . date('Y-m-d H:i:s') . ' -->' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
$xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml"' . "\n";
$xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
$xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
$xml .= '          http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

$totalUrls = 0;
$totalEventos = 0;

foreach ($eventos as $id => $ev) {
    $slugEs  = htmlspecialchars($ev['slug_es'], ENT_XML1, 'UTF-8');
    $lastmod = !empty($ev['updated_at'])
        ? date('Y-m-d', strtotime($ev['updated_at']))
        : date('Y-m-d');

    // Construir mapa completo de alternativas (es + todas las traducciones disponibles)
    $alternativas = ['es' => "{$baseUrl}/evento/{$slugEs}"];
    foreach ($ev['langs'] as $lang => $slugTrad) {
        $slugTradEsc = htmlspecialchars($slugTrad, ENT_XML1, 'UTF-8');
        $alternativas[$lang] = "{$baseUrl}/{$lang}/evento/{$slugTradEsc}";
    }

    // ── Entrada para la URL en español ──────────────────────────────────────
    $xml .= "  <url>\n";
    $xml .= "    <loc>{$baseUrl}/evento/{$slugEs}</loc>\n";
    $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
    $xml .= "    <changefreq>weekly</changefreq>\n";
    $xml .= "    <priority>0.8</priority>\n";
    // hreflang para todas las versiones
    foreach ($alternativas as $hLang => $hUrl) {
        $hLangAttr = ($hLang === 'zh') ? 'zh-Hans' : $hLang;
        $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$hLangAttr}\" href=\"{$hUrl}\"/>\n";
    }
    // x-default apunta a la versión española
    $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$baseUrl}/evento/{$slugEs}\"/>\n";
    $xml .= "  </url>\n";
    $totalUrls++;

    // ── Entrada para cada URL traducida ─────────────────────────────────────
    foreach ($ev['langs'] as $lang => $slugTrad) {
        $slugTradEsc = htmlspecialchars($slugTrad, ENT_XML1, 'UTF-8');
        $hLangAttr   = ($lang === 'zh') ? 'zh-Hans' : $lang;

        $xml .= "  <url>\n";
        $xml .= "    <loc>{$baseUrl}/{$lang}/evento/{$slugTradEsc}</loc>\n";
        $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
        $xml .= "    <changefreq>weekly</changefreq>\n";
        $xml .= "    <priority>0.8</priority>\n";
        // hreflang para todas las versiones
        foreach ($alternativas as $hLang2 => $hUrl2) {
            $hLangAttr2 = ($hLang2 === 'zh') ? 'zh-Hans' : $hLang2;
            $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$hLangAttr2}\" href=\"{$hUrl2}\"/>\n";
        }
        $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$baseUrl}/evento/{$slugEs}\"/>\n";
        $xml .= "  </url>\n";
        $totalUrls++;
    }

    $totalEventos++;
}

$xml .= '</urlset>';

// ── ESCRITURA DEL ARCHIVO ─────────────────────────────────────────────────────
$ok = file_put_contents($outputFile, $xml);

if ($ok !== false) {
    out("✅ sitemap-eventos-i18n.xml generado: <strong>{$totalEventos} eventos</strong>, <strong>{$totalUrls} URLs</strong> (es + traducciones).", $isCli);
} else {
    out("❌ Error al escribir sitemap-eventos-i18n.xml. Verifica permisos de escritura.", $isCli);
    exit(1);
}

// ── ACTUALIZAR SITEMAP ÍNDICE MAESTRO ─────────────────────────────────────────
$sitemapIndex = __DIR__ . '/sitemap.xml';

if (file_exists($sitemapIndex)) {
    $contenido = file_get_contents($sitemapIndex);

    if (strpos($contenido, 'sitemap-eventos-i18n.xml') === false) {
        $entrada  = "\n  <sitemap>\n";
        $entrada .= "    <loc>{$baseUrl}/sitemap-eventos-i18n.xml</loc>\n";
        $entrada .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        $entrada .= "  </sitemap>";

        $contenido = str_replace('</sitemapindex>', $entrada . "\n</sitemapindex>", $contenido);

        if (file_put_contents($sitemapIndex, $contenido)) {
            out("✅ sitemap.xml actualizado: se añadió el enlace a sitemap-eventos-i18n.xml.", $isCli);
        } else {
            out("⚠️ No se pudo actualizar sitemap.xml. Añade manualmente el enlace a sitemap-eventos-i18n.xml.", $isCli);
        }
    } else {
        out("ℹ️ sitemap.xml ya contiene el enlace a sitemap-eventos-i18n.xml. No se modificó.", $isCli);
    }
} else {
    out("⚠️ No se encontró sitemap.xml. Ejecuta primero actualizar-sitemap.php para generar el índice maestro.", $isCli);
}

// ── PING A GOOGLE ─────────────────────────────────────────────────────────────
@file_get_contents("https://www.google.com/ping?sitemap=" . urlencode("{$baseUrl}/sitemap-eventos-i18n.xml"));
out("📢 Ping enviado a Google para sitemap-eventos-i18n.xml.", $isCli);
