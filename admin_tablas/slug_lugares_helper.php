<?php
/**
 * HELPER: GENERACION DE SLUGS PARA LUGARES DE INTERES (i18n)
 * v2 - 25/09/2026
 *
 * LOGICA: {categoria_traducida}-{nombre_propio_limpio}-{municipio}
 *
 * Ejemplo EN: "Bodega AlmaRoja"|"Bodega"|"Fermoselle"    -> winery-almaroja-fermoselle
 * Ejemplo FR: "Castillo de Almansa"|"Castillo"|"Almansa" -> chateau-almansa
 * Ejemplo ZH: "Iglesia de San Pedro"|"Iglesia"|"Zamora"  -> jiaotang-pedro-zamora
 *
 * FALLOS CORREGIDOS (v1 -> v2):
 *   1. limpiarNombre(): do-while para categorias compuestas ("Parque Natural")
 *      -> sin palabras residuales al inicio (ej. "natural-" que quedaba suelto).
 *   2. PALABRAS_TRIVIALES: multiples pasadas con re-check, no un solo break.
 *   3. slugificar(): detecta CJK y los elimina antes de iconv()
 *      -> nunca genera slugs vacios con nombres que contengan caracteres Han.
 *   4. Mapeo zh: 'iglesia' => 'jiaotang' (corregido typo historico 'jaotang').
 *   5. Nuevas categorias: 'santuario', 'alcazar', 'puerta'.
 *
 * DECISION ZH (Opcion A):
 *   Los nombres de lugares son toponimos. El prefijo de categoria viene
 *   en Pinyin (jiaotang, dasheng...) y el cuerpo del slug usa el nombre
 *   propio espanol limpio, igual que EN/FR/DE. Solo cambia el prefijo.
 */

/**
 * MAPA PRIMARIO: slugs exactos de la tabla categories_places → prefijos i18n.
 * Prioridad alta: se comprueba antes que CATEGORIAS_SLUG (fuzzy).
 * Actualizar aquí cada vez que se añada una fila en categories_places.
 *
 * Campos BD visibles:
 *   id | name                    | slug
 *    1 | Monumentos              | monumentos
 *    2 | Castillos               | castillos
 *    3 | Iglesias                | iglesias
 *    4 | Ermitas                 | ermitas
 *    5 | Monasterios             | monasterios
 *    6 | Naturaleza              | naturaleza
 *    7 | Miradores               | miradores
 *    8 | Parques Naturales       | parques-naturales
 *    9 | Ríos y Lagunas          | rios-lagunas
 *   10 | Bosques                 | bosques
 *   11 | Patrimonio Cultural     | patrimonio-cultural
 *   12 | Museos                  | museos
 *   13 | Yacimientos Arqueológicos | yacimientos
 *   14 | Centros de Interpretación | centros-interpretacion
 *   15 | Pueblos con Encanto     | pueblos
 *   16 | Conjuntos Históricos    | conjuntos-historicos
 *   17 | Villas Medievales       | villas-medievales
 *   18 | Bodegas                 | bodegas   (también 'Bodeags' typo — se cubre igual)
 *   19 | Restauración            | restauracion
 *   22 | Parques Temáticos       | parques-tematicos
 *      | Gastronomía             | gastronomia
 *      | Enoturismo              | enoturismo
 */
