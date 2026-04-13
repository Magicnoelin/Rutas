// alojamiento.js - Main entry point for accommodation pages
import { fetchAlojamiento } from './modules/api.js';
import { renderDatos } from './modules/ui.js';
import { loadHeader } from './modules/header.js';
import { loadFooter } from './modules/footer.js';
import { initCLSOptimizer } from './modules/cls-optimizer.js';

async function inicializar() {
    // Initialize CLS optimizer early
    initCLSOptimizer();
    
    try {
        // Load corporate header and footer first for better UX
        await Promise.allSettled([
            loadHeader(),
            loadFooter()
        ]);
    } catch (error) {
        console.warn('Error loading header/footer, continuing with page content:', error);
    }

    const params = new URLSearchParams(window.location.search);
    const slug = params.get("slug");

    if (!slug) {
        console.warn('No slug parameter found in URL');
        // Show a message to the user
        const nombreElement = document.getElementById("nombre");
        if (nombreElement) {
            nombreElement.textContent = "Alojamiento no encontrado";
        }
        const descripcionElement = document.getElementById("descripcion");
        if (descripcionElement) {
            descripcionElement.innerHTML = "<p>Por favor, verifica que la URL sea correcta o regresa a la <a href='/alojamientos-turisticos.html'>lista de alojamientos</a>.</p>";
        }
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
        // Show error message to user
        const nombreElement = document.getElementById("nombre");
        if (nombreElement) {
            nombreElement.textContent = "Error al cargar el alojamiento";
        }
        const descripcionElement = document.getElementById("descripcion");
        if (descripcionElement) {
            descripcionElement.innerHTML = "<p>Lo sentimos, ha ocurrido un error al cargar la información del alojamiento. Por favor, intenta nuevamente más tarde.</p>";
        }
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializar);
} else {
    inicializar();
}
