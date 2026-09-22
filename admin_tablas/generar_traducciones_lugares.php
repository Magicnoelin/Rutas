<?php
/**
 * GENERAR TRADUCCIONES DE LUGARES DE INTERÉS (INCREMENTAL)
 * ----------------------------------------------------------
 * Solo inserta traducciones que faltan (no borra las existentes).
 * Usa slug_lugares_helper.php para slugs correctos:
 *   {categoría_traducida}-{nombre_limpio}-{municipio}
 *
 * Ej EN: "Bodega AlmaRoja"|"Bodega"|"Fermoselle" → winery-almaroja-fermoselle
 * Ej FR: "Castillo de Almansa"|"Castillo"|"Almansa" → chateau-almansa
 */

include 'db.php';
require_once 'slug_lugares_helper.php';

header('Content-Type: text/html; charset=utf-8');

try {

$idiomas = [
    'en' => ['intro'=>'Discover','in'=>'in','h3a'=>'About','h3v'=>'What to See','acc'=>'Wheelchair accessible, family-friendly','msuf'=>'in Spain','mv'=>'Visit','mend'=>'Discover this remarkable place in Spain.'],
    'fr' => ['intro'=>'Découvrez','in'=>'à','h3a'=>'À propos de','h3v'=>'À voir','acc'=>'Accessible, adapté aux familles','msuf'=>'en Espagne','mv'=>'Visitez','mend'=>'Découvrez ce lieu remarquable en Espagne.'],
    'de' => ['intro'=>'Entdecken Sie','in'=>'in','h3a'=>'Über','h3v'=>'Sehenswürdigkeiten','acc'=>'Barrierefrei, familienfreundlich','msuf'=>'in Spanien','mv'=>'Besuchen Sie','mend'=>'Entdecken Sie diesen tollen Ort in Spanien.'],
    'zh' => ['intro'=>'探索','in'=>'在','h3a'=>'关于','h3v'=>'参观亮点','acc'=>'无障碍, 适合家庭','msuf'=>'西班牙','mv'=>'参观','mend'=>'发现西班牙的这个精彩景点。'],
];

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

$totalInsertados = 0;
$previsualizacion = [];

foreach ($idiomas as $lang => $t) {
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.municipality, p.province, p.address,
               p.short_description, p.description,
               p.opening_hours, p.entry_fee, p.entry_fee_details, p.facilities,
               COALESCE(c.name,'') AS categoria
        FROM places_of_interest p
        LEFT JOIN categories_places c ON p.category_id = c.id
        WHERE p.is_active = 1
          AND p.id NOT IN (SELECT place_id FROM places_of_interest_trads WHERE language_code = '$lang')
        ORDER BY p.id
    ");
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lugares as $lugar) {
        $slug = generarSlugLugar($lugar['name'], $lugar['categoria'], $lugar['municipality'], $lang);
        $shortDesc   = $t['intro'].' '.$lugar['name'].' '.$t['in'].' '.$lugar['municipality'].', '.$lugar['province'];
        $description = '<section><h3>'.$t['h3a'].' '.htmlspecialchars($lugar['name']).'</h3>'
                     . '<p>'.($lugar['short_description']??'').'</p></section>'
                     . '<section><h3>'.$t['h3v'].'</h3><p>'.($lugar['description']??'').'</p></section>';
        $catLabel    = !empty($lugar['categoria']) ? $lugar['categoria'] : 'Lugar';
        $metaTitle   = $lugar['name'].' | '.$catLabel.' '.$t['msuf'];
        $metaDesc    = $t['mv'].' '.$lugar['name'].' '.$t['in'].' '.$lugar['municipality'].', '.$lugar['province'].'. '.$t['mend'];

        $insert->execute([
            ':place_id'=>$lugar['id'], ':lang'=>$lang, ':name'=>$lugar['name'], ':slug'=>$slug,
            ':short_desc'=>$shortDesc, ':description'=>$description,
            ':address'=>$lugar['address'], ':municipality'=>$lugar['municipality'],
            ':province'=>$lugar['province'], ':opening_hours'=>$lugar['opening_hours'],
            ':accessibility'=>$t['acc'], ':meta_title'=>$metaTitle, ':meta_description'=>$metaDesc,
            ':entry_fee'=>$lugar['entry_fee'], ':entry_fee_details'=>$lugar['entry_fee_details'],
            ':facilities'=>$lugar['facilities'],
        ]);
        $totalInsertados++;

        if ($lang === 'en' && count($previsualizacion) < 10) {
            $previsualizacion[] = [
                'id'=>$lugar['id'], 'nombre'=>$lugar['name'], 'cat'=>$lugar['categoria'],
                'muni'=>$lugar['municipality'], 'slug_en'=>$slug,
                'slug_fr'=>generarSlugLugar($lugar['name'],$lugar['categoria'],$lugar['municipality'],'fr'),
                'slug_de'=>generarSlugLugar($lugar['name'],$lugar['categoria'],$lugar['municipality'],'de'),
            ];
        }
    }
}

