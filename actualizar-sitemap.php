<?php
/**
 * GENERADOR AUTOMÁTICO DE SITEMAPS - RUTAS RURALES (VERSIÓN FINAL REPARADA)
 */

chdir(__DIR__);

// 1. CONFIGURACIÓN DE BASE DE DATOS
$host = 'localhost';
$dbname = 'u412199647_Rutas';
$username = 'u412199647_olgamarin';
$password = 'Rutas5Rurales7$';
$baseUrl = 'https://rutasrurales.io';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 2. DEFINICIÓN DE SECCIONES Y TABLAS
$secciones = [
    'estatico'     => ['tipo' => 'estatico'],
    'alojamientos' => ['tabla' => 'accommodations',    'prefix' => '/alojamiento/', 'images' => true],
    'lugares'      => ['tabla' => 'places_of_interest', 'prefix' => '/lugar/',       'images' => true],
    'actividades'  => ['tabla' => 'tourist_activities', 'prefix' => '/actividad/',   'images' => false],
    'eventos'      => ['tabla' => 'cultural_events',    'prefix' => '/evento/',      'images' => false],
    'rutas'        => ['tabla' => 'routes',             'prefix' => '/rutas/',       'images' => false],
];

echo "<h2>🔄 Iniciando actualización de Sitemaps...</h2>";

$archivos_generados = [];

// ── Helper: convierte URL relativa de foto a URL absoluta ─────────────────────
function absolutePhotoUrl(string $url, string $baseUrl): string {
    $url = trim($url);
    if (!$url) return '';
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return $url;
    return $baseUrl . '/' . ltrim($url, '/');
}

