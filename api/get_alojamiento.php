<?php

header('Content-Type: application/json');

// conexión 
$conn = new mysqli(
$host = 'localhost'; 
$db   = 'u412199647_Rutas'; 
$user = 'u412199647_olgamarin';   
$pass = 'Rutas5Rurales7$';   
);
// comprobar conexión
if ($conn->connect_error) {
    die("Error de conexión");
}

// coger slug desde la URL
$slug = $_GET['slug'];

// consulta
$sql = "SELECT * FROM accommodations WHERE slug =la-plaza-vinuesa?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $slug);
$stmt->execute();

$stmt = $conn->prepare("
  SELECT 
    name, description, municipality,
    photo1, photo2, photo3, photo4
  FROM accommodations
  WHERE slug = ?
");

$result = $stmt->get_result();
$data = $result->fetch_assoc();

// devolver JSON
echo json_encode($data);

$conn->close();
?>