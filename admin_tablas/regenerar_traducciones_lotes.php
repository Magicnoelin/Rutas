<?php
/**
 * REGENERAR TRADUCCIONES POR LOTES — i18n v3
 * Procesa N lugares por petición (por defecto 10).
 * ?lote=0&tam=10  → procesa lugares 0..9
 * ?reset=1        → borra TODAS las traducciones y reinicia
 */

include 'db.php';
require_once 'slug_lugares_helper.php';

header('Content-Type: text/html; charset=utf-8');
set_time_limit(120);
ini_set('max_execution_time', 120);

// ── HELPER: detectar si un texto ya contiene HTML de bloque ──────────────
function tieneHtmlBloque(string $texto): bool {
    return (bool)preg_match('/<(?:p|h[1-6]|ul|ol|li|div|section|article|blockquote)\\b/i', $texto);
}

// ── FUNCIÓN DE TRADUCCIÓN VÍA GOOGLE TRANSLATE (sin API key) ─────────────
// Estrategia: sustituir etiquetas HTML por placeholders {T0},{T1}... antes de
// enviar a Google Translate, luego restaurarlas.
// Así Google recibe texto puro, traduce correctamente, y el HTML queda intacto.
function traducirTexto(string $texto, string $targetLang): string {
    if (empty(trim($texto))) return $texto;

    // 1. Extraer TODAS las etiquetas HTML y entidades → placeholders {T0},{T1}...
    $tags = [];
    $textoLimpio = preg_replace_callback(
        '/<[^>]+>|&[a-zA-Z0-9#]+;/',
        function ($m) use (&$tags) {
            $idx    = count($tags);
            $tags[] = $m[0];
            return "{T{$idx}}";
        },
        $texto
    );
    if ($textoLimpio === null) $textoLimpio = strip_tags($texto); // fallback

    // 2. Dividir en fragmentos de máx. 4500 chars respetando placeholders
    $partes     = preg_split('/(\{T\d+\})/', $textoLimpio, -1, PREG_SPLIT_DELIM_CAPTURE);
    $fragmentos = [];
    $buffer     = '';
    foreach ((array)$partes as $p) {
        if (strlen($buffer) + strlen($p) > 4500) {
            if ($buffer !== '') $fragmentos[] = $buffer;
            $buffer = $p;
        } else {
            $buffer .= $p;
        }
    }
    if ($buffer !== '') $fragmentos[] = $buffer;

    // 3. Traducir cada fragmento (solo texto, sin HTML)
    $traducido = [];
    foreach ($fragmentos as $frag) {
        // Si el fragmento es solo placeholders, no hay nada que traducir
        if (trim(preg_replace('/\{T\d+\}/', '', $frag)) === '') {
            $traducido[] = $frag;
            continue;
        }
        $apiUrl = 'https://translate.googleapis.com/translate_a/single'
                . '?client=gtx&sl=es&tl=' . urlencode($targetLang) . '&dt=t&dj=1';
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => 'q=' . urlencode($frag),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || !$resp) { $traducido[] = $frag; continue; }
        $data = json_decode($resp, true);
        if (json_last_error() !== JSON_ERROR_NONE) { $traducido[] = $frag; continue; }

        $partesTrad = '';
        if (!empty($data['sentences'])) {
            foreach ($data['sentences'] as $seg) {
                if (!empty($seg['trans'])) $partesTrad .= $seg['trans'];
            }
        } elseif (!empty($data[0])) {
            foreach ($data[0] as $seg) {
                if (!empty($seg[0])) $partesTrad .= $seg[0];
            }
        }
        // Google a veces escapa { } → revertir para que los placeholders funcionen
        $partesTrad = str_replace(
            ['&lbrace;', '&rbrace;', '&#123;', '&#125;', '{ T', '{ t'],
            ['{',        '}',        '{',       '}',      '{T',  '{t'],
            $partesTrad
        );
        // Normalizar espacios dentro de placeholders: { T 0 } → {T0}
        $partesTrad = preg_replace('/\{\s*T\s*(\d+)\s*\}/', '{T$1}', $partesTrad);
        if (empty($partesTrad)) { $traducido[] = $frag; continue; }
        $traducido[] = $partesTrad;
        usleep(120000); // 120 ms entre llamadas
    }

    // 4. Unir y restaurar etiquetas HTML originales
    $resultado = implode('', $traducido);
    foreach ($tags as $idx => $tag) {
        $resultado = str_replace("{T{$idx}}", $tag, $resultado);
    }
    return $resultado;
}


