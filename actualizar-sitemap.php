<?php
/**
 * GENERADOR AUTOMÁTICO DE SITEMAPS - RUTAS RURALES (VERSIÓN FINAL REPARADA)
 */

chdir(__DIR__);

// 1. CONFIGURACIÓN DE BASE DE DATOS
$host = 'localhost';
$dbname = 'u412199647_Rutas';
$username = 'u412199647_olgamarin';
$password = 'Rutas5Rurales7$';
$baseUrl = 'https://rutasrurales.io';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 2. DEFINICIÓN DE SECCIONES Y TABLAS
$secciones = [
    'estatico'     => ['tipo' => 'estatico'],
    'alojamientos' => ['tabla' => 'accommodations',    'prefix' => '/alojamiento/'],
    'lugares'      => ['tabla' => 'places_of_interest', 'prefix' => '/lugar/'],
    'actividades'  => ['tabla' => 'tourist_activities', 'prefix' => '/actividad/'],
    'eventos'      => ['tabla' => 'cultural_events',    'prefix' => '/evento/']
];

echo "<h2>🔄 Iniciando actualización de Sitemaps...</h2>";

$archivos_generados = [];

// 3. GENERACIÓN DE ARCHIVOS INDIVIDUALES
foreach ($secciones as $nombre => $conf) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $contador = 0; 

    if ($nombre == 'estatico') {
        // Páginas fijas e idiomas
        $paginas = [
            '/', 
            '/alojamientos-turisticos.html', 
            '/eventos-culturales.html', 
            '/rutas-turisticas.html',
            '/en/index.html',
            '/fr/index.html',
            '/zh/index.html',
            '/de/index.html'
        ];
        foreach ($paginas as $p) {
            $xml .= "  <url><loc>$baseUrl$p</loc><lastmod>".date('Y-m-d')."</lastmod><priority>1.0</priority><changefreq>daily</changefreq></url>\n";
            $contador++; 
        }
    } else {
        // AQUÍ ESTÁ LO QUE FALTABA: La consulta a la base de datos
        try {
            // Construir la consulta base
            $sql = "SELECT slug, updated_at FROM `{$conf['tabla']}` WHERE is_active = 1 AND slug IS NOT NULL AND slug != ''";
            
            // Añadir filtro de fechas para eventos (solo eventos futuros/actuales)
            if ($nombre == 'eventos') {
                $sql .= " AND (
                    (end_date IS NULL AND start_date >= CURDATE()) OR
                    (end_date IS NOT NULL AND end_date >= CURDATE())
                )";
            }
            
            $stmt = $pdo->query($sql);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $contador++;
                $lastmod = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');
                $xml .= "  <url>\n";
                $xml .= "    <loc>" . htmlspecialchars($baseUrl . $conf['prefix'] . $row['slug']) . "</loc>\n";
                $xml .= "    <lastmod>$lastmod</lastmod>\n";
                $xml .= "    <changefreq>weekly</changefreq>\n";
                $xml .= "    <priority>0.8</priority>\n";
                $xml .= "  </url>\n";
            }
        } catch (Exception $e) {
            echo "⚠️ Error en tabla <b>{$conf['tabla']}</b>: " . $e->getMessage() . "<br>";
            continue;
        }
    }
    
    $xml .= '</urlset>';
    $filename = "sitemap-$nombre.xml";
    
    if (file_put_contents($filename, $xml)) {
        $archivos_generados[] = $filename;
        echo "✅ Archivo <b>$filename</b> actualizado con <b>$contador</b> URLs.<br>";
    } else {
        echo "❌ Error al escribir el archivo <b>$filename</b>.<br>";
    }
}

// 4. GENERACIÓN DEL ÍNDICE MAESTRO
$index = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$index .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($archivos_generados as $file) {
    $index .= "  <sitemap>\n";
    $index .= "    <loc>$baseUrl/$file</loc>\n";
    $index .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
    $index .= "  </sitemap>\n";
}
$index .= '</sitemapindex>';

if (file_put_contents('sitemap.xml', $index)) {
    echo "<h3>🚀 ¡Sitemap principal actualizado con éxito!</h3>";
    @file_get_contents("https://www.google.com/ping?sitemap=" . urlencode($baseUrl . "/sitemap.xml"));
    echo "<p>📢 Aviso enviado a Google (Ping).</p>";
}
?>