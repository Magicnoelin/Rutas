<?php
/**
 * Debug rápido: Ver qué HTML genera la descripción para la-pinariega
 * BORRAR después de usar
 */
require_once 'api/config.php';
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT description, description_linked FROM accommodations WHERE slug = ?");
$stmt->execute(['la-pinariega']);
$alo = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Test strip_tags + description_linked</h2>";

$allowed_tags = '<strong><b><em><i><u><p><br><ul><ol><li><h2><h3><h4><span><a>';
$desc_raw  = !empty($alo['description_linked']) ? $alo['description_linked'] : $alo['description'];
$desc_safe = strip_tags($desc_raw, $allowed_tags);

echo "<h3>HTML final que verá el visitante (RENDERED):</h3>";
echo "<div style='border:2px solid green;padding:20px;margin:20px 0;'>";
echo nl2br($desc_safe);
echo "</div>";

echo "<h3>HTML SOURCE (para verificar que el &lt;a&gt; está):</h3>";
echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto;font-size:0.8em'>";
echo htmlspecialchars(nl2br($desc_safe));
echo "</pre>";

echo "<p><strong>Usando:</strong> " . (!empty($alo['description_linked']) ? '✅ description_linked' : '❌ description (sin links)') . "</p>";
echo "<p><strong>¿Contiene &lt;a href=?</strong> " . (strpos($desc_safe, '<a href=') !== false ? '✅ SÍ' : '❌ NO') . "</p>";
