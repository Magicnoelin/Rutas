<?php
/**
 * GENERADOR AUTOMÁTICO DE SITEMAP DE RUTAS TEMÁTICAS
 * sitemap-rutas.php — rutasrurales.io
 *
 * Indexa SOLO las rutas vigentes y futuras:
 *   - status = 'published'
 *   - is_public = 1
 *
 * Genera: sitemap-rutas.xml
 * Uso:    Ejecutar manualmente o via cron para regenerar el sitemap.
 */

chdir(__DIR__);

// ── CONFIGURACIÓN ────────────────────────────────────────────────────────────
$host    = 'localhost';
$dbname  = 'u412199647_Rutas';
$username = 'u412199647_olgamarin';
$password = 'Rutas5Rurales7$';
$baseUrl  = 'https://rutasrurales.io';
$outputFile = __DIR__ . '/sitemap-rutas.xml';

// ── CONEXIÓN ─────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $msg = "❌ Error de conexión: " . $e->getMessage();
    if (php_sapi_name() === 'cli') {
        echo $msg . "\n";
    } else {
        echo "<p>$msg</p>";
    }
    exit(1);
}

// ── CONSULTA: solo rutas publicadas y públicas ────────────────────────────────
// "Vigentes y futuras" = publicadas (status=published) y visibles al público.
// Las rutas temáticas no tienen fecha de caducidad propia; se excluyen
// borradores (draft) y archivadas (archived).
try {
    $stmt = $pdo->query("
        SELECT
            slug,
            updated_at,
            created_at,
            name,
            province,
            season
        FROM routes
        WHERE status    = 'published'
          AND is_public = 1
          AND slug IS NOT NULL
          AND slug != ''
        ORDER BY updated_at DESC
    ");
    $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $msg = "❌ Error al consultar la tabla routes: " . $e->getMessage();
    if (php_sapi_name() === 'cli') {
        echo $msg . "\n";
    } else {
        echo "<p>$msg</p>";
    }
    exit(1);
}

// ── GENERACIÓN DEL XML ────────────────────────────────────────────────────────
$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<!-- Sitemap de Rutas Temáticas — rutasrurales.io -->' . "\n";
$xml .= '<!-- Generado: ' . date('Y-m-d H:i:s') . ' -->' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
$xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
$xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
$xml .= '          http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

$contador = 0;

foreach ($rutas as $ruta) {
    $slug    = htmlspecialchars($ruta['slug'], ENT_XML1, 'UTF-8');
    $lastmod = !empty($ruta['updated_at'])
        ? date('Y-m-d', strtotime($ruta['updated_at']))
        : (!empty($ruta['created_at']) ? date('Y-m-d', strtotime($ruta['created_at'])) : date('Y-m-d'));

    $xml .= "  <url>\n";
    $xml .= "    <loc>{$baseUrl}/rutas/{$slug}</loc>\n";
    $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
    $xml .= "    <changefreq>weekly</changefreq>\n";
    $xml .= "    <priority>0.9</priority>\n";
    $xml .= "  </url>\n";
    $contador++;
}

$xml .= '</urlset>';

// ── ESCRITURA DEL ARCHIVO ─────────────────────────────────────────────────────
$ok = file_put_contents($outputFile, $xml);

// ── SALIDA ────────────────────────────────────────────────────────────────────
$isCli = (php_sapi_name() === 'cli');

if ($ok !== false) {
    $msg = "✅ sitemap-rutas.xml generado con {$contador} ruta(s) vigente(s).";
    echo $isCli ? $msg . "\n" : "<p>$msg</p>";
} else {
    $msg = "❌ Error al escribir sitemap-rutas.xml. Verifica permisos de escritura.";
    echo $isCli ? $msg . "\n" : "<p>$msg</p>";
    exit(1);
}

// ── ACTUALIZAR SITEMAP ÍNDICE MAESTRO ─────────────────────────────────────────
// Regenera sitemap.xml para incluir sitemap-rutas.xml si no está ya incluido.
$sitemapIndex = __DIR__ . '/sitemap.xml';

if (file_exists($sitemapIndex)) {
    $contenido = file_get_contents($sitemapIndex);

    if (strpos($contenido, 'sitemap-rutas.xml') === false) {
        // Insertar la entrada de rutas antes del cierre </sitemapindex>
        $entrada  = "\n  <sitemap>\n";
        $entrada .= "    <loc>{$baseUrl}/sitemap-rutas.xml</loc>\n";
        $entrada .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        $entrada .= "  </sitemap>";

        $contenido = str_replace('</sitemapindex>', $entrada . "\n</sitemapindex>", $contenido);

        if (file_put_contents($sitemapIndex, $contenido)) {
            $msg2 = "✅ sitemap.xml actualizado: se añadió el enlace a sitemap-rutas.xml.";
        } else {
            $msg2 = "⚠️  No se pudo actualizar sitemap.xml. Añade manualmente el enlace a sitemap-rutas.xml.";
        }
        echo $isCli ? $msg2 . "\n" : "<p>$msg2</p>";
    } else {
        $msg2 = "ℹ️  sitemap.xml ya contiene el enlace a sitemap-rutas.xml. No se modificó.";
        echo $isCli ? $msg2 . "\n" : "<p>$msg2</p>";
    }
} else {
    $msg2 = "⚠️  No se encontró sitemap.xml. Ejecuta primero actualizar-sitemap.php para generar el índice maestro.";
    echo $isCli ? $msg2 . "\n" : "<p>$msg2</p>";
}
