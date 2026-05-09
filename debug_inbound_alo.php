<?php
/**
 * DEBUG: Ver estado de inbound_links para la-pinariega
 * BORRAR después de usar
 */
require_once 'api/config.php';
$pdo = getDBConnection();

// 1. Ver datos del alojamiento
$stmt = $pdo->prepare("SELECT id, name, slug, description, description_linked FROM accommodations WHERE slug = ? LIMIT 1");
$stmt->execute(['la-pinariega']);
$alo = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Alojamiento: la-pinariega</h2>";
if (!$alo) {
    echo "<p style='color:red'>❌ NO ENCONTRADO en BD</p>";
    exit;
}

echo "<p><b>ID:</b> " . $alo['id'] . "</p>";
echo "<p><b>Name:</b> " . htmlspecialchars($alo['name']) . "</p>";

echo "<h3>description (raw):</h3>";
echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:200px'>" . htmlspecialchars($alo['description'] ?? '(vacío)') . "</pre>";

echo "<h3>description_linked (con links):</h3>";
$linked = $alo['description_linked'];
if (empty($linked)) {
    echo "<p style='color:red'>❌ description_linked está VACÍO — necesitas regenerar</p>";
} else {
    echo "<pre style='background:#e8f5e9;padding:10px;overflow:auto;max-height:200px'>" . htmlspecialchars($linked) . "</pre>";
    // Ver si contiene el link a Vinuesa
    if (stripos($linked, 'vinuesa') !== false) {
        echo "<p style='color:green'>✅ description_linked SÍ contiene 'Vinuesa'</p>";
        // Mostrar el trozo
        $pos = stripos($linked, 'vinuesa');
        echo "<pre style='background:#c8e6c9;padding:10px'>" . htmlspecialchars(substr($linked, max(0,$pos-80), 200)) . "</pre>";
    } else {
        echo "<p style='color:red'>❌ description_linked NO contiene 'Vinuesa'</p>";
    }
}

// Comprobar si description menciona Vinuesa
echo "<h3>¿'description' menciona Vinuesa?</h3>";
if (stripos($alo['description'] ?? '', 'vinuesa') !== false) {
    echo "<p style='color:green'>✅ SÍ aparece en description</p>";
} else {
    echo "<p style='color:red'>❌ NO aparece 'Vinuesa' en la description original → el link nunca se puede generar</p>";
}

// 2. Ver keywords en inbound_links
echo "<hr><h2>Keywords en tabla inbound_links:</h2>";
$stmt2 = $pdo->query("SELECT id, keyword, target_url, target_table, is_active FROM inbound_links ORDER BY keyword");
$keywords = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'><tr><th>id</th><th>keyword</th><th>target_url</th><th>target_table</th><th>is_active</th></tr>";
foreach ($keywords as $kw) {
    $highlight = stripos($kw['keyword'], 'vinuesa') !== false ? 'style="background:yellow"' : '';
    echo "<tr $highlight><td>{$kw['id']}</td><td>" . htmlspecialchars($kw['keyword']) . "</td><td>" . htmlspecialchars($kw['target_url']) . "</td><td>" . htmlspecialchars($kw['target_table']) . "</td><td>{$kw['is_active']}</td></tr>";
}
echo "</table>";

// 3. Simular procesarInboundLinks
echo "<hr><h2>Simulación procesarInboundLinks:</h2>";
require_once 'api/inbound_links_helper.php';
if (!empty($alo['description'])) {
    $resultado = procesarInboundLinks($alo['description'], $pdo);
    echo "<p><b>Resultado:</b></p>";
    echo "<pre style='background:#fff3e0;padding:10px;overflow:auto;max-height:200px'>" . htmlspecialchars($resultado) . "</pre>";
    if ($resultado !== $alo['description']) {
        echo "<p style='color:green'>✅ procesarInboundLinks SÍ modificó el texto (añadió links)</p>";
    } else {
        echo "<p style='color:orange'>⚠️ procesarInboundLinks NO modificó el texto (no encontró coincidencias)</p>";
    }
} else {
    echo "<p style='color:red'>❌ description está vacía, no se puede procesar</p>";
}