// 3. GENERACIÓN DE ARCHIVOS INDIVIDUALES
foreach ($secciones as $nombre => $conf) {
    // Namespaces según sección
    $useImages  = !empty($conf['images']);
    $useHreflang = ($nombre === 'alojamientos'); // hreflang solo para alojamientos premium

    // Construir namespaces del <urlset> de forma limpia
    $nsBase  = 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
    $nsXhtml = 'xmlns:xhtml="http://www.w3.org/1999/xhtml"';
    $nsImage = 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';

    $nsList  = [$nsBase];
    if ($useHreflang) $nsList[] = $nsXhtml;
    if ($useImages)   $nsList[] = $nsImage;
    $xmlns = implode("\n      ", $nsList);

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= "<!-- sitemap-$nombre.xml — generado: " . date('Y-m-d H:i:s') . " -->\n";
    $xml .= "<urlset $xmlns>\n";
    $contador = 0;

    if ($nombre == 'estatico') {
        // Páginas fijas e idiomas
        $paginas = [
            ['url' => '/',                            'priority' => '1.0', 'freq' => 'daily'],
            ['url' => '/alojamientos-turisticos.html','priority' => '0.9', 'freq' => 'daily'],
            ['url' => '/eventos-culturales-paginacion.html',     'priority' => '0.9', 'freq' => 'daily'],
            ['url' => '/rutas-turisticas.html',       'priority' => '0.9', 'freq' => 'weekly'],
            ['url' => '/en/index.html',               'priority' => '0.7', 'freq' => 'weekly'],
            ['url' => '/fr/index.html',               'priority' => '0.7', 'freq' => 'weekly'],
            ['url' => '/zh/index.html',               'priority' => '0.7', 'freq' => 'weekly'],
            ['url' => '/de/index.html',               'priority' => '0.7', 'freq' => 'weekly'],
        ];
        foreach ($paginas as $p) {
            $xml .= "  <url>"
                . "<loc>$baseUrl{$p['url']}</loc>"
                . "<lastmod>" . date('Y-m-d') . "</lastmod>"
                . "<priority>{$p['priority']}</priority>"
                . "<changefreq>{$p['freq']}</changefreq>"
                . "</url>\n";
            $contador++;
        }
    } else {
        // Consulta a la base de datos
        try {
            if ($nombre == 'rutas') {
                // Rutas temáticas: usan status/is_public en lugar de is_active
                $sql = "SELECT slug, updated_at, '' AS photo1, '' AS name FROM `{$conf['tabla']}`
                        WHERE status = 'published'
                          AND is_public = 1
                          AND slug IS NOT NULL AND slug != ''";
            } elseif ($nombre == 'alojamientos') {
                // Alojamientos: incluir fotos (photo1…photo5) y nombre para image:title
                // is_premium para los hreflang multiidioma
                $sql = "SELECT slug, updated_at, is_premium,
                               name, municipality, province,
                               photo1, photo2, photo3, photo4, photo5
                        FROM `{$conf['tabla']}`
                        WHERE is_active = 1 AND slug IS NOT NULL AND slug != ''
                        ORDER BY updated_at DESC";
            } elseif ($nombre == 'lugares') {
                // Lugares de interés: incluir foto principal
                $sql = "SELECT slug, updated_at,
                               name, municipality,
                               photo1, '' AS photo2, '' AS photo3, '' AS photo4, '' AS photo5
                        FROM `{$conf['tabla']}`
                        WHERE is_active = 1 AND slug IS NOT NULL AND slug != ''
                        ORDER BY updated_at DESC";
            } else {
                // Resto de secciones (actividades, eventos)
                $sql = "SELECT slug, updated_at FROM `{$conf['tabla']}`
                        WHERE is_active = 1 AND slug IS NOT NULL AND slug != ''";

                if ($nombre == 'eventos') {
                    $sql .= " AND (
                        (end_date IS NULL AND start_date >= CURDATE()) OR
                        (end_date IS NOT NULL AND end_date >= CURDATE())
                    )";
                }
            }

            $stmt = $pdo->query($sql);

            // Prioridad según sección
            $priorityMap = [
                'alojamientos' => '0.9',  // Alta — contenido comercial clave
                'lugares'      => '0.8',
                'actividades'  => '0.7',
                'eventos'      => '0.8',
                'rutas'        => '0.9',
            ];
            $priority = $priorityMap[$nombre] ?? '0.8';

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $contador++;
                $lastmod  = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');
                $pageUrl  = htmlspecialchars($baseUrl . $conf['prefix'] . $row['slug'], ENT_XML1, 'UTF-8');
                $nameEsc  = htmlspecialchars($row['name'] ?? '', ENT_XML1, 'UTF-8');
                $municEsc = htmlspecialchars(($row['municipality'] ?? '') ?: ($row['province'] ?? ''), ENT_XML1, 'UTF-8');

                $xml .= "  <url>\n";
                $xml .= "    <loc>$pageUrl</loc>\n";
                $xml .= "    <lastmod>$lastmod</lastmod>\n";
                $xml .= "    <changefreq>weekly</changefreq>\n";
                $xml .= "    <priority>$priority</priority>\n";

                // ── hreflang SOLO alojamientos premium ────────────────────────
                if ($nombre === 'alojamientos' && !empty($row['is_premium'])) {
                    $idiomas = ['es', 'en', 'fr', 'de', 'zh'];
                    foreach ($idiomas as $idioma) {
                        $href = htmlspecialchars(
                            $baseUrl . ($idioma === 'es' ? '' : "/$idioma") . $conf['prefix'] . $row['slug'],
                            ENT_XML1, 'UTF-8'
                        );
                        $hl = $idioma === 'zh' ? 'zh-Hans' : $idioma;
                        $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"$hl\" href=\"$href\"/>\n";
                    }
                    $defHref = htmlspecialchars($baseUrl . $conf['prefix'] . $row['slug'], ENT_XML1, 'UTF-8');
                    $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"$defHref\"/>\n";
                }

                // ── image:image tags ──────────────────────────────────────────
                if ($useImages) {
                    $photoFields = ['photo1','photo2','photo3','photo4','photo5'];
                    $imgCount    = 0;
                    foreach ($photoFields as $pf) {
                        if (empty($row[$pf])) continue;
                        $imgUrl = absolutePhotoUrl($row[$pf], $baseUrl);
                        if (!$imgUrl) continue;
                        $imgUrl  = htmlspecialchars($imgUrl, ENT_XML1, 'UTF-8');
                        $caption = $nameEsc . ($municEsc ? " — $municEsc" : '');
                        $xml .= "    <image:image>\n";
                        $xml .= "      <image:loc>$imgUrl</image:loc>\n";
                        if ($caption) $xml .= "      <image:caption>$caption</image:caption>\n";
                        if ($nameEsc) $xml .= "      <image:title>$nameEsc</image:title>\n";
                        $xml .= "    </image:image>\n";
                        $imgCount++;
                        if ($imgCount >= 5) break; // Máximo 5 imágenes por URL en sitemap
                    }
                }

                $xml .= "  </url>\n";
            }
        } catch (Exception $e) {
            echo "⚠️ Error en tabla <b>" . htmlspecialchars($conf['tabla']) . "</b>: " . $e->getMessage() . "<br>";
            continue;
        }
    }

    $xml .= '</urlset>';
    $filename = "sitemap-$nombre.xml";

    if (file_put_contents($filename, $xml) !== false) {
        $archivos_generados[] = $filename;
        echo "✅ Archivo <b>$filename</b> actualizado con <b>$contador</b> URLs.<br>";
    } else {
        echo "❌ Error al escribir el archivo <b>$filename</b>.<br>";
    }
}

