<?php
/**
 * GESTOR DE RUTAS TEMÁTICAS
 * admin_tablas/rutas.php
 * Crear, editar, gestionar items y publicar rutas temáticas
 */
require_once __DIR__ . '/db.php';

$pdo = getDBConnection();
$msg = '';
$msgType = 'ok';

// ── ACCIONES POST ────────────────────────────────────────────────────────────

$action = $_REQUEST['action'] ?? '';

// Guardar ruta (crear o editar)
if ($action === 'save_ruta' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $slug        = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration    = (int)($_POST['duration_days'] ?? 3);
    $difficulty  = $_POST['difficulty_level'] ?? 'facil';
    $province    = trim($_POST['province'] ?? 'Soria');
    $season      = trim($_POST['season'] ?? '');
    $hero_image  = trim($_POST['hero_image'] ?? '');
    $cover_color = trim($_POST['cover_color'] ?? '#2F5233');
    $seo_title   = trim($_POST['seo_title'] ?? '');
    $seo_desc    = trim($_POST['seo_description'] ?? '');
    $seo_keys    = trim($_POST['seo_keywords'] ?? '');
    $status      = $_POST['status'] ?? 'draft';
    $is_public   = isset($_POST['is_public']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $itinerary   = trim($_POST['itinerary_json'] ?? '[]');

    // Validar JSON del itinerario
    if (!json_decode($itinerary)) $itinerary = '[]';

    // Auto-slug si vacío
    if (empty($slug) && !empty($name)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $name)));
        $slug = trim($slug, '-');
    }

    if (empty($name) || empty($slug)) {
        $msg = 'El nombre y el slug son obligatorios.';
        $msgType = 'error';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE routes SET name=?, slug=?, description=?, duration_days=?,
                difficulty_level=?, province=?, season=?, hero_image=?, cover_color=?,
                seo_title=?, seo_description=?, seo_keywords=?, status=?, is_public=?,
                is_featured=?, itinerary_json=?, updated_at=NOW()
                WHERE id=?");
            $stmt->execute([$name,$slug,$description,$duration,$difficulty,$province,$season,
                $hero_image,$cover_color,$seo_title,$seo_desc,$seo_keys,$status,$is_public,
                $is_featured,$itinerary,$id]);
            $msg = '✅ Ruta actualizada correctamente.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO routes (name,slug,description,duration_days,
                difficulty_level,province,season,hero_image,cover_color,seo_title,
                seo_description,seo_keywords,status,is_public,is_featured,itinerary_json,
                created_at,updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())");
            $stmt->execute([$name,$slug,$description,$duration,$difficulty,$province,$season,
                $hero_image,$cover_color,$seo_title,$seo_desc,$seo_keys,$status,$is_public,
                $is_featured,$itinerary]);
            $id = $pdo->lastInsertId();
            $msg = '✅ Ruta creada. Ahora añade los items.';
        }
        header("Location: rutas.php?action=edit&id=$id&msg=" . urlencode($msg));
        exit;
    }
}

// Toggle status
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $row = $pdo->prepare("SELECT status, is_public FROM routes WHERE id=?");
    $row->execute([$id]);
    $r = $row->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        $newStatus = ($r['status'] === 'published') ? 'draft' : 'published';
        $newPublic = ($newStatus === 'published') ? 1 : 0;
        $pdo->prepare("UPDATE routes SET status=?, is_public=? WHERE id=?")->execute([$newStatus,$newPublic,$id]);
    }
    header("Location: rutas.php");
    exit;
}

// Eliminar ruta
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM route_items WHERE route_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM routes WHERE id=?")->execute([$id]);
    header("Location: rutas.php?msg=" . urlencode('🗑️ Ruta eliminada.'));
    exit;
}

// Añadir item a ruta
if ($action === 'add_item' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $route_id   = (int)$_POST['route_id'];
    $item_type  = $_POST['item_type'] ?? 'place';
    $item_id    = (int)$_POST['item_id'];
    $day_number = (int)($_POST['day_number'] ?? 1);
    $time_slot  = trim($_POST['time_slot'] ?? '');
    $note       = trim($_POST['editorial_note'] ?? '');
    $highlight  = isset($_POST['is_highlight']) ? 1 : 0;

    // Calcular display_order
    $maxOrder = $pdo->prepare("SELECT COALESCE(MAX(display_order),0)+1 FROM route_items WHERE route_id=? AND day_number=?");
    $maxOrder->execute([$route_id, $day_number]);
    $order = $maxOrder->fetchColumn();

    $pdo->prepare("INSERT INTO route_items (route_id,item_type,item_id,day_number,display_order,time_slot,editorial_note,is_highlight)
        VALUES (?,?,?,?,?,?,?,?)")->execute([$route_id,$item_type,$item_id,$day_number,$order,$time_slot,$note,$highlight]);

    header("Location: rutas.php?action=edit&id=$route_id&tab=items#items");
    exit;
}

// Eliminar item
if ($action === 'del_item' && isset($_GET['item_id'])) {
    $item_id  = (int)$_GET['item_id'];
    $route_id = (int)$_GET['route_id'];
    $pdo->prepare("DELETE FROM route_items WHERE id=?")->execute([$item_id]);
    header("Location: rutas.php?action=edit&id=$route_id&tab=items#items");
    exit;
}

// Obtener items de una ruta (para generar itinerario automático)
if ($action === 'get_route_items' && isset($_GET['route_id'])) {
    header('Content-Type: application/json');
    $route_id = (int)$_GET['route_id'];
    
    // Obtener la ruta para saber duration_days
    $r = $pdo->prepare("SELECT id, name, duration_days FROM routes WHERE id=?");
    $r->execute([$route_id]);
    $route = $r->fetch(PDO::FETCH_ASSOC);
    
    if (!$route) {
        echo json_encode(['success' => false, 'error' => 'Ruta no encontrada']);
        exit;
    }
    
    // Obtener items
    $si = $pdo->prepare("SELECT ri.*, 
        CASE 
            WHEN ri.item_type = 'accommodation' THEN a.name
            WHEN ri.item_type = 'place' THEN p.name
            WHEN ri.item_type = 'activity' THEN t.name
            WHEN ri.item_type = 'event' THEN e.name
        END as item_name,
        CASE 
            WHEN ri.item_type = 'accommodation' THEN a.municipality
            WHEN ri.item_type = 'place' THEN p.municipality
            WHEN ri.item_type = 'activity' THEN t.municipality
            WHEN ri.item_type = 'event' THEN e.municipality
        END as item_location
        FROM route_items ri
        LEFT JOIN accommodations a ON ri.item_type = 'accommodation' AND ri.item_id = a.id
        LEFT JOIN places_of_interest p ON ri.item_type = 'place' AND ri.item_id = p.id
        LEFT JOIN tourist_activities t ON ri.item_type = 'activity' AND ri.item_id = t.id
        LEFT JOIN cultural_events e ON ri.item_type = 'event' AND ri.item_id = e.id
        WHERE ri.route_id = ?
        ORDER BY ri.day_number ASC, ri.display_order ASC");
    $si->execute([$route_id]);
    $items = $si->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'route' => $route,
        'items' => $items
    ]);
    exit;
}

