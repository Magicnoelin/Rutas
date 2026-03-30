<?php 
// 1. CONEXIÓN A LA BASE DE DATOS 
include 'db.php'; 

// 2. INCLUIR EL MENÚ LATERAL (sidebar.php)
include 'sidebar.php'; 

// 3. REGENERAR SITEMAP i18n si se solicita manualmente
$sitemapMsg = '';
if (isset($_GET['regenerar_sitemap'])) {
    try {
        define('REGENERAR_SITEMAP_DESDE_ADMIN', true);
        include __DIR__ . '/cron/regenerar_sitemap_i18n.php';
        $sitemapMsg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <strong>Sitemap i18n regenerado correctamente.</strong> 
            Se han procesado las traducciones desde la base de datos.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } catch (Exception $e) {
        $sitemapMsg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <strong>Error al regenerar sitemap:</strong> ' . htmlspecialchars($e->getMessage()) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// Mensaje de evento actualizado
if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $sitemapMsg .= '<div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill"></i> Evento actualizado correctamente. El sitemap i18n se ha regenerado automáticamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Gestor de Eventos - Rutas Rurales</title>
</head>
<body class="bg-light">

<div class="main-content">
    <div class="container-fluid">
        
        <?= $sitemapMsg ?>
        
        <div class="d-flex justify-content-between mb-4 align-items-center">
            <h2><i class="bi bi-calendar-event"></i> Eventos Culturales</h2>
            <div class="d-flex gap-2">
                <a href="eventos_index.php?regenerar_sitemap=1" class="btn btn-outline-warning btn-sm shadow-sm" 
                   title="Regenera sitemap-eventos-i18n.xml desde la tabla de traducciones"
                   onclick="return confirm('¿Regenerar el sitemap de traducciones (i18n)?');">
                    <i class="bi bi-arrow-repeat"></i> Regenerar Sitemap i18n
                </a>
                <a href="eventos_nuevo.php" class="btn btn-success btn-sm shadow-sm">
                    <i class="bi bi-plus-lg"></i> Nuevo Evento
                </a>
            </div>
        </div>

        <div class="card shadow border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha Inicio</th>
                            <th>Evento</th>
                            <th>Categoría</th> 
                            <th>Municipio</th>
                            <th class="text-center">Publicado</th>
                            <th>Estado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // SQL ordenado por fecha de inicio y usando la columna is_active
                        $sql = "SELECT e.id, e.name, e.start_date, e.municipality, e.status, e.is_active, c.name as categoria_nombre 
                                FROM cultural_events e 
                                LEFT JOIN categories_events c ON e.category_id = c.id 
                                ORDER BY e.start_date ASC";
                        
                        $stmt = $pdo->query($sql);
                        
                        while ($row = $stmt->fetch()): ?>
                        <tr class="align-middle">
                            <td>
                                <i class="bi bi-calendar3 text-muted me-1"></i>
                                <?= date('d/m/Y', strtotime($row['start_date'])) ?>
                            </td>
                            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td>
                                <span class="badge rounded-pill bg-secondary text-white">
                                    <?= $row['categoria_nombre'] ? htmlspecialchars($row['categoria_nombre']) : 'Sin categoría' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['municipality']) ?></td>
                            
                            <td class="text-center">
                                <?php if ($row['is_active'] == 1): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-eye-fill"></i> Sí
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">
                                        <i class="bi bi-eye-slash"></i> No
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php 
                                    $badgeClass = ($row['status'] == 'cancelled') ? 'bg-danger text-white' : 'bg-info text-dark';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= ucfirst($row['status']) ?></span>
                            </td>
                            <td class="text-center">
                                <a href="eventos_editar.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>