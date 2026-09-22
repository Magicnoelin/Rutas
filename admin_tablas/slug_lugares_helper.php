<?php
/**
 * HELPER: GENERACIÓN DE SLUGS PARA LUGARES DE INTERÉS (i18n)
 *
 * LÓGICA:  {categoría_traducida}-{nombre_limpio}-{municipio}
 *
 * Ejemplo EN: "Bodega AlmaRoja" | "Bodega" | "Fermoselle" → winery-almaroja-fermoselle
 * Ejemplo FR: "Castillo de Almansa" | "Castillo" | "Almansa" → chateau-almansa
 */

// Tabla de traducción de categorías por idioma
const CATEGORIAS_SLUG = [
    'bodega'      => ['en'=>'winery',              'fr'=>'cave-a-vin',         'de'=>'weinkeller',         'zh'=>'jiuzhuang'],
    'castillo'    => ['en'=>'castle',              'fr'=>'chateau',            'de'=>'burg',               'zh'=>'chengbao'],
    'palacio'     => ['en'=>'palace',              'fr'=>'palais',             'de'=>'palast',             'zh'=>'gongguan'],
    'catedral'    => ['en'=>'cathedral',           'fr'=>'cathedrale',         'de'=>'kathedrale',         'zh'=>'dasheng'],
    'iglesia'     => ['en'=>'church',              'fr'=>'eglise',             'de'=>'kirche',             'zh'=>'jiaotang'],
    'monasterio'  => ['en'=>'monastery',           'fr'=>'monastere',          'de'=>'kloster',            'zh'=>'si'],
    'convento'    => ['en'=>'convent',             'fr'=>'couvent',            'de'=>'konvent',            'zh'=>'xiuyuan'],
    'ermita'      => ['en'=>'hermitage',           'fr'=>'ermitage',           'de'=>'einsiedelei',        'zh'=>'yinxiuchu'],
    'capilla'     => ['en'=>'chapel',              'fr'=>'chapelle',           'de'=>'kapelle',            'zh'=>'xiaojiaotang'],
    'torre'       => ['en'=>'tower',               'fr'=>'tour',               'de'=>'turm',               'zh'=>'ta'],
    'muralla'     => ['en'=>'city-walls',          'fr'=>'remparts',           'de'=>'stadtmauer',         'zh'=>'chengqiang'],
    'puente'      => ['en'=>'bridge',              'fr'=>'pont',               'de'=>'bruecke',            'zh'=>'qiao'],
    'arco'        => ['en'=>'arch',                'fr'=>'arc',                'de'=>'bogen',              'zh'=>'gongjian'],
    'fuente'      => ['en'=>'fountain',            'fr'=>'fontaine',           'de'=>'brunnen',            'zh'=>'quantou'],
    'molino'      => ['en'=>'mill',                'fr'=>'moulin',             'de'=>'muehle',             'zh'=>'mofang'],
    'monumento'   => ['en'=>'monument',            'fr'=>'monument',           'de'=>'denkmal',            'zh'=>'jinianbei'],
    'ruinas'      => ['en'=>'ruins',               'fr'=>'ruines',             'de'=>'ruinen',             'zh'=>'feixu'],
    'conjunto'    => ['en'=>'historic-district',   'fr'=>'centre-historique',  'de'=>'altstadt',           'zh'=>'lishi-jiequ'],
    'casco'       => ['en'=>'historic-district',   'fr'=>'centre-historique',  'de'=>'altstadt',           'zh'=>'lishi-jiequ'],
    'villa'       => ['en'=>'historic-town',       'fr'=>'ville-historique',   'de'=>'historische-stadt',  'zh'=>'gucheng'],
    'museo'       => ['en'=>'museum',              'fr'=>'musee',              'de'=>'museum',             'zh'=>'bowuguan'],
    'teatro'      => ['en'=>'theatre',             'fr'=>'theatre',            'de'=>'theater',            'zh'=>'juchang'],
    'plaza'       => ['en'=>'square',              'fr'=>'place',              'de'=>'platz',              'zh'=>'guangchang'],
    'mercado'     => ['en'=>'market',              'fr'=>'marche',             'de'=>'markt',              'zh'=>'shichang'],
    'restaurante' => ['en'=>'restaurant',          'fr'=>'restaurant',         'de'=>'restaurant',         'zh'=>'canting'],
    'bar'         => ['en'=>'bar',                 'fr'=>'bar',                'de'=>'bar',                'zh'=>'jiuba'],
    'finca'       => ['en'=>'estate',              'fr'=>'domaine',            'de'=>'gut',                'zh'=>'zhuangyuan'],
    'hacienda'    => ['en'=>'estate',              'fr'=>'domaine',            'de'=>'gut',                'zh'=>'zhuangyuan'],
    'parque'      => ['en'=>'nature-reserve',      'fr'=>'reserve-naturelle',  'de'=>'naturpark',          'zh'=>'ziranbaohuqu'],
    'natural'     => ['en'=>'nature-reserve',      'fr'=>'reserve-naturelle',  'de'=>'naturpark',          'zh'=>'ziranbaohuqu'],
    'reserva'     => ['en'=>'nature-reserve',      'fr'=>'reserve-naturelle',  'de'=>'naturschutzgebiet',  'zh'=>'ziranbaohuqu'],
    'mirador'     => ['en'=>'viewpoint',           'fr'=>'belvedere',          'de'=>'aussichtspunkt',     'zh'=>'guanjingdian'],
    'cueva'       => ['en'=>'cave',                'fr'=>'grotte',             'de'=>'hoehle',             'zh'=>'dongxue'],
    'laguna'      => ['en'=>'lake',                'fr'=>'lac',                'de'=>'see',                'zh'=>'hu'],
    'lago'        => ['en'=>'lake',                'fr'=>'lac',                'de'=>'see',                'zh'=>'hu'],
    'playa'       => ['en'=>'beach',               'fr'=>'plage',              'de'=>'strand',             'zh'=>'haitan'],
    'bosque'      => ['en'=>'forest',              'fr'=>'foret',              'de'=>'wald',               'zh'=>'senlin'],
    'cascada'     => ['en'=>'waterfall',           'fr'=>'cascade',            'de'=>'wasserfall',         'zh'=>'pubao'],
    'yacimiento'  => ['en'=>'archaeological-site', 'fr'=>'site-archeologique', 'de'=>'ausgrabungsstaette', 'zh'=>'kaoguzhi'],
    'dolmen'      => ['en'=>'dolmen',              'fr'=>'dolmen',             'de'=>'dolmen',             'zh'=>'shishi-muzhang'],
    'calzada'     => ['en'=>'roman-road',          'fr'=>'voie-romaine',       'de'=>'roemerstrasse',      'zh'=>'luoma-gudao'],
    // fallback
    'lugar'       => ['en'=>'place',               'fr'=>'lieu',               'de'=>'ort',                'zh'=>'difang'],
];

