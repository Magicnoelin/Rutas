<?php
/**
 * API Endpoint: Obtener Alojamientos Turísticos (Tabla accommodations)
 * GET /api/accommodations.php
 * Parámetros opcionales:
 * - page: número de página (default: 1)
 * - limit: items por página (default: 20)
 * - tipo: filtrar por tipo de alojamiento
 * - provincia: filtrar por provincia
 */

// Configuración básica de headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Datos de prueba para verificar que la API funciona
$testData = [
    [
        'id' => 1,
        'name' => 'Hotel Rural El Mirador',
        'type' => 'Hotel',
        'address' => 'Calle Mayor 15, Vinuesa, Soria',
        'capacity' => 20,
        'price' => 85.00,
        'description' => 'Hotel rural con vistas espectaculares a la Laguna Negra. Habitaciones confortables con baño privado.',
        'phone' => '+34 975 123 456',
        'email' => 'info@hotelruralmirador.com',
        'website' => 'https://hotelruralmirador.com',
        'image1' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop',
        'status' => 'active',
        // Campos compatibles con el frontend
        'Nombre' => 'Hotel Rural El Mirador',
        'Tipo' => 'Hotel',
        'Direccion' => 'Calle Mayor 15, Vinuesa, Soria',
        'Plazas' => 20,
        'Precio' => 85.00,
        'Notaspublicas' => 'Hotel rural con vistas espectaculares a la Laguna Negra. Habitaciones confortables con baño privado.',
        'Telefono1' => '+34 975 123 456',
        'Email' => 'info@hotelruralmirador.com',
        'Web' => 'https://hotelruralmirador.com',
        'Foto1' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop',
        'localidad' => 'Vinuesa',
        'provincia' => 'Soria',
        'fotos' => ['https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop']
    ],
    [
        'id' => 2,
        'name' => 'Apartamentos Turísticos Centro',
        'type' => 'Apartamento',
        'address' => 'Plaza Mayor 8, Soria Capital',
        'capacity' => 6,
        'price' => 65.00,
        'description' => 'Apartamentos modernos en el centro histórico de Soria. Perfectos para familias o grupos.',
        'phone' => '+34 975 654 321',
        'email' => 'reservas@apartamentossoria.com',
        'website' => 'https://apartamentossoria.com',
        'image1' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=300&fit=crop',
        'status' => 'active',
        // Campos compatibles con el frontend
        'Nombre' => 'Apartamentos Turísticos Centro',
        'Tipo' => 'Apartamento',
        'Direccion' => 'Plaza Mayor 8, Soria Capital',
        'Plazas' => 6,
        'Precio' => 65.00,
        'Notaspublicas' => 'Apartamentos modernos en el centro histórico de Soria. Perfectos para familias o grupos.',
        'Telefono1' => '+34 975 654 321',
        'Email' => 'reservas@apartamentossoria.com',
        'Web' => 'https://apartamentossoria.com',
        'Foto1' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=300&fit=crop',
        'localidad' => 'Soria',
        'provincia' => 'Soria',
        'fotos' => ['https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=300&fit=crop']
    ]
];

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
    exit();
}

// Obtener parámetros de filtrado
$tipoFiltro = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$searchFiltro = isset($_GET['search']) ? strtolower($_GET['search']) : '';

// Aplicar filtros
$dataFiltrada = $testData;
if (!empty($tipoFiltro)) {
    $dataFiltrada = array_filter($dataFiltrada, function($item) use ($tipoFiltro) {
        return strtolower($item['type']) === strtolower($tipoFiltro) ||
               strtolower($item['Tipo']) === strtolower($tipoFiltro);
    });
}

if (!empty($searchFiltro)) {
    $dataFiltrada = array_filter($dataFiltrada, function($item) use ($searchFiltro) {
        return strpos(strtolower($item['name']), $searchFiltro) !== false ||
               strpos(strtolower($item['Nombre']), $searchFiltro) !== false ||
               strpos(strtolower($item['address']), $searchFiltro) !== false ||
               strpos(strtolower($item['Direccion']), $searchFiltro) !== false;
    });
}

// Convertir a array indexado
$dataFiltrada = array_values($dataFiltrada);

// Respuesta exitosa con datos de prueba
echo json_encode([
    'success' => true,
    'message' => 'Alojamientos turísticos obtenidos correctamente (datos de prueba)',
    'data' => $dataFiltrada,
    'pagination' => [
        'current_page' => 1,
        'total_pages' => 1,
        'total_records' => count($dataFiltrada),
        'per_page' => 20,
        'has_next' => false,
        'has_prev' => false
    ]
]);
exit();
