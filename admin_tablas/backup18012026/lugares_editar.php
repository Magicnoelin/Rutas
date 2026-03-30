<?php 
include 'db.php';
if (!isset($_GET['id']) || empty($_GET['id'])) { header("Location: lugares_index.php"); exit; }
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM places_of_interest WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { die("El lugar no existe."); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar: <?= htmlspecialchars($item['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .tab-content { border: 1px solid #dee2e6; border-top: none; background: #fff; padding: 25px; min-height: 500px; border-radius: 0 0 8px 8px; }
        .nav-tabs .nav-link { cursor: pointer; color: #555; font-weight: 500; }
        .nav-tabs .nav-link.active { border-top: 3px solid #198754 !important; color: #198754 !important; background: #fff !important; }
        .sticky-header { position: sticky; top: 0; z-index: 1000; background: #f8f9fa; padding: 10px 0; border-bottom: 1px solid #ddd; }
        .section-title { background: #f8f9fa; padding: 8px 12px; border-left: 4px solid #198754; font-weight: bold; margin: 20px 0; color: #198754; }
    </style>
</head>
<body class="bg-light px-4">

<div class="container mt-3 pb-5">
    <form action="guardar_lugar.php" method="POST">
        <input type="hidden" name="id" value="<?= $item['id'] ?>">
        
        <div class="sticky-header">
            <div class="d-flex justify-content-between align-items-center px-3">
                <div>
                    <a href="lugares_index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
                    <span class="ms-2 fw-bold text-uppercase">Editando ID #<?= $item['id'] ?></span>
                </div>
                <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm">GUARDAR CAMBIOS</button>
            </div>
        </div>

        <ul class="nav nav-tabs mt-4" id="misTabs">
            <li class="nav-item">
                <button class="nav-link active" type="button" data-bs-target="#panel-seo">📝 Contenido / SEO</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" type="button" data-bs-target="#panel-datos">⚙️ Datos Técnicos</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" type="button" data-bs-target="#panel-fotos">🖼️ Fotos</button>
            </li>
        </ul>

        <div class="tab-content shadow-sm">
            
            <div class="tab-pane fade show active" id="panel-seo">
                <div class="row g-3">
                    <div class="col-md-6"><label class="fw-bold">Nombre</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>"></div>
                    <div class="col-md-6"><label class="fw-bold text-primary">Slug (URL)</label><input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($item['slug']) ?>"></div>
                    <div class="col-12"><label class="fw-bold text-success">Meta Título</label><input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($item['meta_title'] ?? '') ?>"></div>
                    <div class="col-12"><label class="fw-bold text-success">Meta Descripción</label><textarea name="meta_description" class="form-control" rows="2"><?= htmlspecialchars($item['meta_description'] ?? '') ?></textarea></div>
                    <div class="col-12"><label class="fw-bold">Descripción Larga</label><textarea name="description" class="form-control" rows="10"><?= htmlspecialchars($item['description'] ?? '') ?></textarea></div>
                </div>
            </div>

            <div class="tab-pane fade" id="panel-datos">
                <div class="section-title">UBICACIÓN Y CONTACTO</div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="fw-bold small">Dirección</label><input type="text" name="address" class="form-control" value="<?= htmlspecialchars($item['address'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="fw-bold small">Localidad / Municipio</label><input type="text" name="municipality" class="form-control" value="<?= htmlspecialchars($item['municipality']) ?>"></div>
                    <div class="col-md-3"><label class="fw-bold small">Provincia</label><input type="text" name="province" class="form-control" value="<?= htmlspecialchars($item['province'] ?? 'Soria') ?>"></div>
                    
                    <div class="col-md-4"><label class="fw-bold small">Teléfono</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($item['phone'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="fw-bold small">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($item['email'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="fw-bold small">Sitio Web</label><input type="text" name="website" class="form-control" value="<?= htmlspecialchars($item['website'] ?? '') ?>"></div>
                </div>

                <div class="section-title">COORDENADAS Y CLASIFICACIÓN</div>
                <div class="row g-3">
                    <div class="col-md-3"><label class="fw-bold small">Latitud</label><input type="text" name="latitude" class="form-control" value="<?= $item['latitude'] ?>"></div>
                    <div class="col-md-3"><label class="fw-bold small">Longitud</label><input type="text" name="longitude" class="form-control" value="<?= $item['longitude'] ?>"></div>
                    <div class="col-md-3"><label class="fw-bold small text-danger">ID Categoría</label><input type="number" name="category_id" class="form-control" value="<?= $item['category_id'] ?>"></div>
                    <div class="col-md-3"><label class="fw-bold small text-danger">ID Subcategoría</label><input type="number" name="subcategory_id" class="form-control" value="<?= $item['subcategory_id'] ?>"></div>
                </div>
                
                <input type="hidden" name="short_description" value="<?= htmlspecialchars($item['short_description'] ?? '') ?>">
                <input type="hidden" name="postal_code" value="<?= htmlspecialchars($item['postal_code'] ?? '') ?>">
                <input type="hidden" name="keywords" value="<?= htmlspecialchars($item['keywords'] ?? '') ?>">
                <input type="hidden" name="is_active" value="<?= $item['is_active'] ?>">
            </div>

            <div class="tab-pane fade" id="panel-fotos">
                <div class="row g-4">
                    <?php for($i=1; $i<=4; $i++): $f = "photo$i"; ?>
                    <div class="col-md-6 border p-2">
                        <label class="small fw-bold">URL Foto <?= $i ?></label>
                        <input type="text" name="<?= $f ?>" class="form-control mb-2" value="<?= $item[$f] ?>">
                        <img src="<?= $item[$f] ?>" style="width:100%; height:150px; object-fit:cover;" onerror="this.src='https://via.placeholder.com/400x200'">
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const tabs = document.querySelectorAll('#misTabs button');
        const contents = document.querySelectorAll('.tab-pane');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const target = this.getAttribute('data-bs-target');

                // 1. Desactivar todos los botones
                tabs.forEach(t => t.classList.remove('active'));
                // 2. Activar el botón clicado
                this.classList.add('active');

                // 3. Ocultar todos los paneles
                contents.forEach(c => {
                    c.classList.remove('show', 'active');
                    c.style.display = 'none'; // Forzamos ocultar
                });

                // 4. Mostrar el panel destino
                const activeContent = document.querySelector(target);
                activeContent.classList.add('show', 'active');
                activeContent.style.display = 'block'; // Forzamos mostrar
            });
        });

        // Inicializar: ocultar los que no son el primero
        contents.forEach((c, index) => {
            if(index !== 0) c.style.display = 'none';
        });
    });
</script>

</body>
</html>