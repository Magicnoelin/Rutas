<?php
/**
 * Script para guardar alojamientos en el archivo JSON local
 * Temporal hasta que funcione la API de base de datos
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responder a OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
    exit();
}

try {
    // Obtener datos del POST
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        echo json_encode([
            'success' => false,
            'error' => 'Datos JSON inválidos'
        ]);
        exit();
    }

    // Leer el archivo JSON actual
    $accommodationsFile = __DIR__ . '/accommodations.json';
    $currentData = [];

    if (file_exists($accommodationsFile)) {
        $jsonContent = file_get_contents($accommodationsFile);
        $currentData = json_decode($jsonContent, true);

        if (!is_array($currentData)) {
            $currentData = [];
        }
    }

    // Generar ID único
    $id = 'form_' . time() . '_' . rand(1000, 9999);

    // Crear el nuevo alojamiento con el formato correcto
    $nuevoAlojamiento = [
        'id' => $id,
        'name' => $data['Nombre'] ?? '',
        'type' => $data['Tipo'] ?? '',
        'address' => $data['Direccion'] ?? '',
        'capacity' => $data['Plazas'] ?? 0,
        'price' => $data['Precio'] ?? null,
        'description' => $data['Notaspublicas'] ?? '',
        'phone' => $data['Telefono1'] ?? '',
        'email' => $data['Email'] ?? '',
        'website' => $data['Web'] ?? '',
        'image1' => $data['Foto1'] ?? '',
        'status' => 'active',
        'Nombre' => $data['Nombre'] ?? '',
        'Tipo' => $data['Tipo'] ?? '',
        'Direccion' => $data['Direccion'] ?? '',
        'Plazas' => $data['Plazas'] ?? 0,
        'Precio' => $data['Precio'] ?? null,
        'Notaspublicas' => $data['Notaspublicas'] ?? '',
        'Telefono1' => $data['Telefono1'] ?? '',
        'Email' => $data['Email'] ?? '',
        'Web' => $data['Web'] ?? '',
        'Foto1' => $data['Foto1'] ?? '',
        'localidad' => '', // Extraer de dirección si es posible
        'provincia' => 'Soria', // Default
        'fotos' => [],
        'caracteristicas' => []
    ];

    // Extraer localidad y provincia de la dirección
    $direccion = $data['Direccion'] ?? '';
    if (!empty($direccion)) {
        $partes = explode(',', $direccion);
        if (count($partes) >= 2) {
            $nuevoAlojamiento['localidad'] = trim($partes[count($partes) - 2]);
            $nuevoAlojamiento['provincia'] = trim($partes[count($partes) - 1]);
        }
    }

    // Agregar fotos al array
    $fotos = [];
    for ($i = 1; $i <= 4; $i++) {
        $fotoKey = 'Foto' . $i;
        if (!empty($data[$fotoKey])) {
            $fotos[] = $data[$fotoKey];
        }
    }
    $nuevoAlojamiento['fotos'] = $fotos;

    // Agregar al array de alojamientos
    $currentData[] = $nuevoAlojamiento;

    // Guardar el archivo JSON
    $jsonOutput = json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $result = file_put_contents($accommodationsFile, $jsonOutput);

    if ($result === false) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al guardar el archivo JSON'
        ]);
        exit();
    }

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Alojamiento guardado exitosamente en el archivo JSON',
        'data' => [
            'id' => $id,
            'nombre' => $data['Nombre'] ?? '',
            'estado' => 'activo',
            'recaptcha_score' => 1.0 // Simulado
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
