<?php
// get_antonio_data.php
header('Content-Type: application/json');
include 'config.php'; // Tu archivo de conexión a DB

$response = [
    'accommodations' => [],
    'places_of_interest' => [],
    'tourist_activities' => [],
    'cultural_events' => []
];

// Función para limpiar y rellenar cada categoría
foreach ($response as $tabla => $data) {
    $sql = "SELECT id, nombre, descripcion, ubicacion, precio, icono FROM $tabla LIMIT 10";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $response[$tabla][] = $row;
        }
    }
}

echo json_encode($response);