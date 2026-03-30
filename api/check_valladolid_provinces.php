<?php
/**
 * Verificar los valores exactos de provincia en los registros de Valladolid
 */

require_once 'config.php';

echo "<h1>Provincias de Valladolid - Valores Exactos</h1>";
echo "<pre>";

try {
    $pdo = getDBConnection();

    // Obtener TODOS los registros de Valladolid (sin filtro de is_active)
    $stmt = $pdo->prepare("SELECT id, name, province, is_active FROM accommodations WHERE province LIKE '%valladolid%' COLLATE utf8mb4_general_ci");
    $stmt->execute();
    $todosValladolid = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== TODOS LOS REGISTROS CON 'VALLADOLID' EN PROVINCIA ===\n";
    echo "Encontrados: " . count($todosValladolid) . "\n\n";

    foreach ($todosValladolid as $alojamiento) {
        $provinciaRaw = $alojamiento['province'];
        $provinciaDisplay = "'$provinciaRaw'";
        $isActive = $alojamiento['is_active'] ? 'ACTIVO' : 'INACTIVO';

        echo "ID {$alojamiento['id']}: {$alojamiento['name']} - Provincia: $provinciaDisplay - $isActive\n";
    }

    // Ahora verificar qué devuelve la API (solo activos)
    echo "\n=== LO QUE DEVUELVE LA API (SOLO ACTIVOS) ===\n";

    $_GET['table'] = 'accommodations';
    ob_start();
    include 'alojamientos.php';
    $response = ob_get_clean();

    $data = json_decode($response, true);

    if ($data && isset($data['data']['alojamientos'])) {
        $alojamientosApi = $data['data']['alojamientos'];

        $valladolidApi = [];
        foreach ($alojamientosApi as $alojamiento) {
            $provincia = $alojamiento['Provincia'] ?? $alojamiento['provincia'] ?? $alojamiento['province'] ?? $alojamiento['Province'] ?? '';
            if (stripos($provincia, 'valladolid') !== false) {
                $valladolidApi[] = $alojamiento;
            }
        }

        echo "API devuelve: " . count($valladolidApi) . " Valladolid\n\n";

        foreach ($valladolidApi as $alojamiento) {
            $id = $alojamiento['id'] ?? $alojamiento['ID'];
            $name = $alojamiento['name'] ?? $alojamiento['Nombre'];
            $provincia = $alojamiento['Provincia'] ?? $alojamiento['provincia'] ?? $alojamiento['province'] ?? $alojamiento['Province'];

            echo "ID $id: $name - Provincia: '$provincia'\n";
        }

    } else {
        echo "Error obteniendo datos de API\n";
    }

    echo "\n=== ANÁLISIS ===\n";
    echo "Si la API devuelve menos registros que la BD, hay un problema de filtrado.\n";
    echo "Si la API devuelve los mismos pero la web muestra menos, es problema de frontend.\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
