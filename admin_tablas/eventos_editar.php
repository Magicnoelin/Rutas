<?php 
include 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$fromSuggested = isset($_GET['from_suggested']) ? (int)$_GET['from_suggested'] : 0;

// Si es nuevo (id=0) y viene de sugerencia, crear con datos
if ($id === 0 && $fromSuggested > 0) {
    // Obtener datos de la sugerencia
    $stmtSug = $pdo->prepare("SELECT * FROM suggested_entities WHERE id = ?");
    $stmtSug->execute([$fromSuggested]);
    $suggestion = $stmtSug->fetch();
    
    if ($suggestion) {
        // Generar slug único
        $baseSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]/', '-', $suggestion['name'])));
        $slug = $baseSlug . '-' . time();
        
        // Obtener primera categoría válida (si no hay, usar 1 como fallback)
        try {
            $stmtCat = $pdo->query("SELECT id FROM categories_events ORDER BY id ASC LIMIT 1");
            $defaultCat = $stmtCat->fetchColumn();
            if (!$defaultCat) $defaultCat = 1;
        } catch (Exception $e) {
            $defaultCat = 1;
        }
        
        // Insertar nuevo evento con categoría válida
        $stmtInsert = $pdo->prepare("
            INSERT INTO cultural_events 
            (name, slug, category_id, municipality, province, description, short_description, 
             is_active, status, moderation_status, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'scheduled', 'draft', ?, NOW())
        ");
        $stmtInsert->execute([
            $suggestion['name'],
            $slug,
            $defaultCat,
            $suggestion['municipality'] ?? '',
            $suggestion['province'] ?? '',
            $suggestion['description'] ?? '',
            substr($suggestion['description'] ?? '', 0, 200),
            $suggestion['suggested_by'] ?? null
        ]);
        
        $newId = $pdo->lastInsertId();
        
        // Redirigir al evento recién creado
        header("Location: eventos_editar.php?id=" . $newId . "&from_suggested=" . $fromSuggested);
        exit;
    } else {
        die("Sugerencia no encontrada.");
    }
}

