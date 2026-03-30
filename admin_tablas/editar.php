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
    <title>Editor Maestro: <?= htmlspecialchars($item['name']) ?></title>
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
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Volver</a>
                <h2 class="h4">Ficha Técnica: <span class="text-primary"><?= htmlspecialchars($item['name']) ?></span></h2>
            </div>
            <button type="submit" class="btn btn-success btn-lg shadow-sm px-5">
                <i class="bi bi-save"></i> Guardar Todo
            </button>
        </div>

        <ul class="nav nav-tabs" id="mainTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#textos-panel" type="button">📝 Contenido y SEO</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#fotos-panel" type="button">🖼️ Galería</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#config-panel" type="button">⚙️ Datos Completos</button></li>
        </ul>

        <div class="tab-content shadow-sm">
            
            <div class="tab-pane fade show active" id="textos-panel">
                <div class="mb-4">
                    <label class="form-label fw-bold">Descripción Larga (Web)</label>
                    <textarea name="description" class="form-control" rows="10"><?= htmlspecialchars($item['description']) ?></textarea>
                </div>

                <div class="p-4 bg-light rounded border mt-4">
                    <h6 class="text-uppercase text-muted fw-bold mb-3 small">Configuración Google</h6>
                    <div class="row">
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Meta Título</label>
                                <input type="text" name="meta_title" id="in_title" class="form-control" value="<?= htmlspecialchars($item['meta_title']) ?>" oninput="updatePreview()">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Meta Descripción</label>
                                <textarea name="meta_description" id="in_desc" class="form-control" rows="3" oninput="updatePreview()"><?= htmlspecialchars($item['meta_description']) ?></textarea>
                                <div id="count" class="small text-muted text-end mt-1">0 / 160</div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="seo-preview shadow-sm mt-3">
                                <div class="seo-title" id="out_title">...</div>
                                <div class="seo-url">rutasrurales.io › <?= htmlspecialchars($item['slug']) ?></div>
                                <div class="seo-desc" id="out_desc">...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="fotos-panel">
                <div class="row g-4">
                    <?php for($i=1; $i<=4; $i++): $f = "photo$i"; ?>
                    <div class="col-md-6">
                        <div class="p-3 border rounded bg-light">
                            <label class="form-label fw-bold small">URL Foto <?= $i ?></label>
                            <input type="text" name="<?= $f ?>" class="form-control mb-2" value="<?= $item[$f] ?>">
                            <?php if($item[$f]): ?>
                                <img src="<?= $item[$f] ?>" class="rounded shadow-sm" style="width:100%; height:150px; object-fit:cover;">
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="config-panel">
                
                <h5 class="section-title mt-0">Ubicación Local</h5>
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Nombre del Alojamiento</label>
                    <input type="text" name="name" class="form-control form-control-lg" value="<?= htmlspecialchars($item['name']) ?>" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Dirección</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($item['address'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">C. Postal</label>
                        <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($item['postal_code'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">Municipio</label>
                        <input type="text" name="municipality" class="form-control" value="<?= htmlspecialchars($item['municipality'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">Provincia</label>
                        <input type="text" name="province" class="form-control" value="<?= htmlspecialchars($item['province'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Latitud</label>
                        <input type="text" name="latitude" class="form-control" value="<?= $item['latitude'] ?? '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Longitud</label>
                        <input type="text" name="longitude" class="form-control" value="<?= $item['longitude'] ?? '' ?>">
                    </div>
                </div>

                <h5 class="section-title">Capacidad y Tarifas</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Capacidad Personas</label>
                        <input type="number" name="capacity" class="form-control" value="<?= $item['capacity'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Precio / Noche (€)</label>
                        <input type="number" step="0.01" name="price_per_night" class="form-control" value="<?= $item['price_per_night'] ?>">
                    </div>
                </div>

                <h5 class="section-title">Contacto y Social Media</h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Teléfono</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($item['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Sitio Web</label>
                        <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($item['website'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-danger"><i class="bi bi-instagram"></i> Instagram (URL Perfil)</label>
                        <input type="text" name="instagram_url" class="form-control" value="<?= htmlspecialchars($item['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/nombre...">
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