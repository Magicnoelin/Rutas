<?php
/**
 * GENERADOR DE SITEMAP i18n DE EVENTOS CULTURALES
 * generar-sitemap-eventos-i18n.php — rutasrurales.io
 *
 * Delegador: invoca el generador canónico ubicado en admin_tablas/cron/
 * para garantizar que siempre se usa la misma lógica, tanto al ejecutarlo
 * manualmente (por URL o CLI) como desde el botón del panel de administración.
 *
 * Generador canónico: admin_tablas/cron/regenerar_sitemap_i18n.php
 *
 * Formato generado (óptimo para Google Search Console):
 *   - Una <url> por cada versión de idioma de cada evento
 *   - Cada <url> incluye xhtml:link hreflang para TODOS los idiomas (es, en, fr, de, zh-Hans)
 *   - Incluye x-default apuntando a la URL en español
 *   - Solo eventos vigentes y futuros (end_date >= HOY o start_date >= HOY)
 */

chdir(__DIR__);

// Definir la constante para que el generador canónico sepa que lo llama este script
// y no suprima la salida (aquí sí queremos ver el resultado).
// No definimos REGENERAR_SITEMAP_DESDE_ADMIN para que sí imprima.

$generador = __DIR__ . '/admin_tablas/cron/regenerar_sitemap_i18n.php';

if (!file_exists($generador)) {
    $msg = "❌ No se encontró el generador canónico en: {$generador}";
    $isCli = (php_sapi_name() === 'cli');
    echo $isCli ? strip_tags($msg) . "\n" : "<p>$msg</p>";
    exit(1);
}

require $generador;
