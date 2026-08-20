<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  MAPA CANÓNICO DE PROVINCIAS Y FILTROS — rutasrurales.io
 *  Sistema de Landings Long-Tail para /eventos/{filtros}-{provincia}
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Cada provincia incluye:
 *    'label'       → nombre oficial (con tildes) para mostrar en UI
 *    'db'          → valor EXACTO en columna `province` de la tabla cultural_events
 *    'attractions' → array de 3-4 atractivos principales (para intro texto SEO)
 *    'vibe'        → descripción corta por idioma para intro dinámico
 *
 *  Cada filtro incluye:
 *    'sql'    → condición SQL raw (sin input de usuario, 100% seguro)
 *    'labels' → etiqueta por idioma (para H1 dinámico, breadcrumb, meta)
 *    'icon'   → emoji decorativo
 *    'group'  → 'categoria' | 'precio' | 'temporada'
 */

// ─── PROVINCIAS ───────────────────────────────────────────────────────────────
const EVENTOS_PROVINCIAS = [

    // Castilla y León
    'soria' => [
        'label' => 'Soria', 'db' => 'Soria',
        'lat' => 41.766, 'lng' => -2.468,
        'attractions' => ['Cañón del Río Lobos', 'Lagunas de Urbión', 'Numancia', 'Sierra de Cebollera'],
        'vibe' => [
            'es' => 'una de las provincias más tranquilas y auténticas de España, donde las tradiciones se celebran con una intensidad que pocas ciudades pueden igualar',
            'en' => 'one of Spain\'s most peaceful and authentic provinces, where traditions are celebrated with an intensity few cities can match',
            'fr' => 'l\'une des provinces les plus tranquilles et authentiques d\'Espagne, où les traditions se célèbrent avec une intensité que peu de villes peuvent égaler',
            'de' => 'eine der ruhigsten und authentischsten Provinzen Spaniens, wo Traditionen mit einer Intensität gefeiert werden, die wenige Städte erreichen können',
            'zh' => '西班牙最宁静、最原始的省份之一，传统节日在这里以无与伦比的热情庆祝',
        ],
    ],
    'zamora' => [
        'label' => 'Zamora', 'db' => 'Zamora',
        'lat' => 41.503, 'lng' => -5.744,
        'attractions' => ['Lago de Sanabria', 'Arribes del Duero', 'Semana Santa de Zamora', 'Sierra de la Culebra'],
        'vibe' => [
            'es' => 'tierra de contrastes con una de las Semanas Santas más antiguas de España, festivales medievales y una cultura popular arraigada en cada rincón',
            'en' => 'a land of contrasts with one of Spain\'s oldest Holy Weeks, medieval festivals and a popular culture rooted in every corner',
            'fr' => 'une terre de contrastes avec l\'une des Semaines Saintes les plus anciennes d\'Espagne, des festivals médiévaux et une culture populaire enracinée',
            'de' => 'ein Land der Kontraste mit einer der ältesten Heiligen Wochen Spaniens, mittelalterlichen Festivals und einer verwurzelten Volkskultur',
            'zh' => '充满对比的土地，拥有西班牙最古老的圣周之一，中世纪节日和根植于每个角落的民俗文化',
        ],
    ],
    'salamanca' => [
        'label' => 'Salamanca', 'db' => 'Salamanca',
        'lat' => 40.970, 'lng' => -5.663,
        'attractions' => ['Ferias y Fiestas de Salamanca', 'Festival Universitario', 'La Alberca (Patrimonio)', 'Sierra de Francia'],
        'vibe' => [
            'es' => 'una ciudad universitaria vibrante donde la cultura no descansa: festivales de jazz, teatro clásico, ferias medievales y una agenda cultural que rivaliza con cualquier capital europea',
            'en' => 'a vibrant university city where culture never rests: jazz festivals, classical theatre, medieval fairs and a cultural programme that rivals any European capital',
            'fr' => 'une ville universitaire vibrante où la culture ne s\'arrête pas : festivals de jazz, théâtre classique, foires médiévales et une programmation culturelle rivalisant avec n\'importe quelle capitale européenne',
            'de' => 'eine pulsierende Universitätsstadt, wo Kultur nie ruht: Jazzfestivals, klassisches Theater, mittelalterliche Messen und ein Kulturprogramm, das mit jeder europäischen Hauptstadt mithalten kann',
            'zh' => '充满活力的大学城，文化从不停歇：爵士音乐节、古典戏剧、中世纪集市和可与任何欧洲首都媲美的文化日程',
        ],
    ],
    'burgos' => [
        'label' => 'Burgos', 'db' => 'Burgos',
        'lat' => 42.344, 'lng' => -3.697,
        'attractions' => ['Festival Internacional de Folclore', 'Ruta del Cid', 'Catedral de Burgos', 'Las Merindades'],
        'vibe' => [
            'es' => 'cuna del Cid Campeador y puerta del Camino de Santiago, con festivales que honran siglos de historia y tradiciones que se transmiten de generación en generación',
            'en' => 'birthplace of El Cid and gateway to the Camino de Santiago, with festivals honouring centuries of history and traditions passed down through generations',
            'fr' => 'berceau du Cid et porte du Chemin de Saint-Jacques, avec des festivals honorant des siècles d\'histoire et des traditions transmises de génération en génération',
            'de' => 'Geburtsort von El Cid und Tor zum Jakobsweg, mit Festivals, die Jahrhunderte der Geschichte ehren, und Traditionen, die von Generation zu Generation weitergegeben werden',
            'zh' => '熙德的故乡和圣地亚哥朝圣之路的大门，节日铭记着几个世纪的历史，传统代代相传',
        ],
    ],
    'leon' => [
        'label' => 'León', 'db' => 'León',
        'lat' => 42.599, 'lng' => -5.571,
        'attractions' => ['Las Cantaderas', 'Festival Noroeste Estrella Galicia', 'Fiesta de San Froilán', 'Camino de Santiago'],
        'vibe' => [
            'es' => 'una provincia monumental con una agenda cultural que mezcla tradición medieval con festivales de música contemporánea, todo bajo la sombra de su catedral gótica y los Picos de Europa',
            'en' => 'a monumental province with a cultural programme mixing medieval tradition with contemporary music festivals, all under the shadow of its Gothic cathedral and the Picos de Europa',
            'fr' => 'une province monumentale avec une programmation culturelle mêlant tradition médiévale et festivals de musique contemporaine, le tout sous l\'ombre de sa cathédrale gothique',
            'de' => 'eine monumentale Provinz mit einem Kulturprogramm, das mittelalterliche Traditionen mit zeitgenössischen Musikfestivals verbindet, alles im Schatten ihrer gotischen Kathedrale',
            'zh' => '壮丽的省份，文化日程融合中世纪传统与当代音乐节，尽在哥特式大教堂和欧罗巴峰的庇荫之下',
        ],
    ],
    'valladolid' => [
        'label' => 'Valladolid', 'db' => 'Valladolid',
        'lat' => 41.652, 'lng' => -4.724,
        'attractions' => ['Semana Internacional de Cine', 'Fiestas de la Virgen de San Lorenzo', 'Ribera del Duero', 'Semana Santa'],
        'vibe' => [
            'es' => 'ciudad de cine, vino y Semana Santa declarada de Interés Turístico Internacional, con una agenda cultural que fusiona lo mejor del patrimonio castellano con la creatividad contemporánea',
            'en' => 'a city of cinema, wine and Holy Week declared of International Tourist Interest, with a cultural programme fusing the best of Castilian heritage with contemporary creativity',
            'fr' => 'une ville de cinéma, de vin et de Semaine Sainte déclarée d\'Intérêt Touristique International, avec une programmation culturelle fusionnant le patrimoine castillan et la créativité contemporaine',
            'de' => 'eine Stadt des Kinos, des Weins und der Heiligen Woche, die zum internationalen touristischen Interesse erklärt wurde, mit einem Kulturprogramm, das kastilisches Erbe und zeitgenössische Kreativität vereint',
            'zh' => '电影、葡萄酒与国际旅游兴趣圣周之城，文化日程融合卡斯蒂利亚遗产与当代创意',
        ],
    ],
    'palencia' => [
        'label' => 'Palencia', 'db' => 'Palencia',
        'lat' => 42.009, 'lng' => -4.528,
        'attractions' => ['Feria Renacentista de Aguilar', 'Canal de Castilla', 'Semana Santa de Palencia', 'Fuentes Carrionas'],
        'vibe' => [
            'es' => 'la joya oculta de Castilla, donde las ferias renacentistas de Aguilar de Campoo y los mercados medievales recuperan siglos de historia con espectacularidad',
            'en' => 'Castile\'s hidden gem, where the Renaissance fairs of Aguilar de Campoo and medieval markets recover centuries of history with spectacular flair',
            'fr' => 'le joyau caché de Castille, où les foires de la Renaissance d\'Aguilar de Campoo et les marchés médiévaux récupèrent des siècles d\'histoire avec faste',
            'de' => 'das verborgene Juwel Kastiliens, wo die Renaissancemessen von Aguilar de Campoo und mittelalterliche Märkte Jahrhunderte der Geschichte mit spektakulärer Pracht wiederbeleben',
            'zh' => '卡斯蒂利亚的隐秘珍宝，阿吉拉尔文艺复兴集市和中世纪集市以壮观的方式重现数百年历史',
        ],
    ],
    'segovia' => [
        'label' => 'Segovia', 'db' => 'Segovia',
        'lat' => 40.943, 'lng' => -4.118,
        'attractions' => ['Titirimundi (Festival Internacional de Títeres)', 'Hay Festival Segovia', 'Acueducto de Segovia', 'Pedraza'],
        'vibe' => [
            'es' => 'una provincia que desafía el tiempo, sede del Titirimundi —uno de los festivales de títeres más importantes del mundo— y del Hay Festival, que convierte cada verano en una fiesta del pensamiento',
            'en' => 'a province that defies time, home to Titirimundi —one of the world\'s most important puppet festivals— and the Hay Festival, which turns every summer into a celebration of ideas',
            'fr' => 'une province qui défie le temps, siège du Titirimundi —l\'un des festivals de marionnettes les plus importants du monde— et du Hay Festival',
            'de' => 'eine Provinz, die der Zeit trotzt, Heimat des Titirimundi —eines der wichtigsten Puppentheaterfestivals der Welt— und des Hay Festivals',
            'zh' => '穿越时空的省份，举办世界最重要的木偶节Titirimundi和将夏天变成思想盛宴的Hay Festival',
        ],
    ],
    'avila' => [
        'label' => 'Ávila', 'db' => 'Ávila',
        'lat' => 40.656, 'lng' => -4.699,
        'attractions' => ['Fiesta de Santa Teresa', 'Mercado Medieval de Ávila', 'Muralla Medieval', 'Sierra de Gredos'],
        'vibe' => [
            'es' => 'la ciudad amurallada más alta de Europa con un calendario festivo que honra a Santa Teresa y revive la Edad Media en uno de los mercados medievales más auténticos de España',
            'en' => 'Europe\'s highest walled city with a festive calendar honouring Saint Teresa and reviving the Middle Ages in one of Spain\'s most authentic medieval markets',
            'fr' => 'la plus haute ville fortifiée d\'Europe avec un calendrier festif honorant Sainte Thérèse et faisant revivre le Moyen Âge dans l\'un des marchés médiévaux les plus authentiques d\'Espagne',
            'de' => 'Europas höchste ummauerte Stadt mit einem Festkalender zu Ehren der Heiligen Teresa und einem der authentischsten Mittelaltermärkte Spaniens',
            'zh' => '欧洲海拔最高的城墙城市，节日历法纪念圣特蕾莎，在西班牙最真实的中世纪集市中重现中世纪',
        ],
    ],
    'guadalajara' => [
        'label' => 'Guadalajara', 'db' => 'Guadalajara',
        'lat' => 40.630, 'lng' => -3.164,
        'attractions' => ['Mercado Medieval de Sigüenza', 'Feria de Artesanía del Alto Tajo', 'Hayedo de Tejera Negra', 'Sigüenza'],
        'vibe' => [
            'es' => 'el secreto mejor guardado de la Meseta, con Sigüenza y su mercado medieval como joyas culturales y un calendario de eventos que recupera las tradiciones más auténticas de Castilla',
            'en' => 'the Meseta\'s best kept secret, with Sigüenza and its medieval market as cultural gems and an events calendar that revives the most authentic Castilian traditions',
            'fr' => 'le mieux gardé secret de la Meseta, avec Sigüenza et son marché médiéval comme joyaux culturels et un calendrier d\'événements qui ravive les traditions castillanes les plus authentiques',
            'de' => 'das bestgehütete Geheimnis der Meseta, mit Sigüenza und seinem Mittelaltermarkt als kulturelle Juwelen und einem Veranstaltungskalender, der die authentischsten kastilischen Traditionen wiederbelebt',
            'zh' => '梅塞塔高原最神秘的角落，希圭恩萨及其中世纪集市是文化瑰宝，活动日历重现最纯正的卡斯蒂利亚传统',
        ],
    ],
    'cuenca' => [
        'label' => 'Cuenca', 'db' => 'Cuenca',
        'lat' => 40.072, 'lng' => -2.134,
        'attractions' => ['Semana de Música Religiosa', 'Festival de Teatro', 'Ciudad Encantada', 'Casas Colgadas'],
        'vibe' => [
            'es' => 'tierra de paisajes imposibles y de la Semana de Música Religiosa más prestigiosa de España, que transforma cada primavera la ciudad en un templo del arte y la espiritualidad',
            'en' => 'a land of impossible landscapes and Spain\'s most prestigious Religious Music Week, which transforms the city each spring into a temple of art and spirituality',
            'fr' => 'une terre de paysages impossibles et de la Semaine de Musique Religieuse la plus prestigieuse d\'Espagne, qui transforme chaque printemps la ville en temple de l\'art et de la spiritualité',
            'de' => 'ein Land unmöglicher Landschaften und der prestigiösesten Woche der religiösen Musik Spaniens, die die Stadt jeden Frühling in einen Tempel der Kunst und Spiritualität verwandelt',
            'zh' => '不可思议的风景之地，举办西班牙最负盛名的宗教音乐周，每年春天将城市变成艺术与精神的殿堂',
        ],
    ],
    'ourense' => [
        'label' => 'Ourense', 'db' => 'Ourense',
        'lat' => 42.336, 'lng' => -7.864,
        'attractions' => ['Entroido de Ourense (Carnaval Gallego)', 'Festival de Cine de Ourense', 'Ribeira Sacra', 'Las Termas de Ourense'],
        'vibe' => [
            'es' => 'la provincia del Entroido más salvaje de Galicia y de un festival de cine internacional que compite con los mejores de Europa, todo entre viñedos verticales y aguas termales',
            'en' => 'the province of Galicia\'s wildest Carnival and an international film festival that competes with Europe\'s best, all among vertical vineyards and thermal springs',
            'fr' => 'la province du Carnaval le plus sauvage de Galice et d\'un festival de cinéma international qui rivalise avec les meilleurs d\'Europe, le tout parmi des vignobles verticaux et des sources thermales',
            'de' => 'die Provinz des wildesten Karnevals Galiziens und eines internationalen Filmfestivals, das mit Europas Besten mithalten kann, inmitten vertikaler Weinberge und Thermalquellen',
            'zh' => '加利西亚最狂野狂欢节和可与欧洲最佳媲美的国际电影节所在省份，尽在垂直葡萄园和温泉之间',
        ],
    ],
];

