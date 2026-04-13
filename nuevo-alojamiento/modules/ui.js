// modules/ui.js

// 1. Diccionario de Iconos (Añade aquí los que necesites)
const ICONOS = {
    wifi: '📶',
    parking: '🅿️',
    piscina: '🏊',
    mapa: '📍',
    calefacción: '🔥',
    tv: '📺'
};

/**
 * Función principal que rellena toda la página
 */
export function renderDatos(data) {
    if (!data) return console.error("No hay datos para renderizar");

    // --- A. SEO DINÁMICO (Campos personalizados de tu DB) ---
    document.title = data.meta_title || `${data.name} | Rutas Rurales`;
    const metaDesc = document.querySelector('meta[name="description"]');
    if (metaDesc) {
        metaDesc.setAttribute("content", data.meta_description || "Descubre este alojamiento rural.");
    }

    // --- B. TEXTOS Y CABECERA ---
    const IDs = {
        "nombre": data.name,
        "descripcion": data.description,
        "bread-nombre": data.name,
        "bread-municipio": data.municipality || "Soria"
    };

    Object.keys(IDs).forEach(id => {
        const elem = document.getElementById(id);
        if (elem) {
            if (id === "descripcion") elem.innerHTML = IDs[id]; // Permite HTML en descripción
            else elem.textContent = IDs[id];
        }
    });

    // --- C. RENDERIZAR GALERÍA ---
    renderGaleria(data);

    // --- D. SERVICIOS E ICONOS ---
    const serviciosCont = document.getElementById("servicios");
    if (serviciosCont) {
        const listaServicios = (data.services || "wifi,calefacción,tv").split(",");
        serviciosCont.innerHTML = listaServicios.map(s => `
            <div class="service-item">
                <span class="icon">${ICONOS[s.trim().toLowerCase()] || '✔️'}</span>
                <span>${s.trim()}</span>
            </div>
        `).join("");
    }

    // --- E. SIDEBAR (Reserva y WhatsApp) ---
    const contactoBox = document.getElementById("contacto-box");
    if (contactoBox) {
        contactoBox.innerHTML = `
            <p class="price-hint" style="font-size:0.9rem; color:#666; margin-bottom:10px;">Consúltanos directamente:</p>
            <a href="https://wa.me/${data.phone}" class="btn-whatsapp" target="_blank">
                📲 Contactar por WhatsApp
            </a>
        `;
    }

    // --- F. LÓGICA DE MEMBRESÍA PREMIUM ---
    const premiumBox = document.getElementById("premium-extra");
    if (premiumBox && data.membership === "premium") {
        premiumBox.innerHTML = `
            <div class="premium-card" style="background:#fff3e0; padding:15px; border-radius:10px; margin-top:20px; border:1px solid #ffe0b2;">
                <h4 style="margin:0; color:#e65100;">⭐ Ruta Exclusiva</h4>
                <p style="font-size:0.85rem;">Por alojarte aquí, tienes acceso a rutas secretas de la zona.</p>
                <a href="https://rutasrurales.io/rutas.php?slug=${data.slug}" style="color:#e65100; font-weight:bold; text-decoration:none;">Ver en el Mapa →</a>
            </div>
        `;
    }
}

/**
 * Función para manejar las fotos de forma eficiente
 */
function renderGaleria(data) {
    const galeria = document.getElementById("galeria");
    if (!galeria) return;

    const fotos = [data.photo1, data.photo2, data.photo3, data.photo4].filter(f => f);
    
    if (fotos.length === 0) {
        galeria.innerHTML = "<p>No hay fotos disponibles</p>";
        return;
    }

    const fragment = document.createDocumentFragment();
    galeria.innerHTML = ""; 

    fotos.forEach((url, index) => {
        const figure = document.createElement("figure");
        figure.className = "gallery-item";

        const img = document.createElement("img");
img.src = url;
img.alt = `${data.name} - Foto ${index + 1}`;

// Si es la primera imagen (index 0), quitamos lazy y damos prioridad alta
if (index === 0) {
    img.loading = "eager"; // Carga inmediata
    img.setAttribute("fetchpriority", "high"); // Prioridad máxima para el LCP
} else {
    // El resto de imágenes sí llevan lazy loading para no ralentizar
    img.loading = "lazy";
}

img.onerror = () => figure.remove();
        figure.appendChild(img);
        fragment.appendChild(figure);
    });

    galeria.appendChild(fragment);
}