-- ============================================
-- SCRIPT SQL: GENERAR TRADUCCIONES PARA EVENTOS FUTUROS
-- Solo eventos con start_date >= CURDATE() (fecha actual)
-- Slugs SEO optimizados para turismo internacional
-- Contenidos vitaminados en SEO
-- ============================================

-- 1. PRIMERO: IDENTIFICAR EVENTOS FUTUROS QUE NECESITAN TRADUCCIONES
-- ============================================
SELECT 
    'EVENTOS FUTUROS QUE NECESITAN TRADUCCIONES' as seccion,
    ce.id as event_id,
    ce.name as event_name,
    ce.slug as original_slug,
    ce.venue_name as ubicacion,
    ce.municipality as localidad,
    ce.province as provincia,
    ce.start_date,
    ce.end_date,
    GROUP_CONCAT(DISTINCT cet.language_code ORDER BY cet.language_code) as traducciones_existentes,
    COUNT(DISTINCT cet.language_code) as conteo_traducciones,
    CASE 
        WHEN COUNT(DISTINCT cet.language_code) = 4 THEN 'COMPLETO'
        WHEN COUNT(DISTINCT cet.language_code) > 0 THEN 'PARCIAL'
        ELSE 'SIN TRADUCCIONES'
    END as estado_traducciones
