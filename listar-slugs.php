<?php
// Configuración de base de datos directa
$host = 'localhost';
$dbname = 'u412199647_Rutas';
$username = 'u412199647_olgamarin';
$password = 'Rutas5Rurales7$';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT id, name, slug, municipality, is_active FROM accommodations ORDER BY name");
    $alojamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Slugs Disponibles</title>";
    echo "<style>body{font-family:Arial;padding:2rem;background:#f5f5f5}table{width:100%;background:white;border-collapse:collapse;box-shadow:0 2px 10px rgba(0,0,0,0.1)}th,td{padding:1rem;text-align:left;border-bottom:1px solid #ddd}th{background:#4CAF50;color:white}tr:hover{background:#f0f0f0}a{color:#4CAF50;text-decoration:none;font-weight:bold}a:hover{text-decoration:underline}</style>";
    echo "</head><body>";
    echo "<h1>🏠 Alojamientos Disponibles en la Base de Datos</h1>";
    echo "<p>Total: <strong>" . count($alojamientos) . "</strong> alojamientos</p>";
    
    if (count($alojamientos) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Slug</th><th>Municipio</th><th>Activo</th><th>Ver</th></tr>";
        
        foreach ($alojamientos as $aloj) {
            $activo = $aloj['is_active'] ? '✅ Sí' : '❌ No';
            $slug = htmlspecialchars($aloj['slug']);
            $nombre = htmlspecialchars($aloj['name']);
            $municipio = htmlspecialchars($aloj['municipality']);
            
            echo "<tr>";
            echo "<td>{$aloj['id']}</td>";
            echo "<td>{$nombre}</td>";
            echo "<td><code>{$slug}</code></td>";
            echo "<td>{$municipio}</td>";
            echo "<td>{$activo}</td>";
            echo "<td><a href='/alojamientos/{$slug}' target='_blank'>Ver página →</a></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>❌ No hay alojamientos en la base de datos</p>";
    }
    
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Error</title></head><body>";
    echo "<h1>❌ Error de Conexión</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</body></html>";
}
?>
