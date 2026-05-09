/**
 * EVENTO MODULAR - JavaScript Principal
 * Módulos: Lightbox, Mapa, Contenido Cercano, Engagement, Suscripción
 *
 * Estrategia de carga:
 * 1. Lightbox → inmediato (pequeño, necesario)
 * 2. Mapa → al hacer clic o scroll al 70%
 * 3. Contenido cercano → al hacer scroll al 60%
 * 4. Eventos similares → al hacer scroll al 80%
 */

'use strict';

// ─── ESTADO GLOBAL ────────────────────────────────────────────────────────────
const STATE = {
    evento: window.EVENTO_DATA || null,
    slug: window.EVENTO_SLUG || '',
    lang: window.EVENTO_LANG || 'es',
    ui: window.EVENTO_UI || {},
    mapLoaded: false,
    mapLeaflet: null,
    mapLayers: { evento: null, alojamientos: null, lugares: null, actividades: null },
    nearbyLoaded: false,
    nearbyData: null,
    nearbyAlojamientos: [],
    nearbyLugares: [],
    nearbyActividades: [],
    nearbyShownAloj: 3,
    nearbyShownLug: 3,
    nearbyShownAct: 3,
    lightboxIndex: 0,
    fotos: window.EVENTO_DATA?.fotos || [],
    likes: 0,
    liked: false,
    views: 0,
};

// Acceso rápido a traducciones UI (con fallback en español)
const UI = {
    get: (key, fallback) => STATE.ui[key] || fallback || key,
};

// ─── UTILIDADES ───────────────────────────────────────────────────────────────
function showToast(msg, duration = 3000) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), duration);
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    const locale = STATE.ui.locale || 'es-ES';
    return d.toLocaleDateString(locale, { day: '2-digit', month: 'long', year: 'numeric' });
}

function formatPrice(price) {
    if (!price || price <= 0) return UI.get('consultar', 'Consultar');
    return parseFloat(price).toFixed(2).replace('.', ',') + '€';
}

// ─── MÓDULO: LIGHTBOX ─────────────────────────────────────────────────────────
function openLightbox(index) {
    if (!STATE.fotos.length) return;
    STATE.lightboxIndex = index;
    const lb = document.getElementById('lightbox');
    const img = document.getElementById('lightbox-img');
    if (!lb || !img) return;
    img.src = STATE.fotos[index];
    img.alt = `Foto ${index + 1}`;
    lb.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lb = document.getElementById('lightbox');
    if (lb) lb.classList.remove('active');
    document.body.style.overflow = '';
}

function closeLightboxOnOverlay(e) {
    if (e.target === document.getElementById('lightbox')) closeLightbox();
}

function lightboxNav(dir) {
    const total = STATE.fotos.length;
    if (!total) return;
    STATE.lightboxIndex = (STATE.lightboxIndex + dir + total) % total;
    const img = document.getElementById('lightbox-img');
    if (img) {
        img.style.opacity = '0';
        setTimeout(() => {
            img.src = STATE.fotos[STATE.lightboxIndex];
            img.style.opacity = '1';
        }, 150);
    }
}

