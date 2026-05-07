<?php
// 1. Configuración de la base de datos
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

// Consulta ordenada por event_id ascendente
$stmt = $pdo->query("SELECT * FROM cultural_events_trads ORDER BY event_id DESC, language_code ASC");
$rows = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Cultural Events Trads</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; background: #f4f4f4; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #333; color: #fff; position: sticky; top: 0; z-index: 10; }
        tr:hover { background: #f1f1f1; }
        
        /* Estados de contenido */
        .status-empty { background: #ffcccc !important; color: #900; font-weight: bold; text-align: center; }
        .status-ok { background: #ccffcc !important; color: #006400; font-size: 10px; text-align: center; }
        
        .btn-edit { padding: 4px 8px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; font-weight: bold; }
        .btn-edit:hover { background: #0056b3; }

        /* Colores por Idioma */
        .lang-badge { font-weight: bold; padding: 3px 8px; border-radius: 4px; color: #fff; display: inline-block; min-width: 30px; text-align: center; }
        
        .lang-en { background-color: #3b5998; } /* Azul Inglés */
        .lang-fr { background-color: #0038a8; } /* Azul Francés */
        .lang-de { background-color: #e44d26; } /** Naranja/Rojo Alemán */
        .lang-default { background-color: #999; }
    </style>
</head>
<body>

    <h2>Control de Traducciones: cultural_events_trads</h2>
    <p>🟢 <b>Lleno</b> | 🔴 <b>VACÍO</b> (Los colores de la columna <b>Lang</b> ayudan a distinguir idiomas rápidamente)</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Event ID</th>
                <th>Lang</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Short Desc</th>
                <th>Description</th>
                <th>Program</th>
                <th>Target</th>
                <th>Meta Title</th>
                <th>Meta Desc</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): 
                // Asignar clase de color según el idioma
                $lCode = strtolower($row['language_code']);
                $langClass = 'lang-default';
                if ($lCode == 'en') $langClass = 'lang-en';
                if ($lCode == 'fr') $langClass = 'lang-fr';
                if ($lCode == 'de') $langClass = 'lang-de';
            ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><strong><?= $row['event_id'] ?></strong></td>
                <td>
                    <span class="lang-badge <?= $langClass ?>">
                        <?= strtoupper($row['language_code']) ?>
                    </span>
                </td>
                
                <?php 
                $fields = ['name', 'slug', 'short_description', 'description', 'program', 'target_audience', 'meta_title', 'meta_description'];
                
                foreach ($fields as $field): 
                    $isEmpty = empty(trim($row[$field]));
                    $class = $isEmpty ? 'status-empty' : 'status-ok';
                    $text = $isEmpty ? 'VACÍO' : 'Lleno';
                ?>
                    <td class="<?= $class ?>" title="<?= htmlspecialchars($row[$field]) ?>">
                        <?= $text ?>
                    </td>
                <?php endforeach; ?>

                <td>
                    <a href="cultural_events_trads_editar.php?id=<?= $row['id'] ?>" class="btn-edit">Editar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>