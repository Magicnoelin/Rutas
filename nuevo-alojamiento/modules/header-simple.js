// header-simple.js - Cargar header optimizado para páginas modulares
export async function loadSimpleHeader() {
    const headerElement = document.getElementById('header');
    if (!headerElement) return;

    try {
        // Cargar header simple desde archivo HTML
        const response = await fetch('header-simple.html');
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const html = await response.text();
        
        // Insertar el header completo (incluye su propio CSS inline)
        headerElement.innerHTML = html;
        
        console.log('Header simple cargado exitosamente');
        
        // Ajustar padding del body para header fijo
        document.body.style.paddingTop = '70px';
        
    } catch (error) {
        console.error('Error cargando header simple:', error);
        // Fallback a header mínimo
        loadFallbackHeader(headerElement);
    }
}

function loadFallbackHeader(headerElement) {
    headerElement.innerHTML = `
        <header style="height: 70px; background: #4A6741; position: fixed; top: 0; left: 0; right: 0; z-index: 1000; display: flex; align-items: center; padding: 0 20px;">
            <div style="display: flex; align-items: center; width: 100%; max-width: 1200px; margin: 0 auto;">
                <a href="/" style="margin-right: auto;">
                    <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales" style="height: 35px; width: auto;">
                </a>
                <div style="display: flex; gap: 15px;">
                    <a href="/alojamientos-turisticos.html" style="color: white; text-decoration: none; font-size: 0.9rem;">Alojamientos</a>
                    <a href="/lugares-interes-paginacion.html" style="color: white; text-decoration: none; font-size: 0.9rem;">Lugares</a>
                    <a href="/eventos-culturales-paginacion.html" style="color: white; text-decoration: none; font-size: 0.9rem;">Eventos</a>
                </div>
            </div>
        </header>
    `;
    document.body.style.paddingTop = '70px';
}

// Auto-inicializar si está en el contexto global
if (typeof window !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => loadSimpleHeader());
    } else {
        loadSimpleHeader();
    }
}