<?php
/**
 * Panel de Administración - Gestión de Membresías y Planes
 * 
 * Permite ver y editar los planes de membresía (Free, Premium, Business)
 * y los billing concepts asociados.
 */

require_once 'db.php';
require_once 'navbar.php';

$pdo = getDBConnection();

// ── Procesar guardado ──────────────────────────────────────────────
$saveMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update_plan' && isset($_POST['plan_id'])) {
            $id = (int)$_POST['plan_id'];
            $stmt = $pdo->prepare("
                UPDATE membership_plans SET
                    name = ?,
                    description = ?,
                    price_monthly = ?,
                    price_yearly = ?,
                    official_price_yearly = ?,
                    max_accommodations = ?,
                    max_photos = ?,
                    is_popular = ?,
                    is_launch_offer = ?,
                    launch_discount_percent = ?,
                    has_direct_link = ?,
                    has_api = ?,
                    has_priority_position = ?,
                    has_priority_support = ?,
                    has_advanced_stats = ?,
                    has_basic_stats = ?,
                    can_send_messages = ?,
                    can_receive_messages = ?,
                    has_personalized_consulting = ?,
                    has_reports = ?,
                    multipropiedad_note = ?,
                    is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                (float)$_POST['price_monthly'],
                (float)$_POST['price_yearly'],
                $_POST['official_price_yearly'] !== '' ? (float)$_POST['official_price_yearly'] : null,
                (int)$_POST['max_accommodations'],
                $_POST['max_photos'] !== '' ? (int)$_POST['max_photos'] : null,
                isset($_POST['is_popular']) ? 1 : 0,
                isset($_POST['is_launch_offer']) ? 1 : 0,
                $_POST['launch_discount_percent'] !== '' ? (int)$_POST['launch_discount_percent'] : null,
                isset($_POST['has_direct_link']) ? 1 : 0,
                isset($_POST['has_api']) ? 1 : 0,
                isset($_POST['has_priority_position']) ? 1 : 0,
                isset($_POST['has_priority_support']) ? 1 : 0,
                isset($_POST['has_advanced_stats']) ? 1 : 0,
                isset($_POST['has_basic_stats']) ? 1 : 0,
                isset($_POST['can_send_messages']) ? 1 : 0,
                isset($_POST['can_receive_messages']) ? 1 : 0,
                isset($_POST['has_personalized_consulting']) ? 1 : 0,
                isset($_POST['has_reports']) ? 1 : 0,
                $_POST['multipropiedad_note'] ?? null,
                isset($_POST['is_active']) ? 1 : 0,
                $id
            ]);
            $saveMessage = '✅ Plan actualizado correctamente.';
        } elseif ($_POST['action'] === 'update_features' && isset($_POST['plan_id'])) {
            // Guardar features como JSON
            $id = (int)$_POST['plan_id'];
            $features = [];
            if (!empty($_POST['features_list'])) {
                $lines = explode("\n", trim($_POST['features_list']));
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $features[] = $line;
                    }
                }
            }
            $featuresJson = json_encode($features, JSON_UNESCAPED_UNICODE);
            $stmt = $pdo->prepare("UPDATE membership_plans SET features = ? WHERE id = ?");
            $stmt->execute([$featuresJson, $id]);
            $saveMessage = '✅ Características actualizadas correctamente.';
        }
    } catch (Exception $e) {
        $saveMessage = '❌ Error: ' . $e->getMessage();
    }
}

