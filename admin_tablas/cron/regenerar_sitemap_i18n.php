<?php
/**
 * Regenerador de sitemap-eventos-i18n.xml
 * 
 * Lee la tabla cultural_events_trads y genera el XML estático
 * con todas las traducciones de eventos (excepto español).
 * 
 * Se puede ejecutar:
 *   1. Desde cron (ej: cada hora o diariamente)
 *   2. Automáticamente al guardar un evento desde el admin
 *   3. Manualmente desde el navegador: /admin_tablas/cron/regenerar_sitemap_i18n.php
 * 
 * El archivo generado: /sitemap-eventos-i18n.xml
 */

// Permitir ejecución desde CLI o desde include
$esCLI = (php_sapi_name() === 'cli');
$esInclude = defined('REGENERAR_SITEMAP_DESDE_ADMIN');

// Conexión a BD (reutiliza si ya existe desde el admin)
$host   = 'localhost';
$dbname = 'u412199647_Rutas';
$user   = 'u412199647_olgamarin';
$pass   = 'Rutas5Rurales7$';

$today = date('Y-m-d');
$now   = date('Y-m-d H:i:s');

// Guardar referencia a PDO existente para no perderla
$_pdo_backup = isset($pdo) ? $pdo : null;

// Ruta del archivo XML a generar
// Desde cron/ subimos 2 niveles; desde admin_tablas/ subimos 1 nivel
$baseDir = dirname(__DIR__, 2); // Sube 2 niveles: cron -> admin_tablas -> raíz
$xmlPath = $baseDir . '/sitemap-eventos-i18n.xml';

