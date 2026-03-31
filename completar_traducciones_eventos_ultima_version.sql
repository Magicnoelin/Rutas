-- ============================================
-- SCRIPT SQL FINAL: COMPLETAR TRADUCCIONES DE EVENTOS CULTURALES
-- Eventos con is_active=1 y fechas posteriores al 1 de abril
-- Slugs orientados al turismo extranjero
-- ============================================

-- 1. PRIMERO: IDENTIFICAR EVENTOS QUE NECESITAN TRADUCCIONES
-- ============================================
SELECT 
    ce.id as event_id,
    ce.name as event_name,
    ce.slug as original_slug,
    ce.venue_name as ubicacion,
    ce.municipality as localidad,
    ce.province as provincia,
    ce.start_date,
    ce.end_date,
    GROUP_CONCAT(DISTINCT cet.language_code ORDER BY cet.language_code) as traducciones_existentes,
    COUNT(DISTINCT cet.language_code) as conteo_traducciones
FROM cultural_events ce
LEFT JOIN cultural_events_trads cet ON ce.id = cet.event_id
WHERE ce.is_active = 1 
    AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01')
GROUP BY ce.id
ORDER BY ce.start_date ASC;

-- 2. SEGUNDO: CREAR TRADUCCIONES FALTANTES PARA CADA IDIOMA
-- ============================================

-- 2.1 INGLÉS (en) - Traditional Festival in Spain
-- ============================================
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

-- 2.2 FRANCÉS (fr) - Fête Traditionnelle en Espagne
-- ============================================
INSERT INTO cultural_events_trads 
(event_id, language_code, name, slug, short_description, description, program, target_audience, accessibility, meta_title, meta_description)
SELECT 
    id,
    'fr',
    name,
    CONCAT(slug, '-fete-traditionnelle-espagne'),
    CONCAT('Fête traditionnelle à ', venue_name, ', ', province, ' mettant en valeur la culture locale, la musique et les traditions.'),
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

