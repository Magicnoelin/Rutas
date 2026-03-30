<?php
include 'db.php';
// Consulta mejorada con JOIN para ver el nombre de la categoría
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM places_of_interest p 
    LEFT JOIN categories_places c ON p.category_id = c.id 
    ORDER BY p.id DESC
");
$lugares = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Lugares | Rutas Rurales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .img-preview { width: 60px; height: 45px; object-fit: cover; border-radius: 5px; }
        .table thead { background: #198754; color: white; position: sticky; top: 0; }
        .badge-cat { font-size: 0.7rem; background: #e9ecef; color: #495057; border: 1px solid #dee2e6; }
        /* Animación simple para el cambio */
        .btn-ajax-status { transition: all 0.3s ease; width: 100px; }
    </style>
</head>
<body class="bg-light p-4">
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0 text-success"><i class="bi bi-list-check"></i> Inventario de Lugares</h2>
            <a href="index.php" class="btn btn-dark btn-sm">Inicio</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">ID / Foto</th>
                        <th>Lugar / Categoría</th>
                        <th>SEO & Slug</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lugares as $lugar): ?>
                    <tr>
                        <td class="ps-3">
                            <small class="fw-bold d-block text-muted">#<?= $lugar['id'] ?></small>
                            <img src="<?= $lugar['photo1'] ?: 'https://via.placeholder.com/60x45' ?>" class="img-preview">
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($lugar['name']) ?></div>
                            <div class="d-flex gap-1 mt-1">
                                <span class="badge badge-cat"><?= htmlspecialchars($lugar['category_name'] ?? 'Sin Cat') ?></span>
                                <span class="badge badge-cat">ID: <?= $lugar['id'] ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="small text-primary mb-1">/<?= htmlspecialchars($lugar['slug']) ?></div>
                            <span class="badge <?= !empty($lugar['meta_title']) ? 'bg-success' : 'bg-danger' ?>" style="font-size:0.6rem;">SEO</span>
                        </td>
                        <td class="text-center">
                            <button type="button" 
                                    class="btn btn-sm rounded-pill btn-ajax-status <?= $lugar['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>"
                                    onclick="cambiarEstado(this, <?= $lugar['id'] ?>)"
                                    data-activo="<?= $lugar['is_active'] ?>">
                                <?= $lugar['is_active'] ? 'Público' : 'Borrador' ?>
                            </button>
                        </td>
                        <td class="text-end pe-3">
                            <a href="lugares_editar.php?id=<?= $lugar['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function cambiarEstado(btn, id) {
    // Averiguar el estado actual (si es 1, pasamos a 0 y viceversa)
    let estadoActual = parseInt(btn.getAttribute('data-activo'));
    let nuevoEstado = estadoActual === 1 ? 0 : 1;

    // Crear la petición AJAX
    let formData = new FormData();
    formData.append('id', id);
    formData.append('nuevo_estado', nuevoEstado);

    // Desactivar botón momentáneamente
    btn.disabled = true;
    btn.innerText = "...";

    fetch('toggle_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data === "success") {
            // Actualizar visualmente el botón
            btn.setAttribute('data-activo', nuevoEstado);
            if (nuevoEstado === 1) {
                btn.innerText = "Público";
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-success');
            } else {
                btn.innerText = "Borrador";
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }
        } else {
            alert("Error al cambiar el estado");
        }
    })
    .catch(error => console.error('Error:', error))
    .finally(() => {
        btn.disabled = false;
    });
}
</script>

</body>
</html>