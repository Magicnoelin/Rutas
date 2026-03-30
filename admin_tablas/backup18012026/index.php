<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <title>Gestor de Alojamientos - SEO & Media</title>
    <style>
        .progress { background-color: #e9ecef; border-radius: 10px; height: 6px; }
        .table img { object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .rank-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    </style>
</head>
<body class="bg-light pb-5">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow">
  <div class="container-fluid">
    <a class="navbar-brand" href="#"><i class="bi bi-gear-fill me-2"></i>Admin Rutas</a>
    <div class="navbar-nav">
      <a class="nav-link active" href="index.php">🏠 Alojamientos</a>
      <a class="nav-link" href="lugares_index.php">📍 Lugares</a>
      <a class="nav-link" href="eventos_index.php">🎉 Eventos</a>
      <a class="nav-link" href="actividades_index.php">🥾 Actividades</a>
    </div>
  </div>
</nav>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <h2 class="h3">Gestión de Alojamientos</h2>
        <?php if(isset($_GET['status'])): ?>
            <div class="alert alert-success py-1 px-3 mb-0 shadow-sm border-0">¡Actualizado!</div>
        <?php endif; ?>
    </div>

    <div class="card shadow border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Foto</th>
                        <th>Nombre / SEO</th>
                        <th>Multimedia</th>
                        <th>Contenido</th>
                        <th>Estado</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT id, name, province, description, meta_title, meta_description, is_active, photo1, photo2, photo3, photo4 FROM accommodations ORDER BY id DESC");
                    while ($row = $stmt->fetch()): 
                        
                        // Análisis SEO
                        $seo = 0;
                        if (!empty($row['meta_title'])) $seo += 35;
                        if (!empty($row['meta_description'])) $seo += 35;
                        if (mb_strlen($row['description'] ?? '') > 400) $seo += 30;

                        $color = ($seo < 40) ? 'bg-danger' : (($seo < 80) ? 'bg-warning text-dark' : 'bg-success');
                        
                        // Conteo Fotos
                        $f = 0;
                        if(!empty($row['photo1'])) $f++; if(!empty($row['photo2'])) $f++;
                        if(!empty($row['photo3'])) $f++; if(!empty($row['photo4'])) $f++;
                    ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td>
                            <?php if($row['photo1']): ?>
                                <img src="<?= $row['photo1'] ?>" width="50" height="50">
                            <?php else: ?>
                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="width:50px; height:50px; border-radius:4px;"><i class="bi bi-camera"></i></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div>
                            <div class="d-flex align-items-center mt-1">
                                <div class="progress me-2" style="width: 70px;">
                                    <div class="progress-bar <?= ($seo < 40) ? 'bg-danger' : (($seo < 80) ? 'bg-warning' : 'bg-success') ?>" style="width: <?= $seo ?>%"></div>
                                </div>
                                <span class="rank-label">Rank: <?= $seo ?>%</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= ($f >= 3) ? 'bg-info' : 'bg-light text-dark border' ?>"><?= $f ?> / 4 fotos</span>
                        </td>
                        <td>
                            <small class="fw-bold d-block"><?= mb_strlen($row['description'] ?? '') ?> carac.</small>
                        </td>
                        <td>
                            <?= $row['is_active'] ? '<span class="badge bg-success-subtle text-success border border-success">Público</span>' : '<span class="badge bg-light text-muted border">Borrador</span>' ?>
                        </td>
                        <td class="text-center">
                            <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm">Editar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>