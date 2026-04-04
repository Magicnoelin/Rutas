<?php
header('Content-Type: application/xml; charset=utf-8');

$host = 'localhost';
$dbname = 'u412199647_Rutas';
$username = 'u412199647_olgamarin';
$password = 'Rutas5Rurales7$';
$baseUrl = 'https://rutasrurales.io';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión");
}

$type = $_GET['type'] ?? 'estatico';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

if ($type == 'estatico') {
    // Aquí pones tus URLs fijas (index, contacto, etc.) y las multiidioma
    $paginas = ['/', '/alojamientos-turisticos.html', '/eventos-culturales.html', '/rutas-turisticas.html'];
    foreach ($paginas as $p) {
        echo "<url><loc>$baseUrl$p</loc><priority>1.0</priority><changefreq>daily</changefreq></url>\n";
    }
} else {
    // Mapeo de tipos a tablas y prefijos de URL
    $config = [
        'alojamientos' => ['tabla' => 'accommodations', 'prefix' => '/alojamiento/'],
        'lugares'      => ['tabla' => 'places_of_interest',         'prefix' => '/lugar/'],
        'actividades'  => ['tabla' => 'tourist_activities',     'prefix' => '/actividad/'],
        'eventos'      => ['tabla' => 'cultural_events',         'priority' => '0.9', 'prefix' => '/evento/']
    ];

    if (isset($config[$type])) {
        $c = $config[$type];
        
        // Build the SQL query with appropriate filtering
        $sql = "SELECT slug, updated_at FROM {$c['tabla']} WHERE is_active = 1 AND slug IS NOT NULL";
        
        // Add date filtering for events to exclude past events
        if ($type == 'eventos') {
            $sql .= " AND COALESCE(end_date, DATE_ADD(start_date, INTERVAL 1 DAY)) >= CURDATE()";
        }
        
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lastmod = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($baseUrl . $c['prefix'] . $row['slug']) . "</loc>\n";
            echo "    <lastmod>$lastmod</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.8</priority>\n";
            echo "  </url>\n";
        }
    }
}
echo '</urlset>';