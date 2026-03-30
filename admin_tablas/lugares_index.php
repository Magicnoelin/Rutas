<?php 
// 1. CONEXIÓN A LA BASE DE DATOS 
include 'db.php'; 

// 2. INCLUIR EL MENÚ LATERAL
include 'sidebar.php'; 

try {
    // Consulta con JOIN.
    $sql = "SELECT p.*, c.name as category_name 
            FROM places_of_interest p 
            LEFT JOIN categories_places c ON p.category_id = c.id 
            ORDER BY p.id DESC";
    $stmt = $pdo->query($sql);
    $lugares = $stmt->fetchAll();
} catch (PDOException $e) {
    // Si el JOIN falla, hacemos una consulta simple para que la página no muera
    $stmt = $pdo->query("SELECT * FROM places_of_interest ORDER BY id DESC");
    $lugares = $stmt->fetchAll();
    $error_msg = "Error en Categorías: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Lugares - Rutas Rurales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .img-preview { width: 60px; height: 45px; object-fit: cover; border-radius: 5px; background: #eee; }
        .table thead { background: #198754; color: white; }
        .badge-cat { font-size: 0.7rem; background: #e9ecef; color: #495057; border: 1px solid #dee2e6; }
        /* Ajuste para que el contenido no se pegue al sidebar */
        .main-content { padding: 25px; } 
    </style>
</head>
<body class="bg-light">

<div class="main-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between mb-4 align-items-center">
            <h2><i class="bi bi-geo-alt"></i> Control de Lugares</h2>
            <a href="lugares_nuevo.php" class="btn btn-success btn-sm shadow-sm">
                <i class="bi bi-plus-lg"></i> Nuevo Lugar
            </a>
        </div>

        <?php if(isset($error_msg)): ?>
            <div class="alert alert-warning small py-2"><?= $error_msg ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3" style="width: 120px;">ID / Foto</th>
                            <th>Lugar / Categoría</th>
                            <th>URL / SEO</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lugares as $lugar): ?>
                        <tr>
                            <td class="ps-3">
                                <small class="fw-bold d-block text-muted">#<?= $lugar['id'] ?></small>
                                <img src="<?= $lugar['photo1'] ?>" alt="<?= htmlspecialchars($lugar['name']) ?>" class="img-preview" onerror="this.src='https://via.placeholder.com/60x45?text=No+Img'">
                            </td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($lugar['name']) ?></div>
                                <span class="badge badge-cat">
                                    <i class="bi bi-tag-fill me-1"></i>
                                    <?= htmlspecialchars($lugar['category_name'] ?? 'ID: '.$lugar['category_id']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="small text-primary mb-1">/<?= htmlspecialchars($lugar['slug']) ?></div>
                                <?php 
                                    $seo_score = 0;
                                    if (!empty($lugar['meta_title'])) $seo_score++;
                                    if (!empty($lugar['meta_description'])) $seo_score++;
                                    if (!empty($lugar['keywords'])) $seo_score++;

                                    if ($seo_score == 3) {
                                        echo '<span class="badge bg-success" style="font-size:0.6rem;"><i class="bi bi-check-circle"></i> SEO OK</span>';
                                    } elseif ($seo_score > 0) {
                                        echo '<span class="badge bg-warning text-dark" style="font-size:0.6rem;"><i class="bi bi-exclamation-triangle"></i> SEO INC</span>';
                                    } else {
                                        echo '<span class="badge bg-danger" style="font-size:0.6rem;"><i class="bi bi-x-circle"></i> SIN SEO</span>';
                                    }
                                ?>
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        id="btn-status-<?= $lugar['id'] ?>"
                                        class="btn btn-sm rounded-pill btn-status <?= $lugar['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>"
                                        onclick="toggleEstado(<?= $lugar['id'] ?>, <?= $lugar['is_active'] ?>)">
                                    <?= $lugar['is_active'] ? 'Público' : 'Borrador' ?>
                                </button>
                            </td>
                            <td class="text-end pe-3">
                                <a href="lugares_editar.php?id=<?= $lugar['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleEstado(id, estadoActual) {
    const btn = document.getElementById('btn-status-' + id);
    const actual = parseInt(estadoActual);
    const nuevo = actual === 1 ? 0 : 1;

    btn.disabled = true;
    btn.style.opacity = "0.5";

    const datos = new URLSearchParams();
    datos.append('id', id);
    datos.append('nuevo_estado', nuevo);

    fetch('toggle_status.php', {
        method: 'POST',
        body: datos,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === "1") {
            btn.setAttribute('onclick', `toggleEstado(${id}, ${nuevo})`);
            if (nuevo === 1) {
                btn.innerText = 'Público';
                btn.className = 'btn btn-sm rounded-pill btn-success';
            } else {
                btn.innerText = 'Borrador';
                btn.className = 'btn btn-sm rounded-pill btn-outline-secondary';
            }
        } else {
            alert('Error al actualizar');
        }
    })
    .catch(err => alert('Error de conexión'))
    .finally(() => {
        btn.disabled = false;
        btn.style.opacity = "1";
    });
}
</script>
</body>
</html>