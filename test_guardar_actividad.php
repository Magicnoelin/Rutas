<?php
// Test para verificar la función isValidJson y el manejo de datos

function isValidJson($string) {
    if (empty($string)) return false;
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

echo "=== TEST de función isValidJson ===\n\n";

$test_cases = [
    '[]' => true,
    '{}' => true,
    '""' => true,
    'null' => true,
    '["lunes", "martes"]' => true,
    '{"horario": "9:00-14:00"}' => true,
    '9:00-14:00' => false,
    'lunes, martes' => false,
    'casco, arnés' => false,
    '' => false,
    'NULL' => false,
];

foreach ($test_cases as $input => $expected) {
    $result = isValidJson($input);
    $status = $result === $expected ? '✓' : '✗';
    echo "$status Input: '$input' -> Resultado: " . ($result ? 'true' : 'false') . 
         " (Esperado: " . ($expected ? 'true' : 'false') . ")\n";
}

echo "\n=== TEST de conversión de datos ===\n\n";

// Simular datos del formulario
$datos_simulados = [
    'schedule' => '9:00-14:00',
    'available_days' => 'lunes, martes',
    'available_seasons' => 'primavera, verano',
    'languages_available' => 'español, inglés',
    'provided_equipment' => 'casco, arnés',
    'accessibility' => 'Silla de ruedas',
    'gallery' => 'https://img1.jpg, https://img2.jpg',
    'price_details' => 'Incluye IVA',
    'name' => 'Actividad de prueba',
    'description' => 'Descripción normal',
];

$campos_json_con_restricciones = [
    'schedule', 'available_days', 'available_seasons', 'languages_available',
    'provided_equipment', 'accessibility', 'gallery', 'price_details'
];

foreach ($datos_simulados as $columna => $valor) {
    $valor_trim = trim($valor);
    $resultado = $valor;
    
    if ($valor_trim === '') {
        $resultado = 'NULL';
    } elseif (in_array($columna, $campos_json_con_restricciones)) {
        if ($valor_trim === '[]' || $valor_trim === '{}' || $valor_trim === '""' || 
            $valor_trim === 'null' || $valor_trim === 'NULL') {
            $resultado = 'NULL';
        } elseif (!isValidJson($valor_trim)) {
            if ($columna === 'schedule') {
                $resultado = json_encode(['horario' => $valor_trim]);
            } else {
                $resultado = json_encode([$valor_trim]);
            }
        }
    }
    
    echo "Campo: $columna\n";
    echo "  Input: '$valor'\n";
    echo "  Output: '$resultado'\n";
    echo "  Es JSON válido: " . (isValidJson($resultado) ? 'Sí' : 'No') . "\n\n";
}

echo "=== INSTRUCCIONES ===\n";
echo "1. Este test verifica que los datos del formulario se conviertan correctamente a JSON\n";
echo "2. Los campos con restricciones CHECK se convierten automáticamente a JSON válido\n";
echo "3. Ejemplo: '9:00-14:00' -> {\"horario\":\"9:00-14:00\"}\n";
echo "4. Ejemplo: 'lunes, martes' -> [\"lunes, martes\"]\n";
echo "5. Campos vacíos se convierten a NULL\n";
?>