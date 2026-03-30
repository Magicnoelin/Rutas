<?php
/**
 * Test para verificar qué devuelve la API para Valladolid
 */

require_once 'config.php';

echo "<h1>Test API - Valladolid</h1>";
echo "<pre>";

// Probar la API como lo hace la web
echo "=== LLAMADA A API (como la web) ===\n";

try {
    // Llamar directamente a la API alojamientos.php
    echo "Llamando a alojamientos.php localmente...\n";

    // Simular los parámetros que usa la web
    $_GET['table'] = 'accommodations';

    // Incluir el archivo de la API
    ob_start();
    include 'alojamientos.php';
    $response = ob_get_clean();

    echo "Respuesta obtenida\n";
    $data = json_decode($response, true);

    if ($data && isset($data['data']['alojamientos'])) {
        $alojamientos = $data['data']['alojamientos'];

        echo "✅ API devolvió " . count($alojamientos) . " alojamientos\n\n";

        // Filtrar Valladolid
        $valladolid = array_filter($alojamientos, function($a) {
            $provincia = $a['Provincia'] ?? $a['provincia'] ?? $a['province'] ?? $a['Province'] ?? '';
            return strtolower(trim($provincia)) === 'valladolid';
        });

        echo "=== ALOJAMIENTOS DE VALLADOLID EN API ===\n";
        echo "Encontrados: " . count($valladolid) . "\n\n";

        foreach ($valladolid as $alojamiento) {
            $id = $alojamiento['id'] ?? $alojamiento['ID'] ?? 'N/A';
            $name = $alojamiento['name'] ?? $alojamiento['Nombre'] ?? 'Sin nombre';
            $provincia = $alojamiento['Provincia'] ?? $alojamiento['provincia'] ?? $alojamiento['province'] ?? $alojamiento['Province'] ?? 'Sin provincia';

            echo "ID $id: $name (Provincia: '$provincia')\n";
        }

        // Verificar si hay diferencias de capitalización
        echo "\n=== ANÁLISIS DE PROVINCIAS ===\n";
        $provinciasUnicas = array_unique(array_map(function($a) {
            return $a['Provincia'] ?? $a['provincia'] ?? $a['province'] ?? $a['Province'] ?? '';
        }, $alojamientos));

        echo "Provincias únicas encontradas:\n";
        foreach ($provinciasUnicas as $prov) {
            echo "  '$prov'\n";
        }

    } else {
        echo "❌ Error en respuesta de API\n";
        var_dump($data);
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
