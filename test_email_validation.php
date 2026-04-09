<?php
// Test para validación de email y otros campos

echo "=== TEST de validación de campos ===\n\n";

// Función de validación de email (igual que en guardar_actividad.php)
function testEmailValidation($email) {
    $valor_trim = trim($email);
    
    if ($valor_trim === '') {
        return 'NULL';
    }
    
    if (!filter_var($valor_trim, FILTER_VALIDATE_EMAIL)) {
        return 'NULL (email inválido)';
    }
    
    // Limitar longitud
    if (strlen($valor_trim) > 255) {
        return substr($valor_trim, 0, 255) . ' (truncado)';
    }
    
    return $valor_trim;
}

// Función de validación de teléfono
function testPhoneValidation($phone) {
    $valor_trim = trim($phone);
    
    if ($valor_trim === '') {
        return 'NULL';
    }
    
    // Limpiar teléfono
    $valor = preg_replace('/[^\d\s\+\-\(\)]/', '', $valor_trim);
    
    // Limitar longitud
    if (strlen($valor) > 50) {
        $valor = substr($valor, 0, 50) . ' (truncado)';
    }
    
    return $valor;
}

// Casos de prueba para email
$email_test_cases = [
    '' => 'NULL',
    'test@example.com' => 'test@example.com',
    'invalid-email' => 'NULL (email inválido)',
    'test@example' => 'NULL (email inválido)',
    'a@b.c' => 'a@b.c',
    str_repeat('a', 300) . '@example.com' => substr(str_repeat('a', 300) . '@example.com', 0, 255) . ' (truncado)',
];

echo "--- Validación de Email ---\n";
foreach ($email_test_cases as $input => $expected) {
    $result = testEmailValidation($input);
    $status = ($result === $expected || 
              (strpos($expected, 'truncado') !== false && strpos($result, 'truncado') !== false)) ? '✓' : '✗';
    echo "$status Input: '$input'\n";
    echo "  Resultado: '$result'\n";
    echo "  Esperado: '$expected'\n\n";
}

// Casos de prueba para teléfono
$phone_test_cases = [
    '' => 'NULL',
    '+34 123 456 789' => '+34 123 456 789',
    '123-456-789' => '123-456-789',
    '(123) 456-789' => '(123) 456-789',
    'abc123!@#456' => '123456', // Solo números
    str_repeat('1', 60) => substr(str_repeat('1', 60), 0, 50) . ' (truncado)',
];

echo "--- Validación de Teléfono ---\n";
foreach ($phone_test_cases as $input => $expected) {
    $result = testPhoneValidation($input);
    $status = ($result === $expected || 
              (strpos($expected, 'truncado') !== false && strpos($result, 'truncado') !== false)) ? '✓' : '✗';
    echo "$status Input: '$input'\n";
    echo "  Resultado: '$result'\n";
    echo "  Esperado: '$expected'\n\n";
}

echo "=== INSTRUCCIONES PARA PROBAR ===\n";
echo "1. Ahora el sistema valida automáticamente los emails y teléfonos\n";
echo "2. Emails inválidos se convierten a NULL para evitar errores de restricción\n";
echo "3. Teléfonos se limpian de caracteres no permitidos\n";
echo "4. Campos demasiado largos se truncarán automáticamente\n";
echo "\n";
echo "Para probar en el formulario:\n";
echo "1. Ve a https://rutasurales.io/admin_tablas/actividades_editar.php?id=15\n";
echo "2. Rellena el campo 'Email de Contacto' con un email válido\n";
echo "3. Rellena el campo 'Teléfono de Contacto'\n";
echo "4. Haz clic en 'Guardar Todo'\n";
echo "5. Si hay error, se mostrará información detallada para debugging\n";

?>