// Preposiciones/artículos a eliminar del inicio del nombre limpio (ya en formato slug)
const PALABRAS_TRIVIALES = ['de-la','de-los','de-las','del','de','el','la','los','las','un','una','al'];

// ---------------------------------------------------------------
// FUNCIÓN: Transliterar y slugificar una cadena
// ---------------------------------------------------------------
function slugificar(string $texto): string
{
    // Transliteración UTF-8 → ASCII (ñ→n, á→a, ü→u, etc.)
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = strtolower($texto);
    // Eliminar apóstrofes y comillas
    $texto = str_replace(["'", '"', '`'], '', $texto);
    // Todo carácter no alfanumérico → guion
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    // Limpiar guiones duplicados y bordes
    $texto = trim(preg_replace('/-{2,}/', '-', $texto), '-');
    return $texto;
}

// ---------------------------------------------------------------
// FUNCIÓN: Traducir nombre de categoría → prefijo de slug
// ---------------------------------------------------------------
function traducirCategoria(string $categoriaNombre, string $idioma): string
{
    $normalizado = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $categoriaNombre));

    foreach (CATEGORIAS_SLUG as $clave => $traducciones) {
        if (strpos($normalizado, $clave) !== false) {
            return $traducciones[$idioma] ?? $traducciones['en'] ?? 'place';
        }
    }
    // Fallback: la categoría misma slugificada
    return slugificar($categoriaNombre) ?: 'place';
}

