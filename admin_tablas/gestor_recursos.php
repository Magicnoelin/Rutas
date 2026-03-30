<?php
// 1. Configuración de la base de datos integrada
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = 'Rutas5Rurales7$';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

// 2. PROCESAR ACCIONES (Vincular / Desvincular)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'vincular') {
        $sql = "INSERT INTO user_resources (user_id, resource_id, resource_type, role, status) VALUES (?, ?, ?, 'owner', 'active')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['user_id'], $_POST['res_id'], $_POST['res_type']]);
        $msg = "✅ Vinculación creada correctamente.";
    } 
    if ($_POST['action'] === 'eliminar') {
        $sql = "DELETE FROM user_resources WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['rel_id']]);
        $msg = "🗑️ Vinculación eliminada con éxito.";
    }
}

$tipo_actual = $_GET['tipo'] ?? 'accommodation';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestión de Recursos</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 30px; background: #f4f7f6; color: #333; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; border-top: 4px solid #1a73e8; }
        h2 { margin-top: 0; color: #1a73e8; font-size: 1.2rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f8f9fa; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; }
        .btn { padding: 8px 15px; border-radius: 4px; border: none; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .btn-add { background: #28a745; color: white; }
        .btn-add:hover { background: #218838; }
        .btn-del { background: #dc3545; color: white; font-size: 11px; }
        .btn-del:hover { background: #c82333; }
        select { padding: 8px; border-radius: 4px; border: 1px solid #ddd; outline: none; }
        .nav-tabs { margin-bottom: 25px; display: flex; gap: 10px; }
        .nav-tabs a { text-decoration: none; padding: 12px 25px; background: #e0e0e0; color: #555; border-radius: 5px; font-weight: 600; }
        .nav-tabs a.active { background: #1a73e8; color: white; }
        .msg { padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px; border-left: 5px solid #28a745; }
        .empty-state { color: #999; font-style: italic; }
    </style>
</head>
<body>

    <?php if(isset($msg)) echo "<div class='msg'>$msg</div>"; ?>

    <div class="nav-tabs">
        <a href="?tipo=accommodation" class="<?= $tipo_actual=='accommodation'?'active':'' ?>">🏠 Alojamientos</a>
        <a href="?tipo=place" class="<?= $tipo_actual=='place'?'active':'' ?>">📍 Lugares</a>
        <a href="?tipo=activity" class="<?= $tipo_actual=='activity'?'active':'' ?>">🎯 Actividades</a>
    </div>

    <div class="card">
        <h2>👥 Usuarios sin "<?= $tipo_actual ?>" asignado</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email / Usuario</th>
                    <th>Acción: Asignar Recurso Disponible</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sqlU = "SELECT u.id, u.email FROM users u 
                         WHERE u.id NOT IN (SELECT user_id FROM user_resources WHERE resource_type = ?)";
                $stmtU = $pdo->prepare($sqlU);
                $stmtU->execute([$tipo_actual]);
                
        // Definimos manualmente el nombre de las tablas según el tipo
$tablas_mapeo = [
    'accommodation' => 'accommodations',
    'place'         => 'places_of_interest',
    'activity'      => 'activities'    
];

$tabla_res = $tablas_mapeo[$tipo_actual] ?? $tipo_actual . 's';
                $recursosLibres = $pdo->prepare("SELECT r.id, r.name FROM $tabla_res r 
                                                LEFT JOIN user_resources ur ON r.id = ur.resource_id AND ur.resource_type = ?
                                                WHERE ur.id IS NULL");
                $recursosLibres->execute([$tipo_actual]);
                $libres = $recursosLibres->fetchAll();

                if ($stmtU->rowCount() > 0):
                    while ($u = $stmtU->fetch()): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><strong><?= $u['email'] ?></strong></td>
                            <td>
                                <?php if(count($libres) > 0): ?>
                                <form method="POST" style="display:flex; gap:8px;">
                                    <input type="hidden" name="action" value="vincular">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="res_type" value="<?= $tipo_actual ?>">
                                    <select name="res_id" required>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach($libres as $l): ?>
                                            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-add">Vincular</button>
                                </form>
                                <?php else: ?>
                                    <span class="empty-state">No hay <?= $tipo_actual ?>s libres para asignar.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; 
                else: ?>
                    <tr><td colspan="3" class="empty-state">Todos los usuarios tienen al menos un recurso de este tipo.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>⚠️ <?= ucfirst($tipo_actual) ?>s sin Dueño</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre del Recurso</th>
                    <th>Estado Actual</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($libres) > 0): ?>
                    <?php foreach($libres as $l): ?>
                        <tr>
                            <td><?= $l['id'] ?></td>
                            <td><?= htmlspecialchars($l['name']) ?></td>
                            <td><span style="color:#e67e22; font-weight:bold;">Suelto / Sin Usuario</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="empty-state">No hay recursos huérfanos. Todo está asignado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>✅ Listado General de Vinculaciones</h2>
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Recurso Asignado</th>
                    <th>Rol</th>
                    <th>Gestión</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sqlList = "SELECT ur.id as rel_id, u.email, r.name as res_name, ur.role 
                            FROM user_resources ur
                            JOIN users u ON ur.user_id = u.id
                            JOIN $tabla_res r ON ur.resource_id = r.id
                            WHERE ur.resource_type = ?
                            ORDER BY u.email ASC";
                $stmtList = $pdo->prepare($sqlList);
                $stmtList->execute([$tipo_actual]);
                if ($stmtList->rowCount() > 0):
                    while ($row = $stmtList->fetch()): ?>
                        <tr>
                            <td><?= $row['email'] ?></td>
                            <td><?= htmlspecialchars($row['res_name']) ?></td>
                            <td><span style="background:#eee; padding:3px 8px; border-radius:3px; font-size:0.9em;"><?= $row['role'] ?></span></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('¿Confirmas que quieres eliminar este vínculo?');">
                                    <input type="hidden" name="action" value="eliminar">
                                    <input type="hidden" name="rel_id" value="<?= $row['rel_id'] ?>">
                                    <button type="submit" class="btn btn-del">Quitar Vínculo</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; 
                else: ?>
                    <tr><td colspan="4" class="empty-state">No hay vinculaciones registradas para este tipo.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>