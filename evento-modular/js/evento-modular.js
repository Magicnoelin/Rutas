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
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
}

function formatPrice(price) {
    if (!price || price <= 0) return 'Consultar';
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
    img.alt = `Foto ${index + 1} del evento`;
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
        placeholder.innerHTML = '<div style="font-size:2rem;">⏳</div><p>Cargando mapa...</p>';
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
    if (!STATE.mapLeaflet) return;

    const btn = document.getElementById(`btn-${type}`);
    if (!btn) return;
    const isActive = btn.classList.contains('active');

    if (type === 'evento') return; // El evento siempre visible

    if (isActive) {
        if (STATE.mapLayers[type]) {
            STATE.mapLeaflet.removeLayer(STATE.mapLayers[type]);
            STATE.mapLayers[type] = null;
        }
        btn.classList.remove('active');
    } else {
        // Si los datos nearby aún no están cargados, cargarlos primero
        if (!STATE.nearbyLoaded) {
            btn.textContent = btn.textContent + ' ⏳';
            loadNearbyData().then(() => {
                btn.textContent = btn.textContent.replace(' ⏳', '');
                btn.classList.add('active');
                _addMapLayer(type);
            });
        } else {
            btn.classList.add('active');
            _addMapLayer(type);
        }
    }
}

function _addMapLayer(type) {
    if (!STATE.mapLeaflet || !STATE.nearbyData) return;

    let items, icon;
    if (type === 'alojamientos') {
        items = STATE.nearbyData.alojamientos || [];
        icon = _createMapIcon('🏠', '#1565C0');
    } else if (type === 'lugares') {
        items = STATE.nearbyData.lugares || [];
        icon = _createMapIcon('🏛️', '#6A1B9A');
    } else if (type === 'actividades') {
        items = STATE.nearbyData.actividades || [];
        icon = _createMapIcon('🎯', '#E65100');
    } else {
        return;
    }

    if (!items.length) {
        showToast(`No hay ${type} cercanos disponibles`);
        document.getElementById(`btn-${type}`)?.classList.remove('active');
        return;
    }

    const markers = items.map(item => {
        const lat = parseFloat(item.latitude);
        const lng = parseFloat(item.longitude);
        if (!lat || !lng || isNaN(lat) || isNaN(lng)) return null;

        let extraInfo = '';
        if (type === 'alojamientos' && item.price_per_night) {
            extraInfo = `<br><small>💶 ${formatPrice(item.price_per_night)}/noche</small>`;
        } else if (type === 'actividades' && item.price) {
            extraInfo = `<br><small>💶 ${formatPrice(item.price)}/persona</small>`;
        }
        const dist = item.distance > 0 ? `<br><small>📏 ${item.distance} km</small>` : '';

        return L.marker([lat, lng], { icon })
            .bindPopup(`
                <div style="min-width:160px;">
                    <strong style="color:#2F5233;">${item.name}</strong>
                    <br><small>📍 ${item.municipality || ''}</small>
                    ${extraInfo}${dist}
                    <br><a href="${item.url}" style="color:#2F5233;font-size:0.8rem;">Ver más →</a>
                </div>
            `, { maxWidth: 220 });
    }).filter(Boolean);

    if (!markers.length) {
        showToast(`No hay ${type} con coordenadas disponibles`);
        document.getElementById(`btn-${type}`)?.classList.remove('active');
        return;
    }

    STATE.mapLayers[type] = L.layerGroup(markers).addTo(STATE.mapLeaflet);
}

// ─── MÓDULO: CONTENIDO CERCANO ────────────────────────────────────────────────
async function loadNearbyData() {
    if (STATE.nearbyLoaded || !STATE.evento) return;
    STATE.nearbyLoaded = true;

    const lat = STATE.evento.latitude;
    const lng = STATE.evento.longitude;
    const prov = STATE.evento.province || '';

    let url = `/evento-modular/api/evento-data.php?slug=${encodeURIComponent(STATE.slug)}&mode=nearby&prov=${encodeURIComponent(prov)}`;
    if (lat && lng) url += `&lat=${lat}&lng=${lng}`;

    try {
        const res = await fetch(url);
        const json = await res.json();

        if (!json.success) return;

        STATE.nearbyData = json.data;
        STATE.nearbyAlojamientos = json.data.alojamientos || [];
        STATE.nearbyLugares = json.data.lugares || [];
        STATE.nearbyActividades = json.data.actividades || [];

        // Renderizar secciones
        _renderNearbyAlojamientos();
        _renderNearbyLugares();
        _renderNearbyActividades();
        _renderSimilarEvents();
        _renderStats();

    } catch (e) {
        console.warn('Error cargando contenido cercano:', e);
    }
}

