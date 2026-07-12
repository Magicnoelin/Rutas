<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  MAPA CANÓNICO DE PROVINCIAS Y FILTROS — rutasrurales.io
 *  Sistema de Landings Long-Tail para /alojamientos/{filtros}-{provincia}
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Cada provincia incluye:
 *    'label'       → nombre oficial (con tildes) para mostrar en UI
 *    'db'          → valor EXACTO en columna `province` de la tabla accommodations
 *    'attractions' → array de 3-4 atractivos principales (para intro texto SEO)
 *    'vibe'        → descripción corta por idioma para intro dinámico
 *
 *  Cada filtro incluye:
 *    'sql'    → condición SQL raw (sin input de usuario, 100% seguro)
 *    'labels' → etiqueta por idioma (para H1 dinámico, breadcrumb, meta)
 *    'icon'   → emoji decorativo
 *    'order'  → 1=tipo principal, 2=característica secundaria
 */

// ─── PROVINCIAS ───────────────────────────────────────────────────────────────
const LANDING_PROVINCIAS = [

    // Castilla y León
    'soria' => [
        'label' => 'Soria', 'db' => 'Soria',
        'attractions' => ['Cañón del Río Lobos', 'Lagunas de Urbión', 'Numancia', 'Sierra de Cebollera'],
        'vibe' => [
            'es' => 'una de las provincias más tranquilas y auténticas de España, tierra de pinos centenarios, cañones esculpidos por el Duero y un cielo de estrellas sin contaminar',
            'en' => 'one of Spain\'s most peaceful and authentic provinces, land of ancient pine forests, Duero gorges and unpolluted starry skies',
            'fr' => 'l\'une des provinces les plus tranquilles et authentiques d\'Espagne, terre de pinèdes centenaires et de canyons sculptés par le Duero',
            'de' => 'eine der ruhigsten und authentischsten Provinzen Spaniens, Land uralter Pinienwälder und vom Duero geformter Canyons',
            'zh' => '西班牙最宁静、最原始的省份之一，古老松林、杜罗河峡谷和璀璨星空构成其独特魅力',
        ],
    ],
    'zamora' => [
        'label' => 'Zamora', 'db' => 'Zamora',
        'attractions' => ['Lago de Sanabria', 'Arribes del Duero', 'Catedral de Zamora', 'Sierra de la Culebra'],
        'vibe' => [
            'es' => 'tierra de contrastes donde el Duero forma arribes imponentes, el lago de Sanabria refresca los veranos y el románico medieval florece en cada pueblo',
            'en' => 'a land of contrasts where the Duero carves imposing gorges, Lake Sanabria cools summer days, and medieval Romanesque art blooms in every village',
            'fr' => 'une terre de contrastes où le Duero forme des gorges imposantes, le lac de Sanabria rafraîchit les étés et l\'art roman médiéval fleurit partout',
            'de' => 'ein Land der Kontraste, wo der Duero beeindruckende Schluchten formt und mittelalterliche Romanik in jedem Dorf blüht',
            'zh' => '充满对比的土地：杜罗河形成壮阔峡谷，萨纳布里亚湖清凉夏日，罗马式建筑点缀每个村庄',
        ],
    ],
    'salamanca' => [
        'label' => 'Salamanca', 'db' => 'Salamanca',
        'attractions' => ['Sierra de Francia', 'Las Batuecas', 'Peña de Francia', 'La Alberca (Patrimonio)'],
        'vibe' => [
            'es' => 'una provincia donde la ciudad universitaria más antigua de España convive con sierras vírgenes, pueblos medievales declarados Patrimonio y una gastronomía de leyenda',
            'en' => 'a province where Spain\'s oldest university city coexists with virgin mountain ranges, medieval Heritage villages and legendary gastronomy',
            'fr' => 'une province où la plus ancienne ville universitaire d\'Espagne côtoie des sierras vierges et des villages médiévaux classés au Patrimoine',
            'de' => 'eine Provinz, wo Spaniens älteste Universitätsstadt auf unberührte Gebirge und mittelalterliche Dörfer trifft',
            'zh' => '西班牙最古老大学城与原始山脉、中世纪遗产村庄和传奇美食共存的省份',
        ],
    ],
    'burgos' => [
        'label' => 'Burgos', 'db' => 'Burgos',
        'attractions' => ['Sierra de la Demanda', 'Cañón del Ebro', 'Atapuerca', 'Camino de Santiago'],
        'vibe' => [
            'es' => 'cuna del Cid Campeador y puerta del Camino de Santiago, con la Sierra de la Demanda siempre nevada y valles de una belleza que corta la respiración',
            'en' => 'birthplace of El Cid and gateway to the Camino de Santiago, with the always snow-capped Sierra de la Demanda and breathtaking valleys',
            'fr' => 'berceau du Cid et porte du Chemin de Saint-Jacques, avec la Sierra de la Demanda enneigée et des vallées d\'une beauté à couper le souffle',
            'de' => 'Geburtsort von El Cid und Tor zum Jakobsweg, mit der immer verschneiten Sierra de la Demanda und atemberaubenden Tälern',
            'zh' => '熙德的故乡和圣地亚哥朝圣之路的大门，德曼达山脉白雪皑皑，山谷美景令人屏息',
        ],
    ],
    'leon' => [
        'label' => 'León', 'db' => 'León',
        'attractions' => ['Picos de Europa', 'Babia', 'Las Médulas', 'Camino de Santiago'],
        'vibe' => [
            'es' => 'una provincia monumental donde los Picos de Europa tocan las nubes, Las Médulas muestran el oro romano y el Camino de Santiago cruza valles eternos',
            'en' => 'a monumental province where the Picos de Europa touch the clouds, Las Médulas reveal Roman gold and the Camino de Santiago crosses eternal valleys',
            'fr' => 'une province monumentale où les Picos de Europa touchent les nuages, Las Médulas révèlent l\'or romain et le Chemin de Santiago traverse des vallées éternelles',
            'de' => 'eine monumentale Provinz, wo die Picos de Europa die Wolken berühren, Las Médulas römisches Gold zeigen und der Jakobsweg ewige Täler durchquert',
            'zh' => '欧罗巴峰触碰云端，梅杜拉斯揭示罗马黄金，圣地亚哥之路穿越永恒山谷的壮丽省份',
        ],
    ],
    'valladolid' => [
        'label' => 'Valladolid', 'db' => 'Valladolid',
        'attractions' => ['Ribera del Duero', 'Torozos', 'Medina del Campo', 'Peñafiel'],
        'vibe' => [
            'es' => 'corazón vitícola de Castilla, con viñedos de Ribera del Duero que producen algunos de los mejores vinos del mundo y castillos medievales en cada horizonte',
            'en' => 'the wine-growing heart of Castile, with Ribera del Duero vineyards producing some of the world\'s finest wines and medieval castles on every horizon',
            'fr' => 'le cœur viticole de Castille, avec les vignobles de la Ribera del Duero et des châteaux médiévaux à l\'horizon',
            'de' => 'das Weinherz Kastiliens, mit Ribera del Duero Weinbergen und mittelalterlichen Burgen an jedem Horizont',
            'zh' => '卡斯蒂利亚的葡萄酒之心，杜罗河岸葡萄园出产世界顶级佳酿，中世纪城堡点缀地平线',
        ],
    ],
    'palencia' => [
        'label' => 'Palencia', 'db' => 'Palencia',
        'attractions' => ['Fuentes Carrionas', 'Canal de Castilla', 'Frómista (Camino)', 'Cardaño de Arriba'],
        'vibe' => [
            'es' => 'la joya oculta de Castilla, con las montañas de Fuentes Carrionas reservadas para la fauna salvaje y el Canal de Castilla como ruta ciclista sin igual',
            'en' => 'Castile\'s hidden gem, with the Fuentes Carrionas mountains reserved for wildlife and the Canal de Castilla as an unparalleled cycling route',
            'fr' => 'le joyau caché de Castille, avec les montagnes de Fuentes Carrionas et le Canal de Castille comme itinéraire cycliste incomparable',
            'de' => 'das verborgene Juwel Kastiliens, mit den Fuentes Carrionas Bergen für Wildtiere und dem Canal de Castilla als unvergleichliche Fahrradroute',
            'zh' => '卡斯蒂利亚的隐秘珍宝，卡里奥纳斯源头山区野生动物聚集，卡斯蒂利亚运河是无与伦比的骑行路线',
        ],
    ],
    'segovia' => [
        'label' => 'Segovia', 'db' => 'Segovia',
        'attractions' => ['Sierra de Guadarrama', 'Acueducto Romano', 'Pedraza', 'La Granja de San Ildefonso'],
        'vibe' => [
            'es' => 'una provincia que desafía el tiempo, donde el mejor cochinillo del mundo se sirve bajo el arco de un acueducto romano y la Sierra de Guadarrama es parque nacional',
            'en' => 'a province that defies time, where the world\'s best roast suckling pig is served under a Roman aqueduct arch and Guadarrama is a national park',
            'fr' => 'une province qui défie le temps, où le meilleur cochon de lait du monde est servi sous l\'arc d\'un aqueduc romain',
            'de' => 'eine Provinz, die der Zeit trotzt, wo das beste Spanferkel der Welt unter einem römischen Aquäduktbogen serviert wird',
            'zh' => '穿越时空的省份，世界最佳烤乳猪在罗马渡槽拱门下上桌，瓜达拉马山脉是国家公园',
        ],
    ],
    'avila' => [
        'label' => 'Ávila', 'db' => 'Ávila',
        'attractions' => ['Muralla Medieval de Ávila', 'Sierra de Gredos', 'Valle del Tiétar', 'Toros de Guisando'],
        'vibe' => [
            'es' => 'la ciudad amurallada más alta de Europa con la Sierra de Gredos como telón de fondo, tierra de Santa Teresa y de una naturaleza exuberante a menos de 2h de Madrid',
            'en' => 'Europe\'s highest walled city with the Sierra de Gredos as backdrop, land of Saint Teresa and exuberant nature less than 2h from Madrid',
            'fr' => 'la plus haute ville fortifiée d\'Europe avec la Sierra de Gredos en toile de fond, terre de Sainte Thérèse et d\'une nature luxuriante',
            'de' => 'Europas höchste ummauerte Stadt mit der Sierra de Gredos als Kulisse, Land der Heiligen Teresa und üppiger Natur',
            'zh' => '欧洲海拔最高的城墙城市，格雷多斯山脉为背景，圣特蕾莎的故乡，距马德里不足2小时',
        ],
    ],
    'guadalajara' => [
        'label' => 'Guadalajara', 'db' => 'Guadalajara',
        'attractions' => ['Serranía de Cuenca (Guadalajara)', 'Sigüenza', 'Hayedo de Tejera Negra', 'Alto Tajo'],
        'vibe' => [
            'es' => 'el secreto mejor guardado de la Meseta, con el hayedo de Tejera Negra (el más grande de España), el Alto Tajo Parque Natural y la ciudad medieval de Sigüenza',
            'en' => 'the Meseta\'s best kept secret, with Spain\'s largest beech forest, the Alto Tajo Natural Park and the medieval city of Sigüenza',
            'fr' => 'le mieux gardé secret de la Meseta, avec la plus grande hêtraie d\'Espagne, le Parc Naturel de l\'Alto Tajo et la ville médiévale de Sigüenza',
            'de' => 'das bestgehütete Geheimnis der Meseta, mit Spaniens größtem Buchenwald, dem Naturpark Alto Tajo und der mittelalterlichen Stadt Sigüenza',
            'zh' => '梅塞塔高原最神秘的角落：西班牙最大山毛榉林、塔霍河上游自然公园和中世纪城市希圭恩萨',
        ],
    ],
    'cuenca' => [
        'label' => 'Cuenca', 'db' => 'Cuenca',
        'attractions' => ['Ciudad Encantada', 'Serranía de Cuenca', 'Casas Colgadas', 'Nacimiento del Río Cuervo'],
        'vibe' => [
            'es' => 'tierra de paisajes imposibles, donde las casas colgantes desafían la gravedad sobre el Júcar, la Ciudad Encantada forma figuras de piedra y el río Cuervo nace entre helechos',
            'en' => 'a land of impossible landscapes where hanging houses defy gravity over the Júcar, the Enchanted City forms stone figures and the Cuervo river springs among ferns',
            'fr' => 'une terre de paysages impossibles, où les maisons suspendues défient la gravité et la Cité Enchantée forme des figures de pierre',
            'de' => 'ein Land unmöglicher Landschaften, wo hängende Häuser die Schwerkraft über dem Júcar trotzen und die Verzauberte Stadt Steinfiguren formt',
            'zh' => '不可思议的风景之地：悬空屋俯瞰胡卡河，魔法城形成石头图案，库埃尔沃河源头蕨类丛生',
        ],
    ],
    'ourense' => [
        'label' => 'Ourense', 'db' => 'Ourense',
        'attractions' => ['Termas Ourensanas', 'Ribeira Sacra', 'Cañón del Sil', 'O Invernadeiro'],
        'vibe' => [
            'es' => 'la provincia de las aguas termales gratuitas, los viñedos verticales de la Ribeira Sacra sobre el Sil y una arquitectura rural gallega sin igual',
            'en' => 'the province of free thermal baths, the vertical vineyards of Ribeira Sacra above the Sil river and unparalleled Galician rural architecture',
            'fr' => 'la province des bains thermaux gratuits, des vignobles verticaux de la Ribeira Sacra et d\'une architecture rurale galicienne incomparable',
            'de' => 'die Provinz der kostenlosen Thermalbäder, der vertikalen Weinberge der Ribeira Sacra und unvergleichlicher galizischer Landarchitektur',
            'zh' => '免费温泉浴之省，锡尔河畔里贝拉萨克拉垂直葡萄园和无与伦比的加利西亚乡村建筑',
        ],
    ],
];