// Búsqueda AJAX de items
if ($action === 'search_items') {

    header('Content-Type: application/json');
    $q    = '%' . trim($_GET['q'] ?? '') . '%';
    $type = $_GET['type'] ?? 'place';
    $results = [];
    if ($type === 'accommodation') {
        $s = $pdo->prepare("SELECT id, name, municipality, province FROM accommodations WHERE (name LIKE ? OR municipality LIKE ?) AND is_active=1 LIMIT 15");
        $s->execute([$q,$q]);
    } elseif ($type === 'place') {
        $s = $pdo->prepare("SELECT id, name, municipality, province FROM places_of_interest WHERE (name LIKE ? OR municipality LIKE ?) AND is_active=1 LIMIT 15");
        $s->execute([$q,$q]);
    } elseif ($type === 'activity') {
        $s = $pdo->prepare("SELECT id, name, municipality, province FROM tourist_activities WHERE (name LIKE ? OR municipality LIKE ?) AND is_active=1 LIMIT 15");
        $s->execute([$q,$q]);
    } elseif ($type === 'event') {
        $s = $pdo->prepare("SELECT id, name as name, municipality, province FROM cultural_events WHERE (name LIKE ? OR municipality LIKE ?) AND is_active=1 LIMIT 15");
        $s->execute([$q,$q]);
    }
    echo json_encode($s->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ── CARGAR DATOS PARA VISTAS ─────────────────────────────────────────────────

$editRuta = null;
$editItems = [];

if ($action === 'edit' && isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
    $s = $pdo->prepare("SELECT * FROM routes WHERE id=?");
    $s->execute([$editId]);
    $editRuta = $s->fetch(PDO::FETCH_ASSOC);

    if ($editRuta) {
        $si = $pdo->prepare("SELECT * FROM route_items WHERE route_id=? ORDER BY day_number ASC, display_order ASC");
        $si->execute([$editId]);
        $editItems = $si->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Lista de rutas
$rutas = $pdo->query("SELECT id, name, slug, status, is_public, is_featured, province, duration_days, views_count, created_at FROM routes ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$msgGet = htmlspecialchars($_GET['msg'] ?? '');
$activeTab = $_GET['tab'] ?? 'datos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestor de Rutas Temáticas</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f0f4f8;color:#1a2e1a;display:flex;min-height:100vh}

/* Sidebar */
.sidebar{width:220px;background:#2c3e50;color:#fff;padding:20px 0;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar h2{text-align:center;font-size:1rem;color:#1abc9c;padding:0 15px 20px;border-bottom:1px solid #34495e}
.sidebar ul{list-style:none;padding:0;margin-top:10px}
.sidebar ul li a{display:block;padding:12px 20px;color:#ccc;text-decoration:none;font-size:.9rem;transition:.2s}
.sidebar ul li a:hover,.sidebar ul li a.active{background:#34495e;color:#1abc9c;border-left:3px solid #1abc9c}
.sidebar ul li a i{margin-right:8px;width:16px}

/* Main */
.main{flex:1;padding:24px;overflow-y:auto}
.page-title{font-size:1.5rem;font-weight:700;color:#2c3e50;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.page-title i{color:#1abc9c}

/* Alert */
.alert{padding:12px 16px;border-radius:6px;margin-bottom:16px;font-size:.9rem}
.alert-ok{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
.alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}

/* Cards lista */
.rutas-grid{display:grid;gap:12px}
.ruta-card{background:#fff;border-radius:10px;padding:16px 20px;display:flex;align-items:center;gap:16px;box-shadow:0 2px 8px rgba(0,0,0,.06);border-left:4px solid #ccc}
.ruta-card.published{border-left-color:#27ae60}
.ruta-card.draft{border-left-color:#e67e22}
.ruta-card__info{flex:1}
.ruta-card__name{font-weight:700;font-size:1rem;color:#2c3e50}
.ruta-card__meta{font-size:.8rem;color:#666;margin-top:4px}
.ruta-card__badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:.75rem;font-weight:600}
.badge-published{background:#d4edda;color:#155724}
.badge-draft{background:#fff3cd;color:#856404}
.ruta-card__actions{display:flex;gap:8px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:6px;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;border:none;transition:.2s}
.btn-primary{background:#2F5233;color:#fff}.btn-primary:hover{background:#1e3a22}
.btn-success{background:#27ae60;color:#fff}.btn-success:hover{background:#1e8449}
.btn-warning{background:#e67e22;color:#fff}.btn-warning:hover{background:#ca6f1e}
.btn-danger{background:#e74c3c;color:#fff}.btn-danger:hover{background:#c0392b}
.btn-info{background:#3498db;color:#fff}.btn-info:hover{background:#2980b9}
.btn-secondary{background:#95a5a6;color:#fff}.btn-secondary:hover{background:#7f8c8d}
.btn-sm{padding:5px 10px;font-size:.8rem}
.btn-new{margin-bottom:20px}

/* Formulario */
.form-card{background:#fff;border-radius:10px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:20px}
.form-card h3{font-size:1.1rem;color:#2c3e50;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #f0f4f8;display:flex;align-items:center;gap:8px}
.form-card h3 i{color:#1abc9c}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-grid.cols3{grid-template-columns:1fr 1fr 1fr}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group.full{grid-column:1/-1}
label{font-size:.85rem;font-weight:600;color:#555}
input[type=text],input[type=url],input[type=color],select,textarea{width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:6px;font-size:.9rem;font-family:inherit;transition:.2s}
input:focus,select:focus,textarea:focus{outline:none;border-color:#2F5233;box-shadow:0 0 0 3px rgba(47,82,51,.1)}
textarea{resize:vertical;min-height:80px}
.form-check{display:flex;align-items:center;gap:8px;font-size:.9rem}
.form-check input{width:auto}

/* Tabs */
.tabs{display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #e0e0e0}
.tab-btn{padding:10px 20px;background:none;border:none;cursor:pointer;font-size:.9rem;font-weight:600;color:#666;border-bottom:3px solid transparent;margin-bottom:-2px;transition:.2s}
.tab-btn.active{color:#2F5233;border-bottom-color:#2F5233}
.tab-content{display:none}.tab-content.active{display:block}

/* Items */
.items-toolbar{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;background:#f8f9fa;padding:16px;border-radius:8px}
.items-toolbar .form-group{flex:1;min-width:150px}
.search-results{background:#fff;border:1px solid #ddd;border-radius:6px;max-height:200px;overflow-y:auto;margin-top:4px;display:none}
.search-result-item{padding:10px 14px;cursor:pointer;font-size:.88rem;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center}
.search-result-item:hover{background:#f0f7f0}
.search-result-item .item-loc{font-size:.78rem;color:#888}

.items-list{display:grid;gap:8px}
.item-row{background:#fff;border-radius:8px;padding:12px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);border-left:4px solid #ccc}
.item-row.accommodation{border-left-color:#3498db}
.item-row.place{border-left-color:#27ae60}
.item-row.activity{border-left-color:#e67e22}
.item-row.event{border-left-color:#9b59b6}
.item-row__type{font-size:.7rem;font-weight:700;text-transform:uppercase;padding:2px 8px;border-radius:20px;white-space:nowrap}
.type-accommodation{background:#d6eaf8;color:#1a5276}
.type-place{background:#d5f5e3;color:#1e8449}
.type-activity{background:#fdebd0;color:#784212}
.type-event{background:#e8daef;color:#6c3483}
.item-row__info{flex:1}
.item-row__name{font-weight:600;font-size:.9rem}
.item-row__meta{font-size:.78rem;color:#888;margin-top:2px}
.item-row__day{font-size:.8rem;background:#f0f4f8;padding:3px 8px;border-radius:4px;white-space:nowrap}
.day-group{margin-bottom:16px}
.day-group__header{font-weight:700;font-size:.95rem;color:#2c3e50;padding:8px 12px;background:#e8f4f8;border-radius:6px;margin-bottom:8px;display:flex;align-items:center;gap:8px}

/* Stats */
.stats-bar{display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap}
.stat-box{background:#fff;border-radius:8px;padding:14px 20px;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,.06);flex:1;min-width:100px}
.stat-box__num{font-size:1.8rem;font-weight:800;color:#2F5233}
.stat-box__label{font-size:.78rem;color:#888;margin-top:2px}

/* Preview */
.preview-link{display:inline-flex;align-items:center;gap:6px;color:#2F5233;font-size:.85rem;text-decoration:none;font-weight:600}
.preview-link:hover{text-decoration:underline}

@media(max-width:768px){
    .sidebar{display:none}
    .form-grid{grid-template-columns:1fr}
    .form-grid.cols3{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<nav class="sidebar">
    <h2><i class="fas fa-map-marked-alt"></i> Admin Rutas</h2>
    <ul>
        <li><a href="menu.php"><i class="fas fa-home"></i> Panel principal</a></li>
        <li><a href="rutas.php" class="active"><i class="fas fa-route"></i> Rutas temáticas</a></li>
        <li><a href="moderacion_lugares.php"><i class="fas fa-map-pin"></i> Lugares</a></li>
        <li><a href="moderacion_fotos.php"><i class="fas fa-images"></i> Fotos</a></li>
        <li><a href="https://rutasrurales.io" target="_blank"><i class="fas fa-external-link-alt"></i> Ver web</a></li>
    </ul>
</nav>

<!-- MAIN -->
<main class="main">

<?php if ($msgGet): ?>
<div class="alert alert-ok"><?= $msgGet ?></div>
<?php endif; ?>
<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if ($action === 'new' || ($action === 'edit' && $editRuta)): ?>
<!-- ═══════════════════════════════════════════════════════════
     FORMULARIO CREAR / EDITAR RUTA
═══════════════════════════════════════════════════════════ -->
<?php $r = $editRuta ?? []; $isNew = empty($r); ?>

<div class="page-title">
    <i class="fas fa-route"></i>
    <?= $isNew ? 'Nueva ruta temática' : 'Editar: ' . htmlspecialchars($r['name']) ?>
    <?php if (!$isNew): ?>
    <a href="https://rutasrurales.io/rutas/<?= htmlspecialchars($r['slug']) ?>" target="_blank" class="preview-link" style="margin-left:auto;font-size:.9rem">
        <i class="fas fa-eye"></i> Ver en web
    </a>
    <?php endif; ?>
</div>

<!-- Tabs -->
<div class="tabs">
    <button class="tab-btn <?= $activeTab==='datos'?'active':'' ?>" onclick="showTab('datos')"><i class="fas fa-info-circle"></i> Datos básicos</button>
    <button class="tab-btn <?= $activeTab==='seo'?'active':'' ?>" onclick="showTab('seo')"><i class="fas fa-search"></i> SEO</button>
    <button class="tab-btn <?= $activeTab==='itinerario'?'active':'' ?>" onclick="showTab('itinerario')"><i class="fas fa-calendar-alt"></i> Itinerario JSON</button>
    <?php if (!$isNew): ?>
    <button class="tab-btn <?= $activeTab==='items'?'active':'' ?>" onclick="showTab('items')" id="tab-items-btn"><i class="fas fa-list"></i> Items (<?= count($editItems) ?>)</button>
    <button class="tab-btn <?= $activeTab==='faqs'?'active':'' ?>" onclick="showTab('faqs')"><i class="fas fa-question-circle"></i> FAQs</button>
    <?php endif; ?>
</div>

<form method="POST" action="rutas.php">
<input type="hidden" name="action" value="save_ruta">
<input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">

<!-- TAB: DATOS BÁSICOS -->
<div class="tab-content <?= $activeTab==='datos'?'active':'' ?>" id="tab-datos">
<div class="form-card">
    <h3><i class="fas fa-edit"></i> Información principal</h3>
    <div class="form-grid">
        <div class="form-group full">
            <label>Nombre de la ruta *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($r['name'] ?? '') ?>" placeholder="Puente del 1 de Mayo en Soria 2026" required oninput="autoSlug(this)">
        </div>
        <div class="form-group">
            <label>Slug (URL) *</label>
            <input type="text" name="slug" id="slug-field" value="<?= htmlspecialchars($r['slug'] ?? '') ?>" placeholder="puente-1-mayo-soria" required>
            <small style="color:#888">URL: rutasrurales.io/rutas/<strong id="slug-preview"><?= htmlspecialchars($r['slug'] ?? 'tu-slug') ?></strong></small>
        </div>
        <div class="form-group">
            <label>Provincia</label>
            <input type="text" name="province" value="<?= htmlspecialchars($r['province'] ?? 'Soria') ?>">
        </div>
        <div class="form-group full">
            <label>Descripción</label>
            <textarea name="description" rows="4" placeholder="Descripción atractiva de la ruta..."><?= htmlspecialchars($r['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Duración (días)</label>
            <input type="text" name="duration_days" value="<?= (int)($r['duration_days'] ?? 3) ?>">
        </div>
        <div class="form-group">
            <label>Dificultad</label>
            <select name="difficulty_level">
                <?php foreach(['facil'=>'Fácil','moderado'=>'Moderado','dificil'=>'Difícil'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($r['difficulty_level']??'facil')===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Temporada</label>
            <select name="season">
                <option value="">— Sin especificar —</option>
                <?php foreach(['primavera'=>'🌸 Primavera','verano'=>'☀️ Verano','otoño'=>'🍂 Otoño','invierno'=>'❄️ Invierno','todo-el-año'=>'📅 Todo el año'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($r['season']??'')===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Color de fondo (si no hay imagen)</label>
            <input type="color" name="cover_color" value="<?= htmlspecialchars($r['cover_color'] ?? '#2F5233') ?>">
        </div>
        <div class="form-group full">
            <label>URL imagen hero (fondo del hero)</label>
            <input type="url" name="hero_image" value="<?= htmlspecialchars($r['hero_image'] ?? '') ?>" placeholder="https://rutasrurales.io/menu_images/...">
        </div>
    </div>
</div>

<div class="form-card">
    <h3><i class="fas fa-toggle-on"></i> Estado y visibilidad</h3>
    <div class="form-grid cols3">
        <div class="form-group">
            <label>Estado</label>
            <select name="status">
                <option value="draft" <?= ($r['status']??'draft')==='draft'?'selected':'' ?>>📝 Borrador</option>
                <option value="published" <?= ($r['status']??'')==='published'?'selected':'' ?>>✅ Publicada</option>
                <option value="archived" <?= ($r['status']??'')==='archived'?'selected':'' ?>>📦 Archivada</option>
            </select>
        </div>
        <div class="form-group" style="justify-content:flex-end">
            <label>&nbsp;</label>
            <label class="form-check">
                <input type="checkbox" name="is_public" value="1" <?= ($r['is_public']??0)?'checked':'' ?>>
                Visible al público
            </label>
        </div>
        <div class="form-group" style="justify-content:flex-end">
            <label>&nbsp;</label>
            <label class="form-check">
                <input type="checkbox" name="is_featured" value="1" <?= ($r['is_featured']??0)?'checked':'' ?>>
                ⭐ Destacada
            </label>
        </div>
    </div>
</div>

<div style="display:flex;gap:12px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar ruta</button>
    <a href="rutas.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>
</div><!-- /tab-datos -->

<!-- TAB: SEO -->
<div class="tab-content <?= $activeTab==='seo'?'active':'' ?>" id="tab-seo">
<div class="form-card">
    <h3><i class="fas fa-search"></i> SEO — Metadatos</h3>
    <div class="form-grid">
        <div class="form-group full">
            <label>SEO Title <small>(máx 60 chars)</small></label>
            <input type="text" name="seo_title" value="<?= htmlspecialchars($r['seo_title'] ?? '') ?>" placeholder="Puente 1 de Mayo en Soria 2026 | Escapada Rural + Alojamientos" maxlength="70">
            <small id="seo-title-count" style="color:#888">0 / 60 caracteres</small>
        </div>
        <div class="form-group full">
            <label>SEO Description <small>(máx 160 chars)</small></label>
            <textarea name="seo_description" rows="3" maxlength="320" placeholder="Descubre qué hacer en Soria el puente del 1 de mayo 2026..."><?= htmlspecialchars($r['seo_description'] ?? '') ?></textarea>
            <small id="seo-desc-count" style="color:#888">0 / 160 caracteres</small>
        </div>
        <div class="form-group full">
            <label>Keywords <small>(separadas por comas)</small></label>
            <textarea name="seo_keywords" rows="2" placeholder="puente 1 mayo Soria, escapada rural mayo, casas rurales Soria..."><?= htmlspecialchars($r['seo_keywords'] ?? '') ?></textarea>
        </div>
    </div>
</div>
<div style="display:flex;gap:12px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar SEO</button>
</div>
</div><!-- /tab-seo -->

<!-- TAB: ITINERARIO JSON -->
<div class="tab-content <?= $activeTab==='itinerario'?'active':'' ?>" id="tab-itinerario">
<div class="form-card">
    <h3><i class="fas fa-calendar-alt"></i> Itinerario JSON</h3>
    <p style="font-size:.85rem;color:#666;margin-bottom:12px">
        Define los días del itinerario. Cada día tiene: <code>dia</code>, <code>titulo</code>, <code>descripcion</code>, <code>icono</code>, <code>items_resumen</code>.<br>
        Usa <strong>"Generar desde items"</strong> para crearlo automáticamente a partir de los items que has añadido en la pestaña Items.
    </p>

    <div class="form-group">
        <label>JSON del itinerario</label>
        <textarea name="itinerary_json" id="itinerary-json" rows="14" style="font-family:monospace;font-size:.82rem"><?= htmlspecialchars($r['itinerary_json'] ?? '[]') ?></textarea>
    </div>
    <div style="margin-top:8px;display:flex;gap:8px">
        <button type="button" class="btn btn-info btn-sm" onclick="formatJson()"><i class="fas fa-code"></i> Formatear JSON</button>
        <button type="button" class="btn btn-success btn-sm" onclick="generateFromItems()"><i class="fas fa-magic"></i> Generar desde items</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="validateJson()"><i class="fas fa-check"></i> Validar</button>

    </div>
</div>
<div style="display:flex;gap:12px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar itinerario</button>
</div>
</div><!-- /tab-itinerario -->

</form>

<?php if (!$isNew): ?>
<!-- TAB: ITEMS (fuera del form principal) -->
<div class="tab-content <?= $activeTab==='items'?'active':'' ?>" id="tab-items">

<div class="form-card" id="items">
    <h3><i class="fas fa-plus-circle"></i> Añadir item a la ruta</h3>
    <form method="POST" action="rutas.php" id="add-item-form">
    <input type="hidden" name="action" value="add_item">
    <input type="hidden" name="route_id" value="<?= (int)$r['id'] ?>">
    <div class="items-toolbar">
        <div class="form-group">
            <label>Tipo</label>
            <select name="item_type" id="item-type-sel" onchange="clearSearch()">
                <option value="accommodation">🏠 Alojamiento</option>
                <option value="place" selected>🏛️ Lugar de interés</option>
                <option value="activity">🥾 Actividad</option>
                <option value="event">🎭 Evento</option>
            </select>
        </div>
        <div class="form-group" style="flex:2;position:relative">
            <label>Buscar por nombre o municipio</label>
            <input type="text" id="item-search" placeholder="Escribe para buscar..." autocomplete="off" oninput="searchItems(this.value)">
            <div class="search-results" id="search-results"></div>
            <input type="hidden" name="item_id" id="item-id-hidden">
            <div id="selected-item" style="margin-top:6px;font-size:.85rem;color:#27ae60;font-weight:600"></div>
        </div>
        <div class="form-group">
            <label>Día</label>
            <select name="day_number">
                <?php for($d=1;$d<=$r['duration_days'];$d++): ?>
                <option value="<?= $d ?>">Día <?= $d ?></option>
                <?php endfor; ?>
                <option value="<?= ($r['duration_days']+1) ?>">Día <?= ($r['duration_days']+1) ?></option>
            </select>
        </div>
        <div class="form-group">
            <label>Franja horaria</label>
            <select name="time_slot">
                <option value="">— Sin especificar —</option>
                <option value="mañana">🌅 Mañana</option>
                <option value="tarde">🌇 Tarde</option>
                <option value="noche">🌙 Noche</option>
                <option value="todo-el-dia">📅 Todo el día</option>
            </select>
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <label class="form-check">
                <input type="checkbox" name="is_highlight" value="1"> ⭐ Destacado
            </label>
        </div>
        <div class="form-group full">
            <label>Nota editorial (opcional)</label>
            <input type="text" name="editorial_note" placeholder="Por qué recomendamos este lugar...">
        </div>
        <div>
            <button type="submit" class="btn btn-success" onclick="return validateItem()"><i class="fas fa-plus"></i> Añadir</button>
        </div>
    </div>
    </form>
</div>

<!-- Lista de items agrupados por día -->
<div class="form-card">
    <h3><i class="fas fa-list"></i> Items de la ruta (<?= count($editItems) ?>)</h3>
    <?php if (empty($editItems)): ?>
    <p style="color:#888;text-align:center;padding:20px">No hay items aún. Usa el buscador de arriba para añadir alojamientos, lugares y actividades.</p>
    <?php else: ?>
    <?php
    // Agrupar por día
    $byDay = [];
    foreach ($editItems as $it) {
        $byDay[$it['day_number']][] = $it;
    }
    ksort($byDay);

    $typeLabels = ['accommodation'=>'Alojamiento','place'=>'Lugar','activity'=>'Actividad','event'=>'Evento'];
    $typeColors = ['accommodation'=>'type-accommodation','place'=>'type-place','activity'=>'type-activity','event'=>'type-event'];

    // Cargar nombres reales de los items
    $nameCache = [];
    foreach ($editItems as $it) {
        $t = $it['item_type'];
        $iid = $it['item_id'];
        if (!isset($nameCache[$t][$iid])) {
            $tbl = ['accommodation'=>'accommodations','place'=>'places_of_interest','activity'=>'tourist_activities','event'=>'cultural_events'][$t] ?? null;
            if ($tbl) {
                $nameCol = ($t === 'event') ? 'name' : 'name';
                $ns = $pdo->prepare("SELECT name, municipality FROM $tbl WHERE id=?");
                $ns->execute([$iid]);
                $nameCache[$t][$iid] = $ns->fetch(PDO::FETCH_ASSOC) ?: ['name'=>'(ID '.$iid.')','municipality'=>''];
            }
        }
    }
    ?>
    <?php foreach ($byDay as $day => $dayItems): ?>
    <div class="day-group">
        <div class="day-group__header">
            <i class="fas fa-calendar-day"></i> Día <?= $day ?>
            <span style="font-size:.8rem;font-weight:400;color:#666">(<?= count($dayItems) ?> items)</span>
        </div>
        <div class="items-list">
        <?php foreach ($dayItems as $it):
            $t = $it['item_type'];
            $info = $nameCache[$t][$it['item_id']] ?? ['name'=>'(desconocido)','municipality'=>''];
        ?>
        <div class="item-row <?= $t ?>">
            <span class="item-row__type <?= $typeColors[$t] ?? '' ?>"><?= $typeLabels[$t] ?? $t ?></span>
            <div class="item-row__info">
                <div class="item-row__name"><?= htmlspecialchars($info['name']) ?></div>
                <div class="item-row__meta">
                    <?= htmlspecialchars($info['municipality'] ?? '') ?>
                    <?php if ($it['time_slot']): ?> · <?= htmlspecialchars($it['time_slot']) ?><?php endif; ?>
                    <?php if ($it['is_highlight']): ?> · ⭐ Destacado<?php endif; ?>
                    <?php if ($it['editorial_note']): ?> · <em><?= htmlspecialchars(substr($it['editorial_note'],0,60)) ?></em><?php endif; ?>
                </div>
            </div>
            <span class="item-row__day">Día <?= $it['day_number'] ?> · #<?= $it['display_order'] ?></span>
            <a href="rutas.php?action=del_item&item_id=<?= $it['id'] ?>&route_id=<?= $r['id'] ?>"
               class="btn btn-danger btn-sm"
               onclick="return confirm('¿Eliminar este item?')">
               <i class="fas fa-trash"></i>
            </a>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- TAB: FAQs -->
<div class="tab-content <?= $activeTab==='faqs'?'active':'' ?>" id="tab-faqs">
<div class="form-card">
    <h3><i class="fas fa-question-circle"></i> Preguntas Frecuentes de esta ruta</h3>
    <p style="font-size:.85rem;color:#666;margin-bottom:16px">
        Si añades FAQs aquí, se mostrarán estas en lugar del contenido automático.
        Si no hay FAQs, el sistema genera preguntas automáticas adaptadas a la época del año.
    </p>

    <div id="faqs-container">
        <!-- Las FAQs se cargan vía AJAX -->
        <div style="text-align:center;padding:20px;color:#888">
            <i class="fas fa-spinner fa-spin"></i> Cargando FAQs...
        </div>
    </div>

    <hr style="margin:20px 0;border:none;border-top:1px solid #eee">

    <h4 style="margin-bottom:12px;color:#2c3e50"><i class="fas fa-plus-circle"></i> Añadir nueva FAQ</h4>
    <div class="form-grid" style="grid-template-columns:1fr 1fr">
        <div class="form-group full">
            <label>Pregunta</label>
            <input type="text" id="new-faq-question" placeholder="¿Cuál es la mejor época para visitar...?">
        </div>
        <div class="form-group full">
            <label>Respuesta</label>
            <textarea id="new-faq-answer" rows="3" placeholder="La mejor época para visitar..."></textarea>
        </div>
        <div class="form-group">
            <label>Orden</label>
            <input type="number" id="new-faq-order" value="0" min="0" style="width:80px">
        </div>
        <div style="display:flex;align-items:flex-end">
            <button class="btn btn-success" onclick="addFaq(<?= (int)$r['id'] ?>)"><i class="fas fa-save"></i> Guardar FAQ</button>
        </div>
    </div>
</div>
</div><!-- /tab-faqs -->

<?php endif; ?>

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════════
     LISTA DE RUTAS
═══════════════════════════════════════════════════════════ -->
<div class="page-title"><i class="fas fa-route"></i> Rutas Temáticas</div>

<!-- Stats -->
<?php
$total     = count($rutas);
$published = count(array_filter($rutas, fn($r) => $r['status'] === 'published'));
$draft     = $total - $published;
$totalViews = array_sum(array_column($rutas, 'views_count'));
?>
<div class="stats-bar">
    <div class="stat-box"><div class="stat-box__num"><?= $total ?></div><div class="stat-box__label">Total rutas</div></div>
    <div class="stat-box"><div class="stat-box__num" style="color:#27ae60"><?= $published ?></div><div class="stat-box__label">Publicadas</div></div>
    <div class="stat-box"><div class="stat-box__num" style="color:#e67e22"><?= $draft ?></div><div class="stat-box__label">Borradores</div></div>
    <div class="stat-box"><div class="stat-box__num"><?= number_format($totalViews) ?></div><div class="stat-box__label">Visitas totales</div></div>
</div>

<a href="rutas.php?action=new" class="btn btn-primary btn-new"><i class="fas fa-plus"></i> Nueva ruta</a>

<div class="rutas-grid">
<?php if (empty($rutas)): ?>
<div style="text-align:center;padding:40px;color:#888;background:#fff;border-radius:10px">
    <i class="fas fa-route" style="font-size:3rem;margin-bottom:12px;display:block;color:#ccc"></i>
    No hay rutas aún. <a href="rutas.php?action=new" style="color:#2F5233">Crea la primera</a>.
</div>
<?php else: ?>
<?php foreach ($rutas as $r): ?>
<div class="ruta-card <?= $r['status'] ?>">
    <div class="ruta-card__info">
        <div class="ruta-card__name">
            <?= $r['is_featured'] ? '⭐ ' : '' ?>
            <?= htmlspecialchars($r['name']) ?>
            <span class="ruta-card__badge badge-<?= $r['status'] ?>"><?= $r['status'] === 'published' ? '✅ Publicada' : '📝 Borrador' ?></span>
        </div>
        <div class="ruta-card__meta">
            📍 <?= htmlspecialchars($r['province'] ?? '') ?> &nbsp;·&nbsp;
            🗓️ <?= (int)$r['duration_days'] ?> días &nbsp;·&nbsp;
            👁️ <?= number_format($r['views_count'] ?? 0) ?> visitas &nbsp;·&nbsp;
            🔗 /rutas/<?= htmlspecialchars($r['slug']) ?>
        </div>
    </div>
    <div class="ruta-card__actions">
        <a href="rutas.php?action=edit&id=<?= $r['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Editar</a>
        <a href="rutas.php?action=edit&id=<?= $r['id'] ?>&tab=items" class="btn btn-info btn-sm"><i class="fas fa-list"></i> Items</a>
        <a href="rutas.php?action=toggle&id=<?= $r['id'] ?>" class="btn <?= $r['status']==='published'?'btn-warning':'btn-success' ?> btn-sm"
           onclick="return confirm('¿Cambiar estado?')">
           <i class="fas fa-<?= $r['status']==='published'?'eye-slash':'eye' ?>"></i>
           <?= $r['status']==='published'?'Despublicar':'Publicar' ?>
        </a>
        <a href="https://rutasrurales.io/rutas/<?= htmlspecialchars($r['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-external-link-alt"></i> Ver</a>
        <a href="rutas.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-danger btn-sm"
           onclick="return confirm('¿Eliminar esta ruta y todos sus items? Esta acción no se puede deshacer.')">
           <i class="fas fa-trash"></i>
        </a>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<?php endif; ?>

</main>

<script>
// ── Tabs ──────────────────────────────────────────────────────
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name)?.classList.add('active');
    document.querySelectorAll('.tab-btn').forEach(b => {
        if (b.textContent.toLowerCase().includes(name) || b.getAttribute('onclick')?.includes(name)) {
            b.classList.add('active');
        }
    });
}

// ── Auto-slug ─────────────────────────────────────────────────
function autoSlug(input) {
    const slugField = document.getElementById('slug-field');
    const preview   = document.getElementById('slug-preview');
    if (slugField && !slugField.dataset.manual) {
        const slug = input.value
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
        slugField.value = slug;
        if (preview) preview.textContent = slug || 'tu-slug';
    }
}
document.getElementById('slug-field')?.addEventListener('input', function() {
    this.dataset.manual = '1';
    const preview = document.getElementById('slug-preview');
    if (preview) preview.textContent = this.value || 'tu-slug';
});

// ── SEO counters ──────────────────────────────────────────────
function initCounters() {
    const titleEl = document.querySelector('[name=seo_title]');
    const descEl  = document.querySelector('[name=seo_description]');
    const tc = document.getElementById('seo-title-count');
    const dc = document.getElementById('seo-desc-count');
    if (titleEl && tc) {
        const update = () => { tc.textContent = titleEl.value.length + ' / 60 caracteres'; tc.style.color = titleEl.value.length > 60 ? '#e74c3c' : '#888'; };
        titleEl.addEventListener('input', update); update();
    }
    if (descEl && dc) {
        const update = () => { dc.textContent = descEl.value.length + ' / 160 caracteres'; dc.style.color = descEl.value.length > 160 ? '#e74c3c' : '#888'; };
        descEl.addEventListener('input', update); update();
    }
}
initCounters();

// ── JSON itinerario ───────────────────────────────────────────
function formatJson() {
    const ta = document.getElementById('itinerary-json');
    if (!ta) return;
    try { ta.value = JSON.stringify(JSON.parse(ta.value), null, 2); }
    catch(e) { alert('JSON inválido: ' + e.message); }
}
function validateJson() {
    const ta = document.getElementById('itinerary-json');
    if (!ta) return;
    try { JSON.parse(ta.value); alert('✅ JSON válido'); }
    catch(e) { alert('❌ JSON inválido: ' + e.message); }
}
function generateFromItems() {
    const ta = document.getElementById('itinerary-json');
    if (!ta) return;
    
    // Obtener el ID de la ruta desde el campo oculto
    const routeId = document.querySelector('input[name="id"]')?.value;
    if (!routeId || routeId === '0') {
        alert('Guarda primero la ruta antes de generar el itinerario desde los items.');
        return;
    }
    
    if (ta.value.trim() !== '[]' && !confirm('¿Reemplazar el itinerario actual con el generado automáticamente?')) return;
    
    // Mostrar estado de carga
    ta.value = 'Generando itinerario desde los items...';
    
    fetch('rutas.php?action=get_route_items&route_id=' + routeId)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert('Error: ' + (data.error || 'No se pudieron cargar los items'));
                ta.value = '[]';
                return;
            }
            
            const items = data.items;
            const totalDias = parseInt(data.route.duration_days) || 1;
            
            if (!items.length) {
                alert('No hay items en esta ruta. Añade algunos items en la pestaña "Items" primero.');
                ta.value = '[]';
                return;
            }
            
            // Agrupar items por día
            const porDia = {};
            items.forEach(item => {
                const d = item.day_number || 1;
                if (!porDia[d]) porDia[d] = [];
                porDia[d].push(item);
            });
            
            // Generar array de días
            const itinerario = [];
            const iconosTipo = {
                'accommodation': '🏠',
                'place': '🏛️',
                'activity': '🥾',
                'event': '🎭'
            };
            const labelsTipo = {
                'accommodation': 'Alojamiento',
                'place': 'Lugar',
                'activity': 'Actividad',
                'event': 'Evento'
            };
            
            // Temáticas para títulos según composición del día
            const temas = {
                'accommodation': ['Llegada y alojamiento', 'Descanso y relax', 'Noche de alojamiento'],
                'place': ['Descubriendo la historia', 'Rincones con encanto', 'Patrimonio y cultura'],
                'activity': ['Aventura y naturaleza', 'Actividades al aire libre', 'Día de exploración'],
                'event': ['Cultura y eventos', 'Fiestas y tradiciones', 'Agenda cultural'],
                'mixed': ['Jornada completa', 'Día de inmersión', 'Explorando la provincia']
            };
            
            for (let d = 1; d <= totalDias; d++) {
                const itemsDia = porDia[d] || [];
                
                // Determinar tipos predominantes
                const tipos = {};
                itemsDia.forEach(item => {
                    const t = item.item_type || 'place';
                    tipos[t] = (tipos[t] || 0) + 1;
                });
                
                // Elegir título según composición
                let titulo = 'Día ' + d;
                let icono = '📍';
                let descParts = [];
                let itemsResumen = [];
                
                if (itemsDia.length === 0) {
                    // Día sin items → día libre
                    titulo = 'Día libre / Explora por tu cuenta';
                    icono = '🗺️';
                    descParts.push('Día sin planificar. Aprovecha para descubrir rincones por tu cuenta.');
                } else {
                    // Elegir icono predominante
                    const sorted = Object.entries(tipos).sort((a, b) => b[1] - a[1]);
                    const tipoMain = sorted[0][0];
                    icono = iconosTipo[tipoMain] || '📍';
                    
                    // Generar título según mix de tipos
                    const numTipos = Object.keys(tipos).length;
                    if (numTipos >= 3) {
                        titulo = temas['mixed'][d % 3];
                    } else if (numTipos === 1) {
                        const t = sorted[0][0];
                        const idx = Math.min(d - 1, 2);
                        titulo = temas[t] ? temas[t][idx] : 'Día ' + d;
                    } else {
                        titulo = temas['mixed'][d % 3];
                    }
                    
                    // Generar descripción con nombres de items
                    itemsDia.forEach(item => {
                        const tipoLabel = labelsTipo[item.item_type] || item.item_type;
                        const nombre = item.item_name || '(sin nombre)';
                        const loc = item.item_location ? ' (' + item.item_location + ')' : '';
                        descParts.push(tipoLabel + ': ' + nombre + loc);
                        itemsResumen.push({
                            tipo: item.item_type,
                            icono: iconosTipo[item.item_type] || '📍',
                            nombre: nombre,
                            ubicacion: item.item_location || ''
                        });
                    });
                }
                
                itinerario.push({
                    dia: d,
                    titulo: titulo,
                    descripcion: descParts.join(' · '),
                    icono: icono,
                    items_resumen: itemsResumen
                });
            }
            
            ta.value = JSON.stringify(itinerario, null, 2);
            alert('✅ Itinerario generado desde ' + items.length + ' items en ' + totalDias + ' días.');
        })
        .catch(err => {
            alert('Error al cargar los items: ' + err.message);
            ta.value = '[]';
        });
}


// ── Búsqueda de items ─────────────────────────────────────────
let searchTimeout;
function searchItems(q) {
    clearTimeout(searchTimeout);
    if (q.length < 2) { document.getElementById('search-results').style.display='none'; return; }
    searchTimeout = setTimeout(() => {
        const type = document.getElementById('item-type-sel').value;
        fetch('rutas.php?action=search_items&q=' + encodeURIComponent(q) + '&type=' + type)
            .then(r => r.json())
            .then(data => {
                const box = document.getElementById('search-results');
                if (!data.length) { box.innerHTML='<div style="padding:10px;color:#888;font-size:.85rem">Sin resultados</div>'; box.style.display='block'; return; }
                box.innerHTML = data.map(item =>
                    `<div class="search-result-item" onclick="selectItem(${item.id}, '${escHtml(item.name)}', '${escHtml(item.municipality||'')}')">
                        <span>${escHtml(item.name)}</span>
                        <span class="item-loc">${escHtml(item.municipality||'')} ${escHtml(item.province||'')}</span>
                    </div>`
                ).join('');
                box.style.display = 'block';
            });
    }, 300);
}
function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function selectItem(id, name, loc) {
    document.getElementById('item-id-hidden').value = id;
    document.getElementById('item-search').value = name;
    document.getElementById('selected-item').textContent = '✅ Seleccionado: ' + name + (loc ? ' (' + loc + ')' : '');
    document.getElementById('search-results').style.display = 'none';
}
function clearSearch() {
    document.getElementById('item-id-hidden').value = '';
    document.getElementById('item-search').value = '';
    document.getElementById('selected-item').textContent = '';
    document.getElementById('search-results').style.display = 'none';
}
function validateItem() {
    if (!document.getElementById('item-id-hidden').value) {
        alert('Selecciona un item de la lista de búsqueda primero.');
        return false;
    }
    return true;
}
// Cerrar resultados al hacer click fuera
document.addEventListener('click', e => {
    if (!e.target.closest('#item-search') && !e.target.closest('#search-results')) {
        const box = document.getElementById('search-results');
        if (box) box.style.display = 'none';
    }
});

// ── FAQs: Cargar, añadir, eliminar ────────────────────────────
const FAQS_API = '/api/route-faqs.php';

function loadFaqs(routeId) {
    const container = document.getElementById('faqs-container');
    if (!container) return;
    container.innerHTML = '<div style="text-align:center;padding:20px;color:#888"><i class="fas fa-spinner fa-spin"></i> Cargando FAQs...</div>';

    fetch(FAQS_API + '?route_id=' + routeId)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data.length) {
                container.innerHTML = `
                    <div style="text-align:center;padding:30px;color:#888;background:#f9f9f9;border-radius:8px">
                        <i class="fas fa-question-circle" style="font-size:2rem;display:block;margin-bottom:8px;color:#ccc"></i>
                        No hay FAQs personalizadas. Se usa el contenido automático.
                    </div>`;
                return;
            }
            container.innerHTML = res.data.map((faq, i) => `
                <div style="background:#f8f9fa;border-radius:8px;padding:14px 16px;margin-bottom:10px;border-left:4px solid #1abc9c">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                        <div style="flex:1">
                            <div style="font-weight:700;font-size:.9rem;color:#2c3e50;margin-bottom:4px">
                                <span style="color:#1abc9c;margin-right:6px">Q:</span> ${escHtml(faq.question)}
                            </div>
                            <div style="font-size:.85rem;color:#555;line-height:1.5">
                                <span style="color:#e67e22;margin-right:6px">A:</span> ${escHtml(faq.answer)}
                            </div>
                            <div style="font-size:.75rem;color:#999;margin-top:6px">
                                Orden: ${faq.display_order}
                            </div>
                        </div>
                        <button class="btn btn-danger btn-sm" onclick="deleteFaq(${faq.id}, ${routeId})" style="flex-shrink:0">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        })
        .catch(err => {
            container.innerHTML = '<div style="text-align:center;padding:20px;color:#e74c3c">Error al cargar FAQs</div>';
        });
}

function addFaq(routeId) {
    const question = document.getElementById('new-faq-question').value.trim();
    const answer   = document.getElementById('new-faq-answer').value.trim();
    const order    = parseInt(document.getElementById('new-faq-order').value) || 0;

    if (!question || !answer) {
        alert('Debes rellenar la pregunta y la respuesta.');
        return;
    }

    fetch(FAQS_API, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            route_id: routeId,
            question: question,
            answer: answer,
            display_order: order
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('new-faq-question').value = '';
            document.getElementById('new-faq-answer').value = '';
            document.getElementById('new-faq-order').value = '0';
            loadFaqs(routeId);
        } else {
            alert('Error: ' + (res.error || 'No se pudo guardar'));
        }
    })
    .catch(err => alert('Error de conexión'));
}

function deleteFaq(faqId, routeId) {
    if (!confirm('¿Eliminar esta FAQ?')) return;
    fetch(FAQS_API + '?id=' + faqId, { method: 'DELETE' })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                loadFaqs(routeId);
            } else {
                alert('Error: ' + (res.error || 'No se pudo eliminar'));
            }
        })
        .catch(err => alert('Error de conexión'));
}

// Cargar FAQs al entrar en la pestaña
function initFaqs() {
    <?php if (isset($r['id']) && $r['id'] > 0): ?>
    const routeId = <?= (int)$r['id'] ?>;
    
    // Detectar clicks en TODOS los botones de pestaña
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.textContent.includes('FAQs') || this.getAttribute('onclick')?.includes('faqs')) {
                setTimeout(() => loadFaqs(routeId), 200);
            }
        });
    });
    
    // Si la pestaña FAQs está activa al cargar, cargar directamente
    <?php if ($activeTab === 'faqs'): ?>
    setTimeout(() => loadFaqs(routeId), 300);
    <?php endif; ?>
    <?php endif; ?>
}

document.addEventListener('DOMContentLoaded', function() {
    initFaqs();
});
</script>

</body>
</html>