// Teclado para lightbox
document.addEventListener('keydown', (e) => {
    const lb = document.getElementById('lightbox');
    if (!lb?.classList.contains('active')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') lightboxNav(-1);
    if (e.key === 'ArrowRight') lightboxNav(1);
});

// ─── MÓDULO: MAPA (Leaflet) ───────────────────────────────────────────────────
function initMap() {
    if (STATE.mapLoaded) return;
    if (!STATE.evento?.latitude || !STATE.evento?.longitude) return;

    const placeholder = document.getElementById('map-placeholder');
    const mapEl = document.getElementById('event-map');
    const controls = document.getElementById('map-controls');

    if (!mapEl) return;

    // Mostrar loading
    if (placeholder) {
        placeholder.innerHTML = `<div style="font-size:2rem;">⏳</div><p>${UI.get('cargando_mapa', 'Cargando mapa...')}</p>`;
    }

    // Cargar Leaflet JS si no está cargado
    if (typeof L === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => _renderMap(placeholder, mapEl, controls);
        document.head.appendChild(script);
    } else {
        _renderMap(placeholder, mapEl, controls);
    }
}

function _renderMap(placeholder, mapEl, controls) {
    STATE.mapLoaded = true;
    const lat = parseFloat(STATE.evento.latitude);
    const lng = parseFloat(STATE.evento.longitude);

    // Ocultar placeholder, mostrar mapa
    if (placeholder) placeholder.style.display = 'none';
    mapEl.style.display = 'block';
    if (controls) controls.style.display = 'flex';

    // Inicializar mapa Leaflet
    const map = L.map(mapEl, {
        center: [lat, lng],
        zoom: 14,
        zoomControl: true,
        scrollWheelZoom: false, // Evitar scroll accidental
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 18,
    }).addTo(map);

    // Icono personalizado para el evento
    const eventoIcon = L.divIcon({
        html: '<div style="background:#2F5233;color:white;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;box-shadow:0 2px 8px rgba(0,0,0,0.3);">🎭</div>',
        className: '',
        iconSize: [36, 36],
        iconAnchor: [18, 18],
        popupAnchor: [0, -20],
    });

    // Marcador del evento
    const marker = L.marker([lat, lng], { icon: eventoIcon })
        .addTo(map)
        .bindPopup(`
            <div style="min-width:180px;">
                <strong style="color:#2F5233;">${STATE.evento.titulo}</strong><br>
                <small>📍 ${STATE.evento.localidad || STATE.evento.municipality || ''}</small>
            </div>
        `, { maxWidth: 250 })
        .openPopup();

    STATE.mapLeaflet = map;
    STATE.mapLayers.evento = L.layerGroup([marker]);

    // Cargar datos cercanos para el mapa (si no están cargados)
    if (!STATE.nearbyLoaded) {
        loadNearbyData().then(() => {
            // Los datos ya están disponibles para el mapa
        });
    }
}

// Iconos para el mapa
function _createMapIcon(emoji, color) {
    return L.divIcon({
        html: `<div style="background:${color};color:white;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;font-size:1rem;box-shadow:0 2px 6px rgba(0,0,0,0.25);">${emoji}</div>`,
        className: '',
        iconSize: [30, 30],
        iconAnchor: [15, 15],
        popupAnchor: [0, -18],
    });
}

function toggleMapLayer(type) {
    const btn = document.getElementById(`btn-${type}`);
    if (!btn) return;

    if (type === 'evento') return; // El evento siempre visible

    const isActive = btn.classList.contains('active');

    // Si el mapa NO está cargado aún, cargarlo primero y luego añadir la capa
    if (!STATE.mapLeaflet) {
        btn.textContent = btn.textContent + ' ⏳';
        // Cargar el mapa y esperar a que esté listo
        _initMapAndThen(() => {
            btn.textContent = btn.textContent.replace(' ⏳', '');
            btn.classList.add('active');
            _ensureNearbyAndAddLayer(type);
        });
        return;
    }

    if (isActive) {
        if (STATE.mapLayers[type]) {
            STATE.mapLeaflet.removeLayer(STATE.mapLayers[type]);
            STATE.mapLayers[type] = null;
        }
        btn.classList.remove('active');
    } else {
        btn.classList.add('active');
        _ensureNearbyAndAddLayer(type);
    }
}

// Carga el mapa y ejecuta callback cuando esté listo
function _initMapAndThen(callback) {
    if (STATE.mapLeaflet) { callback(); return; }

    // Escuchar cuando el mapa esté listo
    const checkInterval = setInterval(() => {
        if (STATE.mapLeaflet) {
            clearInterval(checkInterval);
            callback();
        }
    }, 100);

    // Iniciar carga del mapa
    initMap();

    // Timeout de seguridad (5s)
    setTimeout(() => clearInterval(checkInterval), 5000);
}

// Asegura que los datos nearby estén cargados y añade la capa
function _ensureNearbyAndAddLayer(type) {
    if (!STATE.nearbyLoaded) {
        const btn = document.getElementById(`btn-${type}`);
        if (btn && !btn.textContent.includes('⏳')) {
            btn.textContent = btn.textContent + ' ⏳';
        }
        loadNearbyData().then(() => {
            if (btn) btn.textContent = btn.textContent.replace(' ⏳', '');
            _addMapLayer(type);
        });
    } else {
        _addMapLayer(type);
    }
}

function _addMapLayer(type) {
    if (!STATE.mapLeaflet || !STATE.nearbyData) return;

    const items = type === 'alojamientos' ? STATE.nearbyAlojamientos
                : type === 'lugares'      ? STATE.nearbyLugares
                : type === 'actividades'  ? STATE.nearbyActividades
                : [];

    if (!items.length) return;

    const config = {
        alojamientos: { emoji: '🏠', color: '#1565C0' },
        lugares:      { emoji: '🏛️', color: '#6A1B9A' },
        actividades:  { emoji: '🎯', color: '#E65100' },
    };
    const { emoji, color } = config[type] || { emoji: '📍', color: '#333' };
    const icon = _createMapIcon(emoji, color);

    const markers = items.map(item => {
        if (!item.latitude || !item.longitude) return null;
        let extraInfo = '';
        if (type === 'alojamientos' && item.price_per_night) {
            extraInfo = `<br><small>💶 ${formatPrice(item.price_per_night)}${UI.get('noche', '/noche')}</small>`;
        } else if (type === 'actividades' && item.price) {
            extraInfo = `<br><small>💶 ${formatPrice(item.price)}${UI.get('persona', '/persona')}</small>`;
        }
        const dist = item.distance > 0 ? `<br><small>📏 ${item.distance} km</small>` : '';
        return L.marker([parseFloat(item.latitude), parseFloat(item.longitude)], { icon })
            .bindPopup(`
                <div style="min-width:160px;">
                    <strong>${item.name}</strong><br>
                    <small>📍 ${item.municipality || ''}</small>
                    ${extraInfo}${dist}
                    <br><a href="${item.url}" style="color:#2F5233;font-size:0.8rem;">${UI.get('ver_mas', 'Ver más →')}</a>
                </div>
            `, { maxWidth: 220 });
    }).filter(Boolean);

    STATE.mapLayers[type] = L.layerGroup(markers).addTo(STATE.mapLeaflet);
}

// ─── MÓDULO: CONTENIDO CERCANO ────────────────────────────────────────────────
async function loadNearbyData() {
    if (STATE.nearbyLoaded) return STATE.nearbyData;

    const ev = STATE.evento;
    if (!ev) return null;

    const params = new URLSearchParams({
        slug: STATE.slug,
        lang: STATE.lang,
        mode: 'nearby',
        prov: ev.province || '',
    });
    if (ev.latitude && ev.longitude) {
        params.set('lat', ev.latitude);
        params.set('lng', ev.longitude);
    }

    try {
        const res = await fetch(`/evento-modular/api/evento-data.php?${params}`);
        const json = await res.json();

        if (json.success) {
            STATE.nearbyData = json.data;
            STATE.nearbyAlojamientos = json.data.alojamientos || [];
            STATE.nearbyLugares = json.data.lugares || [];
            STATE.nearbyActividades = json.data.actividades || [];
            STATE.nearbyLoaded = true;

            _renderNearbyAlojamientos();
            _renderNearbyLugares();
            _renderNearbyActividades();
            _renderSimilarEvents(json.data.eventos_similares || []);
        }
    } catch (e) {
        console.warn('Error cargando contenido cercano:', e);
    }

    return STATE.nearbyData;
}

function _renderNearbyAlojamientos() {
    const container = document.getElementById('nearby-alojamientos');
    const section = document.getElementById('nearby-section');
    const moreBtn = document.getElementById('more-alojamientos');
    if (!container || !STATE.nearbyAlojamientos.length) return;

    section.style.display = 'block';
    const shown = STATE.nearbyAlojamientos.slice(0, STATE.nearbyShownAloj);

    container.innerHTML = shown.map(a => `
        <a href="${a.url}" class="nearby-card" style="text-decoration:none;color:inherit;">
            <div class="nearby-card-img">
                ${a.main_image ? `<img src="${a.main_image}" alt="${a.name}" loading="lazy">` : '<div style="height:100%;background:#e8f0e8;display:flex;align-items:center;justify-content:center;font-size:2rem;">🏠</div>'}
            </div>
            <div class="nearby-card-body">
                <div class="nearby-card-name">${a.name}</div>
                <div class="nearby-card-meta">📍 ${a.municipality || a.province || ''}</div>
                ${a.distance > 0 ? `<div class="nearby-card-meta">📏 ${a.distance} km</div>` : ''}
                ${a.price_per_night ? `<div class="nearby-card-price">💶 ${formatPrice(a.price_per_night)}${UI.get('noche', '/noche')}</div>` : ''}
            </div>
        </a>
    `).join('');

    if (STATE.nearbyAlojamientos.length > STATE.nearbyShownAloj && moreBtn) {
        moreBtn.style.display = 'block';
    }
}

function _renderNearbyLugares() {
    const container = document.getElementById('nearby-lugares');
    const section = document.getElementById('nearby-lugares-section');
    const moreBtn = document.getElementById('more-lugares');
    if (!container || !STATE.nearbyLugares.length) return;

    section.style.display = 'block';
    const shown = STATE.nearbyLugares.slice(0, STATE.nearbyShownLug);

    container.innerHTML = shown.map(l => `
        <a href="${l.url}" class="nearby-card" style="text-decoration:none;color:inherit;">
            <div class="nearby-card-img">
                ${l.main_image ? `<img src="${l.main_image}" alt="${l.name}" loading="lazy">` : '<div style="height:100%;background:#e8f0e8;display:flex;align-items:center;justify-content:center;font-size:2rem;">🏛️</div>'}
            </div>
            <div class="nearby-card-body">
                <div class="nearby-card-name">${l.name}</div>
                <div class="nearby-card-meta">📍 ${l.municipality || l.province || ''}</div>
                ${l.distance > 0 ? `<div class="nearby-card-meta">📏 ${l.distance} km</div>` : ''}
            </div>
        </a>
    `).join('');

    if (STATE.nearbyLugares.length > STATE.nearbyShownLug && moreBtn) {
        moreBtn.style.display = 'block';
    }
}

function _renderNearbyActividades() {
    const container = document.getElementById('nearby-actividades');
    const section = document.getElementById('nearby-actividades-section');
    const moreBtn = document.getElementById('more-actividades');
    if (!container || !STATE.nearbyActividades.length) return;

    section.style.display = 'block';
    const shown = STATE.nearbyActividades.slice(0, STATE.nearbyShownAct);

    container.innerHTML = shown.map(a => `
        <a href="${a.url}" class="nearby-card" style="text-decoration:none;color:inherit;">
            <div class="nearby-card-img">
                ${a.main_image ? `<img src="${a.main_image}" alt="${a.name}" loading="lazy">` : '<div style="height:100%;background:#e8f0e8;display:flex;align-items:center;justify-content:center;font-size:2rem;">🎯</div>'}
            </div>
            <div class="nearby-card-body">
                <div class="nearby-card-name">${a.name}</div>
                <div class="nearby-card-meta">📍 ${a.municipality || a.province || ''}</div>
                ${a.distance > 0 ? `<div class="nearby-card-meta">📏 ${a.distance} km</div>` : ''}
                ${a.price ? `<div class="nearby-card-price">💶 ${formatPrice(a.price)}${UI.get('persona', '/persona')}</div>` : ''}
            </div>
        </a>
    `).join('');

    if (STATE.nearbyActividades.length > STATE.nearbyShownAct && moreBtn) {
        moreBtn.style.display = 'block';
    }
}

function _renderSimilarEvents(eventos) {
    const container = document.getElementById('similar-events');
    const section = document.getElementById('similar-section');
    if (!container || !eventos.length) return;

    section.style.display = 'block';

    container.innerHTML = eventos.map(e => {
        const precio = e.is_free == 1 ? `🆓 ${UI.get('gratis', 'Gratis')}` : (e.ticket_price > 0 ? `💶 ${formatPrice(e.ticket_price)}` : '');
        const fecha = formatDate(e.start_date);
        const img = e.imagen
            ? `<img src="${e.imagen}" alt="${e.name}" loading="lazy">`
            : '<div style="height:100%;background:linear-gradient(135deg,#e8f0e8,#c8dcc8);display:flex;align-items:center;justify-content:center;font-size:2.5rem;">🎭</div>';

        return `
            <a href="${e.url}" class="similar-event-card" style="text-decoration:none;color:inherit;">
                <div class="similar-event-img">
                    ${img}
                    ${precio ? `<div class="similar-event-badge">${precio}</div>` : ''}
                </div>
                <div class="similar-event-body">
                    <div class="similar-event-name">${e.name}</div>
                    <div class="similar-event-meta">
                        ${fecha ? `<span>📅 ${fecha}</span>` : ''}
                        <span>📍 ${e.municipality || e.province || ''}</span>
                    </div>
                </div>
            </a>
        `;
    }).join('');
}

function showMoreNearby(type) {
    if (type === 'alojamientos') {
        STATE.nearbyShownAloj += 5;
        _renderNearbyAlojamientos();
        if (STATE.nearbyShownAloj >= STATE.nearbyAlojamientos.length) {
            const btn = document.getElementById('more-alojamientos');
            if (btn) btn.style.display = 'none';
        }
    } else if (type === 'lugares') {
        STATE.nearbyShownLug += 5;
        _renderNearbyLugares();
        if (STATE.nearbyShownLug >= STATE.nearbyLugares.length) {
            const btn = document.getElementById('more-lugares');
            if (btn) btn.style.display = 'none';
        }
    } else if (type === 'actividades') {
        STATE.nearbyShownAct += 3;
        _renderNearbyActividades();
        if (STATE.nearbyShownAct >= STATE.nearbyActividades.length) {
            const btn = document.getElementById('more-actividades');
            if (btn) btn.style.display = 'none';
        }
    }
}

// ─── MÓDULO: ESTADÍSTICAS (Likes / Visitas reales de BD) ──────────────────────
async function loadStats() {
    if (!STATE.slug) return;
    
    const likeKey = `like_event_${STATE.slug}`;
    STATE.liked = !!localStorage.getItem(likeKey);
    
    try {
        // Obtener estadísticas reales de la API
        const res = await fetch(`/api/evento-stats.php?slug=${encodeURIComponent(STATE.slug)}`);
        const json = await res.json();
        
        if (json.success && json.data) {
            STATE.views = json.data.views || 0;
            STATE.likes = json.data.likes || 0;
        } else {
            // Fallback si la API falla
            STATE.views = 0;
            STATE.likes = 0;
        }
    } catch (e) {
        console.warn('Error cargando estadísticas:', e);
        STATE.views = 0;
        STATE.likes = 0;
    }
    
    _renderStats();
}

function _renderStats() {
    const likeBtn = document.getElementById('btn-like');
    const likeCount = document.getElementById('like-count');
    const viewCount = document.getElementById('view-count');

    if (likeBtn) likeBtn.textContent = STATE.liked ? '❤️' : '🤍';
    if (likeCount) likeCount.textContent = STATE.likes;
    if (viewCount) viewCount.textContent = STATE.views > 999 ? (STATE.views/1000).toFixed(1) + 'k' : STATE.views;
}

async function toggleLike() {
    if (!STATE.slug) return;
    
    const likeKey = `like_event_${STATE.slug}`;
    const likeBtn = document.getElementById('btn-like');
    const likeCount = document.getElementById('like-count');
    
    const action = STATE.liked ? 'unlike' : 'like';
    
    // Actualizar UI inmediatamente (optimista)
    STATE.liked = !STATE.liked;
    
    if (STATE.liked) {
        STATE.likes++;
        localStorage.setItem(likeKey, '1');
        showToast(UI.get('te_gusta', '❤️ ¡Te gusta este evento!'));
    } else {
        STATE.likes = Math.max(0, STATE.likes - 1);
        localStorage.removeItem(likeKey);
        showToast(UI.get('me_gusta_eliminado', '🤍 Me gusta eliminado'));
    }
    
    if (likeBtn) likeBtn.textContent = STATE.liked ? '❤️' : '🤍';
    if (likeCount) likeCount.textContent = STATE.likes;
    
    // Animación
    if (likeBtn) {
        likeBtn.style.transform = 'scale(1.4)';
        setTimeout(() => likeBtn.style.transform = 'scale(1)', 200);
    }
    
    // Enviar a la API en segundo plano
    try {
        await fetch('/api/evento-stats.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                slug: STATE.slug,
                action: action
            })
        });
    } catch (e) {
        console.warn('Error guardando like:', e);
    }
}

