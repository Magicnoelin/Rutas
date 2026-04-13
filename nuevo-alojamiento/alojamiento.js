// alojamiento.js
import { fetchAlojamiento } from './modules/api.js';
import { renderDatos } from './modules/ui.js';

async function inicializar() {
    const params = new URLSearchParams(window.location.search);
    const slug = params.get("slug");

    if (!slug) return;

    try {
        const data = await fetchAlojamiento(slug);
        
        // ESTA ES LA LÍNEA 8 (Asegúrate de que el ID coincida con el HTML)
        const galeria = document.getElementById("galeria");
        
        if (galeria) {
            renderDatos(data);
        } else {
            console.error("El elemento 'galeria' no existe en el DOM aún.");
        }
    } catch (error) {
        console.error("Fallo:", error);
    }
}

inicializar();