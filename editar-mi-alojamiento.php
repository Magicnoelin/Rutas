<?php 
/**
 * Página de edición de alojamiento para PROPIETARIOS
 * Esta página permite a los propietarios editar sus alojamientos
 */

session_start();

require_once 'api/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    die("ID de alojamiento no válido");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?redirect=editar-mi-alojamiento.php?id=" . $id);
    exit;
}

$userId = $_SESSION['user_id'];

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
    <style>
        .section-title { border-left: 4px solid var(--primary-color); padding-left: 10px; margin: 30px 0 20px 0; color: #333; font-weight: bold; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .status-draft { background: #fff3cd; color: #856404; }
        .status-pending { background: #cce5ff; color: #004085; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--primary-color);">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <img src="logo_990x1076_verde.png" height="40" alt="Rutas">
            </a>
            <div class="ms-auto">
                <a href="https://rutasrurales.io/user-dashboard.html#mis-alojamientos" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Volver a Mi Panel
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <form action="api/actualizar_alojamiento.php" method="POST" id="editForm">
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            
            <div class="alert <?= $item['moderation_status'] === 'pending' ? 'alert-info' : ($item['moderation_status'] === 'approved' ? 'alert-success' : 'alert-warning') ?>">
                <i class="bi bi-info-circle"></i> 
                <strong>Estado:</strong> 
                <?php 
                $statusLabels = [
                    'draft' => 'Borrador - Pendiente de enviar',
                    'pending' => 'Pendiente de revisión',
                    'approved' => 'Aprobado y publicado',
                    'rejected' => 'Rechazado'
                ];
                echo $statusLabels[$item['moderation_status']] ?? 'Desconocido';
                ?>
                <?php if($item['moderation_status'] === 'draft'): ?>
                    <button type="submit" name="submit_action" value="submit" class="btn btn-primary btn-sm ms-2">
                        <i class="bi bi-send"></i> Enviar para revisión
                    </button>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h4">Editar: <span class="text-primary"><?= htmlspecialchars($item['name']) ?></span></h2>
                </div>
                <button type="submit" name="submit_action" value="draft" class="btn btn-success btn-lg shadow-sm px-5">
                    <i class="bi bi-save"></i> Guardar Cambios
                </button>
            </div>

            <ul class="nav nav-tabs" id="mainTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#textos-panel" type="button">📝 Contenido</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#fotos-panel" type="button">🖼️ Fotos</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ubicacion-panel" type="button">📍 Ubicación</button></li>
            </ul>

            <div class="tab-content bg-white p-4 border border-top-0 rounded-bottom">
                <div class="tab-pane fade show active" id="textos-panel">
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
                            <input type="number" name="capacity" class="form-control" value="<?= $item['capacity'] ?>" readonly>
                            <small class="text-muted">La capacidad se modifica desde otra herramienta</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Precio / Noche (€)</label>
                            <input type="number" step="0.01" name="price_per_night" class="form-control" value="<?= $item['price_per_night'] ?>">
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="fotos-panel">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Nota:</strong> Las fotos se gestionan desde otra herramienta. En esta página solo se muestran para visualización.
                    </div>
                    <div class="row g-3">
                        <?php for($i=1; $i<=4; $i++): $f = "photo$i"; ?>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <label class="form-label fw-bold">Foto <?= $i ?></label>
                                <input type="text" name="<?= $f ?>" class="form-control mb-2" value="<?= htmlspecialchars($item[$f] ?? '') ?>" placeholder="URL de la foto" readonly>
                                <?php if(!empty($item[$f])): ?>
                                    <img src="<?= $item[$f] ?>" class="rounded shadow-sm" style="width:100%; height:120px; object-fit:cover;">
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="ubicacion-panel">
                    <div class="row g-3">
                        <div class="col-12 mb-3">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($item['address'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Municipio</label>
                            <input type="text" name="municipality" class="form-control" value="<?= htmlspecialchars($item['municipality'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Provincia</label>
                            <input type="text" name="province" class="form-control" value="<?= htmlspecialchars($item['province'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">C. Postal</label>
                            <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($item['postal_code'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Latitud</label>
                            <input type="text" name="latitude" class="form-control" value="<?= $item['latitude'] ?? '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Longitud</label>
                            <input type="text" name="longitude" class="form-control" value="<?= $item['longitude'] ?? '' ?>">
                        </div>
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
                    window.location.href = 'https://rutasrurales.io/user-dashboard.html#mis-alojamientos';
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