-- 2.3 ALEMÁN (de) - Traditionelles Fest in Spanien
-- ============================================
INSERT INTO cultural_events_trads 
(event_id, language_code, name, slug, short_description, description, program, target_audience, accessibility, meta_title, meta_description)
SELECT 
    id,
    'de',
    name,
    CONCAT(slug, '-traditionelles-fest-spanien'),
    CONCAT('Traditionelles Fest in ', venue_name, ', ', province, ' mit lokaler Kultur, Musik und Traditionen.'),
    CONCAT('<p>Das ', name, ' ist eines der wichtigsten traditionellen Feste in ', province, ', Spanien. Diese jährliche Feier bringt Einheimische und Besucher zusammen, um authentische spanische Kultur zu erleben.</p>
<p>Höhepunkte:</p>
<ul>
<li>Traditionelle Musik- und Tanzvorführungen</li>
<li>Lokale Gastronomie und Essensstände</li>
<li>Kulturausstellungen und Workshops</li>
<li>Familienfreundliche Aktivitäten</li>
<li>Religiöse Prozessionen (falls zutreffend)</li>
</ul>
<p>Daten: ', start_date, IF(end_date IS NOT NULL, CONCAT(' bis ', end_date), ''), '</p>
<p>Ort: ', venue_name, ', ', province, ', Spanien</p>'),
    'Tagesprogramm beinhaltet morgendliche Aktivitäten, nachmittägliche Kulturveranstaltungen und abendliche Feiern mit Musik und traditionellen Darbietungen.',
    'Internationale Touristen, Kulturliebhaber, Familien',
    'Rollstuhlgerecht, familienfreundlich, mehrsprachige Informationen verfügbar',
    CONCAT(name, ' | Traditionelles Fest in Spanien'),
    CONCAT('Erleben Sie das ', name, ' in ', venue_name, ', ', province, '. Traditionelles spanisches Fest mit kulturellen Aktivitäten, lokaler Küche und authentischen Feiern. Perfekt für internationale Touristen.')
FROM cultural_events
WHERE is_active = 1 
    AND (start_date >= '2026-04-01' OR end_date >= '2026-04-01')
    AND id NOT IN (SELECT event_id FROM cultural_events_trads WHERE language_code = 'de');

-- 2.4 CHINO (zh) - 西班牙传统节日
-- ============================================
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

-- 3. TERCERO: ACTUALIZAR TRADUCCIONES EXISTENTES INCOMPLETAS
-- ============================================

-- 3.1 ACTUALIZAR INGLÉS (en)
-- ============================================
UPDATE cultural_events_trads cet
JOIN cultural_events ce ON cet.event_id = ce.id
SET 
    cet.slug = CASE 
        WHEN cet.slug = '' OR cet.slug NOT LIKE '%-traditional-festival-spain' 
        THEN CONCAT(ce.slug, '-traditional-festival-spain')
        ELSE cet.slug 
    END,
    cet.short_description = CASE 
        WHEN cet.short_description = '' 
        THEN CONCAT('Traditional festival in ', ce.venue_name, ', ', ce.province, ' featuring local culture, music, and traditions.')
        ELSE cet.short_description 
    END,
    cet.meta_title = CASE 
        WHEN cet.meta_title = '' 
        THEN CONCAT(ce.name, ' | Traditional Festival in Spain')
        ELSE cet.meta_title 
    END,
    cet.meta_description = CASE 
        WHEN cet.meta_description = '' 
        THEN CONCAT('Experience the ', ce.name, ' in ', ce.venue_name, ', ', ce.province, '. Traditional Spanish festival with cultural activities, local food, and authentic celebrations. Perfect for international tourists.')
        ELSE cet.meta_description 
    END,
    cet.target_audience = CASE 
        WHEN cet.target_audience = '' 
        THEN 'International tourists, culture enthusiasts, families'
        ELSE cet.target_audience 
    END,
    cet.accessibility = CASE 
        WHEN cet.accessibility = '' 
        THEN 'Wheelchair accessible, family-friendly, multilingual information available'
        ELSE cet.accessibility 
    END
WHERE cet.language_code = 'en'
    AND ce.is_active = 1 
    AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01')
    AND (cet.slug = '' OR cet.short_description = '' OR cet.meta_title = '' OR cet.meta_description = '' OR cet.target_audience = '' OR cet.accessibility = '');

-- 3.2 ACTUALIZAR FRANCÉS (fr)
-- ============================================
UPDATE cultural_events_trads cet
JOIN cultural_events ce ON cet.event_id = ce.id
SET 
    cet.slug = CASE 
        WHEN cet.slug = '' OR cet.slug NOT LIKE '%-fete-traditionnelle-espagne' 
        THEN CONCAT(ce.slug, '-fete-traditionnelle-espagne')
        ELSE cet.slug 
    END,
    cet.short_description = CASE 
        WHEN cet.short_description = '' 
        THEN CONCAT('Fête traditionnelle à ', ce.venue_name, ', ', ce.province, ' mettant en valeur la culture locale, la musique et les traditions.')
        ELSE cet.short_description 
    END,
    cet.meta_title = CASE 
        WHEN cet.meta_title = '' 
        THEN CONCAT(ce.name, ' | Fête Traditionnelle en Espagne')
        ELSE cet.meta_title 
    END,
    cet.meta_description = CASE 
        WHEN cet.meta_description = '' 
        THEN CONCAT('Vivez le ', ce.name, ' à ', ce.venue_name, ', ', ce.province, '. Fête traditionnelle espagnole avec activités culturelles, nourriture locale et célébrations authentiques. Parfait pour les touristes internationaux.')
        ELSE cet.meta_description 
    END,
    cet.target_audience = CASE 
        WHEN cet.target_audience = '' 
        THEN 'Touristes internationaux, amateurs de culture, familles'
        ELSE cet.target_audience 
    END,
    cet.accessibility = CASE 
        WHEN cet.accessibility = '' 
        THEN 'Accessible aux fauteuils roulants, adapté aux familles, informations multilingues disponibles'
        ELSE cet.accessibility 
    END
WHERE cet.language_code = 'fr'
    AND ce.is_active = 1 
    AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01')
    AND (cet.slug = '' OR cet.short_description = '' OR cet.meta_title = '' OR cet.meta_description = '' OR cet.target_audience = '' OR cet.accessibility = '');

-- 3.3 ACTUALIZAR ALEMÁN (de)
-- ============================================
UPDATE cultural_events_trads cet
JOIN cultural_events ce ON cet.event_id = ce.id
SET 
    cet.slug = CASE 
        WHEN cet.slug = '' OR cet.slug NOT LIKE '%-traditionelles-fest-spanien' 
        THEN CONCAT(ce.slug, '-traditionelles-fest-spanien')
        ELSE cet.slug 
    END,
    cet.short_description = CASE 
        WHEN cet.short_description = '' 
        THEN CONCAT('Traditionelles Fest in ', ce.venue_name, ', ', ce.province, ' mit lokaler Kultur, Musik und Traditionen.')
        ELSE cet.short_description 
    END,
    cet.meta_title = CASE 
        WHEN cet.meta_title = '' 
        THEN CONCAT(ce.name, ' | Traditionelles Fest in Spanien')
        ELSE cet.meta_title 
    END,
    cet.meta_description = CASE 
        WHEN cet.meta_description = '' 
        THEN CONCAT('Erleben Sie das ', ce.name, ' in ', ce.venue_name, ', ', ce.province, '. Traditionelles spanisches Fest mit kulturellen Aktivitäten, lokaler Küche und authentischen Feiern. Perfekt für internationale Touristen.')
        ELSE cet.meta_description 
    END,
    cet.target_audience = CASE 
        WHEN cet.target_audience = '' 
        THEN 'Internationale Touristen, Kulturliebhaber, Familien'
        ELSE cet.target_audience 
    END,
    cet.accessibility = CASE 
        WHEN cet.accessibility = '' 
        THEN 'Rollstuhlgerecht, familienfreundlich, mehrsprachige Informationen verfügbar'
        ELSE cet.accessibility 
    END
WHERE cet.language_code = 'de'
    AND ce.is_active = 1 
    AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01')
    AND (cet.slug = '' OR cet.short_description = '' OR cet.meta_title = '' OR cet.meta_description = '' OR cet.target_audience = '' OR cet.accessibility = '');

-- 3.4 ACTUALIZAR CHINO (zh)
-- ============================================
UPDATE cultural_events_trads cet
JOIN cultural_events ce ON cet.event_id = ce.id
SET 
    cet.slug = CASE 
        WHEN cet.slug = '' OR cet.slug NOT LIKE '%-chuantongjieri-xibanya' 
        THEN CONCAT(ce.slug, '-chuantongjieri-xibanya')
        ELSE cet.slug 
    END,
    cet.short_description = CASE 
        WHEN cet.short_description = '' 
        THEN CONCAT('西班牙', ce.province, ' ', ce.venue_name, '的传统节日，展示当地文化、音乐和传统。')
        ELSE cet.short_description 
    END,
    cet.meta_title = CASE 
        WHEN cet.meta_title = '' 
        THEN CONCAT(ce.name, ' | 西班牙传统节日')
        ELSE cet.meta_title 
    END,
    cet.meta_description = CASE 
        WHEN cet.meta_description = '' 
        THEN CONCAT('体验西班牙', ce.province, ' ', ce.venue_name, '的', ce.name, '。西班牙传统节日，包含文化活动、当地美食和地道庆祝。非常适合国际游客。')
        ELSE cet.meta_description 
    END,
    cet.target_audience = CASE 
        WHEN cet.target_audience = '' 
        THEN '国际游客, 文化爱好者, 家庭'
        ELSE cet.target_audience 
    END,
    cet.accessibility = CASE 
        WHEN cet.accessibility = '' 
        THEN '轮椅通道, 适合家庭, 提供多语言信息'
        ELSE cet.accessibility 
    END
WHERE cet.language_code = 'zh'
    AND ce.is_active = 1 
    AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01')
    AND (cet.slug = '' OR cet.short_description = '' OR cet.meta_title = '' OR cet.meta_description = '' OR cet.target_audience = '' OR cet.accessibility = '');

-- 4. CUARTO: VERIFICACIÓN FINAL
-- ============================================
SELECT 
    'RESUMEN FINAL' as seccion,
    COUNT(DISTINCT ce.id) as total_eventos,
    SUM(CASE WHEN cet_en.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_con_en,
    SUM(CASE WHEN cet_fr.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_con_fr,
    SUM(CASE WHEN cet_de.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_con_de,
    SUM(CASE WHEN cet_zh.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_con_zh,
    SUM(CASE WHEN cet_en.event_id IS NOT NULL AND cet_fr.event_id IS NOT NULL AND cet_de.event_id IS NOT NULL AND cet_zh.event_id IS NOT NULL THEN 1 ELSE 0 END) as eventos_completos
FROM cultural_events ce
LEFT JOIN cultural_events_trads cet_en ON ce.id = cet_en.event_id AND cet_en.language_code = 'en'
LEFT JOIN cultural_events_trads cet_fr ON ce.id = cet_fr.event_id AND cet_fr.language_code = 'fr'
LEFT JOIN cultural_events_trads cet_de ON ce.id = cet_de.event_id AND cet_de.language_code = 'de'
LEFT JOIN cultural_events_trads cet_zh ON ce.id = cet_zh.event_id AND cet_zh.language_code = 'zh'
WHERE ce.is_active = 1 
    AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01');

-- 4.1 DETALLE POR EVENTO
-- ============================================
SELECT 
    ce.id as event_id,
    ce.name as nombre_evento,
    ce.start_date as fecha_inicio,
    CASE WHEN cet_en.event_id IS NOT NULL THEN '✓' ELSE '✗' END as ingles,
    CASE WHEN cet_fr.event_id IS NOT NULL THEN '✓' ELSE '✗' END as frances,
    CASE WHEN cet_de.event_id IS NOT NULL THEN '✓' ELSE '✗' END as aleman,
    CASE WHEN cet_zh.event_id IS NOT NULL THEN '✓' ELSE '✗' END as chino,
    CASE 
        WHEN cet_en.event_id IS NOT NULL AND cet_fr.event_id IS NOT NULL AND cet_de.event_id IS NOT NULL AND cet_zh.event_id IS NOT NULL 
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

-- 4.2 VERIFICAR CAMPOS VACÍOS
-- ============================================
SELECT 
    cet.event_id,
    cet.language_code as idioma,
    ce.name as nombre_evento,
    CASE WHEN cet.slug = '' OR cet.slug IS NULL THEN 'slug,' ELSE '' END as falta_slug,
    CASE WHEN cet.short_description = '' OR cet.short_description IS NULL THEN 'desc_corta,' ELSE '' END as falta_desc_corta,
    CASE WHEN cet.description = '' OR cet.description IS NULL THEN 'desc,' ELSE '' END as falta_desc,
    CASE WHEN cet.program = '' OR cet.program IS NULL THEN 'program,' ELSE '' END as falta_program,
    CASE WHEN cet.target_audience = '' OR cet.target_audience IS NULL THEN 'target,' ELSE '' END as falta_target,
    CASE WHEN cet.accessibility = '' OR cet.accessibility IS NULL THEN 'accessibility,' ELSE '' END as falta_accessibility,
    CASE WHEN cet.meta_title = '' OR cet.meta_title IS NULL THEN 'meta_title,' ELSE '' END as falta_meta_title,
    CASE WHEN cet.meta_description = '' OR cet.meta_description IS NULL THEN 'meta_desc,' ELSE '' END as falta_meta_desc
FROM cultural_events_trads cet
JOIN cultural_events ce ON cet.event_id = ce.id
WHERE ce.is_active = 1 
    AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01')
    AND (cet.slug = '' OR cet.short_description = '' OR cet.description = '' OR cet.program = '' OR cet.target_audience = '' OR cet.accessibility = '' OR cet.meta_title = '' OR cet.meta_description = '')
ORDER BY cet.event_id, cet.language_code;

-- ============================================
-- FIN DEL SCRIPT SQL FINAL
-- ============================================