// ─── FILTROS DE EVENTOS ───────────────────────────────────────────────────────
// Organizados en 3 grupos: categoría, precio, temporada
// 'sql'    → condición SQL raw (valores hardcoded, nunca user-input)
// 'labels' → etiqueta visible por idioma
// 'icon'   → emoji para UI
// 'group'  → grupo del filtro
const EVENTOS_FILTROS = [

    // ── CATEGORÍAS (group='categoria') ────────────────────────────────────────
    'musica' => [
        'sql'    => "(LOWER(COALESCE(e.target_audience,'')) LIKE '%m%sic%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%concert%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%m%sica%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%festival%'
                      OR e.category_id IN (SELECT id FROM categories_events WHERE LOWER(name) LIKE '%m%sic%'))",
        'labels' => ['es'=>'Eventos de música','en'=>'Music events','fr'=>'Événements musicaux','de'=>'Musikveranstaltungen','zh'=>'音乐活动'],
        'icon'   => '🎵', 'group' => 'categoria',
    ],
    'teatro' => [
        'sql'    => "(LOWER(COALESCE(e.short_description,'')) LIKE '%teatro%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%danza%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%escénic%'
                      OR e.category_id IN (SELECT id FROM categories_events WHERE LOWER(name) LIKE '%teatro%'))",
        'labels' => ['es'=>'Teatro y danza','en'=>'Theatre & dance','fr'=>'Théâtre et danse','de'=>'Theater und Tanz','zh'=>'戏剧与舞蹈'],
        'icon'   => '🎭', 'group' => 'categoria',
    ],
    'exposiciones' => [
        'sql'    => "(LOWER(COALESCE(e.short_description,'')) LIKE '%exposici%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%exhibici%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%galeria%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%museo%'
                      OR e.category_id IN (SELECT id FROM categories_events WHERE LOWER(name) LIKE '%exposici%' OR LOWER(name) LIKE '%arte%'))",
        'labels' => ['es'=>'Exposiciones y arte','en'=>'Exhibitions & art','fr'=>'Expositions et art','de'=>'Ausstellungen und Kunst','zh'=>'展览与艺术'],
        'icon'   => '🎨', 'group' => 'categoria',
    ],
    'gastronomia' => [
        'sql'    => "(LOWER(COALESCE(e.short_description,'')) LIKE '%gastronom%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%vino%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%feria%gastro%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%culinari%'
                      OR e.category_id IN (SELECT id FROM categories_events WHERE LOWER(name) LIKE '%gastro%' OR LOWER(name) LIKE '%cocina%'))",
        'labels' => ['es'=>'Gastronomía y vinos','en'=>'Food & wine','fr'=>'Gastronomie et vins','de'=>'Gastronomie und Weine','zh'=>'美食与葡萄酒'],
        'icon'   => '🍷', 'group' => 'categoria',
    ],
    'tradiciones' => [
        'sql'    => "(LOWER(COALESCE(e.short_description,'')) LIKE '%tradici%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%folklore%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%romeria%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%popular%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%medieval%'
                      OR e.category_id IN (SELECT id FROM categories_events WHERE LOWER(name) LIKE '%tradici%' OR LOWER(name) LIKE '%folklore%'))",
        'labels' => ['es'=>'Tradiciones y folklore','en'=>'Traditions & folklore','fr'=>'Traditions et folklore','de'=>'Traditionen und Folklore','zh'=>'传统与民俗'],
        'icon'   => '🎪', 'group' => 'categoria',
    ],
    'mercados' => [
        'sql'    => "(LOWER(COALESCE(e.short_description,'')) LIKE '%mercado%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%artesania%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%artesanía%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%feria%artesa%'
                      OR e.category_id IN (SELECT id FROM categories_events WHERE LOWER(name) LIKE '%mercado%'))",
        'labels' => ['es'=>'Mercados y artesanía','en'=>'Markets & crafts','fr'=>'Marchés et artisanat','de'=>'Märkte und Kunsthandwerk','zh'=>'集市与手工艺'],
        'icon'   => '🛖', 'group' => 'categoria',
    ],
    'infantil' => [
        'sql'    => "(LOWER(COALESCE(e.target_audience,'')) LIKE '%ni%'
                      OR LOWER(COALESCE(e.target_audience,'')) LIKE '%famili%'
                      OR LOWER(COALESCE(e.target_audience,'')) LIKE '%infant%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%familiar%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%niños%'
                      OR e.category_id IN (SELECT id FROM categories_events WHERE LOWER(name) LIKE '%infant%' OR LOWER(name) LIKE '%famili%'))",
        'labels' => ['es'=>'Familiar e infantil','en'=>'Family & children','fr'=>'Famille et enfants','de'=>'Familie und Kinder','zh'=>'家庭与儿童'],
        'icon'   => '👨‍👩‍👧', 'group' => 'categoria',
    ],
    'naturaleza' => [
        'sql'    => "(LOWER(COALESCE(e.short_description,'')) LIKE '%naturaleza%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%senderismo%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%rutas%'
                      OR LOWER(COALESCE(e.short_description,'')) LIKE '%medioambiente%'
                      OR e.category_id IN (SELECT id FROM categories_events WHERE LOWER(name) LIKE '%naturalez%' OR LOWER(name) LIKE '%medio%'))",
        'labels' => ['es'=>'Naturaleza y rutas','en'=>'Nature & hiking','fr'=>'Nature et randonnées','de'=>'Natur und Wandern','zh'=>'自然与徒步'],
        'icon'   => '🌿', 'group' => 'categoria',
    ],

    // ── PRECIO (group='precio') ───────────────────────────────────────────────
    'gratuitos' => [
        'sql'    => "e.is_free = 1",
        'labels' => ['es'=>'Eventos gratuitos','en'=>'Free events','fr'=>'Événements gratuits','de'=>'Kostenlose Veranstaltungen','zh'=>'免费活动'],
        'icon'   => '🎁', 'group' => 'precio',
    ],
    'de-pago' => [
        'sql'    => "(e.is_free = 0 OR e.ticket_price > 0)",
        'labels' => ['es'=>'Con entrada','en'=>'Ticketed events','fr'=>'Avec entrée payante','de'=>'Kostenpflichtige Veranstaltungen','zh'=>'付费活动'],
        'icon'   => '🎟️', 'group' => 'precio',
    ],

    // ── TEMPORADA (group='temporada') ─────────────────────────────────────────
    // Los filtros de temporada usan BOTH start_date Y end_date para capturar
    // eventos que empiezan en la temporada O que la abarcan (multi-semana).
    // La condición >= CURDATE() ya está en getLandingEventos/Stats, aquí solo
    // filtramos por mes/temporada.
    'primavera' => [
        'sql'    => "(MONTH(e.start_date) IN (3, 4, 5) OR (e.end_date IS NOT NULL AND e.end_date != '0000-00-00' AND MONTH(e.end_date) IN (3, 4, 5)))",
        'labels' => ['es'=>'Eventos de primavera','en'=>'Spring events','fr'=>'Événements de printemps','de'=>'Frühlingsveranstaltungen','zh'=>'春季活动'],
        'icon'   => '🌸', 'group' => 'temporada',
    ],
    'verano' => [
        'sql'    => "(MONTH(e.start_date) IN (6, 7, 8) OR (e.end_date IS NOT NULL AND e.end_date != '0000-00-00' AND MONTH(e.end_date) IN (6, 7, 8)))",
        'labels' => ['es'=>'Eventos de verano','en'=>'Summer events','fr'=>'Événements d\'été','de'=>'Sommerveranstaltungen','zh'=>'夏季活动'],
        'icon'   => '☀️', 'group' => 'temporada',
    ],
    'otono' => [
        'sql'    => "(MONTH(e.start_date) IN (9, 10, 11) OR (e.end_date IS NOT NULL AND e.end_date != '0000-00-00' AND MONTH(e.end_date) IN (9, 10, 11)))",
        'labels' => ['es'=>'Eventos de otoño','en'=>'Autumn events','fr'=>'Événements d\'automne','de'=>'Herbstveranstaltungen','zh'=>'秋季活动'],
        'icon'   => '🍂', 'group' => 'temporada',
    ],
    'invierno' => [
        'sql'    => "(MONTH(e.start_date) IN (12, 1, 2) OR (e.end_date IS NOT NULL AND e.end_date != '0000-00-00' AND MONTH(e.end_date) IN (12, 1, 2)))",
        'labels' => ['es'=>'Eventos de invierno','en'=>'Winter events','fr'=>'Événements d\'hiver','de'=>'Winterveranstaltungen','zh'=>'冬季活动'],
        'icon'   => '❄️', 'group' => 'temporada',
    ],
    'este-mes' => [
        'sql'    => "(MONTH(e.start_date) = MONTH(CURDATE()) AND YEAR(e.start_date) = YEAR(CURDATE()))",
        'labels' => ['es'=>'Este mes','en'=>'This month','fr'=>'Ce mois-ci','de'=>'Diesen Monat','zh'=>'本月'],
        'icon'   => '📅', 'group' => 'temporada',
        'sitemap'=> false, // Excluido del sitemap: contenido varía constantemente
    ],
];

