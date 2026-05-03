<?php
/**
 * ADMIN: Mapa de Inbound Links
 * CRUD completo para gestionar keywords → URLs internas
 */
session_start();
include 'db.php';

// Control de acceso: sesión admin O acceso desde el propio dominio (igual que el resto del admin)
$is_authenticated = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
$is_internal      = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'rutasrurales.io') !== false;
// También permitir acceso directo en local/dev (sin referer) por comodidad
$is_local         = ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1');
if (!$is_authenticated && !$is_internal && !$is_local) {
    // Sin sesión, sin referer del dominio: redirigir a login
    header("Location: https://rutasrurales.io/admin_tablas/index.php");
    exit;
}

$msg = '';
$error = '';

// ─── ACCIONES POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $keyword    = trim($_POST['keyword'] ?? '');
        $url        = trim($_POST['url'] ?? '');
        $link_title = trim($_POST['link_title'] ?? '');
        $is_active  = isset($_POST['is_active']) ? 1 : 0;
        $priority   = intval($_POST['priority'] ?? 10);
        $mercado    = in_array($_POST['mercado'] ?? 'es', ['es','en','fr','de','zh']) ? $_POST['mercado'] : 'es';

        if (!$keyword || !$url || !$link_title) {
            $error = 'Keyword, URL y Título son obligatorios.';
        } else {
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO inbound_links (keyword, url, link_title, is_active, priority, mercado) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$keyword, $url, $link_title, $is_active, $priority, $mercado]);
                $msg = '✅ Keyword <strong>' . htmlspecialchars($keyword) . '</strong> creada correctamente.';
            } else {
                $id = intval($_POST['id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE inbound_links SET keyword=?, url=?, link_title=?, is_active=?, priority=?, mercado=? WHERE id=?");
                $stmt->execute([$keyword, $url, $link_title, $is_active, $priority, $mercado, $id]);
                $msg = '✅ Keyword actualizada correctamente.';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM inbound_links WHERE id=?")->execute([$id]);
        $msg = '✅ Keyword eliminada.';
    } elseif ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE inbound_links SET is_active = NOT is_active WHERE id=?")->execute([$id]);
        $msg = '✅ Estado actualizado.';
    } elseif ($action === 'regenerar_eventos') {
        require_once __DIR__ . '/../api/inbound_links_helper.php';
        $res = regenerarInboundLinksTodos($pdo, 'cultural_events');
        $msg = "✅ Regeneración cultural_events: {$res['procesados']} procesados, {$res['errores']} errores.";
    } elseif ($action === 'regenerar_alojamientos') {
        require_once __DIR__ . '/../api/inbound_links_helper.php';
        $res = regenerarInboundLinksTodos($pdo, 'accommodations');
        $msg = "✅ Regeneración accommodations: {$res['procesados']} procesados, {$res['errores']} errores.";
    }
}

// ─── CARGAR TODOS LOS LINKS ────────────────────────────────────────────────────
$links = $pdo->query("SELECT * FROM inbound_links ORDER BY priority ASC, keyword ASC")->fetchAll(PDO::FETCH_ASSOC);

