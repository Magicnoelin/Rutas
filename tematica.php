<?php
/**
 * Plantilla Maestra para Landings Temáticas SEO
 */
require_once 'api/themes_config.php';

$themeKey = $_GET['theme'] ?? '';

if (!isset($THEMES_CONFIG[$themeKey])) {
    header("Location: /");
    exit;
}

$theme = $THEMES_CONFIG[$themeKey];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($theme['meta_title']) ?> | Rutas Rurales</title>
    <meta name="description" content="<?= htmlspecialchars($theme['meta_description']) ?>">
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .theme-hero {
            height: 400px;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1920&h=600&fit=crop') center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            margin-top: 80px;
        }
        .theme-hero h1 { font-size: 3.5rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        
        .theme-content { padding: 4rem 0; }
        .theme-intro { max-width: 800px; margin: 0 auto 4rem; text-align: center; font-size: 1.2rem; line-height: 1.8; color: #555; }
        
        .section-grid { margin-bottom: 4rem; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 2px solid var(--primary-color); padding-bottom: 0.5rem; }
        .section-header h2 { color: var(--primary-color); font-size: 1.8rem; }
        
        /* Reutilizando estilos de tarjetas rectangulares */
        .thematic-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
        
        .result-card { 
            background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
            display: flex; height: 110px; overflow: hidden; border: 1px solid #eee; transition: all 0.3s; cursor: pointer;
        }
        .result-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-color: var(--primary-color); }
        .result-image-wrapper { width: 120px; height: 100%; flex-shrink: 0; }
        .result-image { width: 100%; height: 100%; object-fit: cover; }
        .result-content { flex: 1; padding: 0.8rem 1rem; display: flex; flex-direction: column; justify-content: space-between; min-width: 0; }
        .result-title { font-size: 1rem; font-weight: 700; color: #333; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .result-meta { font-size: 0.8rem; color: #777; }
        .badge { padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; background: #f0f0f0; }
        
        .loading-placeholder { text-align: center; padding: 3rem; font-size: 1.5rem; color: var(--primary-color); }
    </style>
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Logo" style="height: 50px; margin-right: 10px;">
                </div>
                <ul class="nav-menu">
                    <li><a href="/alojamientos-turisticos.html">Alojamientos</a></li>
                    <li><a href="/index.html#actividades">Actividades</a></li>
                    <li><a href="/eventos-culturales-paginacion.html">Eventos</a></li>
                    <li><a href="/rutas.html" class="active">Explorar Mapa</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <section class="theme-hero">
        <div class="container">
            <h1><?= htmlspecialchars($theme['title']) ?></h1>
        </div>
    </section>

    <main class="theme-content">
        <div class="container">
            <div class="theme-intro">
                <p><?= nl2br(htmlspecialchars($theme['content_text'])) ?></p>
            </div>

            <div id="thematicContent">
                <div class="loading-placeholder">
                    <i class="fas fa-spinner fa-spin"></i> Cargando experiencias...
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 rutasrurales.io - Experiencias Temáticas</p>
        </div>
    </footer>

    <script>
        const themeKey = '<?= $themeKey ?>';

        async function loadThematicContent() {
            try {
                const response = await fetch(`/api/get_thematic_content.php?theme=${themeKey}`);
                const res = await response.json();

                if (!res.success) return;

                const container = document.getElementById('thematicContent');
                container.innerHTML = '';

                // Renderizar Secciones
                renderSection(container, 'Lugares Imperdibles', res.data.places, 'lugar');
                renderSection(container, 'Actividades y Experiencias', res.data.activities, 'actividad');
                renderSection(container, 'Próximos Eventos', res.data.events, 'evento');
                renderSection(container, 'Dónde Dormir', res.data.accommodations, 'alojamiento');

            } catch (error) {
                console.error('Error:', error);
            }
        }

        function renderSection(parent, title, items, type) {
            if (!items || items.length === 0) return;

            const section = document.createElement('div');
            section.className = 'section-grid';
            section.innerHTML = `
                <div class="section-header">
                    <h2>${title}</h2>
                    <a href="/rutas.html" class="btn-secondary">Ver todos</a>
                </div>
                <div class="thematic-grid"></div>
            `;

            const grid = section.querySelector('.thematic-grid');
            items.forEach(item => {
                const card = document.createElement('div');
                card.className = 'result-card';
                card.onclick = () => window.location.href = `/${type}/${item.slug}`;
                
                card.innerHTML = `
                    <div class="result-image-wrapper">
                        <img src="${item.photo1 || '/tourist_activities_images/Patrocinio.webp'}" alt="${item.nombre || 'Actividad turística'}" class="result-image">
                    </div>
                    <div class="result-content">
                        <div>
                            <div class="result-title">${item.name}</div>
                            <div class="result-meta">${item.municipality}, ${item.province}</div>
                        </div>
                        <div>
                            <span class="badge">${type}</span>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });

            parent.appendChild(section);
        }

        window.addEventListener('DOMContentLoaded', loadThematicContent);
    </script>
</body>
</html>