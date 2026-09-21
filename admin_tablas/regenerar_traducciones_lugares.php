<?php
/**
 * REGENERAR TRADUCCIONES DE LUGARES DE INTERÉS
 * ESTE SCRIPT REEMPLAZA TODAS LAS TRADUCCIONES EXISTENTES
 * Usa la categoría del lugar para crear el slug traducido
 */

// 1. CONEXIÓN A LA BASE DE DATOS
include 'db.php';

header('Content-Type: text/html; charset=utf-8');

// Primero borramos todas las traducciones existentes
$pdo->exec("DELETE FROM places_of_interest_trads");

// 2. EJECUTAR EL SCRIPT SQL
$sql_script = "
-- ============================================
-- TRADUCCIONES DE LUGARES DE INTERÉS (REGENERADAS)
-- Usa la categoría del lugar para el slug traducido
-- ============================================

-- 1. INGLÉS (en)
INSERT INTO places_of_interest_trads
(place_id, language_code, name, slug, short_description, description, address, municipality, province, opening_hours, accessibility, meta_title, meta_description, entry_fee, entry_fee_details, facilities)
SELECT
    p.id, 'en', 
    p.name,
    CASE 
        WHEN c.name LIKE '%Monumento%' OR c.name LIKE '%Catedral%' OR c.name LIKE '%Iglesia%' OR c.name LIKE '%Castillo%' OR c.name LIKE '%Palacio%' THEN CONCAT(p.slug, '-historic-monument-spain')
        WHEN c.name LIKE '%Museo%' THEN CONCAT(p.slug, '-museum-spain')
        WHEN c.name LIKE '%Playa%' THEN CONCAT(p.slug, '-beach-spain')
        WHEN c.name LIKE '%Parque%' OR c.name LIKE '%Natural%' THEN CONCAT(p.slug, '-nature-reserve-spain')
        WHEN c.name LIKE '%Restaurante%' OR c.name LIKE '%Bodega%' OR c.name LIKE '%Bar%' THEN CONCAT(p.slug, '-restaurant-spain')
        WHEN c.name LIKE '%Mirador%' THEN CONCAT(p.slug, '-viewpoint-spain')
        ELSE CONCAT(p.slug, '-place-spain')
    END,
    CONCAT('Discover ', p.name, ' in ', p.municipality, ', ', p.province),
    CONCAT('<section><h3>About ', p.name, '</h3><p>', COALESCE(p.short_description, ''), '</p></section><section><h3>What to See</h3><ul><li>', COALESCE(p.description, ''), '</li></ul></section>'),
    p.address, p.municipality, p.province, p.opening_hours,
    'Wheelchair accessible, family-friendly',
    CONCAT(p.name, ' | ', COALESCE(c.name, 'Place'), ' in Spain'),
    CONCAT('Visit ', p.name, ' in ', p.municipality, ', ', p.province, '. Discover this amazing place in Spain.'),
    p.entry_fee, p.entry_fee_details, p.facilities
FROM places_of_interest p
LEFT JOIN categories_places c ON p.category_id = c.id
WHERE p.is_active = 1;

-- 2. FRANCÉS (fr)
INSERT INTO places_of_interest_trads
(place_id, language_code, name, slug, short_description, description, address, municipality, province, opening_hours, accessibility, meta_title, meta_description, entry_fee, entry_fee_details, facilities)
SELECT
    p.id, 'fr', 
    p.name,
    CASE 
        WHEN c.name LIKE '%Monumento%' OR c.name LIKE '%Catedral%' OR c.name LIKE '%Iglesia%' OR c.name LIKE '%Castillo%' OR c.name LIKE '%Palacio%' THEN CONCAT(p.slug, '-monument-historique-espagne')
        WHEN c.name LIKE '%Museo%' THEN CONCAT(p.slug, '-musee-espagne')
        WHEN c.name LIKE '%Playa%' THEN CONCAT(p.slug, '-plage-espagne')
        WHEN c.name LIKE '%Parque%' OR c.name LIKE '%Natural%' THEN CONCAT(p.slug, '-reserve-naturelle-espagne')
        WHEN c.name LIKE '%Restaurante%' OR c.name LIKE '%Bodega%' OR c.name LIKE '%Bar%' THEN CONCAT(p.slug, '-restaurant-espagne')
        WHEN c.name LIKE '%Mirador%' THEN CONCAT(p.slug, '-point-vue-espagne')
        ELSE CONCAT(p.slug, '-lieu-espagne')
    END,
    CONCAT('Découvrez ', p.name, ' à ', p.municipality, ', ', p.province),
    CONCAT('<section><h3>À propos de ', p.name, '</h3><p>', COALESCE(p.short_description, ''), '</p></section>'),
    p.address, p.municipality, p.province, p.opening_hours,
    'Accessible, adapté aux familles',
    CONCAT(p.name, ' | ', COALESCE(c.name, 'Lieu'), ' en Espagne'),
    CONCAT('Visitez ', p.name, ' à ', p.municipality, ', ', p.province, '. Découvrez ce lieu remarquable en Espagne.'),
    p.entry_fee, p.entry_fee_details, p.facilities
FROM places_of_interest p
LEFT JOIN categories_places c ON p.category_id = c.id
WHERE p.is_active = 1;

