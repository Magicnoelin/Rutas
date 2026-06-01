<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración – Rutas Rurales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-bg:   #1e2d1f;
            --sidebar-w:    260px;
            --accent:       #4caf50;
            --accent-light: #81C784;
            --text-dim:     rgba(255,255,255,0.55);
            --text-muted:   rgba(255,255,255,0.35);
            --hover-bg:     rgba(255,255,255,0.07);
            --section-color:#a5d6a7;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f0f4f1;
            min-height: 100vh;
            display: flex;
        }

        /* ── SIDEBAR ─────────────────────────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--sidebar-bg);
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
            color: var(--accent-light);
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .sidebar-brand .logo span { font-size: 1.5rem; }
        .sidebar-brand p {
            color: var(--text-muted);
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
            color: var(--section-color);
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 20px;
            color: var(--text-dim);
            text-decoration: none;
            font-size: 0.875rem;
            border-left: 3px solid transparent;
            transition: all 0.18s ease;
        }
        .nav-item:hover {
            background: var(--hover-bg);
            color: #fff;
            border-left-color: var(--accent);
        }
        .nav-item i {
            width: 18px;
            text-align: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .nav-item.danger { color: #ef9a9a; }
        .nav-item.danger:hover { background: rgba(239,83,80,0.1); border-left-color: #ef5350; color: #ff8a80; }
        .nav-item.highlight { color: var(--accent-light); font-weight: 600; }
        .nav-item.highlight:hover { background: rgba(76,175,80,0.12); border-left-color: var(--accent-light); color: #fff; }

        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.07);
            margin: 10px 20px;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
            font-size: 0.72rem;
            color: var(--text-muted);
            text-align: center;
        }

        /* ── CONTENIDO ────────────────────────────────────────────────────── */
        .content {
            flex: 1;
            padding: 40px 32px;
        }

        .welcome-card {
            background: #fff;
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
            max-width: 680px;
        }
        .welcome-card h1 {
            font-size: 1.6rem;
            color: #1e2d1f;
            margin-bottom: 8px;
        }
        .welcome-card p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.7;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
            margin-top: 28px;
        }
        .quick-link {
            background: #f8faf8;
            border: 1px solid #e0eae0;
            border-radius: 12px;
            padding: 18px 14px;
            text-align: center;
            text-decoration: none;
            color: #2F5233;
            transition: all 0.2s;
        }
        .quick-link:hover {
            background: #e8f5e9;
            border-color: #81C784;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76,175,80,0.15);
        }
        .quick-link i {
            font-size: 1.6rem;
            margin-bottom: 8px;
            color: #4caf50;
            display: block;
        }
        .quick-link span {
            font-size: 0.82rem;
            font-weight: 600;
        }

        /* ── RESPONSIVE ───────────────────────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar { width: 220px; }
            .content { padding: 24px 16px; }
        }
    </style>
</head>
<body>

<!-- ── SIDEBAR ─────────────────────────────────────────────────────────────── -->
<aside class="sidebar">

    <div class="sidebar-brand">
        <a href="menu.php" class="logo"><span>🌿</span> Rutas Rurales</a>
        <p>Panel de Administración</p>
    </div>

    <nav>

        <!-- Contenido principal -->
        <div class="nav-section">Contenido</div>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/index.php">
            <i class="fas fa-bed"></i> Alojamientos
        </a>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/lugares_index.php">
            <i class="fas fa-map-marker-alt"></i> Lugares de Interés
        </a>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/actividades_index.php">
            <i class="fas fa-hiking"></i> Actividades
        </a>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/eventos_index.php">
            <i class="fas fa-calendar-alt"></i> Eventos Culturales
        </a>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/cultural_events_trads_index.php">
            <i class="fas fa-language"></i> Traducciones Eventos
        </a>

        <div class="nav-divider"></div>

        <!-- Monetización -->
        <div class="nav-section">Monetización</div>
        <a class="nav-item highlight" href="https://rutasrurales.io/admin_tablas/membresias_index.php">
            <i class="fas fa-crown"></i> Gestión de Membresías
        </a>

        <div class="nav-divider"></div>

        <!-- Usuarios -->
        <div class="nav-section">Usuarios</div>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/usuarios_index.php">
            <i class="fas fa-users"></i> Gestión de Usuarios
        </a>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/usuarios_roles.php">
            <i class="fas fa-shield-alt"></i> Roles de Usuario
        </a>

        <div class="nav-divider"></div>

        <!-- Moderación -->
        <div class="nav-section">Moderación</div>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/moderacion_alojamientos.php">
            <i class="fas fa-clipboard-check"></i> Alojamientos
        </a>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/moderacion_lugares.php">
            <i class="fas fa-search-location"></i> Lugares
        </a>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/moderacion_lugares.php?type=activity">
            <i class="fas fa-check-circle"></i> Actividades
        </a>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/moderacion_fotos.php">
            <i class="fas fa-images"></i> Fotos Sugeridas
        </a>

        <div class="nav-divider"></div>

        <!-- SEO y Herramientas -->
        <div class="nav-section">SEO &amp; Herramientas</div>
        <a class="nav-item highlight" href="https://rutasrurales.io/admin_tablas/inbound_links.php">
            <i class="fas fa-link"></i> Inbound Links
        </a>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/rutas.php">
            <i class="fas fa-route"></i> Gestor de Rutas
        </a>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/rutas.php?action=new">
            <i class="fas fa-plus-circle"></i> Nueva Ruta
        </a>
        <a class="nav-item" href="https://rutasrurales.io/api/route-faqs.php?route_id=1" target="_blank">
            <i class="fas fa-question-circle"></i> FAQs de Rutas (API)
        </a>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/sql_manager.php">
            <i class="fas fa-database"></i> SQL Manager
        </a>
        <a class="nav-item" href="https://rutasrurales.io/admin_tablas/cola_tareas.php">
            <i class="fas fa-cogs"></i> Cola de Tareas
        </a>

        <div class="nav-divider"></div>

        <a class="nav-item danger" href="https://rutasrurales.io/admin_tablas/logout.php">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>

    </nav>

    <div class="sidebar-footer">
        rutasrurales.io &copy; <?php echo date('Y'); ?>
    </div>

</aside>

<!-- ── CONTENIDO ────────────────────────────────────────────────────────────── -->
<main class="content">
    <div class="welcome-card">
        <h1>👋 Bienvenida al Panel</h1>
        <p>Selecciona una sección del menú lateral para gestionar el contenido del portal de Rutas Rurales.</p>

        <div class="quick-links">
            <a class="quick-link" href="https://rutasrurales.io/admin_tablas/index.php">
                <i class="fas fa-bed"></i><span>Alojamientos</span>
            </a>
            <a class="quick-link" href="https://rutasrurales.io/admin_tablas/eventos_index.php">
                <i class="fas fa-calendar-alt"></i><span>Eventos</span>
            </a>
            <a class="quick-link" href="https://rutasrurales.io/admin_tablas/lugares_index.php">
                <i class="fas fa-map-marker-alt"></i><span>Lugares</span>
            </a>
            <a class="quick-link" href="https://rutasrurales.io/admin_tablas/actividades_index.php">
                <i class="fas fa-hiking"></i><span>Actividades</span>
            </a>
            <a class="quick-link" href="https://rutasrurales.io/admin_tablas/usuarios_index.php">
                <i class="fas fa-users"></i><span>Usuarios</span>
            </a>
            <a class="quick-link" href="https://rutasrurales.io/admin_tablas/inbound_links.php">
                <i class="fas fa-link"></i><span>Inbound Links</span>
            </a>
            <a class="quick-link" href="https://rutasrurales.io/admin_tablas/rutas.php">
                <i class="fas fa-route"></i><span>Rutas</span>
            </a>
            <a class="quick-link" href="https://rutasrurales.io/admin_tablas/moderacion_alojamientos.php">
                <i class="fas fa-clipboard-check"></i><span>Moderación</span>
            </a>
            <a class="quick-link" href="https://rutasrurales.io/admin_tablas/membresias_index.php">
                <i class="fas fa-crown"></i><span>Membresías</span>
            </a>
            <a class="quick-link" href="https://rutasrurales.io/admin_tablas/cola_tareas.php">
                <i class="fas fa-cogs"></i><span>Cola Tareas</span>
            </a>
        </div>
    </div>
</main>

</body>
</html>
