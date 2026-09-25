<?php
/**
 * GENERAR TRADUCCIONES DE LUGARES DE INTERÉS (INCREMENTAL) — v3 i18n
 * ─────────────────────────────────────────────────────────────────────
 * Nueva arquitectura:
 *  FASE 1 — 'es' clonado en places_of_interest_trads (slug limpio v2).
 *            El slug canónico del ROUTER permanece en places_of_interest.slug.
 *  FASE 2 — en/fr/de/zh leen textos base desde trads WHERE language_code='es'.
 *            Datos estructurales (entry_fee, categoria) → JOIN tabla maestra.
 *
 * Slugs:  ES: iglesia-san-pedro-zamora  |  EN: church-san-pedro-zamora
 *         FR: eglise-san-pedro-zamora   |  DE: kirche-san-pedro-zamora
 *
 * Hreflang en cada página de detalle:
 *   hreflang="es"          → places_of_interest.slug  (canónico del router)
 *   hreflang="en|fr|de|zh" → places_of_interest_trads.slug (este script)
 */

include 'db.php';
require_once 'slug_lugares_helper.php';

header('Content-Type: text/html; charset=utf-8');

try {

// ── TEXTOS FIJOS POR IDIOMA (5 idiomas, ES incluido) ─────────────────────
$textos = [
    'es' => [
        'intro' => 'Descubre',      'in'   => 'en',
        'h3a'   => 'Sobre',         'h3v'  => 'Qué ver',
        'acc'   => 'Accesible en silla de ruedas, apto para familias',
        'msuf'  => 'en España',     'mv'   => 'Visita',
        'mend'  => 'Descubre este lugar especial en España.',
    ],
    'en' => [
        'intro' => 'Discover',      'in'   => 'in',
        'h3a'   => 'About',         'h3v'  => 'What to See',
        'acc'   => 'Wheelchair accessible, family-friendly',
        'msuf'  => 'in Spain',      'mv'   => 'Visit',
        'mend'  => 'Discover this remarkable place in Spain.',
    ],
    'fr' => [
        'intro' => 'Découvrez',     'in'   => 'à',
        'h3a'   => 'À propos de',   'h3v'  => 'À voir',
        'acc'   => 'Accessible, adapté aux familles',
        'msuf'  => 'en Espagne',    'mv'   => 'Visitez',
        'mend'  => 'Découvrez ce lieu remarquable en Espagne.',
    ],
    'de' => [
        'intro' => 'Entdecken Sie', 'in'   => 'in',
        'h3a'   => 'Über',          'h3v'  => 'Sehenswürdigkeiten',
        'acc'   => 'Barrierefrei, familienfreundlich',
        'msuf'  => 'in Spanien',    'mv'   => 'Besuchen Sie',
        'mend'  => 'Entdecken Sie diesen tollen Ort in Spanien.',
    ],
    'zh' => [
        'intro' => '探索',          'in'   => '在',
        'h3a'   => '关于',          'h3v'  => '参观亮点',
        'acc'   => '无障碍, 适合家庭',
        'msuf'  => '西班牙',        'mv'   => '参观',
        'mend'  => '发现西班牙的这个精彩景点。',
    ],
];

// ── PREPARED STATEMENT INSERT (reutilizado para los 5 idiomas) ────────────
$insert = $pdo->prepare("
    INSERT INTO places_of_interest_trads
        (place_id, language_code, name, slug, short_description, description,
         address, municipality, province, opening_hours, accessibility,
         meta_title, meta_description, entry_fee, entry_fee_details, facilities)
    VALUES
        (:place_id, :lang, :name, :slug, :short_desc, :description,
         :address, :municipality, :province, :opening_hours, :accessibility,
         :meta_title, :meta_description, :entry_fee, :entry_fee_details, :facilities)
");

$totalInsertados  = 0;
$insertadosEs     = 0;
$previsualizacion = [];

// ══════════════════════════════════════════════════════════════════════════
// FASE 1 — Clonar idioma base 'es' → places_of_interest_trads
//   Fuente:  places_of_interest (tabla maestra) directamente.
//   Slug ES: generarSlugLugar(...,'es') — limpio v2 sin artículos.
//   NOTA:    places_of_interest.slug (el del router) NO se modifica.
// ══════════════════════════════════════════════════════════════════════════
$stmtEs = $pdo->query("
    SELECT p.id,
           p.name,
           p.slug          AS slug_original,
           p.municipality, p.province, p.address,
           p.short_description, p.description,
           p.opening_hours, p.entry_fee, p.entry_fee_details, p.facilities,
           COALESCE(c.name,'') AS categoria
    FROM   places_of_interest p
    LEFT JOIN categories_places c ON p.category_id = c.id
    WHERE  p.is_active = 1
      AND  p.id NOT IN (
               SELECT place_id FROM places_of_interest_trads WHERE language_code = 'es'
           )
    ORDER BY p.id
");
$lugaresParaEs = $stmtEs->fetchAll(PDO::FETCH_ASSOC);
$t = $textos['es'];

foreach ($lugaresParaEs as $lugar) {
    $slugEs    = generarSlugLugar($lugar['name'], $lugar['categoria'], $lugar['municipality'], 'es');
    $catLabel  = !empty($lugar['categoria']) ? $lugar['categoria'] : 'Lugar';
    $shortDesc = $t['intro'].' '.$lugar['name'].' '.$t['in'].' '.$lugar['municipality'].', '.$lugar['province'].'.';
    $desc      = '<section><h3>'.$t['h3a'].' '.htmlspecialchars($lugar['name']).'</h3>'
               . '<p>'.($lugar['short_description'] ?? '').'</p></section>'
               . '<section><h3>'.$t['h3v'].'</h3><p>'.($lugar['description'] ?? '').'</p></section>';
    $metaTitle = $lugar['name'].' | '.$catLabel.' '.$t['msuf'];
    $metaDesc  = $t['mv'].' '.$lugar['name'].' '.$t['in'].' '.$lugar['municipality'].', '.$lugar['province'].'. '.$t['mend'];

    $insert->execute([
        ':place_id'         => $lugar['id'],
        ':lang'             => 'es',
        ':name'             => $lugar['name'],
        ':slug'             => $slugEs,
        ':short_desc'       => $shortDesc,
        ':description'      => $desc,
        ':address'          => $lugar['address']           ?? '',
        ':municipality'     => $lugar['municipality'],
        ':province'         => $lugar['province'],
        ':opening_hours'    => $lugar['opening_hours']     ?? '',
        ':accessibility'    => $t['acc'],
        ':meta_title'       => $metaTitle,
        ':meta_description' => $metaDesc,
        ':entry_fee'        => $lugar['entry_fee']         ?? '',
        ':entry_fee_details'=> $lugar['entry_fee_details'] ?? '',
        ':facilities'       => $lugar['facilities']        ?? '',
    ]);
    $insertadosEs++;
    $totalInsertados++;

    if (count($previsualizacion) < 10) {
        $previsualizacion[$lugar['id']] = [
            'id'        => $lugar['id'],
            'nombre'    => $lugar['name'],
            'cat'       => $lugar['categoria'],
            'muni'      => $lugar['municipality'],
            'slug_orig' => $lugar['slug_original'],
            'slug_es'   => $slugEs,
            'slug_en'   => '—',
            'slug_fr'   => '—',
            'slug_de'   => '—',
        ];
    }
}



// ══════════════════════════════════════════════════════════════════════════
// FASE 2 — Traducciones secundarias (en, fr, de, zh)
//   Fuente de TEXTOS:     places_of_interest_trads (alias pt_es, lang='es')
//   Fuente de ESTRUCTURA: places_of_interest (alias p) + categories_places (c)
//   Solo procesa lugares que YA tienen fila 'es' y NO tienen $lang.
// ══════════════════════════════════════════════════════════════════════════
foreach (['en', 'fr', 'de', 'zh'] as $lang) {
    $t = $textos[$lang];

    $stmtLang = $pdo->prepare("
        SELECT
            pt_es.place_id          AS id,
            pt_es.name,
            pt_es.municipality,
            pt_es.province,
            pt_es.address,
            pt_es.short_description AS short_es,
            pt_es.description       AS desc_es,
            pt_es.opening_hours,
            p.entry_fee,
            p.entry_fee_details,
            p.facilities,
            COALESCE(c.name, '')    AS categoria
        FROM   places_of_interest_trads pt_es
        INNER JOIN places_of_interest p  ON p.id  = pt_es.place_id
        LEFT  JOIN categories_places  c  ON c.id  = p.category_id
        WHERE  pt_es.language_code = 'es'
          AND  p.is_active = 1
          AND  pt_es.place_id NOT IN (
                   SELECT place_id FROM places_of_interest_trads WHERE language_code = :lang
               )
        ORDER BY pt_es.place_id
    ");
    $stmtLang->execute([':lang' => $lang]);
    $lugares = $stmtLang->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lugares as $lugar) {
        $slug      = generarSlugLugar($lugar['name'], $lugar['categoria'], $lugar['municipality'], $lang);
        $catLabel  = !empty($lugar['categoria']) ? $lugar['categoria'] : 'Place';
        $shortDesc = $t['intro'].' '.$lugar['name'].' '.$t['in'].' '.$lugar['municipality'].', '.$lugar['province'].'.';
        $desc      = '<section><h3>'.$t['h3a'].' '.htmlspecialchars($lugar['name']).'</h3>'
                   . '<p>'.($lugar['short_es'] ?? '').'</p></section>'
                   . '<section><h3>'.$t['h3v'].'</h3><p>'.($lugar['desc_es'] ?? '').'</p></section>';
        $metaTitle = $lugar['name'].' | '.$catLabel.' '.$t['msuf'];
        $metaDesc  = $t['mv'].' '.$lugar['name'].' '.$t['in'].' '.$lugar['municipality'].', '.$lugar['province'].'. '.$t['mend'];

        $insert->execute([
            ':place_id'         => $lugar['id'],
            ':lang'             => $lang,
            ':name'             => $lugar['name'],
            ':slug'             => $slug,
            ':short_desc'       => $shortDesc,
            ':description'      => $desc,
            ':address'          => $lugar['address']           ?? '',
            ':municipality'     => $lugar['municipality'],
            ':province'         => $lugar['province'],
            ':opening_hours'    => $lugar['opening_hours']     ?? '',
            ':accessibility'    => $t['acc'],
            ':meta_title'       => $metaTitle,
            ':meta_description' => $metaDesc,
            ':entry_fee'        => $lugar['entry_fee']         ?? '',
            ':entry_fee_details'=> $lugar['entry_fee_details'] ?? '',
            ':facilities'       => $lugar['facilities']        ?? '',
        ]);
        $totalInsertados++;

        if (isset($previsualizacion[$lugar['id']])) {
            $previsualizacion[$lugar['id']]['slug_'.$lang] = $slug;
        } elseif (count($previsualizacion) < 10 && $lang === 'en') {
            $previsualizacion[$lugar['id']] = [
                'id'        => $lugar['id'],   'nombre'    => $lugar['name'],
                'cat'       => $lugar['categoria'],         'muni'  => $lugar['municipality'],
                'slug_orig' => '(ya existía)', 'slug_es'   => '(ya existía)',
                'slug_en'   => $slug,          'slug_fr'   => '—',  'slug_de' => '—',
            ];
        }
    }
    // Completar FR y DE en previews pobladas desde EN
    if (in_array($lang, ['fr', 'de'])) {
        foreach ($previsualizacion as &$prev) {
            if ($prev['slug_'.$lang] === '—') {
                $prev['slug_'.$lang] = generarSlugLugar($prev['nombre'], $prev['cat'], $prev['muni'], $lang);
            }
        }
        unset($prev);
    }
}

// ── MÉTRICAS FINALES ──────────────────────────────────────────────────────
$cuentas = $pdo->query("
    SELECT language_code, COUNT(*) AS total
    FROM   places_of_interest_trads
    GROUP  BY language_code
")->fetchAll(PDO::FETCH_KEY_PAIR);

$totalLugares     = (int) $pdo->query("SELECT COUNT(*) FROM places_of_interest WHERE is_active = 1")->fetchColumn();
$lugaresEs        = (int)($cuentas['es'] ?? 0);
$lugaresEn        = (int)($cuentas['en'] ?? 0);
$lugaresFr        = (int)($cuentas['fr'] ?? 0);
$lugaresDe        = (int)($cuentas['de'] ?? 0);
$lugaresZh        = (int)($cuentas['zh'] ?? 0);
$lugaresCompletos = (int) $pdo->query("
    SELECT COUNT(*) FROM (
        SELECT place_id FROM places_of_interest_trads
        WHERE  language_code IN ('es','en','fr','de','zh')
        GROUP  BY place_id
        HAVING COUNT(DISTINCT language_code) = 5
    ) t
")->fetchColumn();

function colorFila(int $val, int $total): string {
    if ($val === $total) return 'table-success';
    if ($val > 0)        return 'table-warning';
    return 'table-danger';
}

$alertaEs = ($lugaresEs < $totalLugares)
    ? '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill"></i> <strong>Atención:</strong> '.($totalLugares - $lugaresEs).' lugar(es) activo(s) sin fila <code>es</code> en trads. Vuelve a ejecutar.</div>'
    : '<div class="alert alert-success border-0"><i class="bi bi-check-circle-fill"></i> <strong>Correcto:</strong> Todos los lugares activos tienen su fila base <code>es</code>.</div>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Traducciones Lugares — Rutas Rurales</title>
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:1100px">
    <h2 class="mb-1"><i class="bi bi-translate"></i> Traducciones de Lugares de Interés <small class="badge bg-primary fs-6">Incremental</small></h2>
    <p class="text-muted mb-4">Arquitectura i18n v3 — 5 idiomas (ES · EN · FR · DE · ZH)</p>
    <?= $alertaEs ?>
    <!-- TARJETAS RESUMEN -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body py-3">
                    <div class="display-6 fw-bold text-primary"><?= $totalInsertados ?></div>
                    <div class="small text-muted">Insertados esta ejecución</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body py-3">
                    <div class="display-6 fw-bold text-success"><?= $insertadosEs ?></div>
                    <div class="small text-muted">Nuevas filas <code>es</code></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body py-3">
                    <div class="display-6 fw-bold text-info"><?= $totalLugares ?></div>
                    <div class="small text-muted">Lugares activos</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body py-3">
                    <div class="display-6 fw-bold <?= ($lugaresCompletos === $totalLugares) ? 'text-success' : 'text-warning' ?>">
                        <?= $lugaresCompletos ?>/<?= $totalLugares ?>
                    </div>
                    <div class="small text-muted">Completos (5 idiomas)</div>
                </div>
            </div>
        </div>
    </div>

    : '<div class="alert alert-success border-0"><i class="bi bi-check-circle-fill"></i> <strong>Correcto:</strong> Todos los lugares activos tienen su fila base <code>es</code>.</div>';


    <!-- TABLA POR IDIOMA CON BARRA DE PROGRESO -->
    <div class="card shadow border-0 mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-bar-chart-fill"></i> Estado por idioma en <code>places_of_interest_trads</code></h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-dark">
                    <tr><th>Idioma</th><th>Flag</th><th>Registros</th><th>Cobertura</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <tr class="<?= colorFila($lugaresEs, $totalLugares) ?>">
                        <td><strong>Español (es) — BASE</strong></td><td>🇪🇸</td>
                        <td><strong><?= $lugaresEs ?></strong></td>
                        <td><div class="progress" style="height:16px"><div class="progress-bar bg-success" style="width:<?= $totalLugares ? round($lugaresEs/$totalLugares*100) : 0 ?>%"><?= $totalLugares ? round($lugaresEs/$totalLugares*100) : 0 ?>%</div></div></td>
                        <td><?= ($lugaresEs === $totalLugares) ? '✅ Completo' : '⚠️ Incompleto' ?></td>
                    </tr>
                    <?php foreach (['en'=>['🇬🇧','Inglés'],'fr'=>['🇫🇷','Francés'],'de'=>['🇩🇪','Alemán'],'zh'=>['🇨🇳','Chino']] as $code=>[$flag,$nombre]):
                        $cnt = (int)($cuentas[$code] ?? 0);
                        $pct = $totalLugares ? round($cnt/$totalLugares*100) : 0;
                    ?>
                    <tr class="<?= colorFila($cnt, $totalLugares) ?>">
                        <td><?= $nombre ?> (<?= $code ?>)</td><td><?= $flag ?></td>
                        <td><strong><?= $cnt ?></strong></td>
                        <td><div class="progress" style="height:16px"><div class="progress-bar" style="width:<?= $pct ?>%"><?= $pct ?>%</div></div></td>
                        <td><?= ($cnt === $totalLugares) ? '✅ Completo' : ($cnt > 0 ? '⚠️ Parcial' : '❌ Vacío') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-primary fw-bold">
                        <td colspan="2">COMPLETOS (5/5 idiomas)</td>
                        <td colspan="3"><?= $lugaresCompletos ?> / <?= $totalLugares ?> lugares</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- INFO HREFLANG -->
    <div class="alert alert-info mb-4">
        <h6 class="alert-heading"><i class="bi bi-info-circle-fill"></i> Arquitectura hreflang implementada</h6>
        <ul class="mb-0 small">
            <li><code>hreflang="es"</code> → <strong>places_of_interest.slug</strong> (canónico del router, NO modificado)</li>
            <li><code>hreflang="x-default"</code> → mismo que ES (señal canónica para Google)</li>
            <li><code>hreflang="en|fr|de|zh"</code> → <strong>places_of_interest_trads.slug</strong> (helper v2)</li>
            <li>Formato slug: <code>{categoría_traducida}-{nombre_propio}-{municipio}</code></li>
        </ul>
    </div>
    <!-- BOTONES DE ACCIÓN -->
    <div class="mb-4">
        <a href="lugares_index.php" class="btn btn-primary me-2"><i class="bi bi-arrow-left"></i> Volver a Lugares</a>
        <a href="../generar_sitemap_lugares_i18n.php" class="btn btn-warning me-2"><i class="bi bi-globe"></i> Generar Sitemap i18n</a>
        <a href="regenerar_traducciones_lugares.php" class="btn btn-danger"><i class="bi bi-arrow-repeat"></i> Regenerar TODAS</a>
    </div>

    <!-- PREVISUALIZACIÓN DE SLUGS -->
    <?php if (!empty($previsualizacion)): ?>
    <div class="card shadow border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-eye"></i> Previsualización — primeros <?= count($previsualizacion) ?> slugs generados</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table table-sm table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr><th>#</th><th>Nombre</th><th>Cat.</th><th>Municipio</th>
                        <th>🔴 Slug canónico (router)</th><th>🟢 Slug ES trads v2</th>
                        <th>Slug EN</th><th>Slug FR</th><th>Slug DE</th></tr>
                </thead>
                <tbody>
                <?php foreach ($previsualizacion as $p): ?>
                    <tr>
                        <td class="text-muted small"><?= $p['id'] ?></td>
                        <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                        <td><span class="badge bg-secondary small"><?= htmlspecialchars($p['cat']) ?></span></td>
                        <td class="small"><?= htmlspecialchars($p['muni']) ?></td>
                        <td><code class="text-danger" style="font-size:.68rem"><?= htmlspecialchars($p['slug_orig']) ?></code></td>
                        <td><code class="text-success" style="font-size:.68rem"><?= htmlspecialchars($p['slug_es']) ?></code></td>
                        <td><code style="font-size:.68rem"><?= htmlspecialchars($p['slug_en']) ?></code></td>
                        <td><code style="font-size:.68rem"><?= htmlspecialchars($p['slug_fr']) ?></code></td>
                        <td><code style="font-size:.68rem"><?= htmlspecialchars($p['slug_de']) ?></code></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <div class="card-footer text-muted small">
            🔴 <strong>Slug canónico</strong> = places_of_interest.slug · nunca se modifica · lo usa el router |
            🟢 <strong>Slug ES trads</strong> = v2 limpio en places_of_interest_trads · para hreflang="es" en sitemap
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-success border-0">
        <i class="bi bi-check2-all fs-5"></i>
        <strong>¡Todo al día!</strong> Todos los lugares activos ya tienen traducciones en los 5 idiomas.
    </div>
    <?php endif; ?>

</div><!-- /container -->
</body>
</html>
<?php
} catch (PDOException $e) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Error — Rutas Rurales</title>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow border-0">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Error al generar traducciones</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-danger mb-2">
                <strong>PDOException:</strong> <?= htmlspecialchars($e->getMessage()) ?>
            </div>
            <p class="text-muted small mb-3">Código: <?= $e->getCode() ?> · Línea: <?= $e->getLine() ?></p>
            <a href="lugares_index.php" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Volver a Lugares
            </a>
        </div>
    </div>
</div>
</body>
</html>
<?php
}

