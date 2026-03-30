<?php
// 1. Configuración de la base de datos (RELLENA CON TUS DATOS)
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$mensaje = "";

// 2. Procesar el formulario (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    
    $sql = "UPDATE cultural_events_trads SET 
            name = :name, 
            slug = :slug, 
            description = :description, 
            short_description = :short_description, 
            program = :program, 
            target_audience = :target_audience, 
            accessibility = :accessibility, 
            meta_title = :meta_title, 
            meta_description = :meta_description 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id'                => $id,
        ':name'              => $_POST['name'],
        ':slug'              => $_POST['slug'],
        ':description'       => $_POST['description'],
        ':short_description' => $_POST['short_description'],
        ':program'           => $_POST['program'],
        ':target_audience'   => $_POST['target_audience'],
        ':accessibility'     => $_POST['accessibility'],
        ':meta_title'        => $_POST['meta_title'],
        ':meta_description'  => $_POST['meta_description']
    ]);
    
    $mensaje = "<div style='background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #c3e6cb;'>
                ✅ Traducción actualizada correctamente. <br>
                <a href='cultural_events_trads_index.php' style='font-weight:bold; color:#155724;'>← Volver al listado</a>
                </div>";
}

// 3. Obtener datos actuales
$id = $_GET['id'] ?? null;
if (!$id) die("Error: ID no proporcionado.");

$stmt = $pdo->prepare("SELECT * FROM cultural_events_trads WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) die("Error: Registro no encontrado.");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Traducción - ID <?= $row['id'] ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7f6; padding: 20px; color: #333; }
        .wrapper { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header-info { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; margin-bottom: 20px; padding-bottom: 10px; }
        .lang-tag { background: #007bff; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 0.9em; color: #666; }
        input[type="text"], textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        textarea { height: 80px; }
        .btn-save { background: #28a745; color: white; padding: 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; margin-top: 20px; font-weight: bold; }
        .btn-save:hover { background: #218838; }
        .back-btn { text-decoration: none; color: #007bff; font-size: 0.9em; }
    </style>
</head>
<body>

<div class="wrapper">
    <a href="cultural_events_trads_index.php" class="back-btn">← Volver al panel de control</a>
    
    <div class="header-info">
        <h2>Editar Traducción de Evento</h2>
        <div>
            <span style="margin-right: 10px;">ID Evento: <strong><?= $row['event_id'] ?></strong></span>
            <span class="lang-tag"><?= strtoupper($row['language_code']) ?></span>
        </div>
    </div>

    <?= $mensaje ?>

    <form method="POST">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">

        <div class="form-grid">
            <div class="form-group">
                <label>Nombre del Evento</label>
                <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Slug (URL Friendly)</label>
                <input type="text" name="slug" value="<?= htmlspecialchars($row['slug']) ?>" required>
            </div>

            <div class="form-group full-width">
                <label>Descripción Corta</label>
                <textarea name="short_description"><?= htmlspecialchars($row['short_description']) ?></textarea>
            </div>

            <div class="form-group full-width">
                <label>Descripción Completa (HTML)</label>
                <textarea name="description" style="height: 150px;"><?= htmlspecialchars($row['description']) ?></textarea>
            </div>

            <div class="form-group full-width">
                <label>Programa / Recorrido</label>
                <textarea name="program"><?= htmlspecialchars($row['program']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Público Objetivo</label>
                <input type="text" name="target_audience" value="<?= htmlspecialchars($row['target_audience']) ?>">
            </div>

            <div class="form-group">
                <label>Accesibilidad</label>
                <input type="text" name="accessibility" value="<?= htmlspecialchars($row['accessibility']) ?>">
            </div>

            <div class="form-group">
                <label>SEO: Meta Title</label>
                <input type="text" name="meta_title" value="<?= htmlspecialchars($row['meta_title']) ?>">
            </div>

            <div class="form-group">
                <label>SEO: Meta Description</label>
                <textarea name="meta_description"><?= htmlspecialchars($row['meta_description']) ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn-save">GUARDAR CAMBIOS</button>
    </form>
</div>

</body>
</html>