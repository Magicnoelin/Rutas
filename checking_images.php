<?php
// 1. CONFIGURACIÓN BASE DE DATOS
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "USUARIO_DB";
$pass = "PASSWORD_DB";
$charset = "utf8mb4";

// 2. CONEXIÓN PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    die("Error de conexión");
}

// 3. CONSULTA
$sql = "SELECT id, photo1 FROM accommodations
        WHERE photo1 IS NOT NULL AND photo1 <> ''";
$stmt = $pdo->query($sql);

// 4. FUNCIÓN PARA COMPROBAR SI EXISTE LA IMAGEN
function imageExists($url) {
    $headers = @get_headers($url);
    if (!$headers) return false;
    return strpos($headers[0], '200') !== false;
}

// 5. RECORRER REGISTROS
$broken = [];

foreach ($stmt as $row) {
    $url = $row['photo1'];

    if (!imageExists($url)) {
        $broken[] = [
            'id' => $row['id'],
            'photo1' => $url
        ];
    }
}

// 6. RESULTADO
echo "<h2>Imágenes rotas encontradas</h2>";

if (empty($broken)) {
    echo "No hay imágenes rotas.";
} else {
    echo "<ul>";
    foreach ($broken as $img) {
        echo "<li>ID {$img['id']} → {$img['photo1']}</li>";
    }
    echo "</ul>";
}