<?php
// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en producción
ini_set('log_errors', 1);

// Incluir el contenido principal
$contenidoFile = __DIR__ . '/detalle-alojamiento-contenido.php';

if (file_exists($contenidoFile)) {
    require $contenidoFile;
} else {
    // Si no se encuentra el archivo, mostrar error
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body>';
    echo '<h1>Error del Servidor</h1>';
    echo '<p>No se pudo cargar el contenido. Por favor, contacta al administrador.</p>';
    echo '</body></html>';
    exit;
}