const CATEGORIAS_BD_SLUG = [
    // --- Religioso ---
    'iglesias'               => ['en'=>'church',               'fr'=>'eglise',              'de'=>'kirche',               'zh'=>'jiaotang'],
    'ermitas'                => ['en'=>'hermitage',            'fr'=>'ermitage',             'de'=>'einsiedelei',          'zh'=>'yinxiuchu'],
    'monasterios'            => ['en'=>'monastery',            'fr'=>'monastere',            'de'=>'kloster',              'zh'=>'si'],
    // --- Castillos / Civil ---
    'castillos'              => ['en'=>'castle',               'fr'=>'chateau',              'de'=>'burg',                 'zh'=>'chengbao'],
    'monumentos'             => ['en'=>'monument',             'fr'=>'monument',             'de'=>'denkmal',              'zh'=>'jinianbei'],
    // --- Naturaleza ---
    'naturaleza'             => ['en'=>'nature',               'fr'=>'nature',               'de'=>'natur',                'zh'=>'ziran'],
    'parques-naturales'      => ['en'=>'natural-park',         'fr'=>'parc-naturel',         'de'=>'naturpark',            'zh'=>'ziran-gongyuan'],
    'rios-lagunas'           => ['en'=>'rivers-lakes',         'fr'=>'rivieres-lacs',        'de'=>'fluesse-seen',         'zh'=>'heli-hupao'],
    // Alias: slugificar("Ríos y Lagunas") = "rios-y-lagunas"
    'rios-y-lagunas'         => ['en'=>'rivers-lakes',         'fr'=>'rivieres-lacs',        'de'=>'fluesse-seen',         'zh'=>'heli-hupao'],
    'bosques'                => ['en'=>'forest',               'fr'=>'foret',                'de'=>'wald',                 'zh'=>'senlin'],
    // --- Miradores ---
    'miradores'              => ['en'=>'viewpoint',            'fr'=>'belvedere',            'de'=>'aussichtspunkt',       'zh'=>'guanjingdian'],
    // --- Cultura / Historia ---
    'patrimonio-cultural'    => ['en'=>'cultural-heritage',    'fr'=>'patrimoine-culturel',  'de'=>'kulturerbe',           'zh'=>'wenhua-yichan'],
    'museos'                 => ['en'=>'museum',               'fr'=>'musee',                'de'=>'museum',               'zh'=>'bowuguan'],
    'yacimientos'            => ['en'=>'archaeological-site',  'fr'=>'site-archeologique',   'de'=>'ausgrabungsstaette',   'zh'=>'kaoguzhi'],
    'centros-interpretacion'     => ['en'=>'interpretation-centre','fr'=>'centre-interpretation','de'=>'besucherzentrum',      'zh'=>'jieshizhongxin'],
    // Alias: slugificar("Centros de Interpretación") = "centros-de-interpretacion"
    'centros-de-interpretacion'  => ['en'=>'interpretation-centre','fr'=>'centre-interpretation','de'=>'besucherzentrum',      'zh'=>'jieshizhongxin'],
    // --- Pueblos / Conjuntos ---
    'pueblos'                => ['en'=>'charming-village',     'fr'=>'village-de-charme',    'de'=>'malerisches-dorf',     'zh'=>'tese-xiaozhen'],
    'conjuntos-historicos'   => ['en'=>'historic-district',    'fr'=>'centre-historique',    'de'=>'altstadt',             'zh'=>'lishi-jiequ'],
    'villas-medievales'      => ['en'=>'medieval-town',        'fr'=>'cite-medievale',       'de'=>'mittelalterliche-stadt','zh'=>'zhongshi-gucheng'],
    // --- Gastronomía / Vino ---
    'bodegas'                => ['en'=>'winery',               'fr'=>'cave-a-vin',           'de'=>'weinkeller',           'zh'=>'jiuzhuang'],
    'enoturismo'             => ['en'=>'wine-tourism',         'fr'=>'oenotourisme',         'de'=>'weintourismus',        'zh'=>'jiuqu-lvyou'],
    'gastronomia'            => ['en'=>'gastronomy',           'fr'=>'gastronomie',          'de'=>'gastronomie',          'zh'=>'meishi'],
    'restauracion'           => ['en'=>'restaurant',           'fr'=>'restaurant',           'de'=>'restaurant',           'zh'=>'canting'],
    // --- Ocio ---
    'parques-tematicos'      => ['en'=>'theme-park',           'fr'=>'parc-de-loisirs',      'de'=>'freizeitpark',         'zh'=>'zhuti-gongyuan'],
];

