<?php
/**
 * Configuración de Temas para Landings SEO
 * Define qué categorías y subcategorías de cada tabla componen un "Tema".
 * Las claves del array son los SLUGS que se usarán en la URL (Long Tail SEO).
 */

$THEMES_CONFIG = [
    'gastronomia-en-soria-y-castilla' => [
        'title' => 'Gastronomía y Restauración en Soria',
        'meta_title' => 'Turismo Gastronómico en Soria: Mejores Restaurantes y Bodegas',
        'meta_description' => 'Descubre la riqueza culinaria de Soria y Castilla. Los mejores restaurantes, bodegas tradicionales y jornadas gastronómicas.',
        'content_text' => 'La gastronomía soriana es un tesoro por descubrir. Desde el famoso torrezno de Soria hasta las delicadas trufas negras y setas de temporada, nuestra región ofrece una experiencia sensorial única.',
        'filters' => [
            'places' => ['subcategory_ids' => [18, 19]], // Bodegas y Restauración
            'events' => ['category_ids' => [12, 13, 14, 15, 16]], // Ferias y Jornadas Gastronómicas
            'activities' => ['category_ids' => [5]], 
            'accommodations' => ['category_ids' => []]
        ]
    ],
    'semana-santa-rural-tradiciones' => [
        'title' => 'Semana Santa y Tradiciones Rurales',
        'meta_title' => 'Semana Santa en Castilla: Procesiones, Tradición y Turismo Rural',
        'meta_description' => 'Vive la Semana Santa más auténtica en los pueblos de Castilla. Procesiones históricas, silencio y tradición en un entorno rural único.',
        'content_text' => 'La Semana Santa en nuestros pueblos es una experiencia de recogimiento y belleza plástica inigualable. Declaradas en muchos casos de Interés Turístico Nacional.',
        'filters' => [
            'events' => ['category_ids' => [23, 24, 25]], // Semana Santa y Procesiones
            'places' => ['subcategory_ids' => [3, 4, 5]], // Iglesias, Ermitas, Monasterios
            'activities' => ['category_ids' => []],
            'accommodations' => ['category_ids' => []]
        ]
    ],
    'enoturismo-y-bodegas-ribera-del-duero' => [
        'title' => 'Enoturismo y Bodegas en la Ribera',
        'meta_title' => 'Rutas del Vino: Visita las mejores Bodegas y Viñedos de la Ribera del Duero',
        'meta_description' => 'Sumérgete en la cultura del vino. Visitas a bodegas subterráneas, catas dirigidas y paseos entre viñedos centenarios en Soria y Burgos.',
        'content_text' => 'Nuestra tierra respira vino. Descubre la historia de nuestras bodegas y disfruta de experiencias sensoriales únicas en torno a la vid y el Duero.',
        'filters' => [
            'places' => ['subcategory_ids' => [18]], // Solo Bodegas
            'activities' => ['category_ids' => [5]], // Catas y visitas
            'events' => ['category_ids' => [13]], // Ferias del vino
            'accommodations' => ['category_ids' => []]
        ]
    ],
    'naturaleza-y-senderismo-en-el-norte-de-espana' => [
        'title' => 'Naturaleza y Senderismo',
        'meta_title' => 'Turismo de Naturaleza: Parques Naturales, Ríos y Bosques en el Norte',
        'meta_description' => 'Desconecta en los entornos naturales más espectaculares. Rutas por bosques, lagunas y miradores con vistas increíbles en Soria y alrededores.',
        'content_text' => 'Respira aire puro en nuestros espacios protegidos. Desde el Cañón del Río Lobos hasta las cumbres de Urbión, la naturaleza más virgen te espera.',
        'filters' => [
            'places' => ['subcategory_ids' => [6, 7, 8, 9, 10]], // Naturaleza, Miradores, Parques, Ríos, Bosques
            'activities' => ['category_ids' => [1, 2]], 
            'events' => ['category_ids' => []],
            'accommodations' => ['category_ids' => []]
        ]
    ],
    'patrimonio-historico-y-castillos-medievales' => [
        'title' => 'Patrimonio Histórico y Castillos',
        'meta_title' => 'Ruta del Patrimonio: Castillos, Monumentos y Villas Medievales en Castilla',
        'meta_description' => 'Viaja en el tiempo a través de nuestro patrimonio. Castillos imponentes, iglesias románicas y villas medievales llenas de historia.',
        'content_text' => 'Nuestra región atesora siglos de historia grabados en piedra. Descubre fortalezas fronterizas y joyas del románico en cada rincón de nuestra geografía.',
        'filters' => [
            'places' => ['subcategory_ids' => [1, 2, 11, 13, 16, 17]], // Monumentos, Castillos, Patrimonio, Yacimientos, Conjuntos, Villas
            'activities' => ['category_ids' => [3]], 
            'events' => ['category_ids' => [9]], 
            'accommodations' => ['category_ids' => []]
        ]
    ],
    'eclipse-solar-total-2026-norte-espana' => [
        'title' => 'Eclipse Solar Total 2026',
        'meta_title' => 'Eclipse Solar Total 2026 en el Norte de España: Dónde verlo y Alojamientos',
        'meta_description' => 'Prepárate para el eclipse solar total de agosto 2026. Los mejores lugares de observación y alojamientos disponibles para este evento astronómico único.',
        'content_text' => 'El 12 de agosto de 2026, nuestra región será uno de los mejores lugares del mundo para presenciar el eclipse solar total. ¡No te quedes sin sitio y reserva ya tu alojamiento rural!',
        'filters' => [
            'places' => ['subcategory_ids' => [7]], // Miradores (puntos de observación)
            'events' => ['category_ids' => [6]], // Cultura y Espectáculos
            'activities' => ['category_ids' => [4]], 
            'accommodations' => ['category_ids' => []]
        ]
    ]
];
?>