// ─── FILTROS ──────────────────────────────────────────────────────────────────
// 'sql'    → condición SQL raw (valores hardcoded, nunca user-input → SQL injection imposible)
// 'labels' → etiqueta visible por idioma
// 'icon'   → emoji para UI
// 'order'  → 1=tipo principal, 2=característica; afecta al ORDER BY de la query
const LANDING_FILTROS = [

    // ── Tipos de alojamiento (order=1) ────────────────────────────────────────
    'casas-rurales' => [
        'sql'    => "(LOWER(COALESCE(a.accommodation_type,'')) LIKE '%casa%' OR LOWER(COALESCE(c.name,'')) LIKE '%casa%')",
        'labels' => ['es'=>'Casas rurales','en'=>'Rural houses','fr'=>'Maisons rurales','de'=>'Landhäuser','zh'=>'乡村民宿'],
        'icon'   => '🏡', 'order' => 1,
    ],
    'apartamentos-rurales' => [
        'sql'    => "(LOWER(COALESCE(a.accommodation_type,'')) LIKE '%apart%' OR LOWER(COALESCE(c.name,'')) LIKE '%apart%')",
        'labels' => ['es'=>'Apartamentos rurales','en'=>'Rural apartments','fr'=>'Appartements ruraux','de'=>'Landapartments','zh'=>'乡村公寓'],
        'icon'   => '🏠', 'order' => 1,
    ],
    'turismo-rural' => [
        'sql'    => "1=1",
        'labels' => ['es'=>'Turismo rural','en'=>'Rural tourism','fr'=>'Tourisme rural','de'=>'Landurlaub','zh'=>'乡村旅游'],
        'icon'   => '🌿', 'order' => 1,
    ],
    'hoteles-rurales' => [
        'sql'    => "(LOWER(COALESCE(a.accommodation_type,'')) LIKE '%hotel%' OR LOWER(COALESCE(c.name,'')) LIKE '%hotel%')",
        'labels' => ['es'=>'Hoteles rurales','en'=>'Rural hotels','fr'=>'Hôtels ruraux','de'=>'Landhotels','zh'=>'乡村酒店'],
        'icon'   => '🏨', 'order' => 1,
    ],
    'posadas-rurales' => [
        'sql'    => "(LOWER(COALESCE(a.accommodation_type,'')) LIKE '%posada%' OR LOWER(COALESCE(c.name,'')) LIKE '%posada%')",
        'labels' => ['es'=>'Posadas rurales','en'=>'Rural inns','fr'=>'Auberges rurales','de'=>'Landgasthäuser','zh'=>'乡村客栈'],
        'icon'   => '🏯', 'order' => 1,
    ],

    // ── Características (order=2) ─────────────────────────────────────────────
    'con-chimenea' => [
        'sql'    => "(a.amenities LIKE '%chimenea%' OR a.amenities LIKE '%fireplace%' OR a.description LIKE '%chimenea%')",
        'labels' => ['es'=>'con chimenea','en'=>'with fireplace','fr'=>'avec cheminée','de'=>'mit Kamin','zh'=>'带壁炉'],
        'icon'   => '🔥', 'order' => 2,
    ],
    'con-piscina' => [
        // Excluimos "piscina natural"/"piscinas naturales" de description: son pozas de río,
        // no piscinas propias del alojamiento. amenities no existe en la BD → solo description.
        'sql'    => "(a.description LIKE '%piscina%' AND a.description NOT LIKE '%piscina natural%' AND a.description NOT LIKE '%piscinas naturales%')",
        'labels' => ['es'=>'con piscina','en'=>'with pool','fr'=>'avec piscine','de'=>'mit Pool','zh'=>'带游泳池'],
        'icon'   => '🏊', 'order' => 2,
    ],
    'con-mascotas' => [
        'sql'    => "a.pet_friendly = 1",
        'labels' => ['es'=>'para mascotas','en'=>'pet-friendly','fr'=>'pour animaux','de'=>'haustierfreundlich','zh'=>'宠物友好'],
        'icon'   => '🐾', 'order' => 2,
    ],
    // alias sin "con-" — solo para compatibilidad de URLs antiguas
    // sitemap => false: se excluye del sitemap (URL canónica es "con-mascotas")
    'mascotas' => [
        'sql'     => "a.pet_friendly = 1",
        'labels'  => ['es'=>'para mascotas','en'=>'pet-friendly','fr'=>'pour animaux','de'=>'haustierfreundlich','zh'=>'宠物友好'],
        'icon'    => '🐾', 'order' => 2,
        'sitemap' => false, // excluido del sitemap: URL canónica = con-mascotas-{provincia}
    ],
    // La columna `wifi` no existe en la BD; se detecta vía el campo `amenities`
    'con-wifi' => [
        'sql'    => "(a.amenities LIKE '%wifi%' OR a.amenities LIKE '%WiFi%' OR a.amenities LIKE '%wi-fi%')",
        'labels' => ['es'=>'con WiFi','en'=>'with WiFi','fr'=>'avec WiFi','de'=>'mit WLAN','zh'=>'含WiFi'],
        'icon'   => '📶', 'order' => 2,
    ],
    'para-ninos' => [
        'sql'    => "a.suitable_for_children = 1",
        'labels' => ['es'=>'para niños','en'=>'child-friendly','fr'=>'pour enfants','de'=>'kinderfreundlich','zh'=>'亲子友好'],
        'icon'   => '👨‍👩‍👧', 'order' => 2,
    ],
    'romantico' => [
        'sql'    => "(a.description LIKE '%romántico%' OR a.description LIKE '%romantico%' OR a.description LIKE '%pareja%' OR a.amenities LIKE '%jacuzzi%')",
        'labels' => ['es'=>'románticos','en'=>'romantic','fr'=>'romantiques','de'=>'romantisch','zh'=>'浪漫'],
        'icon'   => '💑', 'order' => 2,
    ],
    'con-jacuzzi' => [
        'sql'    => "(a.amenities LIKE '%jacuzzi%' OR a.amenities LIKE '%bañera%' OR a.description LIKE '%jacuzzi%')",
        'labels' => ['es'=>'con jacuzzi','en'=>'with jacuzzi','fr'=>'avec jacuzzi','de'=>'mit Jacuzzi','zh'=>'带按摩浴缸'],
        'icon'   => '♨️', 'order' => 2,
    ],
    'con-barbacoa' => [
        'sql'    => "(a.amenities LIKE '%barbacoa%' OR a.amenities LIKE '%barbecue%' OR a.description LIKE '%barbacoa%')",
        'labels' => ['es'=>'con barbacoa','en'=>'with barbecue','fr'=>'avec barbecue','de'=>'mit Grill','zh'=>'带烧烤'],
        'icon'   => '🍖', 'order' => 2,
    ],
    'con-terraza' => [
        'sql'    => "(a.amenities LIKE '%terraza%' OR a.amenities LIKE '%balcon%' OR a.description LIKE '%terraza%')",
        'labels' => ['es'=>'con terraza','en'=>'with terrace','fr'=>'avec terrasse','de'=>'mit Terrasse','zh'=>'带露台'],
        'icon'   => '🌅', 'order' => 2,
    ],
    'con-jardin' => [
        'sql'    => "(a.amenities LIKE '%jardín%' OR a.amenities LIKE '%jardin%' OR a.description LIKE '%jardín%')",
        'labels' => ['es'=>'con jardín','en'=>'with garden','fr'=>'avec jardin','de'=>'mit Garten','zh'=>'带花园'],
        'icon'   => '🌳', 'order' => 2,
    ],
    'con-parking' => [
        'sql'    => "(a.amenities LIKE '%parking%' OR a.amenities LIKE '%aparcamiento%' OR a.description LIKE '%parking%')",
        'labels' => ['es'=>'con parking','en'=>'with parking','fr'=>'avec parking','de'=>'mit Parkplatz','zh'=>'有停车场'],
        'icon'   => '🅿️', 'order' => 2,
    ],
    'con-cocina' => [
        'sql'    => "(a.kitchen_available = 1 OR a.amenities LIKE '%cocina%')",
        'labels' => ['es'=>'con cocina equipada','en'=>'with full kitchen','fr'=>'avec cuisine équipée','de'=>'mit Küche','zh'=>'含厨房'],
        'icon'   => '🍳', 'order' => 2,
    ],
    'baratos' => [
        'sql'    => "(a.price_per_night > 0 AND a.price_per_night <= 75)",
        'labels' => ['es'=>'baratos','en'=>'budget-friendly','fr'=>'économiques','de'=>'günstig','zh'=>'经济实惠'],
        'icon'   => '💰', 'order' => 2,
    ],
    'grandes-grupos' => [
        'sql'    => "a.capacity >= 8",
        'labels' => ['es'=>'para grupos grandes','en'=>'for large groups','fr'=>'pour grands groupes','de'=>'für Gruppen','zh'=>'大团体适用'],
        'icon'   => '👥', 'order' => 2,
    ],
    'accesibles' => [
        'sql'    => "(a.amenities LIKE '%accesible%' OR a.amenities LIKE '%adaptad%' OR a.description LIKE '%accesib%')",
        'labels' => ['es'=>'accesibles','en'=>'accessible','fr'=>'accessibles','de'=>'barrierefrei','zh'=>'无障碍设施'],
        'icon'   => '♿', 'order' => 2,
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
function parseLandingSlug(string $slug): array
{
    $slug     = strtolower(trim($slug));
    $province = null;
    $filters  = [];
    $remaining = $slug;

    // 1. Detectar provincia — al final del slug
    $provinceKeys = array_keys(LANDING_PROVINCIAS);
    // Ordenar por longitud descendente para evitar matching parcial
    usort($provinceKeys, static fn($a, $b) => strlen($b) - strlen($a));

    foreach ($provinceKeys as $pk) {
        if ($slug === $pk) {
            // Slug es solo una provincia (sin filtros)
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
        $filterKeys = array_keys(LANDING_FILTROS);
        usort($filterKeys, static fn($a, $b) => strlen($b) - strlen($a));

        while (!empty($remaining)) {
            $matched = false;
            foreach ($filterKeys as $fk) {
                // Coincidencia exacta o prefijo seguido de guión
                if ($remaining === $fk || str_starts_with($remaining, $fk . '-')) {
                    $filters[] = $fk;
                    $remaining = ltrim(substr($remaining, strlen($fk)), '-');
                    $matched   = true;
                    break;
                }
            }
            if (!$matched) {
                // Segmento no reconocido → invalidar
                break;
            }
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
