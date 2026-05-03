<?php 
// 1. CONEXIÓN A LA BASE DE DATOS 
include 'db.php'; 

// 2. INCLUIR EL MENÚ LATERAL (sidebar.php)
include 'sidebar.php'; 

// 3. REGENERAR SITEMAP i18n si se solicita manualmente
$sitemapMsg = '';
if (isset($_GET['regenerar_sitemap'])) {
    if (!defined('REGENERAR_SITEMAP_DESDE_ADMIN')) {
        define('REGENERAR_SITEMAP_DESDE_ADMIN', true);
    }
    try {
        // Regenerar AMBOS sitemaps de eventos para mantener sincronía
        include __DIR__ . '/cron/regenerar_sitemap_i18n.php'; // sitemap-eventos-i18n.xml (URLs traducidas)
        include __DIR__ . '/cron/regenerar_sitemap_es.php';   // sitemap-eventos.xml (español + hreflang)
        $sitemapMsg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <strong>Sitemaps regenerados correctamente.</strong>
            <ul class="mb-0 mt-1" style="font-size:0.88rem;">
                <li><code>sitemap-eventos-i18n.xml</code> → URLs traducidas (en/fr/de/zh) con hreflang completo</li>
                <li><code>sitemap-eventos.xml</code> → URLs en español con hreflang para eventos con traducción</li>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } catch (Exception $e) {
        $sitemapMsg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <strong>Error al regenerar sitemaps:</strong> ' . htmlspecialchars($e->getMessage()) . '
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
                            <th class="text-center" style="width:60px;">Foto</th>
                            <th>Fecha Inicio</th>
                            <th>Evento</th>
                            <th>Categoría</th> 
                            <th>Municipio</th>
                            <th class="text-center">Descripción</th>
                            <th class="text-center">Publicado</th>
                            <th>Estado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // SQL ordenado por fecha de introducción (created_at) descendente
                        // Incluye description para contar caracteres
                        $sql = "SELECT e.id, e.name, e.start_date, e.municipality, e.status, e.is_active, e.description, e.created_at, e.poster_image, c.name as categoria_nombre 
                                FROM cultural_events e 
                                LEFT JOIN categories_events c ON e.category_id = c.id 
                                ORDER BY e.created_at DESC";
                        
                        $stmt = $pdo->query($sql);
                        
                        while ($row = $stmt->fetch()): ?>
                        <tr class="align-middle">
                            <td class="text-center">
                                <?php if (!empty($row['poster_image'])): ?>
                                    <img src="<?= htmlspecialchars($row['poster_image']) ?>" 
                                         alt="Thumbnail" 
                                         class="rounded" 
                                         style="width:50px; height:35px; object-fit:cover;"
                                         loading="lazy"
                                         onerror="this.style.display='none'">
                                <?php else: ?>
                                    <span class="text-muted"><i class="bi bi-image"></i></span>
                                <?php endif; ?>
                            </td>
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
                                <?php 
                                    $descLen = $row['description'] ? mb_strlen($row['description']) : 0;
                                    $badgeColor = $descLen > 500 ? 'bg-success' : ($descLen > 200 ? 'bg-warning text-dark' : 'bg-secondary');
                                ?>
                                <span class="badge <?= $badgeColor ?>" title="Caracteres de descripción general">
                                    <i class="bi bi-fonts"></i> <?= $descLen ?>
                                </span>
                            </td>

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