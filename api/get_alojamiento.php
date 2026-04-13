<?php
// Permitir que el frontend lea los datos (CORS)
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=UTF-8');

// 1. Configuración de conexión
$host = 'localhost'; 
$db   = 'u412199647_Rutas'; 
$user = 'u412199647_olgamarin';   
$pass = 'Rutas5Rurales7$';   

// 2. Crear conexión correctamente
$conn = new mysqli($host, $user, $pass, $db);

// 3. Comprobar conexión
if ($conn->connect_error) {
    echo json_encode(["error" => "Error de conexión: " . $conn->connect_error]);
    exit;
}

// 4. Coger slug desde la URL y validar
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    echo json_encode(["error" => "No se proporcionó un slug"]);
    exit;
}

// 5. Consulta preparada (Segura contra inyección SQL)
$stmt = $conn->prepare("
  SELECT 
    name, description, municipality,
    photo1, photo2, photo3, photo4
  FROM accommodations
  WHERE slug = ?
");

$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

// 6. Devolver JSON (o un objeto vacío si no hay resultados)
if ($data) {
    echo json_encode($data);
} else {
    echo json_encode(["error" => "Alojamiento no encontrado"]);
}

// 7. Cerrar
$stmt->close();
$conn->close();
?>