async function trackView() {
    if (!STATE.slug) return;
    
    try {
        await fetch('/api/evento-stats.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                slug: STATE.slug,
                action: 'view'
            })
        });
    } catch (e) {
        console.warn('Error trackeando vista:', e);
    }
}

// ─── MÓDULO: ENGAGEMENT ───────────────────────────────────────────────────────
function saveEvent() {
    const btn = document.getElementById('btn-save-event');
    const saved = localStorage.getItem(`saved_event_${STATE.slug}`);

    if (saved) {
        localStorage.removeItem(`saved_event_${STATE.slug}`);
        if (btn) btn.textContent = UI.get('guardar_evento', '🔖 Guardar evento');
        showToast(UI.get('evento_eliminado', 'Evento eliminado de guardados'));
    } else {
        localStorage.setItem(`saved_event_${STATE.slug}`, JSON.stringify({
            slug: STATE.slug,
            titulo: STATE.evento?.titulo,
            fecha: STATE.evento?.start_date,
            savedAt: new Date().toISOString(),
        }));
        if (btn) btn.textContent = UI.get('guardado', '✅ Guardado');
        showToast(UI.get('evento_guardado', '✅ Evento guardado correctamente'));
    }
}

function addToRoute() {
    // Verificar si el usuario está logueado (cookie o localStorage)
    const isLoggedIn = document.cookie.includes('user_session') || localStorage.getItem('user_id');

    if (!isLoggedIn) {
        showToast(UI.get('inicia_sesion', 'Inicia sesión para añadir a tu ruta'));
        setTimeout(() => {
            window.location.href = `/login.html?action=register&ref=evento&slug=${encodeURIComponent(STATE.slug)}`;
        }, 1500);
        return;
    }

    // Añadir a ruta local
    const routes = JSON.parse(localStorage.getItem('my_route') || '[]');
    const exists = routes.find(r => r.slug === STATE.slug);

    if (exists) {
        showToast(UI.get('ya_en_ruta', 'Este evento ya está en tu ruta'));
        return;
    }

    routes.push({
        type: 'evento',
        slug: STATE.slug,
        titulo: STATE.evento?.titulo,
        fecha: STATE.evento?.start_date,
        lat: STATE.evento?.latitude,
        lng: STATE.evento?.longitude,
        addedAt: new Date().toISOString(),
    });
    localStorage.setItem('my_route', JSON.stringify(routes));
    showToast(UI.get('anadido_ruta', '🗺️ Añadido a tu ruta'));
}

