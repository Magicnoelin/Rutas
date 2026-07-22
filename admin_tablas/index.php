<?php 
include 'db.php'; 
// Se ha eliminado la inclusión de sidebar.php para que no aparezca el menú de la izquierda
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
        .btn-status { width: 110px; } 
        .owner-badge { font-size: 0.8rem; }
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
                            <th>Propietario</th> 
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Consulta incluyendo u.email para poder enviar correos
                        $query = "
                            SELECT 
                                a.id, 
                                a.name, 
                                a.slug, 
                                a.province, 
                                a.capacity, 
                                a.is_active, 
                                a.photo1,
                                u.id AS owner_id,
                                u.first_name AS owner_name,
                                u.email AS owner_email
                            FROM accommodations a
                            LEFT JOIN user_resources ur 
                                ON a.id = ur.resource_id 
                                AND ur.resource_type = 'accommodation' 
                                AND ur.role = 'owner'
                            LEFT JOIN users u 
                                ON ur.user_id = u.id
                            ORDER BY a.id DESC
                        ";
                        
                        $stmt = $pdo->query($query);
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
                                <?php if ($row['owner_id']): ?>
                                    <div class="owner-badge">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-person-fill text-secondary"></i> 
                                            <strong><?= htmlspecialchars($row['owner_name']) ?></strong> 
                                            
                                            <?php if (!empty($row['owner_email'])): ?>
                                                <a href="mailto:<?= htmlspecialchars($row['owner_email']) ?>?subject=Consulta sobre tu alojamiento: <?= urlencode($row['name']) ?>" 
                                                   class="btn btn-link p-0 text-decoration-none text-primary" 
                                                   title="Enviar email a <?= htmlspecialchars($row['owner_email']) ?>">
                                                    <i class="bi bi-envelope-at-fill"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-muted d-block">(ID: #<?= $row['owner_id'] ?>)</span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-danger-emphasis bg-danger-subtle px-2 py-1 rounded owner-badge">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Sin asignar
                                    </span>
                                <?php endif; ?>
                            </td>
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