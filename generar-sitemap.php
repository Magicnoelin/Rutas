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
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '      xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

if ($type == 'estatico') {
    // ── URLs hub canónicas (máxima prioridad — landing principal de cada vertical) ──
    // Formato extendido con hreflang para las verticales multilingüe (alo + eventos).
    // Lugares y Actividades son solo ES de momento; se ampliarán en futuras fases.

    $today = date('Y-m-d');

    // 1. Homepage
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}/</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>daily</changefreq>\n";
    echo "    <priority>1.0</priority>\n";
    $idiomas = ['es', 'en', 'fr', 'de', 'zh'];
    foreach ($idiomas as $hl) {
        $href = $baseUrl . ($hl === 'es' ? '/' : "/$hl/");
        echo "    <xhtml:link rel=\"alternate\" hreflang=\"{$hl}\" href=\"{$href}\" />\n";
    }
    echo "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$baseUrl}/\" />\n";
    echo "  </url>\n";

    // 2. Hub Alojamientos — 5 idiomas
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}/alojamientos/</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>daily</changefreq>\n";
    echo "    <priority>1.0</priority>\n";
    foreach ($idiomas as $hl) {
        $href = $baseUrl . ($hl === 'es' ? '/alojamientos/' : "/$hl/alojamientos/");
        echo "    <xhtml:link rel=\"alternate\" hreflang=\"{$hl}\" href=\"{$href}\" />\n";
    }
    echo "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$baseUrl}/alojamientos/\" />\n";
    echo "  </url>\n";

    // 3. Hub Eventos — 5 idiomas
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}/eventos/</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>daily</changefreq>\n";
    echo "    <priority>1.0</priority>\n";
    foreach ($idiomas as $hl) {
        $href = $baseUrl . ($hl === 'es' ? '/eventos/' : "/$hl/eventos/");
        echo "    <xhtml:link rel=\"alternate\" hreflang=\"{$hl}\" href=\"{$href}\" />\n";
    }
    echo "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$baseUrl}/eventos/\" />\n";
    echo "  </url>\n";

    // 4. Hub Lugares — solo ES (por ahora)
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}/lugares/</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.9</priority>\n";
    echo "    <xhtml:link rel=\"alternate\" hreflang=\"es\" href=\"{$baseUrl}/lugares/\" />\n";
    echo "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$baseUrl}/lugares/\" />\n";
    echo "  </url>\n";

    // 5. Hub Actividades — solo ES (por ahora)
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}/actividades/</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.9</priority>\n";
    echo "    <xhtml:link rel=\"alternate\" hreflang=\"es\" href=\"{$baseUrl}/actividades/\" />\n";
    echo "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$baseUrl}/actividades/\" />\n";
    echo "  </url>\n";

    // 6. Páginas estáticas secundarias (aviso legal, rutas, etc.)
    $secundarias = [
        ['url' => '/rutas-turisticas.html', 'pri' => '0.7', 'freq' => 'weekly'],
        ['url' => '/aviso-legal.html',      'pri' => '0.3', 'freq' => 'yearly'],
        ['url' => '/apoyar.php',            'pri' => '0.5', 'freq' => 'monthly'],
    ];
    foreach ($secundarias as $s) {
        echo "  <url>\n";
        echo "    <loc>{$baseUrl}{$s['url']}</loc>\n";
        echo "    <lastmod>{$today}</lastmod>\n";
        echo "    <changefreq>{$s['freq']}</changefreq>\n";
        echo "    <priority>{$s['pri']}</priority>\n";
        echo "  </url>\n";
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
        // Incluir is_premium para discriminar alojamientos con derecho a traducciones multiidioma
        $sql = "SELECT slug, updated_at, is_premium FROM {$c['tabla']} WHERE is_active = 1 AND slug IS NOT NULL";
        
        // Add date filtering for events to exclude past events
        if ($type == 'eventos') {
            $sql .= " AND (
                (end_date IS NULL AND start_date >= CURDATE()) OR
                (end_date IS NOT NULL AND end_date >= CURDATE())
            )";
        }
        
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lastmod = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');
            
            // ── URL base en español (SIEMPRE) ──
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($baseUrl . $c['prefix'] . $row['slug']) . "</loc>\n";
            echo "    <lastmod>$lastmod</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.8</priority>\n";
            
            // ── Nodos hijo multiidioma SOLO si es Premium (tiene derecho a traducciones) ──
            if ($type === 'alojamientos' && !empty($row['is_premium'])) {
                $idiomas = ['es', 'en', 'fr', 'de', 'zh'];
                foreach ($idiomas as $idioma) {
                    $href = $baseUrl . ($idioma === 'es' ? '' : "/$idioma") . $c['prefix'] . $row['slug'];
                    echo "    <xhtml:link rel=\"alternate\" hreflang=\"$idioma\" href=\"" . htmlspecialchars($href) . "\" />\n";
                }
                echo "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($baseUrl . $c['prefix'] . $row['slug']) . "\" />\n";
            }
            
            echo "  </url>\n";
        }
    }
}
echo '</urlset>';