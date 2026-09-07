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
 *
 *  NOTA: Se incluyen las 50 provincias de España.
 *  Las provincias sin alojamientos activos en BD redirigen al hub (302)
 *  y NO se publican en el sitemap (la query en generar-sitemap.php filtra por COUNT>0).
 */

// ════════════════════════════════════════════════════════════════════════════
// PROVINCIAS — LAS 50 DE ESPAÑA
// ════════════════════════════════════════════════════════════════════════════
const LANDING_PROVINCIAS = [

    // ─────────────────────────────────────────────────────────────────────
    // CASTILLA Y LEÓN (9 provincias)
    // ─────────────────────────────────────────────────────────────────────
    'avila' => [
        'label' => 'Ávila', 'db' => 'Avila',
        'region' => 'Castilla y León',
        'lat' => 40.656, 'lng' => -4.701,
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
        'lat' => 42.343, 'lng' => -3.697,
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
        'lat' => 42.598, 'lng' => -5.567,
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
        'lat' => 42.010, 'lng' => -4.527,
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
        'lat' => 40.970, 'lng' => -5.663,
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
        'lat' => 40.948, 'lng' => -4.118,
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
        'lat' => 41.763, 'lng' => -2.464,
        'attractions' => ['Cañón del Río Lobos', 'Lagunas de Urbión', 'Numancia', 'Sierra de Cebollera'],
        'vibe' => [
            'es' => 'una de las provincias más tranquilas y auténticas de España, tierra de pinos centenarios, cañones esculpidos por el Duero y un cielo de estrellas sin contaminar',
            'en' => 'one of Spain\'s most peaceful and authentic provinces, land of ancient pine forests, Duero gorges and unpolluted starry skies',
            'fr' => 'l\'une des provinces les plus tranquilles et authentiques d\'Espagne, terre de pinèdes centenaires et de canyons sculptés par le Duero',
            'de' => 'eine der ruhigsten und authentischsten Provinzen Spaniens, Land uralter Pinienwälder und vom Duero geformter Canyons',
            'zh' => '西班牙最宁静、最原始的省份之一，古老松林、杜罗河峡谷和璀璨星空构成其独特魅力',
        ],
    ],
    'valladolid' => [
        'label' => 'Valladolid', 'db' => 'Valladolid',
        'region' => 'Castilla y León',
        'lat' => 41.652, 'lng' => -4.724,
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
        'lat' => 41.504, 'lng' => -5.745,
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
        'lat' => 43.371, 'lng' => -8.396,
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
        'lat' => 43.012, 'lng' => -7.556,
        'attractions' => ['Muralla de Lugo', 'Domus do Mitreo', 'Parque Natural dos Ancares', 'Ribeira Sacra'],
        'vibe' => [
            'es' => 'la provincia de la única muralla romana walkable del mundo, donde las termas romanas siguen manando agua curativa y los Ancares esconden los paisajes más salvajes de Galicia',
            'en' => 'the province of the only walkable Roman wall in the world, where Roman thermal baths still flow with healing waters and the Ancares hide Galicia\'s wildest landscapes',
            'fr' => 'la province du seul mur romain praticable au monde, où les thermes romaines coulent toujours avec des eaux curatives et les Ancares cachent les paysages les plus sauvages de Galice',
            'de' => 'die Provincia der einzigen begehbaren Römermauer der Welt, wo Römische Thermen immer noch mit heilendem Wasser fließen und die Ancares Galiziens wildeste Landschaften verbergen',
            'zh' => '世界上唯一可步行的罗马城墙所在省份，罗马温泉仍然流淌着疗愈之水，安卡雷斯隐藏着加利西亚最原始的风景',
        ],
    ],
    'ourense' => [
        'label' => 'Ourense', 'db' => 'Ourense',
        'region' => 'Galicia',
        'lat' => 42.336, 'lng' => -7.864,
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
        'lat' => 42.433, 'lng' => -8.648,
        'attractions' => ['Islas Cíes', 'Ría de Arousa', 'Ribeira Sacra', 'Casco Histórico de Pontevedra'],
        'vibe' => [
            'es' => 'la provincia de las rías más bellas de Galicia, las islas Cíes con sus playas de bandera azul y un casco antiguo pedestrianizado que es museo al aire libre',
            'en' => 'the province of Galicia\'s most beautiful rías, the Cías Islands with their blue flag beaches and a pedestrianized old town that is an open-air museum',
            'fr' => 'la province des plus belles rías de Galice, les îles Cíes avec leurs plages aux drapeau bleu et un vieux centre pedestrianisé qui est un musée en plein air',
            'de' => 'die Provincia der schönsten Rías Galiziens, die Cías-Inseln mit ihren Blauen Flaggen Stränden und eine autofreie Altstadt, die ein Freilichtmuseum ist',
            'zh' => '加利西亚最美丽河湾所在的省份，科斯群岛拥有蓝旗海滩，步行老城是露天博物馆',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // ASTURIAS
    // ─────────────────────────────────────────────────────────────────────
    'asturias' => [
        'label' => 'Asturias', 'db' => 'Asturias',
        'region' => 'Asturias',
        'lat' => 43.362, 'lng' => -5.849,
        'attractions' => ['Picos de Europa', 'Lagos de Covadonga', 'Bufones de Arenillas', 'Gijón'],
        'vibe' => [
            'es' => 'el paraíso verde de España, donde los Picos de Europa rozan las nubes, los bufones escupen agua salada al cielo y la sidra se sirve en tabla tradicional',
            'en' => 'Spain\'s green paradise, where the Picos de Europa touch the clouds, the bufones spout saltwater into the sky and cider is served on traditional wooden boards',
            'fr' => 'le paradis vert de l\'Espagne, où les Picos de Europa touchent les nuages, les bufones crachent de l\'eau salée dans le ciel et le cidre est servi sur des planche traditionnelles',
            'de' => 'das grüne Paradies Spaniens, wo die Picos de Europa die Wolken berühren, die Bufones Salzwasser in den Himmel spucken und Sidra auf traditionellen Brettern serviert wird',
            'zh' => '西班牙的绿色天堂，欧罗巴峰触碰云端，布丰喷泉将盐水喷向天空，苹果酒在传统木板上享用',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // CANTABRIA
    // ─────────────────────────────────────────────────────────────────────
    'cantabria' => [
        'label' => 'Cantabria', 'db' => 'Cantabria',
        'region' => 'Cantabria',
        'lat' => 43.183, 'lng' => -3.988,
        'attractions' => ['Cueva de Altamira', 'Parque Natural de los Picos de Europa', 'Santander', 'Faro de Cabo Mayor'],
        'vibe' => [
            'es' => 'la provincia de las cuevas prehistóricas más importantes del mundo, donde el arte rupestre de Altamira revela secretos de hace 35.000 años y los Picos de Europa muestran su cara norte',
            'en' => 'the province of the world\'s most important prehistoric caves, where Altamira\'s cave art reveals secrets from 35,000 years ago and the Picos de Europa show their northern face',
            'fr' => 'la province des grottes préhistoriques les plus importantes au monde, où l\'art rupestre d\'Altamira révèle des secrets d\'il y a 35 000 ans et les Picos de Europa montrent leur face nord',
            'de' => 'die Provincia der wichtigsten prähistorischen Höhlen der Welt, wo die Höhlenkunst von Altamira Geheimnisse aus vor 35.000 Jahren enthüllt und die Picos de Europa ihre Nordseite zeigen',
            'zh' => '世界上最重要的史前洞穴所在省份，阿尔塔米拉洞穴艺术揭示了35000年前的秘密，欧罗巴峰展现其北麓',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // PAÍS VASCO (3 provincias)
    // ─────────────────────────────────────────────────────────────────────
    'alava' => [
        'label' => 'Álava', 'db' => 'Álava',
        'region' => 'País Vasco',
        'lat' => 42.846, 'lng' => -2.673,
        'attractions' => ['Salinas de Añana', 'Parque Natural de Gorbeia', 'Laguardia', 'Ruta del Vino de Rioja Alavesa'],
        'vibe' => [
            'es' => 'la provincia interior del País Vasco, donde los viñedos de la Rioja Alavesa se funden con el verdor del Gorbeia y las salinas romanas de Añana siguen activas',
            'en' => 'the inland Basque province where Rioja Alavesa vineyards blend with the greenery of Gorbeia and the Roman salt flats of Añana are still active',
            'fr' => 'la province intérieure du Pays Basque, où les vignobles de la Rioja Alavesa se fondent avec le verdure du Gorbeia et les salines romaines d\'Añana sont toujours actives',
            'de' => 'die Binnenprovinz des Baskenlandes, wo sich die Weinberge der Rioja Alavesa mit dem Grün des Gorbeia verbinden und die römischen Salinen von Añana noch aktiv sind',
            'zh' => '巴斯克地区的内陆省份，阿拉维斯里奥哈葡萄园与戈尔贝亚绿意融合，罗马时代的阿尼亚纳盐田至今仍在使用',
        ],
    ],
    'gipuzkoa' => [
        'label' => 'Gipuzkoa', 'db' => 'Gipuzkoa',
        'region' => 'País Vasco',
        'lat' => 43.312, 'lng' => -2.003,
        'attractions' => ['Costa Vasca', 'San Sebastián', 'Zumaia (Flysch)', 'Santuario de Loyola'],
        'vibe' => [
            'es' => 'la provincia más verde del norte, donde los caseríos vascos se asoman a un Cantábrico de olas perfectas, la gastronomía es religión y el flysch de Zumaia cuenta 60 millones de años de historia',
            'en' => 'the greenest province of the north, where Basque farmhouses overlook a Cantabrian Sea with perfect waves, gastronomy is a religion and Zumaia\'s flysch tells 60 million years of history',
            'fr' => 'la province la plus verte du nord, où les fermes basques surplombent une mer Cantabrique aux vagues parfaites et la gastronomie est une religion',
            'de' => 'die grünste Provinz des Nordens, wo baskische Bauernhöfe auf ein kantabrisches Meer mit perfekten Wellen blicken und die Gastronomie eine Religion ist',
            'zh' => '北部最绿省份，巴斯克农场俯瞰坎塔布里亚海完美浪涛，美食如同宗教，苏迈亚飞石讲述6000万年历史',
        ],
    ],
    'vizcaya' => [
        'label' => 'Vizcaya', 'db' => 'Vizcaya',
        'region' => 'País Vasco',
        'lat' => 43.263, 'lng' => -2.935,
        'attractions' => ['Puente Colgante de Bizkaia', 'Costa de Urdaibai', 'Gernika-Lumo', 'Reserva de la Biosfera de Urdaibai'],
        'vibe' => [
            'es' => 'la provincia del Puente Colgante Patrimonio de la Humanidad, la Reserva de la Biosfera de Urdaibai y una costa donde los surfistas y los txipirones conviven en perfecta armonía',
            'en' => 'the province of the UNESCO World Heritage Suspension Bridge, the Urdaibai Biosphere Reserve and a coast where surfers and squid coexist in perfect harmony',
            'fr' => 'la province du Pont Suspendu Patrimoine Mondial, la Réserve de Biosphère d\'Urdaibai et une côte où surfeurs et calamars coexistent en parfaite harmonie',
            'de' => 'die Provincia der UNESCO-Welterbe-Hängebrücke, des Biosphärenreservats Urdaibai und einer Küste, wo Surfer und Tintenfische in perfekter Harmonie koexistieren',
            'zh' => '拥有世界遗产悬索桥、乌尔达依生物圈保护区以及冲浪者与鱿鱼和谐共处的海岸的省份',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // NAVARRA
    // ─────────────────────────────────────────────────────────────────────
    'navarra' => [
        'label' => 'Navarra', 'db' => 'Navarra',
        'region' => 'Navarra',
        'lat' => 42.695, 'lng' => -1.676,
        'attractions' => ['Parque Natural de Urbasa y Andía', 'Bardenas Reales', 'Selva de Irati', 'Camino de Santiago'],
        'vibe' => [
            'es' => 'una provincia de paisajes extremos donde las Bardenas Reales forman un desierto semiárido único en Europa, la Selva de Irati es el segundo bosque de hayas más grande del continente y el Camino de Santiago cruza valles eternos',
            'en' => 'a province of extreme landscapes where the Bardenas Reales form a unique semi-arid desert in Europe, the Irati Forest is the continent\'s second largest beech forest and the Camino de Santiago crosses eternal valleys',
            'fr' => 'une province aux paysages extrêmes où les Bardenas Reales forment un désert semi-aride unique en Europe, la Forêt d\'Irati est la deuxième plus grande hêtraie du continent et le Chemin de Santiago traverse des vallées éternelles',
            'de' => 'eine Provincia mit extremen Landschaften, wo die Bardenas Reales eine einzigartige Halbwüste in Europa bilden, der Irati-Wald Europas zweitgrößter Buchenwald ist und der Jakobsweg ewige Täler durchquert',
            'zh' => '景观极致多样的省份：巴尔德纳斯雷亚莱斯是欧洲独特的半干旱沙漠，伊拉蒂森林是欧洲第二大山毛榉林，圣地亚哥之路穿越永恒山谷',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // LA RIOJA
    // ─────────────────────────────────────────────────────────────────────
    'la-rioja' => [
        'label' => 'La Rioja', 'db' => 'La Rioja',
        'region' => 'La Rioja',
        'lat' => 42.287, 'lng' => -2.540,
        'attractions' => ['Ruta del Vino de La Rioja', 'Sierra de la Demanda', 'Monasterio de San Millán', 'Dinosaurios de Enciso'],
        'vibe' => [
            'es' => 'tierra de la mejor gastronomía de España, viñedos que producen vinos de fama mundial, monasterios medievales declarados Patrimonio y huellas de dinosaurios en roca viva',
            'en' => 'land of Spain\'s finest gastronomy, vineyards producing world-famous wines, medieval Heritage monasteries and dinosaur footprints in living rock',
            'fr' => 'terre de la meilleure gastronomie d\'Espagne, vignobles produisant des vins de renommée mondiale et empreintes de dinosaures dans la roche',
            'de' => 'Land der besten Gastronomie Spaniens, Weinberge mit weltberühmten Weinen und Dinosaurierspuren im Fels',
            'zh' => '西班牙最佳美食之乡，出产世界著名葡萄酒的葡萄园，中世纪遗产修道院和活岩石上的恐龙足迹',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // ARAGÓN (3 provincias)
    // ─────────────────────────────────────────────────────────────────────
    'huesca' => [
        'label' => 'Huesca', 'db' => 'Huesca',
        'region' => 'Aragón',
        'lat' => 42.136, 'lng' => -0.408,
        'attractions' => ['Pirineos Aragoneses', 'Parque Nacional de Ordesa', 'Ainsa', 'Hecho y Ansó'],
        'vibe' => [
            'es' => 'la provincia de los Pirineos más salvajes, con el Monte Perdido como Patrimonio de la Humanidad, valles medievales intactos y el skiing de Formigal',
            'en' => 'the province of the wildest Pyrenees, with Monte Perdido as a World Heritage site, intact medieval valleys and Formigal skiing',
            'fr' => 'la province des Pyrénées les plus sauvages, avec le Monte Perdido Patrimoine Mondial, des vallées médiévales intactes et le ski de Formigal',
            'de' => 'die Provincia der wildesten Pyrenäen, mit dem Welterbe Monte Perdido, intakten mittelalterlichen Tälern und Formigal-Skifahren',
            'zh' => '最原始比利牛斯山脉所在省份，蒙特佩尔迪多是世界遗产，中世纪山谷保存完好，弗米格尔滑雪场享誉四方',
        ],
    ],
    'teruel' => [
        'label' => 'Teruel', 'db' => 'Teruel',
        'region' => 'Aragón',
        'lat' => 40.345, 'lng' => -1.106,
        'attractions' => ['Maestrazgo', 'Dinópolis', 'Albarracín', 'Ruta del Mudéjar'],
        'vibe' => [
            'es' => 'tierra de los amantes más famosos de la historia, de la arquitectura mudéjar declarada Patrimonio, los dinosaurios del Jurásico y pueblos medievales suspendidos en el tiempo',
            'en' => 'land of history\'s most famous lovers, UNESCO Mudéjar architecture, Jurassic dinosaurs and medieval villages suspended in time',
            'fr' => 'terre des amants les plus célèbres de l\'histoire, de l\'architecture mudéjare Patrimoine, des dinosaures du Jurassique et de villages médiévaux suspendus dans le temps',
            'de' => 'Land der berühmtesten Liebenden der Geschichte, UNESCO Mudéjar-Architektur, Jurassischen Dinosaurier und mittelalterlicher Dörfer, die in der Zeit eingefroren scheinen',
            'zh' => '历史上最著名恋人的故乡，联合国教科文组织穆德哈尔建筑，侏罗纪恐龙和悬浮在时间中的中世纪村庄',
        ],
    ],
    'zaragoza' => [
        'label' => 'Zaragoza', 'db' => 'Zaragoza',
        'region' => 'Aragón',
        'lat' => 41.649, 'lng' => -0.887,
        'attractions' => ['Basílica del Pilar', 'Monasterio de Piedra', 'Cinco Villas', 'Moncayo'],
        'vibe' => [
            'es' => 'una provincia donde el Monasterio de Piedra crea cascadas en pleno desierto, el Moncayo preside la llanura y la Basílica del Pilar es la patrona de la Hispanidad',
            'en' => 'a province where Piedra Monastery creates waterfalls in the desert, the Moncayo presides over the plain and the Basilica del Pilar is patron of Hispanic peoples worldwide',
            'fr' => 'une province où le Monastère de Piedra crée des cascades dans le désert, le Moncayo préside la plaine et la Basilique du Pilar est la patronne de l\'hispanité',
            'de' => 'eine Provincia, wo das Kloster Piedra Wasserfälle in der Wüste schafft, der Moncayo die Ebene beherrscht und die Basilika del Pilar Schutzpatronin der hispanischen Welt ist',
            'zh' => '皮耶德拉修道院在沙漠中创造瀑布，蒙卡约山俯瞰平原，皮拉尔圣母大教堂是西班牙语世界守护神的省份',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // CATALUÑA (4 provincias)
    // ─────────────────────────────────────────────────────────────────────
    'barcelona' => [
        'label' => 'Barcelona', 'db' => 'Barcelona',
        'region' => 'Cataluña',
        'lat' => 41.389, 'lng' => 2.159,
        'attractions' => ['Sagrada Familia', 'Parque Natural del Montseny', 'Costa Brava', 'Montserrat'],
        'vibe' => [
            'es' => 'la provincia donde Gaudí convirtió la arquitectura en arte vivo, el Montseny ofrece paisajes de montaña a solo una hora de la ciudad y la Costa Brava esconde calas secretas',
            'en' => 'the province where Gaudí turned architecture into living art, Montseny offers mountain landscapes just an hour from the city and the Costa Brava hides secret coves',
            'fr' => 'la province où Gaudí a transformé l\'architecture en art vivant, le Montseny offre des paysages de montagne à seulement une heure de la ville et la Costa Brava cache des criques secrètes',
            'de' => 'die Provincia, wo Gaudí Architektur in lebendige Kunst verwandelte, der Montseny bietet Berglandschaften nur eine Stunde von der Stadt entfernt und die Costa Brava verborgene Buchten',
            'zh' => '高迪将建筑变为生动艺术的省份，蒙塞尼山距城市仅一小时车程就能欣赏山景，科斯塔布拉瓦隐藏着私密海湾',
        ],
    ],
    'girona' => [
        'label' => 'Girona', 'db' => 'Girona',
        'region' => 'Cataluña',
        'lat' => 41.980, 'lng' => 2.821,
        'attractions' => ['Costa Brava', 'Parque Natural del Cap de Creus', 'Cadaqués', 'Volcanes de la Garrotxa'],
        'vibe' => [
            'es' => 'la provincia de los volcanes extintos de la Garrotxa, la Costa Brava más salvaje y Cadaqués, el pueblo que inspiró a Dalí con su luz única',
            'en' => 'the province of the Garrotxa extinct volcanoes, the wildest Costa Brava and Cadaqués, the village that inspired Dalí with its unique light',
            'fr' => 'la province des volcans éteints de la Garrotxa, la Costa Brava la plus sauvage et Cadaqués, le village qui inspira Dalí avec sa lumière unique',
            'de' => 'die Provincia der erloschenen Vulkane der Garrotxa, der wildesten Costa Brava und Cadaqués, dem Dorf, das Dalí mit seinem einzigartigen Licht inspirierte',
            'zh' => '加罗查熄火山、最野性的科斯塔布拉瓦和以独特光线启发达利的卡达克斯村庄所在的省份',
        ],
    ],
    'lleida' => [
        'label' => 'Lleida', 'db' => 'Lleida',
        'region' => 'Cataluña',
        'lat' => 41.617, 'lng' => 0.621,
        'attractions' => ['Pirineos Catalanes', 'Valle de Arán', 'Aigüestortes', 'Sort (Deportes de aventura)'],
        'vibe' => [
            'es' => 'la puerta de los Pirineos Catalanes, con el Valle de Arán como enclave único de occitano, el Parque Nacional de Aigüestortes y Sort como capital mundial del kayak',
            'en' => 'the gateway to the Catalan Pyrenees, with the Arán Valley as a unique Occitan enclave, the Aigüestortes National Park and Sort as the world capital of kayaking',
            'fr' => 'la porte des Pyrénées Catalanes, avec la Vallée d\'Arán comme enclave occitane unique, le Parc National d\'Aigüestortes et Sort comme capitale mondiale du kayak',
            'de' => 'das Tor zu den Katalanischen Pyrenäen, mit dem Arán-Tal als einzigartiger okzitanischer Enklave, dem Aigüestortes Nationalpark und Sort als Welthauptstadt des Kajakfahrens',
            'zh' => '加泰罗尼亚比利牛斯山脉的门户，阿兰谷地是独特的奥克语飞地，艾格斯托尔特斯国家公园，索尔特是世界皮划艇之都',
        ],
    ],
    'tarragona' => [
        'label' => 'Tarragona', 'db' => 'Tarragona',
        'region' => 'Cataluña',
        'lat' => 41.118, 'lng' => 1.245,
        'attractions' => ['Anfiteatro Romano de Tarragona', 'Delta del Ebro', 'Costa Daurada', 'Priorat (Vinos)'],
        'vibe' => [
            'es' => 'la antigua capital romana de Hispania, donde el anfiteatro mira al mar, el Delta del Ebro es paraíso para los observadores de aves y el Priorat produce vinos de culto',
            'en' => 'the ancient Roman capital of Hispania, where the amphitheater faces the sea, the Ebro Delta is a paradise for birdwatchers and the Priorat produces cult wines',
            'fr' => 'l\'ancienne capitale romaine d\'Hispanie, où l\'amphithéâtre fait face à la mer, le Delta de l\'Ebre est un paradis pour les observateurs d\'oiseaux et le Priorat produit des vins de culte',
            'de' => 'die alte römische Hauptstadt Hispaniens, wo das Amphitheater aufs Meer blickt, das Ebro-Delta ein Paradies für Vogelbeobachter ist und der Priorat Kultweine produziert',
            'zh' => '古罗马西班牙首都，圆形剧场面向大海，埃布罗三角洲是观鸟天堂，普里奥拉特出产膜拜级葡萄酒',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // COMUNIDAD VALENCIANA (3 provincias)
    // ─────────────────────────────────────────────────────────────────────
    'alicante' => [
        'label' => 'Alicante', 'db' => 'Alicante',
        'region' => 'Comunidad Valenciana',
        'lat' => 38.345, 'lng' => -0.483,
        'attractions' => ['Sierra de Mariola', 'Guadalest', 'Marina Alta', 'Castillo de Santa Bárbara'],
        'vibe' => [
            'es' => 'mucho más que playas, una provincia con el interior verde de la Sierra de Mariola, el embalse de Guadalest suspendido sobre un pueblo y viñedos de Monastrell que producen vinos únicos',
            'en' => 'much more than beaches, a province with the green interior of Sierra de Mariola, the Guadalest reservoir suspended above a village and Monastrell vineyards producing unique wines',
            'fr' => 'bien plus que des plages, une province avec l\'intérieur vert de la Sierra de Mariola et des vignobles de Monastrell qui produisent des vins uniques',
            'de' => 'viel mehr als Strände, eine Provincia mit dem grünen Inneren der Sierra de Mariola und Monastrell-Weinbergen, die einzigartige Weine produzieren',
            'zh' => '远不止海滩的省份，马里奥拉山脉绿色内陆，瓜达莱斯特水库悬挂在村庄之上，莫纳斯特雷尔葡萄园出产独特佳酿',
        ],
    ],
    'castellon' => [
        'label' => 'Castellón', 'db' => 'Castellón',
        'region' => 'Comunidad Valenciana',
        'lat' => 39.987, 'lng' => -0.051,
        'attractions' => ['Maestrazgo Castellonense', 'Parque Natural de la Tinença de Benifassà', 'Desierto de las Palmas', 'Morella'],
        'vibe' => [
            'es' => 'una provincia con el Maestrazgo más auténtico, la ciudad medieval de Morella encaramada a una roca y el Desierto de las Palmas como contraste entre el mar y la montaña',
            'en' => 'a province with the most authentic Maestrazgo, the medieval city of Morella perched on a rock and the Desert of Palms as contrast between sea and mountain',
            'fr' => 'une province avec le Maestrazgo le plus authentique, la ville médiévale de Morella perchée sur un rocher et le Désert des Palmes comme contraste entre mer et montagne',
            'de' => 'eine Provincia mit dem authentischsten Maestrazgo, der mittelalterlichen Stadt Morella auf einem Felsen und der Palmen-Wüste als Kontrast zwischen Meer und Berg',
            'zh' => '最原汁原味的马埃斯特拉斯戈地区、矗立岩石上的中世纪城市莫雷利亚以及海山对比的棕榈沙漠所在省份',
        ],
    ],
    'valencia' => [
        'label' => 'Valencia', 'db' => 'Valencia',
        'region' => 'Comunidad Valenciana',
        'lat' => 39.470, 'lng' => -0.376,
        'attractions' => ['Ciudad de las Artes y las Ciencias', 'Albufera de Valencia', 'Bioparc', 'Catedral de Valencia'],
        'vibe' => [
            'es' => 'la provincia del futuro y la tradición, donde la Ciudad de las Artes parece nave espacial y la Albufera produce el arroz más valorado del mundo',
            'en' => 'the province of future and tradition, where the City of Arts looks like a spaceship and the Albufera produces the most prized rice in the world',
            'fr' => 'la province du futur et de la tradition, où la Cité des Arts ressemble à un vaisseau spatial et l\'Albufera produit le riz le plus prisé au monde',
            'de' => 'die Provincia der Zukunft und Tradition, wo die Stadt der Künste wie ein Raumschiff aussieht und die Albufera den begehrtesten Reis der Welt produziert',
            'zh' => '未来与传统相结合的省份，艺术科学城看起来像宇宙飞船，阿尔布费拉产出世界上最有价值的大米',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // MURCIA
    // ─────────────────────────────────────────────────────────────────────
    'murcia' => [
        'label' => 'Murcia', 'db' => 'Murcia',
        'region' => 'Región de Murcia',
        'lat' => 37.983, 'lng' => -1.130,
        'attractions' => ['Mar Menor', 'Sierra Espuña', 'Cartagena Romana', 'Calblanque'],
        'vibe' => [
            'es' => 'la huerta de Europa, con el Mar Menor como laguna salada única, la Sierra Espuña como bosque mediterráneo prístino y Cartagena revelando su pasado romano',
            'en' => 'the garden of Europe, with the Mar Menor as a unique saltwater lagoon, Sierra Espuña as a pristine Mediterranean forest and Cartagena revealing its Roman past',
            'fr' => 'le jardin de l\'Europe, avec le Mar Menor comme lagune salée unique, la Sierra Espuña comme forêt méditerranéenne vierge et Carthagène révélant son passé romain',
            'de' => 'der Garten Europas, mit dem Mar Menor als einzigartiger Salzwasserlagune, Sierra Espuña als unberührtem Mittelmeerwald und Cartagena, das seine römische Vergangenheit enthüllt',
            'zh' => '欧洲花园，马尔梅诺尔是独特的盐水湖，埃斯普纳山是原始地中海森林，卡塔赫纳揭示其罗马过往',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // ANDALUCÍA (8 provincias)
    // ─────────────────────────────────────────────────────────────────────
    'almeria' => [
        'label' => 'Almería', 'db' => 'Almería',
        'region' => 'Andalucía',
        'lat' => 36.838, 'lng' => -2.459,
        'attractions' => ['Desierto de Tabernas', 'Parque Natural de Cabo de Gata', 'Alpujarras Almerienses', 'Castillo de Almería'],
        'vibe' => [
            'es' => 'el único desierto de Europa continental, con el Cabo de Gata como el litoral más virgen de España, las Alpujarras con arquitectura bereber única y más horas de sol que ninguna otra provincia',
            'en' => 'continental Europe\'s only desert, with Cabo de Gata as Spain\'s most unspoiled coastline, the Alpujarras with unique Berber architecture and more sunshine hours than any other province',
            'fr' => 'le seul désert d\'Europe continentale, avec le Cabo de Gata comme le littoral le plus vierge d\'Espagne et les Alpujarras avec une architecture berbère unique',
            'de' => 'Kontinentaleuropas einzige Wüste, mit dem Cabo de Gata als der unberührtesten Küste Spaniens und den Alpujarras mit einzigartiger Berber-Architektur',
            'zh' => '欧洲大陆唯一沙漠，卡博德加塔是西班牙最原始海岸线，阿尔普哈拉斯拥有独特柏柏尔建筑，日照时间居全国之首',
        ],
    ],
    'cadiz' => [
        'label' => 'Cádiz', 'db' => 'Cádiz',
        'region' => 'Andalucía',
        'lat' => 36.527, 'lng' => -6.289,
        'attractions' => ['Parque Natural Sierra de Grazalema', 'Pueblos Blancos', 'Costa de la Luz', 'Parque Nacional de Doñana (borde)'],
        'vibe' => [
            'es' => 'la provincia más antigua de Europa occidental, con los pueblos blancos colgados en la sierra, las dunas de Doñana y el viento del Atlántico que hace de Tarifa la meca del kitesurf',
            'en' => 'Western Europe\'s oldest province, with white villages perched in the mountains, Doñana dunes and Atlantic winds making Tarifa the mecca of kitesurfing',
            'fr' => 'la province la plus ancienne d\'Europe occidentale, avec des villages blancs perchés dans les montagnes, les dunes de Doñana et les vents de l\'Atlantique qui font de Tarifa la mecque du kitesurf',
            'de' => 'die älteste Provinz Westeuropas, mit weißen Dörfern in den Bergen, Doñana-Dünen und Atlantikwinden, die Tarifa zur Mekka des Kitesurfens machen',
            'zh' => '西欧最古老省份，山中白色村庄，多尼亚纳沙丘，大西洋风使塔里法成为风筝冲浪圣地',
        ],
    ],
    'cordoba' => [
        'label' => 'Córdoba', 'db' => 'Cordoba',
        'region' => 'Andalucía',
        'lat' => 37.888, 'lng' => -4.779,
        'attractions' => ['Mezquita-Catedral de Córdoba', 'Medina Azahara', 'Patios de Córdoba', 'Sierra de Hornachuelos'],
        'vibe' => [
            'es' => 'la provincia de la Mezquita infinita, donde el patrimonio andalusí se mezcla con flores de geranio en los patios interiores y la Sierra de Hornachuelos ofrece naturaleza salvaje',
            'en' => 'the province of the infinite Mosque, where Andalusian heritage mixes with geranium flowers in inner courtyards and the Sierra de Hornachuelos offers wild nature',
            'fr' => 'la province de la Mosquée infinie, où le patrimoine andalous se mélange avec des fleurs de géranium dans les patios intérieurs et la Sierra de Hornachuelos offre une nature sauvage',
            'de' => 'die Provincia der unendlichen Moschee, wo andalusisches Erbe sich mit Geranienblüten in Innenhöfen mischt und die Sierra de Hornachuelos wilde Natur bietet',
            'zh' => '无限清真寺所在的省份，安达卢西亚遗产与天竺葵花在内院混合，奥尔纳丘埃洛斯山脉提供原始自然',
        ],
    ],
    'granada' => [
        'label' => 'Granada', 'db' => 'Granada',
        'region' => 'Andalucía',
        'lat' => 37.177, 'lng' => -3.599,
        'attractions' => ['La Alhambra', 'Sierra Nevada', 'Las Alpujarras', 'Sacromonte'],
        'vibe' => [
            'es' => 'donde la Alhambra nace de un sueño nazarí, Sierra Nevada es el esquí más meridional de Europa y las Alpujarras muestran una cultura bereber que sobrevivió cinco siglos',
            'en' => 'where the Alhambra was born from a Nasrid dream, Sierra Nevada is Europe\'s southernmost skiing and the Alpujarras display a Berber culture that survived five centuries',
            'fr' => 'où l\'Alhambra est née d\'un rêve nasride, la Sierra Nevada offre le ski le plus méridional d\'Europe et les Alpujarras montrent une culture berbère qui a survécu cinq siècles',
            'de' => 'wo die Alhambra aus einem nasridischen Traum entstammt, Sierra Nevada das südlichste Skigebiet Europas ist und die Alpujarras eine berberkulturelle Tradition zeigen, die fünf Jahrhunderte überlebt hat',
            'zh' => '阿尔罕布拉宫诞生于纳斯里德梦想之处，内华达山脉是欧洲最南滑雪地，阿尔普哈拉斯展示存活五个世纪的柏柏尔文化',
        ],
    ],
    'huelva' => [
        'label' => 'Huelva', 'db' => 'Huelva',
        'region' => 'Andalucía',
        'lat' => 37.261, 'lng' => -6.945,
        'attractions' => ['Parque Nacional de Doñana', 'Sierra de Aracena', 'Playas de Mazagón', 'Colombres (Muelle de las Carabelas)'],
        'vibe' => [
            'es' => 'la provincia donde Colón partió hacia América, el Parque de Doñana es refugio de lince ibérico y la Sierra de Aracena esconde los mejores jamones ibéricos del mundo',
            'en' => 'the province from where Columbus departed for America, Doñana Park is a refuge for Iberian lynx and the Sierra de Aracena hides the world\'s finest Iberian hams',
            'fr' => 'la province d\'où Colomb est parti pour l\'Amérique, le Parc de Doñana est un refuge pour le lynx ibérique et la Sierra de Aracena cache les meilleurs jambons ibériques au monde',
            'de' => 'die Provincia, von der Kolumbus nach Amerika aufbrach, der Doñana-Park ist ein Rückzugsgebiet für den Iberischen Luchs und die Sierra de Aracena beherbergt die weltbesten Iberischen Schinken',
            'zh' => '哥伦布从这里出发前往美洲，多尼亚纳公园是伊比利亚猞猁的避难所，阿拉塞纳山脉藏有世界最顶级的伊比利亚火腿',
        ],
    ],
    'jaen' => [
        'label' => 'Jaén', 'db' => 'Jaén',
        'region' => 'Andalucía',
        'lat' => 37.779, 'lng' => -3.787,
        'attractions' => ['Parque Natural Sierra de Cazorla', 'Úbeda y Baeza (Patrimonio)', 'Olivares de Jaén', 'Castillo de Santa Catalina'],
        'vibe' => [
            'es' => 'la capital mundial del aceite de oliva, con la Sierra de Cazorla como reserva de la biosfera, Úbeda y Baeza declaradas Patrimonio de la Humanidad y un horizonte infinito de olivos',
            'en' => 'the world olive oil capital, with Sierra de Cazorla as a biosphere reserve, UNESCO-listed Úbeda and Baeza and an infinite horizon of olive trees',
            'fr' => 'la capitale mondiale de l\'huile d\'olive, avec la Sierra de Cazorla comme réserve de biosphère, Úbeda et Baeza classées Patrimoine Mondial et un horizon infini d\'oliviers',
            'de' => 'die Welthauptstadt des Olivenöls, mit der Sierra de Cazorla als Biosphärenreservat, UNESCO-gelisteten Úbeda und Baeza und einem unendlichen Horizont aus Olivenbäumen',
            'zh' => '世界橄榄油之都，卡索尔拉山脉是生物圈保护区，乌贝达和巴埃萨是世界遗产，无边无际的橄榄林延伸至地平线',
        ],
    ],
    'malaga' => [
        'label' => 'Málaga', 'db' => 'Málaga',
        'region' => 'Andalucía',
        'lat' => 36.721, 'lng' => -4.421,
        'attractions' => ['Caminito del Rey', 'Sierra de las Nieves', 'Ronda', 'Axarquía'],
        'vibe' => [
            'es' => 'mucho más que Costa del Sol, con el Caminito del Rey como la vía ferrata más espectacular de España, Ronda sobre un tajo de vértigo y la Axarquía como paraíso del turismo rural interior',
            'en' => 'much more than Costa del Sol, with El Caminito del Rey as Spain\'s most spectacular via ferrata, Ronda perched over a dizzying gorge and the Axarquía as a rural tourism paradise',
            'fr' => 'bien plus que la Costa del Sol, avec El Caminito del Rey comme la via ferrata la plus spectaculaire d\'Espagne, Ronda sur un gouffre vertigineux et l\'Axarquía comme paradis du tourisme rural',
            'de' => 'viel mehr als Costa del Sol, mit El Caminito del Rey als spektakulärster Klettersteig Spaniens, Ronda über einer schwindelerregenden Schlucht und der Axarquía als Paradies für Ruraltourismus',
            'zh' => '远不止太阳海岸，小国王步道是西班牙最壮观的铁道攀岩路线，隆达悬挂在令人晕眩的峡谷上，阿哈尔基亚是乡村旅游天堂',
        ],
    ],
    'sevilla' => [
        'label' => 'Sevilla', 'db' => 'Sevilla',
        'region' => 'Andalucía',
        'lat' => 37.389, 'lng' => -5.984,
        'attractions' => ['Real Alcázar de Sevilla', 'Parque Nacional de Doñana', 'Itálica', 'Sierra Norte de Sevilla'],
        'vibe' => [
            'es' => 'donde el flamenco nació, el Real Alcázar tiene jardines de cuento y la Sierra Norte esconde pueblos blancos con historia milenaria a solo 40 minutos de la capital',
            'en' => 'where flamenco was born, the Royal Alcázar has fairy-tale gardens and the Sierra Norte hides white villages with millennial history just 40 minutes from the capital',
            'fr' => 'où le flamenco est né, le Real Alcázar dispose de jardins de conte de fées et la Sierra Norte cache des villages blancs avec une histoire millénaire à seulement 40 minutes de la capitale',
            'de' => 'wo Flamenco geboren wurde, der Real Alcázar märchenhafte Gärten hat und die Sierra Norte weiße Dörfer mit tausendjähriger Geschichte nur 40 Minuten von der Hauptstadt entfernt birgt',
            'zh' => '弗拉明戈诞生之地，皇家城堡拥有童话花园，北部山脉隐藏着距首都仅40分钟的千年白色村庄',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // EXTREMADURA (2 provincias)
    // ─────────────────────────────────────────────────────────────────────
    'badajoz' => [
        'label' => 'Badajoz', 'db' => 'Badajoz',
        'region' => 'Extremadura',
        'lat' => 38.879, 'lng' => -6.970,
        'attractions' => ['Parque Nacional de Monfragüe', 'Badajoz Ciudad', 'La Serena (estepa cerealista)', 'Zafra'],
        'vibe' => [
            'es' => 'la provincia más grande de España, con Monfragüe como santuario del buitre negro y la cigüeña negra, la estepa de La Serena y una historia extremeña que cruzó el Atlántico',
            'en' => 'Spain\'s largest province, with Monfragüe as a sanctuary for black vultures and black storks, the La Serena steppe and an Extremaduran history that crossed the Atlantic',
            'fr' => 'la plus grande province d\'Espagne, avec Monfragüe comme sanctuaire du vautour noir et de la cigogne noire, la steppe de La Serena et une histoire extrémadourienne qui a traversé l\'Atlantique',
            'de' => 'Spaniens größte Provincia, mit Monfragüe als Refugium für Mönchsgeier und Schwarzstörche, der La Serena Steppe und einer extremadurischen Geschichte, die den Atlantik überquerte',
            'zh' => '西班牙最大省份，蒙弗拉圭是黑兀鹫和黑鹳的圣地，拉塞雷纳草原，和曾经横跨大西洋的埃斯特雷马杜拉历史',
        ],
    ],
    'caceres' => [
        'label' => 'Cáceres', 'db' => 'Cáceres',
        'region' => 'Extremadura',
        'lat' => 39.476, 'lng' => -6.372,
        'attractions' => ['Cáceres Ciudad Patrimonio', 'Parque Nacional de Monfragüe', 'Las Hurdes', 'Valle del Jerte'],
        'vibe' => [
            'es' => 'tierra de conquistadores y cigüeñas, donde la ciudad de Cáceres es Patrimonio de la Humanidad, el Valle del Jerte estalla en blanco cada marzo y las Hurdes guardan secretos ancestrales',
            'en' => 'land of conquistadors and storks, where Cáceres city is a World Heritage Site, the Valle del Jerte bursts white every March and Las Hurdes holds ancestral secrets',
            'fr' => 'terre de conquistadors et de cigognes, où la ville de Cáceres est Patrimoine Mondial, la Valle del Jerte éclate en blanc chaque mars et las Hurdes garde des secrets ancestraux',
            'de' => 'Land der Konquistadoren und Störche, wo die Stadt Cáceres Welterbe ist, das Valle del Jerte jeden März in Weiß erstrahlt und Las Hurdes uralte Geheimnisse bewahrt',
            'zh' => '征服者和白鹳的故乡，卡塞雷斯城是世界遗产，赫尔特山谷每年三月爆发白色花海，乌尔德斯保存着祖先的秘密',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // CASTILLA-LA MANCHA (5 provincias)
    // ─────────────────────────────────────────────────────────────────────
    'albacete' => [
        'label' => 'Albacete', 'db' => 'Albacete',
        'region' => 'Castilla-La Mancha',
        'lat' => 38.994, 'lng' => -1.858,
        'attractions' => ['Sierra de Alcaraz', 'Lagunas de Ruidera', 'Hayedo de Peñascosa', 'Cueva de los Chorros'],
        'vibe' => [
            'es' => 'la provincia de las navajas más famosas del mundo, las Lagunas de Ruidera como paraíso azul en La Mancha y la Sierra de Alcaraz con hayas boreales que aquí viven en el límite sur de su distribución',
            'en' => 'the province of the world\'s most famous penknives, the Ruidera Lagoons as a blue paradise in La Mancha and the Sierra de Alcaraz with beech trees living at the southern limit of their range',
            'fr' => 'la province des couteaux les plus célèbres au monde, les Lagunes de Ruidera comme paradis bleu en La Mancha et la Sierra de Alcaraz avec des hêtres vivant à la limite sud de leur distribution',
            'de' => 'die Provincia der weltberühmtesten Taschenmesser, die Lagunen von Ruidera als blaues Paradies in La Mancha und die Sierra de Alcaraz mit Buchenbäumen an der Südgrenze ihrer Verbreitung',
            'zh' => '世界最著名小刀产地，鲁伊德拉湖群是拉曼恰蓝色天堂，阿尔卡拉斯山脉拥有生存于分布南界的山毛榉',
        ],
    ],
    'ciudad-real' => [
        'label' => 'Ciudad Real', 'db' => 'Ciudad Real',
        'region' => 'Castilla-La Mancha',
        'lat' => 38.986, 'lng' => -3.929,
        'attractions' => ['Parque Nacional de las Tablas de Daimiel', 'Campo de Calatrava (volcanes)', 'Lagunas de Ruidera', 'Almagro'],
        'vibe' => [
            'es' => 'el corazón de La Mancha donde Don Quijote imaginó gigantes, los volcanes del Campo de Calatrava configuran un paisaje lunar y las Tablas de Daimiel son pulmón verde de Europa',
            'en' => 'the heart of La Mancha where Don Quixote imagined giants, the Campo de Calatrava volcanoes create a lunar landscape and the Tablas de Daimiel are Europe\'s green lung',
            'fr' => 'le cœur de La Mancha où Don Quichotte imaginait des géants, les volcans du Campo de Calatrava configurent un paysage lunaire et les Tablas de Daimiel sont le poumon vert de l\'Europe',
            'de' => 'das Herz von La Mancha, wo Don Quijote Riesen sah, die Vulkane des Campo de Calatrava eine Mondlandschaft schaffen und die Tablas de Daimiel die grüne Lunge Europas sind',
            'zh' => '堂吉诃德幻想巨人的拉曼恰之心，卡拉特拉瓦平原火山构成月球景观，戴米尔台地是欧洲绿肺',
        ],
    ],
    'cuenca' => [
        'label' => 'Cuenca', 'db' => 'Cuenca',
        'region' => 'Castilla-La Mancha',
        'lat' => 40.070, 'lng' => -2.134,
        'attractions' => ['Ciudad Encantada', 'Serranía de Cuenca', 'Casas Colgadas de Cuenca', 'Nacimiento del Río Cuervo'],
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
        'lat' => 40.633, 'lng' => -3.163,
        'attractions' => ['Alcarria', 'Sigüenza', 'Hayedo de Tejera Negra', 'Parque Natural del Alto Tajo'],
        'vibe' => [
            'es' => 'el secreto mejor guardado de la Meseta, con el hayedo de Tejera Negra (el más grande de España), el Parque Natural del Alto Tajo y la ciudad medieval de Sigüenza',
            'en' => 'the Meseta\'s best kept secret, with Spain\'s largest beech forest, the Alto Tajo Natural Park and the medieval city of Sigüenza',
            'fr' => 'le mieux gardé secret de la Meseta, avec la plus grande hêtraie d\'Espagne, le Parc Naturel de l\'Alto Tajo et la ville médiévale de Sigüenza',
            'de' => 'das bestgehütete Geheimnis der Meseta, mit Spaniens größtem Buchenwald, dem Naturpark Alto Tajo und der mittelalterlichen Stadt Sigüenza',
            'zh' => '梅塞塔高原最神秘的角落：西班牙最大山毛榉林、塔霍河上游自然公园和中世纪城市希圭恩萨',
        ],
    ],
    'toledo' => [
        'label' => 'Toledo', 'db' => 'Toledo',
        'region' => 'Castilla-La Mancha',
        'lat' => 39.857, 'lng' => -4.024,
        'attractions' => ['Catedral Primada de Toledo', 'Alcázar de Toledo', 'Casco histórico de Toledo', 'Yacimiento de Carranque'],
        'vibe' => [
            'es' => 'la ciudad de las tres culturas donde el cristianismo, el islam y el judaísmo dejaron huella imborrable en calles empedradas y monumentos que son Tesoro Nacional',
            'en' => 'the city of three cultures where Christianity, Islam and Judaism left an indelible mark on cobblestone streets and monuments that are National Treasure',
            'fr' => 'la ville des trois cultures où le christianisme, l\'islam et le judaïsme ont laissé une empreinte indélébile sur les rues pavées et les monuments qui sont Trésor National',
            'de' => 'die Stadt der drei Kulturen, wo Christentum, Islam und Judentum unauslöschliche Spuren auf Kopfsteinpflasterstraßen und Nationalschätzen hinterließen',
            'zh' => '三种文化的城市，基督教、伊斯兰教和犹太教在鹅卵石街道和国家级宝藏纪念碑上留下了不可磨灭的印记',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // MADRID
    // ─────────────────────────────────────────────────────────────────────
    'madrid' => [
        'label' => 'Madrid', 'db' => 'Madrid',
        'region' => 'Comunidad de Madrid',
        'lat' => 40.416, 'lng' => -3.703,
        'attractions' => ['Sierra de Guadarrama', 'El Escorial', 'Aranjuez', 'Valle de la Fuenfría'],
        'vibe' => [
            'es' => 'la provincia donde la capital convive con la Sierra de Guadarrama a solo una hora, los pueblos medievales de la sierra escapan al tráfago y el Valle de la Fuenfría ofrece pinares eternos',
            'en' => 'the province where the capital coexists with the Sierra de Guadarrama just an hour away, sierra medieval villages escape the bustle and the Fuenfría Valley offers eternal pine forests',
            'fr' => 'la province où la capitale coexiste avec la Sierra de Guadarrama à seulement une heure, les villages médiévaux de la sierra échappent à l\'agitation et la Vallée de Fuenfría offre des pinèdes éternelles',
            'de' => 'die Provincia, wo die Hauptstadt mit der Sierra de Guadarrama nur eine Stunde entfernt koexistiert, mittelalterliche Bergdörfer dem Trubel entfliehen und das Fuenfría-Tal ewige Kiefernwälder bietet',
            'zh' => '首都与仅一小时车程的瓜达拉马山脉共存，山区中世纪村庄逃离喧嚣，富恩弗里亚山谷提供永恒松林',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // BALEARES
    // ─────────────────────────────────────────────────────────────────────
    'baleares' => [
        'label' => 'Baleares', 'db' => 'Baleares',
        'region' => 'Islas Baleares',
        'lat' => 39.572, 'lng' => 2.646,
        'attractions' => ['Serra de Tramuntana', 'Coves d\'Artà', 'Menorca Reserva de la Biosfera', 'Ibiza Old Town'],
        'vibe' => [
            'es' => 'mucho más que turismo de sol y playa, con la Serra de Tramuntana como Patrimonio de la Humanidad en Mallorca, Menorca entera como Reserva de la Biosfera y Formentera con aguas cristalinas',
            'en' => 'much more than sun and beach tourism, with Mallorca\'s Serra de Tramuntana as a World Heritage Site, all of Menorca as a Biosphere Reserve and Formentera with crystal-clear waters',
            'fr' => 'bien plus que le tourisme balnéaire, avec la Serra de Tramuntana de Majorque Patrimoine Mondial, toute Minorque comme Réserve de Biosphère et Formentera aux eaux cristallines',
            'de' => 'viel mehr als Sonne-und-Strand-Tourismus, mit Mallorcas Serra de Tramuntana als Welterbe, ganz Menorca als Biosphärenreservat und Formentera mit kristallklarem Wasser',
            'zh' => '远不止阳光沙滩旅游，马略卡岛特拉蒙塔纳山脉是世界遗产，整个梅诺卡岛是生物圈保护区，福门特拉岛拥有清澈海水',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // CANARIAS (2 provincias)
    // ─────────────────────────────────────────────────────────────────────
    'las-palmas' => [
        'label' => 'Las Palmas', 'db' => 'Las Palmas',
        'region' => 'Canarias',
        'lat' => 28.124, 'lng' => -15.430,
        'attractions' => ['Parque Nacional del Teide (Tenerife-Las Palmas)', 'Gran Canaria interior', 'Dunas de Maspalomas', 'Lanzarote (Parques de Timanfaya)'],
        'vibe' => [
            'es' => 'las islas de la eterna primavera, donde el interior de Gran Canaria es un continente en miniatura, Lanzarote es un museo al aire libre de César Manrique y Fuerteventura tiene las mejores dunas de Europa',
            'en' => 'the islands of eternal spring, where Gran Canaria\'s interior is a continent in miniature, Lanzarote is an open-air museum by César Manrique and Fuerteventura has Europe\'s best dunes',
            'fr' => 'les îles du printemps éternel, où l\'intérieur de Grande Canarie est un continent en miniature, Lanzarote est un musée en plein air de César Manrique et Fuerteventura a les meilleures dunes d\'Europe',
            'de' => 'die Inseln des ewigen Frühlings, wo Gran Canarias Inneres ein Kontinent im Miniaturformat ist, Lanzarote ein Freilichtmuseum von César Manrique und Fuerteventura Europas beste Dünen hat',
            'zh' => '永恒春天之岛，大加纳利岛内陆是微型大陆，兰萨罗特岛是塞萨尔·曼里克的露天博物馆，富埃特文图拉岛拥有欧洲最美沙丘',
        ],
    ],
    'santa-cruz-de-tenerife' => [
        'label' => 'Santa Cruz de Tenerife', 'db' => 'Santa Cruz de Tenerife',
        'region' => 'Canarias',
        'lat' => 28.463, 'lng' => -16.252,
        'attractions' => ['Parque Nacional del Teide', 'Anaga (Reserva Biosfera)', 'La Gomera', 'El Hierro'],
        'vibe' => [
            'es' => 'la provincia del Teide, el volcán más alto de España y el tercer más alto del mundo desde su base oceánica, con La Gomera como reserva de bosque laurisilva y El Hierro como isla sostenible 100%',
            'en' => 'the province of Teide, Spain\'s highest volcano and the world\'s third highest from its oceanic base, with La Gomera as a laurel forest reserve and El Hierro as a 100% sustainable island',
            'fr' => 'la province du Teide, le volcan le plus haut d\'Espagne et le troisième plus haut du monde depuis sa base océanique, avec La Gomera comme réserve de forêt de laurisilve et El Hierro comme île 100% durable',
            'de' => 'die Provincia des Teide, Spaniens höchstem Vulkan und dem dritthöchsten der Welt von seiner ozeanischen Basis, mit La Gomera als Lorbeerwald-Reservat und El Hierro als 100% nachhaltiger Insel',
            'zh' => '特内里费火山所在省份，西班牙最高火山从海洋基底算起是世界第三高峰，拉戈梅拉是月桂森林保护区，耶罗岛是100%可持续小岛',
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
