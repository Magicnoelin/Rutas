<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../api/config.php';
$pdo = getDBConnection();
echo '<pre>';

// 1. Datos de la ruta
$r = $pdo->query("SELECT id, name, slug, province, itinerary_json FROM routes WHERE slug='puente-1-mayo-soria' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "=== RUTA ===\n";
echo "province: [" . $r['province'] . "]\n";
echo "itinerary_json: " . $r['itinerary_json'] . "\n\n";

// 2. Valores distintos de province en cultural_events
echo "=== VALORES DISTINTOS DE province EN cultural_events ===\n";
$provs = $pdo->query("SELECT DISTINCT province, COUNT(*) as total FROM cultural_events WHERE is_active=1 GROUP BY province ORDER BY total DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
foreach ($provs as $p) echo "[" . $p['province'] . "] → " . $p['total'] . " eventos\n";

// 3. Eventos con LIKE Soria sin filtro de fecha
echo "\n=== EVENTOS CON province LIKE '%oria%' (sin filtro fecha) ===\n";
$evs = $pdo->query("SELECT id, name, province, start_date, end_date FROM cultural_events WHERE province LIKE '%oria%' AND is_active=1 ORDER BY start_date LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
foreach ($evs as $e) echo "ID:{$e['id']} [{$e['province']}] {$e['start_date']} → {$e['end_date']} | {$e['name']}\n";

// 4. Eventos en abril-mayo 2026 (cualquier provincia)
echo "\n=== EVENTOS ENTRE 2026-04-28 Y 2026-05-02 (cualquier provincia) ===\n";
$evs2 = $pdo->query("SELECT id, name, province, start_date, end_date FROM cultural_events WHERE is_active=1 AND start_date <= '2026-05-02' AND COALESCE(end_date, start_date) >= '2026-04-28' ORDER BY start_date LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
foreach ($evs2 as $e) echo "ID:{$e['id']} [{$e['province']}] {$e['start_date']} → {$e['end_date']} | {$e['name']}\n";
if (empty($evs2)) echo "(ninguno)\n";

echo '</pre>';
?>
