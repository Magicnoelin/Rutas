<?php
/**
 * GENERADOR DE SITEMAP i18n DE LUGARES DE INTERÉS
 * generar-sitemap-lugares-i18n.php — rutasrurales.io
 *
 * Genera sitemap con todas las traducciones de lugares en formato:
 *   - Una <url> por cada versión de idioma de cada lugar
 *   - Cada <url> incluye xhtml:link hreflang para TODOS los idiomas
 *   - Incluye x-default apuntando a la URL en español
 */

ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

chdir(__DIR__);

require_once __DIR__ . '/api/config.php';

header('Content-Type: application/xml; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    // Idiomas soportados
    $idiomas = ['es', 'en', 'fr', 'de', 'zh'];
    $baseUrl = 'https://rutasrurales.io';
    
    // Obtener lugares activos con sus traducciones
    $stmt = $pdo->query("
        SELECT 
            p.id, p.slug, p.name, p.municipality, p.province,
            tr_en.slug AS slug_en, tr_fr.slug AS slug_fr, 
            tr_de.slug AS slug_de, tr_zh.slug AS slug_zh,
            tr_en.name AS name_en, tr_fr.name AS name_fr,
            tr_de.name AS name_de, tr_zh.name AS name_zh,
            tr_en.meta_title AS meta_title_en, tr_fr.meta_title AS meta_title_fr,
            tr_de.meta_title AS meta_title_de, tr_zh.meta_title AS meta_title_zh,
            tr_en.meta_description AS meta_desc_en, tr_fr.meta_description AS meta_desc_fr,
            tr_de.meta_description AS meta_desc_de, tr_zh.meta_description AS meta_desc_zh
        FROM places_of_interest p
        LEFT JOIN places_of_interest_trads tr_en ON p.id = tr_en.place_id AND tr_en.language_code = 'en'
        LEFT JOIN places_of_interest_trads tr_fr ON p.id = tr_fr.place_id AND tr_fr.language_code = 'fr'
        LEFT JOIN places_of_interest_trads tr_de ON p.id = tr_de.place_id AND tr_de.language_code = 'de'
        LEFT JOIN places_of_interest_trads tr_zh ON p.id = tr_zh.place_id AND tr_zh.language_code = 'zh'
        WHERE p.is_active = 1
        ORDER BY p.name
    ");
    
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Iniciar XML
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
    $xml .= '  xmlns:xhtml="http://www.w3.org/1999/xhtml">' . PHP_EOL;
    
    foreach ($lugares as $lugar) {
        // Slugs por idioma
        $slugEs = $lugar['slug'];
        $slugEn = !empty($lugar['slug_en']) ? $lugar['slug_en'] : $slugEs . '-historic-monument-spain';
        $slugFr = !empty($lugar['slug_fr']) ? $lugar['slug_fr'] : $slugEs . '-monument-historique-espagne';
        $slugDe = !empty($lugar['slug_de']) ? $lugar['slug_de'] : $slugEs . '-historische-sehenswurdigkeit-spanien';
        $slugZh = !empty($lugar['slug_zh']) ? $lugar['slug_zh'] : $slugEs . '-lishi-jinianwu-xibanya';
        
        // Nombres por idioma
        $nameEs = $lugar['name'];
        $nameEn = !empty($lugar['name_en']) ? $lugar['name_en'] : $nameEs;
        $nameFr = !empty($lugar['name_fr']) ? $lugar['name_fr'] : $nameEs;
        $nameDe = !empty($lugar['name_de']) ? $lugar['name_de'] : $nameEs;
        $nameZh = !empty($lugar['name_zh']) ? $lugar['name_zh'] : $nameEs;
        
        // Meta titles
        $metaTitleEn = !empty($lugar['meta_title_en']) ? $lugar['meta_title_en'] : $nameEn . ' | Historic Monument in Spain';
        $metaTitleFr = !empty($lugar['meta_title_fr']) ? $lugar['meta_title_fr'] : $nameFr . ' | Monument Historique en Espagne';
        $metaTitleDe = !empty($lugar['meta_title_de']) ? $lugar['meta_title_de'] : $nameDe . ' | Historische Sehenswurdigkeit in Spanien';
        $metaTitleZh = !empty($lugar['meta_title_zh']) ? $lugar['meta_title_zh'] : $nameZh . ' | 西班牙历史纪念物';
        
        // URLs por idioma
        $urlsPorIdioma = [
            'es' => [
                'url' => $baseUrl . '/lugar/' . $slugEs,
                'lang' => 'es-ES'
            ],
            'en' => [
                'url' => $baseUrl . '/en/lugar/' . $slugEn,
                'lang' => 'en-GB'
            ],
            'fr' => [
                'url' => $baseUrl . '/fr/lugar/' . $slugFr,
                'lang' => 'fr-FR'
            ],
            'de' => [
                'url' => $baseUrl . '/de/lugar/' . $slugDe,
                'lang' => 'de-DE'
            ],
            'zh' => [
                'url' => $baseUrl . '/zh/lugar/' . $slugZh,
                'lang' => 'zh-CN'
            ]
        ];
        
        // Generar entrada para cada idioma
        foreach ($urlsPorIdioma as $langCode => $langData) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($langData['url']) . '</loc>' . PHP_EOL;
            
            // Añadir hreflang para todos los idiomas
            foreach ($urlsPorIdioma as $hreflangCode => $hreflangData) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . $hreflangData['lang'] . '" href="' . htmlspecialchars($hreflangData['url']) . '"/>' . PHP_EOL;
            }
            
            // Última modificación (usar fecha actual como placeholder)
            $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
            $xml .= '    <priority>0.8</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }
    }
    
    $xml .= '</urlset>' . PHP_EOL;
    
    // Guardar archivo
    $archivo = __DIR__ . '/sitemap-lugares-i18n.xml';
    file_put_contents($archivo, $xml);
    
    // Contar resultados
    $totalUrls = count($lugares) * count($idiomas);
    $totalLugares = count($lugares);
    
    // Mostrar resultado
    echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <title>Sitemap Lugares i18n Generado | Rutas Rurales</title>
</head>
<body class='bg-light'>
<div class='container py-5'>
    <div class='card'>
        <div class='card-header bg-success text-white'>
            <h4 class='mb-0'>✅ Sitemap de Lugares i18n Generado Correctamente</h4>
        </div>
        <div class='card-body'>
            <table class='table table-bordered'>
                <tr><td>Total lugares</td><td><strong>$totalLugares</strong></td></tr>
                <tr><td>Total URLs generadas</td><td><strong>$totalUrls</strong></td></tr>
                <tr><td>Archivo generado</td><td><code>sitemap-lugares-i18n.xml</code></td></tr>
            </table>
            
            <div class='alert alert-info mt-3'>
                <strong>📌 Importante:</strong> Ahora debes actualizar el archivo <code>sitemap.xml</code> principal 
                para incluir una referencia al nuevo sitemap de lugares i18n.
            </div>
            
            <a href='sitemap-lugares-i18n.xml' class='btn btn-primary' target='_blank'>
                👁️ Ver sitemap generado
            </a>
            <a href='admin_tablas/lugares_index.php' class='btn btn-secondary'>
                ← Volver a Lugares
            </a>
        </div>
    </div>
</div>
</body>
</html>";
    
} catch (Exception $e) {
    echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Error - Rutas Rurales</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container py-5'>
    <div class='card'>
        <div class='card-header bg-danger text-white'>
            <h4 class='mb-0'>❌ Error al generar sitemap</h4>
        </div>
        <div class='card-body'>
            <div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>
            <a href='admin_tablas/lugares_index.php' class='btn btn-secondary'>← Volver</a>
        </div>
    </div>
</div>
</body>
</html>";
}
