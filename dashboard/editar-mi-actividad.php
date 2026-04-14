<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once '../api/config.php'; 
$pdo = getDBConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensaje = "";

// PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $campos = [
        'name', 'short_description', 'description', 'municipality', 'province', 
        'meeting_point', 'latitude', 'longitude', 'duration', 'difficulty_level',
        'min_age', 'max_age', 'min_participants', 'max_participants',
        'price_adult', 'price_child', 'price_group', 'price_details',
        'includes', 'not_includes', 'what_to_bring', 'provided_equipment',
        'schedule', 'available_seasons', 'languages_available',
        'accessibility', 'suitable_for_families', 'pet_friendly', 'indoor_outdoor',
        'weather_dependent', 'booking_required', 'advance_booking_days',
        'cancellation_policy', 'contact_phone', 'contact_email', 'website', 'booking_url',
        'meta_title', 'meta_description'
    ];

    $setPart = [];
    $values = [];
    
    foreach ($campos as $campo) {
        $setPart[] = "$campo = ?";
        $values[] = $_POST[$campo] ?? '';
    }
    
    // Manejar available_days por separado para evitar error de restricción
    // Si está vacío, no lo actualizamos para mantener el valor actual
    $available_days = $_POST['available_days'] ?? '';
    if ($available_days !== '') {
        $setPart[] = "available_days = ?";
        $values[] = $available_days;
    }

    $values[] = $id;

    try {
        $sql = "UPDATE tourist_activities SET " . implode(', ', $setPart) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $mensaje = "<div class='alert alert-success shadow fixed-top m-3'>✅ Cambios guardados correctamente.</div>";
    } catch (Exception $e) {
        $mensaje = "<div class='alert alert-danger shadow fixed-top m-3'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

// CONSULTAR DATOS
$stmt = $pdo->prepare("SELECT * FROM tourist_activities WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { die("Actividad no encontrada."); }

// Decodificar campos JSON
$schedule = json_decode($item['schedule'] ?? '{}', true) ?: [];
$seasons = json_decode($item['available_seasons'] ?? '[]', true) ?: [];
$days = json_decode($item['available_days'] ?? '[]', true) ?: [];
$languages = json_decode($item['languages_available'] ?? '[]', true) ?: [];
$accessibility = json_decode($item['accessibility'] ?? '[]', true) ?: [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editor - <?= htmlspecialchars($item['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
    body { background-color: #f0f4f8; padding-bottom: 80px; }
    .card-editor { border: none; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
    
    /* Verde Corporativo */
    .amenity-box:checked + .amenity-label { 
        background-color:  #246634 ; color: white; border-color:  #246634 ; transform: scale(1.05);
    }
    .nav-pills .nav-link.active { background-color:  #246634  !important; shadow: 0 4px 10px rgba(40, 167, 69, 0.3); }
    .btn-primary, .btn-success { background-color:  #246634  !important; border-color:  #246634  !important; }
    .text-primary { color:  #246634  !important; }
    
    .amenity-box { display: none; }
    .amenity-label { 
        cursor: pointer; padding: 10px 15px; border: 2px solid #dee2e6; border-radius: 10px;
        transition: all 0.3s; display: block; text-align: center; height: 100%; font-size: 0.9rem;
    }
    .bloqueado { background-color: #f8f9fa !important; color: #adb5bd; cursor: not-allowed; }
</style>
</head>
<body class="p-3 p-md-5">
<div class="container shadow bg-white card-editor p-4">
    <?= $mensaje ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h2 class="fw-bold text-dark mb-0">🎯 Editar Mi Actividad</h2>
        <a href="/gestion-fotos-actividades.html?slug=<?= $item['slug'] ?>" class="btn btn-primary btn-lg mt-3 mt-md-0 shadow-sm">
            <i class="bi bi-images me-2"></i> Gestionar Fotos
        </a>
    </div>

    <form method="POST">
        <ul class="nav nav-pills nav-justified mb-4 gap-2" id="pills-tab" role="tablist">
            <li class="nav-item"><button class="nav-link active shadow-sm" data-bs-toggle="pill" data-bs-target="#tab-general" type="button">1. Información</button></li>
            <li class="nav-item"><button class="nav-link shadow-sm" data-bs-toggle="pill" data-bs-target="#tab-datos" type="button">2. Datos Actividad</button></li>
            <li class="nav-item"><button class="nav-link shadow-sm" data-bs-toggle="pill" data-bs-target="#tab-precios" type="button">3. Precios</button></li>
            <li class="nav-item"><button class="nav-link shadow-sm" data-bs-toggle="pill" data-bs-target="#tab-contacto" type="button">4. Contacto</button></li>
        </ul>

        <div class="tab-content mt-4" id="pills-tabContent">
            
            <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nombre de la Actividad</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Dificultad</label>
                        <select name="difficulty_level" class="form-select border-success">
                            <option value="fácil" <?= $item['difficulty_level'] == 'fácil' ? 'selected' : '' ?>>Fácil</option>
                            <option value="moderada" <?= $item['difficulty_level'] == 'moderada' ? 'selected' : '' ?>>Moderada</option>
                            <option value="difícil" <?= $item['difficulty_level'] == 'difícil' ? 'selected' : '' ?>>Difícil</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Resumen Corto (SEO)</label>
                        <input type="text" name="short_description" class="form-control" value="<?= htmlspecialchars($item['short_description'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Duración</label>
                        <input type="text" name="duration" class="form-control" placeholder="Ej: 3 horas, 1 día" value="<?= htmlspecialchars($item['duration'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea name="description" class="form-control" rows="6"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-datos" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-12 mt-3 border-bottom pb-2 text-success fw-bold">📍 Ubicación</div>
                    
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Municipio</label>
                        <input type="text" name="municipality" class="form-control" value="<?= htmlspecialchars($item['municipality'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Provincia</label>
                        <input type="text" name="province" class="form-control" value="<?= htmlspecialchars($item['province'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Punto de Encuentro</label>
                        <input type="text" name="meeting_point" class="form-control" value="<?= htmlspecialchars($item['meeting_point'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Latitud</label>
                        <input type="text" name="latitude" class="form-control" value="<?= $item['latitude'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Longitud</label>
                        <input type="text" name="longitude" class="form-control" value="<?= $item['longitude'] ?>">
                    </div>
                    
                    <div class="col-md-12 mt-4 border-bottom pb-2 text-success fw-bold">👥 Participantes</div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Edad Mínima</label>
                        <input type="number" name="min_age" class="form-control" value="<?= $item['min_age'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Edad Máxima</label>
                        <input type="number" name="max_age" class="form-control" value="<?= $item['max_age'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Mín. Participantes</label>
                        <input type="number" name="min_participants" class="form-control" value="<?= $item['min_participants'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Máx. Participantes</label>
                        <input type="number" name="max_participants" class="form-control" value="<?= $item['max_participants'] ?>">
                    </div>
                    
                    <div class="col-md-12 mt-4 border-bottom pb-2 text-success fw-bold">📋 Información Adicional</div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Incluye</label>
                        <textarea name="includes" class="form-control" rows="3"><?= htmlspecialchars($item['includes'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">No Incluye</label>
                        <textarea name="not_includes" class="form-control" rows="3"><?= htmlspecialchars($item['not_includes'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Qué Llevar</label>
                        <textarea name="what_to_bring" class="form-control" rows="3"><?= htmlspecialchars($item['what_to_bring'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Equipo Proporcionado</label>
                        <textarea name="provided_equipment" class="form-control" rows="3"><?= htmlspecialchars($item['provided_equipment'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-precios" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-success">Precio Adulto (€)</label>
                        <input type="text" name="price_adult" class="form-control" value="<?= $item['price_adult'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Precio Niño (€)</label>
                        <input type="text" name="price_child" class="form-control" value="<?= $item['price_child'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Precio Grupo (€)</label>
                        <input type="text" name="price_group" class="form-control" value="<?= $item['price_group'] ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Detalles de Precios</label>
                        <textarea name="price_details" class="form-control" rows="2" placeholder="Ej: Precios por persona, grupos de más de 10 personas..."><?= htmlspecialchars($item['price_details'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="col-md-12 mt-4 border-bottom pb-2 text-success fw-bold">📅 Disponibilidad</div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Temporadas Disponibles</label>
                        <input type="text" name="available_seasons" class="form-control" placeholder="Ej: Primavera, Verano, Otoño" value="<?= htmlspecialchars($item['available_seasons'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Días de la Semana</label>
                        <input type="text" name="available_days" class="form-control" placeholder="Ej: Lunes a Viernes" value="<?= htmlspecialchars($item['available_days'] ?? '') ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Horario</label>
                        <input type="text" name="schedule" class="form-control" placeholder="Ej: 9:00 - 14:00" value="<?= htmlspecialchars($item['schedule'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-12 mt-4 border-bottom pb-2 text-success fw-bold">🔍 SEO</div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Meta Título</label>
                        <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($item['meta_title'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Meta Descripción</label>
                        <input type="text" name="meta_description" class="form-control" value="<?= htmlspecialchars($item['meta_description'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-contacto" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Teléfono</label>
                        <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($item['contact_phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($item['contact_email'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Web</label>
                        <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($item['website'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">URL de Reserva</label>
                        <input type="text" name="booking_url" class="form-control" value="<?= htmlspecialchars($item['booking_url'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Reserva Requerida</label>
                        <select name="booking_required" class="form-select">
                            <option value="0" <?= $item['booking_required'] == 0 ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= $item['booking_required'] == 1 ? 'selected' : '' ?>>Sí</option>
                        </select>
                    </div>
                    <div class="col-md-12 mt-4 border-bottom pb-2 text-success fw-bold">📝 Política de Cancelación</div>
                    <div class="col-12">
                        <textarea name="cancellation_policy" class="form-control" rows="3" placeholder="Ej: Cancelación gratuita hasta 48 horas antes..."><?= htmlspecialchars($item['cancellation_policy'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-5 text-end">
            <a href="/user-dashboard.html#mis-actividades" class="btn btn-secondary btn-lg me-2">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <button type="submit" name="guardar" class="btn btn-success btn-lg px-5 py-3 shadow-lg fw-bold text-uppercase">
                <i class="bi bi-save me-2"></i> Guardar Todo
            </button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>