// ---------------------------------------------------------------
// FUNCIÓN: Limpiar el nombre del lugar
//   Elimina la palabra de categoría del inicio + preposiciones
//   Ej: "Bodega AlmaRoja" + cat "Bodega"        → "almaroja"
//   Ej: "Castillo de Almansa" + cat "Castillo"  → "almansa"
//   Ej: "Parque Natural del Cañón..." + cat "Parque Natural" → "canon-del-rio-lobos"
// ---------------------------------------------------------------
function limpiarNombre(string $nombre, string $categoriaNombre): string
{
    $nombreSlug = slugificar($nombre);
    $catNorm    = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $categoriaNombre));

    // 1. Intentar quitar el slug COMPLETO de la categoría del inicio (ej. "parque-natural")
    $catSlugCompleto = slugificar($categoriaNombre);
    if ($catSlugCompleto && strpos($nombreSlug, $catSlugCompleto . '-') === 0) {
        $nombreSlug = substr($nombreSlug, strlen($catSlugCompleto) + 1);
    } else {
        // 2. Quitar cada palabra clave de la categoría que aparezca al inicio
        foreach (array_keys(CATEGORIAS_SLUG) as $clave) {
            if (strpos($catNorm, $clave) !== false) {
                $claveSlug = slugificar($clave);
                if ($claveSlug && strpos($nombreSlug, $claveSlug . '-') === 0) {
                    $nombreSlug = substr($nombreSlug, strlen($claveSlug) + 1);
                    // Continuar el loop para quitar también palabras adicionales de la categoría
                    // Ej. si la categoría es "Parque Natural" y el nombre empieza por "natural-"
                }
            }
        }
    }

    // 3. Eliminar preposiciones/artículos triviales del inicio (una pasada)
    foreach (PALABRAS_TRIVIALES as $palabra) {
        if (strpos($nombreSlug, $palabra . '-') === 0) {
            $nombreSlug = substr($nombreSlug, strlen($palabra) + 1);
            break;
        }
    }

    return trim($nombreSlug, '-');
}

// ---------------------------------------------------------------
// FUNCIÓN AUXILIAR: Quitar del nombre limpio las partes que ya
//   están en el municipio (para evitar duplicados en el slug)
//   Ej: "berlanga" contenido en "berlanga-de-duero"         → omitir todo
//   Ej: "mayor-de-medinaceli" termina en "medinaceli"       → quitar "-medinaceli"
// ---------------------------------------------------------------
function nombreSinMunicipio(string $nombreLimpio, string $muniSlug): string
{
    if (empty($nombreLimpio)) {
        return '';
    }
    // Caso 1: el nombre limpio completo está dentro del municipio
    if (strpos($muniSlug, $nombreLimpio) !== false) {
        return '';
    }
    // Caso 2: el municipio (o su primera palabra) aparece al final del nombre limpio
    // Ej: "mayor-de-medinaceli" + muni "medinaceli" → quitar "-medinaceli" del final
    $muniPartes = explode('-', $muniSlug);
    // Usamos solo la primera "palabra significativa" del municipio (≥ 4 letras para evitar falsos positivos)
    foreach ($muniPartes as $parte) {
        if (strlen($parte) >= 4 && substr($nombreLimpio, -strlen($parte)) === $parte) {
            $nombreLimpio = rtrim(substr($nombreLimpio, 0, -strlen($parte)), '-');
            break;
        }
    }
    return trim($nombreLimpio, '-');
}

// ---------------------------------------------------------------
// FUNCIÓN PRINCIPAL: Generar el slug i18n del lugar
//   Formato: {cat_traducida}-{nombre_limpio}-{municipio}
//   Si nombre limpio == municipio o está vacío → {cat}-{municipio}
// ---------------------------------------------------------------
function generarSlugLugar(string $nombre, string $categoriaNombre, string $municipio, string $idioma): string
{
    $catPrefix    = traducirCategoria($categoriaNombre, $idioma);
    $muniSlug     = slugificar($municipio);
    $nombreLimpio = limpiarNombre($nombre, $categoriaNombre);

    // Quitar del nombre partes que ya están en el municipio (evita duplicados)
    $nombreLimpio = nombreSinMunicipio($nombreLimpio, $muniSlug);

    // Si el nombre limpio quedó vacío o es idéntico al municipio, no lo repetimos
    if (empty($nombreLimpio) || $nombreLimpio === $muniSlug) {
        $slug = $catPrefix . '-' . $muniSlug;
    } else {
        $slug = $catPrefix . '-' . $nombreLimpio . '-' . $muniSlug;
    }

    return trim(preg_replace('/-{2,}/', '-', $slug), '-');
}