$log = [];
$log[] = "[$now] Iniciando regeneración de sitemap-eventos-i18n.xml";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener todas las traducciones activas (no español)
    // NOTA: cultural_events_trads NO tiene updated_at/created_at, usamos las de cultural_events
    // CORRECCIÓN: Se cambió la lógica de fechas para coincidir con sitemap-eventos.php
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

    $log[] = "Traducciones encontradas: " . count($traducciones);

    // Agrupar por idioma para los comentarios del XML
    $porIdioma = [];
    foreach ($traducciones as $trad) {
        $porIdioma[$trad['language_code']][] = $trad;
    }

    // Nombres de idiomas para los comentarios
    $nombresIdiomas = [
        'de' => 'ALEMÁN',
        'en' => 'INGLÉS',
        'fr' => 'FRANCÉS',
        'zh' => 'CHINO',
    ];

    // Construir el XML
    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<!--' . "\n";
    $xml .= '  SITEMAP: Eventos Culturales - Versiones Internacionales' . "\n";
    $xml .= '  Contiene SOLO las URLs de idiomas distintos al español (de, en, fr, zh).' . "\n";
    $xml .= '  Las URLs en español están en sitemap-eventos.php' . "\n";
    $xml .= '' . "\n";
    $xml .= '  Formato de URL: /[lang]/evento/[slug-traducido]' . "\n";
    $xml .= '  Ejemplo: /de/evento/volksfest-san-pedro-zamora-2026' . "\n";
    $xml .= '' . "\n";
    $xml .= '  GENERADO AUTOMÁTICAMENTE - NO EDITAR MANUALMENTE' . "\n";
    $xml .= '  Última regeneración: ' . $now . "\n";
    $xml .= '  Total traducciones: ' . count($traducciones) . "\n";
    $xml .= '-->' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    // Generar URLs agrupadas por idioma
    foreach ($porIdioma as $lang => $trads) {
        $nombreIdioma = $nombresIdiomas[$lang] ?? strtoupper($lang);
        $count = count($trads);

        $xml .= "\n";
        $xml .= "  <!-- ============================================================\n";
        $xml .= "       {$nombreIdioma} ({$lang}) - {$count} eventos traducidos\n";
        $xml .= "       ============================================================ -->\n";

        foreach ($trads as $trad) {
            $slugTrad = htmlspecialchars($trad['slug_traducido']);
            $slugEs   = htmlspecialchars($trad['slug_original']);
            $fechaMod = !empty($trad['fecha_mod']) ? date('Y-m-d', strtotime($trad['fecha_mod'])) : $today;

            // Prioridad: eclipse tiene más relevancia
            $priority = '0.7';
            if (strpos($slugTrad, 'eclipse') !== false || strpos($slugTrad, 'sonnenfinsternis') !== false || strpos($slugTrad, 'éclipse') !== false) {
                $priority = '0.9';
            }

            $xml .= "\n";
            $xml .= "  <url>\n";
            $xml .= "    <loc>https://www.rutasrurales.io/{$lang}/evento/{$slugTrad}</loc>\n";
            $xml .= "    <lastmod>{$fechaMod}</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>{$priority}</priority>\n";
            $xml .= "    <!-- Versión original en español -->\n";
            $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"es\" href=\"https://www.rutasrurales.io/evento/{$slugEs}\"/>\n";
            $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$lang}\" href=\"https://www.rutasrurales.io/{$lang}/evento/{$slugTrad}\"/>\n";
            $xml .= "  </url>\n";
        }
    }

    // Si no hay traducciones para algún idioma, añadir comentario placeholder
    $idiomasSoportados = ['de', 'en', 'fr', 'zh'];
    foreach ($idiomasSoportados as $lang) {
        if (!isset($porIdioma[$lang])) {
            $nombreIdioma = $nombresIdiomas[$lang] ?? strtoupper($lang);
            $xml .= "\n";
            $xml .= "  <!-- ============================================================\n";
            $xml .= "       {$nombreIdioma} ({$lang}) - Sin traducciones aún\n";
            $xml .= "       ============================================================ -->\n";
        }
    }

    $xml .= "\n</urlset>\n";

    // Escribir el archivo
    $bytesWritten = file_put_contents($xmlPath, $xml);

    if ($bytesWritten === false) {
        $log[] = "ERROR: No se pudo escribir en {$xmlPath}";
    } else {
        $log[] = "OK: Archivo generado ({$bytesWritten} bytes) en {$xmlPath}";
    }

    // También actualizar el lastmod en sitemap.xml (índice principal)
    $sitemapIndexPath = $baseDir . '/sitemap.xml';
    if (file_exists($sitemapIndexPath)) {
        $sitemapContent = file_get_contents($sitemapIndexPath);
        
        // Verificar si sitemap-eventos-i18n.xml ya está en el índice
        if (strpos($sitemapContent, 'sitemap-eventos-i18n.xml') === false) {
            // No existe, agregarlo antes del cierre de </sitemapindex>
            $newEntry = "  <sitemap>\n    <loc>https://rutasrurales.io/sitemap-eventos-i18n.xml</loc>\n    <lastmod>{$today}</lastmod>\n  </sitemap>\n</sitemapindex>";
            $sitemapContent = str_replace('</sitemapindex>', $newEntry, $sitemapContent);
            $log[] = "OK: Agregado sitemap-eventos-i18n.xml al índice principal";
        } else {
            // Ya existe, actualizar solo la fecha
            $sitemapContent = preg_replace(
                '/(sitemap-eventos-i18n\.xml<\/loc>\s*<lastmod>)\d{4}-\d{2}-\d{2}(<\/lastmod>)/',
                '${1}' . $today . '${2}',
                $sitemapContent
            );
            $log[] = "OK: Actualizado lastmod de sitemap-eventos-i18n.xml en sitemap.xml";
        }
        
        // También actualizar la fecha de sitemap-eventos.php
        $sitemapContent = preg_replace(
            '/(sitemap-eventos\.php<\/loc>\s*<lastmod>)\d{4}-\d{2}-\d{2}(<\/lastmod>)/',
            '${1}' . $today . '${2}',
            $sitemapContent
        );
        $log[] = "OK: Actualizado lastmod de sitemap-eventos.php en sitemap.xml";
        
        file_put_contents($sitemapIndexPath, $sitemapContent);
    }

} catch (PDOException $e) {
    $log[] = "ERROR BD: " . $e->getMessage();
} catch (Exception $e) {
    $log[] = "ERROR: " . $e->getMessage();
}

// Restaurar la conexión PDO original si existía (para no romper el flujo del admin)
if ($_pdo_backup !== null) {
    $pdo = $_pdo_backup;
}
unset($_pdo_backup);

// Guardar log
$logPath = __DIR__ . '/cron.log';
$logEntry = implode("\n", $log) . "\n---\n";
file_put_contents($logPath, $logEntry, FILE_APPEND);

// Salida según contexto
if ($esCLI) {
    echo implode("\n", $log) . "\n";
} elseif (!$esInclude) {
    // Acceso directo por navegador
    header('Content-Type: text/plain; charset=UTF-8');
    echo implode("\n", $log) . "\n";
} 
// Si es include desde guardar_evento.php, no imprime nada
