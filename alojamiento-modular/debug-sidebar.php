<?php
/**
 * DEBUG TEMPORAL - Ver todos los campos de un alojamiento
 * Subir al servidor, visitar, luego BORRAR
 * URL: /alojamiento-modular/debug-sidebar.php?slug=casa-enrique
 */
define('API_NO_HEADERS', true);
require_once '../api/config.php';

$slug = $_GET['slug'] ?? 'casa-enrique';

try {
    $pdo = getDBConnection();
    
    // Ver columnas de la tabla
    $cols = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll(PDO::FETCH_ASSOC);
    
    // Ver datos del alojamiento
    $stmt = $pdo->prepare("SELECT * FROM accommodations WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Debug Sidebar - <?php echo htmlspecialchars($slug); ?></title>
<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
h2 { color: #2F5233; }
table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
th { background: #2F5233; color: white; padding: 8px 12px; text-align: left; }
td { padding: 6px 12px; border-bottom: 1px solid #ddd; background: white; }
td:first-child { font-weight: bold; color: #555; width: 200px; }
.empty { color: #ccc; font-style: italic; }
.has-value { color: #2F5233; font-weight: bold; }
</style>
</head>
<body>
<h2>🔍 Debug: Alojamiento "<?php echo htmlspecialchars($slug); ?>"</h2>

<?php if (!$row): ?>
<p style="color:red">❌ No se encontró el alojamiento con slug: <strong><?php echo htmlspecialchars($slug); ?></strong></p>
<?php else: ?>

<h3>📋 Campos relevantes para el sidebar:</h3>
<table>
<tr><th>Campo</th><th>Valor</th></tr>
<?php
$campos_importantes = [
    'id', 'name', 'slug', 'is_active',
    'capacity', 'max_guests', 'guests', 'num_guests',
    'check_in_time', 'checkin_time', 'check_in', 'checkin',
    'check_out_time', 'checkout_time', 'check_out', 'checkout',
    'municipality', 'city', 'town', 'village',
    'province', 'region', 'state',
    'phone', 'telephone', 'mobile',
    'email', 'website', 'url',
    'price_per_night', 'price', 'nightly_rate',
    'latitude', 'longitude',
    'accommodation_type', 'category_id',
    'address',
];

foreach ($campos_importantes as $campo) {
    if (array_key_exists($campo, $row)) {
        $val = $row[$campo];
        $empty = ($val === null || $val === '' || $val === '0' || $val === 0);
        echo '<tr>';
        echo '<td>' . htmlspecialchars($campo) . '</td>';
        echo '<td class="' . ($empty ? 'empty' : 'has-value') . '">';
        echo $empty ? '(vacío/null)' : htmlspecialchars((string)$val);
        echo '</td>';
        echo '</tr>';
    }
}
?>
</table>

<h3>📦 TODOS los campos del alojamiento:</h3>
<table>
<tr><th>Campo</th><th>Valor</th></tr>
<?php foreach ($row as $k => $v): ?>
<tr>
    <td><?php echo htmlspecialchars($k); ?></td>
    <td class="<?php echo ($v === null || $v === '') ? 'empty' : 'has-value'; ?>">
        <?php 
        if ($v === null) echo '(NULL)';
        elseif ($v === '') echo '(cadena vacía)';
        else echo htmlspecialchars(substr((string)$v, 0, 200));
        ?>
    </td>
</tr>
<?php endforeach; ?>
</table>

<?php endif; ?>

<p style="color:red;margin-top:30px;"><strong>⚠️ BORRAR ESTE ARCHIVO DEL SERVIDOR DESPUÉS DE USARLO</strong></p>
</body>
</html>
