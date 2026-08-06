<?php
include 'db.php';

// Vincular la conexión existente desde db.php ($conn o $pdo)
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

if (!isset($pdo)) {
    $charset = $charset ?? 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}

// Inicialización de variables
$mensaje = '';
$action = $_GET['action'] ?? 'list';
$edit_data = null;

// -----------------------------------------------------------------------------
// PROCESAR FORMULARIO (CREAR O ACTUALIZAR)
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $user_id       = (int)$_POST['user_id'];
    $resource_type = $_POST['resource_type'];
    $resource_id   = (int)$_POST['resource_id'];
    $role          = $_POST['role'];
    $status        = $_POST['status'];
    $permissions   = !empty($_POST['permissions']) ? $_POST['permissions'] : null;
    $notes         = !empty($_POST['notes']) ? $_POST['notes'] : null;
    $validated_by  = !empty($_POST['validated_by']) ? (int)$_POST['validated_by'] : null;

    if ($id) {
        // ACTUALIZAR (UPDATE)
        $sql = "UPDATE user_resources 
                SET user_id = :user_id, 
                    resource_type = :resource_type, 
                    resource_id = :resource_id, 
                    role = :role, 
                    status = :status, 
                    permissions = :permissions, 
                    notes = :notes, 
                    validated_at = " . ($status === 'active' ? "IFNULL(validated_at, NOW())" : "NULL") . ", 
                    validated_by = :validated_by 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id'       => $user_id,
            ':resource_type' => $resource_type,
            ':resource_id'   => $resource_id,
            ':role'          => $role,
            ':status'        => $status,
            ':permissions'   => $permissions,
            ':notes'         => $notes,
            ':validated_by'  => $validated_by,
            ':id'            => $id
        ]);
        $mensaje = "Registro #$id actualizado correctamente.";
    } else {
        // CREAR (INSERT)
        $sql = "INSERT INTO user_resources 
                (user_id, resource_type, resource_id, role, status, permissions, notes, validated_at, validated_by) 
                VALUES 
                (:user_id, :resource_type, :resource_id, :role, :status, :permissions, :notes, " . ($status === 'active' ? "NOW()" : "NULL") . ", :validated_by)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id'       => $user_id,
            ':resource_type' => $resource_type,
            ':resource_id'   => $resource_id,
            ':role'          => $role,
            ':status'        => $status,
            ':permissions'   => $permissions,
            ':notes'         => $notes,
            ':validated_by'  => $validated_by
        ]);
        $mensaje = "Nueva vinculación creada correctamente con ID #" . $pdo->lastInsertId();
    }
    $action = 'list';
}

// ELIMINAR REGISTRO
if ($action === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM user_resources WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['id']]);
    $mensaje = "Registro #" . (int)$_GET['id'] . " eliminado.";
    $action = 'list';
}

