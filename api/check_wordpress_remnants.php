<?php
/**
 * Herramienta de Diagnóstico y Limpieza de WordPress
 * Accede a: https://rutasrurales.io/api/check_wordpress_remnants.php
 */

header('Content-Type: text/html; charset=utf-8');

$rootPath = realpath(__DIR__ . '/..');
$htaccessPath = $rootPath . '/.htaccess';
$indexPhpPath = $rootPath . '/index.php';

// Acción de limpieza
if (isset($_POST['action']) && $_POST['action'] === 'clean_htaccess') {
    $cleanHtaccess = <<<HTACCESS
RewriteEngine On
RewriteBase /

# Evitar listar directorios
Options -Indexes

# Forzar HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Redirecciones para Rutas Rurales (redirect_manager.php)
RewriteRule ^lugar/([^/]+)/?$ redirect_manager.php?type=lugar&slug=$1 [L,QSA]
RewriteRule ^actividad/([^/]+)/?$ redirect_manager.php?type=actividad&slug=$1 [L,QSA]
RewriteRule ^alojamiento/([^/]+)/?$ redirect_manager.php?type=alojamiento&slug=$1 [L,QSA]
RewriteRule ^evento/([^/]+)/?$ redirect_manager.php?type=evento&slug=$1 [L,QSA]

# Redirecciones de archivos HTML limpios (opcional)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^\.]+)$ $1.html [NC,L]
HTACCESS;

    file_put_contents($htaccessPath, $cleanHtaccess);
    $message = "✅ Archivo .htaccess restablecido correctamente para Rutas Rurales.";
}

// Detección de archivos
$filesToCheck = [
    'wp-config.php',
    'wp-settings.php',
    'wp-load.php',
    'wp-blog-header.php',
    'xmlrpc.php',
    'wp-login.php'
];

$foundWPFiles = [];
foreach ($filesToCheck as $file) {
    if (file_exists($rootPath . '/' . $file)) {
        $foundWPFiles[] = $file;
    }
}

$htaccessContent = file_exists($htaccessPath) ? file_get_contents($htaccessPath) : 'No existe .htaccess';
$hasWPRules = strpos($htaccessContent, 'BEGIN WordPress') !== false;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detector de Conflictos WordPress</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f4f4f4; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h1 { color: #2c3e50; }
        .danger { color: #e74c3c; font-weight: bold; }
        .success { color: #27ae60; font-weight: bold; }
        pre { background: #2d3436; color: #f1c40f; padding: 15px; overflow-x: auto; border-radius: 5px; }
        button { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #2980b9; }
    </style>
</head>
<body>
    <h1>🕵️ Diagnóstico de Conflictos</h1>

    <?php if (isset($message)): ?>
        <div class="card" style="background: #d4edda; color: #155724;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>1. Análisis de Archivos WordPress</h2>
        <?php if (!empty($foundWPFiles)): ?>
            <p class="danger">⚠️ Se han encontrado archivos de WordPress en la raíz:</p>
            <ul>
                <?php foreach ($foundWPFiles as $f): ?>
                    <li><?= $f ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Deberías eliminarlos manualmente por FTP si ya no usas WordPress.</p>
        <?php else: ?>
            <p class="success">✅ No se encontraron archivos críticos de WordPress.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>2. Análisis de .htaccess</h2>
        <?php if ($hasWPRules): ?>
            <p class="danger">⚠️ ALERTA: Se detectaron reglas de WordPress en tu .htaccess.</p>
            <p>Esto está causando que tus URLs personalizadas fallen.</p>
        <?php else: ?>
            <p class="success">✅ No parece haber bloques estándar de WordPress en el .htaccess.</p>
        <?php endif; ?>

        <h3>Contenido actual de .htaccess:</h3>
        <pre><?= htmlspecialchars($htaccessContent) ?></pre>

        <form method="POST" style="margin-top: 20px;">
            <input type="hidden" name="action" value="clean_htaccess">
            <button type="submit" onclick="return confirm('¿Seguro que quieres sobrescribir el .htaccess?')">
                🧹 Limpiar y Reparar .htaccess
            </button>
        </form>
    </div>
</body>
</html>