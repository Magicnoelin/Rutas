<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../api/config.php'; 

try {
    $pdo = getDBConnection();

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (isset($data['slug']) && isset($data['fotos'])) {
        $slug = $data['slug'];
        $fotos = $data['fotos']; 

        $updateParts = [];
        $params = [':slug' => $slug];

        // AJUSTE AQUÍ: Si tu tabla tiene solo 4 columnas, ponemos 4.
        // Si tiene 10, pon 10. Si ponemos de más, dará el error "Column not found".
        $num_columnas_reales = 10; 

        for ($i = 0; $i < $num_columnas_reales; $i++) {
            $columna = "photo" . ($i + 1); // Nombre exacto: photo1, photo2...
            $valor = isset($fotos[$i]) ? basename($fotos[$i]) : "";
            $updateParts[] = "$columna = :f" . ($i + 1);
            $params[":f" . ($i + 1)] = $valor;
        }

        $sql = "UPDATE accommodations SET " . implode(", ", $updateParts) . " WHERE slug = :slug";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se pudo actualizar la tabla']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>