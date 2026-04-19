<?php 
include 'db.php';
$id = isset($_GET['id']) ? $_GET['id'] : die("ID no proporcionado.");
$stmt = $pdo->prepare("SELECT * FROM tourist_activities WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { die("Actividad no encontrada."); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Editar: <?= htmlspecialchars($item['name']) ?></title>
    <style>
        .tab-content { border: 1px solid #dee2e6; border-top: none; background: #fff; padding: 25px; border-radius: 0 0 .5rem .5rem; }
        .nav-link { cursor: pointer; font-weight: 500; }
        .form-label { font-weight: bold; color: #444; margin-top: 10px; }
        .section-title { border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color: #0d6efd; }
    </style>
</head>
<body class="bg-light p-4">

<?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert" style="max-width: 1200px; margin: 0 auto 20px;">
    <strong>✅ ¡Guardado exitoso!</strong> Los cambios se han guardado correctamente.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="container-fluid" style="max-width: 1200px;">
    <form action="guardar_actividad.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $item['id'] ?>">
        
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
            <div>
                <a href="actividades_index.php" class="btn btn-outline-secondary btn-sm mb-2">&larr; Volver al listado</a>
                <h2 class="mb-0">Editando: <?= htmlspecialchars($item['name']) ?></h2>
                <small class="text-muted">ID: <?= $item['id'] ?> | Slug: <?= $item['slug'] ?></small>
            </div>
            <button type="submit" class="btn btn-primary btn-lg px-5 shadow">Guardar Todo</button>
        </div>

        <ul class="nav nav-tabs" id="mainTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#basico" type="button">1. General</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#logistica" type="button">2. Logística y Requisitos</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#precios" type="button">3. Precios y Reserva</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#media" type="button">4. Multimedia e Imágenes</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo" type="button">5. SEO / Visibilidad</button></li>
        </ul>

        <div class="tab-content shadow-sm mb-5">
            
            <div class="tab-pane fade show active" id="basico">
                <h4 class="section-title">Información Principal</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre de la actividad</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Slug (URL Amigable)</label>
                        <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($item['slug']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Municipio</label>
                        <input type="text" name="municipality" class="form-control" value="<?= htmlspecialchars($item['municipality']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Provincia</label>
                        <input type="text" name="province" class="form-control" value="<?= htmlspecialchars($item['province']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Punto de encuentro</label>
                        <input type="text" name="meeting_point" class="form-control" value="<?= htmlspecialchars($item['meeting_point']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Latitud</label>
                        <input type="text" name="latitude" class="form-control" value="<?= $item['latitude'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Longitud</label>
                        <input type="text" name="longitude" class="form-control" value="<?= $item['longitude'] ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-primary">Descripción Larga (HTML/SEO)</label>
                        <textarea name="description" class="form-control" rows="8"><?= htmlspecialchars($item['description']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción Corta (Extracto)</label>
                        <textarea name="short_description" class="form-control" rows="2"><?= htmlspecialchars($item['short_description']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="logistica">
                <h4 class="section-title">Detalles Técnicos</h4>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Dificultad</label>
                        <select name="difficulty_level" class="form-select">
                            <option value="">-- Seleccionar --</option>
                            <?php $niveles = ['facil', 'moderado', 'dificil', 'muy_dificil']; 
                            foreach($niveles as $n): ?>
                                <option value="<?= $n ?>" <?= $item['difficulty_level']==$n ? 'selected':'' ?>><?= ucfirst($n) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Duración (minutos)</label>
                        <input type="number" name="duration" class="form-control" value="<?= $item['duration'] ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Entorno</label>
                        <select name="indoor_outdoor" class="form-select">
                            <option value="">-- Seleccionar --</option>
                            <option value="interior" <?= $item['indoor_outdoor']=='interior'?'selected':'' ?>>Interior</option>
                            <option value="exterior" <?= $item['indoor_outdoor']=='exterior'?'selected':'' ?>>Exterior</option>
                            <option value="mixto" <?= $item['indoor_outdoor']=='mixto'?'selected':'' ?>>Mixto</option>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">Mín. Participantes</label><input type="number" name="min_participants" class="form-control" value="<?= $item['min_participants'] ?>"></div>
                    <div class="col-md-3"><label class="form-label">Máx. Participantes</label><input type="number" name="max_participants" class="form-control" value="<?= $item['max_participants'] ?>"></div>
                    <div class="col-md-3"><label class="form-label">Edad Mínima</label><input type="number" name="min_age" class="form-control" value="<?= $item['min_age'] ?>"></div>
                    <div class="col-md-3"><label class="form-label">Edad Máxima</label><input type="number" name="max_age" class="form-control" value="<?= $item['max_age'] ?>"></div>
                    
                    <div class="col-md-6">
                        <label class="form-label">¿Qué incluye?</label>
                        <textarea name="includes" class="form-control" rows="3"><?= htmlspecialchars($item['includes']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">¿Qué NO incluye?</label>
                        <textarea name="not_includes" class="form-control" rows="3"><?= htmlspecialchars($item['not_includes']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Requisitos</label>
                        <textarea name="requirements" class="form-control" rows="3"><?= htmlspecialchars($item['requirements']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">¿Qué llevar?</label>
                        <textarea name="what_to_bring" class="form-control" rows="3"><?= htmlspecialchars($item['what_to_bring']) ?></textarea>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Equipo Proporcionado</label>
                        <textarea name="provided_equipment" class="form-control" rows="3" placeholder='casco, arnés, cuerdas'><?= htmlspecialchars($item['provided_equipment']) ?></textarea>
                        <small class="text-muted">Separar por comas: casco, arnés, cuerdas</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Idiomas Disponibles</label>
                        <textarea name="languages_available" class="form-control" rows="3" placeholder='español, inglés, francés'><?= htmlspecialchars($item['languages_available']) ?></textarea>
                        <small class="text-muted">Separar por comas: español, inglés, francés</small>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Temporadas</label>
                        <textarea name="available_seasons" class="form-control" rows="2" placeholder='primavera, verano, otoño'><?= htmlspecialchars($item['available_seasons']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Días Disponibles</label>
                        <textarea name="available_days" class="form-control" rows="2" placeholder='lunes, miércoles, viernes'><?= htmlspecialchars($item['available_days']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Horario</label>
                        <textarea name="schedule" class="form-control" rows="2" placeholder='9:00-14:00, 9.00-13.00'><?= htmlspecialchars($item['schedule']) ?></textarea>
                        <small class="text-muted">Ejemplos: 9:00-14:00, 9.00-13.00</small>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Accesibilidad</label>
                        <textarea name="accessibility" class="form-control" rows="2" placeholder='Silla de ruedas, baño adaptado'><?= htmlspecialchars($item['accessibility']) ?></textarea>
                        <small class="text-muted">Características de accesibilidad separadas por comas</small>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="precios">
                <h4 class="section-title">Economía y Contacto</h4>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Precio Adulto (€)</label><input type="number" step="0.01" name="price_adult" class="form-control" value="<?= $item['price_adult'] ?>"></div>
                    <div class="col-md-4"><label class="form-label">Precio Niño (€)</label><input type="number" step="0.01" name="price_child" class="form-control" value="<?= $item['price_child'] ?>"></div>
                    <div class="col-md-4"><label class="form-label">Precio Grupo (€)</label><input type="number" step="0.01" name="price_group" class="form-control" value="<?= $item['price_group'] ?>"></div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Detalles del Precio</label>
                        <textarea name="price_details" class="form-control" rows="2"><?= htmlspecialchars($item['price_details']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Días de Antelación para Reserva</label>
                        <input type="number" name="advance_booking_days" class="form-control" value="<?= $item['advance_booking_days'] ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Proveedor / Empresa</label>
                        <input type="text" name="provider_name" class="form-control" value="<?= htmlspecialchars($item['provider_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ID del Proveedor</label>
                        <input type="number" name="provider_id" class="form-control" value="<?= $item['provider_id'] ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Teléfono de Contacto</label>
                        <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($item['contact_phone']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sitio Web</label>
                        <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($item['website']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email de Contacto</label>
                        <div class="form-control bg-light">
                            <?php 
                            // Obtener información del usuario creador
                            $user_email = 'No asignado';
                            $user_name = 'Usuario desconocido';
                            if (!empty($item['created_by'])) {
                                $user_stmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
                                $user_stmt->execute([$item['created_by']]);
                                $user = $user_stmt->fetch();
                                if ($user) {
                                    $user_email = htmlspecialchars($user['email']);
                                    $user_name = htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name']));
                                }
                            }
                            ?>
                            <small><strong>Email del perfil:</strong> <?= $user_email ?></small><br>
                            <small><strong>Usuario:</strong> <?= $user_name ?></small><br>
                            <small class="text-muted">(Se toma del perfil del usuario creador)</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">URL Reserva Directa</label>
                        <input type="text" name="booking_url" class="form-control" value="<?= htmlspecialchars($item['booking_url']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Categoría ID</label>
                        <input type="number" name="category_id" class="form-control" value="<?= $item['category_id'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subcategoría ID</label>
                        <input type="number" name="subcategory_id" class="form-control" value="<?= $item['subcategory_id'] ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-check form-switch border p-3 rounded">
                            <input type="hidden" name="booking_required" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="booking_required" value="1" <?= $item['booking_required'] ? 'checked':'' ?>>
                            <label class="ms-4 fw-bold">Requiere Reserva</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch border p-3 rounded">
                            <input type="hidden" name="pet_friendly" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="pet_friendly" value="1" <?= $item['pet_friendly'] ? 'checked':'' ?>>
                            <label class="ms-4 fw-bold">Pet Friendly</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch border p-3 rounded">
                            <input type="hidden" name="weather_dependent" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="weather_dependent" value="1" <?= $item['weather_dependent'] ? 'checked':'' ?>>
                            <label class="ms-4 fw-bold">Depende del clima</label>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-check form-switch border p-3 rounded">
                            <input type="hidden" name="suitable_for_families" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="suitable_for_families" value="1" <?= $item['suitable_for_families'] ? 'checked':'' ?>>
                            <label class="ms-4 fw-bold">Apto para Familias</label>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Política de Cancelación</label>
                        <textarea name="cancellation_policy" class="form-control" rows="2"><?= htmlspecialchars($item['cancellation_policy']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="media">
                <h4 class="section-title">Multimedia e Imágenes</h4>
                <div class="row g-3">
                    <?php for($i=1; $i<=4; $i++): ?>
                    <div class="col-md-6">
                        <label class="form-label">Foto <?= $i ?></label>
                        <input type="text" name="photo<?= $i ?>" class="form-control" value="<?= htmlspecialchars($item['photo'.$i]) ?>" placeholder="https://...">
                        <?php if($item['photo'.$i]): ?>
                            <img src="<?= $item['photo'.$i] ?>" class="mt-2 rounded" style="height: 80px;">
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                    <div class="col-12">
                        <label class="form-label">Galería de Imágenes (URLs)</label>
                        <textarea name="gallery" class="form-control" rows="3" placeholder='https://imagen1.jpg, https://imagen2.jpg'><?= htmlspecialchars($item['gallery']) ?></textarea>
                        <small class="text-muted">Separar URLs por comas: https://imagen1.jpg, https://imagen2.jpg</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Video URL (YouTube/Vimeo)</label>
                        <input type="text" name="video_url" class="form-control" value="<?= htmlspecialchars($item['video_url']) ?>">
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="seo">
                <h4 class="section-title">Configuración SEO y Estado</h4>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($item['meta_title']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="3"><?= htmlspecialchars($item['meta_description']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch border p-3 rounded bg-warning bg-opacity-10">
                            <input type="hidden" name="is_featured" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="is_featured" value="1" <?= $item['is_featured'] ? 'checked':'' ?>>
                            <label class="ms-4 fw-bold">Actividad Destacada</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch border p-3 rounded bg-success bg-opacity-10">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked':'' ?>>
                            <label class="ms-4 fw-bold">Publicado / Activo</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch border p-3 rounded bg-info bg-opacity-10">
                            <input type="hidden" name="verified" value="0">
                            <input class="form-check-input ms-1" type="checkbox" name="verified" value="1" <?= $item['verified'] ? 'checked':'' ?>>
                            <label class="ms-4 fw-bold">Verificada</label>
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