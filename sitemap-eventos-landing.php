<?php
/**
 * sitemap-eventos-landing.php
 * ────────────────────────────────────────────────────────────────────────────
 * Genera el sitemap XML de las landings long-tail de EVENTOS CULTURALES.
 * Solo incluye combinaciones filtro × provincia que tienen AL MENOS 1
 * evento activo y aprobado (futuro o en curso) en la base de datos.
 *
 * Patrón idéntico a sitemap-landings.php (alojamientos).
 * Accesible en: https://rutasrurales.io/sitemap-eventos-landing.php
 * ────────────────────────────────────────────────────────────────────────────
 */
header("Content-Type: application/xml; charset=utf-8");

require_once 'api/config.php';
require_once 'eventos-landing/config/filters.php';

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    exit;
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n<!-- sitemap-eventos-landing | generado: " . gmdate('Y-m-d H:i') . " UTC -->";
echo "\n<!-- Solo URLs con ≥1 evento activo, aprobado y futuro -->";
echo "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
         xmlns:xhtml="http://www.w3.org/1999/xhtml">';

// Prefijos de idioma: '' = español (sin prefijo), resto con prefijo
$idiomas = [
    ''    => 'es',
    'en/' => 'en',
    'fr/' => 'fr',
    'de/' => 'de',
    'zh/' => 'zh',
];

$urlsIncluidas = 0;
$urlsOmitidas  = 0;
$hoy           = date('Y-m-d');

/**
 * Verifica si existe al menos 1 evento válido para la combinación dada.
 */
function tieneEventos(PDO $pdo, string $provincia_db, string $sql_filtro): bool
{
    $where = [
        'e.is_active = 1',
        "e.moderation_status = 'approved'",
        'COALESCE(e.end_date, e.start_date) >= CURDATE()',
        'e.province = :province',
    ];

    if (!empty(trim($sql_filtro))) {
        $where[] = '(' . $sql_filtro . ')';
    }

    $sql = 'SELECT 1 FROM cultural_events e WHERE '
         . implode(' AND ', $where)
         . ' LIMIT 1';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':province' => $provincia_db]);
        return ($stmt->fetchColumn() !== false);
    } catch (PDOException $e) {
        error_log('[sitemap-eventos-landing] SQL error: ' . $e->getMessage());
        return false;
    }
}

// ── BLOQUE 1: Solo provincia (/eventos/{provincia}) ──────────────────────────
// Prioridad 1.0 — página que agrupa todos los eventos de la provincia
foreach (EVENTOS_PROVINCIAS as $prov_slug => $prov_data) {

    $provincia_db = $prov_data['db'];

    // ¿Tiene algún evento activo en esta provincia?
    $sql = 'SELECT 1 FROM cultural_events e
            WHERE e.is_active = 1
              AND e.moderation_status = \'approved\'
              AND COALESCE(e.end_date, e.start_date) >= CURDATE()
              AND e.province = :province
            LIMIT 1';
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':province' => $provincia_db]);
        $tiene = ($stmt->fetchColumn() !== false);
    } catch (PDOException $e) {
        error_log('[sitemap-eventos-landing] provincia error: ' . $e->getMessage());
        $tiene = false;
    }

    if (!$tiene) {
        $urlsOmitidas++;
        continue;
    }

    $canonicUrl = 'https://rutasrurales.io/eventos/' . $prov_slug;

    echo "\n\n  <url>";
    echo "\n    <loc>" . htmlspecialchars($canonicUrl, ENT_XML1) . "</loc>";
    echo "\n    <lastmod>" . $hoy . "</lastmod>";
    echo "\n    <changefreq>weekly</changefreq>";
    echo "\n    <priority>1.0</priority>";
    foreach ($idiomas as $langPrefix => $langCode) {
        $altUrl = 'https://rutasrurales.io/' . $langPrefix . 'eventos/' . $prov_slug;
        echo "\n    <xhtml:link rel=\"alternate\" hreflang=\"{$langCode}\" href=\"" . htmlspecialchars($altUrl, ENT_XML1) . "\"/>";
    }
    echo "\n    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($canonicUrl, ENT_XML1) . "\"/>";
    echo "\n  </url>";
    $urlsIncluidas++;
}

