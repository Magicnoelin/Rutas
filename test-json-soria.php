<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Análisis de accommodations.json</h2>";

$jsonFile = 'accommodations.json';

if (!file_exists($jsonFile)) {
    echo "<p style='color: red;'>❌ El archivo $jsonFile NO existe</p>";
    exit;
}

$jsonContent = file_get_contents($jsonFile);
$data = json_decode($jsonContent, true);

if (!$data) {
    echo "<p style='color: red;'>❌ Error al parsear JSON</p>";
    exit;
}

echo "<h3>Total de alojamientos en JSON: " . count($data) . "</h3>";

// Contar por provincia
$porProvincia = [];
foreach ($data as $aloj) {
    $prov = $aloj['Provincia'] ?? $aloj['provincia'] ?? $aloj['province'] ?? $aloj['Province'] ?? 'Sin provincia';
    if (!isset($porProvincia[$prov])) {
        $porProvincia[$prov] = 0;
    }
    $porProvincia[$prov]++;
}

echo "<h3>Alojamientos por provincia:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #4CAF50; color: white;'><th>Provincia</th><th>Cantidad</th></tr>";
foreach ($porProvincia as $prov => $count) {
    $bgColor = $prov === 'Soria' ? '#ffeb3b' : '#fff';
    echo "<tr style='background: $bgColor;'><td><strong>$prov</strong></td><td><strong>$count</strong></td></tr>";
}
echo "</table>";

// Listar alojamientos de Soria
$soria = array_filter($data, function($a) {
    $prov = $a['Provincia'] ?? $a['provincia'] ?? $a['province'] ?? $a['Province'] ?? '';
    return $prov === 'Soria';
});

echo "<h3>Detalle de alojamientos de Soria en JSON:</h3>";
echo "<p><strong>Total: " . count($soria) . "</strong></p>";

if (count($soria) > 0) {
    echo "<ol>";
    foreach ($soria as $s) {
        $nombre = $s['Nombre'] ?? $s['nombre'] ?? $s['name'] ?? 'Sin nombre';
        $localidad = $s['Localidad'] ?? $s['localidad'] ?? $s['municipality'] ?? 'Sin localidad';
        echo "<li><strong>$nombre</strong> - $localidad</li>";
    }
    echo "</ol>";
} else {
    echo "<p style='color: red;'>❌ NO hay alojamientos de Soria en el JSON</p>";
}
?>