function shareEvent(platform) {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(STATE.evento?.titulo || 'Evento en Rutas Rurales');
    const text = encodeURIComponent(`${STATE.evento?.titulo || 'Evento'}`);

    const urls = {
        whatsapp: `https://wa.me/?text=${text}%20${url}`,
        twitter:  `https://twitter.com/intent/tweet?text=${text}&url=${url}`,
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
        copy:     null,
    };

    if (platform === 'copy') {
        navigator.clipboard?.writeText(window.location.href)
            .then(() => showToast(UI.get('enlace_copiado', '🔗 Enlace copiado')))
            .catch(() => showToast(UI.get('error_copiar', 'No se pudo copiar el enlace')));
        return;
    }

    if (urls[platform]) {
        window.open(urls[platform], '_blank', 'width=600,height=400');
    }
}

// ─── MÓDULO: SUSCRIPCIÓN ──────────────────────────────────────────────────────
async function subscribeEvents(e) {
    e.preventDefault();
    const emailInput = document.getElementById('subscribe-email');
    const email = emailInput?.value?.trim();

    if (!email) return;

    const btn = e.target.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = UI.get('enviando', '⏳ Enviando...'); }

    try {
        // Guardar suscripción en localStorage (fallback si no hay API)
        const subs = JSON.parse(localStorage.getItem('event_subscriptions') || '[]');
        subs.push({
            email,
            categoria: STATE.evento?.categoria,
            province: STATE.evento?.province,
            slug: STATE.slug,
            subscribedAt: new Date().toISOString(),
        });
        localStorage.setItem('event_subscriptions', JSON.stringify(subs));

        // Intentar enviar a API
        try {
            await fetch('/api/subscribe-events.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email,
                    categoria: STATE.evento?.categoria,
                    province: STATE.evento?.province,
                    source_slug: STATE.slug,
                    source_event_id: STATE.evento?.id,
                }),
            });
        } catch (_) { /* API no disponible, guardado en localStorage */ }

        // Mostrar confirmación
        const card = document.getElementById('subscribe-card');
        const catName = STATE.evento?.categoria || UI.get('esta_categoria', 'esta categoría');
        const provName = STATE.evento?.province || UI.get('tu_zona', 'tu zona');
        if (card) {
            card.innerHTML = `
                <div style="font-size:2rem;margin-bottom:8px;">✅</div>
                <h3 style="color:#2F5233;">${UI.get('suscripcion_ok_h3', '¡Suscripción confirmada!')}</h3>
                <p>${UI.get('suscripcion_ok_p1', 'Te avisaremos de eventos de')} <strong>${catName}</strong> ${UI.get('suscripcion_ok_p2', 'en')} <strong>${provName}</strong>.</p>
            `;
        }
        showToast(UI.get('suscripcion_ok', '🔔 ¡Suscripción confirmada!'));

    } catch (err) {
        showToast(UI.get('error_suscripcion', 'Error al suscribirse. Inténtalo de nuevo.'));
        if (btn) { btn.disabled = false; btn.textContent = UI.get('suscripcion_btn', '🔔 Suscribirme'); }
    }
}

