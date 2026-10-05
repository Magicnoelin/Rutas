<?php
/**
 * REGENERAR TRADUCCIONES DE LUGARES DE INTERÉS — v3 i18n
 * ─────────────────────────────────────────────────────────
 * DESTRUYE y RECREA TODAS las traducciones en los 5 idiomas.
 * Nueva arquitectura:
 *  FASE 1 — 'es' desde places_of_interest (tabla maestra).
 *            Slug ES: generarSlugLugar(...,'es') — limpio v2.
 *            places_of_interest.slug (canónico router) NO se toca.
 *  FASE 2 — en/fr/de/zh leyendo textos desde la fila 'es' recién insertada.
 *            Datos estructurales: JOIN a places_of_interest + categories_places.
 *
 * Todo en TRANSACCIÓN ATÓMICA: si falla algo → rollback completo.
 *
 * Hreflang en detalle:
 *   hreflang="es"          → places_of_interest.slug  (canónico)
 *   hreflang="en|fr|de|zh" → places_of_interest_trads.slug (este script)
 */

include 'db.php';
require_once 'slug_lugares_helper.php';

header('Content-Type: text/html; charset=utf-8');
set_time_limit(0); // Sin límite: el script puede tardar varios minutos traduciendo todos los lugares
ini_set('max_execution_time', 0);

// ── FUNCIÓN DE TRADUCCIÓN VÍA GOOGLE TRANSLATE (sin API key) ─────────────
/**
 * Traduce texto de 'es' al idioma destino usando la API pública de Google Translate.
 * Divide textos largos en fragmentos para evitar límites de URL.
 * Retorna el texto original si la traducción falla.
 */
function traducirTexto(string $texto, string $targetLang): string {
    if (empty(trim($texto))) return $texto;
    // Google Translate soporta segmentos de hasta ~5000 chars
    $fragmentos = [];
    $partes = explode("\n\n", $texto);
    $buffer = '';
    foreach ($partes as $parte) {
        if (strlen($buffer) + strlen($parte) > 4500) {
            if ($buffer !== '') $fragmentos[] = $buffer;
            $buffer = $parte;
        } else {
            $buffer .= ($buffer !== '' ? "\n\n" : '') . $parte;
        }
    }
    if ($buffer !== '') $fragmentos[] = $buffer;

    $traducido = [];
    foreach ($fragmentos as $frag) {
        $url = 'https://translate.googleapis.com/translate_a/single'
             . '?client=gtx&sl=es&tl=' . urlencode($targetLang)
             . '&dt=t&q=' . urlencode($frag);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || !$resp) {
            $traducido[] = $frag; // fallback: original
            continue;
        }
        $data = json_decode($resp, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data[0])) {
            $traducido[] = $frag;
            continue;
        }
        $partesTrad = '';
        foreach ($data[0] as $segmento) {
            if (!empty($segmento[0])) $partesTrad .= $segmento[0];
        }
        $traducido[] = $partesTrad;
        usleep(150000); // 150ms entre peticiones para no ser bloqueado
    }
    return implode("\n\n", $traducido);
}

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

// ── TRANSACCIÓN ATÓMICA ───────────────────────────────────────────────────
$pdo->beginTransaction();

// 1. Borrar TODAS las traducciones existentes
$pdo->exec("DELETE FROM places_of_interest_trads");