// 4. GENERACIÓN DEL SITEMAP i18n DE EVENTOS (traducciones en/fr/de/zh)
echo "<h3>🌍 Generando sitemap de eventos traducidos (i18n)...</h3>";
try {
    $sqlI18n = "
        SELECT
            e.id,
            e.slug          AS slug_es,
            e.updated_at,
            t.language_code AS lang,
            t.slug          AS slug_trad
        FROM cultural_events e
        INNER JOIN cultural_events_trads t ON t.event_id = e.id
        WHERE e.is_active = 1
          AND e.slug IS NOT NULL AND e.slug != ''
          AND t.slug IS NOT NULL AND t.slug != ''
          AND t.language_code IN ('en', 'fr', 'de', 'zh')
          AND (
              (e.end_date IS NULL     AND e.start_date >= CURDATE()) OR
              (e.end_date IS NOT NULL AND e.end_date   >= CURDATE())
          )
        ORDER BY e.id ASC, t.language_code ASC
    ";
    $stmtI18n = $pdo->query($sqlI18n);
    $rowsI18n = $stmtI18n->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar por evento
    $eventosI18n = [];
    foreach ($rowsI18n as $row) {
        $eid = $row['id'];
        if (!isset($eventosI18n[$eid])) {
            $eventosI18n[$eid] = [
                'slug_es'    => $row['slug_es'],
                'updated_at' => $row['updated_at'],
                'langs'      => [],
            ];
        }
        $eventosI18n[$eid]['langs'][$row['lang']] = $row['slug_trad'];
    }

    // Generar XML con hreflang
    $xmlI18n  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xmlI18n .= '<!-- Sitemap i18n de Eventos Culturales — rutasrurales.io -->' . "\n";
    $xmlI18n .= '<!-- Solo eventos vigentes y futuros con traducciones -->' . "\n";
    $xmlI18n .= '<!-- Generado: ' . date('Y-m-d H:i:s') . ' -->' . "\n";
    $xmlI18n .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    $xmlI18n .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    $cntI18nUrls   = 0;
    $cntI18nEventos = 0;

    foreach ($eventosI18n as $ev) {
        $slugEs  = htmlspecialchars($ev['slug_es'], ENT_XML1, 'UTF-8');
        $lastmod = !empty($ev['updated_at']) ? date('Y-m-d', strtotime($ev['updated_at'])) : date('Y-m-d');

        // Mapa de alternativas
        $alts = ['es' => "$baseUrl/evento/$slugEs"];
        foreach ($ev['langs'] as $lang => $slugTrad) {
            $alts[$lang] = "$baseUrl/$lang/evento/" . htmlspecialchars($slugTrad, ENT_XML1, 'UTF-8');
        }

        // URL española con hreflang
        $xmlI18n .= "  <url>\n";
        $xmlI18n .= "    <loc>$baseUrl/evento/$slugEs</loc>\n";
        $xmlI18n .= "    <lastmod>$lastmod</lastmod>\n";
        $xmlI18n .= "    <changefreq>weekly</changefreq>\n";
        $xmlI18n .= "    <priority>0.8</priority>\n";
        foreach ($alts as $hl => $hu) {
            $hla = ($hl === 'zh') ? 'zh-Hans' : $hl;
            $xmlI18n .= "    <xhtml:link rel=\"alternate\" hreflang=\"$hla\" href=\"$hu\"/>\n";
        }
        $xmlI18n .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"$baseUrl/evento/$slugEs\"/>\n";
        $xmlI18n .= "  </url>\n";
        $cntI18nUrls++;

        // URLs traducidas con hreflang
        foreach ($ev['langs'] as $lang => $slugTrad) {
            $slugTradEsc = htmlspecialchars($slugTrad, ENT_XML1, 'UTF-8');
            $xmlI18n .= "  <url>\n";
            $xmlI18n .= "    <loc>$baseUrl/$lang/evento/$slugTradEsc</loc>\n";
            $xmlI18n .= "    <lastmod>$lastmod</lastmod>\n";
            $xmlI18n .= "    <changefreq>weekly</changefreq>\n";
            $xmlI18n .= "    <priority>0.8</priority>\n";
            foreach ($alts as $hl => $hu) {
                $hla = ($hl === 'zh') ? 'zh-Hans' : $hl;
                $xmlI18n .= "    <xhtml:link rel=\"alternate\" hreflang=\"$hla\" href=\"$hu\"/>\n";
            }
            $xmlI18n .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"$baseUrl/evento/$slugEs\"/>\n";
            $xmlI18n .= "  </url>\n";
            $cntI18nUrls++;
        }
        $cntI18nEventos++;
    }

    $xmlI18n .= '</urlset>';

    if (file_put_contents('sitemap-eventos-i18n.xml', $xmlI18n)) {
        $archivos_generados[] = 'sitemap-eventos-i18n.xml';
        echo "✅ Archivo <b>sitemap-eventos-i18n.xml</b> actualizado con <b>$cntI18nEventos</b> eventos y <b>$cntI18nUrls</b> URLs (es + en/fr/de/zh).<br>";
    } else {
        echo "❌ Error al escribir <b>sitemap-eventos-i18n.xml</b>.<br>";
    }
} catch (Exception $e) {
    echo "⚠️ Error generando sitemap i18n de eventos: " . $e->getMessage() . "<br>";
}

