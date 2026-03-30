<?php
/**
 * Debug para registro de usuarios
 * Muestra los datos que llegan al endpoint
 */

require_once 'config.php';

echo "<h1>Debug Registro de Usuarios</h1>";
echo "<pre>";

// Solo procesar si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "=== DATOS RECIBIDOS ===\n";

    // Mostrar headers
    echo "Headers:\n";
    foreach (getallheaders() as $name => $value) {
        echo "  $name: $value\n";
    }
    echo "\n";

    // Mostrar body raw
    $rawBody = file_get_contents('php://input');
    echo "Raw Body:\n$rawBody\n\n";

    // Decodificar JSON
    $data = json_decode($rawBody, true);
    if ($data) {
        echo "JSON Decodificado:\n";
        foreach ($data as $key => $value) {
            echo "  $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
        echo "\n";

        // Verificar campos específicos
        echo "=== VERIFICACIÓN DE CAMPOS ===\n";
        $camposRequeridos = ['userType', 'firstName', 'lastName', 'email', 'password'];

        foreach ($camposRequeridos as $campo) {
            if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
                echo "❌ Campo faltante o vacío: $campo\n";
            } else {
                echo "✅ Campo OK: $campo = " . $data[$campo] . "\n";
            }
        }

        // Verificar userType específicamente
        echo "\n=== VERIFICACIÓN userType ===\n";
        if (isset($data['userType'])) {
            $userType = $data['userType'];
            echo "userType recibido: '$userType'\n";

            $tiposValidos = ['turista', 'alojamiento', 'promotor_eventos', 'actividad_cultural'];
            if (in_array($userType, $tiposValidos)) {
                echo "✅ userType válido\n";
            } else {
                echo "❌ userType inválido. Valores válidos: " . implode(', ', $tiposValidos) . "\n";
            }
        } else {
            echo "❌ userType no recibido\n";
        }

    } else {
        echo "❌ Error decodificando JSON\n";
    }

} else {
    echo "Esperando petición POST...\n";
    echo "Haz un registro desde el formulario para ver los datos aquí.\n";
}

echo "</pre>";
?>
