<?php
header('Content-Type: application/json');

// 1. Mapeo: Tabla DB => Carpeta en Servidor
$entity_map = [
    'accommodations'     => 'alojamientos/',
    'cultural_events'    => 'eventos-culturales/',
    'places_of_interest' => 'lugares/',
    'activities'         => 'actividades/'
];

$base_path = "../img/";

// 2. Recibir datos
$entity_type = $_POST['entity_type'] ?? 'accommodations'; // Nombre de la tabla
$entity_id   = $_POST['entity_id'] ?? null;
$slug        = $_POST['slug'] ?? 'recurso-sin-nombre';
$category    = $_POST['category'] ?? 'general';
$file        = $_FILES['photo'] ?? null;

// Validación básica
if (!$entity_id || !$file || !isset($entity_map[$entity_type])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos o tipo de entidad no soportado']);
    exit;
}

// 3. Carpeta SEO
$folder_name = $entity_map[$entity_type] . $slug . "/";
$upload_dir = $base_path . $folder_name;

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 4. Nombre de archivo SEO (Ej: soria-monumento-fachada-12345.webp)
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_file_name = $slug . "-" . $category . "-" . time() . "." . $extension;
$final_path = $upload_dir . $new_file_name;

// 5. Guardar
if (move_uploaded_file($file['tmp_name'], $final_path)) {
    
    include 'db_connect.php'; // Tu conexión habitual

    // Insertamos en la tabla maestra entity_photos
    $sql = "INSERT INTO entity_photos (entity_type, entity_id, category, file_path, alt_text) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $db_path = "/img/" . $folder_name . $new_file_name;
    $alt_text = str_replace('-', ' ', $slug) . " - " . $category;
    
    $stmt->bind_param("sisss", $entity_type, $entity_id, $category, $db_path, $alt_text);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'url' => $db_path]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al registrar en DB']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Error al mover el archivo']);
}