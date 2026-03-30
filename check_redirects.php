<?php
/**
 * Script para verificar redirecciones y enlaces
 * Sube este archivo a tu servidor y ejecútalo: https://rutasrurales.io/check_redirects.php
 */

// Ajusta esto si tu dominio es diferente
$baseUrl = "https://rutasrurales.io"; 

// Lista de URLs para probar y su destino esperado
$pruebas = [
    // Lugares de Interés (redirect_manager.php)
    '/lugar/restaurante-santo-domingo-ii' => '/lugar/restaurante-santo-domingo-2-soria',
    '/lugares-interes/restaurante-santo-domingo-ii' => '/lugar/restaurante-santo-domingo-2-soria',
    '/lugar-interes.html?slug=restaurante-santo-domingo-ii' => '/lugar/restaurante-santo-domingo-2-soria',
    
    // Alojamientos (.htaccess)
    '/detalle.html?slug=villa-de-bejar-2' => '/alojamiento/villa-de-bejar-2',
    '/alojamiento/ca-ada-real' => '/alojamiento/canada-real',
    '/detalle.html?slug=ca-ada-real' => '/alojamiento/canada-real',
    
    // Páginas en francés (Coming Soon)
    '/fr/alojamientos-turisticos.html' => '/coming-soon.html',
    '/lugares-interes/bodegas-la-loba' => '/lugar/bodegas-la-loba',
];

echo "<!DOCTYPE html><html><head><title>Verificador de Redirecciones</title>";
echo "<style>body{font-family:sans-serif;padding:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} .ok{color:green;font-weight:bold;} .error{color:red;font-weight:bold;}</style>";
echo "</head><body>";
echo "<h1>Informe de Verificación de Redirecciones</h1>";
echo "<table>";
echo "<tr><th>URL Probada</th><th>Código HTTP</th><th>Destino Real</th><th>Destino Esperado</th><th>Resultado</th></tr>";

foreach ($pruebas as $origen => $destinoEsperado) {
    $urlCompleta = $baseUrl . $origen;
    
    $ch = curl_init($urlCompleta);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); // Incluir headers en la salida
    curl_setopt($ch, CURLOPT_NOBODY, true); // No descargar el cuerpo
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // No seguir redirecciones automáticamente
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignorar errores SSL si es local/dev
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Obtener header Location
    $location = '';
    if (preg_match('/^Location: (.+)$/mi', $response, $matches)) {
        $location = trim($matches[1]);
    }
    
    // Normalizar location (quitar dominio si es absoluto)
    $locationRelativo = str_replace($baseUrl, '', $location);
    
    // Verificar
    $esRedirect = ($httpCode >= 300 && $httpCode < 400);
    $destinoCorrecto = ($locationRelativo == $destinoEsperado || $location == $destinoEsperado);
    
    $clase = ($esRedirect && $destinoCorrecto) ? 'ok' : 'error';
    $resultado = ($esRedirect && $destinoCorrecto) ? 'CORRECTO' : 'FALLO';
    
    if (!$esRedirect) $resultado = "NO REDIRIGE ($httpCode)";
    elseif (!$destinoCorrecto) $resultado = "DESTINO INCORRECTO";

    echo "<tr class='$clase'>";
    echo "<td>$origen</td>";
    echo "<td>$httpCode</td>";
    echo "<td>" . ($location ?: '-') . "</td>";
    echo "<td>$destinoEsperado</td>";
    echo "<td>$resultado</td>";
    echo "</tr>";
    
    curl_close($ch);
}

echo "</table>";
echo "<p>Nota: Asegúrate de que \$baseUrl al inicio del script coincida con tu dominio.</p>";
echo "</body></html>";
?>