const CATEGORIAS_SLUG = [
    'monasterio' => ['en'=>'monastery', 'fr'=>'monastere', 'de'=>'kloster', 'zh'=>'si'],
    'convento' => ['en'=>'convent', 'fr'=>'couvent', 'de'=>'konvent', 'zh'=>'xiuyuan'],
    'catedral' => ['en'=>'cathedral', 'fr'=>'cathedrale', 'de'=>'kathedrale', 'zh'=>'dasheng'],
    'santuario' => ['en'=>'sanctuary', 'fr'=>'sanctuaire', 'de'=>'heiligtum', 'zh'=>'shengdi'],
    'ermita' => ['en'=>'hermitage', 'fr'=>'ermitage', 'de'=>'einsiedelei', 'zh'=>'yinxiuchu'],
    'capilla' => ['en'=>'chapel', 'fr'=>'chapelle', 'de'=>'kapelle', 'zh'=>'xiaojiaotang'],
    'iglesia' => ['en'=>'church', 'fr'=>'eglise', 'de'=>'kirche', 'zh'=>'jiaotang'],
    'castillo' => ['en'=>'castle', 'fr'=>'chateau', 'de'=>'burg', 'zh'=>'chengbao'],
    'palacio' => ['en'=>'palace', 'fr'=>'palais', 'de'=>'palast', 'zh'=>'gongguan'],
    'alcazar' => ['en'=>'alcazar', 'fr'=>'alcazar', 'de'=>'alcazar', 'zh'=>'chengbao'],
    'muralla' => ['en'=>'city-walls', 'fr'=>'remparts', 'de'=>'stadtmauer', 'zh'=>'chengqiang'],
    'torre' => ['en'=>'tower', 'fr'=>'tour', 'de'=>'turm', 'zh'=>'ta'],
    'arco' => ['en'=>'arch', 'fr'=>'arc', 'de'=>'bogen', 'zh'=>'gongjian'],
    'puerta' => ['en'=>'gateway', 'fr'=>'porte', 'de'=>'stadttor', 'zh'=>'chengmen'],
    'puente' => ['en'=>'bridge', 'fr'=>'pont', 'de'=>'bruecke', 'zh'=>'qiao'],
    'conjunto' => ['en'=>'historic-district', 'fr'=>'centre-historique', 'de'=>'altstadt', 'zh'=>'lishi-jiequ'],
    'casco' => ['en'=>'historic-district', 'fr'=>'centre-historique', 'de'=>'altstadt', 'zh'=>'lishi-jiequ'],
    'villa' => ['en'=>'historic-town', 'fr'=>'ville-historique', 'de'=>'historische-stadt', 'zh'=>'gucheng'],
    'plaza' => ['en'=>'square', 'fr'=>'place', 'de'=>'platz', 'zh'=>'guangchang'],
    'fuente' => ['en'=>'fountain', 'fr'=>'fontaine', 'de'=>'brunnen', 'zh'=>'quantou'],
    'molino' => ['en'=>'mill', 'fr'=>'moulin', 'de'=>'muehle', 'zh'=>'mofang'],
    'museo' => ['en'=>'museum', 'fr'=>'musee', 'de'=>'museum', 'zh'=>'bowuguan'],
    'teatro' => ['en'=>'theatre', 'fr'=>'theatre', 'de'=>'theater', 'zh'=>'juchang'],
    'mercado' => ['en'=>'market', 'fr'=>'marche', 'de'=>'markt', 'zh'=>'shichang'],
    'yacimiento' => ['en'=>'archaeological-site', 'fr'=>'site-archeologique', 'de'=>'ausgrabungsstaette', 'zh'=>'kaoguzhi'],
    'ruinas' => ['en'=>'ruins', 'fr'=>'ruines', 'de'=>'ruinen', 'zh'=>'feixu'],
    'monumento' => ['en'=>'monument', 'fr'=>'monument', 'de'=>'denkmal', 'zh'=>'jinianbei'],
    'dolmen' => ['en'=>'dolmen', 'fr'=>'dolmen', 'de'=>'dolmen', 'zh'=>'shishi-muzhang'],
    'calzada' => ['en'=>'roman-road', 'fr'=>'voie-romaine', 'de'=>'roemerstrasse', 'zh'=>'luoma-gudao'],
    'parque' => ['en'=>'nature-reserve', 'fr'=>'reserve-naturelle', 'de'=>'naturpark', 'zh'=>'ziranbaohuqu'],
    'natural' => ['en'=>'nature-reserve', 'fr'=>'reserve-naturelle', 'de'=>'naturpark', 'zh'=>'ziranbaohuqu'],
    'reserva' => ['en'=>'nature-reserve', 'fr'=>'reserve-naturelle', 'de'=>'naturschutzgebiet', 'zh'=>'ziranbaohuqu'],
    'mirador' => ['en'=>'viewpoint', 'fr'=>'belvedere', 'de'=>'aussichtspunkt', 'zh'=>'guanjingdian'],
    'cueva' => ['en'=>'cave', 'fr'=>'grotte', 'de'=>'hoehle', 'zh'=>'dongxue'],
    'laguna' => ['en'=>'lake', 'fr'=>'lac', 'de'=>'see', 'zh'=>'hu'],
    'lago' => ['en'=>'lake', 'fr'=>'lac', 'de'=>'see', 'zh'=>'hu'],
    'playa' => ['en'=>'beach', 'fr'=>'plage', 'de'=>'strand', 'zh'=>'haitan'],
    'bosque' => ['en'=>'forest', 'fr'=>'foret', 'de'=>'wald', 'zh'=>'senlin'],
    'cascada' => ['en'=>'waterfall', 'fr'=>'cascade', 'de'=>'wasserfall', 'zh'=>'pubao'],
    'bodega' => ['en'=>'winery', 'fr'=>'cave-a-vin', 'de'=>'weinkeller', 'zh'=>'jiuzhuang'],
    'restaurante' => ['en'=>'restaurant', 'fr'=>'restaurant', 'de'=>'restaurant', 'zh'=>'canting'],
    'bar' => ['en'=>'bar', 'fr'=>'bar', 'de'=>'bar', 'zh'=>'jiuba'],
    'finca' => ['en'=>'estate', 'fr'=>'domaine', 'de'=>'gut', 'zh'=>'zhuangyuan'],
    'hacienda' => ['en'=>'estate', 'fr'=>'domaine', 'de'=>'gut', 'zh'=>'zhuangyuan'],
    'lugar' => ['en'=>'place', 'fr'=>'lieu', 'de'=>'ort', 'zh'=>'difang'],
];