$totalLugares     = $pdo->query("SELECT COUNT(*) FROM places_of_interest WHERE is_active=1")->fetchColumn();
$lugaresEn        = $pdo->query("SELECT COUNT(DISTINCT place_id) FROM places_of_interest_trads WHERE language_code='en'")->fetchColumn();
$lugaresFr        = $pdo->query("SELECT COUNT(DISTINCT place_id) FROM places_of_interest_trads WHERE language_code='fr'")->fetchColumn();
$lugaresDe        = $pdo->query("SELECT COUNT(DISTINCT place_id) FROM places_of_interest_trads WHERE language_code='de'")->fetchColumn();
$lugaresZh        = $pdo->query("SELECT COUNT(DISTINCT place_id) FROM places_of_interest_trads WHERE language_code='zh'")->fetchColumn();
$lugaresCompletos = $pdo->query("SELECT COUNT(*) FROM (SELECT place_id FROM places_of_interest_trads WHERE language_code IN ('en','fr','de','zh') GROUP BY place_id HAVING COUNT(DISTINCT language_code)=4) t")->fetchColumn();

// bloques FR y DE eliminados - ahora generados por PHP en el loop de $idiomas

// ZH y demás idiomas ya procesados arriba en el foreach de $idiomas
    
// Los conteos ya están en $totalLugares, $lugaresEn, etc. (calculados arriba)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Traducciones Generadas - Rutas Rurales</title>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow border-0 mb-4">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="bi bi-check-circle-fill"></i> Traducciones de Lugares generadas correctamente</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-dark"><tr><th>Métrica</th><th>Valor</th></tr></thead>
                <tbody>
                    <tr><td>Total lugares activos</td><td><strong><?= $totalLugares ?></strong></td></tr>
                    <tr><td>Nuevas traducciones insertadas</td><td><strong><?= $totalInsertados ?></strong></td></tr>
                    <tr><td>Lugares con inglés (en)</td><td><strong><?= $lugaresEn ?></strong></td></tr>
                    <tr><td>Lugares con francés (fr)</td><td><strong><?= $lugaresFr ?></strong></td></tr>
                    <tr><td>Lugares con alemán (de)</td><td><strong><?= $lugaresDe ?></strong></td></tr>
                    <tr><td>Lugares con chino (zh)</td><td><strong><?= $lugaresZh ?></strong></td></tr>
                    <tr class="table-success">
                        <td><strong>Lugares COMPLETOS (4 idiomas)</strong></td>
                        <td><strong><?= $lugaresCompletos ?> / <?= $totalLugares ?></strong></td>
                    </tr>
                </tbody>
            </table>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Formato de slug:</strong> <code>{categoría_traducida}-{nombre_limpio}-{municipio}</code>
                — sin sufijos "-spain"/"-espagne", con transliteración de caracteres no-ASCII.
            </div>
            <a href="lugares_index.php" class="btn btn-primary me-2">
                <i class="bi bi-arrow-left"></i> Volver a Lugares
            </a>
            <a href="../generar_sitemap_lugares_i18n.php" class="btn btn-warning">
                <i class="bi bi-globe"></i> Generar Sitemap i18n
            </a>
        </div>
    </div>
    <?php if (!empty($previsualizacion)): ?>
    <div class="card shadow border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-eye"></i> Previsualización — slugs EN recién generados</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-dark">
                    <tr><th>#</th><th>Nombre</th><th>Categoría</th><th>Municipio</th><th>Slug EN</th><th>Slug FR</th><th>Slug DE</th></tr>
                </thead>
                <tbody>
                <?php foreach ($previsualizacion as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars($p['cat']) ?></td>
                        <td><?= htmlspecialchars($p['muni']) ?></td>
                        <td><code><?= htmlspecialchars($p['slug_en']) ?></code></td>
                        <td><code><?= htmlspecialchars($p['slug_fr']) ?></code></td>
                        <td><code><?= htmlspecialchars($p['slug_de']) ?></code></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-success"><i class="bi bi-check2-all"></i> Todos los lugares ya tenían traducciones en todos los idiomas.</div>
    <?php endif; ?>
</div>
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
    <title>Error - Rutas Rurales</title>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow border-0">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Error al generar traducciones</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-danger"><?= htmlspecialchars($e->getMessage()) ?></div>
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
