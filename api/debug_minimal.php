<?php
/**
 * Debug minimal - solo probar conexión a BD
 */

// Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    echo "=== DEBUG MINIMAL ===\n";

    // Solo intentar cargar config y conectar
    require_once 'config.php';
    echo "✅ config.php cargado\n";

    $pdo = getDBConnection();
    echo "✅ Conexión BD exitosa\n";

    // Probar una consulta simple
    $result = $pdo->query("SELECT 1 as test");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "✅ Consulta simple funciona: " . $row['test'] . "\n";

    echo "🎉 TODO OK - El problema debe estar en la lógica del registro\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>
