<?php
/**
 * search_eventos.php — Endpoint REST del Buscador de Eventos
 * rutasrurales.io · GET /api/search_eventos.php
 *
 * Parámetros: q, provincia, categoria, gratuito, fecha_desde, lang, page, limit
 * Respuesta:  { success, total, page, per_page, pages, query_time_ms, results[], facets{} }
 * Seguridad:  filter_input() + Prepared Statements + Rate limiting por sesión
 */
declare(strict_types=1);
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);
define('API_NO_HEADERS', true);

require_once __DIR__ . '/config.php';

// Rate limiting por sesión (120 req/min)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['srpm'] = $_SESSION['srpm'] ?? ['n' => 0, 't' => time()];
if (time() - $_SESSION['srpm']['t'] > 60) $_SESSION['srpm'] = ['n' => 0, 't' => time()];
if (++$_SESSION['srpm']['n'] > 120) {
    ob_end_clean();
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Demasiadas peticiones'], JSON_UNESCAPED_UNICODE);
    exit;
}

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: no-cache, must-revalidate');
header('X-Robots-Tag: noindex');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET')     { _jsonError('Método no permitido', 405); }

// Sanitización estricta de todos los parámetros de entrada
$q         = mb_substr(trim(filter_input(INPUT_GET,'q',         FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),0,100,'UTF-8');
$provincia = trim(filter_input(INPUT_GET,'provincia',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$categoria = filter_input(INPUT_GET,'categoria',  FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
$gratuito  = filter_input(INPUT_GET,'gratuito',   FILTER_VALIDATE_BOOLEAN) ?: false;
$f_desde   = trim(filter_input(INPUT_GET,'fecha_desde', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$lang      = trim(filter_input(INPUT_GET,'lang',        FILTER_SANITIZE_SPECIAL_CHARS) ?? 'es');
$page      = max(1, (int)(filter_input(INPUT_GET,'page',  FILTER_VALIDATE_INT) ?: 1));
$limit     = min(48, max(6, (int)(filter_input(INPUT_GET,'limit', FILTER_VALIDATE_INT) ?: 12)));

$lang      = in_array($lang, ['es','en','fr','zh'], true) ? $lang : 'es';
$fecha_min = (!empty($f_desde) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_desde)) ? $f_desde : date('Y-m-d');

// Conexión PDO
$t0 = microtime(true);
try { $pdo = getDBConnection(); }
catch (Throwable $e) { _jsonError('Error BD', 500); }

// Construcción SQL dinámica
$where  = ["e.is_active=1","e.moderation_status='approved'",
           "COALESCE(e.end_date,e.start_date)>=:fecha_min"];
$params = [':fecha_min' => $fecha_min];
$sel_rel = '0 AS relevance,';

// Búsqueda: Usar LIKE (más compatible que FULLTEXT)
$usar_like = false;
if (mb_strlen($q, 'UTF-8') >= 2) {
    $words = array_filter(preg_split('/\s+/u', $q), fn($w) => mb_strlen($w,'UTF-8') >= 2);
    
    if (!empty($words)) {
        $like_conds = [];
        $i = 0;
        foreach ($words as $w) {
            $param_key = ':q_like_' . $i;
            $like_conds[] = "(e.name LIKE {$param_key} OR e.short_description LIKE {$param_key} OR e.municipality LIKE {$param_key} OR e.province LIKE {$param_key})";
            $params[$param_key] = '%' . $w . '%';
            $i++;
        }
        if (!empty($like_conds)) {
            $where[] = '(' . implode(' OR ', $like_conds) . ')';
            $usar_like = true;
        }
    }
}


if (!empty($provincia)) { $where[]='e.province=:prov';   $params[':prov']=$provincia; }
if ($categoria > 0)     { $where[]='e.category_id=:cat'; $params[':cat']=$categoria; }
if ($gratuito)          { $where[]='e.is_free=1'; }

$wsql  = implode(' AND ', $where);
$osql  = $usar_like ? 'ORDER BY e.is_featured DESC,e.start_date ASC'
                  : 'ORDER BY e.is_featured DESC,e.start_date ASC';
$offset = ($page-1)*$limit;

// COUNT total para paginación
$sc = $pdo->prepare("SELECT COUNT(*) FROM cultural_events e WHERE {$wsql}");
foreach ($params as $k=>$v) $sc->bindValue($k,$v);
$sc->execute();
$total = (int)$sc->fetchColumn();
$pages = max(1,(int)ceil($total/$limit));

// SELECT principal
$sql = "SELECT e.id, e.name AS titulo, e.slug, e.short_description,
               e.municipality, e.province, e.start_date, e.end_date,
               e.is_free, e.ticket_price, e.category_id, e.is_featured,
               e.poster_image, e.photo1, e.venue_name, e.organizer,
               e.latitude, e.longitude, {$sel_rel} e.latitude AS lat2
        FROM cultural_events e WHERE {$wsql} {$osql}
        LIMIT :lim OFFSET :off";
$sm = $pdo->prepare($sql);
foreach ($params as $k=>$v) $sm->bindValue($k,$v);
$sm->bindValue(':lim',$limit, PDO::PARAM_INT);
$sm->bindValue(':off',$offset,PDO::PARAM_INT);
$sm->execute();
$rows = $sm->fetchAll(PDO::FETCH_ASSOC);

// Enriquecer con traducción cuando lang ≠ es
if ($lang !== 'es' && !empty($rows)) {
    $ids = implode(',', array_map('intval', array_column($rows,'id')));
    $st  = $pdo->prepare("SELECT event_id,name,short_description,slug
                           FROM cultural_events_trads
                           WHERE event_id IN ({$ids}) AND language_code=:lang");
    $st->bindValue(':lang',$lang);
    $st->execute();
    $trads = array_column($st->fetchAll(PDO::FETCH_ASSOC), null, 'event_id');
    foreach ($rows as &$ev) {
        $tr = $trads[$ev['id']] ?? null;
        if (!$tr) continue;
        if (!empty($tr['name']))              $ev['titulo']            = $tr['name'];
        if (!empty($tr['short_description'])) $ev['short_description'] = $tr['short_description'];
        if (!empty($tr['slug']))              $ev['slug']              = $tr['slug'];
    }
    unset($ev);
}

// Normalizar imagen y URL canónica de detalle
$base     = 'https://rutasrurales.io';
$fallback = $base.'/menu_images/turismo_rural.webp';
$prefix   = ($lang!=='es') ? "/{$lang}" : '';
foreach ($rows as &$ev) {
    $img = $ev['poster_image'] ?: $ev['photo1'] ?: '';
    $ev['imagen'] = $img
        ? (preg_match('/^https?:\/\//',$img) ? $img : $base.'/'.ltrim($img,'/'))
        : $fallback;
    $ev['url']       = $base.$prefix.'/evento/'.urlencode($ev['slug']);
    $ev['relevance'] = round((float)($ev['relevance']??0),4);
    unset($ev['poster_image'],$ev['photo1'],$ev['lat2']);
}
unset($ev);

// Facetas para los filtros dinámicos del frontend
$facets = ['provincias'=>[],'categorias'=>[]];
try {
    $fw = "is_active=1 AND moderation_status='approved' AND COALESCE(end_date,start_date)>=CURDATE()";
    $facets['provincias'] = $pdo->query(
        "SELECT province AS value,COUNT(*) AS total FROM cultural_events
         WHERE {$fw} GROUP BY province ORDER BY total DESC,province ASC LIMIT 30"
    )->fetchAll(PDO::FETCH_ASSOC);
    $facets['categorias'] = $pdo->query(
        "SELECT category_id AS value,COUNT(*) AS total FROM cultural_events
         WHERE {$fw} GROUP BY category_id ORDER BY total DESC LIMIT 25"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* facetas opcionales */ }

// Respuesta JSON
echo json_encode([
    'success'       => true,
    'query'         => $q,
    'lang'          => $lang,
    'total'         => $total,
    'page'          => $page,
    'per_page'      => $limit,
    'pages'         => $pages,
    'query_time_ms' => round((microtime(true)-$t0)*1000,1),
    'results'       => $rows,
    'facets'        => $facets,
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

// Helper de error JSON
function _jsonError(string $msg, int $code=400): never {
    http_response_code($code);
    echo json_encode(['success'=>false,'error'=>$msg],JSON_UNESCAPED_UNICODE);
    exit;
}
