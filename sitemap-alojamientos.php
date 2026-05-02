<?php
/**
 * Sitemap dinámico de alojamientos con soporte de imágenes
 * URL: https://rutasrurales.io/sitemap-alojamientos.php
 *
 * Incluye <image:image> para cada foto, ayudando a Google a indexar las imágenes
 * de los alojamientos y mejorar la visibilidad en Google Images.
 */

define('API_NO_HEADERS', true);
require_once 'api/config.php';

header('Content-Type: application/xml; charset=utf-8');
// Cache agresivo: Google no necesita ver cambios en tiempo real
header('Cache-Control: public, max-age=43200'); // 12 horas

$baseUrl = 'https://rutasrurales.io';

try {
    $pdo = getDBConnection();

    $stmt = $pdo->query("
        SELECT id, slug, name, municipality, province,
               photo1, photo2, photo3, photo4, photo5,
               photo6, photo7, photo8, photo9, photo10,
               updated_at, created_at
        FROM accommodations
        WHERE is_active = 1
          AND slug IS NOT NULL
          AND slug != ''
        ORDER BY updated_at DESC
    ");

    $alojamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('sitemap-alojamientos.php error: ' . $e->getMessage());
    $alojamientos = [];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

foreach ($alojamientos as $alo) {
    $slug    = htmlspecialchars($alo['slug']);
    $loc     = $baseUrl . '/alojamiento/' . $slug;
    $lastmod = !empty($alo['updated_at']) ? date('Y-m-d', strtotime($alo['updated_at'])) : date('Y-m-d');
    $name    = htmlspecialchars($alo['name'] ?? '');
    $ubicacion = trim(($alo['municipality'] ?? '') . ', ' . ($alo['province'] ?? ''), ', ');

    echo "  <url>\n";
    echo "    <loc>" . $loc . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";

    // Imágenes del alojamiento
    $foto_idx = 0;
    for ($i = 1; $i <= 10; $i++) {
        $foto = $alo['photo' . $i] ?? '';
        if (empty($foto)) continue;

        // Normalizar URL
        if (!preg_match('/^https?:\/\//', $foto)) {
            $foto = $baseUrl . '/' . ltrim($foto, '/');
        }

        // Saltar imágenes de Unsplash (no son nuestras, pueden no indexarse bien)
        if (strpos($foto, 'unsplash.com') !== false) continue;

        $foto_idx++;
        $foto_esc = htmlspecialchars($foto);
        $caption  = htmlspecialchars($name . ($ubicacion ? ' — ' . $ubicacion : '') . ' (foto ' . $foto_idx . ')');
        $title    = htmlspecialchars($name);

        echo "    <image:image>\n";
        echo "      <image:loc>" . $foto_esc . "</image:loc>\n";
        echo "      <image:title>" . $title . "</image:title>\n";
        echo "      <image:caption>" . $caption . "</image:caption>\n";
        echo "    </image:image>\n";
    }

    echo "  </url>\n";
}

echo '</urlset>';
