<?php
require_once 'config.php';
$outputFile = 'routes_schema.txt';

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $out = "SCHEMA DUMP\n\n";
    $tables = ['routes', 'route_items'];

    foreach ($tables as $table) {
        $out .= "TABLE: $table\n";
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $out .= "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
            }
        } catch (Exception $e) {
            $out .= "  NOT FOUND or ERROR: " . $e->getMessage() . "\n";
        }
        $out .= "\n";
    }

    file_put_to_contents($outputFile, $out);
    echo "Schema dumped to $outputFile";

} catch (Exception $e) {
    file_put_to_contents($outputFile, "FATAL ERROR: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
?>