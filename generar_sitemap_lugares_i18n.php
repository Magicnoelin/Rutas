<?php
/**
 * ============================================================
 * GENERADOR DE SITEMAP i18n — LUGARES DE INTERÉS
 * generar_sitemap_lugares_i18n.php — rutasrurales.io
 * ============================================================
 * MEJORAS SEO TÉCNICAS:
 *  1. Hoja de estilo XSLT <?xml-stylesheet ...?>
 *  2. Namespace correcto sitemaps.org/schemas/sitemap/0.9
 *  3. 1 <url> por idioma × lugar con hreflang bidireccional completo
 *  4. x-default → URL canónica española
 *  5. lastmod desde updated_at real de BD
 *  6. priority: 0.9 ES · 0.8 EN/FR/DE · 0.7 ZH
 *  7. Fallback slug (nunca URL vacía)
 *  8. Slugs con categoría traducida (via slug_lugares_helper)
 *  9. Panel HTML de confirmación con previsualización
 * ============================================================
 */

ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);
chdir(__DIR__);
require_once __DIR__ . '/api/config.php';

const BASE_URL   = 'https://rutasrurales.io';
const LASTMOD_FB = '2026-09-22';   // fallback si no hay fecha en BD
const CHANGEFREQ = 'monthly';

try {
    $pdo = getDBConnection();

    // Traer lugar + slugs de las 4 traducciones + lastmod real desde BD
    $stmt = $pdo->query("
        SELECT
            p.id,
            p.slug                      AS slug_es,
            p.name,
            p.municipality,
            p.province,
            COALESCE(
                DATE_FORMAT(p.updated_at, '%Y-%m-%d'),
                DATE_FORMAT(p.created_at, '%Y-%m-%d'),
                '" . LASTMOD_FB . "'
            )                           AS lastmod,
            tr_en.slug                  AS slug_en,
            tr_fr.slug                  AS slug_fr,
            tr_de.slug                  AS slug_de,
            tr_zh.slug                  AS slug_zh
        FROM places_of_interest p
        LEFT JOIN places_of_interest_trads tr_en ON p.id = tr_en.place_id AND tr_en.language_code = 'en'
        LEFT JOIN places_of_interest_trads tr_fr ON p.id = tr_fr.place_id AND tr_fr.language_code = 'fr'
        LEFT JOIN places_of_interest_trads tr_de ON p.id = tr_de.place_id AND tr_de.language_code = 'de'
        LEFT JOIN places_of_interest_trads tr_zh ON p.id = tr_zh.place_id AND tr_zh.language_code = 'zh'
        WHERE p.is_active = 1
        ORDER BY p.name ASC
    ");
    
    $lugares   = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalLugares = count($lugares);
    $totalUrls    = 0;
    $hoy          = date('Y-m-d');

    // ── CONSTRUIR XML ──────────────────────────────────────────────────────
    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<?xml-stylesheet type="text/xsl" href="' . BASE_URL . '/sitemap.xsl"?>' . "\n";
    $xml .= '<!-- SITEMAP i18n LUGARES DE INTERÉS — rutasrurales.io' . "\n";
    $xml .= '     Generado: ' . date('Y-m-d H:i:s') . ' | Total lugares: ' . $totalLugares . ' -->' . "\n";
    $xml .= '<urlset' . "\n";
    $xml .= '  xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    $xml .= '  xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n\n";

    foreach ($lugares as $lugar) {
        // ── Slugs con fallback limpio (nunca slug vacío en sitemap) ─────────
        $slugEs = $lugar['slug_es'];
        $slugEn = !empty($lugar['slug_en']) ? $lugar['slug_en'] : $slugEs;
        $slugFr = !empty($lugar['slug_fr']) ? $lugar['slug_fr'] : $slugEs;
        $slugDe = !empty($lugar['slug_de']) ? $lugar['slug_de'] : $slugEs;
        $slugZh = !empty($lugar['slug_zh']) ? $lugar['slug_zh'] : $slugEs;

        // ── URLs por idioma ─────────────────────────────────────────────────
        $urlEs = BASE_URL . '/lugar/'    . $slugEs;
        $urlEn = BASE_URL . '/en/lugar/' . $slugEn;
        $urlFr = BASE_URL . '/fr/lugar/' . $slugFr;
        $urlDe = BASE_URL . '/de/lugar/' . $slugDe;
        $urlZh = BASE_URL . '/zh/lugar/' . $slugZh;

        // ── lastmod desde BD o fallback ─────────────────────────────────────
        $lastmod = !empty($lugar['lastmod']) ? $lugar['lastmod'] : LASTMOD_FB;

        // ── Bloque hreflang completo (idéntico en los 5 <url>) ──────────────
        $hreflang  = '    <xhtml:link rel="alternate" hreflang="es"        href="' . htmlspecialchars($urlEs) . '"/>' . "\n";
        $hreflang .= '    <xhtml:link rel="alternate" hreflang="en"        href="' . htmlspecialchars($urlEn) . '"/>' . "\n";
        $hreflang .= '    <xhtml:link rel="alternate" hreflang="fr"        href="' . htmlspecialchars($urlFr) . '"/>' . "\n";
        $hreflang .= '    <xhtml:link rel="alternate" hreflang="de"        href="' . htmlspecialchars($urlDe) . '"/>' . "\n";
        $hreflang .= '    <xhtml:link rel="alternate" hreflang="zh"        href="' . htmlspecialchars($urlZh) . '"/>' . "\n";
        $hreflang .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($urlEs) . '"/>' . "\n";

        // ── Comentario de grupo ─────────────────────────────────────────────
        $xml .= '  <!-- ' . htmlspecialchars($lugar['name'])
              . ' · ' . htmlspecialchars($lugar['municipality'])
              . ' (id:' . $lugar['id'] . ') -->' . "\n";

        // ── 5 bloques <url>, uno por idioma ─────────────────────────────────
        $bloques = [
            ['url' => $urlEs, 'priority' => '0.9'],
            ['url' => $urlEn, 'priority' => '0.8'],
            ['url' => $urlFr, 'priority' => '0.8'],
            ['url' => $urlDe, 'priority' => '0.8'],
            ['url' => $urlZh, 'priority' => '0.7'],
        ];

        foreach ($bloques as $b) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($b['url']) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . CHANGEFREQ . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $b['priority'] . '</priority>' . "\n";
            $xml .= $hreflang;
            $xml .= '  </url>' . "\n";
            $totalUrls++;
        }
        $xml .= "\n";
    }

    $xml .= '</urlset>' . "\n";

    // ── GUARDAR EN DISCO ───────────────────────────────────────────────────
    $rutaArchivo   = __DIR__ . '/sitemap-lugares-i18n.xml';
    $bytesEscritos = file_put_contents($rutaArchivo, $xml);
    $tamanoKB      = $bytesEscritos !== false ? round($bytesEscritos / 1024, 1) : 0;

    // Preview: 5 primeros para mostrar en el panel
    $preview = array_slice($lugares, 0, 5);
    
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Sitemap Lugares i18n — Rutas Rurales</title>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow border-0 mb-4">
        <div class="card-header bg-success text-white d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <h4 class="mb-0">Sitemap de Lugares i18n generado correctamente</h4>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3"><div class="border rounded p-3 text-center bg-white">
                    <div class="fs-2 fw-bold text-success"><?= $totalLugares ?></div>
                    <div class="text-muted small">Lugares</div>
                </div></div>
                <div class="col-6 col-md-3"><div class="border rounded p-3 text-center bg-white">
                    <div class="fs-2 fw-bold text-primary"><?= $totalUrls ?></div>
                    <div class="text-muted small">URLs totales</div>
                </div></div>
                <div class="col-6 col-md-3"><div class="border rounded p-3 text-center bg-white">
                    <div class="fs-2 fw-bold text-warning"><?= $tamanoKB ?> KB</div>
                    <div class="text-muted small">Tamaño</div>
                </div></div>
                <div class="col-6 col-md-3"><div class="border rounded p-3 text-center bg-white">
                    <div class="fs-2 fw-bold text-info"><?= $hoy ?></div>
                    <div class="text-muted small">Generado</div>
                </div></div>
            </div>
            <table class="table table-bordered table-sm mb-3">
                <tr><td>Archivo</td><td><code>sitemap-lugares-i18n.xml</code>
                    <span class="badge <?= $bytesEscritos !== false ? 'bg-success' : 'bg-danger' ?>">
                        <?= $bytesEscritos !== false ? '✓ OK' : 'Error escritura' ?></span></td></tr>
                <tr><td>Namespace</td><td><code>http://www.sitemaps.org/schemas/sitemap/0.9</code></td></tr>
                <tr><td>Hoja XSL</td><td><code><?= BASE_URL ?>/sitemap.xsl</code></td></tr>
                <tr><td>Hreflang</td><td>
                    <span class="badge bg-primary">es</span>
                    <span class="badge bg-success">en</span>
                    <span class="badge bg-info">fr</span>
                    <span class="badge bg-warning text-dark">de</span>
                    <span class="badge bg-danger">zh</span>
                    <span class="badge bg-dark">x-default→ES</span>
                </td></tr>
                <tr><td>Priority</td><td><code>ES:0.9 · EN/FR/DE:0.8 · ZH:0.7</code></td></tr>
            </table>
            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle"></i>
                Verifica que <code>sitemap.xml</code> incluye <code><?= BASE_URL ?>/sitemap-lugares-i18n.xml</code>
                y envíalo a <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a>.
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="sitemap-lugares-i18n.xml" class="btn btn-primary" target="_blank">
                    <i class="bi bi-eye"></i> Ver XML</a>
                <a href="admin_tablas/lugares_index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver</a>
                <a href="admin_tablas/regenerar_traducciones_lugares.php" class="btn btn-outline-warning">
                    <i class="bi bi-arrow-repeat"></i> Regenerar traducciones</a>
            </div>
        </div>
    </div>
    
    <div class="card shadow border-0 mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Previsualización — primeros 5 lugares</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-dark">
                    <tr><th>Lugar</th><th>ES (0.9)</th><th>EN (0.8)</th><th>FR (0.8)</th><th>x-default</th></tr>
                </thead>
                <tbody>
                <?php foreach ($preview as $l):
                    $sEs = $l['slug_es'];
                    $sEn = !empty($l['slug_en']) ? $l['slug_en'] : $sEs;
                    $sFr = !empty($l['slug_fr']) ? $l['slug_fr'] : $sEs; ?>
                <tr>
                    <td><strong><?= htmlspecialchars($l['name']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($l['municipality']) ?></small></td>
                    <td><code style="font-size:.7rem">/lugar/<?= htmlspecialchars($sEs) ?></code></td>
                    <td><code style="font-size:.7rem">/en/lugar/<?= htmlspecialchars($sEn) ?></code></td>
                    <td><code style="font-size:.7rem">/fr/lugar/<?= htmlspecialchars($sFr) ?></code></td>
                    <td><code style="font-size:.7rem">/lugar/<?= htmlspecialchars($sEs) ?></code> ✓</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="bi bi-shield-check"></i> Mejoras SEO técnicas implementadas</h5>
        </div>
        <div class="card-body">
            <ul class="mb-0 small">
                <li>✅ <strong>Hoja XSLT</strong> — legible en navegador directamente.</li>
                <li>✅ <strong>Namespace correcto</strong> — <code>sitemaps.org/schemas/sitemap/0.9</code>.</li>
                <li>✅ <strong>1 &lt;url&gt; por idioma</strong> — 5 bloques × lugar (<?= $totalUrls ?> URLs totales).</li>
                <li>✅ <strong>Hreflang bidireccional completo</strong> — 6 etiquetas en cada bloque (5 idiomas + x-default).</li>
                <li>✅ <strong>x-default → ES</strong> — señal canónica principal para Google.</li>
                <li>✅ <strong>Priority diferenciada</strong> — ES:0.9, EN/FR/DE:0.8, ZH:0.7.</li>
                <li>✅ <strong>lastmod real</strong> — desde <code>updated_at</code> de BD.</li>
                <li>✅ <strong>Slugs con categoría traducida</strong> — <code>winery-/chateau-/burg-</code>, sin <code>-spain</code>.</li>
                <li>✅ <strong>Fallback slug seguro</strong> — nunca se genera una URL vacía.</li>
                <li>✅ <strong>Tamaño controlado</strong> — <?= $tamanoKB ?> KB (límite Google: 50 MB / 50 000 URLs).</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
<?php
} catch (Exception $e) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Error — Sitemap</title>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow border-0">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Error al generar sitemap</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-danger"><?= htmlspecialchars($e->getMessage()) ?></div>
            <a href="admin_tablas/lugares_index.php" class="btn btn-secondary">← Volver</a>
        </div>
    </div>
</div>
</body>
</html>
<?php
}
