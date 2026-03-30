<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Gestor de Eventos</title>
</head>
<body class="bg-light p-4">
<div class="container">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <h2>Eventos Culturales</h2>
        <div>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">Alojamientos</a>
            <a href="lugares_index.php" class="btn btn-sm btn-outline-secondary">Lugares</a>
        </div>
    </div>
    <div class="card shadow border-0">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Fecha Inicio</th>
                    <th>Evento</th>
                    <th>Municipio</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT id, name, start_date, municipality, status FROM cultural_events ORDER BY start_date DESC");
                while ($row = $stmt->fetch()): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($row['start_date'])) ?></td>
                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['municipality']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= $row['status'] ?></span></td>
                    <td><a href="eventos_editar.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Editar</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Panel de Control</a>
    <div class="navbar-nav">
      <a class="nav-link" href="index.php">🏠 Alojamientos</a>
      <a class="nav-link" href="lugares_index.php">📍 Lugares</a>
      <a class="nav-link" href="eventos_index.php">🎉 Eventos</a>
      <a class="nav-link" href="actividades_index.php">🥾 Actividades</a>
    </div>
  </div>
</nav>
</html>