// ─── FALLBACK: Rellenar etiquetas vacías desde EVENTO_UI ─────────────────────
// Cubre el caso en que el PHP del servidor tiene un $ui incompleto
function _fillEmptyLabels() {
    const ui = STATE.ui;
    if (!ui || !Object.keys(ui).length) return;

    // Rellenar card-titles vacíos por orden de aparición
    const cardTitles = document.querySelectorAll('.card-title');
    const titleKeys = ['sobre_evento','info_evento','aloj_cercanos','lugares_cercanos','activ_cercanas','eventos_similares'];
    let keyIdx = 0;
    cardTitles.forEach(el => {
        if (!el.textContent.trim() && keyIdx < titleKeys.length) {
            const val = ui[titleKeys[keyIdx]];
            if (val) el.textContent = val;
            keyIdx++;
        }
    });

    // Rellenar meta-labels vacíos por orden de aparición
    const metaLabels = document.querySelectorAll('.meta-label');
    const metaKeys = ['fecha_inicio','fecha_fin','ubicacion','direccion','categoria','precio','organiza'];
    let mIdx = 0;
    metaLabels.forEach(el => {
        if (!el.textContent.trim() && mIdx < metaKeys.length) {
            const val = ui[metaKeys[mIdx]];
            if (val) el.textContent = val;
            mIdx++;
        }
    });

    // Rellenar botones del mapa vacíos
    [['btn-evento','btn_evento'],['btn-alojamientos','btn_alojamientos'],
     ['btn-lugares','btn_lugares'],['btn-actividades','btn_actividades']].forEach(([id,key]) => {
        const btn = document.getElementById(id);
        if (btn && !btn.textContent.trim() && ui[key]) btn.textContent = ui[key];
    });

    // Rellenar mapa placeholder
    const mapStrong = document.querySelector('.map-placeholder strong');
    if (mapStrong && !mapStrong.textContent.trim() && ui.ver_mapa) mapStrong.textContent = ui.ver_mapa;
    const mapP = document.querySelector('.map-placeholder p');
    if (mapP && !mapP.textContent.trim() && ui.click_mapa) mapP.textContent = ui.click_mapa;

    // Rellenar botones "ver más"
    const moreAloj = document.querySelector('#more-alojamientos button');
    if (moreAloj && !moreAloj.textContent.trim() && ui.ver_mas_aloj) moreAloj.textContent = ui.ver_mas_aloj;
    const moreLug = document.querySelector('#more-lugares button');
    if (moreLug && !moreLug.textContent.trim() && ui.ver_mas_lugares) moreLug.textContent = ui.ver_mas_lugares;
    const moreAct = document.querySelector('#more-actividades button');
    if (moreAct && !moreAct.textContent.trim() && ui.ver_mas_activ) moreAct.textContent = ui.ver_mas_activ;

    // Rellenar CTA sidebar
    const ctaH3 = document.querySelector('#cta-register h3');
    if (ctaH3 && !ctaH3.textContent.trim() && ui.cta_titulo) ctaH3.textContent = ui.cta_titulo;
    const ctaBtns = document.querySelectorAll('#cta-register .btn');
    if (ctaBtns[0] && !ctaBtns[0].textContent.trim() && ui.cta_register) ctaBtns[0].textContent = ui.cta_register;
    if (ctaBtns[1] && !ctaBtns[1].textContent.trim() && ui.cta_login) ctaBtns[1].textContent = ui.cta_login;

    // Rellenar botones guardar/añadir
    const btnSave = document.getElementById('btn-save-event');
    if (btnSave && !btnSave.textContent.trim() && ui.guardar) btnSave.textContent = ui.guardar;

    // Rellenar suscripción
    const subH3 = document.querySelector('.subscribe-card h3');
    if (subH3 && !subH3.textContent.trim() && ui.suscripcion_titulo) subH3.textContent = ui.suscripcion_titulo;
    const subBtn = document.querySelector('.subscribe-form button[type="submit"]');
    if (subBtn && !subBtn.textContent.trim() && ui.suscripcion_btn) subBtn.textContent = ui.suscripcion_btn;

    // Rellenar visitas/likes labels
    const visitasEl = document.querySelector('#view-count + div');
    if (visitasEl && !visitasEl.textContent.trim() && ui.visitas) visitasEl.textContent = ui.visitas;
}

