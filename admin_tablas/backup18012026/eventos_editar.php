<?php 
include 'db.php';
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM cultural_events WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { die("Evento no encontrado."); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Editar Evento: <?= htmlspecialchars($item['name']) ?></title>
    <style>
        .tab-content { border: 1px solid #dee2e6; border-top: none; background: #fff; padding: 25px; border-radius: 0 0 .5rem .5rem; }
    </style>
</head>
<body class="bg-light p-4">

<div class="container">
    <form action="guardar_evento.php" method="POST">
        <input type="hidden" name="id" value="<?= $item['id'] ?>">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="eventos_index.php" class="btn btn-outline-secondary btn-sm mb-2">&larr; Volver</a>
                <h2>Evento: <?= htmlspecialchars($item['name']) ?></h2>
            </div>
            <button type="submit" class="btn btn-success btn-lg shadow">Actualizar Evento</button>
        </div>

        <ul class="nav nav-tabs" id="eventTabs">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general">Información</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cuándo">Fecha y Hora</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tickets">Tickets y Contacto</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo">Multimedia y SEO</button></li>
        </ul>

        <div class="tab-content shadow-sm mb-5">
            <div class="tab-pane fade show active" id="general">
                <div class="row g-3">
                    <div class="col-md-8"><label class="fw-bold">Nombre del Evento</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>"></div>
                    <div class="col-md-4">
                        <label class="fw-bold">Estado</label>
                        <select name="status" class="form-select">
                            <?php $estados = ['scheduled', 'ongoing', 'completed', 'cancelled']; 
                            foreach($estados as $e): ?>
                                <option value="<?= $e ?>" <?= $item['status']==$e ? 'selected':'' ?>><?= ucfirst($e) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="fw-bold">Lugar (Recinto)</label><input type="text" name="venue_name" class="form-control" value="<?= htmlspecialchars($item['venue_name']) ?>"></div>
                    <div class="col-md-6"><label class="fw-bold">Dirección</label><input type="text" name="venue_address" class="form-control" value="<?= htmlspecialchars($item['venue_address']) ?>"></div>
                    <div class="col-12"><label class="fw-bold">Descripción Corta</label><textarea name="short_description" class="form-control" rows="2"><?= htmlspecialchars($item['short_description']) ?></textarea></div>
                </div>
            </div>

            <div class="tab-pane fade" id="cuándo">
                <div class="row g-3">
                    <div class="col-md-3"><label class="fw-bold">Fecha Inicio</label><input type="date" name="start_date" class="form-control" value="<?= $item['start_date'] ?>"></div>
                    <div class="col-md-3"><label class="fw-bold">Fecha Fin</label><input type="date" name="end_date" class="form-control" value="<?= $item['end_date'] ?>"></div>
                    <div class="col-md-3"><label class="fw-bold">Hora Inicio</label><input type="time" name="start_time" class="form-control" value="<?= $item['start_time'] ?>"></div>
                    <div class="col-md-3"><label class="fw-bold">Hora Fin</label><input type="time" name="end_time" class="form-control" value="<?= $item['end_time'] ?>"></div>
                    <div class="col-md-4 mt-4">
                        <div class="form-check form-switch border p-2 rounded">
                            <input type="hidden" name="all_day_event" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="all_day_event" value="1" <?= $item['all_day_event'] ? 'checked':'' ?>>
                            <label class="ms-2">Evento de todo el día</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tickets">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="fw-bold">¿Es Gratis?</label>
                        <select name="is_free" class="form-select">
                            <option value="1" <?= $item['is_free'] ? 'selected':'' ?>>Sí (Gratis)</option>
                            <option value="0" <?= !$item['is_free'] ? 'selected':'' ?>>De Pago</option>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="fw-bold">Precio (€)</label><input type="number" step="0.01" name="ticket_price" class="form-control" value="<?= $item['ticket_price'] ?>"></div>
                    <div class="col-md-6"><label class="fw-bold">URL Venta Entradas</label><input type="text" name="ticket_url" class="form-control" value="<?= $item['ticket_url'] ?>"></div>
                    <div class="col-md-6"><label class="fw-bold">Organizador</label><input type="text" name="organizer" class="form-control" value="<?= htmlspecialchars($item['organizer']) ?>"></div>
                    <div class="col-md-6"><label class="fw-bold">Contacto Org.</label><input type="text" name="organizer_contact" class="form-control" value="<?= htmlspecialchars($item['organizer_contact']) ?>"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="seo">
                <div class="row g-3">
                    <div class="col-12"><label class="fw-bold">Meta Title</label><input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($item['meta_title']) ?>"></div>
                    <div class="col-12"><label class="fw-bold">Meta Descripción</label><textarea name="meta_description" class="form-control" rows="2"><?= htmlspecialchars($item['meta_description']) ?></textarea></div>
                    <div class="col-md-6"><label class="fw-bold">URL Video</label><input type="text" name="video_url" class="form-control" value="<?= $item['video_url'] ?>"></div>
                    <div class="col-md-6">
                        <div class="form-check form-switch border p-2 mt-4 rounded">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked':'' ?>>
                            <label class="ms-2">Evento Activo en Web</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>