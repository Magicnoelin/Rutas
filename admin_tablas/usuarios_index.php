<?php
include 'db.php';
// Consulta a la tabla 'users'
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Rutas Rurales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people text-primary"></i> Gestión de Usuarios</h2>
            <a href="usuarios_nuevo.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nuevo Usuario</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Suscripción</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td class="text-muted fw-bold"><?= $u['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($u['nickname'] ?? 'Sin alias') ?></strong>
                            </td>
                            <td><?= htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge bg-info text-dark uppercase small"><?= $u['user_type'] ?></span>
                            </td>
                            <td>
                                <?php $sub_level = $u['subscription_level'] ?? 'basic'; ?>
                                <span class="badge <?= $sub_level === 'premium' ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                    <?= ucfirst($sub_level) ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $status = $u['status'] ?? 'inactive';
                                $status_class = [
                                    'active'    => 'bg-success',
                                    'inactive'  => 'bg-secondary',
                                    'suspended' => 'bg-danger',
                                    'deleted'   => 'bg-dark'
                                ][$status] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $status_class ?>"><?= ucfirst($status) ?></span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="usuarios_editar.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmarEliminar(<?= $u['id'] ?>)" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No se encontraron usuarios en la base de datos.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function confirmarEliminar(id) {
        if(confirm('¿Seguro que deseas eliminar este usuario?')) {
            window.location.href = 'usuarios_eliminar.php?id=' + id;
        }
    }
    </script>
</body>
</html>