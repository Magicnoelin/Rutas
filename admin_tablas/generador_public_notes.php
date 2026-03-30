<?php
// 1. Configuración de la base de datos (RELLENA CON TUS DATOS)
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Error: " . $e->getMessage());
}

// 2. Buscamos los que tengan las keywords vacías
$sql = "SELECT id, name, municipality, province FROM accommodations WHERE keywords IS NULL OR keywords = ''";
$stmt = $pdo->query($sql);
$alojamientos = $stmt->fetchAll();

echo "<h2>Generando Keywords...</h2>";
$count = 0;

foreach ($alojamientos as $row) {
    // Creamos una lista de keywords lógica
    $keywordsArray = [
        $row['name'],
        "alojamiento " . $row['municipality'],
        "casa rural " . $row['province'],
        "dormir en " . $row['municipality'],
        "turismo " . $row['province'],
        "donde dormir en " . $row['province']
    ];
    
    // Las unimos con comas
    $listaKeywords = implode(", ", $keywordsArray);

    // 3. Actualizamos
    $updateSql = "UPDATE accommodations SET keywords = ? WHERE id = ?";
    $pdo->prepare($updateSql)->execute([$listaKeywords, $row['id']]);
    
    echo "ID " . $row['id'] . ": Keywords creadas.<br>";
    $count++;
}

echo "<br><strong>¡Hecho! Se han actualizado $count campos de keywords.</strong>";
?>