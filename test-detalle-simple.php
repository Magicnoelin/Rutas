<?php
// Test simple para verificar PHP
echo "PHP está funcionando correctamente<br>";
echo "Versión PHP: " . phpinfo(INFO_GENERAL);

// Test de parámetros GET
$slug = $_GET['slug'] ?? 'no-slug';
echo "<br>Slug recibido: " . htmlspecialchars($slug);

// Test de conexión a base de datos
try {
    require_once 'api/config_updated.php';
    $pdo = getDBConnection();
    echo "<br>✅ Conexión a base de datos exitosa";
    
    $stmt = $pdo->prepare("SELECT * FROM accommodations WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<br>✅ Alojamiento encontrado: " . htmlspecialchars($result['name']);
    } else {
        echo "<br>❌ Alojamiento no encontrado para slug: " . htmlspecialchars($slug);
    }
} catch (Exception $e) {
    echo "<br>❌ Error: " . htmlspecialchars($e->getMessage());
}
?>
