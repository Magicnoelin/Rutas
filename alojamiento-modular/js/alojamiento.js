/**
 * JavaScript modular para página de alojamiento
 * Carga diferida para mejorar velocidad
 */

(function() {
    'use strict';

    // ─── CONFIGURACIÓN ─────────────────────────────────────────────────────────────
    const CONFIG = {
        API_BASE: '/alojamiento-modular/api/alojamiento-data.php',
        MAP_ZOOM: 13,
        RADIUS_KM: 50,
        NEARBY_LIMIT: 6,
        LAZY_LOAD_DELAY: 1000
    };

    // ─── ESTADO GLOBAL ────────────────────────────────────────────────────────────
    let state = {
        alojamiento: window.alojamientoData || null,
        map: null,
        markers: [],
        nearbyLoaded: false
    };

    // ─── UTILIDADES ───────────────────────────────────────────────────────────────
    const utils = {
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        formatPrice(price) {
            if (!price || price <= 0) return 'Consultar';
            return new Intl.NumberFormat('es-ES', {
                style: 'currency',
                currency: 'EUR',
                minimumFractionDigits: 0
            }).format(price);
        },

        distanceToKm(distance) {
            if (!distance) return '';
            return `${distance.toFixed(1)} km`;
        },

        createElement(tag, attrs = {}, children = []) {
            const el = document.createElement(tag);
            Object.keys(attrs).forEach(key => {
                if (key === 'className') {
                    el.className = attrs[key];
                } else if (key === 'textContent') {
                    el.textContent = attrs[key];
                } else if (key === 'innerHTML') {
                    el.innerHTML = attrs[key];
                } else {
                    el.setAttribute(key, attrs[key]);
                }
            });
            children.forEach(child => {
                if (typeof child === 'string') {
                    el.appendChild(document.createTextNode(child));
                } else if (child) {
                    el.appendChild(child);
                }
            });
            return el;
        }
    };

    // ─── GALERÍA DE FOTOS ─────────────────────────────────────────────────────────
    const gallery = {
        init() {
            const thumbnails = document.querySelectorAll('.thumbnail');
            if (!thumbnails.length) return;

            thumbnails.forEach(thumb => {
                thumb.addEventListener('click', function(e) {
                    e.preventDefault();
                    const src = this.getAttribute('src');
                    const mainImg = document.getElementById('galleryMainImage');
                    if (mainImg && src) {
                        mainImg.src = src;
                        thumbnails.forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                    }
                });
            });

            // Preload imágenes siguientes
            if (state.alojamiento && state.alojamiento.fotos) {
                setTimeout(() => {
                    state.alojamiento.fotos.slice(1, 4).forEach(src => {
                        const img = new Image();
                        img.src = src;
                    });
                }, 500);
            }
        }
    };

    // ─── MAPA (LAZY LOAD) ─────────────────────────────────────────────────────────
    const mapModule = {
        init() {
            const placeholder = document.getElementById('map-placeholder');
            if (!placeholder) return;

            placeholder.addEventListener('click', () => this.loadMap());
            
            // Cargar mapa automáticamente si está en viewport después de 2 segundos
            setTimeout(() => {
                const rect = placeholder.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 0) {
                    this.loadMap();
                }
            }, 2000);
        },

        loadMap() {
            if (!state.alojamiento || !state.alojamiento.latitude || !state.alojamiento.longitude) {
                console.warn('No hay coordenadas para el mapa');
                return;
            }

            const placeholder = document.getElementById('map-placeholder');
            if (placeholder) placeholder.style.display = 'none';
            
            const mapEl = document.getElementById('map');
            if (mapEl) mapEl.style.display = 'block';

            // Cargar Leaflet dinámicamente
            this.loadLeaflet().then(() => {
                this.initMap();
                this.addMainMarker();
                this.loadNearbyMarkers();
            }).catch(err => {
                console.error('Error cargando Leaflet:', err);
                if (placeholder) placeholder.style.display = 'flex';
                if (mapEl) mapEl.style.display = 'none';
            });
        },

        loadLeaflet() {
            return new Promise((resolve, reject) => {
                // Verificar si ya está cargado
                if (window.L) {
                    resolve();
                    return;
                }

                const css = utils.createElement('link', {
                    rel: 'stylesheet',
                    href: 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
                    integrity: 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=',
                    crossorigin: ''
                });

                const js = utils.createElement('script', {
                    src: 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                    integrity: 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=',
                    crossorigin: ''
                });

                js.onload = resolve;
                js.onerror = reject;

                document.head.appendChild(css);
                document.head.appendChild(js);
            });
        },

        initMap() {
            const mapEl = document.getElementById('map');
            if (!mapEl) return;

            state.map = L.map('map').setView([
                state.alojamiento.latitude,
                state.alojamiento.longitude
            ], CONFIG.MAP_ZOOM);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 18
            }).addTo(state.map);
        },

        addMainMarker() {
            if (!state.map) return;

            const marker = L.marker([
                state.alojamiento.latitude,
                state.alojamiento.longitude
            ]).addTo(state.map);

            const popupContent = `
                <div style="padding: 8px; max-width: 200px;">
                    <b>${state.alojamiento.name}</b><br>
                    ${state.alojamiento.address || ''}
                </div>
            `;

            marker.bindPopup(popupContent).openPopup();
            state.markers.push(marker);
        },

        loadNearbyMarkers() {
            if (!state.map || state.nearbyLoaded) return;

            fetch(`${CONFIG.API_BASE}?slug=${state.alojamiento.slug}&lat=${state.alojamiento.latitude}&lng=${state.alojamiento.longitude}&radius=${CONFIG.RADIUS_KM}&mode=nearby`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data) {
                        this.addNearbyMarkers(data.data);
                        state.nearbyLoaded = true;
                    }
                })
                .catch(err => console.error('Error cargando marcadores cercanos:', err));
        },

        addNearbyMarkers(data) {
            // Alojamientos cercanos (verdes)
            (data.alojamientos || []).forEach(item => {
                if (item.latitude && item.longitude) {
                    const marker = L.marker([item.latitude, item.longitude], {
                        icon: L.divIcon({
                            className: 'custom-marker',
                            html: '<div style="background: #2F5233; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px;">🏠</div>'
                        })
                    }).addTo(state.map);
                    
                    marker.bindPopup(`<b>${item.name}</b><br>${item.distance} km`);
                    state.markers.push(marker);
                }
            });

            // Lugares de interés (azules)
            (data.lugares || []).slice(0, 5).forEach(item => {
                if (item.latitude && item.longitude) {
                    const marker = L.marker([item.latitude, item.longitude], {
                        icon: L.divIcon({
                            className: 'custom-marker',
                            html: '<div style="background: #2196F3; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px;">🏛️</div>'
                        })
                    }).addTo(state.map);
                    
                    marker.bindPopup(`<b>${item.name}</b><br>${item.distance} km`);
                    state.markers.push(marker);
                }
            });
        }
    };

    // ─── CONTENIDO CERCANO ────────────────────────────────────────────────────────
    const nearbyModule = {
        init() {
            if (!state.alojamiento || !state.alojamiento.latitude || !state.alojamiento.longitude) {
                return;
            }

            // Cargar después de un delay
            setTimeout(() => this.loadNearbyContent(), CONFIG.LAZY_LOAD_DELAY);
        },

        loadNearbyContent() {
            const url = `${CONFIG.API_BASE}?slug=${state.alojamiento.slug}&lat=${state.alojamiento.latitude}&lng=${state.alojamiento.longitude}&radius=${CONFIG.RADIUS_KM}&mode=nearby`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data) {
                        this.renderNearbyContent(data.data);
                    }
                })
                .catch(err => console.error('Error cargando contenido cercano:', err));
        },

        renderNearbyContent(data) {
            this.renderSection('nearby-accommodations', data.alojamientos || [], '🏠');
            this.renderSection('nearby-places', data.lugares || [], '🏛️');
            this.renderSection('nearby-events', data.eventos_similares || [], '🎭');
            this.renderSection('nearby-activities', data.actividades || [], '🎯');
        },

        renderSection(sectionId, items, icon = '') {
            const section = document.getElementById(sectionId);
            if (!section) return;

            section.innerHTML = '';

            if (!items.length) {
                section.appendChild(utils.createElement('div', {
                    className: 'no-items'
                }, ['No hay elementos cercanos']));
                return;
            }

            items.slice(0, CONFIG.NEARBY_LIMIT).forEach(item => {
                const card = this.createCard(item, icon);
                section.appendChild(card);
            });

            if (items.length > CONFIG.NEARBY_LIMIT) {
                const showMore = utils.createElement('button', {
                    className: 'show-more-btn',
                    textContent: 'Ver más'
                });
                showMore.addEventListener('click', () => {
                    // Redirigir a página de búsqueda con filtros
                    window.location.href = `/rutas.php?lat=${state.alojamiento.latitude}&lng=${state.alojamiento.longitude}&radius=${CONFIG.RADIUS_KM}`;
                });
                section.appendChild(showMore);
            }
        },

        createCard(item, icon) {
            const card = utils.createElement('div', {
                className: 'nearby-card'
            });

            // Imagen
            const imgContainer = utils.createElement('div', {
                className: 'nearby-card-img'
            });
            const img = utils.createElement('img', {
                src: item.main_image || '/img/placeholder.jpg',
                alt: item.name,
                loading: 'lazy'
            });
            imgContainer.appendChild(img);

            // Cuerpo
            const body = utils.createElement('div', {
                className: 'nearby-card-body'
            });

            const name = utils.createElement('div', {
                className: 'nearby-card-name',
                textContent: item.name
            });

            const meta = utils.createElement('div', {
                className: 'nearby-card-meta'
            });
            const metaText = [];
            if (item.municipality) metaText.push(item.municipality);
            if (item.distance) metaText.push(`${item.distance} km`);
            meta.textContent = metaText.join(' · ');

            body.appendChild(name);
            body.appendChild(meta);

            // Precio si existe
            if (item.price_per_night || item.price) {
                const price = utils.createElement('div', {
                    className: 'nearby-card-price',
                    textContent: utils.formatPrice(item.price_per_night || item.price)
                });
                body.appendChild(price);
            }

            card.appendChild(imgContainer);
            card.appendChild(body);

            // Click para redirigir
            card.addEventListener('click', () => {
                if (item.url) {
                    window.location.href = item.url;
                }
            });

            return card;
        }
    };

    // ─── INICIALIZACIÓN ───────────────────────────────────────────────────────────
    const init = {
        start() {
            if (!state.alojamiento) {
                console.warn('No hay datos de alojamiento');
                return;
            }

            // Inicializar módulos
            gallery.init();
            mapModule.init();
            nearbyModule.init();

            // Cargar CSS diferido
            this.loadDeferredCSS();

            // Eventos adicionales
            this.bindEvents();
        },

        loadDeferredCSS() {
            const link = utils.createElement('link', {
                rel: 'stylesheet',
                href: '/alojamiento-modular/css/alojamiento.css'
            });
            document.head.appendChild(link);
        },

        bindEvents() {
            // Smooth scroll para anclas internas
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const target = document.querySelector(targetId);
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Lazy load de imágenes fuera del viewport
            if ('IntersectionObserver' in window) {
                const lazyImages = document.querySelectorAll('img[loading="lazy"]');
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src || img.src;
                            observer.unobserve(img);
                        }
                    });
                });

                lazyImages.forEach(img => {
                    if (img.dataset.src) {
                        observer.observe(img);
                    }
                });
            }
        }
    };

    // ─── EXPORTACIÓN (para pruebas) ───────────────────────────────────────────────
    window.AlojamientoApp = {
        state,
        utils,
        gallery,
        mapModule,
        nearbyModule,
        init
    };

    // ─── EJECUCIÓN AL CARGAR EL DOM ───────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        init.start();
    });

})();

           