<?php
/**
 * DISPARADOR MANUAL - Regenerar Sitemap i18n
 * 
 * URL: https://rutasurales.io/admin_tablas/regenerar_sitemap.php
 * 
 * Úsalo después de insertar/modificar traducciones en cultural_events_trads.
 * Protegido por el .htaccess/.htpasswd de admin_tablas.
 */
include 'db.php';

// Ejecutar regeneración
define('REGENERAR_SITEMAP_DESDE_ADMIN', true);
include __DIR__ . '/cron/regenerar_sitemap_i18n.php';

// Leer el log para mostrar resultado
$logLines = $log ?? [];
$ok = !empty($logLines) && !preg_grep('/^ERROR/', $logLines);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerar Sitemap i18n</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
<div class="container" style="max-width: 700px;">
    
    <h2 class="mb-4"><i class="bi bi-arrow-repeat"></i> Regenerar Sitemap i18n</h2>
    
    <?php if ($ok): ?>
    <div class="alert alert-success">
        <h5><i class="bi bi-check-circle-fill"></i> ¡Sitemap regenerado correctamente!</h5>
        <p class="mb-0">El archivo <code>sitemap-eventos-i18n.xml</code> se ha actualizado con las traducciones de la base de datos.</p>
    </div>
    <?php else: ?>
    <div class="alert alert-danger">
        <h5><i class="bi bi-exclamation-triangle-fill"></i> Hubo un problema</h5>
        <p class="mb-0">Revisa los detalles del log más abajo.</p>
    </div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <i class="bi bi-terminal"></i> Log de ejecución
        </div>
        <div class="card-body">
            <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.85rem;"><?php 
                foreach ($logLines as $line) {
                    if (strpos($line, 'ERROR') !== false) {
                        echo '<span class="text-danger">' . htmlspecialchars($line) . '</span>' . "\n";
                    } elseif (strpos($line, 'OK') !== false) {
                        echo '<span class="text-success">' . htmlspecialchars($line) . '</span>' . "\n";
                    } else {
                        echo htmlspecialchars($line) . "\n";
                    }
                }
            ?></pre>
        </div>
    </div>
    
    <div class="mt-4 d-flex gap-2">
        <a href="regenerar_sitemap.php" class="btn btn-warning">
            <i class="bi bi-arrow-repeat"></i> Volver a regenerar
        </a>
        <a href="eventos_index.php" class="btn btn-outline-primary">
            <i class="bi bi-calendar-event"></i> Ir a Eventos
        </a>
        <a href="/sitemap-eventos-i18n.xml" target="_blank" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-code"></i> Ver sitemap-eventos-i18n.xml
        </a>
    </div>
    
    <div class="mt-4 text-muted small">
        <p><strong>Tip:</strong> Guarda esta URL en favoritos para regenerar rápidamente después de insertar traducciones:</p>
        <code>https://rutasurales.io/admin_tablas/regenerar_sitemap.php</code>
    </div>
    
</div>
</body>
</html>