// ── TEXTOS FIJOS ──────────────────────────────────────────────────────────
$textos = [
    'es' => ['intro'=>'Descubre','in'=>'en','h3a'=>'Sobre','h3v'=>'Qué ver',
             'acc'=>'Accesible en silla de ruedas, apto para familias',
             'msuf'=>'en España','mv'=>'Visita','mend'=>'Descubre este lugar especial en España.'],
    'en' => ['intro'=>'Discover','in'=>'in','h3a'=>'About','h3v'=>'What to See',
             'acc'=>'Wheelchair accessible, family-friendly',
             'msuf'=>'in Spain','mv'=>'Visit','mend'=>'Discover this remarkable place in Spain.'],
    'fr' => ['intro'=>'Découvrez','in'=>'à','h3a'=>'À propos de','h3v'=>'À voir',
             'acc'=>'Accessible, adapté aux familles',
             'msuf'=>'en Espagne','mv'=>'Visitez','mend'=>'Découvrez ce lieu remarquable en Espagne.'],
    'de' => ['intro'=>'Entdecken Sie','in'=>'in','h3a'=>'Über','h3v'=>'Sehenswürdigkeiten',
             'acc'=>'Barrierefrei, familienfreundlich',
             'msuf'=>'in Spanien','mv'=>'Besuchen Sie','mend'=>'Entdecken Sie diesen tollen Ort in Spanien.'],
    'zh' => ['intro'=>'探索','in'=>'在','h3a'=>'关于','h3v'=>'参观亮点',
             'acc'=>'无障碍, 适合家庭',
             'msuf'=>'西班牙','mv'=>'参观','mend'=>'发现西班牙的这个精彩景点。'],
];
$fallbackIntro = [
    'en'=>'This remarkable location offers a unique experience for travelers seeking authentic rural tourism.',
    'fr'=>"Ce lieu remarquable offre une expérience unique aux voyageurs à la recherche d'authenticité.",
    'de'=>'Dieser bemerkenswerte Ort bietet Reisenden ein einzigartiges Erlebnis im ländlichen Spanien.',
    'zh'=>'这个出色的景点为游客提供独特的旅行体验，充满西班牙乡村的传统与魅力。',
];
$fallbackBody = [
    'en'=>'Enjoy its surroundings, cultural heritage, and local environment.',
    'fr'=>'Profitez de ses environs, de son patrimoine culturel et de son environnement local.',
    'de'=>'Genießen Sie die Umgebung, das kulturelle Erbe und die lokale Natur.',
    'zh'=>'尽情欣赏周边环境、文化遗产和当地风情。',
];

// ── RESET ─────────────────────────────────────────────────────────────────
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    $pdo->exec("DELETE FROM places_of_interest_trads");
    header('Location: regenerar_traducciones_lotes.php?lote=0&tam=10&msg=reset');
    exit;
}

// ── PARÁMETROS ────────────────────────────────────────────────────────────
$tam  = max(1, min(50, (int)($_GET['tam']  ?? 10)));
$lote = (int)($_GET['lote'] ?? -1);
$msg  = $_GET['msg'] ?? '';

$totalLugares = (int)$pdo->query("SELECT COUNT(*) FROM places_of_interest WHERE is_active=1")->fetchColumn();
$totalLotes   = (int)ceil($totalLugares / $tam);

function contarCompletos(PDO $pdo): int {
    return (int)$pdo->query("SELECT COUNT(*) FROM (
        SELECT place_id FROM places_of_interest_trads
        WHERE language_code IN ('es','en','fr','de','zh')
        GROUP BY place_id HAVING COUNT(DISTINCT language_code)=5) t")->fetchColumn();
}
function contarPorIdioma(PDO $pdo): array {
    return $pdo->query("SELECT language_code, COUNT(*) AS c FROM places_of_interest_trads GROUP BY language_code")
               ->fetchAll(PDO::FETCH_KEY_PAIR);
}

// ── PROCESAR LOTE ─────────────────────────────────────────────────────────
$procesados = []; $errores = [];
$tiempoInicio = microtime(true);

