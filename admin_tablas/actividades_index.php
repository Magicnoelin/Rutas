<?php 
// 1. CONEXIÓN A LA BASE DE DATOS 
include 'db.php'; 

// 2. INCLUIR EL MENÚ LATERAL
include 'sidebar.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <title>Gestor de Actividades - Rutas Rurales</title>
    <style>
        .progress { background-color: #e9ecef; border-radius: 10px; height: 6px; }
        .table img { object-fit: cover; border-radius: 4px; border: 1px solid #ddd; width: 50px; height: 50px; }
        .rank-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
        .main-content { padding: 25px; }
        .province-tag { font-size: 0.75rem; color: #6c757d; display: flex; align-items: center; gap: 4px; }
    </style>
</head>
<body class="bg-light">

<div class="main-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between mb-4 align-items-center">
            <h2 class="h3"><i class="bi bi-geo-alt"></i> Gestión de Actividades</h2>
            <div class="d-flex gap-2">
                <?php if(isset($_GET['status'])): ?>
                    <div class="alert alert-success py-1 px-3 mb-0 shadow-sm border-0 small">¡Actualizado con éxito!</div>
                <?php endif; ?>
                <a href="actividades_nuevo.php" class="btn btn-success btn-sm shadow-sm"><i class="bi bi-plus-lg"></i> Nueva Actividad</a>
            </div>
        </div>

        <div class="card shadow border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Foto</th>
                            <th>Actividad / Ubicación</th>
                            <th>Multimedia</th>
                            <th>SEO Status</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Nota: He cambiado la tabla a tourist_activities basándome en tus mensajes anteriores
                        $stmt = $pdo->query("SELECT id, name, province, description, meta_title, meta_description, is_active, photo1, photo2, photo3, photo4 FROM tourist_activities ORDER BY id DESC");
                        while ($row = $stmt->fetch()): 
                            
                            // Análisis SEO
                            $seo = 0;
                            if (!empty($row['meta_title'])) $seo += 35;
                            if (!empty($row['meta_description'])) $seo += 35;
                            $desc_len = mb_strlen(strip_tags($row['description'] ?? ''));
                            if ($desc_len > 400) $seo += 30;

                            $color = ($seo < 40) ? 'bg-danger' : (($seo < 80) ? 'bg-warning' : 'bg-success');
                            
                            // Conteo Fotos
                            $f = 0;
                            for($i=1; $i<=4; $i++) { if(!empty($row['photo'.$i])) $f++; }
                        ?>
                        <tr>
                            <td class="text-muted small">#<?= $row['id'] ?></td>
                            <td>
                                <?php if($row['photo1']): ?>
                                    <img src="<?= htmlspecialchars($row['photo1']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" onerror="this.src='https://via.placeholder.com/50'">
                                <?php else: ?>
                                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="width:50px; height:50px; border-radius:4px;"><i class="bi bi-camera"></i></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></div>
                                <div class="province-tag">
                                    <i class="bi bi-map"></i> <?= htmlspecialchars($row['province'] ?? 'Sin provincia') ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= ($f >= 3) ? 'bg-info text-white' : 'bg-light text-dark border' ?>">
                                    <i class="bi bi-image me-1"></i> <?= $f ?> / 4
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress me-2" style="width: 60px;">
                                        <div class="progress-bar <?= $color ?>" style="width: <?= $seo ?>%"></div>
                                    </div>
                                    <span class="rank-label" style="font-size: 0.6rem;"><?= $seo ?>%</span>
                                </div>
                                <small class="text-muted" style="font-size: 0.7rem;"><?= $desc_len ?> caracteres</small>
                            </td>
                            <td class="text-center">
                                <?php if($row['is_active']): ?>
                                    <span class="badge rounded-pill bg-success-subtle text-success border border-success px-3">
                                        <i class="bi bi-check-circle-fill me-1"></i> Publicado
                                    </span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning px-3">
                                        <i class="bi bi-pause-circle me-1"></i> Borrador
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="actividades_editar.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-white border shadow-sm px-3 rounded-pill">
                                    <i class="bi bi-pencil-square text-primary"></i> Editar
                                </a>
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