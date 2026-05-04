<?php
/**
 * PÁGINA: Rutas listas para disfrutar
 * Muestra las rutas destacadas (is_featured = 1) desde la base de datos
 */
require_once __DIR__ . '/api/config.php';

$rutas = [];
$error = null;

try {
    $pdo = getDBConnection();
    
    // Obtener rutas publicadas y destacadas
    $stmt = $pdo->prepare("
        SELECT id, name, slug, description, duration_days, difficulty_level,
               province, season, hero_image, cover_color, is_featured,
               created_at
        FROM routes
        WHERE status = 'published' AND is_public = 1 AND is_featured = 1
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('rutas-listas-para-disfrutar.php ERROR: ' . $e->getMessage());
    $error = 'Error al cargar las rutas.';
}

// Fallback: si no hay rutas destacadas, cargar las publicadas
if (empty($rutas) && !$error) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, slug, description, duration_days, difficulty_level,
                   province, season, hero_image, cover_color, is_featured,
                   created_at
            FROM routes
            WHERE status = 'published' AND is_public = 1
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('rutas-listas-para-disfrutar.php ERROR (fallback): ' . $e->getMessage());
    }
}

// Helper para obtener la imagen de la ruta
function getRutaImage($ruta) {
    if (!empty($ruta['hero_image'])) {
        $img = $ruta['hero_image'];
        // Si es URL absoluta, usarla directamente
        if (preg_match('/^https?:\/\//', $img)) {
            return $img;
        }
        // Si es ruta relativa, construir URL completa
        return 'https://rutasrurales.io/' . ltrim($img, '/');
    }
    // Fallback por defecto
    return 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=400&h=300&fit=crop';
}

// Helper para icono según temporada
function getSeasonIcon($season) {
    $icons = [
        'primavera' => '🌸',
        'verano'    => '☀️',
        'otoño'     => '🍂',
        'invierno'  => '❄️',
        'todo-el-ano' => '📅',
    ];
    return $icons[$season] ?? '📍';
}

// Helper para badge de dificultad
function getDifficultyBadge($level) {
    $labels = [
        'facil'    => ['text' => 'Fácil', 'color' => '#27ae60'],
        'moderado' => ['text' => 'Moderado', 'color' => '#e67e22'],
        'dificil'  => ['text' => 'Difícil', 'color' => '#e74c3c'],
    ];
    return $labels[$level] ?? ['text' => 'Fácil', 'color' => '#27ae60'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Google tag (gtag.js) -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MBP57VQM');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="canonical" href="https://rutasrurales.io/rutas-listas-para-disfrutar.html" />
    <title>Rutas listas para disfrutar - Rutas Rurales</title>
    <link rel="icon" href="/favicon.png" type="image/png">
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1920&h=1080&fit=crop') center/cover;
            margin-top: 70px;
        }
        .section {
            padding: 3rem 1rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .section-title {
            text-align: center;
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 2rem;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .card-content {
            padding: 1.5rem;
        }
        .card h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .card p {
            color: #666;
            line-height: 1.6;
        }
        .card-meta {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #888;
            flex-wrap: wrap;
        }
        .card-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .difficulty-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
        }
        .btn-primary {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 1rem;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #1e8449;
        }
        .no-rutas {
            text-align: center;
            padding: 3rem;
            color: #888;
        }
        .no-rutas i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
            display: block;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Logo">
                </div>
                <ul class="nav-menu">
                    <li><a href="/index.html">Inicio</a></li>
                    <li class="dropdown">
                        <a href="/index.html#accommodations">Alojamientos <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="/alojamientos.html">Ver alojamientos</a></li>
                            <li><a href="/alojamientos.html?categoria=casas-rurales">Casas rurales</a></li>
                            <li><a href="/alojamientos.html?categoria=apartamentos-urbanos">Apartamentos urbanos</a></li>
                            <li><a href="/alojamientos.html?categoria=viviendas-turisticas">Viviendas turísticas</a></li>
                            <li><a href="/agregar-alojamiento.html">Añadir alojamiento</a></li>
                        </ul>
                    </li>
                    <li><a href="/rutas-turisticas.html">Rutas turísticas</a></li>
                    <li><a href="/index.html#actividades">Actividades</a></li>
                    <li><a href="/eventos-culturales-paginacion.html">Eventos culturales</a></li>
                    <li><a href="/index.html#lugares">Lugares de interés</a></li>
                    <li><a href="/compromiso-social.html">Compromiso social</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1><i class="fas fa-check-circle"></i> Rutas listas para disfrutar</h1>
            <p>Rutas preparadas para tu próxima aventura</p>
        </div>
    </section>

    <!-- Featured Routes -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-star"></i> Rutas destacadas
            </h2>

            <?php if ($error): ?>
            <div class="no-rutas">
                <i class="fas fa-exclamation-triangle"></i>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
            <?php elseif (empty($rutas)): ?>
            <div class="no-rutas">
                <i class="fas fa-route"></i>
                <p>No hay rutas disponibles en este momento. ¡Vuelve pronto!</p>
            </div>
            <?php else: ?>

            <div class="grid">
                <?php foreach ($rutas as $ruta): 
                    $img = getRutaImage($ruta);
                    $dif = getDifficultyBadge($ruta['difficulty_level'] ?? 'facil');
                    $seasonIcon = getSeasonIcon($ruta['season'] ?? '');
                ?>
                <div class="card">
                    <img src="<?= htmlspecialchars($img) ?>" 
                         alt="<?= htmlspecialchars($ruta['name']) ?>" 
                         loading="lazy"
                         onerror="this.src='https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=400&h=300&fit=crop'">
                    <div class="card-content">
                        <h3><i class="fas fa-route"></i> <?= htmlspecialchars($ruta['name']) ?></h3>
                        <p><?= htmlspecialchars(substr($ruta['description'] ?? '', 0, 200)) ?><?= strlen($ruta['description'] ?? '') > 200 ? '...' : '' ?></p>
                        <div class="card-meta">
                            <?php if ($ruta['province']): ?>
                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ruta['province']) ?></span>
                            <?php endif; ?>
                            <span><i class="fas fa-clock"></i> <?= (int)$ruta['duration_days'] ?> día<?= (int)$ruta['duration_days'] !== 1 ? 's' : '' ?></span>
                            <?php if ($ruta['season']): ?>
                            <span><?= $seasonIcon ?> <?= htmlspecialchars(ucfirst($ruta['season'])) ?></span>
                            <?php endif; ?>
                            <span class="difficulty-badge" style="background: <?= $dif['color'] ?>">
                                <?= $dif['text'] ?>
                            </span>
                        </div>
                        <a href="/rutas/<?= htmlspecialchars($ruta['slug']) ?>" class="btn-primary">
                            <i class="fas fa-route"></i> Ver ruta
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Back link -->
            <div style="text-align: center; margin-top: 3rem;">
                <a href="/rutas-turisticas.html" class="btn-primary" style="background: #666;">
                    <i class="fas fa-arrow-left"></i> Volver a rutas turísticas
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content-simple">
                <div class="footer-info">
                    <span><i class="fas fa-envelope"></i> olgamarin@rutasrurales.io</span>
                    <span><i class="fas fa-phone"></i> +34 605 249 696</span>
                </div>
                <div class="footer-links">
                    <a href="/aviso-legal.html">Aviso legal</a>
                    <a href="/politica-cookies.html">Política de cookies</a>
                    <a href="/agradecimientos.html">Agradecimientos</a>
                </div>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/rutas_rurales/" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div class="footer-copyright">
                <p>&copy; 2026 rutasrurales.io. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="/script.js"></script>
</body>
</html>