if ($lote >= 0) {
    $offset = $lote * $tam;
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.municipality, p.province, p.address,
               p.short_description AS short_original,
               p.description AS desc_original,
               p.opening_hours, p.entry_fee, p.entry_fee_details, p.facilities,
               COALESCE(c.name,'') AS categoria
        FROM   places_of_interest p
        LEFT JOIN categories_places c ON p.category_id = c.id
        WHERE  p.is_active = 1
        ORDER  BY p.id LIMIT :tam OFFSET :offset");
    $stmt->bindValue(':tam', $tam, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insert = $pdo->prepare("
        INSERT INTO places_of_interest_trads
            (place_id, language_code, name, slug, short_description, description,
             address, municipality, province, opening_hours, accessibility,
             meta_title, meta_description, entry_fee, entry_fee_details, facilities)
        VALUES (:place_id,:lang,:name,:slug,:short_desc,:description,
                :address,:municipality,:province,:opening_hours,:accessibility,
                :meta_title,:meta_description,:entry_fee,:entry_fee_details,:facilities)
        ON DUPLICATE KEY UPDATE
            name=VALUES(name), slug=VALUES(slug),
            short_description=VALUES(short_description), description=VALUES(description),
            address=VALUES(address), municipality=VALUES(municipality),
            province=VALUES(province), opening_hours=VALUES(opening_hours),
            accessibility=VALUES(accessibility), meta_title=VALUES(meta_title),
            meta_description=VALUES(meta_description), entry_fee=VALUES(entry_fee),
            entry_fee_details=VALUES(entry_fee_details), facilities=VALUES(facilities)");



    foreach ($lugares as $lugar) {
        try {
            // NO strip_tags: preservar HTML original para traducirlo correctamente
            $shortOrigRaw = trim($lugar['short_original'] ?? '');
            $descOrigRaw  = trim($lugar['desc_original']  ?? '');
            $catLabel     = !empty($lugar['categoria']) ? $lugar['categoria'] : 'Lugar de Interés';

            // FASE 1: ES — texto original en español, sin traducir
            $t           = $textos['es'];
            $slug        = generarSlugLugar($lugar['name'], $lugar['categoria'], $lugar['municipality'], 'es');
            $shortDescES = $t['intro'].' '.$lugar['name'].' '.$t['in'].' '.$lugar['municipality'].', '.$lugar['province'].'.';
            $introES     = !empty($shortOrigRaw) ? $shortOrigRaw : $shortDescES;
            // NO htmlspecialchars: bodyES puede contener HTML legítimo (<p>, <ul>, etc.)
            $bodyES      = !empty($descOrigRaw)  ? $descOrigRaw  : 'Un espacio especial en España.';
            if (tieneHtmlBloque($bodyES)) {
                $descES = '<section><h3>'.$t['h3a'].' '.htmlspecialchars($lugar['name']).'</h3><p>'.$introES.'</p></section>'
                        . '<section><h3>'.$t['h3v'].'</h3>'.$bodyES.'</section>';
            } else {
                $descES = '<section><h3>'.$t['h3a'].' '.htmlspecialchars($lugar['name']).'</h3><p>'.$introES.'</p></section>'
                        . '<section><h3>'.$t['h3v'].'</h3><p>'.$bodyES.'</p></section>';
            }

            $insert->execute([':place_id'=>$lugar['id'],':lang'=>'es',':name'=>$lugar['name'],
                ':slug'=>$slug,':short_desc'=>$shortDescES,':description'=>$descES,
                ':address'=>$lugar['address']??'',':municipality'=>$lugar['municipality'],
                ':province'=>$lugar['province'],':opening_hours'=>$lugar['opening_hours']??'',
                ':accessibility'=>$t['acc'],
                ':meta_title'=>$lugar['name'].' | '.$catLabel.' '.$t['msuf'],
                ':meta_description'=>$t['mv'].' '.$lugar['name'].' '.$t['in'].' '.$lugar['municipality'].'. '.$t['mend'],
                ':entry_fee'=>$lugar['entry_fee']??'',':entry_fee_details'=>$lugar['entry_fee_details']??'',
                ':facilities'=>$lugar['facilities']??'']);

            // FASE 2: EN/FR/DE/ZH — traducir con Google Translate
            foreach (['en','fr','de','zh'] as $lang) {
                $t    = $textos[$lang];
                $slug = generarSlugLugar($lugar['name'], $lugar['categoria'], $lugar['municipality'], $lang);
                $introContent = !empty($shortOrigRaw)
                    ? traducirTexto($shortOrigRaw, $lang)
                    : $fallbackIntro[$lang];
                $bodyContent = !empty($descOrigRaw)
                    ? traducirTexto($descOrigRaw, $lang)
                    : $fallbackBody[$lang];
                $shortTrad = traducirTexto($shortDescES, $lang);
                if (tieneHtmlBloque($bodyContent)) {
                    $desc = '<section><h3>'.$t['h3a'].' '.htmlspecialchars($lugar['name']).'</h3><p>'.$introContent.'</p></section>'
                          . '<section><h3>'.$t['h3v'].'</h3>'.$bodyContent.'</section>';
                } else {
                    $desc = '<section><h3>'.$t['h3a'].' '.htmlspecialchars($lugar['name']).'</h3><p>'.$introContent.'</p></section>'
                          . '<section><h3>'.$t['h3v'].'</h3><p>'.$bodyContent.'</p></section>';
                }
                $insert->execute([':place_id'=>$lugar['id'],':lang'=>$lang,':name'=>$lugar['name'],
                    ':slug'=>$slug,':short_desc'=>$shortTrad,':description'=>$desc,
                    ':address'=>$lugar['address']??'',':municipality'=>$lugar['municipality'],
                    ':province'=>$lugar['province'],':opening_hours'=>$lugar['opening_hours']??'',
                    ':accessibility'=>$t['acc'],
                    ':meta_title'=>$lugar['name'].' | '.$catLabel.' '.$t['msuf'],
                    ':meta_description'=>$t['mv'].' '.$lugar['name'].' '.$t['in'].' '.$lugar['municipality'].'. '.$t['mend'],
                    ':entry_fee'=>$lugar['entry_fee']??'',':entry_fee_details'=>$lugar['entry_fee_details']??'',
                    ':facilities'=>$lugar['facilities']??'']);
            }
            $procesados[] = ['id'=>$lugar['id'],'name'=>$lugar['name']];
        } catch (Exception $e) {
            $errores[] = ['id'=>$lugar['id'],'name'=>$lugar['name'],'error'=>$e->getMessage()];
        }
    }
}

$tiempoTotal   = round(microtime(true) - $tiempoInicio, 1);
$completos     = contarCompletos($pdo);
$porIdioma     = contarPorIdioma($pdo);
$siguienteLote = $lote + 1;
$hayMas        = ($lote >= 0) && ($siguienteLote < $totalLotes);
$pct           = $totalLugares > 0 ? round($completos / $totalLugares * 100) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Traducciones por lotes</title>
    <style>.progress{height:28px;font-size:1rem}#log-box{max-height:260px;overflow-y:auto;font-size:.82rem}.card-lang{min-width:110px;text-align:center}</style>
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:960px">
    <h2 class="mb-1"><i class="bi bi-translate"></i> Regenerar traducciones <span class="badge bg-primary">por lotes</span></h2>
    <p class="text-muted mb-3">Lote de <strong><?= $tam ?></strong> lugares · <?= $totalLugares ?> totales · <?= $totalLotes ?> lotes</p>
    <?php if ($msg === 'reset'): ?>
    <div class="alert alert-warning"><i class="bi bi-trash3-fill"></i> Tabla limpiada. Listo para empezar desde lote 0.</div>
    <?php endif; ?>
    <!-- PROGRESO -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <strong>Progreso global</strong>
                <span class="badge bg-primary fs-6"><?= $pct ?>% — <?= $completos ?>/<?= $totalLugares ?> completos</span>
            </div>
            <div class="progress mb-3">
                <div class="progress-bar progress-bar-striped bg-success" style="width:<?= $pct ?>%"><?= $pct ?>%</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
            <?php foreach (['es','en','fr','de','zh'] as $l): $c=(int)($porIdioma[$l]??0); $ok=$c===$totalLugares; ?>
            <div class="card card-lang border-0 shadow-sm <?= $ok?'bg-success text-white':'bg-light' ?>">
                <div class="card-body py-2 px-3"><div class="fw-bold"><?= strtoupper($l) ?></div><div class="small"><?= $c ?>/<?= $totalLugares ?></div></div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- CONTROLES -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end flex-wrap">
                <div class="col-auto">
                    <label class="form-label fw-semibold">Lugares por lote</label>
                    <select id="sel-tam" class="form-select" style="width:auto">
                        <?php foreach ([5,10,20,30] as $v): ?>
                        <option value="<?= $v ?>"<?= $v==$tam?' selected':''?>><?= $v ?> lugares</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label fw-semibold">Nº de lote</label>
                    <input id="inp-lote" type="number" class="form-control" style="width:90px"
                           value="<?= max(0,$lote) ?>" min="0" max="<?= $totalLotes-1 ?>">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" onclick="irALote()">
                        <i class="bi bi-play-fill"></i> Procesar este lote
                    </button>
                </div>
                <?php if ($hayMas): ?>
                <div class="col-auto">
                    <a id="btn-sig" href="?lote=<?= $siguienteLote ?>&tam=<?= $tam ?>" class="btn btn-success">
                        <i class="bi bi-skip-forward-fill"></i> Siguiente lote (<?= $siguienteLote ?>/<?= $totalLotes-1 ?>) →
                    </a>
                </div>
                <?php elseif ($lote >= 0): ?>
                <div class="col-auto">
                    <span class="badge bg-success fs-6 p-2"><i class="bi bi-check-circle-fill"></i> ¡Todos los lotes procesados!</span>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($hayMas): ?>
            <div class="form-check form-switch mt-3">
                <input class="form-check-input" type="checkbox" id="chk-auto">
                <label class="form-check-label" for="chk-auto">
                    <strong>Autoavance</strong> — ir al siguiente lote en
                    <select id="sel-delay" class="form-select form-select-sm d-inline-block ms-1" style="width:auto">
                        <option value="10">10 s</option>
                        <option value="15" selected>15 s</option>
                        <option value="20">20 s</option>
                        <option value="30">30 s</option>
                    </select>
                    <span id="cuenta-atras" class="ms-2 fw-bold text-danger"></span>
                </label>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- LOG -->
    <?php if ($lote >= 0): ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header <?= count($errores)>0?'bg-warning text-dark':'bg-success text-white' ?> d-flex justify-content-between">
            <strong>Lote <?= $lote ?> — <?= count($procesados) ?> lugares en <?= $tiempoTotal ?>s</strong>
            <?php if(count($errores)): ?><span class="badge bg-danger"><?= count($errores) ?> errores</span><?php endif; ?>
        </div>
        <div id="log-box" class="card-body p-2 bg-dark text-light font-monospace">
            <?php foreach($procesados as $p): ?>
            <div class="text-success"><i class="bi bi-check2"></i> [<?= $p['id'] ?>] <?= htmlspecialchars($p['name']) ?></div>
            <?php endforeach; ?>
            <?php foreach($errores as $e): ?>
            <div class="text-danger"><i class="bi bi-x-circle"></i> [<?= $e['id'] ?>] <?= htmlspecialchars($e['name']) ?> — <?= htmlspecialchars($e['error']) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <!-- BOTONES -->
    <div class="d-flex gap-2 flex-wrap">
        <a href="?lote=0&tam=<?= $tam ?>" class="btn btn-outline-primary"><i class="bi bi-skip-backward-fill"></i> Empezar desde lote 0</a>
        <a href="?reset=1" class="btn btn-outline-danger" onclick="return confirm('¿Borrar TODAS las traducciones y empezar de cero?')"><i class="bi bi-trash3"></i> Reset completo</a>
        <a href="lugares_index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver a Lugares</a>
    </div>
</div>
<script>
function irALote(){
    const lote=document.getElementById('inp-lote').value;
    const tam=document.getElementById('sel-tam').value;
    window.location.href=`?lote=${lote}&tam=${tam}`;
}
const chkAuto=document.getElementById('chk-auto');
const selDelay=document.getElementById('sel-delay');
const spanCD=document.getElementById('cuenta-atras');
const hayMas=<?= $hayMas?'true':'false' ?>;
const urlSig=<?= $hayMas?json_encode('?lote='.$siguienteLote.'&tam='.$tam):'null' ?>;
let timer=null,cd=0;
function arrancarCD(){
    cd=parseInt(selDelay?selDelay.value:15);
    spanCD.textContent=`⏱ ${cd}s`;
    timer=setInterval(()=>{ cd--; spanCD.textContent=`⏱ ${cd}s`; if(cd<=0){clearInterval(timer);window.location.href=urlSig;} },1000);
}
function pararCD(){clearInterval(timer);if(spanCD)spanCD.textContent='';}
if(chkAuto){
    if(localStorage.getItem('auto_lotes')==='1'&&hayMas){chkAuto.checked=true;arrancarCD();}
    chkAuto.addEventListener('change',()=>{
        localStorage.setItem('auto_lotes',chkAuto.checked?'1':'0');
        chkAuto.checked?arrancarCD():pararCD();
    });
    if(selDelay)selDelay.addEventListener('change',()=>{if(chkAuto.checked){pararCD();arrancarCD();}});
}
if(!hayMas)localStorage.removeItem('auto_lotes');
const logBox=document.getElementById('log-box');
if(logBox)logBox.scrollTop=logBox.scrollHeight;
</script>
</body>
</html>

