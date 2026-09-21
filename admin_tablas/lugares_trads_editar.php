<?php
// Configuración de base de datos
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

// Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    
    $sql = "UPDATE places_of_interest_trads SET 
            name = :name, 
            slug = :slug, 
            description = :description, 
            short_description = :short_description, 
            address = :address,
            municipality = :municipality,
            province = :province,
            opening_hours = :opening_hours,
            accessibility = :accessibility, 
            meta_title = :meta_title, 
            meta_description = :meta_description,
            entry_fee = :entry_fee,
            entry_fee_details = :entry_fee_details,
            facilities = :facilities
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id'                => $id,
        ':name'              => $_POST['name'],
        ':slug'              => $_POST['slug'],
        ':description'       => $_POST['description'],
        ':short_description' => $_POST['short_description'],
        ':address'           => $_POST['address'],
        ':municipality'      => $_POST['municipality'],
        ':province'         => $_POST['province'],
        ':opening_hours'    => $_POST['opening_hours'],
        ':accessibility'    => $_POST['accessibility'],
        ':meta_title'       => $_POST['meta_title'],
        ':meta_description' => $_POST['meta_description'],
        ':entry_fee'        => $_POST['entry_fee'],
        ':entry_fee_details' => $_POST['entry_fee_details'],
        ':facilities'       => $_POST['facilities']
    ]);
    
    $mensaje = "<div style='background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #c3e6cb;'>
                ✅ Traducción actualizada correctamente. <br>
                <a href='lugares_trads_index.php' style='font-weight:bold; color:#155724;'>← Volver al listado</a>
                </div>";
}

// Obtener datos actuales
$id = $_GET['id'] ?? null;
if (!$id) die("Error: ID no proporcionado.");

$stmt = $pdo->prepare("SELECT * FROM places_of_interest_trads WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) die("Error: Registro no encontrado.");

// Obtener nombre del lugar original
$stmtPlace = $pdo->prepare("SELECT name FROM places_of_interest WHERE id = ?");
$stmtPlace->execute([$row['place_id']]);
$placeName = $stmtPlace->fetchColumn() ?? 'Unknown';
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
        .lang-tag { background: #28a745; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
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
    <a href="lugares_trads_index.php" class="back-btn">← Volver al panel de control</a>
    
    <div class="header-info">
        <h2>Editar Traducción de Lugar</h2>
        <div>
            <span style="margin-right: 10px;">ID Lugar: <strong><?= $row['place_id'] ?></strong></span>
            <span style="margin-right: 10px;">Lugar: <strong><?= htmlspecialchars($placeName) ?></strong></span>
            <span class="lang-tag"><?= strtoupper($row['language_code']) ?></span>
        </div>
    </div>

    <?= $mensaje ?>

    <form method="POST">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre del Lugar</label>
                <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Slug (URL Friendly)</label>
                <input type="text" name="slug" value="<?= htmlspecialchars($row['slug']) ?>" required>
            </div>
            <div class="form-group">
                <label>Municipio</label>
                <input type="text" name="municipality" value="<?= htmlspecialchars($row['municipality']) ?>">
            </div>
            <div class="form-group">
                <label>Provincia</label>
                <input type="text" name="province" value="<?= htmlspecialchars($row['province']) ?>">
            </div>
            <div class="form-group full-width">
                <label>Dirección</label>
                <input type="text" name="address" value="<?= htmlspecialchars($row['address']) ?>">
            </div>
            <div class="form-group full-width">
                <label>Descripción Corta</label>
                <textarea name="short_description"><?= htmlspecialchars($row['short_description']) ?></textarea>
            </div>
            <div class="form-group full-width">
                <label>Descripción Completa (HTML)</label>
                <textarea name="description" style="height: 150px;"><?= htmlspecialchars($row['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Horario de Apertura</label>
                <input type="text" name="opening_hours" value="<?= htmlspecialchars($row['opening_hours']) ?>">
            </div>
            <div class="form-group">
                <label>Accesibilidad</label>
                <input type="text" name="accessibility" value="<?= htmlspecialchars($row['accessibility']) ?>">
            </div>
            <div class="form-group">
                <label>Precio de Entrada</label>
                <input type="text" name="entry_fee" value="<?= htmlspecialchars($row['entry_fee']) ?>">
            </div>
            <div class="form-group">
                <label>Detalles del Precio</label>
                <input type="text" name="entry_fee_details" value="<?= htmlspecialchars($row['entry_fee_details']) ?>">
            </div>
            <div class="form-group full-width">
                <label>Instalaciones</label>
                <textarea name="facilities"><?= htmlspecialchars($row['facilities']) ?></textarea>
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
