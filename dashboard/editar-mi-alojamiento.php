<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once '../api/config.php'; 
$pdo = getDBConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 119;
$mensaje = "";

// PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $campos = [
        'name', 'registration_number', 'category_id', 'accommodation_type',
        'description', 'short_description', 'address', 'municipality', 'province', 'postal_code',
        'latitude', 'longitude', 'phone', 'email', 'booking', 'bedrooms', 'bathrooms', 
        'price_per_night', 'min_nights', 'meta_title', 'meta_description'
    ];

    $setPart = [];
    $values = [];
    
    foreach ($campos as $campo) {
        $setPart[] = "$campo = ?";
        $values[] = $_POST[$campo] ?? '';
    }

    // GESTIÓN DE AMENITIES (Los checkboxes del formulario)
    $amenities_seleccionados = $_POST['amenities_list'] ?? [];
    $setPart[] = "amenities = ?";
    $values[] = json_encode(array_values($amenities_seleccionados), JSON_UNESCAPED_UNICODE);

    $values[] = $id;

    try {
        $sql = "UPDATE accommodations SET " . implode(', ', $setPart) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $mensaje = "<div class='alert alert-success shadow fixed-top m-3'>✅ Cambios guardados correctamente.</div>";
    } catch (Exception $e) {
        $mensaje = "<div class='alert alert-danger shadow fixed-top m-3'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

// CONSULTAR DATOS
$stmt = $pdo->prepare("SELECT * FROM accommodations WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { die("Alojamiento no encontrado."); }

// Decodificar amenities actuales para marcarlos en el formulario
$current_amenities = json_decode($item['amenities'] ?? '[]', true) ?: [];

// Lista de servicios para los botones
$servicios_disponibles = ['Piscina', 'Wifi', 'Barbacoa', 'Aire Acondicionado', 'Calefacción', 'Aparcamiento', 'Cocina Equipada', 'Lavadora', 'TV', 'Admite Mascotas', 'Jardín', 'Vistas'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editor Pro - <?= htmlspecialchars($item['name']) ?></title>
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
        <h2 class="fw-bold text-dark mb-0">🏠 Editar Mi Alojamiento</h2>
        <a href="gestion-fotos-v2.html?slug=<?= $item['slug'] ?>" class="btn btn-primary btn-lg mt-3 mt-md-0 shadow-sm">
            <i class="bi bi-images me-2"></i> Ordenar Fotos
        </a>
    </div>

    <form method="POST">
        <ul class="nav nav-pills nav-justified mb-4 gap-2" id="pills-tab" role="tablist">
            <li class="nav-item"><button class="nav-link active shadow-sm" data-bs-toggle="pill" data-bs-target="#tab-general" type="button">1. Información</button></li>
            <li class="nav-item"><button class="nav-link shadow-sm" data-bs-toggle="pill" data-bs-target="#tab-servicios" type="button">2. Servicios</button></li>
            <li class="nav-item"><button class="nav-link shadow-sm" data-bs-toggle="pill" data-bs-target="#tab-precios" type="button">3. Precios y Ubicación</button></li>
            <li class="nav-item"><button class="nav-link shadow-sm" data-bs-toggle="pill" data-bs-target="#tab-contacto" type="button">4. Contacto</button></li>
        </ul>

        <div class="tab-content mt-4" id="pills-tabContent">
            
            <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nombre del Alojamiento</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Categoría</label>
                        <select name="category_id" class="form-select border-success">
                            <option value="1" <?= $item['category_id'] == 1 ? 'selected' : '' ?>>Casa Rural</option>
                            <option value="2" <?= $item['category_id'] == 2 ? 'selected' : '' ?>>Apartamento</option>
                            <option value="3" <?= $item['category_id'] == 3 ? 'selected' : '' ?>>Piso turístico</option>
                            <option value="4" <?= $item['category_id'] == 4 ? 'selected' : '' ?>>Chalé</option>
                            <option value="5" <?= $item['category_id'] == 5 ? 'selected' : '' ?>>Hotel Rural</option>
                            <option value="6" <?= $item['category_id'] == 6 ? 'selected' : '' ?>>Hostal</option>
                            <option value="7" <?= $item['category_id'] == 7 ? 'selected' : '' ?>>Albergue</option>
                            <option value="8" <?= $item['category_id'] == 8 ? 'selected' : '' ?>>Glamping</option>
                            <option value="9" <?= $item['category_id'] == 9 ? 'selected' : '' ?>>Otro</option>
                            <option value="10" <?= $item['category_id'] == 10 ? 'selected' : '' ?>>VUT</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nº Registro</label>
                        <input type="text" name="registration_number" class="form-control" value="<?= htmlspecialchars($item['registration_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Resumen Corto (SEO)</label>
                        <input type="text" name="short_description" class="form-control" value="<?= htmlspecialchars($item['short_description'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Descripción para la Web</label>
                        <textarea name="description" class="form-control" rows="6"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-servicios" role="tabpanel">
                <h5 class="mb-3 fw-bold text-success">Selecciona los servicios disponibles:</h5>
                <div class="row g-2">
                    <?php foreach ($servicios_disponibles as $index => $servicio): ?>
                    <div class="col-6 col-md-3 col-lg-2">
                        <input type="checkbox" name="amenities_list[]" value="<?= $servicio ?>" id="serv_<?= $index ?>" class="amenity-box" <?= in_array($servicio, $current_amenities) ? 'checked' : '' ?>>
                        <label for="serv_<?= $index ?>" class="amenity-label shadow-sm">
                            <i class="bi bi-check2-circle d-block h4"></i>
                            <?= $servicio ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-precios" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-success">Precio / Noche (€)</label>
                        <input type="text" name="price_per_night" class="form-control" value="<?= $item['price_per_night'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Mín. Noches</label>
                        <input type="number" name="min_nights" class="form-control" value="<?= $item['min_nights'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Capacidad (Protegido)</label>
                        <input type="text" class="form-control bloqueado" value="<?= $item['capacity'] ?> personas" readonly>
                    </div>
                    
                    <div class="col-md-12 mt-4 border-bottom pb-2 text-success fw-bold">📍 Ubicación Exacta</div>
                    
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Dirección Completa</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($item['address'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Municipio</label>
                        <input type="text" name="municipality" class="form-control" value="<?= htmlspecialchars($item['municipality'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Provincia</label>
                        <input type="text" name="province" class="form-control" value="<?= htmlspecialchars($item['province'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Código Postal</label>
                        <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($item['postal_code'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Latitud</label>
                        <input type="text" name="latitude" class="form-control" value="<?= $item['latitude'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Longitud</label>
                        <input type="text" name="longitude" class="form-control" value="<?= $item['longitude'] ?>">
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-contacto" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Teléfono Principal</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($item['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Teléfono 2</label>
                        <input type="text" name="phone2" class="form-control" value="<?= htmlspecialchars($item['phone2'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($item['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Página Web (URL)</label>
                        <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($item['website'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Instagram URL</label>
                        <input type="text" name="instagram" class="form-control" value="<?= htmlspecialchars($item['instagram'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-12 mt-4 border-bottom pb-2 text-success fw-bold">🔍 SEO y Multimedia</div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Vídeo URL</label>
                        <input type="text" name="video_url" class="form-control" value="<?= htmlspecialchars($item['video_url'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tour Virtual URL</label>
                        <input type="text" name="virtual_tour_url" class="form-control" value="<?= htmlspecialchars($item['virtual_tour_url'] ?? '') ?>">
                    </div>
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
        </div> <div class="mt-5 text-end">
            <button type="submit" name="guardar" class="btn btn-success btn-lg px-5 py-3 shadow-lg fw-bold text-uppercase">
                <i class="bi bi-save me-2"></i> Guardar Todo
            </button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>