// ─── INICIALIZACIÓN Y SCROLL OBSERVER ────────────────────────────────────────
function init() {
    // Rellenar etiquetas vacías (fallback para versiones antiguas del servidor)
    _fillEmptyLabels();

    // Restaurar estado de guardado
    if (STATE.slug && localStorage.getItem(`saved_event_${STATE.slug}`)) {
        const btn = document.getElementById('btn-save-event');
        if (btn) btn.textContent = UI.get('guardado', '✅ Guardado');
    }

    // Ocultar CTA de registro si ya está logueado
    const isLoggedIn = document.cookie.includes('user_session') || localStorage.getItem('user_id');
    if (isLoggedIn) {
        const cta = document.getElementById('cta-register');
        if (cta) cta.style.display = 'none';
    }

    // Intersection Observer para carga diferida
    if ('IntersectionObserver' in window) {
        // Observer para el mapa (carga al 70% de visibilidad)
        const mapContainer = document.getElementById('event-map-container');
        if (mapContainer) {
            const mapObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !STATE.mapLoaded) {
                        // Pequeño delay para no bloquear el scroll
                        setTimeout(initMap, 300);
                        mapObserver.disconnect();
                    }
                });
            }, { threshold: 0.1, rootMargin: '200px' });
            mapObserver.observe(mapContainer);
        }

        // Observer para contenido cercano (carga al hacer scroll)
        const nearbyTrigger = document.getElementById('nearby-section') ||
                              document.getElementById('similar-section');
        if (nearbyTrigger) {
            const nearbyObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !STATE.nearbyLoaded) {
                        loadNearbyData();
                        nearbyObserver.disconnect();
                    }
                });
            }, { threshold: 0, rootMargin: '400px' });
            nearbyObserver.observe(nearbyTrigger);
        }
    } else {
        // Fallback para navegadores sin IntersectionObserver
        window.addEventListener('scroll', function onScroll() {
            const scrolled = window.scrollY / (document.body.scrollHeight - window.innerHeight);
            if (scrolled > 0.4 && !STATE.mapLoaded) initMap();
            if (scrolled > 0.5 && !STATE.nearbyLoaded) {
                loadNearbyData();
                window.removeEventListener('scroll', onScroll);
            }
        }, { passive: true });
    }

    // Precargar datos cercanos después de 3 segundos (si el usuario no ha scrolleado)
    setTimeout(() => {
        if (!STATE.nearbyLoaded) loadNearbyData();
    }, 3000);

    // Cargar estadísticas reales desde la BD y registrar vista
    loadStats();
    trackView();
}

// Ejecutar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

// Exponer funciones globales necesarias para los onclick del HTML
window.openLightbox = openLightbox;
window.closeLightbox = closeLightbox;
window.closeLightboxOnOverlay = closeLightboxOnOverlay;
window.lightboxNav = lightboxNav;
window.initMap = initMap;
window.toggleMapLayer = toggleMapLayer;
window.showMoreNearby = showMoreNearby;
window.saveEvent = saveEvent;
window.addToRoute = addToRoute;
window.shareEvent = shareEvent;
window.subscribeEvents = subscribeEvents;
window.toggleLike = toggleLike;
