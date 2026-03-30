<?php
include 'db.php';
$id = $_GET['id'] ?? null;

if (!$id) { die("ID no proporcionado."); }

// Obtener el nombre y slug actual
$stmt = $pdo->prepare("SELECT name, slug FROM accommodations WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) { die("Alojamiento no encontrado."); }

// Procesar el cambio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_POST['slug'])); // Limpieza básica
    
    try {
        $update = $pdo->prepare("UPDATE accommodations SET slug = ? WHERE id = ?");
        $update->execute([$nuevo_slug, $id]);
        header("Location: index.php?status=slug_actualizado");
        exit;
    } catch (PDOException $e) {
        $error = "Error: El slug ya existe o es inválido.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Editar URL (Slug)</title>
</head>
<body class="bg-light p-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark fw-bold">
                        ⚠️ Editar URL Amigable (Slug)
                    </div>
                    <div class="card-body">
                        <p>Estás editando la URL de: <strong><?= htmlspecialchars($item['name']) ?></strong></p>
                        
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Slug actual:</label>
                                <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($item['slug']) ?>" required>
                                <div class="form-text text-danger">
                                    ¡Cuidado! Si cambias esto, los enlaces antiguos en Google dejarán de funcionar.
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-warning fw-bold">Actualizar URL</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>