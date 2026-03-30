<?php
echo "<h1>Test .htaccess</h1>";

// Verificar si mod_rewrite está habilitado
if (function_exists('apache_get_modules')) {
    $mods = apache_get_modules();
    echo "<p>mod_rewrite: " . (in_array('mod_rewrite', $mods) ? "✅ Activado" : "❌ Desactivado") . "</p>";
} else {
    echo "<p>No se puede verificar mod_rewrite</p>";
}

// Verificar si .htaccess existe
$htaccess = __DIR__ . '/.htaccess';
echo "<p>.htaccess existe: " . (file_exists($htaccess) ? "✅ Sí" : "❌ No") . "</p>";

// Verificar reglas del .htaccess
if (file_exists($htaccess)) {
    $content = file_get_contents($htaccess);
    echo "<p>Contiene 'lugares-interes': " . (strpos($content, 'lugares-interes') !== false ? "✅ Sí" : "❌ No") . "</p>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
}

// Test de rewrite
echo "<h2>Test de URL amigable</h2>";
echo "<p>Si ves esta página en lugar del contenido de lugar-interes.html, el rewrite no funciona.</p>";
echo "<p>Prueba acceder directamente: <a href='/lugares-interes/test-slug'>/lugares-interes/test-slug</a></p>";
