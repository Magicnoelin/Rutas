<?php
// Guardamos el conteo en un archivo de texto en la misma carpeta segura
$archivo_conteo = __DIR__ . '/visitas_organismos.txt';

// Si el archivo ya existe, lee lo que tiene; si no, empieza en 0
$visitas = file_exists($archivo_conteo) ? (int)file_get_contents($archivo_conteo) : 0;

// Sumamos 1 visita
file_put_contents($archivo_conteo, $visitas + 1);

// Enviamos una imagen transparente de 1x1 píxel para que el navegador no dé error
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
exit;
?>