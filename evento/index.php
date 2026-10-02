<?php
/**
 * /evento/ — Buscador de Eventos Culturales Rurales — rutasrurales.io
 *
 * URLs:  /evento/  · /en/evento/  · /fr/evento/  · /zh/evento/
 * Flujo: idioma → i18n → sanitizar → SQL SSR → SEO → HTML + JS debounce
 */
declare(strict_types=1);
ini_set('display_errors','0');
error_reporting(E_ERROR|E_PARSE);
define('API_NO_HEADERS',true);

$_BASE = dirname(__DIR__);
require_once $_BASE.'/api/config.php';
require_once __DIR__.'/i18n/search_translations.php';

// ── 1. Idioma ─────────────────────────────────────────────────────────────
$lang = trim(filter_input(INPUT_GET,'lang',FILTER_SANITIZE_SPECIAL_CHARS)??'es');
$lang = in_array($lang,['es','en','fr','zh'],true) ? $lang : 'es';
$t    = getBuscadorTranslations($lang);

// ── 2. Filtros (filter_input estricto, sin $_GET directo) ─────────────────
$q         = mb_substr(trim(filter_input(INPUT_GET,'q',        FILTER_SANITIZE_SPECIAL_CHARS)??''),0,100,'UTF-8');
$provincia = trim(filter_input(INPUT_GET,'provincia', FILTER_SANITIZE_SPECIAL_CHARS)??'');
$categoria = filter_input(INPUT_GET,'categoria', FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
$gratuito  = filter_input(INPUT_GET,'gratuito',  FILTER_VALIDATE_BOOLEAN) ?: false;
$f_desde   = trim(filter_input(INPUT_GET,'fecha_desde',FILTER_SANITIZE_SPECIAL_CHARS)??'');
$page      = max(1,(int)(filter_input(INPUT_GET,'p',FILTER_VALIDATE_INT)?:1));
if (!empty($f_desde) && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$f_desde)) $f_desde='';
$fecha_min = !empty($f_desde) ? $f_desde : date('Y-m-d');

