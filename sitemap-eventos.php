<?php
/**
 * Sitemap dinámico unificado: Todos los Eventos Culturales
 * 
 * Genera un sitemap completo con:
 * - Todas las URLs en español (/evento/slug-es)
 * - Todas las traducciones disponibles (/de/evento/slug-de, /en/evento/slug-en, etc.)
 * - Etiquetas hreflang correctas en AMBAS direcciones (español ↔ traducción)
 * - Etiqueta x-default apuntando a la versión española
 * 
 * Regla de oro: si un evento tiene traducción al alemán, TANTO la URL española
 * como la alemana deben incluir xhtml:link apuntando a la otra.
 * 
 * URL: https://www.rutasrurales.io/sitemap-eventos.php
 */

header('Content-Type: application/xml; charset=UTF-8');

$host   = 'localhost';
$dbname = 'u412199647_Rutas';
$user   = 'u412199647_olgamarin';
$pass   = 'Rutas5Rurales7$';
$today  = date('Y-m-d');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Obtener todos los eventos activos en español
    $stmtEs = $pdo->query("
        SELECT 
            id,
            slug,
            COALESCE(updated_at, created_at) AS fecha_mod
        FROM cultural_events
        WHERE is_active = 1
          AND slug IS NOT NULL
          AND slug != ''
        ORDER BY COALESCE(updated_at, created_at) DESC
    ");
    $eventosEs = $stmtEs->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener todas las traducciones activas (no español)
    $stmtTrad = $pdo->query("
        SELECT 
            t.event_id,
            t.language_code,
            t.slug AS slug_trad,
            COALESCE(e.updated_at, e.created_at) AS fecha_mod_trad
        FROM cultural_events_trads t
        INNER JOIN cultural_events e ON e.id = t.event_id
        WHERE t.language_code != 'es'
          AND e.is_active = 1
          AND t.slug IS NOT NULL
          AND t.slug != ''
        ORDER BY t.event_id, t.language_code
    ");
    $traducciones = $stmtTrad->fetchAll(PDO::FETCH_ASSOC);

    // 3. Indexar traducciones por event_id para acceso rápido
    // $tradsByEventId[123] = [ 'de' => 'volksfest-...', 'en' => 'san-pedro-...' ]
    $tradsByEventId = [];
    foreach ($traducciones as $trad) {
        $tradsByEventId[$trad['event_id']][$trad['language_code']] = [
            'slug'      => $trad['slug_trad'],
            'fecha_mod' => $trad['fecha_mod_trad'],
        ];
    }

} catch (PDOException $e) {
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<!-- Error BD: ' . htmlspecialchars($e->getMessage()) . ' -->' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    exit;
}

// ============================================================
// Generar XML
// ============================================================
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<!--' . "\n";
echo '  Sitemap unificado de eventos: español + todos los idiomas.' . "\n";
echo '  Generado automáticamente desde la base de datos.' . "\n";
echo '  Generado: ' . date('Y-m-d H:i:s') . "\n";
echo '  Eventos en español: ' . count($eventosEs) . "\n";
echo '  Traducciones: ' . count($traducciones) . "\n";
echo '-->' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">

<?php
// ── BLOQUE 1: URLs en español (con hreflang a sus traducciones) ──────────────
foreach ($eventosEs as $evento):
    $id       = $evento['id'];
    $slugEs   = htmlspecialchars($evento['slug']);
    $fechaMod = !empty($evento['fecha_mod']) ? date('Y-m-d', strtotime($evento['fecha_mod'])) : $today;
    $trads    = $tradsByEventId[$id] ?? [];

    // Prioridad: eclipse tiene más relevancia
    $priority = (strpos($slugEs, 'eclipse') !== false) ? '0.9' : '0.8';
?>
  <url>
    <loc>https://www.rutasrurales.io/evento/<?= $slugEs ?></loc>
    <lastmod><?= $fechaMod ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority><?= $priority ?></priority>
    <!-- x-default: versión por defecto para usuarios sin idioma específico -->
    <xhtml:link rel="alternate" hreflang="x-default" href="https://www.rutasrurales.io/evento/<?= $slugEs ?>"/>
    <xhtml:link rel="alternate" hreflang="es"        href="https://www.rutasrurales.io/evento/<?= $slugEs ?>"/>
<?php foreach ($trads as $lang => $tradData):
    $slugTrad = htmlspecialchars($tradData['slug']);
    $langCode = htmlspecialchars($lang);
?>
    <xhtml:link rel="alternate" hreflang="<?= $langCode ?>" href="https://www.rutasrurales.io/<?= $langCode ?>/evento/<?= $slugTrad ?>"/>
<?php endforeach; ?>
  </url>

<?php endforeach; ?>

<?php
// ── BLOQUE 2: URLs en otros idiomas (con hreflang de retorno al español) ─────
foreach ($traducciones as $trad):
    $eventId  = $trad['event_id'];
    $lang     = htmlspecialchars($trad['language_code']);
    $slugTrad = htmlspecialchars($trad['slug_trad']);
    $fechaMod = !empty($trad['fecha_mod_trad']) ? date('Y-m-d', strtotime($trad['fecha_mod_trad'])) : $today;

    // Buscar el slug en español del evento padre
    $eventoEs = null;
    foreach ($eventosEs as $e) {
        if ($e['id'] == $eventId) { $eventoEs = $e; break; }
    }
    if (!$eventoEs) continue; // Seguridad: si no hay evento padre, saltar

    $slugEs   = htmlspecialchars($eventoEs['slug']);
    $trads    = $tradsByEventId[$eventId] ?? [];

    // Prioridad
    $priority = (strpos($slugTrad, 'eclipse') !== false || strpos($slugTrad, 'sonnenfinsternis') !== false) ? '0.9' : '0.7';
?>
  <url>
    <loc>https://www.rutasrurales.io/<?= $lang ?>/evento/<?= $slugTrad ?></loc>
    <lastmod><?= $fechaMod ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority><?= $priority ?></priority>
    <!-- x-default apunta siempre a la versión española (original) -->
    <xhtml:link rel="alternate" hreflang="x-default" href="https://www.rutasrurales.io/evento/<?= $slugEs ?>"/>
    <xhtml:link rel="alternate" hreflang="es"        href="https://www.rutasrurales.io/evento/<?= $slugEs ?>"/>
    <xhtml:link rel="alternate" hreflang="<?= $lang ?>" href="https://www.rutasrurales.io/<?= $lang ?>/evento/<?= $slugTrad ?>"/>
<?php // Añadir también los otros idiomas disponibles para este evento
foreach ($trads as $otroLang => $otraTrad):
    if ($otroLang === $trad['language_code']) continue; // No repetir el idioma actual
    $otroSlug = htmlspecialchars($otraTrad['slug']);
    $otroLangCode = htmlspecialchars($otroLang);
?>
    <xhtml:link rel="alternate" hreflang="<?= $otroLangCode ?>" href="https://www.rutasrurales.io/<?= $otroLangCode ?>/evento/<?= $otroSlug ?>"/>
<?php endforeach; ?>
  </url>

<?php endforeach; ?>
</urlset>