// Si es nuevo sin sugerencia, crear en blanco
if ($id === 0) {
    $slug = 'nuevo-evento-' . time();
    $stmtInsert = $pdo->prepare("
        INSERT INTO cultural_events 
        (name, slug, is_active, status, moderation_status, created_at)
        VALUES ('Nuevo Evento', ?, 0, 'scheduled', 'draft', NOW())
    ");
    $stmtInsert->execute([$slug]);
    
    $newId = $pdo->lastInsertId();
    header("Location: eventos_editar.php?id=" . $newId);
    exit;
}

// Obtener evento existente
$stmt = $pdo->prepare("SELECT * FROM cultural_events WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) { 
    // Si no existe, crear uno en blanco
    $slug = 'nuevo-evento-' . time();
    $stmtInsert = $pdo->prepare("
        INSERT INTO cultural_events 
        (name, slug, is_active, status, moderation_status, created_at)
        VALUES ('Nuevo Evento', ?, 0, 'scheduled', 'draft', NOW())
    ");
    $stmtInsert->execute([$slug]);
    
    $newId = $pdo->lastInsertId();
    header("Location: eventos_editar.php?id=" . $newId);
    exit;
}

// Obtener categorías
$stmtCat = $pdo->query("SELECT id, name FROM categories_events ORDER BY name ASC");
$categorias = $stmtCat->fetchAll();

// Obtener datos de sugerencia si viene de una
$suggestionData = null;
if ($fromSuggested > 0) {
    $stmtSug = $pdo->prepare("SELECT * FROM suggested_entities WHERE id = ?");
    $stmtSug->execute([$fromSuggested]);
    $suggestionData = $stmtSug->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Editar Evento: <?= htmlspecialchars($item['name']) ?></title>
    <style>
        .tab-content { border: 1px solid #dee2e6; border-top: none; background: #fff; padding: 25px; border-radius: 0 0 .5rem .5rem; }
        .nav-tabs { flex-wrap: wrap; }
        .nav-tabs .nav-link { color: #495057; font-weight: 500; }
        .nav-tabs .nav-link.active { color: #0d6efd; font-weight: bold; }
        label { margin-top: 10px; }
        .suggestion-banner { background: #e7f3ff; border-left: 4px solid #0d6efd; padding: 10px 15px; margin-bottom: 20px; }
    </style>
</head>
<body class="bg-light p-4">

<div class="container-fluid" style="max-width: 1200px;">
    
    <?php if ($suggestionData): ?>
    <div class="suggestion-banner">
        <strong><i class="fas fa-lightbulb"></i> Creado desde sugerencia:</strong> 
        <?= htmlspecialchars($suggestionData['name']) ?> 
        (Sugerencia #<?= $fromSuggested ?>)
        <a href="moderacion_fotos.php?tab=suggestions" class="btn btn-sm btn-outline-primary float-end">Volver a moderación</a>
    </div>
    <?php endif; ?>
    
    <form action="guardar_evento.php" method="POST">
        <input type="hidden" name="id" value="<?= $item['id'] ?>">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="eventos_index.php" class="btn btn-outline-secondary btn-sm mb-2">&larr; Volver al listado</a>
                <h2>Evento: <?= htmlspecialchars($item['name']) ?></h2>
            </div>
            <button type="submit" class="btn btn-success btn-lg shadow">Actualizar Evento Completo</button>
        </div>

        <ul class="nav nav-tabs" id="eventTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general" type="button">General</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cuando" type="button">Fecha/Recurrencia</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ubicacion" type="button">Ubicación</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#detalles" type="button">Detalles y Público</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tickets" type="button">Tickets y Org.</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#multimedia" type="button">Multimedia</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo" type="button">SEO/Estado</button></li>
        </ul>

        <div class="tab-content shadow-sm mb-5" id="eventTabsContent">
            
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-bold">Nombre del Evento</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold">Slug (URL)</label>
                        <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($item['slug']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold">Categoría ID</label>
                        <select name="category_id" class="form-select">
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $item['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold">Subcategoría ID</label>
                        <input type="number" name="subcategory_id" class="form-control" value="<?= $item['subcategory_id'] ?>">
                    </div>
                    <div class="col-12">
                        <label class="fw-bold">Descripción Corta</label>
                        <textarea name="short_description" class="form-control" rows="2"><?= htmlspecialchars($item['short_description']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="fw-bold">Descripción Larga</label>
                        <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($item['description']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="cuando" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-3"><label class="fw-bold">Fecha Inicio</label><input type="date" name="start_date" class="form-control" value="<?= $item['start_date'] ?>"></div>
                    <div class="col-md-3"><label class="fw-bold">Fecha Fin</label><input type="date" name="end_date" class="form-control" value="<?= $item['end_date'] ?>"></div>
                    <div class="col-md-3"><label class="fw-bold">Hora Inicio</label><input type="time" name="start_time" class="form-control" value="<?= $item['start_time'] ?>"></div>
                    <div class="col-md-3"><label class="fw-bold">Hora Fin</label><input type="time" name="end_time" class="form-control" value="<?= $item['end_time'] ?>"></div>
                    
                    <div class="col-md-4">
                        <div class="form-check form-switch border p-2 rounded mt-4">
                            <input type="hidden" name="is_recurring" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="is_recurring" value="1" <?= $item['is_recurring'] ? 'checked':'' ?>>
                            <label class="ms-2 mt-0">¿Es Recurrente?</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold">Patrón de Recurrencia</label>
                        <input type="text" name="recurrence_pattern" class="form-control" placeholder="Ej: Semanal" value="<?= htmlspecialchars($item['recurrence_pattern'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch border p-2 rounded mt-4">
                            <input type="hidden" name="all_day_event" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="all_day_event" value="1" <?= $item['all_day_event'] ? 'checked':'' ?>>
                            <label class="ms-2 mt-0">Todo el día</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="ubicacion" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-6"><label class="fw-bold">Lugar (Recinto)</label><input type="text" name="venue_name" class="form-control" value="<?= htmlspecialchars($item['venue_name']) ?>"></div>
                    <div class="col-md-6"><label class="fw-bold">Dirección</label><input type="text" name="venue_address" class="form-control" value="<?= htmlspecialchars($item['venue_address']) ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Municipio</label><input type="text" name="municipality" class="form-control" value="<?= htmlspecialchars($item['municipality']) ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Provincia</label><input type="text" name="province" class="form-control" value="<?= htmlspecialchars($item['province']) ?>"></div>
                    <div class="col-md-2"><label class="fw-bold">Latitud</label><input type="text" name="latitude" class="form-control" value="<?= $item['latitude'] ?>"></div>
                    <div class="col-md-2"><label class="fw-bold">Longitud</label><input type="text" name="longitude" class="form-control" value="<?= $item['longitude'] ?>"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="detalles" role="tabpanel">
                <div class="row g-3">
                    <div class="col-12"><label class="fw-bold">Programa del Evento</label><textarea name="program" class="form-control" rows="3"><?= htmlspecialchars($item['program']) ?></textarea></div>
                    <div class="col-md-4"><label class="fw-bold">Público Objetivo</label><textarea name="target_audience" class="form-control"><?= htmlspecialchars($item['target_audience']) ?></textarea></div>
                    <div class="col-md-4"><label class="fw-bold">Accesibilidad</label><textarea name="accessibility" class="form-control"><?= htmlspecialchars($item['accessibility']) ?></textarea></div>
                    <div class="col-md-4"><label class="fw-bold">Idiomas</label><textarea name="languages_available" class="form-control"><?= htmlspecialchars($item['languages_available']) ?></textarea></div>
                    <div class="col-md-6"><label class="fw-bold">Código de Vestimenta</label><input type="text" name="dress_code" class="form-control" value="<?= htmlspecialchars($item['dress_code']) ?>"></div>
                    <div class="col-md-6"><label class="fw-bold">Capacidad</label><input type="number" name="capacity" class="form-control" value="<?= $item['capacity'] ?>"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="tickets" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="fw-bold">¿Es Gratis?</label>
                        <select name="is_free" class="form-select">
                            <option value="1" <?= $item['is_free'] ? 'selected':'' ?>>Sí (Gratis)</option>
                            <option value="0" <?= !$item['is_free'] ? 'selected':'' ?>>De Pago</option>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="fw-bold">Precio (€)</label><input type="number" step="0.01" name="ticket_price" class="form-control" value="<?= $item['ticket_price'] ?>"></div>
                    <div class="col-md-3"><label class="fw-bold">Rango Precios</label><input type="text" name="ticket_price_range" class="form-control" value="<?= htmlspecialchars($item['ticket_price_range']) ?>"></div>
                    <div class="col-md-3">
                        <div class="form-check form-switch border p-2 rounded mt-4">
                            <input type="hidden" name="booking_required" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="booking_required" value="1" <?= $item['booking_required'] ? 'checked':'' ?>>
                            <label class="ms-2 mt-0">Reserva obligatoria</label>
                        </div>
                    </div>
                    <div class="col-md-6"><label class="fw-bold">URL Venta Entradas</label><input type="text" name="ticket_url" class="form-control" value="<?= $item['ticket_url'] ?>"></div>
                    <div class="col-md-6"><label class="fw-bold">Organizador</label><input type="text" name="organizer" class="form-control" value="<?= htmlspecialchars($item['organizer']) ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Contacto Org.</label><input type="text" name="organizer_contact" class="form-control" value="<?= htmlspecialchars($item['organizer_contact']) ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Teléfono Público</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($item['phone']) ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Email Público</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($item['email']) ?>"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="multimedia" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-6"><label class="fw-bold">Poster Principal (URL)</label><input type="text" name="poster_image" class="form-control" value="<?= $item['poster_image'] ?>"></div>
                    <div class="col-md-6"><label class="fw-bold">Imagen Galería 1 (URL)</label><input type="text" name="photo1" class="form-control" value="<?= $item['photo1'] ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Imagen 2</label><input type="text" name="photo2" class="form-control" value="<?= $item['photo2'] ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Imagen 3</label><input type="text" name="photo3" class="form-control" value="<?= $item['photo3'] ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Imagen 4</label><input type="text" name="photo4" class="form-control" value="<?= $item['photo4'] ?>"></div>
                    <div class="col-12"><label class="fw-bold">Galería (JSON/Urls)</label><textarea name="gallery" class="form-control"><?= htmlspecialchars($item['gallery']) ?></textarea></div>
                    <div class="col-md-6"><label class="fw-bold">URL Web Oficial</label><input type="text" name="website" class="form-control" value="<?= $item['website'] ?>"></div>
                    <div class="col-md-6"><label class="fw-bold">Redes Sociales (JSON)</label><textarea name="social_media" class="form-control" rows="1"><?= htmlspecialchars($item['social_media']) ?></textarea></div>
                </div>
            </div>

            <div class="tab-pane fade" id="seo" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-bold">Estado del Evento</label>
                        <select name="status" class="form-select">
                            <?php $estados = ['scheduled', 'ongoing', 'completed', 'cancelled']; 
                            foreach($estados as $e): ?>
                                <option value="<?= $e ?>" <?= $item['status']==$e ? 'selected':'' ?>><?= ucfirst($e) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch border p-2 mt-4 rounded">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked':'' ?>>
                            <label class="ms-2 mt-0">Activo</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch border p-2 mt-4 rounded">
                            <input type="hidden" name="is_featured" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="is_featured" value="1" <?= $item['is_featured'] ? 'checked':'' ?>>
                            <label class="ms-2 mt-0">Destacado</label>
                        </div>
                    </div>
                    <div class="col-12"><label class="fw-bold">Meta Title</label><input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($item['meta_title']) ?>"></div>
                    <div class="col-12"><label class="fw-bold">Meta Descripción</label><textarea name="meta_description" class="form-control" rows="2"><?= htmlspecialchars($item['meta_description']) ?></textarea></div>
                    <div class="col-md-4"><label class="fw-bold">Contador de Vistas</label><input type="number" name="views_count" class="form-control" value="<?= $item['views_count'] ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Contador Interesados</label><input type="number" name="interested_count" class="form-control" value="<?= $item['interested_count'] ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Video URL</label><input type="text" name="video_url" class="form-control" value="<?= $item['video_url'] ?>"></div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