// 5. GENERACIÓN DE SITEMAPS DE LANDINGS LONG-TAIL
// Indicamos a los regeneradores que NO toquen sitemap.xml (lo hacemos nosotros)
define('SKIP_SITEMAP_INDEX_UPDATE', true);

echo "<h3>🏨 Generando sitemap de landings de alojamientos...</h3>";
try {
    ob_start();
    require_once __DIR__ . '/regenerar_sitemap_landings.php';
    ob_end_clean();
    if (file_exists(__DIR__ . '/sitemap-landings.xml')) {
        $archivos_generados[] = 'sitemap-landings.xml';
        echo "✅ Archivo <b>sitemap-landings.xml</b> incluido.<br>";
    }
} catch (Throwable $e) {
    echo "⚠️ Error en landings de alojamientos: " . $e->getMessage() . "<br>";
}

echo "<h3>🗓️ Generando sitemap de landings de eventos...</h3>";
try {
    ob_start();
    require_once __DIR__ . '/regenerar_sitemap_eventos_landing.php';
    ob_end_clean();
    if (file_exists(__DIR__ . '/sitemap-eventos-landing.xml')) {
        $archivos_generados[] = 'sitemap-eventos-landing.xml';
        echo "✅ Archivo <b>sitemap-eventos-landing.xml</b> incluido.<br>";
    }
} catch (Throwable $e) {
    echo "⚠️ Error en landings de eventos: " . $e->getMessage() . "<br>";
}

// 6. GENERACIÓN DEL ÍNDICE MAESTRO
$index = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$index .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($archivos_generados as $file) {
    $index .= "  <sitemap>\n";
    $index .= "    <loc>$baseUrl/$file</loc>\n";
    $index .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
    $index .= "  </sitemap>\n";
}
$index .= '</sitemapindex>';

if (file_put_contents('sitemap.xml', $index)) {
    echo "<h3>🚀 ¡Sitemap principal actualizado con éxito!</h3>";
    @file_get_contents("https://www.google.com/ping?sitemap=" . urlencode($baseUrl . "/sitemap.xml"));
    echo "<p>📢 Aviso enviado a Google (Ping).</p>";
}
?>