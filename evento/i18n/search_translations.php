<?php
/**
 * search_translations.php — Traducciones del Buscador de Eventos
 * rutasrurales.io · /evento/
 * Idiomas: es · en · fr · zh (zh-CN Simplificado)
 */

const BUSCADOR_I18N = [

    // ── ESPAÑOL ──────────────────────────────────────────────────────────────
    'es' => [
        'lang_code'    => 'es',
        'lang_locale'  => 'es-ES',
        'lang_hreflang'=> 'es',
        'dir'          => 'ltr',
        'zh_date_fmt'  => false,
        'meta_title'   => 'Buscador de Eventos Rurales — Agenda Cultural España | rutasrurales.io',
        'meta_desc'    => 'Encuentra fiestas, conciertos, mercados y eventos culturales rurales en toda España. Más de 1.200 eventos verificados. ¡Busca gratis!',
        'og_title'     => 'Busca Eventos Culturales Rurales en España',
        'og_desc'      => 'Agenda cultural rural completa: fiestas patronales, mercados medievales, conciertos y gastronomía. Filtra por provincia, fecha y tipo.',
        'h1'           => 'Buscador de Eventos Culturales Rurales',
        'h2_results'   => 'Resultados de búsqueda',
        'h2_featured'  => 'Próximos eventos destacados',
        'search_placeholder' => 'Busca un evento, municipio o provincia…',
        'search_btn'         => 'Buscar',
        'search_clear'       => 'Limpiar',
        'filter_toggle'      => 'Filtros',
        'filter_province'    => 'Provincia',
        'filter_category'    => 'Categoría',
        'filter_date_from'   => 'Desde',
        'filter_free'        => 'Solo eventos gratuitos',
        'filter_apply'       => 'Aplicar filtros',
        'filter_reset'       => 'Resetear',
        'all_provinces'      => 'Todas las provincias',
        'all_categories'     => 'Todas las categorías',
        'results_count'      => '{N} eventos encontrados',
        'results_1'          => '1 evento encontrado',
        'results_0'          => 'No se encontraron eventos',
        'results_for'        => 'para',
        'loading'            => 'Buscando eventos…',
        'page_of'            => 'de',
        'prev'               => '← Anterior',
        'next'               => 'Siguiente →',
        'page_label'         => 'Página',
        'card_free'          => 'Entrada gratuita',
        'card_from'          => 'Desde',
        'card_see'           => 'Ver evento',
        'card_until'         => 'Hasta',
        'card_ongoing'       => 'En curso',
        'card_starts'        => 'Empieza',
        'no_results_h2'      => '😕 No encontramos eventos con esos criterios',
        'no_results_p'       => 'Prueba a cambiar los filtros, ampliar la fecha o buscar en otra provincia.',
        'no_results_cta'     => 'Ver todos los eventos',
        'aria_search'        => 'Formulario de búsqueda de eventos',
        'aria_results'       => 'Resultados de búsqueda de eventos',
        'aria_filters'       => 'Panel de filtros de búsqueda',
        'aria_loading'       => 'Cargando resultados…',
        'aria_page_curr'     => 'Página actual',
        'aria_close'         => 'Cerrar',
        'months_short'       => ['','ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'],
        'categories' => [
            1=>'Fiestas Populares',2=>'Fiestas Patronales',3=>'Fiestas Tradicionales',
            4=>'Romerías',5=>'Carnavales',6=>'Cultura y Espectáculos',7=>'Conciertos',
            8=>'Teatro',9=>'Exposiciones',10=>'Festivales de Música',11=>'Cine',
            12=>'Gastronomía y Ferias',13=>'Ferias Gastronómicas',14=>'Jornadas Gastronómicas',
            15=>'Mercados Tradicionales',16=>'Ferias de Productos Locales',17=>'Deportes',
            18=>'Carreras Populares',19=>'Maratones y Medias',20=>'Competiciones Ciclistas',
            21=>'Eventos Deportivos',22=>'Religión y Tradición',23=>'Semana Santa',
            24=>'Procesiones',25=>'Celebraciones Religiosas',
        ],
        'bc_home'=>'Inicio','bc_events'=>'Eventos','bc_search'=>'Buscador',
        'footer_events'=>'Eventos','footer_stays'=>'Alojamientos',
        'footer_places'=>'Lugares de interés','footer_legal'=>'Aviso legal','footer_cookies'=>'Cookies',
    ],

    // ── ENGLISH ───────────────────────────────────────────────────────────────
    'en' => [
        'lang_code'=>'en','lang_locale'=>'en-GB','lang_hreflang'=>'en','dir'=>'ltr','zh_date_fmt'=>false,
        'meta_title'   => 'Rural Events Search — Cultural Agenda Spain | rutasrurales.io',
        'meta_desc'    => 'Find rural festivals, concerts, markets and cultural events across Spain. Over 1,200 verified events. Search for free!',
        'og_title'     => 'Search Rural Cultural Events in Spain',
        'og_desc'      => 'Complete rural cultural agenda: patron saint festivals, medieval markets, concerts and gastronomy. Filter by province, date and type.',
        'h1'           => 'Rural Cultural Events Search',
        'h2_results'   => 'Search results',
        'h2_featured'  => 'Upcoming featured events',
        'search_placeholder'=>'Search for an event, town or province…',
        'search_btn'=>'Search','search_clear'=>'Clear','filter_toggle'=>'Filters',
        'filter_province'=>'Province','filter_category'=>'Category','filter_date_from'=>'From',
        'filter_free'=>'Free events only','filter_apply'=>'Apply filters','filter_reset'=>'Reset',
        'all_provinces'=>'All provinces','all_categories'=>'All categories',
        'results_count'=>'{N} events found','results_1'=>'1 event found','results_0'=>'No events found',
        'results_for'=>'for','loading'=>'Searching for events…','page_of'=>'of',
        'prev'=>'← Previous','next'=>'Next →','page_label'=>'Page',
        'card_free'=>'Free admission','card_from'=>'From','card_see'=>'See event',
        'card_until'=>'Until','card_ongoing'=>'Ongoing','card_starts'=>'Starts',
        'no_results_h2'=>'😕 No events match your criteria',
        'no_results_p'=>'Try changing the filters, widening the date range or searching in another province.',
        'no_results_cta'=>'View all events',
        'aria_search'=>'Event search form','aria_results'=>'Event search results',
        'aria_filters'=>'Search filters panel','aria_loading'=>'Loading results…',
        'aria_page_curr'=>'Current page','aria_close'=>'Close',
        'months_short'=>['','JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'],
        'categories' => [
            1=>'Popular Festivals',2=>'Patron Saint Festivals',3=>'Traditional Festivals',
            4=>'Pilgrimages',5=>'Carnivals',6=>'Culture & Shows',7=>'Concerts',
            8=>'Theatre',9=>'Exhibitions',10=>'Music Festivals',11=>'Cinema',
            12=>'Gastronomy & Fairs',13=>'Gastronomic Fairs',14=>'Gastronomic Days',
            15=>'Traditional Markets',16=>'Local Product Fairs',17=>'Sports',
            18=>'Popular Races',19=>'Marathons & Half-Marathons',20=>'Cycling Competitions',
            21=>'Sporting Events',22=>'Religion & Tradition',23=>'Holy Week',
            24=>'Processions',25=>'Religious Celebrations',
        ],
        'bc_home'=>'Home','bc_events'=>'Events','bc_search'=>'Search',
        'footer_events'=>'Events','footer_stays'=>'Accommodation',
        'footer_places'=>'Places of interest','footer_legal'=>'Legal notice','footer_cookies'=>'Cookies',
    ],

    // ── FRANÇAIS ──────────────────────────────────────────────────────────────
    'fr' => [
        'lang_code'=>'fr','lang_locale'=>'fr-FR','lang_hreflang'=>'fr','dir'=>'ltr','zh_date_fmt'=>false,
        'meta_title'   => 'Recherche d\'Événements Ruraux — Agenda Culturel Espagne | rutasrurales.io',
        'meta_desc'    => 'Trouvez des fêtes rurales, concerts, marchés et événements culturels dans toute l\'Espagne. Plus de 1 200 événements vérifiés.',
        'og_title'     => 'Rechercher des Événements Culturels Ruraux en Espagne',
        'og_desc'      => 'Agenda culturel rural complet : fêtes patronales, marchés médiévaux, concerts et gastronomie.',
        'h1'           => 'Recherche d\'Événements Culturels Ruraux',
        'h2_results'   => 'Résultats de recherche',
        'h2_featured'  => 'Prochains événements à la une',
        'search_placeholder'=>'Rechercher un événement, une ville ou une province…',
        'search_btn'=>'Rechercher','search_clear'=>'Effacer','filter_toggle'=>'Filtres',
        'filter_province'=>'Province','filter_category'=>'Catégorie','filter_date_from'=>'À partir du',
        'filter_free'=>'Événements gratuits uniquement','filter_apply'=>'Appliquer','filter_reset'=>'Réinitialiser',
        'all_provinces'=>'Toutes les provinces','all_categories'=>'Toutes les catégories',
        'results_count'=>'{N} événements trouvés','results_1'=>'1 événement trouvé','results_0'=>'Aucun événement trouvé',
        'results_for'=>'pour','loading'=>'Recherche en cours…','page_of'=>'sur',
        'prev'=>'← Précédent','next'=>'Suivant →','page_label'=>'Page',
        'card_free'=>'Entrée gratuite','card_from'=>'À partir de','card_see'=>'Voir l\'événement',
        'card_until'=>'Jusqu\'au','card_ongoing'=>'En cours','card_starts'=>'Commence le',
        'no_results_h2'=>'😕 Aucun événement ne correspond à vos critères',
        'no_results_p'=>'Essayez de modifier les filtres ou de chercher dans une autre province.',
        'no_results_cta'=>'Voir tous les événements',
        'aria_search'=>'Formulaire de recherche','aria_results'=>'Résultats de recherche',
        'aria_filters'=>'Panneau de filtres','aria_loading'=>'Chargement des résultats…',
        'aria_page_curr'=>'Page actuelle','aria_close'=>'Fermer',
        'months_short'=>['','JAN','FÉV','MAR','AVR','MAI','JUN','JUL','AOÛ','SEP','OCT','NOV','DÉC'],
        'categories' => [
            1=>'Fêtes Populaires',2=>'Fêtes Patronales',3=>'Fêtes Traditionnelles',
            4=>'Pèlerinages',5=>'Carnavals',6=>'Culture & Spectacles',7=>'Concerts',
            8=>'Théâtre',9=>'Expositions',10=>'Festivals de Musique',11=>'Cinéma',
            12=>'Gastronomie & Foires',13=>'Foires Gastronomiques',14=>'Journées Gastronomiques',
            15=>'Marchés Traditionnels',16=>'Foires de Produits Locaux',17=>'Sports',
            18=>'Courses Populaires',19=>'Marathons',20=>'Compétitions Cyclistes',
            21=>'Événements Sportifs',22=>'Religion & Tradition',23=>'Semaine Sainte',
            24=>'Processions',25=>'Célébrations Religieuses',
        ],
        'bc_home'=>'Accueil','bc_events'=>'Événements','bc_search'=>'Recherche',
        'footer_events'=>'Événements','footer_stays'=>'Hébergements',
        'footer_places'=>'Lieux d\'intérêt','footer_legal'=>'Mentions légales','footer_cookies'=>'Cookies',
    ],

    // ── 中文 (zh-CN — Chino Simplificado) ────────────────────────────────────
    'zh' => [
        'lang_code'=>'zh','lang_locale'=>'zh-CN',
        'lang_hreflang'=>'zh-Hans', // Estándar W3C para Chino Simplificado
        'dir'=>'ltr','zh_date_fmt'=>true, // Activa formato YYYY年MM月DD日
        'meta_title'   => '乡村活动搜索 — 西班牙文化日历 | rutasrurales.io',
        'meta_desc'    => '在西班牙各地寻找乡村节日、音乐会、集市和文化活动。超过1,200个已核实活动。免费搜索！',
        'og_title'     => '搜索西班牙乡村文化活动',
        'og_desc'      => '完整的乡村文化日历：守护圣人节日、中世纪集市、音乐会和美食。',
        'h1'           => '乡村文化活动搜索',
        'h2_results'   => '搜索结果',
        'h2_featured'  => '即将到来的精选活动',
        'search_placeholder'=>'搜索活动、城镇或省份…',
        'search_btn'=>'搜索','search_clear'=>'清除','filter_toggle'=>'筛选',
        'filter_province'=>'省份','filter_category'=>'类别','filter_date_from'=>'开始日期',
        'filter_free'=>'仅免费活动','filter_apply'=>'应用筛选','filter_reset'=>'重置',
        'all_provinces'=>'所有省份','all_categories'=>'所有类别',
        'results_count'=>'找到 {N} 项活动','results_1'=>'找到 1 项活动','results_0'=>'未找到活动',
        'results_for'=>'关于','loading'=>'正在搜索活动…','page_of'=>'共',
        'prev'=>'← 上一页','next'=>'下一页 →','page_label'=>'第',
        'card_free'=>'免费入场','card_from'=>'起价','card_see'=>'查看活动',
        'card_until'=>'至','card_ongoing'=>'进行中','card_starts'=>'开始',
        'no_results_h2'=>'😕 没有找到符合条件的活动',
        'no_results_p'=>'请尝试更改筛选条件、扩大日期范围或在其他省份搜索。',
        'no_results_cta'=>'查看所有活动',
        'aria_search'=>'活动搜索表单','aria_results'=>'活动搜索结果',
        'aria_filters'=>'搜索筛选面板','aria_loading'=>'正在加载结果…',
        'aria_page_curr'=>'当前页','aria_close'=>'关闭',
        'months_short'=>['','1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'],
        'categories' => [
            1=>'民间节日',2=>'守护圣人节',3=>'传统节日',4=>'朝圣活动',5=>'狂欢节',
            6=>'文化与演出',7=>'音乐会',8=>'戏剧',9=>'展览',10=>'音乐节',11=>'电影',
            12=>'美食与集市',13=>'美食博览会',14=>'美食节',15=>'传统市场',
            16=>'本地产品集市',17=>'体育',18=>'大众赛跑',19=>'马拉松与半马',
            20=>'自行车赛',21=>'体育赛事',22=>'宗教与传统',23=>'圣周',
            24=>'宗教游行',25=>'宗教庆典',
        ],
        'bc_home'=>'首页','bc_events'=>'活动','bc_search'=>'搜索',
        'footer_events'=>'文化活动','footer_stays'=>'乡村住宿',
        'footer_places'=>'景点','footer_legal'=>'法律声明','footer_cookies'=>'Cookies',
    ],
];

/** Carga traducciones del buscador con fallback a español */
function getBuscadorTranslations(string $lang): array
{
    return BUSCADOR_I18N[$lang] ?? BUSCADOR_I18N['es'];
}

/**
 * Formatea fecha según el idioma activo.
 * zh → YYYY年MM月DD日  |  resto → DD MMM YYYY
 * @return array ['dia'=>int, 'mes'=>string, 'full'=>string]
 */
function formatEventDate(string $date_str, array $t): array
{
    if (empty($date_str)) return ['dia'=>'--','mes'=>'---','full'=>''];
    [$y,$m,$d] = explode('-', $date_str);
    $mes = $t['months_short'][(int)$m] ?? $m;
    if (!empty($t['zh_date_fmt'])) {
        return ['dia'=>(int)$d,'mes'=>$mes,'full'=>"{$y}年{$m}月{$d}日"];
    }
    return ['dia'=>(int)$d,'mes'=>$mes,'full'=>"{$d} {$mes} {$y}"];
}

