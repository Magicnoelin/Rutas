<?php
header('Content-Type: text/html; charset=utf-8');

// Configuración de base de datos
$host = 'localhost';
$db_name = 'u412199647_Rutas';
$username = 'u412199647_olgamarin';
$password = 'Rutas5Rurales7$';

echo "<h1>🔍 Buscador de Duplicados</h1>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // El slug que mencionas
    $slugProblema = 'casa-rural-la-real-herreria-1';
    
    echo "<h3>Buscando variaciones de 'La Real Herrería'...</h3>";
    
    // Buscar por slug exacto o parecido en el nombre
    $stmt = $pdo->prepare("SELECT id, name, slug, is_active, created_at FROM accommodations WHERE slug LIKE ? OR name LIKE ?");
    $stmt->execute(['%real-herreria%', '%Real Herrer%']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($resultados) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f2f2f2;'><th>ID</th><th>Nombre</th><th>Slug (URL)</th><th>Activo</th><th>Nota</th></tr>";
        
        foreach ($resultados as $row) {
            $esElProblema = ($row['slug'] === $slugProblema);
            $estilo = $esElProblema ? "background: #ffcccc;" : "";
            
            echo "<tr style='$estilo'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td><strong>{$row['slug']}</strong></td>";
            echo "<td>" . ($row['is_active'] ? '✅ Sí' : '❌ No') . "</td>";
            echo "<td>";
            if ($esElProblema) {
                echo "⚠️ <strong>Este es el duplicado (-1)</strong>";
            } else {
                echo "Posible original";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='margin-top: 20px; padding: 15px; background: #e7f3fe; border-left: 5px solid #2196F3;'>";
        echo "<h3>ℹ️ ¿Qué es el guión 1?</h3>";
        echo "<p>El sufijo <code>-1</code> se añade automáticamente cuando intentas guardar un alojamiento cuyo nombre genera una URL (slug) que <strong>ya existe</strong> en la base de datos.</p>";
        echo "<p>Por ejemplo:</p>";
        echo "<ul>";
        echo "<li>El primer 'Casa Rural La Real Herrería' obtiene: <code>casa-rural-la-real-herreria</code></li>";
        echo "<li>El segundo (duplicado) obtiene: <code>casa-rural-la-real-herreria-1</code></li>";
        echo "</ul>";
        echo "<p>Esto evita errores técnicos, pero indica que tienes el alojamiento repetido.</p>";
        echo "</div>";
        
    } else {
        echo "<p>❌ No se encontraron alojamientos con ese nombre o slug.</p>";
    }

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>