<?php
/**
 * Página de edición de alojamiento
 */
session_start();

require_once 'api/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    die("ID de alojamiento no válido");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?redirect=edit-accommodation.php?id=" . $id);
    exit;
}

$userId = $_SESSION['user_id'];

// Verificar que el usuario es propietario del alojamiento
$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT a.*, u.id as user_id, u.first_name, u.last_name, u.email as user_email
    FROM accommodations a 
    LEFT JOIN users u ON a.created_by = u.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    die("Alojamiento no encontrado.");
}

// Verificar que el usuario actual es el propietario
if ($item['created_by'] != $userId) {
    die("No tienes permiso para editar este alojamiento.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar: <?= htmlspecialchars($item['name']) ?> - Rutas Rurales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--primary-color);">
        <div class="container">
            <a class="navbar-brand" href="index.html">Rutas</a>
            <div class="ms-auto">
                <a href="user-dashboard.html#mis-alojamientos" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Volver a Mis Alojamientos
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <form action="api/actualizar_alojamiento.php" method="POST" id="editForm">
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4">Editar: <span class="text-primary"><?= htmlspecialchars($item['name']) ?></span></h2>
                <button type="submit" class="btn btn-success btn-lg shadow-sm px-5">
                    <i class="bi bi-save"></i> Guardar Cambios
                </button>
            </div>

            <div class="bg-white p-4 border rounded">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre del Alojamiento</label>
                    <input type="text" name="name" class="form-control form-control-lg" value="<?= htmlspecialchars($item['name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Descripción</label>
                    <textarea name="description" class="form-control" rows="8"><?= htmlspecialchars($item['description']) ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Capacidad (personas)</label>
                        <input type="number" name="capacity" class="form-control" value="<?= $item['capacity'] ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Precio / Noche (€)</label>
                        <input type="number" step="0.01" name="price_per_night" class="form-control" value="<?= $item['price_per_night'] ?>">
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('api/actualizar_alojamiento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Cambios guardados correctamente');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('❌ Error de conexión');
            });
        });
    </script>
</body>
</html>