FROM cultural_events ce
LEFT JOIN cultural_events_trads cet ON ce.id = cet.event_id
WHERE ce.is_active = 1 
    AND ce.start_date >= CURDATE()
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
    -- Slug SEO optimizado para inglés
    CONCAT(
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(COALESCE(name, '')), ' ', '-'), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n'),
        '-traditional-festival-',
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(COALESCE(province, '')), ' ', '-'), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n'),
        '-spain-',
        YEAR(start_date)
    ),
    -- Short description SEO optimizada
    CONCAT('Experience the authentic ', name, ' in ', venue_name, ', ', province, '. Traditional Spanish festival with local culture, music, food, and celebrations for international tourists.'),
    -- Description completa optimizada para SEO
    CONCAT('<h1>', name, ' - Traditional Festival in ', province, ', Spain</h1>
<p>The ', name, ' is one of the most authentic and important traditional festivals in ', province, ', Spain. This annual celebration offers international visitors a unique opportunity to experience genuine Spanish culture and traditions.</p>

<h2>Festival Highlights</h2>
<ul>
<li><strong>Traditional Music & Dance:</strong> Live performances of authentic Spanish music and traditional dances</li>
<li><strong>Local Gastronomy:</strong> Taste traditional Spanish food and regional specialties from ', province, '</li>
<li><strong>Cultural Exhibitions:</strong> Discover local crafts, art, and cultural heritage</li>
<li><strong>Family Activities:</strong> Enjoy family-friendly events and workshops</li>
<li><strong>Religious Processions:</strong> Experience traditional religious ceremonies (where applicable)</li>
<li><strong>Local Markets:</strong> Browse artisan markets with handmade products</li>
</ul>

<h2>Practical Information</h2>
<p><strong>Dates:</strong> ', start_date, IF(end_date IS NOT NULL AND end_date != start_date, CONCAT(' to ', end_date), ''),
    IF(end_date IS NOT NULL AND end_date != start_date, 
        CONCAT(' (', DATEDIFF(end_date, start_date) + 1, ' days)'), 
        ' (1 day)'), '</p>
<p><strong>Location:</strong> ', venue_name, ', ', municipality, ', ', province, ', Spain</p>
<p><strong>Best for:</strong> International tourists, culture enthusiasts, families, photographers</p>
<p><strong>Entry:</strong> ', IF(is_free = 1, 'Free admission', CONCAT('Ticket price: €', ticket_price)), '</p>

<h2>Why Visit This Festival?</h2>
<p>The ', name, ' represents the authentic spirit of Spanish traditions. Unlike commercial tourist events, this festival maintains its genuine cultural roots while welcoming international visitors. It''s the perfect opportunity to experience real Spanish hospitality and create unforgettable memories.</p>

<h2>Travel Tips</h2>
<ul>
<li><strong>Best Time to Arrive:</strong> Morning for full-day experience</li>
<li><strong>What to Wear:</strong> Comfortable shoes for walking, light clothing for daytime</li>
<li><strong>Photography:</strong> Excellent opportunities for cultural photography</li>
<li><strong>Local Transport:</strong> Public transportation available to ', venue_name, '</li>
</ul>'),
    -- Program detallado
    CONCAT('Daily program includes morning cultural activities (10:00-14:00), afternoon traditional performances (16:00-19:00), and evening celebrations with music and dance (20:00-23:00). Special events vary by day.'),
    -- Target audience optimizada
    'International tourists, cultural travelers, expats in Spain, photography enthusiasts, families visiting Spain',
    -- Accessibility info
    CONCAT('Wheelchair accessible areas available. Family-friendly environment. Multilingual information points. Public transportation access. Parking facilities near ', venue_name, '.'),
    -- Meta title SEO optimizado
    CONCAT(name, ' | Traditional Spanish Festival in ', province, ' | ', YEAR(start_date)),
    -- Meta description SEO optimizado
    CONCAT('Experience the authentic ', name, ' festival in ', venue_name, ', ', province, ', Spain. Traditional Spanish celebrations, local food, music, and cultural activities. Perfect for international tourists visiting Spain in ', YEAR(start_date), '.')
FROM cultural_events
WHERE is_active = 1 
    AND start_date >= CURDATE()
    AND id NOT IN (SELECT event_id FROM cultural_events_trads WHERE language_code = 'en');

-- 2.2 FRANCÉS (fr) - Fête Traditionnelle en Espagne
-- ============================================
INSERT INTO cultural_events_trads 
(event_id, language_code, name, slug, short_description, description, program, target_audience, accessibility, meta_title, meta_description)
SELECT 
    id,
    'fr',
    name,
    -- Slug SEO optimizado para francés
    CONCAT(
        REPLACE(LOWER(name), ' ', '-'),
        '-fete-traditionnelle-',
        REPLACE(LOWER(province), ' ', '-'),
        '-espagne-',
        YEAR(start_date)
    ),
    -- Short description SEO optimizada
    CONCAT('Vivez l''authentique ', name, ' à ', venue_name, ', ', province, '. Fête traditionnelle espagnole avec culture locale, musique, gastronomie et célébrations pour touristes internationaux.'),
    -- Description completa optimizada para SEO
    CONCAT('<h1>', name, ' - Fête Traditionnelle à ', province, ', Espagne</h1>
<p>Le ', name, ' est l''une des fêtes traditionnelles les plus authentiques et importantes de ', province, ', Espagne. Cette célébration annuelle offre aux visiteurs internationaux une opportunité unique de vivre la culture et les traditions espagnoles authentiques.</p>

<h2>Points Forts du Festival</h2>
<ul>
<li><strong>Musique & Danse Traditionnelles:</strong> Spectacles en direct de musique espagnole authentique et danses traditionnelles</li>
<li><strong>Gastronomie Locale:</strong> Dégustez la cuisine traditionnelle espagnole et les spécialités régionales de ', province, '</li>
<li><strong>Expositions Culturelles:</strong> Découvrez l''artisanat local, l''art et le patrimoine culturel</li>
<li><strong>Activités Familiales:</strong> Profitez d''événements et d''ateliers adaptés aux familles</li>
<li><strong>Processions Religieuses:</strong> Vivez les cérémonies religieuses traditionnelles (le cas échéant)</li>
<li><strong>Marchés Locaux:</strong> Parcourez les marchés artisanaux avec des produits faits main</li>
</ul>

<h2>Informations Pratiques</h2>
<p><strong>Dates:</strong> ', start_date, IF(end_date IS NOT NULL AND end_date != start_date, CONCAT(' au ', end_date), ''),
    IF(end_date IS NOT NULL AND end_date != start_date, 
        CONCAT(' (', DATEDIFF(end_date, start_date) + 1, ' jours)'), 
        ' (1 jour)'), '</p>
<p><strong>Lieu:</strong> ', venue_name, ', ', municipality, ', ', province, ', Espagne</p>
<p><strong>Idéal pour:</strong> Touristes internationaux, amateurs de culture, familles, photographes</p>
<p><strong>Entrée:</strong> ', IF(is_free = 1, 'Entrée gratuite', CONCAT('Prix du billet: €', ticket_price)), '</p>

<h2>Pourquoi Visiter Ce Festival?</h2>
<p>Le ', name, ' représente l''esprit authentique des traditions espagnoles. Contrairement aux événements touristiques commerciaux, cette fête conserve ses racines culturelles authentiques tout en accueillant les visiteurs internationaux. C''est l''occasion parfaite de vivre l''hospitalité espagnole authentique et de créer des souvenirs inoubliables.</p>

<h2>Conseils de Voyage</h2>
<ul>
<li><strong>Meilleur moment pour arriver:</strong> Le matin pour une expérience complète</li>
<li><strong>Quoi porter:</strong> Chaussures confortables pour marcher, vêtements légers pour la journée</li>
<li><strong>Photographie:</strong> Excellentes opportunités pour la photographie culturelle</li>
<li><strong>Transport local:</strong> Transport public disponible vers ', venue_name, '</li>
</ul>'),
    -- Program detallado
    CONCAT('Programme quotidien incluant activités culturelles matinales (10:00-14:00), spectacles traditionnels l''après-midi (16:00-19:00) et célébrations nocturnes avec musique et danse (20:00-23:00). Événements spéciaux varient selon les jours.'),
    -- Target audience optimizada
    'Touristes internationaux, voyageurs culturels, expatriés en Espagne, passionnés de photographie, familles visitant l''Espagne',
    -- Accessibility info
    CONCAT('Zones accessibles aux fauteuils roulants disponibles. Environnement adapté aux familles. Points d''information multilingues. Accès aux transports publics. Parkings à proximité de ', venue_name, '.'),
    -- Meta title SEO optimizado
    CONCAT(name, ' | Fête Traditionnelle Espagnole à ', province, ' | ', YEAR(start_date)),
    -- Meta description SEO optimizado
    CONCAT('Vivez l''authentique fête ', name, ' à ', venue_name, ', ', province, ', Espagne. Célébrations traditionnelles espagnoles, nourriture locale, musique et activités culturelles. Parfait pour les touristes internationaux visitant l''Espagne en ', YEAR(start_date), '.')
FROM cultural_events
WHERE is_active = 1 
    AND start_date >= CURDATE()
    AND id NOT IN (SELECT event_id FROM cultural_events_trads WHERE language_code = 'fr');

-- 2.3 ALEMÁN (de) - Traditionelles Fest in Spanien
-- ============================================
INSERT INTO cultural_events_trads 
(event_id, language_code, name, slug, short_description, description, program, target_audience, accessibility, meta_title, meta_description)
SELECT 
    id,
    'de',
    name,
    -- Slug SEO optimizado para alemán
    CONCAT(
        REPLACE(LOWER(name), ' ', '-'),
        '-traditionelles-fest-',
        REPLACE(LOWER(province), ' ', '-'),
        '-spanien-',
        YEAR(start_date)
    ),
    -- Short description SEO optimizada
    CONCAT('Erleben Sie das authentische ', name, ' in ', venue_name, ', ', province, '. Traditionelles spanisches Fest mit lokaler Kultur, Musik, Essen und Feiern für internationale Touristen.'),
    -- Description completa optimizada para SEO
    CONCAT('<h1>', name, ' - Traditionelles Fest in ', province, ', Spanien</h1>
<p>Das ', name, ' ist eines der authentischsten und wichtigsten traditionellen Feste in ', province, ', Spanien. Diese jährliche Feier bietet internationalen Besuchern eine einzigartige Gelegenheit, echte spanische Kultur und Traditionen zu erleben.</p>

<h2>Festival-Höhepunkte</h2>
<ul>
<li><strong>Traditionelle Musik & Tanz:</strong> Live-Aufführungen authentischer spanischer Musik und traditioneller Tänze</li>
<li><strong>Lokale Gastronomie:</strong> Kosten Sie traditionelles spanisches Essen und regionale Spezialitäten aus ', province, '</li>
<li><strong>Kulturausstellungen:</strong> Entdecken Sie lokales Handwerk, Kunst und kulturelles Erbe</li>
<li><strong>Familienaktivitäten:</strong> Genießen Sie familienfreundliche Veranstaltungen und Workshops</li>
<li><strong>Religiöse Prozessionen:</strong> Erleben Sie traditionelle religiöse Zeremonien (falls zutreffend)</li>
<li><strong>Lokale Märkte:</strong> Durchstöbern Sie Kunsthandwerksmärkte mit handgefertigten Produkten</li>
</ul>

<h2>Praktische Informationen</h2>
<p><strong>Daten:</strong> ', start_date, IF(end_date IS NOT NULL AND end_date != start_date, CONCAT(' bis ', end_date), ''),
    IF(end_date IS NOT NULL AND end_date != start_date, 
        CONCAT(' (', DATEDIFF(end_date, start_date) + 1, ' Tage)'), 
        ' (1 Tag)'), '</p>
<p><strong>Ort:</strong> ', venue_name, ', ', municipality, ', ', province, ', Spanien</p>
<p><strong>Am besten für:</strong> Internationale Touristen, Kulturliebhaber, Familien, Fotografen</p>
<p><strong>Eintritt:</strong> ', IF(is_free = 1, 'Freier Eintritt', CONCAT('Eintrittspreis: €', ticket_price)), '</p>

<h2>Warum Dieses Festival Besuchen?</h2>
<p>Das ', name, ' repräsentiert den authentischen Geist spanischer Traditionen. Im Gegensatz zu kommerziellen Touristenveranstaltungen bewahrt dieses Fest seine echten kulturellen Wurzeln, während es internationale Besucher willkommen heißt. Es ist die perfekte Gelegenheit, echte spanische Gastfreundschaft zu erleben und unvergessliche Erinnerungen zu schaffen.</p>

<h2>Reisetipps</h2>
<ul>
<li><strong>Beste Ankunftszeit:</strong> Morgens für ein Tageserlebnis</li>
<li><strong>Was anzuziehen:</strong> Bequeme Schuhe zum Laufen, leichte Kleidung für den Tag</li>
<li><strong>Fotografie:</strong> Ausgezeichnete Möglichkeiten für Kulturfotografie</li>
<li><strong>Lokaler Transport:</strong> Öffentliche Verkehrsmittel verfügbar nach ', venue_name, '</li>
</ul>'),
    -- Program detallado
    CONCAT('Tagesprogramm beinhaltet morgendliche Kulturaktivitäten (10:00-14:00), nachmittägliche traditionelle Aufführungen (16:00-19:00) und abendliche Feiern mit Musik und Tanz (20:00-23:00). Besondere Veranstaltungen variieren je nach Tag.'),
    -- Target audience optimizada
    'Internationale Touristen, Kulturreisende, Expats in Spanien, Fotografie-Enthusiasten, Familien, die Spanien besuchen',
    -- Accessibility info
    CONCAT('Rollstuhlgerechte Bereiche verfügbar. Familienfreundliche Umgebung. Mehrsprachige Informationspunkte. Öffentliche Verkehrsanbindung. Parkplätze in der Nähe von ', venue_name, '.'),
    -- Meta title SEO optimizado
    CONCAT(name, ' | Traditionelles Spanisches Fest in ', province, ' | ', YEAR(start_date)),
    -- Meta description SEO optimizado
    CONCAT('Erleben Sie das authentische ', name, '-Fest in ', venue_name, ', ', province, ', Spanien. Traditionelle spanische Feiern, lokales Essen, Musik und kulturelle Aktivitäten. Perfekt für internationale Touristen, die Spanien im Jahr ', YEAR(start_date), ' besuchen.')
FROM cultural_events
WHERE is_active = 1 
    AND start_date >= CURDATE()
    AND id NOT IN (SELECT event_id FROM cultural_events_trads WHERE language_code = 'de');

-- 2.4 CHINO (zh) - 西班牙传统节日
-- ============================================
INSERT INTO cultural_events_trads 
(event_id, language_code, name, slug, short_description, description, program, target_audience, accessibility, meta_title, meta_description)
SELECT 
    id,
    'zh',
    name,
    -- Slug SEO optimizado para chino
    CONCAT(
        REPLACE(LOWER(name), ' ', '-'),
        '-chuantongjieri-',
        REPLACE(LOWER(province), ' ', '-'),
        '-xibanya-',
        YEAR(start_date)
    ),
    -- Short description SEO optimizada
    CONCAT('体验西班牙', province, ' ', venue_name, '的', name, '。西班牙传统节日，包含当地文化、音乐、美食和庆祝活动，适合国际游客。'),
    -- Description completa optimizada para SEO
    CONCAT('<h1>', name, ' - 西班牙', province, '传统节日</h1>
<p>', name, '是西班牙', province, '最正宗、最重要的传统节日之一。这个年度庆典为国际游客提供了体验真正西班牙文化和传统的独特机会。</p>

<h2>节日亮点</h2>
<ul>
<li><strong>传统音乐与舞蹈:</strong> 正宗西班牙音乐和传统舞蹈的现场表演</li>
<li><strong>当地美食:</strong> 品尝传统西班牙美食和', province, '的地区特色菜</li>
<li><strong>文化展览:</strong> 探索当地手工艺品、艺术和文化遗产</li>
<li><strong>家庭活动:</strong> 享受适合家庭的活动和工作坊</li>
<li><strong>宗教游行:</strong> 体验传统宗教仪式（如适用）</li>
<li><strong>当地市场:</strong> 浏览手工制作产品的工艺品市场</li>
</ul>

<h2>实用信息</h2>
<p><strong>日期:</strong> ', start_date, IF(end_date IS NOT NULL AND end_date != start_date, CONCAT(' 至 ', end_date), ''),
    IF(end_date IS NOT NULL AND end_date != start_date, 
        CONCAT(' (', DATEDIFF(end_date, start_date) + 1, ' 天)'), 
        ' (1 天)'), '</p>
<p><strong>地点:</strong> ', venue_name, ', ', municipality, ', ', province, ', 西班牙</p>
<p><strong>最适合:</strong> 国际游客, 文化爱好者, 家庭, 摄影师</p>
<p><strong>入场:</strong> ', IF(is_free = 1, '免费入场', CONCAT('门票价格: €', ticket_price)), '</p>

<h2>为什么参观这个节日？</h2>
<p>', name, '代表了西班牙传统的真实精神。与商业旅游活动不同，这个节日在欢迎国际游客的同时保持了其真正的文化根源。这是体验真正西班牙热情好客并创造难忘回忆的完美机会。</p>

<h2>旅行建议</h2>
<ul>
<li><strong>最佳到达时间:</strong> 早上以获得全天体验</li>
<li><strong>穿着建议:</strong> 舒适的步行鞋，白天的轻便服装</li>
<li><strong>摄影:</strong> 文化摄影的绝佳机会</li>
<li><strong>当地交通:</strong> 可乘坐公共交通工具前往', venue_name, '</li>
</ul>'),
    -- Program detallado
    CONCAT('每日行程包括上午文化活动（10:00-14:00）、下午传统表演（16:00-19:00）和晚间音乐舞蹈庆祝活动（20:00-23:00）。特别活动因日期而异。'),
    -- Target audience optimizada
    '国际游客, 文化旅行者, 在西班牙的外籍人士, 摄影爱好者, 访问西班牙的家庭',
    -- Accessibility info
    CONCAT('提供轮椅无障碍区域。家庭友好环境。多语言信息点。公共交通便利。', venue_name, '附近有停车设施。'),
    -- Meta title SEO optimizado
    CONCAT(name, ' | 西班牙', province, '传统节日 | ', YEAR(start_date)),
    -- Meta description SEO optimizado
    CONCAT('体验西班牙', province, ' ', venue_name, '的', name, '节日。西班牙传统庆祝活动、当地美食、音乐和文化活动。非常适合', YEAR(start_date), '年访问西班牙的国际游客。')
FROM cultural_events
WHERE is_active = 1 
    AND start_date >= CURDATE()
    AND id NOT IN (SELECT event_id FROM cultural_events_trads WHERE language_code = 'zh');

-- 3. TERCERO: VERIFICACIÓN FINAL
-- ============================================
SELECT 
    'TOTAL DE TRADUCCIONES ACTUALES' as seccion,
    language_code as idioma,
    COUNT(*) as total
FROM cultural_events_trads 
GROUP BY language_code
ORDER BY language_code;

SELECT 
    'EVENTOS FUTUROS CON TRADUCCIONES COMPLETAS' as seccion,
    ce.id as event_id,
    ce.name as event_name,
    ce.start_date,
    ce.province,
    GROUP_CONCAT(DISTINCT cet.language_code ORDER BY cet.language_code) as traducciones_completas,
    COUNT(DISTINCT cet.language_code) as total_idiomas
FROM cultural_events ce
LEFT JOIN cultural_events_trads cet ON ce.id = cet.event_id
WHERE ce.is_active = 1 
    AND ce.start_date >= CURDATE()
GROUP BY ce.id
HAVING COUNT(DISTINCT cet.language_code) = 4
ORDER BY ce.start_date ASC;

SELECT 
    'EVENTOS FUTUROS CON TRADUCCIONES INCOMPLETAS' as seccion,
    ce.id as event_id,
    ce.name as event_name,
    ce.start_date,
    ce.province,
    GROUP_CONCAT(DISTINCT cet.language_code ORDER BY cet.language_code) as traducciones_existentes,
    COUNT(DISTINCT cet.language_code) as total_idiomas,
    4 - COUNT(DISTINCT cet.language_code) as idiomas_faltantes
FROM cultural_events ce
LEFT JOIN cultural_events_trads cet ON ce.id = cet.event_id
WHERE ce.is_active = 1 
    AND ce.start_date >= CURDATE()
GROUP BY ce.id
HAVING COUNT(DISTINCT cet.language_code) < 4
ORDER BY ce.start_date ASC;
