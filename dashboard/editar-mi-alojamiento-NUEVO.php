<?php
session_start();
require_once '../api/config.php';

$pdo = getDBConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    die("ID de alojamiento no encontrado en la URL. Añade ?id=XXX al final de la dirección.");
}

// Verificar sesión de usuario
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html?redirect=/dashboard/editar-mi-alojamiento.php?id=" . $id);
    exit();
}

$userId = $_SESSION['user_id'];

// Consultar datos del alojamiento
$stmt = $pdo->prepare("SELECT * FROM accommodations WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    die("Alojamiento no encontrado.");
}

// Verificar que el usuario es propietario
if ($item['created_by'] != $userId) {
    die("No tienes permiso para editar este alojamiento.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar: <?php echo htmlspecialchars($item['name']); ?> - Rutas Rurales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary-color: #2e7d32; }
        body { background: #f5f5f5; }
        .navbar { background: var(--primary-color) !important; }
        .btn-primary { background: var(--primary-color); border-color: var(--primary-color); }
        .btn-primary:hover { background: #1b5e20; border-color: #1b5e20; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.html">
                <strong>Rutas Rurales</strong>
            </a>
            <div class="ms-auto">
                <a href="../user-dashboard.html#mis-alojamientos" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Volver a Mi Panel
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h4 class="mb-0">Editar: <?php echo htmlspecialchars($item['name']); ?></h4>
            </div>
            <div class="card-body">
                <form action="guardar-mi-alojamiento.php" method="POST" id="editForm">
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Alojamiento</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea name="description" class="form-control" rows="6"><?php echo htmlspecialchars($item['description']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacidad (personas)</label>
                            <input type="number" name="capacity" class="form-control" value="<?php echo $item['capacity']; ?>" readonly>
                            <small class="text-muted">La capacidad se modifica desde otra herramienta</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Precio / Noche (€)</label>
                            <input type="number" step="0.01" name="price_per_night" class="form-control" value="<?php echo $item['price_per_night']; ?>">
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">Ubicación</h5>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($item['address'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Municipio</label>
                            <input type="text" name="municipality" class="form-control" value="<?php echo htmlspecialchars($item['municipality'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Provincia</label>
                            <input type="text" name="province" class="form-control" value="<?php echo htmlspecialchars($item['province'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">C. Postal</label>
                            <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($item['postal_code'] ?? ''); ?>">
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">Fotos (Solo visualización)</h5>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        Las fotos se gestionan desde la herramienta de gestión de fotos.
                        <a href="../gestion-fotos-simple.html?slug=<?php echo $item['slug']; ?>" target="_blank" class="btn btn-sm btn-primary ms-2">
                            <i class="bi bi-camera"></i> Gestionar Fotos
                        </a>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
