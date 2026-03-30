<?php
require_once 'config.php';
$outputFile = 'routes_data.txt';

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $out = "ROUTES DATA DUMP\n\n";
    
    $out .= "TABLE: routes\n";
    try {
        $stmt = $pdo->query("SELECT * FROM routes LIMIT 5");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $row) {
            $out .= print_r($row, true) . "\n";
        }
    } catch (Exception $e) { $out .= "Error: " . $e->getMessage() . "\n"; }

    $out .= "\nTABLE: route_items\n";
    try {
        $stmt = $pdo->query("SELECT * FROM route_items LIMIT 5");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $row) {
            $out .= print_r($row, true) . "\n";
        }
    } catch (Exception $e) { $out .= "Error: " . $e->getMessage() . "\n"; }

    file_put_contents($outputFile, $out);
    echo "Data dumped to $outputFile";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>