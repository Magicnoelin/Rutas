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
 *    'region'      → comunidad autónoma (para SEO y estructura)
 *
 *  Cada filtro incluye:
 *    'sql'    → condición SQL raw (sin input de usuario, 100% seguro)
 *    'labels' → etiqueta por idioma (para H1 dinámico, breadcrumb, meta)
 *    'icon'   → emoji decorativo
 *    'order'  → 1=tipo principal, 2=característica secundaria
 */

// ════════════════════════════════════════════════════════════════════════════
// PROVINCIAS CON ALOJAMIENTOS ACTIVOS EN LA BASE DE DATOS
// (solo las que tienen registros reales para evitar páginas vacías)
// ════════════════════════════════════════════════════════════════════════════
const LANDING_PROVINCIAS = [

    // ─────────────────────────────────────────────────────────────────────
    // CASTILLA Y LEÓN (9 provincias)
    // ─────────────────────────────────────────────────────────────────────
    'avila' => [
        'label' => 'Ávila', 'db' => 'Avila',
        'region' => 'Castilla y León',
        'attractions' => ['Muralla Medieval de Ávila', 'Sierra de Gredos', 'Valle del Tiétar', 'Toros de Guisando'],
        'vibe' => [
            'es' => 'la ciudad amurallada más alta de Europa con la Sierra de Gredos como telón de fondo, tierra de Santa Teresa y de una naturaleza exuberante a menos de 2h de Madrid',
            'en' => 'Europe\'s highest walled city with the Sierra de Gredos as backdrop, land of Saint Teresa and exuberant nature less than 2h from Madrid',
            'fr' => 'la plus haute ville fortifiée d\'Europe avec la Sierra de Gredos en toile de fond, terre de Sainte Thérèse et d\'une nature luxuriante',
            'de' => 'Europas höchste ummauerte Stadt mit der Sierra de Gredos als Kulisse, Land der Heiligen Teresa und üppiger Natur',
            'zh' => '欧洲海拔最高的城墙城市，格雷多斯山脉为背景，圣特蕾莎的故乡，距马德里不足2小时',
        ],
    ],
    'burgos' => [
        'label' => 'Burgos', 'db' => 'Burgos',
        'region' => 'Castilla y León',
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
        'region' => 'Castilla y León',
        'attractions' => ['Picos de Europa', 'Babia', 'Las Médulas', 'Camino de Santiago'],
        'vibe' => [
            'es' => 'una provincia monumental donde los Picos de Europa tocan las nubes, Las Médulas muestran el oro romano y el Camino de Santiago cruza valles eternos',
            'en' => 'a monumental province where the Picos de Europa touch the clouds, Las Médulas reveal Roman gold and the Camino de Santiago crosses eternal valleys',
            'fr' => 'une province monumentale où les Picos de Europa touchent les nuages, Las Médulas révèlent l\'or romain et le Chemin de Santiago traverse des vallées éternelles',
            'de' => 'eine monumentale Provincia, wo die Picos de Europa die Wolken berühren, Las Médulas römisches Gold zeigen und der Jakobsweg ewige Täler durchquert',
            'zh' => '欧罗巴峰触碰云端，梅杜拉斯揭示罗马黄金，圣地亚哥之路穿越永恒山谷的壮丽省份',
        ],
    ],
    'palencia' => [
        'label' => 'Palencia', 'db' => 'Palencia',
        'region' => 'Castilla y León',
        'attractions' => ['Fuentes Carrionas', 'Canal de Castilla', 'Frómista (Camino)', 'Cardaño de Arriba'],
        'vibe' => [
            'es' => 'la joya oculta de Castilla, con las montañas de Fuentes Carrionas reservadas para la fauna salvaje y el Canal de Castilla como ruta ciclista sin igual',
            'en' => 'Castile\'s hidden gem, with the Fuentes Carrionas mountains reserved for wildlife and the Canal de Castilla as an unparalleled cycling route',
            'fr' => 'le joyau caché de Castille, avec les montagnes de Fuentes Carrionas et le Canal de Castille comme itinéraire cycliste incomparable',
            'de' => 'das verborgene Juwel Kastiliens, mit den Fuentes Carrionas Bergen für Wildtiere und dem Canal de Castilla als unvergleichliche Fahrradroute',
            'zh' => '卡斯蒂利亚的隐秘珍宝，卡里奥纳斯源头山区野生动物聚集，卡斯蒂利亚运河是无与伦比的骑行路线',
        ],
    ],
    'salamanca' => [
        'label' => 'Salamanca', 'db' => 'Salamanca',
        'region' => 'Castilla y León',
        'attractions' => ['Sierra de Francia', 'Las Batuecas', 'Peña de Francia', 'La Alberca (Patrimonio)'],
        'vibe' => [
            'es' => 'una provincia donde la ciudad universitaria más antigua de España convive con sierras vírgenes, pueblos medievales declarados Patrimonio y una gastronomía de leyenda',
            'en' => 'a province where Spain\'s oldest university city coexists with virgin mountain ranges, medieval Heritage villages and legendary gastronomy',
            'fr' => 'une province où la plus ancienne ville universitaire d\'Espagne côtoie des sierras vierges et des villages médiévaux classés au Patrimoine',
            'de' => 'eine Provincia, wo Spaniens älteste Universitätsstadt auf unberührte Gebirge und mittelalterliche Dörfer trifft',
            'zh' => '西班牙最古老大学城与原始山脉、中世纪遗产村庄和传奇美食共存的省份',
        ],
    ],
    'segovia' => [
        'label' => 'Segovia', 'db' => 'Segovia',
        'region' => 'Castilla y León',
        'attractions' => ['Sierra de Guadarrama', 'Acueducto Romano', 'Pedraza', 'La Granja de San Ildefonso'],
        'vibe' => [
            'es' => 'una provincia que desafía el tiempo, donde el mejor cochinillo del mundo se sirve bajo el arco de un acueducto romano y la Sierra de Guadarrama es parque nacional',
            'en' => 'a province that defies time, where the world\'s best roast suckling pig is served under a Roman aqueduct arch and Guadarrama is a national park',
            'fr' => 'une province qui défie le temps, où le meilleur cochon de lait du monde est servi sous l\'arc d\'un aqueduc romain',
            'de' => 'eine Provincia, die der Zeit trotzt, wo das beste Spanferkel der Welt unter einem Römischen Aquäduktbogen serviert wird',
            'zh' => '穿越时空的省份，世界最佳烤乳猪在罗马渡槽拱门下上桌，瓜达拉马山脉是国家公园',
        ],
    ],
    'soria' => [
        'label' => 'Soria', 'db' => 'Soria',
        'region' => 'Castilla y León',
        'attractions' => ['Cañón del Río Lobos', 'Lagunas de Urbión', 'Numancia', 'Sierra de Cebollera'],
        'vibe' => [
            'es' => 'una de las provincias más tranquilas y auténticas de España, tierra de pinos centenarios, cañones esculpidos por el Duero y un cielo de estrellas sin contaminar',
            'en' => 'one of Spain\'s most peaceful and authentic provinces, land of ancient pine forests, Duero gorges and unpolluted starry skies',
            'fr' => 'l\'une des provinces les plus tranquilles et authentiques d\'Espagne, terre de pinèdes centenaires et de canyons sculptés par le Duero',
            'de' => 'eine der ruhigsten und authentischstenProvinzen Spaniens, Land uralter Pinienwälder und vom Duero geformter Canyons',
            'zh' => '西班牙最宁静、最原始的省份之一，古老松林、杜罗河峡谷和璀璨星空构成其独特魅力',
        ],
    ],
    'valladolid' => [
        'label' => 'Valladolid', 'db' => 'Valladolid',
        'region' => 'Castilla y León',
        'attractions' => ['Ribera del Duero', 'Torozos', 'Medina del Campo', 'Peñafiel'],
        'vibe' => [
            'es' => 'corazón vitícola de Castilla, con viñedos de Ribera del Duero que producen algunos de los mejores vinos del mundo y castillos medievales en cada horizonte',
            'en' => 'the wine-growing heart of Castile, with Ribera del Duero vineyards producing some of the world\'s finest wines and medieval castles on every horizon',
            'fr' => 'le cœur viticole de Castille, avec les vignobles de la Ribera du Duero et des châteaux médiévaux à l\'horizon',
            'de' => 'das Weinherz Kastiliens, mit Ribera del Duero Weinbergen und mittelalterlichen Burgen an jedem Horizont',
            'zh' => '卡斯蒂利亚的葡萄酒之心，杜罗河岸葡萄园出产世界顶级佳酿，中世纪城堡点缀地平线',
        ],
    ],
    'zamora' => [
        'label' => 'Zamora', 'db' => 'Zamora',
        'region' => 'Castilla y León',
        'attractions' => ['Lago de Sanabria', 'Arribes del Duero', 'Castillo de Zamora', 'Sierra de la Culebra'],
        'vibe' => [
            'es' => 'tierra de contrastes donde el Duero forma arribes imponentes, el lago de Sanabria refresca los veranos y el románico medieval florece en cada pueblo',
            'en' => 'a land of contrasts where the Duero carves imposing gorges, Lake Sanabria cools summer days, and medieval Romanesque art blooms in every village',
            'fr' => 'une terre de contrastes où le Duero forme des gorges imposantes, le lac de Sanabria rafraîchit les étés et l\'art roman médiéval fleurit partout',
            'de' => 'ein Land der Kontraste, wo der Duero beeindruckende Schluchten formt und mittelalterliche Romanik in jedem Dorf blüht',
            'zh' => '充满对比的土地：杜罗河形成壮阔峡谷，萨纳布里亚湖清凉夏日，罗马式建筑点缀每个村庄',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // GALICIA (4 provincias)
    // ─────────────────────────────────────────────────────────────────────
    'a-coruna' => [
        'label' => 'A Coruña', 'db' => 'A Coruña',
        'region' => 'Galicia',
        'attractions' => ['Torre de Hércules', 'Costa da Morte', 'Rías Altas', 'Santiago de Compostela'],
        'vibe' => [
            'es' => 'la provincia del faro romano más antiguo del mundo, donde la Costa da Morte guarda secretos de naufragios y las rías ofrecen las mejores ostras de Galicia',
            'en' => 'the province of the world\'s oldest Roman lighthouse, where the Costa da Morte hides shipwreck secrets and the rías offer the best oysters in Galicia',
            'fr' => 'la province du phare romain le plus ancien du monde, où la Costa da Morte garde des secrets de naufrages et les rías offrent les meilleures huîtres de Galice',
            'de' => 'die Provincia des ältesten romanischen Leuchtturms der Welt, wo die Costa da Morte Schiffswracks birgt und die Rías die besten Austern Galiziens bieten',
            'zh' => '拥有世界上最古老罗马灯塔的省份，死亡海岸藏着沉船秘密，河湾提供加利西亚最美味的牡蛎',
        ],
    ],
    'lugo' => [
        'label' => 'Lugo', 'db' => 'Lugo',
        'region' => 'Galicia',
        'attractions' => ['Muralla Romana de Lugo', 'Las Termas de Augustóbriga', 'Parque Natural dos Ancares', 'Ribeira Sacra'],
        'vibe' => [
            'es' => 'la provincia de la única muralla romana walkable del mundo, donde las termas romana siguen manando agua curativa y los Ancares esconden los paisajes más salvajes de Galicia',
            'en' => 'the province of the only walkable Roman wall in the world, where Roman thermal baths still flow with healing waters and the Ancares hide Galicia\'s wildest landscapes',
            'fr' => 'la province du seul mur romain praticable au monde, où les thermes romaines coulent toujours avec des eaux curatives et les Ancares cachent les paysages les plus sauvages de Galice',
            'de' => 'die Provincia der einzigen begehbaren Römermauer der Welt, wo Römische Thermen immer noch mit heilendem Wasser fließen und die Ancares Galiziens wildeste Landschaften verbergen',
            'zh' => '世界上唯一可步行的罗马城墙所在省份，罗马温泉仍然流淌着疗愈之水，安卡雷斯隐藏着加利西亚最原始的风景',
        ],
    ],
    'ourense' => [
        'label' => 'Ourense', 'db' => 'Ourense',
        'region' => 'Galicia',
        'attractions' => ['Termas Ourensanas', 'Ribeira Sacra', 'Cañón del Sil', 'O Invernadeiro'],
        'vibe' => [
            'es' => 'la provincia de las aguas termales gratuitas, los viñedos verticales de la Ribeira Sacra sobre el Sil y una arquitectura rural gallega sin igual',
            'en' => 'the province of free thermal baths, the vertical vineyards of Ribeira Sacra above the Sil river and unparalleled Galician rural architecture',
            'fr' => 'la province des bains thermaux gratuits, des vignobles verticaux de la Ribeira Sacra et d\'une architecture rurale galicienne incomparable',
            'de' => 'die Provincia der kostenlosen Thermalbäder, der vertikalen Weinberge der Ribeira Sacra und unvergleichlicher galizischer Landarchitektur',
            'zh' => '免费温泉浴之省，锡尔河畔里贝拉萨克拉垂直葡萄园和无与伦比的加利西亚乡村建筑',
        ],
    ],
    'pontevedra' => [
        'label' => 'Pontevedra', 'db' => 'Pontevedra',
        'region' => 'Galicia',
        'attractions' => ['Islas Cíes', 'Ría de Arousa', 'Ribeira Sacra', 'Pontevedra Old Town'],
        'vibe' => [
            'es' => 'la provincia de las rías más bellas de Galicia, las islas Cíes con sus playas de bandera azul y un casco antiguo pedestrianizado que es museo al aire libre',
            'en' => 'the province of Galicia\'s most beautiful rías, the Cías Islands with their blue flag beaches and a pedestrianized old town that is an open-air museum',
            'fr' => 'la province des plus belles rías de Galice, les îles Cíes avec leurs plages aux drapeau bleu et un vieux centre pedestrianisé qui est un musée en plein air',
            'de' => 'die Provincia der schönsten Rías Galiziens, die Cías-Inseln mit ihren Blauen Flaggen Stränden und eine autofreie Altstadt, die ein Freilichtmuseum ist',
            'zh' => '加利西亚最美丽河湾所在的省份，科斯群岛拥有蓝旗海滩，步行老城是露天博物馆',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // ASTURIAS (1 provincia)
    // ─────────────────────────────────────────────────────────────────────
    'asturias' => [
        'label' => 'Asturias', 'db' => 'Asturias',
        'region' => 'Asturias',
        'attractions' => ['Picos de Europa', 'Cueva de Altamira', 'Bufones de Arenillas', 'Gijón'],
        'vibe' => [
            'es' => 'el paraíso verde de España, donde los Picos de Europa rozan las nubes, los bufones escupen agua salada al cielo y la sidra se sirve en tabla tradicional',
            'en' => 'Spain\'s green paradise, where the Picos de Europa touch the clouds, the bufones spout saltwater into the sky and cider is served on traditional wooden boards',
            'fr' => 'le paradis vert de l\'Espagne, où les Picos de Europa touchent les nuages, les bufones crachent de l\'eau salée dans le ciel et le cidre est servi sur des planche traditionnelles',
            'de' => 'das grüne Paradies Spaniens, wo die Picos de Europa die Wolken berühren, die Bufones Salzwasser in den Himmel spucken und Sidra auf traditionellen Brettern serviert wird',
            'zh' => '西班牙的绿色天堂，欧罗巴峰触碰云端，布丰喷泉将盐水喷向天空，苹果酒在传统木板上享用',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // CANTABRIA (1 provincia)
    // ─────────────────────────────────────────────────────────────────────
    'cantabria' => [
        'label' => 'Cantabria', 'db' => 'Cantabria',
        'region' => 'Cantabria',
        'attractions' => ['Cueva de Altamira', 'Parque Natural de los Picos de Europa', 'Santander', 'Cabo Mayor'],
        'vibe' => [
            'es' => 'la provincia de las cuevas prehistóricas más importantes del mundo, donde el arte rupestre de Altamira revela secretos de hace 35.000 años y los Picos de Europa muestran su cara norte',
            'en' => 'the province of the world\'s most important prehistoric caves, where Altamira\'s cave art reveals secrets from 35,000 years ago and the Picos de Europa show their northern face',
            'fr' => 'la province des grottes préhistoriques les plus importantes au monde, où l\'art rupestre d\'Altamira révèle des secrets d\'il y a 35 000 ans et les Picos de Europa montrent leur face nord',
            'de' => 'die Provincia der wichtigsten prähistorischen Höhlen der Welt, wo die Höhlenkunst von Altamira Geheimnisse aus vor 35.000 Jahren enthüllt und die Picos de Europa ihre Nordseite zeigen',
            'zh' => '世界上最重要的史前洞穴所在省份，阿尔塔米拉洞穴艺术揭示了35000年前的秘密，欧罗巴峰展现其北麓',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // CATALUÑA (1 provincia)
    // ─────────────────────────────────────────────────────────────────────
    'barcelona' => [
        'label' => 'Barcelona', 'db' => 'Barcelona',
        'region' => 'Cataluña',
        'attractions' => ['Sagrada Familia', 'Parque Natural del Montseny', 'Costa Brava', 'Montserrat'],
        'vibe' => [
            'es' => 'la provincia donde Gaudí convirtió la arquitectura en arte vivo, el Montseny ofrece paisajes de montaña a solo una hora de la ciudad y la Costa Brava esconde calas secretas',
            'en' => 'the province where Gaudí turned architecture into living art, Montseny offers mountain landscapes just an hour from the city and the Costa Brava hides secret coves',
            'fr' => 'la province où Gaudí a transformé l\'architecture en art vivant, le Montseny offre des paysages de montagne à seulement une heure de la ville et la Costa Brava cache des criques secrètes',
            'de' => 'die Provincia, wo Gaudí Architektur in lebendige Kunst verwandelte, der Montseny bietet Berglandschaften nur eine Stunde von der Stadt entfernt und die Costa Brava verborgene Buchten',
            'zh' => '高迪将建筑变为生动艺术的省份，蒙塞尼山距城市仅一小时车程就能欣赏山景，科斯塔布拉瓦隐藏着私密海湾',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // ANDALUCÍA (1 provincia)
    // ─────────────────────────────────────────────────────────────────────
    'cordoba' => [
        'label' => 'Córdoba', 'db' => 'Cordoba',
        'region' => 'Andalucía',
        'attractions' => ['Mezquita-Catedral de Córdoba', 'Medina Azahara', 'Patios de Córdoba', 'Sierra de Hornachuelos'],
        'vibe' => [
            'es' => 'la provincia de la Mezquita infinita, donde el patrimonio andalusí se mezcla con flores de geranio en los patios interiores y la Sierra de Hornachuelos ofrece naturaleza salvaje',
            'en' => 'the province of the infinite Mosque, where Andalusian heritage mixes with geranium flowers in inner courtyards and the Sierra de Hornachuelos offers wild nature',
            'fr' => 'la province de la Mosquée infinie, où le patrimoine andalous se mélange avec des fleurs de géranium dans les patios intérieurs et la Sierra de Hornachuelos offre une nature sauvage',
            'de' => 'die Provincia der unendlichen Moschee, wo andalusisches Erbe sich mit Geranienblüten in Innenhöfen mischt und die Sierra de Hornachuelos wilde Natur bietet',
            'zh' => '无限清真寺所在的省份，安达卢西亚遗产与天竺葵花在内院混合，奥尔纳丘埃洛斯山脉提供原始自然',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // CASTILLA-LA MANCHA (3 provincias)
    // ─────────────────────────────────────────────────────────────────────
    'cuenca' => [
        'label' => 'Cuenca', 'db' => 'Cuenca',
        'region' => 'Castilla-La Mancha',
        'attractions' => ['Ciudad Encantada', 'Serranía de Cuenca', 'Casas Colgadas', 'Nacimiento del Río Cuervo'],
        'vibe' => [
            'es' => 'tierra de paisajes imposibles, donde las casas colgantes desafían la gravedad sobre el Júcar, la Ciudad Encantada forma figuras de piedra y el río Cuervo nace entre helechos',
            'en' => 'a land of impossible landscapes where hanging houses defy gravity over the Júcar, the Enchanted City forms stone figures and the Cuervo river springs among ferns',
            'fr' => 'une terre de paysages impossibles, où les maisons suspendues défient la gravité et la Cité Enchantée forme des figures de pierre',
            'de' => 'ein Land unmöglicher Landschaften, wo hängende Häuser die Schwerkraft über dem Júcar trotzen und die Verzauberte Stadt Steinfiguren formt',
            'zh' => '不可思议的风景之地：悬空屋俯瞰胡卡河，魔法城形成石头图案，库埃尔沃河源头蕨类丛生',
        ],
    ],
    'guadalajara' => [
        'label' => 'Guadalajara', 'db' => 'Guadalajara',
        'region' => 'Castilla-La Mancha',
        'attractions' => ['Serranía de Cuenca (Guadalajara)', 'Sigüenza', 'Hayedo de Tejera Negra', 'Alto Tajo'],
        'vibe' => [
            'es' => 'el secreto mejor guardado de la Meseta, con el hayedo de Tejera Negra (el más grande de España), el Alto Tajo Parque Natural y la ciudad medieval de Sigüenza',
            'en' => 'the Meseta\'s best kept secret, with Spain\'s largest beech forest, the Alto Tajo Natural Park and the medieval city of Sigüenza',
            'fr' => 'le mieux gardé secret de la Meseta, avec la plus grande hêtraie d\'Espagne, le Parc Naturel de l\'Alto Tajo et la ville médiévale de Sigüenza',
            'de' => 'das bestgehütete Geheimnis der Meseta, mit Spaniens größtem Buchenwald, dem Naturpark Alto Tajo und der mittelalterlichen Stadt Sigüenza',
            'zh' => '梅塞塔高原最神秘的角落：西班牙最大山毛榉林、塔霍河上游自然公园和中世纪城市希圭恩萨',
        ],
    ],
    'toledo' => [
        'label' => 'Toledo', 'db' => 'Toledo',
        'region' => 'Castilla-La Mancha',
        'attractions' => ['Catedral de Toledo', 'Alcázar', 'Casco Antiguo', 'Yacimiento de Carranque'],
        'vibe' => [
            'es' => 'la ciudad de las tres culturas donde el cristianismo, el islam y el judaísmo dejaron huella imborrable en calles empedradas y monumentos que son Tesoro Nacional',
            'en' => 'the city of three cultures where Christianity, Islam and Judaism left an indelible mark on cobblestone streets and monuments that are National Treasure',
            'fr' => 'la ville des trois cultures où le christianisme, l\'islam et le judaïsme ont laissé une empreinte indélébile sur les rues pavées et les monuments qui sont Trésor National',
            'de' => 'die Stadt der drei Kulturen, wo Christentum, Islam und Judentum unauslöschliche Spuren auf Kopfsteinpflasterstraßen und Nationalschätzen hinterließen',
            'zh' => '三种文化的城市，基督教、伊斯兰教和犹太教在鹅卵石街道和国家级宝藏纪念碑上留下了不可磨灭的印记',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // COMUNIDAD VALENCIANA (1 provincia)
    // ─────────────────────────────────────────────────────────────────────
    'valencia' => [
        'label' => 'Valencia', 'db' => 'Valencia',
        'region' => 'Comunidad Valenciana',
        'attractions' => ['Ciudad de las Artes y las Ciencias', 'Albufera de Valencia', 'Bioparc', 'Catedral de Valencia'],
        'vibe' => [
            'es' => 'la provincia del futuro y la tradición, donde la Ciudad de las Artes parece nave espacial y la Albufera produce el arroz más valorado del mundo',
            'en' => 'the province of future and tradition, where the City of Arts looks like a spaceship and the Albufera produces the most prized rice in the world',
            'fr' => 'la province du futur et de la tradition, où la Cité des Arts ressemble à un vaisseau spatial et l\'Albufera produit le riz le plus prisé au monde',
            'de' => 'die Provincia der Zukunft und Tradition, wo die Stadt der Künste wie ein Raumschiff aussieht und die Albufera den begehrtesten Reis der Welt produziert',
            'zh' => '未来与传统相结合的省份，艺术科学城看起来像宇宙飞船，阿尔布费拉产出世界上最有价值的大米',
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
        // amenities es JSON: ["Chimenea","Wifi",...] — buscar con comillas para mayor precisión
        'sql'    => "(a.amenities LIKE '%\"Chimenea\"%' OR a.amenities LIKE '%chimenea%' OR a.amenities LIKE '%fireplace%' OR a.description LIKE '%chimenea%')",
        'labels' => ['es'=>'con chimenea','en'=>'with fireplace','fr'=>'avec cheminée','de'=>'mit Kamin','zh'=>'带壁炉'],
        'icon'   => '🔥', 'order' => 2,
    ],
    'con-piscina' => [
        // amenities es un campo JSON, ej: ["Piscina","Wifi","Barbacoa"...]
        // Buscamos el valor exacto "Piscina" en el JSON (LIKE '%"Piscina"%').
        // En description excluimos "piscina natural"/"piscinas naturales" (pozas de río).
        'sql'    => "(a.amenities LIKE '%\"Piscina\"%' OR (a.description LIKE '%piscina%' AND a.description NOT LIKE '%piscina natural%' AND a.description NOT LIKE '%piscinas naturales%'))",
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
