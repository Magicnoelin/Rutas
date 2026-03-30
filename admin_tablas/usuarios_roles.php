<?php
/**
 * Admin: Gestión de Roles de Usuarios
 * Permite ver y editar los roles asignados a cada usuario.
 */
include 'db.php';

$mensaje = '';
$tipoMensaje = '';

// ── Procesar formulario de cambio de roles ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $userId    = intval($_POST['user_id']);
    $rolSlugs  = $_POST['roles'] ?? [];

    try {
        // Obtener IDs de los roles seleccionados
        if (!empty($rolSlugs)) {
            $placeholders = implode(',', array_fill(0, count($rolSlugs), '?'));
            $stmtIds = $pdo->prepare("SELECT id FROM roles WHERE slug IN ($placeholders)");
            $stmtIds->execute($rolSlugs);
            $roleIds = array_column($stmtIds->fetchAll(), 'id');
        } else {
            $roleIds = [];
        }

        // Borrar roles actuales del usuario
        $pdo->prepare("DELETE FROM role_user WHERE user_id = ?")->execute([$userId]);

        // Insertar nuevos roles
        if (!empty($roleIds)) {
            $stmtIns = $pdo->prepare("INSERT IGNORE INTO role_user (user_id, role_id) VALUES (?, ?)");
            foreach ($roleIds as $rid) {
                $stmtIns->execute([$userId, $rid]);
            }
        }

        // Sincronizar user_type (compatibilidad legacy)
        $stmtFirst = $pdo->prepare("
            SELECT r.slug FROM roles r
            INNER JOIN role_user ru ON ru.role_id = r.id
            WHERE ru.user_id = ? ORDER BY r.id LIMIT 1
        ");
        $stmtFirst->execute([$userId]);
        $first = $stmtFirst->fetch();
        $newType = $first ? $first['slug'] : 'turista';
        $pdo->prepare("UPDATE users SET user_type = ? WHERE id = ?")->execute([$newType, $userId]);

        $mensaje     = "Roles del usuario #$userId actualizados correctamente.";
        $tipoMensaje = 'success';

    } catch (PDOException $e) {
        $mensaje     = "Error al actualizar roles: " . $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

// ── Cargar todos los roles disponibles ───────────────────────────────────
$todosRoles = [];
try {
    $todosRoles = $pdo->query("SELECT id, nombre, slug FROM roles ORDER BY id")->fetchAll();
} catch (PDOException $e) {
    $mensaje     = "⚠ Las tablas de roles no existen aún. Ejecuta primero <code>api/crear_sistema_roles.sql</code>.";
    $tipoMensaje = 'warning';
}

// ── Cargar usuarios con sus roles ────────────────────────────────────────
$usuarios = [];
if (!empty($todosRoles)) {
    try {
        $usuarios = $pdo->query("
            SELECT 
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.user_type,
                u.status,
                GROUP_CONCAT(r.slug ORDER BY r.id SEPARATOR ',') AS roles_slugs,
                GROUP_CONCAT(r.nombre ORDER BY r.id SEPARATOR ', ') AS roles_nombres
            FROM users u
            LEFT JOIN role_user ru ON ru.user_id = u.id
            LEFT JOIN roles r ON r.id = ru.role_id
            GROUP BY u.id
            ORDER BY u.id DESC
        ")->fetchAll();
    } catch (PDOException $e) {
        $mensaje     = "Error al cargar usuarios: " . $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

// ── Filtro de búsqueda ────────────────────────────────────────────────────
$busqueda = trim($_GET['q'] ?? '');
if ($busqueda && !empty($usuarios)) {
    $usuarios = array_filter($usuarios, function($u) use ($busqueda) {
        return stripos($u['email'], $busqueda) !== false
            || stripos($u['first_name'] . ' ' . $u['last_name'], $busqueda) !== false
            || stripos($u['roles_nombres'] ?? '', $busqueda) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Roles - Rutas Rurales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .badge-rol { font-size: .75rem; margin: 1px; }
        .table td { vertical-align: middle; }
        .modal-body .form-check { padding: .4rem 0; }
        /* Colores personalizados para roles */
        .rol-turista            { background-color: #0dcaf0 !important; color: #000 !important; }
        .rol-senderista         { background-color: #198754 !important; }
        .rol-alojamiento        { background-color: #0d6efd !important; }
        .rol-restaurante        { background-color: #fd7e14 !important; }
        .rol-bodega             { background-color: #6f42c1 !important; }
        .rol-organizador_eventos{ background-color: #d63384 !important; }
        .rol-actividad_cultural { background-color: #20c997 !important; color: #000 !important; }
        .rol-ayuntamiento       { background-color: #0a58ca !important; }
        .rol-organismo_oficial  { background-color: #084298 !important; }
        .rol-asociacion         { background-color: #6610f2 !important; }
        .rol-fotografo          { background-color: #495057 !important; }
        .rol-creador_contenido  { background-color: #e83e8c !important; }
        .rol-colaborador        { background-color: #6c757d !important; }
        .rol-guia_turistico     { background-color: #ffc107 !important; color: #000 !important; }
        .rol-artesano           { background-color: #a0522d !important; }
        .rol-agricultor_ganadero{ background-color: #5a6e2a !important; }
        .rol-empresa_actividades{ background-color: #17a2b8 !important; color: #000 !important; }
        .rol-transporte_turistico{ background-color: #343a40 !important; }
        .rol-comercio_local     { background-color: #e67e22 !important; }
        .rol-admin              { background-color: #dc3545 !important; }
        /* Grupos en el modal */
        .rol-group-title { font-size: .7rem; text-transform: uppercase; color: #6c757d; 
                           padding: .5rem 0 .2rem; border-top: 1px solid #dee2e6; margin-top: .5rem; }
    </style>
</head>
<body class="bg-light p-4">
<div class="container-fluid">

    <!-- Cabecera -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-shield-lock text-primary"></i> Gestión de Roles de Usuarios</h2>
        <a href="usuarios_index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver a Usuarios
        </a>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
        <?= $mensaje ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (empty($todosRoles)): ?>
    <div class="alert alert-warning">
        <h5><i class="bi bi-exclamation-triangle"></i> Sistema de Roles no instalado</h5>
        <p>Las tablas <code>roles</code>, <code>role_user</code>, <code>profile_alojamientos</code> y <code>profile_turistas</code> no existen todavía.</p>
        <p><strong>Pasos para instalar:</strong></p>
        <ol>
            <li>Abre <strong>phpMyAdmin</strong> y selecciona la base de datos <code>u412199647_Rutas</code></li>
            <li>Ve a la pestaña <strong>SQL</strong></li>
            <li>Copia y ejecuta el contenido del archivo <code>api/crear_sistema_roles.sql</code></li>
        </ol>
    </div>
    <?php else: ?>

    <!-- Resumen de roles -->
    <div class="row mb-4">
        <?php foreach ($todosRoles as $rol): ?>
        <?php
            $count = 0;
            foreach ($usuarios as $u) {
                $slugs = explode(',', $u['roles_slugs'] ?? '');
                if (in_array($rol['slug'], $slugs)) $count++;
            }
        ?>
        <div class="col-xl-2 col-md-3 col-sm-4 mb-2">
            <div class="card text-center h-100">
                <div class="card-body py-2 px-1">
                    <div class="fs-4 fw-bold"><?= $count ?></div>
                    <span class="badge rol-<?= $rol['slug'] ?> badge-rol d-block text-truncate">
                        <?= htmlspecialchars($rol['nombre']) ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Buscador -->
    <form method="GET" class="mb-3 d-flex gap-2">
        <input type="text" name="q" class="form-control" placeholder="Buscar por email, nombre o rol..." value="<?= htmlspecialchars($busqueda) ?>">
        <button class="btn btn-primary"><i class="bi bi-search"></i></button>
        <?php if ($busqueda): ?>
        <a href="usuarios_roles.php" class="btn btn-outline-secondary">Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- Tabla de usuarios -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>user_type (legacy)</th>
                        <th>Roles Asignados</th>
                        <th>Estado</th>
                        <th class="text-center">Editar Roles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <?php
                        $slugsAsignados = $u['roles_slugs'] ? explode(',', $u['roles_slugs']) : [];
                        $status = $u['status'] ?? 'inactive';
                        $statusClass = ['active'=>'bg-success','inactive'=>'bg-secondary','suspended'=>'bg-danger','deleted'=>'bg-dark'][$status] ?? 'bg-secondary';
                    ?>
                    <tr>
                        <td class="text-muted fw-bold"><?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($u['user_type'] ?? '-') ?></span></td>
                        <td>
                            <?php if (empty($slugsAsignados) || $slugsAsignados === ['']): ?>
                                <span class="text-muted small">Sin roles</span>
                            <?php else: ?>
                                <?php
                                foreach ($slugsAsignados as $slug):
                                    $nombre = '';
                                    foreach ($todosRoles as $r) { if ($r['slug'] === $slug) { $nombre = $r['nombre']; break; } }
                                ?>
                                <span class="badge rol-<?= htmlspecialchars($slug) ?> badge-rol"><?= htmlspecialchars($nombre ?: $slug) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $statusClass ?>"><?= ucfirst($status) ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalRoles"
                                    data-userid="<?= $u['id'] ?>"
                                    data-username="<?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>"
                                    data-roles="<?= htmlspecialchars($u['roles_slugs'] ?? '') ?>">
                                <i class="bi bi-pencil-square"></i> Roles
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($usuarios)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron usuarios.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- Modal Editar Roles -->
<div class="modal fade" id="modalRoles" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-shield-lock"></i> Editar Roles de <span id="modalUserName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="modalUserId">
                    <p class="text-muted small">Selecciona uno o varios roles para este usuario:</p>
                    <?php
                    // Grupos para organizar visualmente el modal
                    $grupos = [
                        'Visitantes'              => ['turista','senderista'],
                        'Hostelería'              => ['alojamiento','restaurante','bodega'],
                        'Eventos y Cultura'       => ['organizador_eventos','actividad_cultural'],
                        'Instituciones'           => ['ayuntamiento','organismo_oficial','asociacion'],
                        'Creadores'               => ['fotografo','creador_contenido','colaborador'],
                        'Servicios'               => ['guia_turistico','artesano','agricultor_ganadero','empresa_actividades','transporte_turistico','comercio_local'],
                        'Sistema'                 => ['admin'],
                    ];
                    // Indexar roles por slug para acceso rápido
                    $rolesBySlug = [];
                    foreach ($todosRoles as $r) { $rolesBySlug[$r['slug']] = $r; }

                    foreach ($grupos as $grupoNombre => $slugsGrupo):
                        $rolesEnGrupo = array_filter($slugsGrupo, fn($s) => isset($rolesBySlug[$s]));
                        if (empty($rolesEnGrupo)) continue;
                    ?>
                    <div class="rol-group-title"><?= $grupoNombre ?></div>
                    <?php foreach ($rolesEnGrupo as $slug):
                        $rol = $rolesBySlug[$slug];
                    ?>
                    <div class="form-check">
                        <input class="form-check-input rol-check"
                               type="checkbox"
                               name="roles[]"
                               value="<?= $rol['slug'] ?>"
                               id="rol_<?= $rol['slug'] ?>">
                        <label class="form-check-label" for="rol_<?= $rol['slug'] ?>">
                            <span class="badge rol-<?= $rol['slug'] ?>"><?= htmlspecialchars($rol['nombre']) ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                    <div class="alert alert-info mt-3 small mb-0">
                        <i class="bi bi-info-circle"></i>
                        El campo <code>user_type</code> se sincronizará automáticamente con el primer rol asignado (compatibilidad con el sistema anterior).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Roles</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('modalRoles').addEventListener('show.bs.modal', function(event) {
    const btn      = event.relatedTarget;
    const userId   = btn.getAttribute('data-userid');
    const userName = btn.getAttribute('data-username');
    const rolesStr = btn.getAttribute('data-roles') || '';
    const roles    = rolesStr ? rolesStr.split(',') : [];

    document.getElementById('modalUserId').value   = userId;
    document.getElementById('modalUserName').textContent = userName;

    // Marcar los checkboxes correspondientes
    document.querySelectorAll('.rol-check').forEach(function(cb) {
        cb.checked = roles.includes(cb.value);
    });
});
</script>
</body>
</html>