// OBTENER DATOS PARA EDICIÓN
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM user_resources WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['id']]);
    $edit_data = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de User Resources</title>
    <style>
        body { font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 1100px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #2c3e50; }
        .alert { padding: 10px 15px; background-color: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; color: #fff; }
        .bg-active { background-color: #28a745; }
        .bg-pending { background-color: #ffc107; color: #000; }
        .bg-suspended { background-color: #dc3545; }
        .btn { padding: 6px 12px; text-decoration: none; border-radius: 4px; color: #fff; display: inline-block; font-size: 13px; border: none; cursor: pointer; }
        .btn-primary { background-color: #007bff; }
        .btn-success { background-color: #28a745; }
        .btn-danger { background-color: #dc3545; }
        .btn-secondary { background-color: #6c757d; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 70px; font-family: monospace; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
    </style>
</head>
<body>

<div class="container">
    <h2>Gestión de Recursos por Usuario (`user_resources`)</h2>

    <?php if (!empty($mensaje)): ?>
        <div class="alert"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($action === 'new' || $action === 'edit'): ?>
        <h3><?= $action === 'edit' ? 'Editar Vinculación #' . htmlspecialchars($edit_data['id'] ?? '') : 'Nueva Vinculación' ?></h3>
        
        <form method="POST" action="user_resources.php">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>ID Usuario (user_id):</label>
                    <input type="number" name="user_id" value="<?= $edit_data['user_id'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Tipo de Recurso (resource_type):</label>
                    <select name="resource_type" required>
                        <?php 
                        $types = ['accommodation', 'place', 'activity', 'event'];
                        $current_type = $edit_data['resource_type'] ?? 'accommodation';
                        foreach ($types as $t): 
                        ?>
                            <option value="<?= $t ?>" <?= $current_type === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ID Recurso (resource_id):</label>
                    <input type="number" name="resource_id" value="<?= $edit_data['resource_id'] ?? '' ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Rol (role):</label>
                    <select name="role" required>
                        <?php 
                        $roles = ['owner', 'manager', 'collaborator'];
                        $current_role = $edit_data['role'] ?? 'owner';
                        foreach ($roles as $r): 
                        ?>
                            <option value="<?= $r ?>" <?= $current_role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado (status):</label>
                    <select name="status" required>
                        <?php 
                        $statuses = ['pending', 'active', 'suspended'];
                        $current_status = $edit_data['status'] ?? 'pending';
                        foreach ($statuses as $s): 
                        ?>
                            <option value="<?= $s ?>" <?= $current_status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Validado por ID Admin (validated_by):</label>
                    <input type="number" name="validated_by" value="<?= $edit_data['validated_by'] ?? '1' ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Permisos JSON (permissions):</label>
                <textarea name="permissions"><?= htmlspecialchars($edit_data['permissions'] ?? '{"can_edit": true, "can_delete": false}') ?></textarea>
            </div>

            <div class="form-group">
                <label>Notas internas (notes):</label>
                <textarea name="notes"><?= htmlspecialchars($edit_data['notes'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-success">
                <?= $action === 'edit' ? 'Guardar Cambios' : 'Crear Vinculación' ?>
            </button>
            <a href="user_resources.php" class="btn btn-secondary">Cancelar</a>
        </form>

    <?php else: ?>

        <div style="margin-bottom: 15px; display: flex; justify-content: space-between;">
            <a href="user_resources.php?action=new" class="btn btn-primary">+ Nueva Vinculación</a>
            <form method="GET" action="user_resources.php" style="display: flex; gap: 10px;">
                <input type="number" name="filter_user" placeholder="Filtrar por ID Usuario" value="<?= htmlspecialchars($_GET['filter_user'] ?? '') ?>" style="width: 200px;">
                <button type="submit" class="btn btn-secondary">Filtrar</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Tipo</th>
                    <th>Resource ID</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Permisos</th>
                    <th>Validación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $filter_user = $_GET['filter_user'] ?? null;
                if ($filter_user) {
                    $stmt = $pdo->prepare("SELECT * FROM user_resources WHERE user_id = :user_id ORDER BY id DESC");
                    $stmt->execute([':user_id' => (int)$filter_user]);
                } else {
                    $stmt = $pdo->query("SELECT * FROM user_resources ORDER BY id DESC LIMIT 50");
                }
                $rows = $stmt->fetchAll();

                if (empty($rows)): ?>
                    <tr><td colspan="9" style="text-align: center;">No se encontraron registros.</td></tr>
                <?php else: 
                    foreach ($rows as $row): 
                        $statusClass = 'bg-' . $row['status'];
                ?>
                    <tr>
                        <td><strong><?= $row['id'] ?></strong></td>
                        <td><?= $row['user_id'] ?></td>
                        <td><?= htmlspecialchars($row['resource_type']) ?></td>
                        <td><?= $row['resource_id'] ?></td>
                        <td><?= htmlspecialchars($row['role']) ?></td>
                        <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                        <td><small><code><?= htmlspecialchars($row['permissions'] ?? 'NULL') ?></code></small></td>
                        <td>
                            <small>
                                <strong>Fecha:</strong> <?= $row['validated_at'] ?? 'Sin validar' ?><br>
                                <strong>Por:</strong> <?= $row['validated_by'] ?? '-' ?>
                            </small>
                        </td>
                        <td>
                            <a href="user_resources.php?action=edit&id=<?= $row['id'] ?>" class="btn btn-primary">Editar</a>
                            <a href="user_resources.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('¿Seguro que deseas eliminar esta vinculación?');">Borrar</a>
                        </td>
                    </tr>
                <?php 
                    endforeach;
                endif; 
                ?>
            </tbody>
        </table>

    <?php endif; ?>
</div>

</body>
</html>