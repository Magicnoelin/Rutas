<?php
header('Content-Type: text/html; charset=utf-8');

// Credenciales de tu base de datos
$host = 'localhost';
$db_name = 'u412199647_Rutas';
$username = 'u412199647_olgamarin';
$password = 'Rutas5Rurales7$';

echo "<div style='font-family: sans-serif; padding: 20px;'>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lógica de borrado si se pulsó el botón
    if (isset($_POST['borrar_id'])) {
        $deleteStmt = $pdo->prepare("DELETE FROM accommodations WHERE id = ?");
        $deleteStmt->execute([$_POST['borrar_id']]);
        echo "<h2 style='color:green; border: 2px solid green; padding: 10px; display: inline-block;'>✅ Registro eliminado correctamente.</h2><hr>";
    }

    // Buscar el duplicado específico
    $slug = 'casa-rural-la-real-herreria-1';
    $stmt = $pdo->prepare("SELECT id, name, slug, created_at FROM accommodations WHERE slug = ?");
    $stmt->execute([$slug]);
    $duplicado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($duplicado) {
        echo "<h1>⚠️ Duplicado Encontrado</h1>";
        echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffeeba; border-radius: 5px;'>";
        echo "<p><strong>ID:</strong> <span style='font-size: 1.5em; color: red; font-weight: bold;'>" . $duplicado['id'] . "</span></p>";
        echo "<p><strong>Nombre:</strong> " . $duplicado['name'] . "</p>";
        echo "<p><strong>Slug (URL):</strong> " . $duplicado['slug'] . "</p>";
        echo "<p><strong>Fecha creación:</strong> " . $duplicado['created_at'] . "</p>";
        
        echo "<form method='POST' onsubmit='return confirm(\"¿Estás seguro de que quieres borrar este duplicado permanentemente?\");'>";
        echo "<input type='hidden' name='borrar_id' value='" . $duplicado['id'] . "'>";
        echo "<button type='submit' style='background: #dc3545; color: white; padding: 15px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold;'>🗑️ BORRAR ESTE DUPLICADO AHORA</button>";
        echo "</form>";
        echo "</div>";
    } else {
        echo "<h1>✅ No se encontró el duplicado</h1>";
        echo "<p>No existe ningún alojamiento con el slug <code>$slug</code> en la base de datos. Probablemente ya lo has borrado.</p>";
    }

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
echo "</div>";
?>