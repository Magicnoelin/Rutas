-- ============================================
-- SCRIPT SQL SIMPLIFICADO PARA ACTUALIZAR TRADUCCIONES
-- Basado en generar_traducciones_eventos_futuros.sql
-- ============================================

-- 1. PRIMERO VERIFICAR EL ESTADO ACTUAL
SELECT 
    'ESTADO ACTUAL DE TRADUCCIONES' as seccion,
    COUNT(DISTINCT ce.id) as total_eventos_activos,
    SUM(CASE WHEN cet_en.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_con_en,
    SUM(CASE WHEN cet_fr.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_con_fr,
    SUM(CASE WHEN cet_de.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_con_de,
    SUM(CASE WHEN cet_zh.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_con_zh,
    SUM(CASE WHEN cet_en.event_id IS NOT NULL AND cet_fr.event_id IS NOT NULL 
              AND cet_de.event_id IS NOT NULL AND cet_zh.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_completos
FROM cultural_events ce
LEFT JOIN cultural_events_trads cet_en ON ce.id = cet_en.event_id AND cet_en.language_code = 'en'
LEFT JOIN cultural_events_trads cet_fr ON ce.id = cet_fr.event_id AND cet_fr.language_code = 'fr'
LEFT JOIN cultural_events_trads cet_de ON ce.id = cet_de.event_id AND cet_de.language_code = 'de'
LEFT JOIN cultural_events_trads cet_zh ON ce.id = cet_zh.event_id AND cet_zh.language_code = 'zh'
WHERE ce.is_active = 1 
    AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01');

-- 2. INSERTAR TRADUCCIONES FALTANTES PARA INGLÉS
INSERT INTO cultural_events_trads 
(event_id, language_code, name, slug, short_description, description, program, target_audience, accessibility, meta_title, meta_description)
SELECT 
    id,
    'en',
    name,
    CONCAT(slug, '-traditional-festival-spain'),
    CONCAT('Traditional festival in ', venue_name, ', ', province, ' featuring local culture, music, and traditions.'),
    CONCAT('<p>The ', name, ' is one of the most important traditional festivals in ', province, ', Spain. This annual celebration brings together locals and visitors to experience authentic Spanish culture.</p>
<p>Highlights include:</p>
<ul>
<li>Traditional music and dance performances</li>
<li>Local gastronomy and food stalls</li>
<li>Cultural exhibitions and workshops</li>
<li>Family-friendly activities</li>
<li>Religious processions (if applicable)</li>
</ul>
<p>Dates: ', start_date, IF(end_date IS NOT NULL, CONCAT(' to ', end_date), ''), '</p>
<p>Location: ', venue_name, ', ', province, ', Spain</p>'),
    'Daily schedule includes morning activities, afternoon cultural events, and evening celebrations with music and traditional performances.',
    'International tourists, culture enthusiasts, families',
    'Wheelchair accessible, family-friendly, multilingual information available',
    CONCAT(name, ' | Traditional Festival in Spain'),
    CONCAT('Experience the ', name, ' in ', venue_name, ', ', province, '. Traditional Spanish festival with cultural activities, local food, and authentic celebrations. Perfect for international tourists.')
FROM cultural_events
WHERE is_active = 1 
    AND (start_date >= '2026-04-01' OR end_date >= '2026-04-01')
    AND id NOT IN (SELECT event_id FROM cultural_events_trads WHERE language_code = 'en');

-- 3. INSERTAR TRADUCCIONES FALTANTES PARA FRANCÉS
INSERT INTO cultural_events_trads 
(event_id, language_code, name, slug, short_description, description, program, target_audience, accessibility, meta_title, meta_description)
SELECT 
    id,
    'fr',
    name,
    CONCAT(slug, '-fete-traditionnelle-espagne'),
    CONCAT('Fête traditionnelle à ', venue_name, ', ', province, ' mettant en valeur la culture locale, la música y las tradiciones.'),
    CONCAT('<p>Le ', name, ' est l\'une des fêtes traditionnelles les plus importantes de ', province, ', Espagne. Cette célébration annuelle réunit habitants et visiteurs pour vivre une expérience authentique de la culture espagnole.</p>
<p>Points forts :</p>
<ul>
<li>Spectacles de musique et danse traditionnelles</li>
<li>Gastronomie locale et stands de nourriture</li>
<li>Expositions et ateliers culturels</li>
<li>Activités familiales</li>
<li>Processions religieuses (le cas échéant)</li>
</ul>
<p>Dates : ', start_date, IF(end_date IS NOT NULL, CONCAT(' au ', end_date), ''), '</p>
<p>Lieu : ', venue_name, ', ', province, ', Espagne</p>'),
    'Programme quotidien incluant activités matinales, événements culturels l''après-midi et célébrations nocturnes avec musique et spectacles traditionnels.',
    'Touristes internationaux, amateurs de culture, familles',
    'Accessible aux fauteuils roulants, adapté aux familles, informations multilingues disponibles',
    CONCAT(name, ' | Fête Traditionnelle en Espagne'),
    CONCAT('Vivez le ', name, ' à ', venue_name, ', ', province, '. Fête traditionnelle espagnole avec activités culturelles, nourriture locale et célébrations authentiques. Parfait pour les touristes internationaux.')
FROM cultural_events
WHERE is_active = 1 
    AND (start_date >= '2026-04-01' OR end_date >= '2026-04-01')
    AND id NOT IN (SELECT event_id FROM cultural_events_trads WHERE language_code = 'fr');

-- 4. INSERTAR TRADUCCIONES FALTANTES PARA ALEMÁN
INSERT INTO cultural_events_trads 
(event_id, language_code, name, slug, short_description, description, program, target_audience, accessibility, meta_title, meta_description)
SELECT 
    id,
    'de',
    name,
    CONCAT(slug, '-traditionelles-fest-spanien'),
    CONCAT('Traditionelles Fest in ', venue_name, ', ', province, ' mit lokaler Kultur, Musik und Traditionen.'),
    CONCAT('<p>Das ', name, ' ist eines der wichtigsten traditionellen Feste in ', province, ', Spanien. Esta celebración anual reúne a locales y visitantes para experimentar la auténtica cultura española.</p>
<p>Puntos destacados:</p>
<ul>
<li>Espectáculos de música y danza tradicionales</li>
<li>Gastronomía local y puestos de comida</li>
<li>Exposiciones y talleres culturales</li>
<li>Actividades familiares</li>
<li>Procesiones religiosas (si aplica)</li>
</ul>
<p>Fechas: ', start_date, IF(end_date IS NOT NULL, CONCAT(' hasta ', end_date), ''), '</p>
<p>Ubicación: ', venue_name, ', ', province, ', España</p>'),
    'Programa diario incluye actividades matutinas, eventos culturales por la tarde y celebraciones nocturnas con música y espectáculos tradicionales.',
    'Turistas internacionales, amantes de la cultura, familias',
    'Accesible en silla de ruedas, familiar, información multilingüe disponible',
    CONCAT(name, ' | Fiesta Tradicional en España'),
    CONCAT('Vive el ', name, ' en ', venue_name, ', ', province, '. Fiesta tradicional española con actividades culturales, comida local y celebraciones auténticas. Perfecto para turistas internacionales.')
FROM cultural_events
WHERE is_active = 1 
    AND (start_date >= '2026-04-01' OR end_date >= '2026-04-01')
    AND id NOT IN (SELECT event_id FROM cultural_events_trads WHERE language_code = 'de');

-- 5. INSERTAR TRADUCCIONES FALTANTES PARA CHINO
INSERT INTO cultural_events_trads 
(event_id, language_code, name, slug, short_description, description, program, target_audience, accessibility, meta_title, meta_description)
SELECT 
    id,
    'zh',
    name,
    CONCAT(slug, '-chuantongjieri-xibanya'),
    CONCAT('西班牙', province, ' ', venue_name, '的传统节日，展示当地文化、音乐和传统。'),
    CONCAT('<p>', name, '是西班牙', province, '最重要的传统节日之一。这个年度庆典汇聚了当地居民和游客，共同体验地道的西班牙文化。</p>
<p>亮点包括：</p>
<ul>
<li>传统音乐和舞蹈表演</li>
<li>当地美食和小吃摊</li>
<li>文化展览和工作坊</li>
<li>适合家庭的活动</li>
<li>宗教游行（如适用）</li>
</ul>
<p>日期：', start_date, IF(end_date IS NOT NULL, CONCAT(' 至 ', end_date), ''), '</p>
<p>地点：西班牙', province, ' ', venue_name, '</p>'),
    '每日行程包括上午活动、下午文化活动和晚间庆祝活动，配有音乐和传统表演。',
    '国际游客, 文化爱好者, 家庭',
    '轮椅通道, 适合家庭, 提供多语言信息',
    CONCAT(name, ' | 西班牙传统节日'),
    CONCAT('体验西班牙', province, ' ', venue_name, '的', name, '。西班牙传统节日，包含文化活动、当地美食和地道庆祝。非常适合国际游客。')
FROM cultural_events
WHERE is_active = 1 
    AND (start_date >= '2026-04-01' OR end_date >= '2026-04-01')
    AND id NOT IN (SELECT event_id FROM cultural_events_trads WHERE language_code = 'zh');

-- 6. VERIFICACIÓN FINAL
SELECT 
    'RESUMEN FINAL DESPUÉS DE ACTUALIZAR' as seccion,
    COUNT(DISTINCT ce.id) as total_eventos_activos,
    SUM(CASE WHEN cet_en.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_con_en,
    SUM(CASE WHEN cet_fr.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_con_fr,
    SUM(CASE WHEN cet_de.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_con_de,
    SUM(CASE WHEN cet_zh.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_con_zh,
    SUM(CASE WHEN cet_en.event_id IS NOT NULL AND cet_fr.event_id IS NOT NULL 
              AND cet_de.event_id IS NOT NULL AND cet_zh.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_completos
FROM cultural_events ce
LEFT JOIN cultural_events_trads cet_en ON ce.id = cet_en.event_id AND cet_en.language_code = 'en'
LEFT JOIN cultural_events_trads cet_fr ON ce.id = cet_fr.event_id AND cet_fr.language_code = 'fr'
LEFT JOIN cultural_events_trads cet_de ON ce.id = cet_de.event_id AND cet_de.language_code = 'de'
LEFT JOIN cultural_events_trads cet_zh ON ce.id = cet_zh.event_id AND cet_zh.language_code = 'zh'
WHERE ce.is_active = 1 
    AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01');

-- 7. DETALLE POR EVENTO
SELECT 
    ce.id as event_id,
    ce.name as nombre_evento,
    ce.start_date as fecha_inicio,
    CASE WHEN cet_en.event_id IS NOT NULL THEN '✓' ELSE '✗' END as ingles,
    CASE WHEN cet_fr.event_id IS NOT NULL THEN '✓' ELSE '✗' END as frances,
    CASE WHEN cet_de.event_id IS NOT NULL THEN '✓' ELSE '✗' END as aleman,
    CASE WHEN cet_zh.event_id IS NOT NULL THEN '✓' ELSE '✗' END as chino,
    CASE 
        WHEN cet_en.event_id IS NOT NULL AND cet_fr.event_id IS NOT NULL 
             AND cet_de.event_id IS NOT NULL AND cet_zh.event_id IS NOT NULL 
        THEN 'COMPLETO' 
        ELSE 'INCOMPLETO' 
    END as estado
FROM cultural_events ce
LEFT JOIN cultural_events_trads cet_en ON ce.id = cet_en.event_id AND cet_en.language_code = 'en'
LEFT JOIN cultural_events_trads cet_fr ON ce.id = cet_fr.event_id AND cet_fr.language_code = 'fr'
LEFT JOIN cultural_events_trads cet_de ON ce.id = cet_de.event_id AND cet_de.language_code = 'de'
LEFT JOIN cultural_events_trads cet_zh ON ce.id = cet_zh.event_id AND cet_zh.language_code = 'zh'
WHERE ce.is_active = 1 
    AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01')
ORDER BY ce.start_date ASC;