// ─── EDIT: cargar link para editar ────────────────────────────────────────────
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM inbound_links WHERE id=?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbound Links – Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f5f7fa; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 24px 16px; }
        h1 { color: #2F5233; margin-bottom: 4px; }
        .subtitle { color: #666; font-size: 0.9rem; margin-bottom: 24px; }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); padding: 24px; margin-bottom: 24px; }
        .card h2 { font-size: 1.1rem; color: #2F5233; margin-bottom: 16px; border-bottom: 2px solid #81C784; padding-bottom: 8px; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 700; color: #555; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.4px; }
        .form-group input, .form-group select { width: 100%; padding: 9px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #81C784; box-shadow: 0 0 0 3px rgba(129,199,132,0.2); }
        .form-group.full { grid-column: 1 / -1; }
        .checkbox-label { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; margin-top: 24px; }
        .checkbox-label input[type=checkbox] { width: 16px; height: 16px; }

        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; border: none; font-size: 0.875rem; transition: all 0.2s; }
        .btn-green  { background: #2F5233; color: #fff; }
        .btn-green:hover  { background: #3d6b42; }
        .btn-red    { background: #e53935; color: #fff; }
        .btn-red:hover    { background: #c62828; }
        .btn-orange { background: #F9A825; color: #333; }
        .btn-orange:hover { background: #f0a000; }
        .btn-blue   { background: #1565C0; color: #fff; }
        .btn-blue:hover   { background: #0d47a1; }
        .btn-sm { padding: 5px 12px; font-size: 0.78rem; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
        .alert-success { background: #e8f5e9; border-left: 4px solid #4caf50; color: #2e7d32; }
        .alert-error   { background: #ffebee; border-left: 4px solid #e53935; color: #c62828; }

        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th { background: #f0f4f0; color: #2F5233; padding: 10px 12px; text-align: left; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.4px; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        tr:hover td { background: #fafffe; }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
        .badge-on  { background: #e8f5e9; color: #2e7d32; }
        .badge-off { background: #fbe9e7; color: #bf360c; }

        .priority-badge { display: inline-block; background: #e3f2fd; color: #1565C0; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 700; }

        .actions-top { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
        .keyword-cell { font-weight: 700; color: #2F5233; }
        .url-cell { font-size: 0.8rem; color: #555; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .title-cell { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .td-actions { display: flex; gap: 6px; }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            table { font-size: 0.8rem; }
            .actions-top { flex-direction: column; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <h1>🔗 Mapa de Inbound Links</h1>
    <p class="subtitle">Keywords → URLs internas. Los links se insertan <strong>en el momento de guardar</strong> el contenido, sin impacto en la velocidad de las páginas.</p>

    <?php if ($msg): ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- ── Acciones masivas ─────────────────────────────────────────────────── -->
    <div class="actions-top">
        <form method="post" onsubmit="return confirm('¿Regenerar description_linked para TODOS los eventos? Puede tardar varios segundos.')">
            <input type="hidden" name="action" value="regenerar_eventos">
            <button type="submit" class="btn btn-orange">⚡ Regenerar todos los eventos</button>
        </form>
        <form method="post" onsubmit="return confirm('¿Regenerar description_linked para TODOS los alojamientos?')">
            <input type="hidden" name="action" value="regenerar_alojamientos">
            <button type="submit" class="btn btn-orange">⚡ Regenerar todos los alojamientos</button>
        </form>
    </div>

    <!-- ── Formulario crear / editar ───────────────────────────────────────── -->
    <div class="card">
        <h2><?php echo $edit ? '✏️ Editar Keyword' : '➕ Nueva Keyword'; ?></h2>
        <form method="post">
            <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'create'; ?>">
            <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?php echo $edit['id']; ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label>Keyword (Palabra)</label>
                    <input type="text" name="keyword" required placeholder="ej: Mercado Castellano"
                           value="<?php echo htmlspecialchars($edit['keyword'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>URL de Destino</label>
                    <input type="text" name="url" required placeholder="ej: /mercados/castellano"
                           value="<?php echo htmlspecialchars($edit['url'] ?? ''); ?>">
                </div>
                <div class="form-group full">
                    <label>Título del Enlace (SEO – atributo title)</label>
                    <input type="text" name="link_title" required placeholder="ej: Todo sobre el Mercado Castellano"
                           value="<?php echo htmlspecialchars($edit['link_title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Prioridad (menor = primero)</label>
                    <input type="number" name="priority" min="1" max="100" value="<?php echo intval($edit['priority'] ?? 10); ?>">
                </div>
                <div class="form-group">
                    <label>Mercado</label>
                    <select name="mercado">
                        <?php foreach (['es'=>'🇪🇸 Castellano','en'=>'🇬🇧 English','fr'=>'🇫🇷 Français','de'=>'🇩🇪 Deutsch','zh'=>'🇨🇳 中文'] as $v => $l): ?>
                        <option value="<?php echo $v; ?>" <?php echo ($edit['mercado'] ?? 'es') === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo ($edit['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        Activo
                    </label>
                </div>
            </div>

            <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-green"><?php echo $edit ? '💾 Guardar cambios' : '➕ Crear Keyword'; ?></button>
                <?php if ($edit): ?>
                <a href="inbound_links.php" class="btn" style="background:#eee;color:#333;">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ── Tabla de keywords ───────────────────────────────────────────────── -->
    <div class="card">
        <h2>📋 Keywords configuradas (<?php echo count($links); ?>)</h2>
        <?php if (empty($links)): ?>
        <p style="color:#999;text-align:center;padding:32px;">Aún no hay keywords. Añade la primera arriba.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Prio</th>
                    <th>Keyword</th>
                    <th>URL Destino</th>
                    <th>Título SEO</th>
                    <th>Mercado</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($links as $link): ?>
            <tr>
                <td><span class="priority-badge"><?php echo intval($link['priority']); ?></span></td>
                <td class="keyword-cell"><?php echo htmlspecialchars($link['keyword']); ?></td>
                <td class="url-cell" title="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['url']); ?></td>
                <td class="title-cell" title="<?php echo htmlspecialchars($link['link_title']); ?>"><?php echo htmlspecialchars($link['link_title']); ?></td>
                <td><?php echo htmlspecialchars($link['mercado'] ?? 'es'); ?></td>
                <td>
                    <span class="badge <?php echo $link['is_active'] ? 'badge-on' : 'badge-off'; ?>">
                        <?php echo $link['is_active'] ? 'Activo' : 'Inactivo'; ?>
                    </span>
                </td>
                <td>
                    <div class="td-actions">
                        <a href="?edit=<?php echo $link['id']; ?>" class="btn btn-blue btn-sm">✏️</a>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                            <button type="submit" class="btn btn-orange btn-sm"><?php echo $link['is_active'] ? '⏸' : '▶'; ?></button>
                        </form>
                        <form method="post" style="margin:0;" onsubmit="return confirm('¿Eliminar esta keyword?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                            <button type="submit" class="btn btn-red btn-sm">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Nota informativa ────────────────────────────────────────────────── -->
    <div class="card" style="background:#e8f5e9;border-left:4px solid #4caf50;">
        <h2 style="border-bottom-color:#a5d6a7;">ℹ️ ¿Cómo funciona?</h2>
        <ul style="line-height:2;font-size:0.9rem;color:#555;">
            <li>Al <strong>guardar</strong> un evento o alojamiento en el admin, el texto de descripción se procesa y los links se insertan automáticamente en el campo <code>description_linked</code>.</li>
            <li>Las páginas modulares sirven <strong>directamente</strong> el HTML pre-generado → <strong>0 ms de overhead</strong> para el visitante.</li>
            <li>Cada keyword solo se enlaza <strong>una vez</strong> por página (la primera ocurrencia).</li>
            <li>Los links nunca se generan dentro de etiquetas <code>&lt;a&gt;</code> existentes.</li>
            <li>Usa <strong>"Regenerar todos"</strong> para aplicar nuevas keywords al contenido ya guardado anteriormente.</li>
        </ul>
    </div>
</div>

</body>
</html>
