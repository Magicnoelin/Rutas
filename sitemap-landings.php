<?php
/**
 * sitemap-landings.php
 * ────────────────────────────────────────────────────────────────────────────
 * Genera el sitemap XML de las landings SEO long-tail de alojamientos.
 * Solo incluye combinaciones filtro × provincia que tienen AL MENOS 1
 * alojamiento activo en la base de datos.
 *
 * Fix aplicado: se añade $stmt->execute() antes de $stmt->fetch()
 * (antes se llamaba a fetch() sobre un statement no ejecutado → siempre false)
 * ────────────────────────────────────────────────────────────────────────────
 */
header("Content-Type: application/xml; charset=utf-8");

require_once 'api/config.php';
require_once 'alojamientos-landing/config/filters.php';

try {
    $pdo = getDBConnection();
    // Modo estricto: las excepciones PDO se lanzan si algo falla
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    // Si no hay BD, devolver sitemap vacío pero válido
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    exit;
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
         xmlns:xhtml="http://www.w3.org/1999/xhtml">';

// Prefijos de idioma: '' = español (sin prefijo), resto con prefijo
$idiomas = [
    ''     => 'es',
    'en/'  => 'en',
    'fr/'  => 'fr',
    'de/'  => 'de',
    'zh/'  => 'zh',
];

// Contadores para el log interno (no se emiten al XML)
$urlsIncluidas = 0;
$urlsOmitidas  = 0;

foreach (LANDING_FILTROS as $filter_slug => $filter_data) {

    // Saltar filtros excluidos explícitamente del sitemap (ej: alias duplicados)
    if (isset($filter_data['sitemap']) && $filter_data['sitemap'] === false) {
        continue;
    }

    $sql_filter_condition = $filter_data['sql'];

    foreach (LANDING_PROVINCIAS as $prov_slug => $prov_data) {

        $provincia_db = $prov_data['db'];

        // ── CONSULTA DE VALIDACIÓN ──────────────────────────────────────────
        // ¿Existe al menos 1 alojamiento activo con este filtro en esta provincia?
        // Las condiciones SQL del filtro son constantes PHP → no hay riesgo de
        // inyección. Solo :province viene de parámetro preparado.
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
            $stmt->execute([':province' => $provincia_db]); // ← LA LÍNEA QUE FALTABA
            $tieneContenido = ($stmt->fetch(PDO::FETCH_COLUMN) !== false);
        } catch (PDOException $e) {
            // Si la condición SQL del filtro falla (columna inexistente, etc.)
            // omitimos esta combinación silenciosamente
            error_log("[sitemap-landings] Error en filtro={$filter_slug} prov={$prov_slug}: " . $e->getMessage());
            $tieneContenido = false;
        }

        if (!$tieneContenido) {
            $urlsOmitidas++;
            continue; // Sin resultados → no incluir en sitemap
        }

        // ── EMITIR URLs PARA LOS 5 IDIOMAS ───────────────────────────────
        // La URL canónica española va sin prefijo; el resto con /{lang}/
        $urlSlug = $filter_slug . '-' . $prov_slug;
        $lastmod = date('Y-m-d');

        // Comentario separador para legibilidad del XML
        echo "\n\n  <!-- {$urlSlug} -->";

        foreach ($idiomas as $langPrefix => $langCode) {
            $url = 'https://rutasrurales.io/' . $langPrefix . 'alojamientos/' . $urlSlug;

            echo "\n  <url>";
            echo "\n    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>";
            echo "\n    <lastmod>" . $lastmod . "</lastmod>";
            echo "\n    <changefreq>weekly</changefreq>";
            echo "\n    <priority>" . ($langCode === 'es' ? '0.8' : '0.7') . "</priority>";

            // Etiquetas xhtml:link para hreflang
            foreach ($idiomas as $hlPrefix => $hlCode) {
                $hlUrl = 'https://rutasrurales.io/' . $hlPrefix . 'alojamientos/' . $urlSlug;
                echo "\n    <xhtml:link rel=\"alternate\" hreflang=\"{$hlCode}\" href=\"" . htmlspecialchars($hlUrl, ENT_XML1) . "\"/>";
            }
            // x-default apunta a la versión española
            $defaultUrl = 'https://rutasrurales.io/alojamientos/' . $urlSlug;
            echo "\n    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($defaultUrl, ENT_XML1) . "\"/>";

            echo "\n  </url>";
        }

        $urlsIncluidas += count($idiomas);
    }
}

echo "\n</urlset>";

// Log interno (solo visible en error_log, no en el XML)
error_log(sprintf(
    '[sitemap-landings] Generado OK: %d URLs incluidas, %d combinaciones omitidas (sin contenido)',
    $urlsIncluidas,
    $urlsOmitidas
));
