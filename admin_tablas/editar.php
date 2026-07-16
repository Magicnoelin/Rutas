<?php 
include 'db.php';
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM accommodations WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { die("Alojamiento no encontrado."); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <title>Editor Maestro: <?= htmlspecialchars($item['name'] ?? 'Alojamiento') ?></title>
    <style>
        .tab-content { border: 1px solid #dee2e6; border-top: none; background: #fff; padding: 30px; border-radius: 0 0 .5rem .5rem; min-height: 450px; }
        .nav-tabs .nav-link { color: #555; cursor: pointer; }
        .nav-tabs .nav-link.active { font-weight: bold; color: #0d6efd !important; border-top: 3px solid #0d6efd; }
        .seo-preview { background: #fff; border: 1px solid #dadce0; border-radius: 8px; padding: 15px; max-width: 600px; font-family: arial, sans-serif; }
        .seo-title { color: #1a0dab; font-size: 20px; margin-bottom: 3px; }
        .seo-url { color: #006621; font-size: 14px; margin-bottom: 3px; }
        .seo-desc { color: #545454; font-size: 14px; line-height: 1.58; }
        .section-title { border-left: 4px solid #0d6efd; padding-left: 10px; margin: 30px 0 20px 0; color: #333; font-weight: bold; }
    </style>
</head>
<body class="bg-light p-4">

<div class="container pb-5">
    <form action="guardar.php" method="POST">
        <input type="hidden" name="id" value="<?= $item['id'] ?>">
        
        <!-- CABECERA PRINCIPAL -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Volver</a>
                <h2 class="h4">Ficha Técnica: <span class="text-primary"><?= htmlspecialchars($item['name'] ?? '') ?></span></h2>
                <div class="text-muted small">
                    ID: <strong><?= $item['id'] ?></strong> | UUID/Ext: <strong><?= htmlspecialchars($item['external_id'] ?? 'N/A') ?></strong> | Token Público: <strong><?= htmlspecialchars($item['token_publico'] ?? 'N/A') ?></strong>
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-lg shadow-sm px-5">
                <i class="bi bi-save"></i> Guardar Todo
            </button>
        </div>

        <!-- PESTAÑAS -->
        <ul class="nav nav-tabs" id="mainTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#textos-panel" type="button">📝 Contenido y SEO</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#fotos-panel" type="button">🖼️ Galería e Imagenes (20)</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#config-panel" type="button">⚙️ Datos Técnicos y Control</button></li>
        </ul>

        <div class="tab-content shadow-sm">
            
            <!-- PANEL 1: CONTENIDO Y SEO -->
            <div class="tab-pane fade show active" id="textos-panel">
                
                <h5 class="section-title mt-0">Textos Principales</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Nombre del Alojamiento</label>
                        <input type="text" name="name" class="form-control form-control-lg" value="<?= htmlspecialchars($item['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Slug (URL Amigable)</label>
                        <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($item['slug'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Número de Registro Turístico</label>
                        <input type="text" name="registration_number" class="form-control" value="<?= htmlspecialchars($item['registration_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold small">Breve Descripción (Fichas/Tarjetas)</label>
                        <input type="text" name="short_description" class="form-control" value="<?= htmlspecialchars($item['short_description'] ?? '') ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Descripción Larga (HTML / Detalle Web)</label>
                        <textarea name="description" class="form-control" rows="8"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold text-muted small">Descripción Enlazada / Interna (Linked)</label>
                        <textarea name="description_linked" class="form-control" rows="4"><?= htmlspecialchars($item['description_linked'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="p-4 bg-light rounded border mt-4">
                    <h6 class="text-uppercase text-muted fw-bold mb-3 small"><i class="bi bi-google"></i> Configuración SEO Google</h6>
                    <div class="row">
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Meta Título</label>
                                <input type="text" name="meta_title" id="in_title" class="form-control" value="<?= htmlspecialchars($item['meta_title'] ?? '') ?>" oninput="updatePreview()">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Palabras Clave (Keywords)</label>
                                <input type="text" name="keywords" class="form-control" value="<?= htmlspecialchars($item['keywords'] ?? '') ?>" placeholder="ej. rural, piscina, montaña">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Meta Descripción</label>
                                <textarea name="meta_description" id="in_desc" class="form-control" rows="3" oninput="updatePreview()"><?= htmlspecialchars($item['meta_description'] ?? '') ?></textarea>
                                <div id="count" class="small text-muted text-end mt-1">0 / 160</div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="seo-preview shadow-sm mt-3">
                                <div class="seo-title" id="out_title">...</div>
                                <div class="seo-url">rutasrurales.io › <?= htmlspecialchars($item['slug'] ?? 'url-slug') ?></div>
                                <div class="seo-desc" id="out_desc">...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-primary"><i class="bi bi-journal-text"></i> Notas Públicas</label>
                        <textarea name="public_notes" class="form-control" rows="3" placeholder="Información visible para usuarios..."><?= htmlspecialchars($item['public_notes'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-danger"><i class="bi bi-lock"></i> Notas Privadas (Uso Interno)</label>
                        <textarea name="private_notes" class="form-control" rows="3" placeholder="Detalles de administración, incidencias..."><?= htmlspecialchars($item['private_notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- PANEL 2: GALERÍA Y MULTIMEDIA -->
            <div class="tab-pane fade" id="fotos-panel">
                <h5 class="section-title mt-0">Canales Multimedia</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small"><i class="bi bi-youtube"></i> Enlace de Vídeo (YouTube/Vimeo)</label>
                        <input type="text" name="video_url" class="form-control" value="<?= htmlspecialchars($item['video_url'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small"><i class="bi bi-vr"></i> Enlace de Tour Virtual 3D</label>
                        <input type="text" name="virtual_tour_url" class="form-control" value="<?= htmlspecialchars($item['virtual_tour_url'] ?? '') ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold small">Galería JSON o Texto (Extra / `gallery` campo largo)</label>
                        <textarea name="gallery" class="form-control" rows="2" placeholder="URLs adicionales separadas por comas o JSON..."><?= htmlspecialchars($item['gallery'] ?? '') ?></textarea>
                    </div>
                </div>

                <h5 class="section-title">Fotos del Alojamiento (Foto 1 a Foto 20)</h5>
                <div class="row g-4">
                    <!-- Renderizamos las 20 fotos usando un bucle PHP limpio -->
                    <?php for($i=1; $i<=20; $i++): $f = "photo$i"; ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="p-3 border rounded bg-light h-100 d-flex flex-column justify-content-between">
                            <div>
                                <label class="form-label fw-bold small text-secondary">
                                    <?= $i === 1 ? '🌟 Foto Principal (Foto 1)' : "Foto $i" ?>
                                </label>
                                <input type="text" name="<?= $f ?>" class="form-control form-control-sm mb-2" value="<?= htmlspecialchars($item[$f] ?? '') ?>" placeholder="https://dominio.com/imagen.jpg">
                            </div>
                            <div class="text-center mt-2 bg-white rounded border d-flex align-items-center justify-content-center" style="height: 120px; overflow: hidden;">
                                <?php if(!empty($item[$f])): ?>
                                    <img src="<?= htmlspecialchars($item[$f]) ?>" class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">
                                <?php else: ?>
                                    <span class="text-muted small"><i class="bi bi-image text-muted fs-3"></i><br>Sin imagen</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- PANEL 3: DATOS TÉCNICOS, LOGÍSTICA Y CONTROL -->
            <div class="tab-pane fade" id="config-panel">
                
                <!-- SECCIÓN: CATEGORIZACIÓN -->
                <h5 class="section-title mt-0">Categoría y Tipo</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">ID Categoría</label>
                        <input type="number" name="category_id" class="form-control" value="<?= $item['category_id'] ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">ID Subcategoría</label>
                        <input type="number" name="subcategory_id" class="form-control" value="<?= $item['subcategory_id'] ?? '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tipo de Alojamiento (Texto)</label>
                        <input type="text" name="accommodation_type" class="form-control" value="<?= htmlspecialchars($item['accommodation_type'] ?? '') ?>" placeholder="ej. Casa Rural, Hotel, Albergue">
                    </div>
                </div>

                <!-- SECCIÓN: UBICACIÓN -->
                <h5 class="section-title">Ubicación y Geolocalización</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-5">
                        <label class="form-label fw-bold small">Dirección</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($item['address'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Municipio</label>
                        <input type="text" name="municipality" class="form-control" value="<?= htmlspecialchars($item['municipality'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">Provincia</label>
                        <input type="text" name="province" class="form-control" value="<?= htmlspecialchars($item['province'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">C. Postal</label>
                        <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($item['postal_code'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted"><i class="bi bi-geo"></i> Latitud</label>
                        <input type="text" name="latitude" class="form-control" value="<?= $item['latitude'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted"><i class="bi bi-geo"></i> Longitud</label>
                        <input type="text" name="longitude" class="form-control" value="<?= $item['longitude'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-danger"><i class="bi bi-map"></i> Google Maps URL</label>
                        <input type="text" name="google_maps_url" class="form-control" value="<?= htmlspecialchars($item['google_maps_url'] ?? '') ?>">
                    </div>
                </div>

                <!-- SECCIÓN: CAPACIDAD Y DISTRIBUCIÓN -->
                <h5 class="section-title">Capacidad y Habitaciones</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Capacidad Máxima (Pax)</label>
                        <input type="number" name="capacity" class="form-control" value="<?= $item['capacity'] ?? 0 ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Dormitorios</label>
                        <input type="number" name="bedrooms" class="form-control" value="<?= $item['bedrooms'] ?? 0 ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Baños</label>
                        <input type="number" name="bathrooms" class="form-control" value="<?= $item['bathrooms'] ?? 0 ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Mínimo de noches</label>
                        <input type="number" name="min_nights" class="form-control" value="<?= $item['min_nights'] ?? 1 ?>">
                    </div>
                </div>

                <!-- SECCIÓN: PRECIOS -->
                <h5 class="section-title">Precios y Tarifas (€)</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-success">Precio por Noche</label>
                        <input type="number" step="0.01" name="price_per_night" class="form-control" value="<?= $item['price_per_night'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Precio Fin de Semana</label>
                        <input type="number" step="0.01" name="price_weekend" class="form-control" value="<?= $item['price_weekend'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Precio por Semana completa</label>
                        <input type="number" step="0.01" name="price_week" class="form-control" value="<?= $item['price_week'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Detalles de Tarifas (Texto)</label>
                        <input type="text" name="price_details" class="form-control" value="<?= htmlspecialchars($item['price_details'] ?? '') ?>" placeholder="ej: Fianza 100€, suplementos...">
                    </div>
                </div>

                <!-- SECCIÓN: CARACTERÍSTICAS Y COMODIDADES -->
                <h5 class="section-title">Comodidades y Normas</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold small">Lista de Amenities (Texto largo/JSON)</label>
                        <textarea name="amenities" class="form-control" rows="2" placeholder="Wifi, Piscina, Calefacción, Chimenea..."><?= htmlspecialchars($item['amenities'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 border rounded bg-light">
                            <label class="form-label fw-bold small d-block mb-1">Mascotas Permitidas</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pet_friendly" id="pet_yes" value="1" <?= ($item['pet_friendly'] == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="pet_yes">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="pet_friendly" id="pet_no" value="0" <?= ($item['pet_friendly'] == 0) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="pet_no">No</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 border rounded bg-light">
                            <label class="form-label fw-bold small d-block mb-1">Permitido Fumar</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="smoking_allowed" id="smoke_yes" value="1" <?= ($item['smoking_allowed'] == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="smoke_yes">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="smoking_allowed" id="smoke_no" value="0" <?= ($item['smoking_allowed'] == 0) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="smoke_no">No</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 border rounded bg-light">
                            <label class="form-label fw-bold small d-block mb-1">Apto para Niños</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="suitable_for_children" id="child_yes" value="1" <?= ($item['suitable_for_children'] == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="child_yes">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="suitable_for_children" id="child_no" value="0" <?= ($item['suitable_for_children'] == 0) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="child_no">No</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 border rounded bg-light">
                            <label class="form-label fw-bold small d-block mb-1">Cocina Disponible</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="kitchen_available" id="kitchen_yes" value="1" <?= ($item['kitchen_available'] == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="kitchen_yes">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="kitchen_available" id="kitchen_no" value="0" <?= ($item['kitchen_available'] == 0) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="kitchen_no">No</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 border rounded bg-light">
                            <label class="form-label fw-bold small d-block mb-1">Accesible a Minusválidos</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="has_accessibility" id="access_yes" value="1" <?= ($item['has_accessibility'] == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="access_yes">Sí</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="has_accessibility" id="access_no" value="0" <?= ($item['has_accessibility'] == 0) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="access_no">No</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Detalles de Accesibilidad</label>
                        <input type="text" name="accessibility_details" class="form-control" value="<?= htmlspecialchars($item['accessibility_details'] ?? '') ?>" placeholder="Rampa, baño adaptado...">
                    </div>
                </div>

                <!-- SECCIÓN: CONTACTO Y GESTIÓN -->
                <h5 class="section-title">Gestión de Contacto e Integración Externa</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Nombre del Gestor</label>
                        <input type="text" name="manager_name" class="form-control" value="<?= htmlspecialchars($item['manager_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Apodo/Alias del Gestor</label>
                        <input type="text" name="manager_nickname" class="form-control" value="<?= htmlspecialchars($item['manager_nickname'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Teléfono 1</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($item['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Teléfono 2</label>
                        <input type="text" name="phone2" class="form-control" value="<?= htmlspecialchars($item['phone2'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Email del Alojamiento</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($item['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Sitio Web</label>
                        <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($item['website'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Estado del Contacto</label>
                        <input type="text" name="contact_status" class="form-control" value="<?= htmlspecialchars($item['contact_status'] ?? '') ?>" placeholder="ej. Activo, Pendiente...">
                    </div>
                </div>

                <!-- SECCIÓN: REDES SOCIALES Y ENLACES DE RESERVAS -->
                <h5 class="section-title">Enlaces Sociales y Canales de Venta Directos</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-primary"><i class="bi bi-facebook"></i> Facebook URL</label>
                        <input type="text" name="facebook_url" class="form-control" value="<?= htmlspecialchars($item['facebook_url'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-danger"><i class="bi bi-instagram"></i> Instagram URL</label>
                        <input type="text" name="instagram_url" class="form-control" value="<?= htmlspecialchars($item['instagram_url'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted"><i class="bi bi-person-badge"></i> Instagram Usuario</label>
                        <input type="text" name="instagram_user" class="form-control" value="<?= htmlspecialchars($item['instagram_user'] ?? '') ?>" placeholder="@nombre_usuario">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-success"><i class="bi bi-house-up"></i> Airbnb URL</label>
                        <input type="text" name="airbnb_url" class="form-control" value="<?= htmlspecialchars($item['airbnb_url'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-info"><i class="bi bi-journal-bookmark"></i> Booking URL</label>
                        <input type="text" name="booking_url" class="form-control" value="<?= htmlspecialchars($item['booking_url'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-secondary"><i class="bi bi-calendar-check"></i> Booking ID / Widget info</label>
                        <input type="text" name="booking" class="form-control" value="<?= htmlspecialchars($item['booking'] ?? '') ?>">
                    </div>
                </div>

                <!-- SECCIÓN: ADYACENCIA E INFORMACIÓN DEL ENTORNO -->
                <h5 class="section-title">Entorno y Alrededores</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold small">Eventos Cercanos o Puntos de Interés (`near_events`)</label>
                        <textarea name="near_events" class="form-control" rows="2" placeholder="Fiestas locales, monumentos cercanos..."><?= htmlspecialchars($item['near_events'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- SECCIÓN: CONTROL INTERNO, ESTADOS Y AUDITORÍA -->
                <h5 class="section-title text-muted">Datos de Control Interno (Moderación, Estadísticas e Histórico)</h5>
                <div class="row g-3 mb-4 p-3 bg-light rounded border">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Estado de Moderación</label>
                        <select name="moderation_status" class="form-select">
                            <option value="draft" <?= ($item['moderation_status'] == 'draft') ? 'selected' : '' ?>>Draft (Borrador)</option>
                            <option value="pending" <?= ($item['moderation_status'] == 'pending') ? 'selected' : '' ?>>Pending (Pendiente)</option>
                            <option value="approved" <?= ($item['moderation_status'] == 'approved') ? 'selected' : '' ?>>Approved (Aprobado)</option>
                            <option value="rejected" <?= ($item['moderation_status'] == 'rejected') ? 'selected' : '' ?>>Rejected (Rechazado)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">¿Tiene cambios pendientes?</label>
                        <select name="has_pending_changes" class="form-select">
                            <option value="1" <?= ($item['has_pending_changes'] == 1) ? 'selected' : '' ?>>Sí</option>
                            <option value="0" <?= ($item['has_pending_changes'] == 0) ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Revisado Por (ID Admin)</label>
                        <input type="number" name="reviewed_by" class="form-control text-muted" value="<?= $item['reviewed_by'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Fecha de Revisión</label>
                        <input type="text" name="reviewed_at" class="form-control text-muted" value="<?= $item['reviewed_at'] ?? '' ?>" disabled>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold small text-danger">Razón de Rechazo (si aplica)</label>
                        <textarea name="rejection_reason" class="form-control" rows="2"><?= htmlspecialchars($item['rejection_reason'] ?? '') ?></textarea>
                    </div>
                    
                    <hr class="my-3">

                    <div class="col-md-3">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= ($item['is_active'] == 1) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold small" for="isActive">¿Está Activo?</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="isFeatured" <?= ($item['is_featured'] == 1) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold small text-primary" for="isFeatured">⭐️ ¿Es Destacado (Featured)?</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_premium" value="1" id="isPremium" <?= ($item['is_premium'] == 1) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold small text-warning" for="isPremium">👑 ¿Suscripción Premium?</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_verified" value="1" id="isVerified" <?= ($item['is_verified'] == 1) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold small text-success" for="isVerified">✅ ¿Verificado?</label>
                        </div>
                    </div>

                    <!-- POSICIÓN DESTACADA TEMPORAL (herramienta comercial) -->
                    <div class="col-12 mt-3">
                        <div class="p-3 border rounded" style="background:#fff8e1;border-color:#ffe082!important;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span style="font-size:1.2rem;">⭐</span>
                                <strong class="text-warning" style="color:#e65100!important;">Posición Destacada en Eventos Cercanos</strong>
                            </div>
                            <p class="text-muted small mb-2">
                                Cuando esté activo y la fecha no haya caducado, este alojamiento aparecerá <strong>primero</strong> en la sección "Alojamientos cercanos" de las páginas de eventos. Se desactiva automáticamente al llegar a la fecha indicada.
                            </p>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold small" for="featured_until">📅 Destacado hasta (fecha y hora)</label>
                                    <input type="datetime-local" name="featured_until" id="featured_until" class="form-control"
                                        value="<?= !empty($item['featured_until']) ? date('Y-m-d\TH:i', strtotime($item['featured_until'])) : '' ?>">
                                    <div class="form-text">Dejar vacío para desactivar el destacado.</div>
                                </div>
                                <div class="col-md-7">
                                    <?php
                                    $fu = $item['featured_until'] ?? null;
                                    if ($fu && strtotime($fu) > time()):
                                    ?>
                                    <div class="alert alert-warning py-2 px-3 mb-0" style="font-size:0.82rem;">
                                        ⭐ <strong>Activo ahora</strong> — Destacado hasta el
                                        <strong><?= date('d/m/Y H:i', strtotime($fu)) ?></strong>
                                        (<?= round((strtotime($fu) - time()) / 3600, 1) ?> h restantes)
                                    </div>
                                    <?php elseif ($fu): ?>
                                    <div class="alert alert-secondary py-2 px-3 mb-0" style="font-size:0.82rem;">
                                        ⏱ <strong>Caducado</strong> — La fecha de destacado ya pasó (<?= date('d/m/Y H:i', strtotime($fu)) ?>). Actualiza la fecha para reactivarlo.
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-light py-2 px-3 mb-0" style="font-size:0.82rem;border:1px solid #dee2e6;">
                                        Sin posición destacada activa.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mt-3">
                        <label class="form-label small text-muted">Nivel de Suscripción</label>
                        <input type="number" name="suscripcion_nivel" class="form-control" value="<?= $item['suscripcion_nivel'] ?? 1 ?>">
                    </div>
                    <div class="col-md-3 mt-3">
                        <label class="form-label small text-muted">Creado Por (ID Usuario)</label>
                        <input type="number" name="created_by" class="form-control" value="<?= $item['created_by'] ?? '' ?>">
                    </div>
                    <div class="col-md-3 mt-3">
                        <label class="form-label small text-muted">Visitas totales</label>
                        <input type="number" class="form-control" value="<?= $item['views_count'] ?? 0 ?>" disabled>
                    </div>
                    <div class="col-md-3 mt-3">
                        <label class="form-label small text-muted">Reservas acumuladas</label>
                        <input type="number" class="form-control" value="<?= $item['bookings_count'] ?? 0 ?>" disabled>
                    </div>

                    <div class="col-md-4 mt-3">
                        <label class="form-label small text-muted">Rating Promedio (Avg)</label>
                        <input type="text" class="form-control" value="<?= $item['rating_avg'] ?? '0.00' ?>" disabled>
                    </div>
                    <div class="col-md-4 mt-3">
                        <label class="form-label small text-muted">Número de Opiniones (Reviews)</label>
                        <input type="number" class="form-control" value="<?= $item['reviews_count'] ?? 0 ?>" disabled>
                    </div>
                    <div class="col-md-4 mt-3">
                        <label class="form-label small text-muted">Contraseña Hash (Solo Lectura/Sistema)</label>
                        <input type="text" class="form-control text-muted small" value="<?= htmlspecialchars($item['password_hash'] ?? 'N/A') ?>" disabled>
                    </div>

                    <div class="col-md-4 mt-3">
                        <label class="form-label small text-muted">Fecha Publicación</label>
                        <input type="text" class="form-control text-muted" value="<?= $item['published_at'] ?? 'No publicado' ?>" disabled>
                    </div>
                    <div class="col-md-4 mt-3">
                        <label class="form-label small text-muted">Último Envío (Submit)</label>
                        <input type="text" class="form-control text-muted" value="<?= $item['last_submitted_at'] ?? 'N/A' ?>" disabled>
                    </div>
                    <div class="col-md-4 mt-3">
                        <label class="form-label small text-muted">Verificado el</label>
                        <input type="text" class="form-control text-muted" value="<?= $item['verified_at'] ?? 'N/A' ?>" disabled>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="form-label small text-muted">Fecha Creación</label>
                        <input type="text" class="form-control text-muted" value="<?= $item['created_at'] ?? '' ?>" disabled>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="form-label small text-muted">Última Modificación (Updated)</label>
                        <input type="text" class="form-control text-muted" value="<?= $item['updated_at'] ?? '' ?>" disabled>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updatePreview() {
    const t = document.getElementById('in_title').value;
    const d = document.getElementById('in_desc').value;
    document.getElementById('out_title').innerText = t || 'Título...';
    document.getElementById('out_desc').innerText = d || 'Descripción para Google...';
    document.getElementById('count').innerText = d.length + ' / 160';
}
updatePreview();
</script>
</body>
</html>