// 2. Obtener todos los lugares activos con su categoría
$stmt = $pdo->query("
    SELECT p.id,
           p.name,
           p.slug          AS slug_original,
           p.municipality, p.province, p.address,
           p.short_description, p.description,
           p.opening_hours, p.entry_fee, p.entry_fee_details, p.facilities,
           COALESCE(c.name, '') AS categoria
    FROM   places_of_interest p
    LEFT JOIN categories_places c ON p.category_id = c.id
    WHERE  p.is_active = 1
    ORDER  BY p.id
");
$lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── PREPARED STATEMENT INSERT ─────────────────────────────────────────────
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
$previsualizacion = [];

// ══════════════════════════════════════════════════════════════════════════
// FASE 1 — Insertar idioma base 'es' desde tabla maestra
// ══════════════════════════════════════════════════════════════════════════
$t = $textos['es'];
foreach ($lugares as $lugar) {
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
// FASE 2 — Insertar idiomas secundarios leyendo desde la fila 'es' en trads
// ══════════════════════════════════════════════════════════════════════════
// Textos genéricos de fallback por idioma (cuando description real está vacía)
$fallbackIntro = [
    'en' => 'This remarkable location offers a unique experience for travelers seeking authentic rural tourism, full of charm and local tradition.',
    'fr' => "Ce lieu remarquable offre une expérience unique aux voyageurs à la recherche d'authenticité et de patrimoine rural.",
    'de' => 'Dieser bemerkenswerte Ort bietet Reisenden ein einzigartiges Erlebnis im ländlichen Spanien voller Tradition und Charme.',
    'zh' => '这个出色的景点为游客提供独特的旅行体验，充满西班牙乡村的传统与魅力。',
];
$fallbackWhatToSee = [
    'en' => 'Enjoy its surroundings, cultural heritage, and local environment.',
    'fr' => 'Profitez de ses environs, de son patrimoine culturel et de son environnement local.',
    'de' => 'Genießen Sie die Umgebung, das kulturelle Erbe und die lokale Natur.',
    'zh' => '尽情欣赏周边环境、文化遗产和当地风情。',
];

foreach (['en', 'fr', 'de', 'zh'] as $lang) {
    $t = $textos[$lang];
    $stmtLang = $pdo->prepare("
        SELECT pt_es.place_id AS id, pt_es.name, pt_es.municipality, pt_es.province, pt_es.address,
               pt_es.opening_hours,
               p.short_description AS short_original, p.description AS desc_original,
               p.entry_fee, p.entry_fee_details, p.facilities, COALESCE(c.name,'') AS categoria
        FROM   places_of_interest_trads pt_es
        INNER JOIN places_of_interest p ON p.id = pt_es.place_id
        LEFT  JOIN categories_places  c ON c.id = p.category_id
        WHERE  pt_es.language_code = 'es' AND p.is_active = 1
        ORDER  BY pt_es.place_id
    ");
    $stmtLang->execute();
    $lugaresLang = $stmtLang->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lugaresLang as $lugar) {
        $slug      = generarSlugLugar($lugar['name'], $lugar['categoria'], $lugar['municipality'], $lang);
        $catLabel  = !empty($lugar['categoria']) ? $lugar['categoria'] : 'Place';
        $shortDesc = $t['intro'].' '.$lugar['name'].' '.$t['in'].' '.$lugar['municipality'].', '.$lugar['province'].'.';

        // Obtener texto original en español (sin HTML)
        $shortOriginal = trim(strip_tags($lugar['short_original'] ?? ''));
        $descOriginal  = trim(strip_tags($lugar['desc_original']  ?? ''));

        // Traducir al idioma destino (o usar fallback si está vacío)
        if (!empty($shortOriginal)) {
            $introContent = htmlspecialchars(traducirTexto($shortOriginal, $lang));
        } else {
            $introContent = $fallbackIntro[$lang];
        }
        if (!empty($descOriginal)) {
            $bodyContent = htmlspecialchars(traducirTexto($descOriginal, $lang));
        } else {
            $bodyContent = $fallbackWhatToSee[$lang];
        }

        $desc = '<section><h3>'.$t['h3a'].' '.htmlspecialchars($lugar['name']).'</h3>'
              . '<p>'.$introContent.'</p></section>'
              . '<section><h3>'.$t['h3v'].'</h3><p>'.$bodyContent.'</p></section>';

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

        if (isset($previsualizacion[$lugar['id']]) && in_array($lang, ['en','fr','de'])) {
            $previsualizacion[$lugar['id']]['slug_'.$lang] = $slug;
        }
    }
}

// Confirmar transacción
$pdo->commit();

// bloque FR eliminado - ahora generado por PHP

// bloque DE eliminado - ahora generado por PHP

// bloque ZH eliminado - ahora generado por PHP

// ── MÉTRICAS FINALES ──────────────────────────────────────────────────────
$cuentas = $pdo->query("
    SELECT language_code, COUNT(*) AS total
    FROM   places_of_interest_trads GROUP BY language_code
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
        GROUP  BY place_id HAVING COUNT(DISTINCT language_code) = 5
    ) t
")->fetchColumn();

function colorFila(int $val, int $total): string {
    if ($val === $total) return 'table-success';
    if ($val > 0)        return 'table-warning';
    return 'table-danger';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Traducciones REGENERADAS — Rutas Rurales</title>
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:1100px">
    <h2 class="mb-1"><i class="bi bi-arrow-repeat"></i> Traducciones REGENERADAS <small class="badge bg-danger fs-6">Full reset</small></h2>
    <p class="text-muted mb-3">Arquitectura i18n v3 · Transacción atómica · 5 idiomas</p>
    <div class="alert alert-success border-0"><i class="bi bi-check-circle-fill"></i> <strong><?= $totalInsertados ?></strong> registros insertados (<?= $totalLugares ?> lugares × 5 idiomas).</div>
    <!-- TARJETAS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="card shadow-sm border-0 text-center h-100"><div class="card-body py-3">
            <div class="display-6 fw-bold text-primary"><?= $totalInsertados ?></div><div class="small text-muted">Total insertados</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card shadow-sm border-0 text-center h-100"><div class="card-body py-3">
            <div class="display-6 fw-bold text-info"><?= $totalLugares ?></div><div class="small text-muted">Lugares activos</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card shadow-sm border-0 text-center h-100"><div class="card-body py-3">
            <div class="display-6 fw-bold text-warning">5</div><div class="small text-muted">Idiomas</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card shadow-sm border-0 text-center h-100"><div class="card-body py-3">
            <div class="display-6 fw-bold <?= ($lugaresCompletos === $totalLugares) ? 'text-success' : 'text-warning' ?>"><?= $lugaresCompletos ?>/<?= $totalLugares ?></div>
            <div class="small text-muted">Completos (5/5)</div>
        </div></div></div>
    </div>
    <!-- TABLA IDIOMAS -->
    <div class="card shadow border-0 mb-4">
        <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="bi bi-table"></i> Conteo por idioma</h5></div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-dark"><tr><th>Idioma</th><th>Flag</th><th>Registros</th><th>Fuente textos</th><th>Est.</th></tr></thead>
                <tbody>
                    <tr class="<?= colorFila($lugaresEs, $totalLugares) ?>">
                        <td><strong>Español (es) — BASE</strong></td><td>🇪🇸</td><td><strong><?= $lugaresEs ?></strong></td>
                        <td><code>places_of_interest</code> (tabla maestra)</td><td><?= ($lugaresEs === $totalLugares) ? '✅' : '⚠️' ?></td>
                    </tr>
                    <?php foreach (['en'=>['🇬🇧','Inglés'],'fr'=>['🇫🇷','Francés'],'de'=>['🇩🇪','Alemán'],'zh'=>['🇨🇳','Chino']] as $code=>[$flag,$nombre]):
                        $cnt = (int)($cuentas[$code] ?? 0); ?>
                    <tr class="<?= colorFila($cnt, $totalLugares) ?>">
                        <td><?= $nombre ?> (<?= $code ?>)</td><td><?= $flag ?></td><td><strong><?= $cnt ?></strong></td>
                        <td><code>places_of_interest_trads</code> lang='es'</td><td><?= ($cnt === $totalLugares) ? '✅' : '⚠️' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-primary fw-bold"><td colspan="2">COMPLETOS (5/5)</td><td colspan="3"><?= $lugaresCompletos ?> / <?= $totalLugares ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="alert alert-info mb-4">
        <h6><i class="bi bi-info-circle-fill"></i> Arquitectura hreflang</h6>
        <ul class="mb-0 small">
            <li><code>hreflang="es"</code> → <strong>places_of_interest.slug</strong> (canónico router)</li>
            <li><code>hreflang="x-default"</code> → mismo que ES</li>
            <li><code>hreflang="en|fr|de|zh"</code> → <strong>places_of_interest_trads.slug</strong></li>
        </ul>
    </div>
    <div class="mb-4">
        <a href="lugares_index.php" class="btn btn-primary me-2"><i class="bi bi-arrow-left"></i> Volver</a>
        <a href="../generar_sitemap_lugares_i18n.php" class="btn btn-warning me-2"><i class="bi bi-globe"></i> Sitemap i18n</a>
        <a href="generar_traducciones_lugares.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Modo incremental</a>
    </div>

    <!-- PREVISUALIZACIÓN -->
    <?php if (!empty($previsualizacion)): ?>
    <div class="card shadow border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-eye"></i> Previsualización — primeros <?= count($previsualizacion) ?> slugs</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table table-sm table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr><th>#</th><th>Nombre</th><th>Cat.</th><th>Municipio</th>
                        <th>🔴 Slug canónico</th><th>🟢 Slug ES v2</th>
                        <th>Slug EN</th><th>Slug FR</th><th>Slug DE</th></tr>
                </thead>
                <tbody>
                <?php foreach ($previsualizacion as $p): ?>
                    <tr>
                        <td class="small text-muted"><?= $p['id'] ?></td>
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
            🔴 Slug canónico = places_of_interest.slug (router, intacto) |
            🟢 Slug ES v2 = places_of_interest_trads (hreflang)
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
<?php
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
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
            <h4 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Error — ROLLBACK ejecutado</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-danger mb-2">
                <strong>PDOException:</strong> <?= htmlspecialchars($e->getMessage()) ?>
            </div>
            <p class="small text-muted mb-2">Código: <?= $e->getCode() ?> · Línea: <?= $e->getLine() ?></p>
            <div class="alert alert-info small">
                <i class="bi bi-shield-check"></i>
                Transacción revertida: los datos anteriores en <code>places_of_interest_trads</code> permanecen intactos.
            </div>
            <a href="lugares_index.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Volver a Lugares</a>
        </div>
    </div>
</div>
</body>
</html>
<?php
}
