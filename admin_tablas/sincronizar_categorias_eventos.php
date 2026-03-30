<?php
$host = 'localhost';
$dbname = 'u412199647_Rutas';
$username = 'u412199647_olgamarin';
$password = 'Rutas5Rurales7$';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h3>Sincronizando por nombre de evento...</h3>";

    // 1. Obtenemos las categorías maestras
    $stmtCat = $pdo->query("SELECT id, name FROM categories_events");
    $categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categorias as $cat) {
        $catId = $cat['id'];
        $catNombre = $cat['name'];

        // 2. Intentamos buscar eventos cuyo NOMBRE coincida con la categoría
        // OJO: He cambiado 'category_name' por 'name', que sí existe en tu tabla.
        $sql = "UPDATE cultural_events 
                SET category_id = :catId 
                WHERE name LIKE :catNombreBusqueda 
                AND (category_id IS NULL OR category_id = 0)";
        
        $stmtUpdate = $pdo->prepare($sql);
        // Usamos % para que encuentre el nombre aunque sea parte de una frase
        $stmtUpdate->execute([
            'catId' => $catId, 
            'catNombreBusqueda' => '%' . $catNombre . '%'
        ]);
        
        $filas = $stmtUpdate->rowCount();
        if ($filas > 0) {
            echo "✅ <b>$catNombre</b>: Se asignó el ID $catId a $filas eventos.<br>";
        }
    }

    echo "<h3>🚀 Proceso completado</h3>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>