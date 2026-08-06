<?php 
include 'db.php'; 

// Detectar si la conexión viene en $pdo o en $conn
$db = $pdo ?? $conn ?? null;

if (!$db) {
    die("Error: No se encontró una variable de conexión válida (\$pdo o \$conn) en db.php.");
}

// 1. Captura de filtros desde el formulario (GET)
$categoria_filter = $_GET['categoria'] ?? 'ALL';
$province_filter  = $_GET['province'] ?? 'ALL';
$status_filter    = $_GET['is_active'] ?? '1'; // Por defecto solo activos

// 2. Construcción dinámica de la consulta SQL
$sql = "SELECT * FROM event_newsletter_subscribers WHERE 1=1";
$params = [];

if ($categoria_filter !== 'ALL') {
    $sql .= " AND categoria = :categoria";
    $params[':categoria'] = $categoria_filter;
}

if ($province_filter !== 'ALL') {
    $sql .= " AND province = :province";
    $params[':province'] = $province_filter;
}

if ($status_filter !== 'ALL') {
    $sql .= " AND is_active = :is_active";
    $params[':is_active'] = $status_filter;
}

$sql .= " ORDER BY subscribed_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$suscriptores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Extraer listas únicas para los desplegables
$categorias_list = $db->query("SELECT DISTINCT categoria FROM event_newsletter_subscribers WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC")->fetchAll(PDO::FETCH_COLUMN);
$provincias_list = $db->query("SELECT DISTINCT province FROM event_newsletter_subscribers WHERE province IS NOT NULL AND province != '' ORDER BY province ASC")->fetchAll(PDO::FETCH_COLUMN);

// 4. Array solo con emails de la lista filtrada (para copiar/enviar)
$emails_activos = array_column(array_filter($suscriptores, fn($s) => $s['is_active'] == 1), 'email');
$string_emails  = implode(', ', $emails_activos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Suscriptores a Eventos</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f4f6f9; margin: 20px; color: #333; }
        .card { background: #fff; border-radius: 6px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .filter-form { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        select, input, button { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        .btn { background: #007bff; color: white; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; text-decoration: none; padding: 9px 15px; font-size: 14px; border-radius: 4px; display: inline-block; }
        .btn-success:hover { background: #218838; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #dee2e6; padding: 10px; text-align: left; font-size: 14px; }
        th { background: #e9ecef; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; color: #fff; }
        .badge-active { background-color: #28a745; }
        .badge-inactive { background-color: #dc3545; }
        textarea { width: 100%; height: 70px; margin-top: 10px; font-family: monospace; font-size: 12px; box-sizing: border-box; }
    </style>
</head>
<body>

<h1>Panel de Administración - Suscriptores a Eventos</h1>

<!-- Formulario de Filtros -->
<div class="card">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label><strong>Categoría:</strong></label>
            <select name="categoria">
                <option value="ALL">-- Todas --</option>
                <?php foreach ($categorias_list as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $categoria_filter === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label><strong>Provincia:</strong></label>
            <select name="province">
                <option value="ALL">-- Todas --</option>
                <?php foreach ($provincias_list as $prov): ?>
                    <option value="<?= htmlspecialchars($prov) ?>" <?= $province_filter === $prov ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prov) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label><strong>Estado:</strong></label>
            <select name="is_active">
                <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Solo Activos</option>
                <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Solo Bajas</option>
                <option value="ALL" <?= $status_filter === 'ALL' ? 'selected' : '' ?>>Todos</option>
            </select>
        </div>

        <button type="submit" class="btn">Filtrar</button>
        <a href="?" style="padding: 8px; font-size: 13px; text-decoration: none; color: #666;">Limpiar filtros</a>
    </form>
</div>

<!-- Zona de Exportación / Envío de Email -->
<div class="card">
    <h3>Envío masivo (<?= count($emails_activos) ?> destinatarios activos filtrados)</h3>
    <?php if (!empty($string_emails)): ?>
        <a href="mailto:?bcc=<?= urlencode($string_emails) ?>" class="btn-success" style="color:white;">
            ✉️ Abrir en cliente de correo (CCO / BCC)
        </a>
    <?php endif; ?>
    <br><br>
    <label><strong>Lista de emails filtrados (para copiar y pegar):</strong></label>
    <textarea readonly id="emailsArea"><?= htmlspecialchars($string_emails) ?></textarea>
    <button class="btn" onclick="navigator.clipboard.writeText(document.getElementById('emailsArea').value); alert('¡Emails copiados al portapapeles!');">
        📋 Copiar al portapapeles
    </button>
</div>

<!-- Tabla de Registros -->
<div class="card" style="overflow-x: auto;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Categoría</th>
                <th>Provincia</th>
                <th>Evento Origen (Slug / ID)</th>
                <th>Fecha Suscripción</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($suscriptores)): ?>
                <tr><td colspan="7" style="text-align:center;">No se encontraron suscriptores con los filtros seleccionados.</td></tr>
            <?php else: ?>
                <?php foreach ($suscriptores as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><strong><?= htmlspecialchars($row['email']) ?></strong></td>
                        <td><?= htmlspecialchars($row['categoria']) ?></td>
                        <td><?= htmlspecialchars($row['province']) ?></td>
                        <td>
                            <small>
                                <?= htmlspecialchars($row['source_slug'] ?? '-') ?> 
                                <?= !empty($row['source_event_id']) ? '('.$row['source_event_id'].')' : '' ?>
                            </small>
                        </td>
                        <td><?= $row['subscribed_at'] ? date('d/m/Y H:i', strtotime($row['subscribed_at'])) : '-' ?></td>
                            <td>
                            <?php if ($row['is_active'] == 1): ?>
                                <span class="badge badge-active">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-inactive" title="Baja el: <?= $row['unsubscribed_at'] ?>">Dado de baja</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>