// ── Obtener datos ──────────────────────────────────────────────────
$plans = $pdo->query("
    SELECT * FROM membership_plans ORDER BY id ASC
")->fetchAll();

$billingConcepts = $pdo->query("
    SELECT * FROM billing_concepts ORDER BY billing_type, amount ASC
")->fetchAll();

// Contar usuarios por plan
$userCounts = $pdo->query("
    SELECT membership_type, COUNT(*) as total 
    FROM users 
    WHERE membership_type IS NOT NULL 
    GROUP BY membership_type
")->fetchAll(PDO::FETCH_KEY_PAIR);

$totalUsers = array_sum($userCounts);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Membresías – Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f0f4f1;
            min-height: 100vh;
            display: flex;
        }
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: #1e2d1f;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            overflow-y: auto;
            flex-shrink: 0;
        }
        .sidebar-brand {
            padding: 28px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .sidebar-brand .logo {
            font-size: 1.25rem;
            font-weight: 700;
            color: #81C784;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .sidebar-brand p {
            color: rgba(255,255,255,0.35);
            font-size: 0.72rem;
            margin-top: 4px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        nav { padding: 12px 0; flex: 1; }
        .nav-section {
            padding: 16px 20px 4px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #a5d6a7;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 20px;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-size: 0.875rem;
            border-left: 3px solid transparent;
            transition: all 0.18s ease;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.07);
            color: #fff;
            border-left-color: #4caf50;
        }
        .nav-item i { width: 18px; text-align: center; }
        .nav-item.active { color: #81C784; font-weight: 600; }
        .nav-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 10px 20px; }
        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
            font-size: 0.72rem;
            color: rgba(255,255,255,0.35);
            text-align: center;
        }

        .content {
            flex: 1;
            padding: 32px;
            max-width: calc(100vw - 260px);
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .page-header h1 {
            font-size: 1.5rem;
            color: #1e2d1f;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-header p { color: #666; font-size: 0.9rem; margin-top: 4px; }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 18px 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
            border-top: 4px solid #4caf50;
        }
        .stat-card.premium { border-top-color: #b8956a; }
        .stat-card.business { border-top-color: #2f5233; }
        .stat-card.free { border-top-color: #6c7a6c; }
        .stat-value { font-size: 1.6rem; font-weight: 700; color: #1e2d1f; }
        .stat-label { font-size: 0.78rem; color: #888; margin-top: 4px; }

        .card {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }
        .card-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e2d1f;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .save-alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .save-alert.success { background: #e8f5e9; color: #1e7e34; border: 1px solid #a5d6a7; }
        .save-alert.error { background: #fdecea; color: #c0392b; border: 1px solid #f1948a; }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 20px;
        }
        .plan-card {
            border: 2px solid #e0eae0;
            border-radius: 14px;
            overflow: hidden;
            transition: all 0.2s;
        }
        .plan-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
        .plan-card.popular { border-color: #b8956a; }
        .plan-card.launch { border-color: #e74c3c; }

        .plan-header {
            padding: 16px 20px;
            background: #f8faf8;
            border-bottom: 1px solid #e0eae0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .plan-header h3 { font-size: 1.1rem; color: #1e2d1f; }
        .plan-badge {
            font-size: 0.7rem;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 700;
        }
        .plan-badge.popular { background: #b8956a; color: #fff; }
        .plan-badge.launch { background: #e74c3c; color: #fff; }
        .plan-badge.inactive { background: #ccc; color: #666; }

        .plan-body { padding: 20px; }

        .form-group { margin-bottom: 14px; }
        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 4px;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d0dcd0;
            border-radius: 8px;
            font-size: 0.88rem;
            outline: none;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus { border-color: #4caf50; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 4px;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 400;
            font-size: 0.85rem;
            color: #555;
            cursor: pointer;
        }
        .checkbox-group input[type="checkbox"] { width: auto; }

        .btn {
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary { background: #2f5233; color: #fff; }
        .btn-primary:hover { background: #4a7c59; }
        .btn-accent { background: #b8956a; color: #fff; }
        .btn-accent:hover { background: #d4a574; }
        .btn-secondary { background: #f0f0f0; color: #444; }
        .btn-secondary:hover { background: #e0e0e0; }
        .btn-sm { padding: 5px 12px; font-size: 0.78rem; }

        .billing-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }
        .billing-table th {
            text-align: left;
            padding: 10px 12px;
            background: #f8faf8;
            border-bottom: 2px solid #e0eae0;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
        }
        .billing-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eef4ee;
        }
        .billing-table tr:hover td { background: #f8faf8; }

        .badge-type {
            font-size: 0.72rem;
            padding: 2px 8px;
            border-radius: 8px;
            font-weight: 600;
        }
        .badge-type.monthly { background: #e3f2fd; color: #1565c0; }
        .badge-type.yearly { background: #fce4ec; color: #c62828; }

        .price-display {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e2d1f;
        }
        .price-display small {
            font-size: 0.75rem;
            font-weight: 400;
            color: #888;
        }
        .price-official {
            font-size: 0.82rem;
            color: #999;
            text-decoration: line-through;
        }

        .features-textarea {
            min-height: 120px;
            font-size: 0.85rem;
            line-height: 1.6;
        }

        .toggle-section {
            cursor: pointer;
            user-select: none;
        }
        .toggle-section:hover { color: #4caf50; }

        @media (max-width: 768px) {
            .sidebar { width: 220px; }
            .content { padding: 16px; max-width: calc(100vw - 220px); }
            .form-row, .form-row-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ── SIDEBAR ── -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="menu.php" class="logo"><span>🌿</span> Rutas Rurales</a>
        <p>Panel de Administración</p>
    </div>
    <nav>
        <div class="nav-section">Contenido</div>
        <a class="nav-item" href="index.php"><i class="fas fa-bed"></i> Alojamientos</a>
        <a class="nav-item" href="lugares_index.php"><i class="fas fa-map-marker-alt"></i> Lugares</a>
        <a class="nav-item" href="actividades_index.php"><i class="fas fa-hiking"></i> Actividades</a>
        <a class="nav-item" href="eventos_index.php"><i class="fas fa-calendar-alt"></i> Eventos</a>
        <a class="nav-item" href="cultural_events_trads_index.php"><i class="fas fa-language"></i> Traducciones</a>

        <div class="nav-divider"></div>
        <div class="nav-section">Usuarios</div>
        <a class="nav-item" href="usuarios_index.php"><i class="fas fa-users"></i> Gestión de Usuarios</a>
        <a class="nav-item" href="usuarios_roles.php"><i class="fas fa-shield-alt"></i> Roles</a>

        <div class="nav-divider"></div>
        <div class="nav-section">Monetización</div>
        <a class="nav-item active" href="membresias_index.php"><i class="fas fa-crown"></i> Membresías</a>

        <div class="nav-divider"></div>
        <div class="nav-section">Moderación</div>
        <a class="nav-item" href="moderacion_alojamientos.php"><i class="fas fa-clipboard-check"></i> Alojamientos</a>
        <a class="nav-item" href="moderacion_lugares.php"><i class="fas fa-search-location"></i> Lugares</a>
        <a class="nav-item" href="moderacion_fotos.php"><i class="fas fa-images"></i> Fotos</a>

        <div class="nav-divider"></div>
        <div class="nav-section">Herramientas</div>
        <a class="nav-item" href="inbound_links.php"><i class="fas fa-link"></i> Inbound Links</a>
        <a class="nav-item" href="rutas.php"><i class="fas fa-route"></i> Gestor de Rutas</a>
        <a class="nav-item" href="sql_manager.php"><i class="fas fa-database"></i> SQL Manager</a>
        <a class="nav-item" href="cola_tareas.php"><i class="fas fa-cogs"></i> Cola de Tareas</a>

        <div class="nav-divider"></div>
        <a class="nav-item" href="menu.php"><i class="fas fa-arrow-left"></i> Volver al Menú</a>
    </nav>
    <div class="sidebar-footer">rutasrurales.io &copy; <?php echo date('Y'); ?></div>
</aside>

<!-- ── CONTENIDO ── -->
<main class="content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-crown" style="color:#b8956a;"></i> Gestión de Membresías</h1>
            <p>Administra los planes de membresía, precios y características de la plataforma.</p>
        </div>
        <a href="menu.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <?php if ($saveMessage): ?>
        <div class="save-alert <?php echo strpos($saveMessage, '✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $saveMessage; ?>
        </div>
    <?php endif; ?>

    <!-- ── ESTADÍSTICAS ── -->
    <div class="stats-row">
        <div class="stat-card free">
            <div class="stat-value"><?php echo $userCounts['free'] ?? 0; ?></div>
            <div class="stat-label">Usuarios Free</div>
        </div>
        <div class="stat-card premium">
            <div class="stat-value"><?php echo $userCounts['premium'] ?? 0; ?></div>
            <div class="stat-label">Usuarios Premium</div>
        </div>
        <div class="stat-card business">
            <div class="stat-value"><?php echo $userCounts['business'] ?? 0; ?></div>
            <div class="stat-label">Usuarios Business</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $totalUsers; ?></div>
            <div class="stat-label">Total Usuarios</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo count($plans); ?></div>
            <div class="stat-label">Planes Activos</div>
        </div>
    </div>

    <!-- ── PLANES DE MEMBRESÍA ── -->
    <div class="card">
        <div class="card-title"><i class="fas fa-store"></i> Planes de Membresía</div>
        <p style="color:#888;font-size:0.88rem;margin-bottom:20px;">
            Edita los planes directamente. Los cambios se reflejarán automáticamente en la web.
        </p>

        <div class="plans-grid">
            <?php foreach ($plans as $plan): 
                $isPopular = $plan['is_popular'];
                $isLaunch = $plan['is_launch_offer'];
                $isActive = $plan['is_active'];
                $features = $plan['features'];
                if ($features && is_string($features)) {
                    try {
                        $featuresArr = json_decode($features, true);
                        $featuresText = is_array($featuresArr) ? implode("\n", $featuresArr) : $features;
                    } catch (Exception $e) {
                        $featuresText = $features;
                    }
                } else {
                    $featuresText = '';
                }
            ?>
            <div class="plan-card <?php echo $isPopular ? 'popular' : ''; ?> <?php echo $isLaunch ? 'launch' : ''; ?>">
                <div class="plan-header">
                    <h3>
                        <?php if ($plan['id'] == 1): ?><i class="fas fa-user" style="color:#6c7a6c;"></i>
                        <?php elseif ($plan['id'] == 2): ?><i class="fas fa-crown" style="color:#b8956a;"></i>
                        <?php else: ?><i class="fas fa-building" style="color:#2f5233;"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($plan['name']); ?>
                    </h3>
                    <div>
                        <?php if ($isLaunch): ?>
                            <span class="plan-badge launch"><i class="fas fa-fire"></i> -<?php echo (int)$plan['launch_discount_percent']; ?>%</span>
                        <?php endif; ?>
                        <?php if ($isPopular): ?>
                            <span class="plan-badge popular"><i class="fas fa-star"></i> POPULAR</span>
                        <?php endif; ?>
                        <?php if (!$isActive): ?>
                            <span class="plan-badge inactive">INACTIVO</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="plan-body">
                    <form method="POST" style="margin-bottom:16px;">
                        <input type="hidden" name="action" value="update_plan">
                        <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">

                        <div class="form-group">
                            <label>Nombre del Plan</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($plan['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description" rows="2"><?php echo htmlspecialchars($plan['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Precio Mensual (€)</label>
                                <input type="number" step="0.01" name="price_monthly" value="<?php echo $plan['price_monthly']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Precio Anual (€)</label>
                                <input type="number" step="0.01" name="price_yearly" value="<?php echo $plan['price_yearly']; ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Precio Oficial Anual (€) <small style="color:#999;">(tachado, para descuentos)</small></label>
                                <input type="number" step="0.01" name="official_price_yearly" value="<?php echo $plan['official_price_yearly'] ?? ''; ?>" placeholder="Ej: 240.00">
                            </div>
                            <div class="form-group">
                                <label>% Descuento Lanzamiento</label>
                                <input type="number" name="launch_discount_percent" value="<?php echo $plan['launch_discount_percent'] ?? ''; ?>" placeholder="Ej: 50">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Máx. Alojamientos</label>
                                <input type="number" name="max_accommodations" value="<?php echo (int)$plan['max_accommodations']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Máx. Fotos <small style="color:#999;">(vacío = ilimitado)</small></label>
                                <input type="number" name="max_photos" value="<?php echo $plan['max_photos'] ?? ''; ?>" placeholder="Ilimitado">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Nota Multipropiedad</label>
                            <input type="text" name="multipropiedad_note" value="<?php echo htmlspecialchars($plan['multipropiedad_note'] ?? ''); ?>" placeholder="Ej: ¿Tienes más de un alojamiento? Consúltanos...">
                        </div>

                        <div class="form-group">
                            <label>Características (flags)</label>
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="is_popular" <?php echo $isPopular ? 'checked' : ''; ?>> 🌟 Popular</label>
                                <label><input type="checkbox" name="is_launch_offer" <?php echo $isLaunch ? 'checked' : ''; ?>> 🔥 Oferta Lanzamiento</label>
                                <label><input type="checkbox" name="is_active" <?php echo $isActive ? 'checked' : ''; ?>> ✅ Activo</label>
                                <label><input type="checkbox" name="has_direct_link" <?php echo $plan['has_direct_link'] ? 'checked' : ''; ?>> 🔗 Enlace Directo</label>
                                <label><input type="checkbox" name="has_api" <?php echo $plan['has_api'] ? 'checked' : ''; ?>> 🔌 API</label>
                                <label><input type="checkbox" name="has_priority_position" <?php echo $plan['has_priority_position'] ? 'checked' : ''; ?>> 📌 Posición Destacada</label>
                                <label><input type="checkbox" name="has_priority_support" <?php echo $plan['has_priority_support'] ? 'checked' : ''; ?>> 🆘 Soporte Prioritario</label>
                                <label><input type="checkbox" name="has_advanced_stats" <?php echo $plan['has_advanced_stats'] ? 'checked' : ''; ?>> 📊 Estadísticas Avanzadas</label>
                                <label><input type="checkbox" name="has_basic_stats" <?php echo $plan['has_basic_stats'] ? 'checked' : ''; ?>> 📈 Estadísticas Básicas</label>
                                <label><input type="checkbox" name="can_send_messages" <?php echo $plan['can_send_messages'] ? 'checked' : ''; ?>> 💬 Enviar Mensajes</label>
                                <label><input type="checkbox" name="can_receive_messages" <?php echo $plan['can_receive_messages'] ? 'checked' : ''; ?>> 📨 Recibir Mensajes</label>
                                <label><input type="checkbox" name="has_personalized_consulting" <?php echo $plan['has_personalized_consulting'] ? 'checked' : ''; ?>> 🎯 Asesoramiento</label>
                                <label><input type="checkbox" name="has_reports" <?php echo $plan['has_reports'] ? 'checked' : ''; ?>> 📋 Informes</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Plan</button>
                    </form>

                    <!-- Features como texto -->
                    <form method="POST" style="border-top:1px solid #e0eae0;padding-top:16px;margin-top:8px;">
                        <input type="hidden" name="action" value="update_features">
                        <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                        <div class="form-group">
                            <label>Características (una por línea)</label>
                            <textarea name="features_list" class="features-textarea"><?php echo htmlspecialchars($featuresText); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-accent"><i class="fas fa-list"></i> Guardar Características</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── BILLING CONCEPTS ── -->
    <div class="card">
        <div class="card-title toggle-section" onclick="toggleBillingTable()">
            <i class="fas fa-receipt"></i> Conceptos de Facturación (Billing Concepts)
            <span id="billing-toggle-icon" style="margin-left:8px;font-size:0.8rem;color:#888;"><i class="fas fa-chevron-down"></i></span>
        </div>
        <div id="billing-table-container" style="display:none;margin-top:12px;">
            <p style="color:#888;font-size:0.88rem;margin-bottom:12px;">
                Estos son los productos/servicios configurados en el sistema de facturación.
            </p>
            <table class="billing-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Importe</th>
                        <th>Tipo</th>
                        <th>Activo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($billingConcepts) > 0): ?>
                        <?php foreach ($billingConcepts as $bc): ?>
                        <tr>
                            <td><?php echo $bc['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($bc['concept_name']); ?></strong></td>
                            <td style="max-width:300px;font-size:0.82rem;color:#666;">
                                <?php echo htmlspecialchars($bc['description'] ?? ''); ?>
                            </td>
                            <td>
                                <div class="price-display"><?php echo number_format($bc['amount'], 2, ',', '.'); ?>€</div>
                                <?php if ($bc['official_price_yearly'] ?? null): ?>
                                    <div class="price-official"><?php echo number_format($bc['official_price_yearly'], 2, ',', '.'); ?>€</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-type <?php echo $bc['billing_type']; ?>">
                                    <?php echo $bc['billing_type'] === 'monthly' ? 'Mensual' : ($bc['billing_type'] === 'yearly' ? 'Anual' : $bc['billing_type']); ?>
                                </span>
                            </td>
                            <td><?php echo $bc['active'] ? '✅' : '❌'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;padding:20px;color:#888;">No hay conceptos de facturación configurados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
function toggleBillingTable() {
    const container = document.getElementById('billing-table-container');
    const icon = document.getElementById('billing-toggle-icon');
    if (container.style.display === 'none') {
        container.style.display = 'block';
        icon.innerHTML = '<i class="fas fa-chevron-up"></i>';
    } else {
        container.style.display = 'none';
        icon.innerHTML = '<i class="fas fa-chevron-down"></i>';
    }
}
</script>

</body>
</html>
