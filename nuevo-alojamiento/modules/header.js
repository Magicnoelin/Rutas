// header.js - Modular header component for nuevo-alojamiento pages
export async function loadHeader() {
    const headerElement = document.getElementById('header');
    if (!headerElement) return;

    try {
        // Fetch header content
        const response = await fetch('/header.php');
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const html = await response.text();
        
        // Extract just the header content (from <header class="header"> to </header>)
        const headerMatch = html.match(/<header\b[^>]*\bclass\s*=\s*["']header["'][^>]*>.*?<\/header>/is);
        if (headerMatch) {
            headerElement.innerHTML = headerMatch[0];
            
            // Load any necessary scripts/styles
            loadHeaderDependencies();
            
            console.log('Header loaded successfully');
        } else {
            console.warn('Header not found in response');
        }
    } catch (error) {
        console.error('Error loading header:', error);
        // Fallback to simple header
        headerElement.innerHTML = `
            <nav class="navbar">
                <div class="container">
                    <div class="logo">
                        <a href="/"><img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales"></a>
                    </div>
                </div>
            </nav>
        `;
    }
}

function loadHeaderDependencies() {
    // Load Font Awesome if not already loaded
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        link.crossOrigin = 'anonymous';
        document.head.appendChild(link);
    }
    
    // Load main styles if not already loaded
    if (!document.querySelector('link[href*="/styles.css"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = '/styles.css';
        document.head.appendChild(link);
    }
}

// Initialize header when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => loadHeader());
} else {
    loadHeader();
}