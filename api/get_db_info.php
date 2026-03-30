<?php
require_once 'config.php';
$outputFile = 'db_info.txt';

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $out = "DATABASE INFO DUMP\n";
    $out .= "Generated at: " . date('Y-m-d H:i:s') . "\n\n";

    $out .= "TABLES LIST:\n";
    $stmt = $pdo->query("SHOW TABLES");
    while($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $out .= "- " . $row[0] . "\n";
    }
    $out .= "\n";

    $tablesToDescribe = ['routes', 'route_items'];
    foreach($tablesToDescribe as $table) {
        $out .= "STRUCTURE FOR '$table':\n";
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $out .= "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
            }
        } catch (Exception $e) {
            $out .= "  NOT FOUND: " . $e->getMessage() . "\n";
        }
        $out .= "\n";
    }

    file_put_contents($outputFile, $out);
    echo "Info written to $outputFile";

} catch (Exception $e) {
    file_put_contents($outputFile, "FATAL ERROR: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
?>