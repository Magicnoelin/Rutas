<?php
/**
 * DEBUG TEMPORAL — borrar después de diagnosticar
 * URL: https://rutasrurales.io/rutas-tematicas/debug.php
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../api/config.php';

echo '<pre>';

try {
    $pdo = getDBConnection();
    echo "✅ Conexión BD OK\n\n";

    // 1. Ver si la ruta existe
    $stmt = $pdo->query("SELECT id, name, slug, status, is_public FROM routes WHERE slug = 'puente-1-mayo-soria' LIMIT 1");
    $ruta = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($ruta) {
        echo "✅ Ruta encontrada:\n";
        print_r($ruta);
    } else {
        echo "❌ Ruta NO encontrada con slug 'puente-1-mayo-soria'\n";
        echo "\nRutas existentes:\n";
        $all = $pdo->query("SELECT id, name, slug, status, is_public FROM routes LIMIT 10");
        print_r($all->fetchAll(PDO::FETCH_ASSOC));
    }

    // 2. Ver columnas de route_items
    echo "\n\nColumnas de route_items:\n";
    $cols = $pdo->query("DESCRIBE route_items");
    print_r($cols->fetchAll(PDO::FETCH_ASSOC));

    // 3. Ver items de la ruta si existe
    if ($ruta) {
        echo "\n\nItems de la ruta (route_id=" . $ruta['id'] . "):\n";
        $items = $pdo->prepare("SELECT * FROM route_items WHERE route_id = ? LIMIT 5");
        $items->execute([$ruta['id']]);
        print_r($items->fetchAll(PDO::FETCH_ASSOC));
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
}

echo '</pre>';
?>