/**
 * Parsea el slug de la URL y devuelve provincia + array de filtros.
 *
 * Algoritmo greedy:
 *   1. Detecta la provincia al final del slug.
 *   2. Sobre el resto, aplica matching greedy (más largo primero) para extraer filtros.
 *
 * @param  string $slug  Segmento URL normalizado (minúsculas, solo a-z0-9-)
 * @return array{province:string|null, filters:string[], valid:bool, original:string}
 */
function parseEventosLandingSlug(string $slug): array
{
    $slug      = strtolower(trim($slug));
    $province  = null;
    $filters   = [];
    $remaining = $slug;

    // 1. Detectar provincia — al final del slug
    $provinceKeys = array_keys(EVENTOS_PROVINCIAS);
    // Ordenar por longitud descendente para evitar matching parcial
    usort($provinceKeys, static fn($a, $b) => strlen($b) - strlen($a));

    foreach ($provinceKeys as $pk) {
        if ($slug === $pk) {
            $province  = $pk;
            $remaining = '';
            break;
        }
        if (str_ends_with($slug, '-' . $pk)) {
            $province  = $pk;
            $remaining = substr($slug, 0, -(strlen($pk) + 1));
            break;
        }
    }

    // 2. Extraer filtros del segmento restante (greedy, más largo primero)
    if (!empty($remaining)) {
        $filterKeys = array_keys(EVENTOS_FILTROS);
        usort($filterKeys, static fn($a, $b) => strlen($b) - strlen($a));

        while (!empty($remaining)) {
            $matched = false;
            foreach ($filterKeys as $fk) {
                if ($remaining === $fk || str_starts_with($remaining, $fk . '-')) {
                    $filters[] = $fk;
                    $remaining = ltrim(substr($remaining, strlen($fk)), '-');
                    $matched   = true;
                    break;
                }
            }
            if (!$matched) break;
        }
    }

    $valid = ($province !== null || !empty($filters));

    return [
        'province' => $province,
        'filters'  => $filters,
        'valid'    => $valid,
        'original' => $slug,
    ];
}