const PALABRAS_TRIVIALES = [
    'de-la', 'de-los', 'de-las', 'de-le', 'de-l',
    'del', 'al',
    'el', 'la', 'los', 'las',
    'un', 'una', 'unos', 'unas',
    'de', 'en', 'y',
    // Sustantivos genéricos que aparecen en el nombre y ya están cubiertos por el prefijo de categoría
    'pueblo', 'villa', 'villas', 'rio', 'lago', 'bosque', 'mirador', 'centro', 'centros',
    'interpretacion', 'natural', 'parque', 'conjunto', 'patrimonio', 'cultural',
    'yacimiento', 'mirador', 'miradero',
];

function contieneCJK(string $texto): bool
{
    return (bool) preg_match('/[\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}\x{F900}-\x{FAFF}]/u', $texto);
}

// FIX v2: elimina CJK antes de iconv (evita slugs vacios con caracteres Han)
function slugificar(string $texto): string
{
    if (contieneCJK($texto)) {
        $texto = preg_replace('/[\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}\x{F900}-\x{FAFF}]/u', ' ', $texto);
    }
    $conv  = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = ($conv !== false) ? $conv : $texto;
    $texto = strtolower($texto);
    $texto = str_replace(["'", '"', '`', '.', ',', ';', ':', '!', '?', '(', ')', '[', ']', '/'], '', $texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim(preg_replace('/-{2,}/', '-', $texto), '-');
}

/**
 * Traduce el nombre/slug de una categoría al prefijo correcto en el idioma dado.
 *
 * @param string $categoriaNombre  Nombre ES de la categoría (ej: "Parques Naturales")
 *                                  o su slug BD (ej: "parques-naturales").
 * @param string $idioma           Código ISO 639-1: en | fr | de | zh
 * @return string  Prefijo slugificado (ej: "natural-park", "parc-naturel"…)
 */
function traducirCategoria(string $categoriaNombre, string $idioma): string
{
    // 1) Intentar coincidencia exacta con el slug BD (prioridad máxima).
    //    Normaliza a minúsculas + ASCII + guiones para cubrir variantes.
    $slugBD = slugificar($categoriaNombre);   // "parques-naturales", "ermitas", etc.
    if ($slugBD && isset(CATEGORIAS_BD_SLUG[$slugBD])) {
        return CATEGORIAS_BD_SLUG[$slugBD][$idioma]
            ?? CATEGORIAS_BD_SLUG[$slugBD]['en']
            ?? 'place';
    }

    // 2) Coincidencia parcial de palabra dentro del nombre normalizado (fallback fuzzy).
    $normalizado = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $categoriaNombre) ?: $categoriaNombre);
    foreach (CATEGORIAS_SLUG as $clave => $traducciones) {
        if (strpos($normalizado, $clave) !== false) {
            return $traducciones[$idioma] ?? $traducciones['en'] ?? 'place';
        }
    }

    // 3) Sin coincidencia: slugificar el nombre tal cual.
    return $slugBD ?: 'place';
}

