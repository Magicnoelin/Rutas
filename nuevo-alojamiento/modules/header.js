// header.js - Modular header component for nuevo-alojamiento pages
export async function loadHeader() {
    const headerElement = document.getElementById('header');
    if (!headerElement) return;

    try {
        // Get base path for relative URLs
        const basePath = window.location.pathname.includes('/nuevo-alojamiento/') ? '../' : '/';
        
        // Fetch header content
        const response = await fetch(`${basePath}header.php`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const html = await response.text();
        
        // Extract just the header content (from <header class="header"> to </header>)
        const headerMatch = html.match(/<header\b[^>]*\bclass\s*=\s*["']header["'][^>]*>.*?<\/header>/is);
        if (headerMatch) {
            headerElement.innerHTML = headerMatch[0];
            
            // Load any necessary scripts/styles
            loadHeaderDependencies(basePath);
            
            console.log('Header loaded successfully');
        } else {
            console.warn('Header not found in response');
            loadFallbackHeader(headerElement, basePath);
        }
    } catch (error) {
        console.error('Error loading header:', error);
        // Fallback to simple header
        const basePath = window.location.pathname.includes('/nuevo-alojamiento/') ? '../' : '/';
        loadFallbackHeader(headerElement, basePath);
    }
}

function loadHeaderDependencies(basePath) {
    // Load Font Awesome if not already loaded
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        link.crossOrigin = 'anonymous';
        document.head.appendChild(link);
    }
    
    // Load main styles if not already loaded
    if (!document.querySelector('link[href*="styles.css"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = `${basePath}styles.css`;
        document.head.appendChild(link);
    }
}

function loadFallbackHeader(headerElement, basePath) {
    headerElement.innerHTML = `
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <a href="${basePath}"><img src="${basePath}menu_images/Logo%20transparente.webp" alt="Rutas Rurales"></a>
                </div>
                <div class="nav-menu" id="navMenu">
                    <ul class="nav-row">
                        <li><a href="${basePath}alojamientos-turisticos.html">
                            <i class="fas fa-bed"></i>
                            <span>Alojamientos</span>
                        </a></li>
                        <li><a href="${basePath}lugares-interes-paginacion.html">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Lugares</span>
                        </a></li>
                        <li><a href="${basePath}eventos-culturales-paginacion.html">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Eventos</span>
                        </a></li>
                        <li><a href="${basePath}actividades-turisticas.html">
                            <i class="fas fa-hiking"></i>
                            <span>Actividades</span>
                        </a></li>
                    </ul>
                </div>
            </div>
        </nav>
    `;
}

// Initialize header when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => loadHeader());
} else {
    loadHeader();
}
