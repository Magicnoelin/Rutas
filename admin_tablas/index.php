<?php 
include 'db.php'; 
// Si el sidebar causa el bloqueo, asegúrate de que el archivo existe
if (file_exists('sidebar.php')) {
    include 'sidebar.php'; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <title>Gestor Alojamientos</title>
    <style>
        .main-content { padding: 20px; }
        #preloader, .loader, .loading { display: none !important; } 
        .table img { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        .slug-text { font-family: monospace; font-size: 0.85rem; color: #6c757d; }
        .btn-status { width: 110px; } /* Mantiene los botones de estado alineados */
    </style>
</head>
<body class="bg-light">

<div class="main-content">
    <div class="container-fluid">
        <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> Cambios guardados correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="bi bi-house"></i> Alojamientos</h3>
            <a href="alojamientos_nuevo.php" class="btn btn-success btn-sm">Nuevo Alojamiento</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Foto</th>
                            <th>Nombre / URL (Slug)</th>
                            <th>Provincia</th>
                            <th>Capacidad</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Consulta optimizada
                        $stmt = $pdo->query("SELECT id, name, slug, province, capacity, is_active, photo1 FROM accommodations ORDER BY id DESC");
                        while ($row = $stmt->fetch()): 
                        ?>
                        <tr>
                            <td>#<?= $row['id'] ?></td>
                            <td>
                                <img src="<?= $row['photo1'] ?: 'https://via.placeholder.com/50' ?>" alt="Thumbnail">
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?= htmlspecialchars($row['name']) ?></div>
                                <div class="slug-text">
                                    <i class="bi bi-link-45deg"></i> /<?= htmlspecialchars($row['slug']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($row['province']) ?></td>
                            <td><?= $row['capacity'] ?> plazas</td>
                            <td>
                                <?php if ($row['is_active']): ?>
                                    <a href="cambiar_estado_alojamiento.php?id=<?= $row['id'] ?>&status=0" 
                                       class="btn btn-sm btn-success btn-status shadow-sm"
                                       onclick="return confirm('¿Poner este alojamiento en modo Borrador?')">
                                        <i class="bi bi-eye"></i> Activo
                                    </a>
                                <?php else: ?>
                                    <a href="cambiar_estado_alojamiento.php?id=<?= $row['id'] ?>&status=1" 
                                       class="btn btn-sm btn-outline-secondary btn-status">
                                        <i class="bi bi-eye-slash"></i> Borrador
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group shadow-sm">
                                    <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar contenido">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <a href="editar_slug_alojamiento.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning" title="Cambiar URL (Slug)">
                                        <i class="bi bi-gear"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>