// FIX v2: do-while para categorias compuestas + multiples pasadas triviales
// FIX v3: strip de segmentos del slug de categoría BD uno a uno al inicio del nombre
function limpiarNombre(string $nombre, string $categoriaNombre): string
{
    $nombreSlug = slugificar($nombre);
    $catNorm    = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $categoriaNombre) ?: $categoriaNombre);

    // --- Paso 1: eliminar slug completo de categoría si aparece al inicio ---
    $catSlugCompleto = slugificar($categoriaNombre);
    if ($catSlugCompleto) {
        // Caso A: slug_nombre empieza por slug_cat + '-algo' (hay texto propio después)
        if (strpos($nombreSlug, $catSlugCompleto . '-') === 0) {
            $nombreSlug = substr($nombreSlug, strlen($catSlugCompleto) + 1);
        }
        // Caso B: slug_nombre == slug_cat exacto (nombre == categoría, sin texto propio)
        elseif ($nombreSlug === $catSlugCompleto) {
            $nombreSlug = '';
        }
        // Caso C: strip palabra a palabra de los segmentos del slug de categoría (ej: "villas-medievales" → quitar "villas", luego "medievales")
        else {
            $catSegmentos = explode('-', $catSlugCompleto);
            $cambio = true;
            while ($cambio) {
                $cambio = false;
                foreach ($catSegmentos as $seg) {
                    if ($seg !== '' && strpos($nombreSlug, $seg . '-') === 0) {
                        $nombreSlug = substr($nombreSlug, strlen($seg) + 1);
                        $cambio = true;
                        break;
                    }
                    // también al final
                    if ($seg !== '' && substr($nombreSlug, -(strlen($seg))) === $seg
                        && strlen($nombreSlug) > strlen($seg)
                        && $nombreSlug[strlen($nombreSlug) - strlen($seg) - 1] === '-') {
                        $nombreSlug = rtrim(substr($nombreSlug, 0, -strlen($seg)), '-');
                        $cambio = true;
                        break;
                    }
                }
            }
        }
    }

    // --- Paso 2: fallback fuzzy con palabras clave de CATEGORIAS_SLUG ---
    if ($nombreSlug !== '') {
        $cambio = true;
        while ($cambio) {
            $cambio = false;
            foreach (array_keys(CATEGORIAS_SLUG) as $clave) {
                if (strpos($catNorm, $clave) !== false) {
                    $claveSlug = slugificar($clave);
                    if ($claveSlug && strpos($nombreSlug, $claveSlug . '-') === 0) {
                        $nombreSlug = substr($nombreSlug, strlen($claveSlug) + 1);
                        $cambio     = true;
                    }
                }
            }
        }
    }

    // --- Paso 3: eliminar palabras triviales al inicio ---
    $cambio = true;
    while ($cambio) {
        $cambio = false;
        foreach (PALABRAS_TRIVIALES as $palabra) {
            if (strpos($nombreSlug, $palabra . '-') === 0) {
                $nombreSlug = substr($nombreSlug, strlen($palabra) + 1);
                $cambio     = true;
                break;
            }
        }
    }

    return trim($nombreSlug, '-');
}

function nombreSinMunicipio(string $nombreLimpio, string $muniSlug): string
{
    if (empty($nombreLimpio)) return '';
    if (strpos($muniSlug, $nombreLimpio) !== false) return '';
    $muniPartes = explode('-', $muniSlug);
    foreach ($muniPartes as $parte) {
        if (strlen($parte) >= 4 && substr($nombreLimpio, -strlen($parte)) === $parte) {
            $nombreLimpio = rtrim(substr($nombreLimpio, 0, -strlen($parte)), '-');
            break;
        }
    }
    return trim($nombreLimpio, '-');
}

function generarSlugLugar(string $nombre, string $categoriaNombre, string $municipio, string $idioma): string
{
    $catPrefix    = traducirCategoria($categoriaNombre, $idioma);
    $muniSlug     = slugificar($municipio);
    $nombreLimpio = limpiarNombre($nombre, $categoriaNombre);
    $nombreLimpio = nombreSinMunicipio($nombreLimpio, $muniSlug);

    if (empty($nombreLimpio) || $nombreLimpio === $muniSlug) {
        $slug = $catPrefix . '-' . $muniSlug;
    } else {
        $slug = $catPrefix . '-' . $nombreLimpio . '-' . $muniSlug;
    }

    return trim(preg_replace('/-{2,}/', '-', $slug), '-');
}
