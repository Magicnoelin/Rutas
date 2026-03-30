<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Gestor de Actividades</title>
</head>

<body class="bg-light p-4">
<div class="container">
    <div class="d-flex justify-content-between mb-4 align-items-center">
        <h2>Actividades Turísticas</h2>
        <div class="btn-group">
            <a href="index.php" class="btn btn-sm btn-outline-secondary">Alojamientos</a>
            <a href="lugares_index.php" class="btn btn-sm btn-outline-secondary">Lugares</a>
            <a href="eventos_index.php" class="btn btn-sm btn-outline-secondary">Eventos</a>
        </div>
    </div>
    <div class="card shadow border-0">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Actividad</th>
                    <th>Dificultad</th>
                    <th>Precio Adulto</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT id, name, difficulty_level, price_adult FROM tourist_activities ORDER BY id DESC");
                while ($row = $stmt->fetch()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                    <td><span class="badge bg-warning text-dark"><?= $row['difficulty_level'] ?></span></td>
                    <td><?= $row['price_adult'] ?>€</td>
                    <td><a href="actividades_editar.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Editar</a></td>
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