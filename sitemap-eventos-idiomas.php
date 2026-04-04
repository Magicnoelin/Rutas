<?php
/**
 * Sitemap dinámico: Eventos Culturales en otros idiomas
 * 
 * Genera automáticamente el sitemap con TODAS las traducciones de eventos
 * que existan en la tabla cultural_events_trads (excepto español 'es',
 * que ya está en sitemap-eventos.php).
 * 
 * URL: https://www.rutasrurales.io/sitemap-eventos-idiomas.php
 */

header('Content-Type: application/xml; charset=UTF-8');

// Conexión directa a BD (sin usar api/config.php para evitar headers JSON)
$host = 'localhost';
$dbname = 'u412199647_Rutas';
$user = 'u412199647_olgamarin';
$pass = 'Rutas5Rurales7$';

$today = date('Y-m-d');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /**
     * Consulta: obtener todas las traducciones activas
     * - Solo idiomas distintos al español
     * - Solo eventos activos (is_active = 1) que son futuros o actuales
     * - Incluye el slug original en español para el hreflang
     * CORRECCIÓN: Se cambió la lógica de fechas para coincidir con sitemap-eventos.php
     */
    $stmt = $pdo->prepare("
        SELECT 
            t.language_code,
            t.slug AS slug_traducido,
            e.slug AS slug_original,
            e.start_date,
            e.end_date,
            COALESCE(e.updated_at, e.created_at, NOW()) AS fecha_mod
        FROM cultural_events_trads t
        INNER JOIN cultural_events e ON e.id = t.event_id
        WHERE t.language_code != 'es'
          AND e.is_active = 1
          AND t.slug IS NOT NULL
          AND t.slug != ''
          AND (
            -- Eventos que NO han terminado todavía (misma lógica que sitemap-eventos.php)
            (e.end_date IS NULL AND e.start_date >= CURDATE()) OR  -- Eventos sin fecha fin que empiezan hoy o después
            (e.end_date IS NOT NULL AND e.end_date >= CURDATE())   -- Eventos con fecha fin que terminan hoy o después
          )
        ORDER BY t.language_code, t.slug
    ");
    $stmt->execute();
    $traducciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si hay error de BD, devolver sitemap vacío válido
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<!-- Error al conectar con la base de datos: ' . htmlspecialchars($e->getMessage()) . ' -->' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    exit;
}

// Generar XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<!--' . "\n";
echo '  Sitemap generado automáticamente desde la base de datos.' . "\n";
echo '  Contiene SOLO eventos en idiomas distintos al español (de, en, fr, zh...).' . "\n";
echo '  Las URLs en español están en sitemap-eventos.php' . "\n";
echo '  Generado: ' . date('Y-m-d H:i:s') . "\n";
echo '  Total traducciones: ' . count($traducciones) . "\n";
echo '-->' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach ($traducciones as $trad):
    $lang = htmlspecialchars($trad['language_code']);
    $slugTrad = htmlspecialchars($trad['slug_traducido']);
    $slugEs = htmlspecialchars($trad['slug_original']);
    $fechaMod = !empty($trad['fecha_mod']) ? date('Y-m-d', strtotime($trad['fecha_mod'])) : $today;
    
    // Prioridad: el eclipse tiene más relevancia
    $priority = (strpos($slugTrad, 'eclipse') !== false || strpos($slugTrad, 'sonnenfinsternis') !== false) ? '0.9' : '0.7';
?>
  <url>
    <loc>https://www.rutasrurales.io/<?= $lang ?>/evento/<?= $slugTrad ?></loc>
    <lastmod><?= $fechaMod ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority><?= $priority ?></priority>
    <xhtml:link rel="alternate" hreflang="es" href="https://www.rutasrurales.io/evento/<?= $slugEs ?>"/>
    <xhtml:link rel="alternate" hreflang="<?= $lang ?>" href="https://www.rutasrurales.io/<?= $lang ?>/evento/<?= $slugTrad ?>"/>
  </url>
<?php endforeach; ?>
</urlset>