// ── BLOQUE 2: Filtro + provincia (/eventos/{filtro}-{provincia}) ─────────────
// Prioridad 0.8 — el núcleo long-tail
foreach (EVENTOS_FILTROS as $filter_slug => $filter_data) {

    // Excluir filtros marcados como no-sitemap (ej: este-mes)
    if (isset($filter_data['sitemap']) && $filter_data['sitemap'] === false) {
        continue;
    }

    $sql_filtro = $filter_data['sql'];

    foreach (EVENTOS_PROVINCIAS as $prov_slug => $prov_data) {

        $provincia_db = $prov_data['db'];

        if (!tieneEventos($pdo, $provincia_db, $sql_filtro)) {
            $urlsOmitidas++;
            continue;
        }

        $urlSlug    = $filter_slug . '-' . $prov_slug;
        $canonicUrl = 'https://rutasrurales.io/eventos/' . $urlSlug;

        echo "\n\n  <url>";
        echo "\n    <loc>" . htmlspecialchars($canonicUrl, ENT_XML1) . "</loc>";
        echo "\n    <lastmod>" . $hoy . "</lastmod>";
        echo "\n    <changefreq>weekly</changefreq>";
        echo "\n    <priority>0.8</priority>";
        foreach ($idiomas as $langPrefix => $langCode) {
            $altUrl = 'https://rutasrurales.io/' . $langPrefix . 'eventos/' . $urlSlug;
            echo "\n    <xhtml:link rel=\"alternate\" hreflang=\"{$langCode}\" href=\"" . htmlspecialchars($altUrl, ENT_XML1) . "\"/>";
        }
        echo "\n    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($canonicUrl, ENT_XML1) . "\"/>";
        echo "\n  </url>";
        $urlsIncluidas++;
    }
}

// ── BLOQUE 3: Categoría + temporada + provincia (/eventos/{cat}-{temp}-{prov})
// Prioridad 0.7 — combinaciones de mayor especificidad con demanda real
$categoria_keys = array_keys(array_filter(EVENTOS_FILTROS, fn($f) => $f['group'] === 'categoria'));
$temporada_keys = array_keys(array_filter(EVENTOS_FILTROS, fn($f) => $f['group'] === 'temporada'
    && !(isset($f['sitemap']) && $f['sitemap'] === false)));

foreach ($categoria_keys as $ckey) {
    foreach ($temporada_keys as $tkey) {

        $sql_combinado = '(' . EVENTOS_FILTROS[$ckey]['sql'] . ')'
                       . ' AND ('
                       . EVENTOS_FILTROS[$tkey]['sql'] . ')';

        foreach (EVENTOS_PROVINCIAS as $prov_slug => $prov_data) {

            $provincia_db = $prov_data['db'];

            // Construimos la verificación inline para la combinación AND
            $where = [
                'e.is_active = 1',
                "e.moderation_status = 'approved'",
                'COALESCE(e.end_date, e.start_date) >= CURDATE()',
                'e.province = :province',
                '(' . EVENTOS_FILTROS[$ckey]['sql'] . ')',
                '(' . EVENTOS_FILTROS[$tkey]['sql'] . ')',
            ];
            $sqlCheck = 'SELECT 1 FROM cultural_events e WHERE '
                      . implode(' AND ', $where)
                      . ' LIMIT 1';
            try {
                $stmt = $pdo->prepare($sqlCheck);
                $stmt->execute([':province' => $provincia_db]);
                $tiene = ($stmt->fetchColumn() !== false);
            } catch (PDOException $e) {
                error_log('[sitemap-eventos-landing] cat+temp+prov error: ' . $e->getMessage());
                $tiene = false;
            }

            if (!$tiene) {
                $urlsOmitidas++;
                continue;
            }

            $urlSlug    = $ckey . '-' . $tkey . '-' . $prov_slug;
            $canonicUrl = 'https://rutasrurales.io/eventos/' . $urlSlug;

            echo "\n\n  <url>";
            echo "\n    <loc>" . htmlspecialchars($canonicUrl, ENT_XML1) . "</loc>";
            echo "\n    <lastmod>" . $hoy . "</lastmod>";
            echo "\n    <changefreq>weekly</changefreq>";
            echo "\n    <priority>0.7</priority>";
            foreach ($idiomas as $langPrefix => $langCode) {
                $altUrl = 'https://rutasrurales.io/' . $langPrefix . 'eventos/' . $urlSlug;
                echo "\n    <xhtml:link rel=\"alternate\" hreflang=\"{$langCode}\" href=\"" . htmlspecialchars($altUrl, ENT_XML1) . "\"/>";
            }
            echo "\n    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($canonicUrl, ENT_XML1) . "\"/>";
            echo "\n  </url>";
            $urlsIncluidas++;
        }
    }
}

echo "\n</urlset>";

error_log(sprintf(
    '[sitemap-eventos-landing] Generado OK: %d URLs incluidas, %d combinaciones omitidas (sin contenido)',
    $urlsIncluidas,
    $urlsOmitidas
));
