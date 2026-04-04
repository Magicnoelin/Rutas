<?php
/**
 * Script simple para ejecutar la regeneración del sitemap de eventos
 * Acceder desde: https://rutasrurales.io/ejecutar_regeneracion_sitemap.php
 */

// Incluir el script de regeneración que ya existe
require_once 'admin_tablas/cron/regenerar_sitemap_i18n.php';

// El script regenerar_sitemap_i18n.php ya genera el sitemap-eventos-i18n.xml
// y actualiza el sitemap.xml principal

echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitemap Regenerado</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; max-width: 800px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Sitemap Regenerado Correctamente</h1>
        
        <p class="success">El sitemap de eventos ha sido regenerado con la lógica corregida.</p>
        
        <h2>📋 Archivos actualizados:</h2>
        <ul>
            <li><strong>sitemap-eventos-i18n.xml</strong> - Sitemap de traducciones de eventos</li>
            <li><strong>sitemap.xml</strong> - Índice principal de sitemaps (actualizado lastmod)</li>
        </ul>
        
        <h2>🔗 Sitemaps disponibles:</h2>
        <ul>
            <li><a href="/sitemap-eventos.php" target="_blank">sitemap-eventos.php</a> (dinámico, ya corregido)</li>
            <li><a href="/sitemap-eventos.xml" target="_blank">sitemap-eventos.xml</a> (estático, versión anterior)</li>
            <li><a href="/sitemap-eventos-i18n.xml" target="_blank">sitemap-eventos-i18n.xml</a> (traducciones, recién regenerado)</li>
            <li><a href="/sitemap-eventos-idiomas.php" target="_blank">sitemap-eventos-idiomas.php</a> (dinámico de traducciones, ya corregido)</li>
        </ul>
        
        <h2>📝 Pasos para Google Search Console:</h2>
        <ol>
            <li>Acceder a <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
            <li>Seleccionar la propiedad "rutasrurales.io"</li>
            <li>Ir a "Sitemaps" en el menú lateral</li>
            <li>Si hay errores con sitemaps antiguos, eliminarlos</li>
            <li>Agregar estos sitemaps:
                <ul>
                    <li><code>https://rutasrurales.io/sitemap-eventos.php</code> (principal)</li>
                    <li><code>https://rutasrurales.io/sitemap-eventos-i18n.xml</code> (traducciones)</li>
                </ul>
            </li>
        </ol>
        
        <h2>🔄 Cron automático:</h2>
        <p>El sistema ya tiene configurado un cron que se ejecuta automáticamente para regenerar los sitemaps.</p>
        <p>Archivo de cron: <code>admin_tablas/cron/regenerar_sitemap_i18n.php</code></p>
        
        <p class="info">✅ Los sitemaps ahora solo incluyen eventos futuros/actuales y todas sus traducciones.</p>
    </div>
</body>
</html>';