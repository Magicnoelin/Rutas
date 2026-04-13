// alojamiento.js - Main entry point for accommodation pages
import { fetchAlojamiento } from './modules/api.js';
import { renderDatos } from './modules/ui.js';
import { loadHeader } from './modules/header.js';
import { loadFooter } from './modules/footer.js';

async function inicializar() {
    // Load corporate header and footer first for better UX
    await Promise.all([
        loadHeader(),
        loadFooter()
    ]);

    const params = new URLSearchParams(window.location.search);
    const slug = params.get("slug");

    if (!slug) {
        console.warn('No slug parameter found in URL');
        return;
    }

    try {
        const data = await fetchAlojamiento(slug);
        
        // Verify gallery element exists
        const galeria = document.getElementById("galeria");
        
        if (galeria) {
            renderDatos(data);
        } else {
            console.error("El elemento 'galeria' no existe en el DOM aún.");
        }
    } catch (error) {
        console.error("Error loading accommodation data:", error);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializar);
} else {
    inicializar();
}
