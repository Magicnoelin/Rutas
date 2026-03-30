<?php
require_once 'config.php';
header('Content-Type: text/plain');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    
    echo "LISTA DE TABLAS:\n";
    $stmt = $pdo->query("SHOW TABLES");
    while($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "- " . $row[0] . "\n";
    }
    
    $tablesToDescribe = ['routes', 'route_items'];
    foreach($tablesToDescribe as $table) {
        echo "\nESTRUCTURA DE LA TABLA '$table':\n";
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
            }
        } catch (Exception $e) {
            echo "  Error: La tabla no existe o no se puede leer.\n";
        }
    }

} catch (Exception $e) {
    echo "Error general: " . $e->getMessage();
}
?>