// ── 3. Consulta SQL SSR (para Googlebot + first paint sin JS) ─────────────
$ssr_eventos=[];$ssr_total=0;$facets_prov=[];$facets_cat=[];$pdo=null;
try {
    $pdo = getDBConnection();
    $fw  = "is_active=1 AND moderation_status='approved' AND COALESCE(end_date,start_date)>=CURDATE()";
    $facets_prov=$pdo->query("SELECT province AS value,COUNT(*) AS total FROM cultural_events WHERE {$fw} GROUP BY province ORDER BY total DESC,province ASC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
    $facets_cat =$pdo->query("SELECT category_id AS value,COUNT(*) AS total FROM cultural_events WHERE {$fw} GROUP BY category_id ORDER BY total DESC LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);

    $where=["e.is_active=1","e.moderation_status='approved'","COALESCE(e.end_date,e.start_date)>=:fm"];
    $params=[':fm'=>$fecha_min];
    $usar_ft=mb_strlen($q,'UTF-8')>=2;
    $sel_rel='0 AS relevance,';
    if($usar_ft){
        $words=array_filter(preg_split('/\s+/u',$q),fn($w)=>mb_strlen($w,'UTF-8')>=2);
        $qb=implode(' ',array_map(fn($w)=>'+'.preg_replace('/[^\p{L}\p{N}]/u','',$w).'*',$words));
        if(!empty(trim($qb))){
            $ft="MATCH(e.name,e.short_description,e.municipality,e.province,e.description) AGAINST(:q_ft IN BOOLEAN MODE)";
            $sel_rel="{$ft} AS relevance,";$where[]=$ft;$params[':q_ft']=$qb;
        } else $usar_ft=false;
    }
    if(!empty($provincia)){$where[]='e.province=:prov';   $params[':prov']=$provincia;}
    if($categoria>0)      {$where[]='e.category_id=:cat'; $params[':cat']=$categoria;}
    if($gratuito)          $where[]='e.is_free=1';
    $wsql=implode(' AND ',$where);
    $osql=$usar_ft?'ORDER BY relevance DESC,e.is_featured DESC,e.start_date ASC':'ORDER BY e.is_featured DESC,e.start_date ASC';
    $lim=12;$off=($page-1)*$lim;
    $sc=$pdo->prepare("SELECT COUNT(*) FROM cultural_events e WHERE {$wsql}");
    foreach($params as $k=>$v)$sc->bindValue($k,$v);
    $sc->execute();$ssr_total=(int)$sc->fetchColumn();
    $sm=$pdo->prepare("SELECT e.id,e.name AS titulo,e.slug,e.short_description,e.municipality,e.province,e.start_date,e.end_date,e.is_free,e.ticket_price,e.category_id,e.is_featured,e.poster_image,e.photo1,e.venue_name,{$sel_rel}e.organizer FROM cultural_events e WHERE {$wsql} {$osql} LIMIT :lim OFFSET :off");
    foreach($params as $k=>$v)$sm->bindValue($k,$v);
    $sm->bindValue(':lim',$lim,PDO::PARAM_INT);$sm->bindValue(':off',$off,PDO::PARAM_INT);
    $sm->execute();$rows=$sm->fetchAll(PDO::FETCH_ASSOC);
    if($lang!=='es'&&!empty($rows)){
        $ids=implode(',',array_map('intval',array_column($rows,'id')));
        $st=$pdo->prepare("SELECT event_id,name,short_description,slug FROM cultural_events_trads WHERE event_id IN ({$ids}) AND language_code=:lang");
        $st->bindValue(':lang',$lang);$st->execute();
        $trads=array_column($st->fetchAll(PDO::FETCH_ASSOC),null,'event_id');
        foreach($rows as &$ev){$tr=$trads[$ev['id']]??null;if(!$tr)continue;if(!empty($tr['name']))$ev['titulo']=$tr['name'];if(!empty($tr['short_description']))$ev['short_description']=$tr['short_description'];if(!empty($tr['slug']))$ev['slug']=$tr['slug'];}unset($ev);
    }
    $bu='https://rutasrurales.io';$fb=$bu.'/menu_images/turismo_rural.webp';$lp=($lang!=='es')?"/{$lang}":'';
    foreach($rows as &$ev){$img=$ev['poster_image']?:$ev['photo1']?:'';$ev['imagen']=$img?(preg_match('/^https?:\/\//',$img)?$img:$bu.'/'.ltrim($img,'/'))  :$fb;$ev['url']=$bu.$lp.'/evento/'.urlencode($ev['slug']);unset($ev['poster_image'],$ev['photo1'],$ev['relevance']);}unset($ev);
    $ssr_eventos=$rows;
} catch(Throwable $e){error_log('evento/index.php: '.$e->getMessage());}
$ssr_pages=max(1,(int)ceil($ssr_total/12));

// ── 4. SEO: URLs, hreflang, metas ─────────────────────────────────────────
$base_domain='https://rutasrurales.io';
$lp2=($lang!=='es')?"/{$lang}":'';
$canonical=$base_domain.$lp2.'/evento/';
$cp=[];
if(!empty($q))        $cp['q']=$q;
if(!empty($provincia))$cp['provincia']=$provincia;
if($categoria>0)      $cp['categoria']=$categoria;
if($gratuito)         $cp['gratuito']='1';
if(!empty($f_desde))  $cp['fecha_desde']=$f_desde;
if($page>1)           $cp['p']=$page;
if(!empty($cp))$canonical.='?'.http_build_query($cp);
$qs_extra=!empty($cp)?'?'.http_build_query($cp):'';
$hreflang=[
    'es'      =>$base_domain.'/evento/'   .$qs_extra,
    'en'      =>$base_domain.'/en/evento/'.$qs_extra,
    'fr'      =>$base_domain.'/fr/evento/'.$qs_extra,
    'zh-Hans' =>$base_domain.'/zh/evento/'.$qs_extra,
    'x-default'=>$base_domain.'/evento/',
];
$meta_title=$t['meta_title'];
$meta_desc =$t['meta_desc'];
if(!empty($q)){
    $meta_title=htmlspecialchars($q,ENT_QUOTES,'UTF-8').' — '.$t['h1'].' | rutasrurales.io';
    $meta_desc=mb_substr(str_replace('{N}',$ssr_total,$t['results_count']).' '.$t['results_for'].' "'.$q.'". '.$meta_desc,0,155,'UTF-8');
}
$robots=($ssr_total===0&&(mb_strlen($q,'UTF-8')>=2||!empty($provincia)))?'noindex, follow':'index, follow, max-snippet:-1, max-image-preview:large';
$og_image=$base_domain.'/menu_images/eventos_fiestas_populares.webp';

// ── 5. Schema.org JSON-LD ─────────────────────────────────────────────────
$schema_items=[];
foreach(array_slice($ssr_eventos,0,8) as $i=>$ev){
    $fd=formatEventDate($ev['start_date']??'',$t);
    $it=['@type'=>'Event','position'=>$i+1,'name'=>$ev['titulo'],'url'=>$ev['url'],
         'startDate'=>$ev['start_date'],
         'location'=>['@type'=>'Place','name'=>$ev['venue_name']??$ev['municipality'],
           'address'=>['@type'=>'PostalAddress','addressLocality'=>$ev['municipality'],
                       'addressRegion'=>$ev['province'],'addressCountry'=>'ES']],
         'description'=>mb_substr(strip_tags($ev['short_description']??''),0,160,'UTF-8'),
         'image'=>$ev['imagen'],'isAccessibleForFree'=>(bool)$ev['is_free'],
         'organizer'=>['@type'=>'Organization','name'=>$ev['organizer']??'rutasrurales.io'],
         'eventStatus'=>'https://schema.org/EventScheduled',
         'eventAttendanceMode'=>'https://schema.org/OfflineEventAttendanceMode'];
    if(!empty($ev['end_date']))$it['endDate']=$ev['end_date'];
    if(!$ev['is_free']&&!empty($ev['ticket_price']))$it['offers']=['@type'=>'Offer','price'=>$ev['ticket_price'],'priceCurrency'=>'EUR'];
    $schema_items[]=$it;
}
$schema=['@context'=>'https://schema.org','@graph'=>[
    ['@type'=>'CollectionPage','@id'=>$canonical.'#webpage','url'=>$canonical,
     'name'=>$meta_title,'description'=>$meta_desc,'inLanguage'=>$t['lang_locale'],
     'isPartOf'=>['@type'=>'WebSite','url'=>$base_domain],
     'breadcrumb'=>['@id'=>$canonical.'#breadcrumb']],
    ['@type'=>'BreadcrumbList','@id'=>$canonical.'#breadcrumb','itemListElement'=>[
        ['@type'=>'ListItem','position'=>1,'name'=>$t['bc_home'],  'item'=>$base_domain.$lp2.'/'],
        ['@type'=>'ListItem','position'=>2,'name'=>$t['bc_events'],'item'=>$base_domain.$lp2.'/eventos/'],
        ['@type'=>'ListItem','position'=>3,'name'=>$t['bc_search'],'item'=>$canonical],
    ]],
    ['@type'=>'ItemList','name'=>$t['h1'],'numberOfItems'=>$ssr_total,'itemListElement'=>$schema_items],
]];
$json_ld=json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

// ── Helper SSR card ───────────────────────────────────────────────────────
function renderSsrCard(array $ev,array $t,int $idx):string{
    $fd=formatEventDate($ev['start_date']??'',$t);
    $today=date('Y-m-d');
    $ongoing=!empty($ev['end_date'])&&$ev['end_date']>=$today&&!empty($ev['start_date'])&&$ev['start_date']<=$today;
    $cat=$t['categories'][$ev['category_id']]??'';
    $ld=$idx===0?'eager':'lazy';
    $dbc=$ongoing?' srch-card__date-badge--ongoing':'';
    $dm=$ongoing?htmlspecialchars($t['card_ongoing'],ENT_QUOTES,'UTF-8'):$fd['mes'];
    $pb='';
    if($ev['is_free'])$pb='<span class="srch-card__badge-free">'.htmlspecialchars($t['card_free']).'</span>';
    elseif(!empty($ev['ticket_price']))$pb='<span class="srch-card__badge-price">'.htmlspecialchars($t['card_from']).' '.htmlspecialchars((string)$ev['ticket_price']).'€</span>';
    $loc=htmlspecialchars(($ev['venue_name']?:$ev['municipality']).' ('.$ev['province'].')',ENT_QUOTES,'UTF-8');
    $desc=htmlspecialchars(mb_substr(strip_tags($ev['short_description']??''),0,120,'UTF-8'),ENT_QUOTES,'UTF-8');
    $ttl=htmlspecialchars($ev['titulo'],ENT_QUOTES,'UTF-8');
    $url=htmlspecialchars($ev['url'],ENT_QUOTES,'UTF-8');
    $img=htmlspecialchars($ev['imagen'],ENT_QUOTES,'UTF-8');
    $star=$ev['is_featured']?'<span class="srch-card__featured-icon" aria-hidden="true">⭐</span>':'';
    $see=htmlspecialchars($t['card_see'],ENT_QUOTES,'UTF-8');
    return <<<HTML
<article class="srch-card" itemscope itemtype="https://schema.org/Event">
  <div class="srch-card__img-wrap">
    <img class="srch-card__img" src="{$img}" alt="{$ttl}" loading="{$ld}" decoding="async" itemprop="image">
    <div class="srch-card__date-badge{$dbc}" aria-hidden="true"><span class="srch-card__date-dia">{$fd['dia']}</span><span class="srch-card__date-mes">{$dm}</span></div>
    {$pb}<meta itemprop="startDate" content="{$ev['start_date']}">
  </div>
  <div class="srch-card__body">
    <p class="srch-card__cat">{$cat}</p>
    <h3 class="srch-card__title" itemprop="name">{$ttl}</h3>
    <p class="srch-card__desc">{$desc}</p>
    <p class="srch-card__meta"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg><span itemprop="location">{$loc}</span></p>
  </div>
  <div class="srch-card__footer"><a class="srch-card__link" href="{$url}" itemprop="url">{$see} →</a>{$star}</div>
</article>
HTML;
}

?>
<!DOCTYPE html>
<html lang="<?= $t['lang_locale'] ?>" dir="<?= $t['dir'] ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($meta_title,ENT_QUOTES,'UTF-8') ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_desc,ENT_QUOTES,'UTF-8') ?>">
<meta name="robots" content="<?= $robots ?>">
<link rel="canonical" href="<?= htmlspecialchars($canonical,ENT_QUOTES,'UTF-8') ?>">
<?php foreach($hreflang as $hl=>$hurl):?>
<link rel="alternate" hreflang="<?= $hl ?>" href="<?= htmlspecialchars($hurl,ENT_QUOTES,'UTF-8') ?>">
<?php endforeach;?>
<!-- Open Graph -->
<meta property="og:type"        content="website">
<meta property="og:site_name"   content="rutasrurales.io">
<meta property="og:locale"      content="<?= $t['lang_locale'] ?>">
<meta property="og:url"         content="<?= htmlspecialchars($canonical,ENT_QUOTES,'UTF-8') ?>">
<meta property="og:title"       content="<?= htmlspecialchars($t['og_title'],ENT_QUOTES,'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars($t['og_desc'],ENT_QUOTES,'UTF-8') ?>">
<meta property="og:image"       content="<?= $og_image ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height"content="630">
<meta property="og:image:alt"   content="<?= htmlspecialchars($t['h1'],ENT_QUOTES,'UTF-8') ?>">
<!-- Twitter Card -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:site"        content="@rutasrurales">
<meta name="twitter:title"       content="<?= htmlspecialchars($t['og_title'],ENT_QUOTES,'UTF-8') ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($t['og_desc'],ENT_QUOTES,'UTF-8') ?>">
<meta name="twitter:image"       content="<?= $og_image ?>">
<!-- Geo SEO -->
<meta name="geo.region"    content="ES">
<meta name="geo.placename" content="España">
<meta name="ICBM"          content="40.416775, -3.703790">
<!-- Schema.org JSON-LD -->
<script type="application/ld+json"><?= $json_ld ?></script>
<!-- CSS global -->
<link rel="stylesheet" href="/styles.css">
<link rel="stylesheet" href="/css/buscador-eventos.css">
<link rel="sitemap" type="application/xml" title="Sitemap Eventos" href="/sitemap-eventos.php">
<link rel="icon"             href="/menu_images/Favicon.png" type="image/png">
<link rel="apple-touch-icon" href="/menu_images/Favicon.png">
<meta name="theme-color" content="#2F5233">

<!-- Variables CSS y estilos del navbar — idénticos a /eventos/ -->
<style>
:root{
    --primary:#2F5233;--primary-dark:#1a3d1e;--accent:#81C784;
    --accent-warm:#F9A825;--white:#fff;--bg:#f8f9fa;--bg-alt:#f0f4f1;
    --text:#2d3436;--text-light:#636e72;--border:#e8eaed;
    --radius:14px;--radius-sm:8px;--shadow:0 2px 12px rgba(0,0,0,.07);
    --max-w:1200px;--tr:.18s ease;
}
html{scroll-behavior:smooth}
body{font-family:'Montserrat','Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.65;overflow-x:hidden;-webkit-font-smoothing:antialiased}
img{display:block;max-width:100%;height:auto}
a{color:var(--primary);text-decoration:none}
ul{list-style:none;padding:0;margin:0}

/* ── Navbar idéntico al de /eventos/ ── */
.evt-nav{position:sticky;top:0;z-index:900;background:var(--white);border-bottom:1px solid var(--border);height:60px;display:flex;align-items:center;padding:0 20px;gap:14px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
.evt-nav__logo{display:flex;align-items:center;gap:10px;font-weight:800;color:var(--primary);font-size:1rem;flex-shrink:0}
.evt-nav__logo img{width:38px;height:38px;border-radius:50%;object-fit:cover}
.evt-nav__links{display:flex;align-items:center;gap:4px;margin-left:auto;font-size:.8rem}
.evt-nav__links a{color:var(--text);font-weight:600;padding:6px 10px;border-radius:var(--radius-sm);white-space:nowrap;transition:background var(--tr)}
.evt-nav__links a:hover,.evt-nav__links a[aria-current="page"]{background:var(--bg-alt);color:var(--primary)}
.evt-nav__cta{background:var(--primary)!important;color:var(--white)!important;padding:7px 14px!important;border-radius:var(--radius-sm)!important;font-weight:700!important}
@media(max-width:640px){.evt-nav__links{display:none}}
</style>
</head>
<body>
<a href="#srch-results" class="srch-skip-link"><?= htmlspecialchars($t['h1']) ?></a>

<!-- NAVBAR — idéntico al de /eventos/ -->
<?php
$_home   = ($lang !== 'es') ? "/{$lang}/" : '/';
$_prefix = ($lang !== 'es') ? "/{$lang}" : '';
// Etiquetas de navegación por idioma (mismas claves que usa /eventos/)
$_nav_labels = [
    'es' => ['home'=>'Inicio','stays'=>'Alojamientos','events'=>'Eventos','places'=>'Lugares','activities'=>'Actividades','map'=>'Mapa','login'=>'Acceder'],
    'en' => ['home'=>'Home','stays'=>'Accommodation','events'=>'Events','places'=>'Places','activities'=>'Activities','map'=>'Map','login'=>'Log in'],
    'fr' => ['home'=>'Accueil','stays'=>'Hébergements','events'=>'Événements','places'=>'Lieux','activities'=>'Activités','map'=>'Carte','login'=>'Se connecter'],
    'zh' => ['home'=>'首页','stays'=>'住宿','events'=>'活动','places'=>'景点','activities'=>'活动','map'=>'地图','login'=>'登录'],
];
$_nl = $_nav_labels[$lang] ?? $_nav_labels['es'];
?>
<header class="evt-nav" role="banner">
  <a href="<?= $_home ?>" class="evt-nav__logo" aria-label="Rutas Rurales — <?= htmlspecialchars($_nl['home']) ?>">
    <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales" width="38" height="38" loading="eager">
    <span>Rutas Rurales</span>
  </a>
  <nav class="evt-nav__links" aria-label="Navegación principal">
    <a href="<?= $_prefix ?>/alojamientos/">🏡 <?= htmlspecialchars($_nl['stays']) ?></a>
    <a href="<?= $_prefix ?>/eventos/">🎭 <?= htmlspecialchars($_nl['events']) ?></a>
    <a href="<?= $_prefix ?>/lugares/">📍 <?= htmlspecialchars($_nl['places']) ?></a>
    <a href="<?= $_prefix ?>/actividades/">🥾 <?= htmlspecialchars($_nl['activities']) ?></a>
    <a href="/rutas.php">🗺️ <?= htmlspecialchars($_nl['map']) ?></a>
    <a href="/login.html" class="evt-nav__cta" rel="nofollow"><?= htmlspecialchars($_nl['login']) ?></a>
  </nav>
</header>
<!-- BREADCRUMB -->
<nav class="srch-breadcrumb" aria-label="Breadcrumb">
  <a href="/<?= $lang!=='es'?$lang.'/':'' ?>"><?= htmlspecialchars($t['bc_home']) ?></a>
  <span class="srch-breadcrumb__sep" aria-hidden="true">›</span>
  <a href="/<?= $lang!=='es'?$lang.'/':'' ?>eventos/"><?= htmlspecialchars($t['bc_events']) ?></a>
  <span class="srch-breadcrumb__sep" aria-hidden="true">›</span>
  <span aria-current="page"><?= htmlspecialchars($t['bc_search']) ?></span>
</nav>
<!-- HERO + BUSCADOR -->
<header class="srch-hero">
  <h1 class="srch-hero__title"><?= htmlspecialchars($t['h1']) ?></h1>
  <p class="srch-hero__subtitle"><?= htmlspecialchars($t['og_desc']) ?></p>
  <form class="srch-form" role="search" aria-label="<?= htmlspecialchars($t['aria_search']) ?>"
        id="srch-form" method="get" action="">
    <?php if($lang!=='es'):?><input type="hidden" name="lang" value="<?= $lang ?>"><?php endif;?>
    <input class="srch-form__input" type="search" id="srch-q" name="q"
           value="<?= htmlspecialchars($q,ENT_QUOTES,'UTF-8') ?>"
           placeholder="<?= htmlspecialchars($t['search_placeholder'],ENT_QUOTES,'UTF-8') ?>"
           autocomplete="off" autocorrect="off" spellcheck="false" maxlength="100"
           aria-label="<?= htmlspecialchars($t['search_placeholder'],ENT_QUOTES,'UTF-8') ?>">
    <button type="button" class="srch-form__clear" id="srch-clear"
            aria-label="<?= htmlspecialchars($t['search_clear']) ?>">✕</button>
    <button type="submit" class="srch-form__btn">🔍 <?= htmlspecialchars($t['search_btn']) ?></button>
  </form>
</header>
<!-- BARRA DE FILTROS -->
<div class="srch-filter-bar">
  <button class="srch-filter-toggle" id="filter-toggle" aria-expanded="false"
          aria-controls="filter-panel" aria-label="<?= htmlspecialchars($t['aria_filters']) ?>">
    ⚙️ <?= htmlspecialchars($t['filter_toggle']) ?>
    <span class="srch-filter-toggle__icon" aria-hidden="true">▾</span>
  </button>
  <div class="srch-active-filters" id="active-chips" aria-label="Filtros activos">
    <?php if(!empty($provincia)):?>
    <span class="srch-chip">📍 <?= htmlspecialchars($provincia) ?>
      <button class="srch-chip__remove" data-filter="provincia" aria-label="Quitar filtro provincia">✕</button></span>
    <?php endif;?>
    <?php if($categoria>0):?>
    <span class="srch-chip">🎭 <?= htmlspecialchars($t['categories'][$categoria]??$categoria) ?>
      <button class="srch-chip__remove" data-filter="categoria" aria-label="Quitar filtro categoría">✕</button></span>
    <?php endif;?>
    <?php if($gratuito):?>
    <span class="srch-chip">🎁 <?= htmlspecialchars($t['filter_free']) ?>
      <button class="srch-chip__remove" data-filter="gratuito" aria-label="Quitar filtro gratuito">✕</button></span>
    <?php endif;?>
  </div>
</div>
<!-- PANEL DE FILTROS (colapsable) -->
<div class="srch-filters" id="filter-panel" aria-label="<?= htmlspecialchars($t['aria_filters']) ?>">
  <div class="srch-filters__inner">
    <div class="srch-filter-group">
      <label for="f-provincia"><?= htmlspecialchars($t['filter_province']) ?></label>
      <select id="f-provincia" name="provincia">
        <option value=""><?= htmlspecialchars($t['all_provinces']) ?></option>
        <?php foreach($facets_prov as $fp):?>
        <option value="<?= htmlspecialchars($fp['value'],ENT_QUOTES,'UTF-8') ?>" <?= $provincia===$fp['value']?'selected':'' ?>>
          <?= htmlspecialchars($fp['value']) ?> (<?= $fp['total'] ?>)
        </option>
        <?php endforeach;?>
      </select>
    </div>
    <div class="srch-filter-group">
      <label for="f-categoria"><?= htmlspecialchars($t['filter_category']) ?></label>
      <select id="f-categoria" name="categoria">
        <option value=""><?= htmlspecialchars($t['all_categories']) ?></option>
        <?php foreach($facets_cat as $fc):$cn=$t['categories'][$fc['value']]??'Cat.'.$fc['value'];?>
        <option value="<?= (int)$fc['value'] ?>" <?= $categoria===(int)$fc['value']?'selected':'' ?>>
          <?= htmlspecialchars($cn) ?> (<?= $fc['total'] ?>)
        </option>
        <?php endforeach;?>
      </select>
    </div>
    <div class="srch-filter-group">
      <label for="f-fecha"><?= htmlspecialchars($t['filter_date_from']) ?></label>
      <input type="date" id="f-fecha" name="fecha_desde"
             value="<?= htmlspecialchars($f_desde,ENT_QUOTES,'UTF-8') ?>"
             min="<?= date('Y-m-d') ?>">
    </div>
    <label class="srch-filter-free">
      <input type="checkbox" id="f-gratuito" name="gratuito" value="1" <?= $gratuito?'checked':'' ?>>
      <?= htmlspecialchars($t['filter_free']) ?>
    </label>
    <div class="srch-filter-actions">
      <button class="btn-apply" id="btn-apply-filters"><?= htmlspecialchars($t['filter_apply']) ?></button>
      <button class="btn-reset" id="btn-reset-filters"><?= htmlspecialchars($t['filter_reset']) ?></button>
    </div>
  </div>
</div>
<!-- HEADER RESULTADOS -->
<div class="srch-results-header">
  <p class="srch-results-count" id="srch-count" aria-live="polite" aria-atomic="true">
    <?php if($ssr_total===1):?><strong>1</strong> <?= htmlspecialchars($t['results_1']) ?>
    <?php elseif($ssr_total>1):?><strong><?= number_format($ssr_total,0,',','.') ?></strong> <?= htmlspecialchars(str_replace('{N}','',$t['results_count'])) ?>
    <?php elseif(mb_strlen($q,'UTF-8')>=2||!empty($provincia)):?><?= htmlspecialchars($t['results_0']) ?>
    <?php else:?><?= htmlspecialchars($t['h2_featured']) ?>
    <?php endif;?>
  </p>
  <span class="srch-results-time" id="srch-time"></span>
</div>
<!-- RESULTADOS (SSR + reemplazado por JS) -->
<main id="srch-results" aria-label="<?= htmlspecialchars($t['aria_results']) ?>"
      aria-live="polite" aria-busy="false">
  <div class="srch-grid" id="srch-grid">
    <?php if(empty($ssr_eventos)&&(mb_strlen($q,'UTF-8')>=2||!empty($provincia))):?>
    <div class="srch-empty" style="grid-column:1/-1">
      <div class="srch-empty__icon">😕</div>
      <h2 class="srch-empty__h2"><?= htmlspecialchars($t['no_results_h2']) ?></h2>
      <p class="srch-empty__p"><?= htmlspecialchars($t['no_results_p']) ?></p>
      <a class="srch-empty__cta" href="/<?= $lang!=='es'?$lang.'/':'' ?>eventos/"><?= htmlspecialchars($t['no_results_cta']) ?></a>
    </div>
    <?php else:?>
    <?php foreach($ssr_eventos as $idx=>$ev):?>
    <?= renderSsrCard($ev,$t,$idx) ?>
    <?php endforeach;?>
    <?php endif;?>
  </div>
  <?php if($ssr_pages>1):?>
  <nav class="srch-pagination" id="srch-pagination" aria-label="Paginación">
    <?php
    $bpq=array_merge($cp,['lang'=>$lang]);
    for($p=1;$p<=$ssr_pages;$p++){
        $pu='?'.http_build_query(array_merge($bpq,['p'=>$p]));
        $cr=$p===$page?' aria-current="page"':'';
        echo "<a class=\"srch-pagination__btn\" href=\"".htmlspecialchars($pu)."\"{$cr}>{$p}</a>\n";
    }?>
  </nav>
  <?php endif;?>
</main>
<!-- BLOQUE SEO -->
<section class="srch-seo-block" aria-label="Información sobre eventos rurales">
  <div class="srch-seo-block__inner">
    <h2><?= htmlspecialchars($t['h2_featured']) ?></h2>
    <p><?= htmlspecialchars($t['og_desc']) ?></p>
    <p>
      <a href="/<?= $lang!=='es'?$lang.'/':'' ?>eventos/"><?= htmlspecialchars($t['footer_events']) ?></a> ·
      <a href="/<?= $lang!=='es'?$lang.'/':'' ?>alojamientos/"><?= htmlspecialchars($t['footer_stays']) ?></a> ·
      <a href="/<?= $lang!=='es'?$lang.'/':'' ?>lugares/"><?= htmlspecialchars($t['footer_places']) ?></a>
    </p>
  </div>
</section>
<!-- FOOTER -->
<footer style="background:#1a2e1a;color:#aaa;text-align:center;padding:1.5rem 1rem;font-size:.825rem;">
  <p style="margin:0 0 .5rem">
    <a href="/<?= $lang!=='es'?$lang.'/':'' ?>aviso-legal.html" style="color:#9ca3af"><?= htmlspecialchars($t['footer_legal']) ?></a> ·
    <a href="/<?= $lang!=='es'?$lang.'/':'' ?>politica-cookies.html" style="color:#9ca3af"><?= htmlspecialchars($t['footer_cookies']) ?></a>
  </p>
  <p style="margin:0">© <?= date('Y') ?> rutasrurales.io</p>
</footer>

<!-- JS: Buscador asíncrono Debounce 300ms + AbortController + history.pushState -->
<script>
(function(){
'use strict';
const LANG='<?= $lang ?>';
const API='/api/search_eventos.php';
const LIMIT=12;
const T=<?= json_encode([
    'loading'       =>$t['loading'],
    'results_count' =>$t['results_count'],
    'results_1'     =>$t['results_1'],
    'results_0'     =>$t['results_0'],
    'results_for'   =>$t['results_for'],
    'card_free'     =>$t['card_free'],
    'card_from'     =>$t['card_from'],
    'card_see'      =>$t['card_see'],
    'card_ongoing'  =>$t['card_ongoing'],
    'no_results_h2' =>$t['no_results_h2'],
    'no_results_p'  =>$t['no_results_p'],
    'no_results_cta'=>$t['no_results_cta'],
    'prev'          =>$t['prev'],
    'next'          =>$t['next'],
    'aria_loading'  =>$t['aria_loading'],
    'months_short'  =>$t['months_short'],
    'categories'    =>$t['categories'],
    'ev_base'       =>($lang!=='es'?"/{$lang}":'').'/evento/',
],JSON_UNESCAPED_UNICODE) ?>;

// ── DOM refs ──────────────────────────────────────────────────────────────
const $=id=>document.getElementById(id);
const inputQ=$('srch-q'),clearBtn=$('srch-clear'),
      filterToggle=$('filter-toggle'),filterPanel=$('filter-panel'),
      fProv=$('f-provincia'),fCat=$('f-categoria'),
      fFecha=$('f-fecha'),fGratis=$('f-gratuito'),
      btnApply=$('btn-apply-filters'),btnReset=$('btn-reset-filters'),
      grid=$('srch-grid'),pagination=$('srch-pagination'),
      countEl=$('srch-count'),timeEl=$('srch-time'),mainEl=$('srch-results');
let ctrl=null,timer=null;

// ── Utils ─────────────────────────────────────────────────────────────────
const esc=s=>String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
const debounce=(fn,ms)=>(...a)=>{clearTimeout(timer);timer=setTimeout(()=>fn(...a),ms);};
const fmtDate=d=>{if(!d)return{dia:'--',mes:'---'};const[y,m,dd]=d.split('-');return{dia:+dd,mes:T.months_short[+m]||m};};
const getParams=(page=1)=>{const p={lang:LANG,page,limit:LIMIT};const q=inputQ?.value.trim()||'';if(q.length>=2)p.q=q;if(fProv?.value)p.provincia=fProv.value;if(fCat?.value)p.categoria=+fCat.value;if(fGratis?.checked)p.gratuito=1;if(fFecha?.value)p.fecha_desde=fFecha.value;return p;};
const mkSkeletons=n=>Array.from({length:n},()=>`<article class="srch-card srch-card--skeleton"><div class="srch-card__img-wrap srch-skeleton"></div><div class="srch-card__body"><div class="srch-skeleton srch-skeleton-title"></div><div class="srch-skeleton srch-skeleton-text"></div></div></article>`).join('');


// ── Renderizar tarjeta JS ─────────────────────────────────────────────────
function mkCard(ev,idx){
    const fd=fmtDate(ev.start_date);
    const today=new Date().toISOString().slice(0,10);
    const ongoing=ev.end_date&&ev.end_date>=today&&ev.start_date<=today;
    const catLbl=T.categories[ev.category_id]||'';
    const loading=idx===0?'eager':'lazy';
    const dbc=ongoing?' srch-card__date-badge--ongoing':'';
    const dm=ongoing?esc(T.card_ongoing):fd.mes;
    let pb='';
    if(ev.is_free)pb=`<span class="srch-card__badge-free">${esc(T.card_free)}</span>`;
    else if(ev.ticket_price)pb=`<span class="srch-card__badge-price">${esc(T.card_from)} ${esc(String(ev.ticket_price))}€</span>`;
    const loc=esc([ev.venue_name||ev.municipality,ev.province].filter(Boolean).join(' (')+(ev.province?')':''));
    const desc=esc((ev.short_description||'').replace(/<[^>]*>/g,'').slice(0,120));
    const star=ev.is_featured?'<span class="srch-card__featured-icon" aria-hidden="true">⭐</span>':'';
    const url=ev.url||('https://rutasrurales.io'+T.ev_base+encodeURIComponent(ev.slug));
    return `<article class="srch-card" itemscope itemtype="https://schema.org/Event">
  <div class="srch-card__img-wrap"><img class="srch-card__img" src="${esc(ev.imagen)}" alt="${esc(ev.titulo)}" loading="${loading}" decoding="async" itemprop="image"><div class="srch-card__date-badge${dbc}" aria-hidden="true"><span class="srch-card__date-dia">${fd.dia}</span><span class="srch-card__date-mes">${dm}</span></div>${pb}<meta itemprop="startDate" content="${esc(ev.start_date)}"></div>
  <div class="srch-card__body"><p class="srch-card__cat">${esc(catLbl)}</p><h3 class="srch-card__title" itemprop="name">${esc(ev.titulo)}</h3><p class="srch-card__desc">${desc}</p><p class="srch-card__meta"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>${loc}</p></div>
  <div class="srch-card__footer"><a class="srch-card__link" href="${esc(url)}" itemprop="url">${esc(T.card_see)} →</a>${star}</div>
</article>`;
}
// ── Paginación JS ─────────────────────────────────────────────────────────
function mkPagination(total,page,pages){
    if(!pagination){return;}
    if(pages<=1){pagination.innerHTML='';return;}
    let h='';
    if(page>1)h+=`<button class="srch-pagination__btn" data-page="${page-1}">${T.prev}</button>`;
    const all=pages<=7?[...Array(pages)].map((_,i)=>i+1):[...new Set([1,2,...[page-1,page,page+1].filter(n=>n>1&&n<pages),pages-1,pages])].sort((a,b)=>a-b);
    let prev2=0;
    all.forEach(p=>{if(prev2&&p-prev2>1)h+='<span class="srch-pagination__ellipsis">…</span>';h+=`<button class="srch-pagination__btn" data-page="${p}"${p===page?' aria-current="page"':''}>${p}</button>`;prev2=p;});
    if(page<pages)h+=`<button class="srch-pagination__btn" data-page="${page+1}">${T.next}</button>`;
    pagination.innerHTML=h;
    pagination.querySelectorAll('[data-page]').forEach(b=>b.addEventListener('click',()=>doSearch(+b.dataset.page)));
}
// ── Contador ──────────────────────────────────────────────────────────────
function mkCount(total,q,ms){
    if(!countEl)return;
    let tx=total===1?`<strong>1</strong> ${T.results_1}`:total>1?`<strong>${total.toLocaleString()}</strong> ${T.results_count.replace('{N}','')}`:T.results_0;
    if(q)tx+=` ${T.results_for} "<em>${esc(q)}</em>"`;
    countEl.innerHTML=tx;
    if(timeEl)timeEl.textContent=ms?`(${ms}ms)`:'';
}

// ── FETCH PRINCIPAL ───────────────────────────────────────────────────────
async function doSearch(page=1){
    const params=getParams(page);
    if(ctrl)ctrl.abort();
    ctrl=new AbortController();
    if(mainEl)mainEl.setAttribute('aria-busy','true');
    if(grid)grid.innerHTML=mkSkeletons(6);
    if(countEl)countEl.innerHTML=`<em>${T.aria_loading}</em>`;
    const up=new URLSearchParams(params);
    if(page===1)up.delete('page');
    history.replaceState({},'',location.pathname+(up.toString()?'?'+up.toString():''));
    try{
        const res=await fetch(`${API}?${new URLSearchParams(params)}`,{signal:ctrl.signal,headers:{'Accept':'application/json'}});
        if(!res.ok)throw new Error('HTTP '+res.status);
        const data=await res.json();
        if(!data.success)throw new Error(data.error||'Error');
        const cards=(data.results||[]).map((ev,i)=>mkCard(ev,i)).join('');
        if(grid){
            if(cards){
                grid.innerHTML=cards;
                if('IntersectionObserver' in window){
                    const io=new IntersectionObserver((en,o)=>{en.forEach(e=>{if(e.isIntersecting){const img=e.target;if(img.dataset.src)img.src=img.dataset.src;o.unobserve(img);}});},{rootMargin:'200px'});
                    grid.querySelectorAll('img[loading="lazy"]').forEach(img=>io.observe(img));
                }
            } else {
                grid.innerHTML=`<div class="srch-empty" style="grid-column:1/-1"><div class="srch-empty__icon">😕</div><h2 class="srch-empty__h2">${T.no_results_h2}</h2><p class="srch-empty__p">${T.no_results_p}</p><a class="srch-empty__cta" href="/${LANG!=='es'?LANG+'/':''}eventos/">${T.no_results_cta}</a></div>`;
            }
        }
        mkCount(data.total,params.q||'',data.query_time_ms);
        mkPagination(data.total,data.page,data.pages);
    }catch(e){
        if(e.name==='AbortError')return;
        if(grid)grid.innerHTML='<p style="padding:2rem 1rem;color:#dc3545">Error al cargar. Inténtalo de nuevo.</p>';
    }finally{
        if(mainEl)mainEl.setAttribute('aria-busy','false');
    }
}
// ── Listeners ─────────────────────────────────────────────────────────────
const dSearch=debounce(()=>doSearch(1),300);
if(inputQ){
    inputQ.addEventListener('input',()=>{if(clearBtn)clearBtn.classList.toggle('visible',inputQ.value.length>0);dSearch();});
    if(clearBtn&&inputQ.value.length>0)clearBtn.classList.add('visible');
}
if(clearBtn)clearBtn.addEventListener('click',()=>{inputQ.value='';clearBtn.classList.remove('visible');inputQ.focus();doSearch(1);});
if(filterToggle&&filterPanel)filterToggle.addEventListener('click',()=>{const o=filterPanel.classList.toggle('open');filterToggle.setAttribute('aria-expanded',String(o));});
if(btnApply)btnApply.addEventListener('click',()=>{filterPanel?.classList.remove('open');filterToggle?.setAttribute('aria-expanded','false');doSearch(1);});
if(btnReset)btnReset.addEventListener('click',()=>{
    [inputQ,fProv,fCat,fFecha].forEach(el=>{if(el)el.value='';});
    if(fGratis)fGratis.checked=false;
    if(clearBtn)clearBtn.classList.remove('visible');
    filterPanel?.classList.remove('open');filterToggle?.setAttribute('aria-expanded','false');doSearch(1);
});
document.querySelectorAll('.srch-chip__remove').forEach(btn=>btn.addEventListener('click',()=>{
    const f=btn.dataset.filter;
    if(f==='provincia'&&fProv)fProv.value='';
    if(f==='categoria'&&fCat)fCat.value='';
    if(f==='gratuito'&&fGratis)fGratis.checked=false;
    btn.closest('.srch-chip')?.remove();doSearch(1);
}));
[fProv,fCat,fFecha,fGratis].forEach(el=>{if(el)el.addEventListener('change',()=>doSearch(1));});
document.getElementById('srch-form')?.addEventListener('submit',e=>{e.preventDefault();doSearch(1);});
document.addEventListener('keydown',e=>{
    if(e.key==='Escape'&&filterPanel?.classList.contains('open')){
        filterPanel.classList.remove('open');filterToggle?.setAttribute('aria-expanded','false');filterToggle?.focus();
    }
});
})();
</script>
</body>
</html>