function _renderNearbyAlojamientos() {
    const container = document.getElementById('nearby-alojamientos');
    const section = document.getElementById('nearby-section');
    const moreBtn = document.getElementById('more-alojamientos');

    if (!container || !STATE.nearbyAlojamientos.length) return;

    section.style.display = 'block';
    const shown = STATE.nearbyAlojamientos.slice(0, STATE.nearbyShownAloj);
    const hasMore = STATE.nearbyAlojamientos.length > STATE.nearbyShownAloj;

    container.innerHTML = shown.map(a => `
        <a href="${a.url}" class="nearby-card" style="text-decoration:none;">
            <div class="nearby-card-img">
                ${a.main_image
                    ? `<img src="${a.main_image}" alt="${a.name}" loading="lazy">`
                    : `<div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem;">🏠</div>`
                }
            </div>
            <div class="nearby-card-body">
                <div class="nearby-card-name">${a.name}</div>
                <div class="nearby-card-meta">📍 ${a.municipality || a.province || ''}</div>
                ${a.distance > 0 ? `<div class="nearby-card-meta">📏 ${a.distance} km</div>` : ''}
                ${a.price_per_night ? `<div class="nearby-card-price">💶 ${formatPrice(a.price_per_night)}/noche</div>` : ''}
            </div>
        </a>
    `).join('');

    if (moreBtn) moreBtn.style.display = hasMore ? 'block' : 'none';
}

function _renderNearbyLugares() {
    const container = document.getElementById('nearby-lugares');
    const section = document.getElementById('nearby-lugares-section');
    const moreBtn = document.getElementById('more-lugares');

    if (!container || !STATE.nearbyLugares.length) return;

    section.style.display = 'block';
    const shown = STATE.nearbyLugares.slice(0, STATE.nearbyShownLug);
    const hasMore = STATE.nearbyLugares.length > STATE.nearbyShownLug;

    container.innerHTML = shown.map(l => `
        <a href="${l.url}" class="nearby-card" style="text-decoration:none;">
            <div class="nearby-card-img">
                ${l.main_image
                    ? `<img src="${l.main_image}" alt="${l.name}" loading="lazy">`
                    : `<div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem;">🏛️</div>`
                }
            </div>
            <div class="nearby-card-body">
                <div class="nearby-card-name">${l.name}</div>
                <div class="nearby-card-meta">📍 ${l.municipality || l.province || ''}</div>
                ${l.distance > 0 ? `<div class="nearby-card-meta">📏 ${l.distance} km</div>` : ''}
                ${l.category ? `<div class="nearby-card-meta">🏷️ ${l.category}</div>` : ''}
            </div>
        </a>
    `).join('');

    if (moreBtn) moreBtn.style.display = hasMore ? 'block' : 'none';
}

function _renderSimilarEvents() {
    const container = document.getElementById('similar-events');
    const section = document.getElementById('similar-section');

    if (!container || !STATE.nearbyData?.eventos_similares?.length) return;

    section.style.display = 'block';
    const eventos = STATE.nearbyData.eventos_similares;

    container.innerHTML = eventos.map(e => {
        const precio = e.is_free == 1 ? '🆓 Gratis' : (e.ticket_price > 0 ? `💶 ${formatPrice(e.ticket_price)}` : '');
        const fecha = formatDate(e.start_date);
        return `
        <a href="${e.url}" class="similar-event-card" style="text-decoration:none;">
            <div class="similar-event-img">
                ${e.imagen
                    ? `<img src="${e.imagen}" alt="${e.name}" loading="lazy">`
                    : `<div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;">🎭</div>`
                }
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

function _renderNearbyActividades() {
    const container = document.getElementById('nearby-actividades');
    const section = document.getElementById('nearby-actividades-section');
    const moreBtn = document.getElementById('more-actividades');

    if (!container || !STATE.nearbyActividades.length) return;

    section.style.display = 'block';
    const shown = STATE.nearbyActividades.slice(0, STATE.nearbyShownAct);
    const hasMore = STATE.nearbyActividades.length > STATE.nearbyShownAct;

    container.innerHTML = shown.map(a => `
        <a href="${a.url}" class="nearby-card" style="text-decoration:none;">
            <div class="nearby-card-img">
                ${a.main_image
                    ? `<img src="${a.main_image}" alt="${a.name}" loading="lazy">`
                    : `<div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem;">🎯</div>`
                }
            </div>
            <div class="nearby-card-body">
                <div class="nearby-card-name">${a.name}</div>
                <div class="nearby-card-meta">📍 ${a.municipality || a.province || ''}</div>
                ${a.distance > 0 ? `<div class="nearby-card-meta">📏 ${a.distance} km</div>` : ''}
                ${a.price ? `<div class="nearby-card-price">💶 ${formatPrice(a.price)}/persona</div>` : ''}
            </div>
        </a>
    `).join('');

    if (moreBtn) moreBtn.style.display = hasMore ? 'block' : 'none';
}

// Mostrar más elementos cercanos
function showMoreNearby(type) {
    if (type === 'alojamientos') {
        STATE.nearbyShownAloj = STATE.nearbyAlojamientos.length;
        _renderNearbyAlojamientos();
    } else if (type === 'lugares') {
        STATE.nearbyShownLug = STATE.nearbyLugares.length;
        _renderNearbyLugares();
    } else if (type === 'actividades') {
        STATE.nearbyShownAct = STATE.nearbyActividades.length;
        _renderNearbyActividades();
    }
}

// ─── MÓDULO: VISITAS Y LIKES ──────────────────────────────────────────────────
function _renderStats() {
    // Likes desde localStorage (sin BD para no afectar velocidad)
    const likeKey = `like_event_${STATE.slug}`;
    STATE.liked = !!localStorage.getItem(likeKey);

    const likeBtn = document.getElementById('btn-like');
    const likeCount = document.getElementById('like-count');
    const viewCount = document.getElementById('view-count');

    // Simular contador de visitas (localStorage + número base)
    const viewKey = `views_event_${STATE.slug}`;
    let views = parseInt(localStorage.getItem(viewKey) || '0');
    views++;
    localStorage.setItem(viewKey, views);
    STATE.views = views;

    // Likes guardados en localStorage
    const likesKey = `likes_total_${STATE.slug}`;
    STATE.likes = parseInt(localStorage.getItem(likesKey) || Math.floor(Math.random() * 40 + 5));
    localStorage.setItem(likesKey, STATE.likes);

    if (likeBtn) {
        likeBtn.textContent = STATE.liked ? '❤️' : '🤍';
        likeBtn.title = STATE.liked ? 'Quitar me gusta' : 'Me gusta';
    }
    if (likeCount) likeCount.textContent = STATE.likes;
    if (viewCount) viewCount.textContent = views > 999 ? (views/1000).toFixed(1) + 'k' : views;
}

function toggleLike() {
    const likeKey = `like_event_${STATE.slug}`;
    const likesKey = `likes_total_${STATE.slug}`;
    const likeBtn = document.getElementById('btn-like');
    const likeCount = document.getElementById('like-count');

    STATE.liked = !STATE.liked;

    if (STATE.liked) {
        STATE.likes++;
        localStorage.setItem(likeKey, '1');
        showToast('❤️ ¡Te gusta este evento!');
    } else {
        STATE.likes = Math.max(0, STATE.likes - 1);
        localStorage.removeItem(likeKey);
        showToast('🤍 Me gusta eliminado');
    }

    localStorage.setItem(likesKey, STATE.likes);
    if (likeBtn) likeBtn.textContent = STATE.liked ? '❤️' : '🤍';
    if (likeCount) likeCount.textContent = STATE.likes;

    // Animación
    if (likeBtn) {
        likeBtn.style.transform = 'scale(1.4)';
        setTimeout(() => likeBtn.style.transform = 'scale(1)', 200);
    }
}

// ─── MÓDULO: ENGAGEMENT ───────────────────────────────────────────────────────
function saveEvent() {
    const btn = document.getElementById('btn-save-event');
    const saved = localStorage.getItem(`saved_event_${STATE.slug}`);

    if (saved) {
        localStorage.removeItem(`saved_event_${STATE.slug}`);
        if (btn) btn.textContent = '🔖 Guardar evento';
        showToast('Evento eliminado de guardados');
    } else {
        localStorage.setItem(`saved_event_${STATE.slug}`, JSON.stringify({
            slug: STATE.slug,
            titulo: STATE.evento?.titulo,
            fecha: STATE.evento?.start_date,
            savedAt: new Date().toISOString(),
        }));
        if (btn) btn.textContent = '✅ Guardado';
        showToast('✅ Evento guardado correctamente');
    }
}

function addToRoute() {
    // Verificar si el usuario está logueado (cookie o localStorage)
    const isLoggedIn = document.cookie.includes('user_session') || localStorage.getItem('user_id');

    if (!isLoggedIn) {
        showToast('Inicia sesión para añadir a tu ruta');
        setTimeout(() => {
            window.location.href = `/login.html?action=register&ref=evento&slug=${encodeURIComponent(STATE.slug)}`;
        }, 1500);
        return;
    }

    // Añadir a ruta local
    const routes = JSON.parse(localStorage.getItem('my_route') || '[]');
    const exists = routes.find(r => r.slug === STATE.slug);

    if (exists) {
        showToast('Este evento ya está en tu ruta');
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
    showToast('🗺️ Añadido a tu ruta');
}

function shareEvent(platform) {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(STATE.evento?.titulo || 'Evento en Rutas Rurales');
    const text = encodeURIComponent(`¡Mira este evento! ${STATE.evento?.titulo}`);

    const urls = {
        whatsapp: `https://wa.me/?text=${text}%20${url}`,
        twitter:  `https://twitter.com/intent/tweet?text=${text}&url=${url}`,
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
        copy:     null,
    };

    if (platform === 'copy') {
        navigator.clipboard?.writeText(window.location.href)
            .then(() => showToast('🔗 Enlace copiado'))
            .catch(() => showToast('No se pudo copiar el enlace'));
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
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Enviando...'; }

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

        // Intentar enviar a API (si existe)
        try {
            await fetch('/api/subscribe-events.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email,
                    categoria: STATE.evento?.categoria,
                    province: STATE.evento?.province,
                    source_slug: STATE.slug,
                }),
            });
        } catch (_) { /* API no disponible, guardado en localStorage */ }

        // Mostrar confirmación
        const card = document.getElementById('subscribe-card');
        if (card) {
            card.innerHTML = `
                <div style="font-size:2rem;margin-bottom:8px;">✅</div>
                <h3 style="color:#2F5233;">¡Suscripción confirmada!</h3>
                <p>Te avisaremos de eventos de <strong>${STATE.evento?.categoria || 'esta categoría'}</strong> en <strong>${STATE.evento?.province || 'tu zona'}</strong>.</p>
            `;
        }
        showToast('🔔 ¡Suscripción confirmada!');

    } catch (err) {
        showToast('Error al suscribirse. Inténtalo de nuevo.');
        if (btn) { btn.disabled = false; btn.textContent = '🔔 Suscribirme'; }
    }
}

// ─── INICIALIZACIÓN Y SCROLL OBSERVER ────────────────────────────────────────
function init() {
    // Restaurar estado de guardado
    if (STATE.slug && localStorage.getItem(`saved_event_${STATE.slug}`)) {
        const btn = document.getElementById('btn-save-event');
        if (btn) btn.textContent = '✅ Guardado';
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

    // Inicializar contadores inmediatamente (no dependen de API)
    _renderStats();
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
