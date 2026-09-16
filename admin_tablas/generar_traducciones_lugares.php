<?php
/**
 * GENERAR TRADUCCIONES DE LUGARES DE INTERÉS
 * Ejecuta el script SQL para completar traducciones faltantes
 * Basado en generar_traducciones_eventos.php
 */

// 1. CONEXIÓN A LA BASE DE DATOS
include 'db.php';

header('Content-Type: text/html; charset=utf-8');

// 2. EJECUTAR EL SCRIPT SQL
$sql_script = "
-- ============================================
-- SCRIPT SQL: TRADUCCIONES DE LUGARES DE INTERÉS
-- Lugares con is_active=1
-- ============================================

-- 1. INGLÉS (en)
INSERT INTO places_of_interest_trads 
(place_id, language_code, name, slug, short_description, description, address, municipality, province, opening_hours, accessibility, meta_title, meta_description, entry_fee, entry_fee_details, facilities)
SELECT 
    id, 'en', name,
    CONCAT(slug, '-historic-monument-spain'),
    CONCAT('Discover ', name, ' in ', municipality, ', ', province, '. A fascinating landmark showcasing the rich cultural heritage and history of Spain.'),
    CONCAT('<section>
        <h3>About ', name, '</h3>
        <p>', COALESCE(short_description, ''), ' This remarkable site offers visitors a unique glimpse into the local history and traditions that have shaped the region over centuries.</p>
    </section>
    <section>
        <h3>What to See</h3>
        <ul>
            <li><strong>Architectural Heritage:</strong> ', COALESCE(description, ''), '</li>
            <li><strong>Local Culture:</strong> Immerse yourself in the traditions and customs of ', municipality, '</li>
            <li><strong>Scenic Surroundings:</strong> Enjoy the beautiful landscapes of ', province, ' province</li>
        </ul>
    </section>
    <section>
        <h3>Practical Information</h3>
        <p><strong>Location:</strong> ', municipality, ', ', province, ', Spain</p>
        <p><strong>Entry Fee:</strong> ', IF(entry_fee > 0, CONCAT('€', entry_fee), 'Free admission'), '</p>
    </section>'),
    address,
    municipality,
    province,
    opening_hours,
    'Wheelchair accessible, family-friendly, multilingual information available',
    CONCAT(name, ' | Historic Monument in Spain'),
    CONCAT('Visit ', name, ' in ', municipality, ', ', province, '. Discover one of Spain most fascinating landmarks with rich history, stunning architecture, and breathtaking views. Perfect for cultural tourism and history enthusiasts.'),
    entry_fee,
    entry_fee_details,
    facilities
FROM places_of_interest
WHERE is_active = 1 
    AND id NOT IN (SELECT place_id FROM places_of_interest_trads WHERE language_code = 'en');

-- 2. FRANCÉS (fr)
INSERT INTO places_of_interest_trads 
(place_id, language_code, name, slug, short_description, description, address, municipality, province, opening_hours, accessibility, meta_title, meta_description, entry_fee, entry_fee_details, facilities)
SELECT 
    id, 'fr', name,
    CONCAT(slug, '-monument-historique-espagne'),
    CONCAT('Découvrez ', name, ' à ', municipality, ', ', province, '. Un site fascinant mettant en valeur le patrimoine culturel et l\'histoire riche de l\'Espagne.'),
    CONCAT('<section>
        <h3>À propos de ', name, '</h3>
        <p>', COALESCE(short_description, ''), ' Ce site remarquable offre aux visiteurs un aperçu unique de l\'histoire et des traditions locales qui ont façonné la région au fil des siècles.</p>
    </section>
    <section>
        <h3>Que voir</h3>
        <ul>
            <li><strong>Patrimoine Architectural:</strong> ', COALESCE(description, ''), '</li>
            <li><strong>Culture Locale:</strong> Plongez dans les traditions de ', municipality, '</li>
            <li><strong>Paysages:</strong> Profitez des magnifiques paysages de la province de ', province, '</li>
        </ul>
    </section>
    <section>
        <h3>Informations Pratiques</h3>
        <p><strong>Localisation:</strong> ', municipality, ', ', province, ', Espagne</p>
        <p><strong>Entrée:</strong> ', IF(entry_fee > 0, CONCAT('€', entry_fee), 'Entrée gratuite'), '</p>
    </section>'),
    address,
    municipality,
    province,
    opening_hours,
    'Accessible aux fauteuils roulants, adapté aux familles, informations multilingues disponibles',
    CONCAT(name, ' | Monument Historique en Espagne'),
    CONCAT('Visitez ', name, ' à ', municipality, ', ', province, '. Découvrez l\'un des sites les plus fascinants d\'Espagne avec une histoire riche, une architecture stunning et des vues à couper le souffle. Parfait pour le tourisme culturel et les amateurs d\'histoire.'),
    entry_fee,
    entry_fee_details,
    facilities
FROM places_of_interest
WHERE is_active = 1 
    AND id NOT IN (SELECT place_id FROM places_of_interest_trads WHERE language_code = 'fr');

-- 3. ALEMÁN (de)
INSERT INTO places_of_interest_trads 
(place_id, language_code, name, slug, short_description, description, address, municipality, province, opening_hours, accessibility, meta_title, meta_description, entry_fee, entry_fee_details, facilities)
SELECT 
    id, 'de', name,
    CONCAT(slug, '-historische-sehenswurdigkeit-spanien'),
    CONCAT('Entdecken Sie ', name, ' in ', municipality, ', ', province, '. Eine faszinierende Sehenswürdigkeit, die das reiche Kulturerbe und die Geschichte Spaniens zeigt.'),
    CONCAT('<section>
        <h3>Über ', name, '</h3>
        <p>', COALESCE(short_description, ''), ' Diese bemerkenswerte Stätte bietet Besuchern einen einzigartigen Einblick in die lokale Geschichte und Traditionen, die die Region über Jahrhunderte geprägt haben.</p>
    </section>
    <section>
        <h3>Was es zu sehen gibt</h3>
        <ul>
            <li><strong>Architektonisches Erbe:</strong> ', COALESCE(description, ''), '</li>
            <li><strong>Lokale Kultur:</strong> Tauchen Sie ein in die Traditionen von ', municipality, '</li>
            <li><strong>Landschaft:</strong> Genießen Sie die schönen Landschaften der Provinz ', province, '</li>
        </ul>
    </section>
    <section>
        <h3>Praktische Informationen</h3>
        <p><strong>Standort:</strong> ', municipality, ', ', province, ', Spanien</p>
        <p><strong>Eintritt:</strong> ', IF(entry_fee > 0, CONCAT('€', entry_fee), 'Freier Eintritt'), '</p>
    </section>'),
    address,
    municipality,
    province,
    opening_hours,
    'Rollstuhlgerecht, familienfreundlich, mehrsprachige Informationen verfügbar',
    CONCAT(name, ' | Historische Sehenswürdigkeit in Spanien'),
    CONCAT('Besuchen Sie ', name, ' in ', municipality, ', ', province, '. Entdecken Sie eine der faszinierendsten Sehenswürdigkeiten Spaniens mit reicher Geschichte, atemberaubender Architektur und spektakulären Ausblicken. Perfekt für Kulturtourismus und Geschichtsinteressierte.'),
    entry_fee,
    entry_fee_details,
    facilities
FROM places_of_interest
WHERE is_active = 1 
    AND id NOT IN (SELECT place_id FROM places_of_interest_trads WHERE language_code = 'de');

-- 4. CHINO (zh)
INSERT INTO places_of_interest_trads 
(place_id, language_code, name, slug, short_description, description, address, municipality, province, opening_hours, accessibility, meta_title, meta_description, entry_fee, entry_fee_details, facilities)
SELECT 
    id, 'zh', name,
    CONCAT(slug, '-lishi-jinianwu-xibanya'),
    CONCAT('探索西班牙', municipality, '的', name, '。这里是展示西班牙丰富文化遗产和历史的迷人地标。'),
    CONCAT('<section>
        <h3>关于', name, '</h3>
        <p>', COALESCE(short_description, ''), '这个著名景点为游客提供了独特的视角，让他们了解几个世纪以来塑造该地区的当地历史和传统。</p>
    </section>
    <section>
        <h3>看点</h3>
        <ul>
            <li><strong>建筑遗产：</strong> ', COALESCE(description, ''), '</li>
            <li><strong>当地文化：</strong>沉浸在', municipality, '的传统中</li>
            <li><strong>风景：</strong>欣赏', province, '省的美丽风景</li>
        </ul>
    </section>
    <section>
        <h3>实用信息</h3>
        <p><strong>位置：</strong>西班牙', province, '省', municipality, '</p>
        <p><strong>门票：</strong> ', IF(entry_fee > 0, CONCAT('€', entry_fee), '免费入场'), '</p>
    </section>'),
    address,
    municipality,
    province,
    opening_hours,
    '轮椅通道, 适合家庭, 提供多语言信息',
    CONCAT(name, ' | 西班牙历史纪念物'),
    CONCAT('参观西班牙', province, '省', municipality, '的', name, '。发现西班牙最迷人的地标之一，拥有丰富的历史、令人惊叹的建筑和壮丽的景色。是文化旅游和历史爱好者的完美目的地。'),
    entry_fee,
    entry_fee_details,
    facilities
FROM places_of_interest
WHERE is_active = 1 
    AND id NOT IN (SELECT place_id FROM places_of_interest_trads WHERE language_code = 'zh');
";

try {
    // Ejecutar INSERTs
    $pdo->exec($sql_script);
    
    // Verificar resultados
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT p.id) AS total_lugares,
            SUM(CASE WHEN tr_en.place_id IS NOT NULL THEN 1 ELSE 0 END) AS lugares_con_en,
            SUM(CASE WHEN tr_fr.place_id IS NOT NULL THEN 1 ELSE 0 END) AS lugares_con_fr,
            SUM(CASE WHEN tr_de.place_id IS NOT NULL THEN 1 ELSE 0 END) AS lugares_con_de,
            SUM(CASE WHEN tr_zh.place_id IS NOT NULL THEN 1 ELSE 0 END) AS lugares_con_zh,
            SUM(CASE WHEN tr_en.place_id IS NOT NULL AND tr_fr.place_id IS NOT NULL AND tr_de.place_id IS NOT NULL AND tr_zh.place_id IS NOT NULL THEN 1 ELSE 0 END) AS lugares_completos
        FROM places_of_interest p
        LEFT JOIN places_of_interest_trads tr_en ON p.id = tr_en.place_id AND tr_en.language_code = 'en'
        LEFT JOIN places_of_interest_trads tr_fr ON p.id = tr_fr.place_id AND tr_fr.language_code = 'fr'
        LEFT JOIN places_of_interest_trads tr_de ON p.id = tr_de.place_id AND tr_de.language_code = 'de'
        LEFT JOIN places_of_interest_trads tr_zh ON p.id = tr_zh.place_id AND tr_zh.language_code = 'zh'
        WHERE p.is_active = 1
    ");
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Mostrar resultado
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <title>Traducciones Generadas - Lugares | Rutas Rurales</title>
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="card shadow border-0">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="bi bi-check-circle-fill"></i> Traducciones de Lugares generadas correctamente</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Métrica</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Total lugares activos</td><td><strong><?= $resumen['total_lugares'] ?></strong></td></tr>
                            <tr><td>Lugares con inglés (en)</td><td><strong><?= $resumen['lugares_con_en'] ?></strong></td></tr>
                            <tr><td>Lugares con francés (fr)</td><td><strong><?= $resumen['lugares_con_fr'] ?></strong></td></tr>
                            <tr><td>Lugares con alemán (de)</td><td><strong><?= $resumen['lugares_con_de'] ?></strong></td></tr>
                            <tr><td>Lugares con chino (zh)</td><td><strong><?= $resumen['lugares_con_zh'] ?></strong></td></tr>
                            <tr class="table-success">
                                <td><strong>Lugares COMPLETOS (4 idiomas)</strong></td>
                                <td><strong><?= $resumen['lugares_completos'] ?> / <?= $resumen['total_lugares'] ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Próximo paso:</strong> Ejecuta el generador de sitemap i18n para crear el sitemap de traducciones de lugares.
                    </div>
                    
                    <a href="lugares_index.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Volver a Lugares
                    </a>
                    <a href="../generar_sitemap_lugares_i18n.php" class="btn btn-warning">
                        <i class="bi bi-globe"></i> Generar Sitemap i18n
                    </a>
                </div>
            </div>
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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <title>Error - Rutas Rurales</title>
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="card shadow border-0">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Error al generar traducciones</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($e->getMessage()) ?>
                    </div>
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
