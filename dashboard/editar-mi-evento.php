<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once '../api/config.php'; 
$pdo = getDBConnection();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensaje = "";

if (!$id) {
    die("ID de evento no especificado");
}

// PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $campos = [
        'name', 'municipality', 'province', 'description', 'start_date', 'end_date',
        'category', 'location', 'organizer', 'entry_fee', 'contact_phone',
        'website', 'photo1'
    ];

    $setPart = [];
    $values = [];
    
    foreach ($campos as $campo) {
        $setPart[] = "$campo = ?";
        $values[] = $_POST[$campo] ?? '';
    }

    $values[] = $id;

    try {
        $sql = "UPDATE cultural_events SET " . implode(', ', $setPart) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $mensaje = "<div class='alert alert-success shadow'>✅ Cambios guardados correctamente.</div>";
        
        // Recargar datos
        $stmt = $pdo->prepare("SELECT * FROM cultural_events WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
    } catch (Exception $e) {
        $mensaje = "<div class='alert alert-danger shadow'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

// CONSULTAR DATOS
if (!isset($item)) {
    $stmt = $pdo->prepare("SELECT * FROM cultural_events WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
}

if (!$item) { 
    die("Evento no encontrado."); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Mi Evento - Rutas Rurales</title>
    <link rel="icon" href="../favicon.png" type="image/png">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f9f9f9; }
        .edit-section {
            padding: 4rem 2rem;
            min-height: calc(100vh - 200px);
            margin-top: 70px;
        }
        .edit-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 150px;
            font-family: inherit;
        }
        .btn-save {
            background-color: #27ae60;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        .btn-save:hover {
            background-color: #219150;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <a href="../index.html">
                        <img src="../menu_images/Logo%20transparente.webp" alt="Rutas Logo">
                    </a>
                </div>
                <ul class="nav-menu">
                    <li><a href="../index.html">Inicio</a></li>
                    <li><a href="../user-dashboard.html#mis-eventos">Mi Dashboard</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <section class="edit-section">
        <div class="container">
            <div class="edit-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h1><i class="fas fa-calendar-alt"></i> Editar Mi Evento</h1>
                    <a href="../user-dashboard.html#mis-eventos" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>

                <?= $mensaje ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="name">Nombre del Evento*</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="municipality">Municipio*</label>
                            <input type="text" id="municipality" name="municipality" value="<?= htmlspecialchars($item['municipality']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="province">Provincia*</label>
                            <input type="text" id="province" name="province" value="<?= htmlspecialchars($item['province']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción del Evento*</label>
                        <textarea id="description" name="description" required><?= htmlspecialchars($item['description']) ?></textarea>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="start_date">Fecha de Inicio*</label>
                            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($item['start_date']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">Fecha de Fin</label>
                            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($item['end_date'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category">Categoría*</label>
                        <select id="category" name="category" required>
                            <option value="">Seleccionar categoría...</option>
                            <option value="festival" <?= $item['category'] == 'festival' ? 'selected' : '' ?>>Festival</option>
                            <option value="feria" <?= $item['category'] == 'feria' ? 'selected' : '' ?>>Feria</option>
                            <option value="mercado" <?= $item['category'] == 'mercado' ? 'selected' : '' ?>>Mercado</option>
                            <option value="concierto" <?= $item['category'] == 'concierto' ? 'selected' : '' ?>>Concierto</option>
                            <option value="exposicion" <?= $item['category'] == 'exposicion' ? 'selected' : '' ?>>Exposición</option>
                            <option value="teatro" <?= $item['category'] == 'teatro' ? 'selected' : '' ?>>Teatro</option>
                            <option value="gastronomia" <?= $item['category'] == 'gastronomia' ? 'selected' : '' ?>>Gastronomía</option>
                            <option value="deportivo" <?= $item['category'] == 'deportivo' ? 'selected' : '' ?>>Deportivo</option>
                            <option value="religioso" <?= $item['category'] == 'religioso' ? 'selected' : '' ?>>Religioso</option>
                            <option value="cultural" <?= $item['category'] == 'cultural' ? 'selected' : '' ?>>Cultural</option>
                            <option value="otro" <?= $item['category'] == 'otro' ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="location">Ubicación/Lugar del Evento</label>
                        <input type="text" id="location" name="location" value="<?= htmlspecialchars($item['location'] ?? '') ?>" placeholder="Ej: Plaza Mayor, Centro Cultural...">
                    </div>

                    <div class="form-group">
                        <label for="organizer">Organizador</label>
                        <input type="text" id="organizer" name="organizer" value="<?= htmlspecialchars($item['organizer'] ?? '') ?>" placeholder="Nombre del organizador o entidad">
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="entry_fee">Precio de Entrada</label>
                            <input type="text" id="entry_fee" name="entry_fee" value="<?= htmlspecialchars($item['entry_fee'] ?? '') ?>" placeholder="Ej: Gratuito, 5€, Variable...">
                        </div>
                        <div class="form-group">
                            <label for="contact_phone">Teléfono de Contacto</label>
                            <input type="tel" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($item['contact_phone'] ?? '') ?>" placeholder="+34 600 000 000">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="website">Sitio Web o Más Información</label>
                        <input type="url" id="website" name="website" value="<?= htmlspecialchars($item['website'] ?? '') ?>" placeholder="https://...">
                    </div>

                    <div class="form-group">
                        <label for="photo1">URL de la Foto Principal</label>
                        <input type="url" id="photo1" name="photo1" value="<?= htmlspecialchars($item['photo1'] ?? '') ?>">
                        <?php if (!empty($item['photo1'])): ?>
                            <img src="<?= htmlspecialchars($item['photo1']) ?>" alt="Preview" style="max-width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-top: 0.5rem; border: 1px solid #ddd;">
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="guardar" class="btn-save">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </form>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content-simple">
                <div class="footer-info">
                    <span><i class="fas fa-envelope"></i> olgamarin@rutasrurales.io</span>
                    <span><i class="fas fa-phone"></i> +34 605 249 696</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
