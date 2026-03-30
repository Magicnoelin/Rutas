<?php 
include 'db.php'; 

// 1. CONFIGURACIÓN DE ERRORES
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. CARGAR CATEGORÍAS (Padres e Hijos)
try {
    $parents = $pdo->query("SELECT id, name FROM categories_events WHERE parent_id IS NULL ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $subs = $pdo->query("SELECT id, name, parent_id FROM categories_events WHERE parent_id IS NOT NULL ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $parents = [];
    $subs = [];
}

// 3. PROCESAR EL FORMULARIO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $sql = "INSERT INTO cultural_events (
            name, slug, category_id, subcategory_id, description, short_description,
            venue_name, venue_address, municipality, province, 
            start_date, end_date, start_time, end_time,
            is_free, ticket_price, ticket_url,
            meta_title, meta_description, status, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', 1)";
        
        $stmt = $pdo->prepare($sql);
        
        $name = $_POST['name'] ?: 'Evento sin nombre';
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        $stmt->execute([
            $name,
            $slug,
            $_POST['category_id'] ?: null,
            $_POST['subcategory_id'] ?: null,
            $_POST['description'] ?? null,
            $_POST['short_description'] ?? null,
            $_POST['venue_name'] ?? null,
            $_POST['venue_address'] ?? null,
            $_POST['municipality'] ?? null,
            $_POST['province'] ?? null,
            $_POST['start_date'] ?: date('Y-m-d'),
            $_POST['end_date'] ?: null,
            $_POST['start_time'] ?: '00:00:00',
            $_POST['end_time'] ?: '00:00:00',
            isset($_POST['is_free']) ? 1 : 0,
            $_POST['ticket_price'] ?: 0,
            $_POST['ticket_url'] ?? null,
            $_POST['meta_title'] ?? null,
            $_POST['meta_description'] ?? null
        ]);

        echo "<script>alert('¡Evento guardado con éxito!'); window.location.href='eventos_index.php';</script>";
        exit;
    } catch (PDOException $e) {
        $error_msg = "Error al guardar: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Eventos - Rutas Rurales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .nav-tabs .nav-link { color: #495057; font-weight: bold; }
        .nav-tabs .nav-link.active { background-color: #fff; border-bottom-color: transparent; color: #0d6efd; }
    </style>
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>✨ Nuevo Evento</h2>
        <a href="eventos_index.php" class="btn btn-outline-secondary">Volver al Listado</a>
    </div>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger shadow-sm"><?= $error_msg ?></div>
    <?php endif; ?>

    <form method="POST" class="card shadow border-0">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs nav-fill" id="eventTab" role="tablist">
                <li class="nav-item text-start">
                    <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general">1. General</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fecha">2. Fecha y Hora</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tickets">3. Tickets</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-seo">4. SEO</button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content">
                
                <div class="tab-pane fade show active" id="tab-general">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Nombre del Evento</label>
                            <input type="text" name="name" class="form-control form-control-lg" placeholder="Ej: Feria de la Trufa">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Categoría Principal</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="">Selecciona...</option>
                                <?php foreach($parents as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= $p['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Subcategoría</label>
                            <select name="subcategory_id" id="subcategory_id" class="form-select">
                                <option value="">Opcional...</option>
                                <?php foreach($subs as $s): ?>
                                    <option value="<?= $s['id'] ?>" data-parent="<?= $s['parent_id'] ?>" style="display:none;"><?= $s['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Provincia</label>
                            <input type="text" name="province" class="form-control" placeholder="Soria, Lugo...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Municipio</label>
                            <input type="text" name="municipality" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lugar (Recinto)</label>
                            <input type="text" name="venue_name" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción Corta</label>
                            <input type="text" name="short_description" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Descripción Completa</label>
                            <textarea name="description" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-fecha">
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label fw-bold">Fecha Inicio</label><input type="date" name="start_date" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Fecha Fin</label><input type="date" name="end_date" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Hora Inicio</label><input type="time" name="start_time" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Hora Fin</label><input type="time" name="end_time" class="form-control"></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-tickets">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label fw-bold">Precio (€)</label><input type="number" step="0.01" name="ticket_price" class="form-control" value="0.00"></div>
                        <div class="col-md-8"><label class="form-label">URL de Venta / Entradas</label><input type="text" name="ticket_url" class="form-control"></div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" name="is_free" class="form-check-input" id="freeCheck">
                                <label class="form-check-label fw-bold" for="freeCheck">Este evento es gratuito</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-seo">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label fw-bold">Título SEO (Meta Title)</label><input type="text" name="meta_title" class="form-control"></div>
                        <div class="col-12"><label class="form-label fw-bold">Descripción SEO (Meta Description)</label><textarea name="meta_description" class="form-control" rows="3"></textarea></div>
                    </div>
                </div>

            </div>
        </div>

        <div class="card-footer bg-light p-3 text-end">
            <button type="submit" class="btn btn-primary btn-lg px-5 shadow">Publicar Evento</button>
        </div>
    </form>
</div>

<script>
// Filtrado dinámico de subcategorías
document.getElementById('category_id').addEventListener('change', function() {
    const parentId = this.value;
    const subSelect = document.getElementById('subcategory_id');
    subSelect.value = "";
    subSelect.querySelectorAll('option').forEach(opt => {
        if(opt.getAttribute('data-parent') === parentId || opt.value === "") {
            opt.style.display = 'block';
        } else {
            opt.style.display = 'none';
        }
    });
});
</script>

</body>
</html>