-- 3. ALEMÁN (de)
INSERT INTO places_of_interest_trads
(place_id, language_code, name, slug, short_description, description, address, municipality, province, opening_hours, accessibility, meta_title, meta_description, entry_fee, entry_fee_details, facilities)
SELECT
    p.id, 'de', 
    p.name,
    CASE 
        WHEN c.name LIKE '%Monumento%' OR c.name LIKE '%Catedral%' OR c.name LIKE '%Iglesia%' OR c.name LIKE '%Castillo%' OR c.name LIKE '%Palacio%' THEN CONCAT(p.slug, '-historische-sehenswurdigkeit-spanien')
        WHEN c.name LIKE '%Museo%' THEN CONCAT(p.slug, '-museum-spanien')
        WHEN c.name LIKE '%Playa%' THEN CONCAT(p.slug, '-strand-spanien')
        WHEN c.name LIKE '%Parque%' OR c.name LIKE '%Natural%' THEN CONCAT(p.slug, '-naturpark-spanien')
        WHEN c.name LIKE '%Restaurante%' OR c.name LIKE '%Bodega%' OR c.name LIKE '%Bar%' THEN CONCAT(p.slug, '-restaurant-spanien')
        WHEN c.name LIKE '%Mirador%' THEN CONCAT(p.slug, '-aussichtspunkt-spanien')
        ELSE CONCAT(p.slug, '-ort-spanien')
    END,
    CONCAT('Entdecken Sie ', p.name, ' in ', p.municipality, ', ', p.province),
    CONCAT('<section><h3>Über ', p.name, '</h3><p>', COALESCE(p.short_description, ''), '</p></section>'),
    p.address, p.municipality, p.province, p.opening_hours,
    'Barrierefrei, familienfreundlich',
    CONCAT(p.name, ' | ', COALESCE(c.name, 'Ort'), ' in Spanien'),
    CONCAT('Besuchen Sie ', p.name, ' in ', p.municipality, ', ', p.province, '. Entdecken Sie diesen tollen Ort in Spanien.'),
    p.entry_fee, p.entry_fee_details, p.facilities
FROM places_of_interest p
LEFT JOIN categories_places c ON p.category_id = c.id
WHERE p.is_active = 1;

-- 4. CHINO (zh)
INSERT INTO places_of_interest_trads
(place_id, language_code, name, slug, short_description, description, address, municipality, province, opening_hours, accessibility, meta_title, meta_description, entry_fee, entry_fee_details, facilities)
SELECT
    p.id, 'zh', 
    p.name,
    CASE 
        WHEN c.name LIKE '%Monumento%' OR c.name LIKE '%Catedral%' OR c.name LIKE '%Iglesia%' OR c.name LIKE '%Castillo%' OR c.name LIKE '%Palacio%' THEN CONCAT(p.slug, '-lishi-jinianwu-xibanya')
        WHEN c.name LIKE '%Museo%' THEN CONCAT(p.slug, '-bowuguan-xibanya')
        WHEN c.name LIKE '%Playa%' THEN CONCAT(p.slug, '-haitan-xibanya')
        WHEN c.name LIKE '%Parque%' OR c.name LIKE '%Natural%' THEN CONCAT(p.slug, '-ziranguanbaohuqu-xibanya')
        WHEN c.name LIKE '%Restaurante%' OR c.name LIKE '%Bodega%' OR c.name LIKE '%Bar%' THEN CONCAT(p.slug, '-canting-xibanya')
        WHEN c.name LIKE '%Mirador%' THEN CONCAT(p.slug, '-guanjing-xibanya')
        ELSE CONCAT(p.slug, '-difang-xibanya')
    END,
    CONCAT('探索 ', p.name, ' 在 ', p.municipality, ', ', p.province),
    CONCAT('<section><h3>关于', p.name, '</h3><p>', COALESCE(p.short_description, ''), '</p></section>'),
    p.address, p.municipality, p.province, p.opening_hours,
    '无障碍, 适合家庭',
    CONCAT(p.name, ' | 西班牙', COALESCE(c.name, '景点')),
    CONCAT('参观 ', p.name, ' 在 ', p.municipality, ', ', p.province, '. 发现西班牙的这个精彩景点。'),
    p.entry_fee, p.entry_fee_details, p.facilities
FROM places_of_interest p
LEFT JOIN categories_places c ON p.category_id = c.id
WHERE p.is_active = 1;
";

try {
    // Ejecutar el script SQL
    $pdo->exec($sql_script);
    
    // Contar resultados
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM places_of_interest WHERE is_active = 1");
    $totalLugares = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT place_id) as total FROM places_of_interest_trads WHERE language_code = 'en'");
    $lugaresEn = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT place_id) as total FROM places_of_interest_trads WHERE language_code = 'fr'");
    $lugaresFr = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT place_id) as total FROM places_of_interest_trads WHERE language_code = 'de'");
    $lugaresDe = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT place_id) as total FROM places_of_interest_trads WHERE language_code = 'zh'");
    $lugaresZh = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    ?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Traducciones Regeneradas - Rutas Rurales</title>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow border-0">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">✅ Traducciones de Lugares REGENERADAS correctamente</h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr><td>Total lugares activos</td><td><strong><?= $totalLugares ?></strong></td></tr>
                    <tr><td>Lugares con inglés (en)</td><td><strong><?= $lugaresEn ?></strong></td></tr>
                    <tr><td>Lugares con francés (fr)</td><td><strong><?= $lugaresFr ?></strong></td></tr>
                    <tr><td>Lugares con alemán (de)</td><td><strong><?= $lugaresDe ?></strong></td></tr>
                    <tr><td>Lugares con chino (zh)</td><td><strong><?= $lugaresZh ?></strong></td></tr>
                </table>
                <a href="lugares_index.php" class="btn btn-primary">← Volver a Lugares</a>
                <a href="../generar_sitemap_lugares_i18n.php" class="btn btn-warning">Generar Sitemap</a>
            </div>
        </div>
    </div>
</body>
</html>
    <?php
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
