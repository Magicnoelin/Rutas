<?php 
include 'db.php';
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM tourist_activities WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { die("Actividad no encontrada."); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Editar Actividad: <?= htmlspecialchars($item['name']) ?></title>
    <style>
        .tab-content { border: 1px solid #dee2e6; border-top: none; background: #fff; padding: 25px; border-radius: 0 0 .5rem .5rem; }
    </style>
</head>
<body class="bg-light p-4">

<div class="container">
    <form action="guardar_actividad.php" method="POST">
        <input type="hidden" name="id" value="<?= $item['id'] ?>">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="actividades_index.php" class="btn btn-outline-secondary btn-sm mb-2">&larr; Volver</a>
                <h2>Actividad: <?= htmlspecialchars($item['name']) ?></h2>
            </div>
            <button type="submit" class="btn btn-success btn-lg shadow">Guardar Cambios</button>
        </div>

        <ul class="nav nav-tabs" id="activityTabs">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#info">Información</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#logistica">Logística</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#precios">Precios</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#marketing">Multimedia/SEO</button></li>
        </ul>

        <div class="tab-content shadow-sm mb-5">
            <div class="tab-pane fade show active" id="info">
                <div class="row g-3">
                    <div class="col-md-6"><label class="fw-bold">Nombre</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>"></div>
                    <div class="col-md-6"><label class="fw-bold">Punto de encuentro</label><input type="text" name="meeting_point" class="form-control" value="<?= htmlspecialchars($item['meeting_point']) ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Municipio</label><input type="text" name="municipality" class="form-control" value="<?= htmlspecialchars($item['municipality']) ?>"></div>
                    <div class="col-md-4">
                        <label class="fw-bold">Dificultad</label>
                        <select name="difficulty_level" class="form-select">
                            <?php $niveles = ['facil', 'moderado', 'dificil', 'muy_dificil']; 
                            foreach($niveles as $n): ?>
                                <option value="<?= $n ?>" <?= $item['difficulty_level']==$n ? 'selected':'' ?>><?= ucfirst($n) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="fw-bold">Duración (min)</label><input type="number" name="duration" class="form-control" value="<?= $item['duration'] ?>"></div>
                    <div class="col-12"><label class="fw-bold">Descripción Corta</label><textarea name="short_description" class="form-control" rows="2"><?= htmlspecialchars($item['short_description']) ?></textarea></div>
                </div>
            </div>

            <div class="tab-pane fade" id="logistica">
                <div class="row g-3">
                    <div class="col-md-3"><label class="fw-bold">Min. Partic.</label><input type="number" name="min_participants" class="form-control" value="<?= $item['min_participants'] ?>"></div>
                    <div class="col-md-3"><label class="fw-bold">Max. Partic.</label><input type="number" name="max_participants" class="form-control" value="<?= $item['max_participants'] ?>"></div>
                    <div class="col-md-3"><label class="fw-bold">Edad Mínima</label><input type="number" name="min_age" class="form-control" value="<?= $item['min_age'] ?>"></div>
                    <div class="col-md-3">
                        <label class="fw-bold">Entorno</label>
                        <select name="indoor_outdoor" class="form-select">
                            <option value="interior" <?= $item['indoor_outdoor']=='interior'?'selected':'' ?>>Interior</option>
                            <option value="exterior" <?= $item['indoor_outdoor']=='exterior'?'selected':'' ?>>Exterior</option>
                            <option value="mixto" <?= $item['indoor_outdoor']=='mixto'?'selected':'' ?>>Mixto</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch border p-2 rounded">
                            <input type="hidden" name="booking_required" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="booking_required" value="1" <?= $item['booking_required'] ? 'checked':'' ?>>
                            <label class="ms-2 fw-bold">Requiere Reserva</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="precios">
                <div class="row g-3">
                    <div class="col-md-4"><label class="fw-bold">Precio Adulto (€)</label><input type="number" step="0.01" name="price_adult" class="form-control" value="<?= $item['price_adult'] ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Precio Niño (€)</label><input type="number" step="0.01" name="price_child" class="form-control" value="<?= $item['price_child'] ?>"></div>
                    <div class="col-md-4"><label class="fw-bold">Precio Grupo (€)</label><input type="number" step="0.01" name="price_group" class="form-control" value="<?= $item['price_group'] ?>"></div>
                    <div class="col-12"><label class="fw-bold">Detalles Precio</label><textarea name="price_details" class="form-control" rows="2"><?= htmlspecialchars($item['price_details']) ?></textarea></div>
                </div>
            </div>

            <div class="tab-pane fade" id="marketing">
                <div class="row g-3">
                    <div class="col-12"><label class="fw-bold">Meta Title</label><input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($item['meta_title']) ?>"></div>
                    <div class="col-md-6"><label class="fw-bold">Proveedor</label><input type="text" name="provider_name" class="form-control" value="<?= htmlspecialchars($item['provider_name']) ?>"></div>
                    <div class="col-md-6"><label class="fw-bold">Web Reserva</label><input type="text" name="booking_url" class="form-control" value="<?= $item['booking_url'] ?>"></div>
                    <div class="col-md-4">
                        <div class="form-check form-switch border p-2 rounded mt-2">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked':'' ?>>
                            <label class="ms-2">Actividad Activa</label>
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