/**
 * lugar.js — JavaScript para lugar-modular
 * Depende de window.LUG_DATA inyectado por index.php
 */

(function() {
    'use strict';

    var lug   = window.LUG_DATA;
    // Compatibilidad: el PHP puede pasar 'photos' o 'fotos'
    var fotos = lug ? (lug.photos || lug.fotos || []) : [];
    var map = null;
    var markers = {};

    // ─── GALERÍA ─────────────────────────────────────────────────────────────

    function initGallery() {
        if (!fotos || fotos.length < 2) return;

        var mainImg = document.getElementById('gallery-main-img');
        var thumbs  = document.querySelectorAll('.gallery-thumb');
        var counter = document.getElementById('gallery-counter');

        if (!mainImg) return;

        // Click en thumbnails
        thumbs.forEach(function(thumb, idx) {
            thumb.addEventListener('click', function() {
                if (fotos[idx]) {
                    mainImg.src = fotos[idx];
                    mainImg.alt = 'Foto ' + (idx + 1);
                    
                    // Actualizar clase activa
                    thumbs.forEach(function(t) { t.classList.remove('active'); });
                    thumb.classList.add('active');
                    
                    // Actualizar contador
                    if (counter) counter.textContent = (idx + 1) + '/' + fotos.length;
                }
            });
        });

        // Expandir galería (lightbox)
        var expandBtn = document.getElementById('gallery-expand-btn');
        if (expandBtn) {
            expandBtn.addEventListener('click', function() {
                openLightbox(0);
            });
        }

        // Click en imagen principal para lightbox
        if (mainImg) {
            mainImg.addEventListener('click', function() {
                openLightbox(0);
            });
        }
    }

    // ─── LIGHTBOX ────────────────────────────────────────────────────────────

    var currentLightboxIndex = 0;

    function openLightbox(index) {
        if (!fotos || fotos.length === 0) return;
        
        currentLightboxIndex = Math.max(0, Math.min(index || 0, fotos.length - 1));
        
        var overlay = document.getElementById('lightbox-overlay');
        if (!overlay) {
            createLightboxHTML();
            overlay = document.getElementById('lightbox-overlay');
        }
        
        updateLightboxImage();
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function createLightboxHTML() {
        var html = `
            <div class="lbox-overlay" id="lightbox-overlay">
                <img class="lbox-img" id="lightbox-img" alt="">
                <button class="lbox-close" onclick="closeLightbox()" aria-label="Cerrar">&times;</button>
                ${fotos.length > 1 ? '<button class="lbox-nav lbox-prev" onclick="prevLightbox()" aria-label="Anterior">&#8249;</button>' : ''}
                ${fotos.length > 1 ? '<button class="lbox-nav lbox-next" onclick="nextLightbox()" aria-label="Siguiente">&#8250;</button>' : ''}
                <div class="lbox-caption" id="lightbox-caption"></div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', html);

        // Cerrar con Esc
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') prevLightbox();
            if (e.key === 'ArrowRight') nextLightbox();
        });

        // Cerrar al hacer click fuera de la imagen
        document.getElementById('lightbox-overlay').addEventListener('click', function(e) {
            if (e.target === this) closeLightbox();
        });
    }

    function updateLightboxImage() {
        var img = document.getElementById('lightbox-img');
        var caption = document.getElementById('lightbox-caption');
        
        if (img && fotos[currentLightboxIndex]) {
            img.src = fotos[currentLightboxIndex];
            img.alt = 'Foto ' + (currentLightboxIndex + 1) + ' de ' + fotos.length;
        }
        
        if (caption) {
            caption.textContent = (currentLightboxIndex + 1) + ' / ' + fotos.length;
        }
    }

    window.closeLightbox = function() {
        var overlay = document.getElementById('lightbox-overlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    window.nextLightbox = function() {
        if (fotos && fotos.length > 1) {
            currentLightboxIndex = (currentLightboxIndex + 1) % fotos.length;
            updateLightboxImage();
        }
    };

    window.prevLightbox = function() {
        if (fotos && fotos.length > 1) {
            currentLightboxIndex = currentLightboxIndex > 0 ? currentLightboxIndex - 1 : fotos.length - 1;
            updateLightboxImage();
        }
    };

    // ─── DESCRIPCIÓN TOGGLE ──────────────────────────────────────────────────

    window.toggleDesc = function() {
        var text = document.getElementById('desc-text');
        var btn = document.getElementById('desc-toggle');
        
        if (!text || !btn) return;
        
        var isExpanded = btn.getAttribute('aria-expanded') === 'true';
        
        if (isExpanded) {
            text.classList.add('collapsed');
            btn.textContent = btn.dataset.more || '↓ Leer más';
            btn.setAttribute('aria-expanded', 'false');
        } else {
            text.classList.remove('collapsed');
            btn.textContent = btn.dataset.less || '↑ Leer menos';
            btn.setAttribute('aria-expanded', 'true');
        }
    };

    function initDescToggle() {
        var text = document.getElementById('desc-text');
        var btn = document.getElementById('desc-toggle');
        
        if (!text || !btn) return;
        
        // Guardar textos de los botones
        btn.dataset.more = btn.textContent || '↓ Leer más';
        btn.dataset.less = btn.getAttribute('data-less') || '↑ Leer menos';
        
        // Solo colapsar si el texto es muy largo
        if (text.scrollHeight > 180) {
            text.classList.add('collapsed');
            btn.style.display = 'inline-block';
        } else {
            btn.style.display = 'none';
        }
    }

    // ─── MAPA LEAFLET ────────────────────────────────────────────────────────

    window.initMap = function() {
        if (!lug || !lug.latitude || !lug.longitude) return;

        var placeholder = document.getElementById('map-placeholder');
        var mapEl = document.getElementById('event-map');
        var controls = document.getElementById('map-controls');

        if (placeholder) placeholder.style.display = 'none';
        if (mapEl) mapEl.style.display = 'block';
        if (controls) controls.style.display = 'flex';

        // Cargar Leaflet dinámicamente
        if (!window.L) {
            loadCSS('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
            loadJS('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', function() {
                createMap();
            });
        } else {
            createMap();
        }
    };

    function createMap() {
        if (map) return; // Ya existe

        var lat = parseFloat(lug.latitude);
        var lng = parseFloat(lug.longitude);

        try {
            map = L.map('event-map').setView([lat, lng], 13);

            // Tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 18
            }).addTo(map);

            // Marcador del lugar
            markers.lugar = L.marker([lat, lng]).addTo(map)
                .bindPopup('<strong>' + (lug.name || 'Lugar de interés') + '</strong>');

            // Cargar datos cercanos
            loadNearbyData();

        } catch (e) {
            console.error('Error creating map:', e);
        }
    }

    function loadNearbyData() {
        if (!lug.latitude || !lug.longitude) return;

        // Cargar alojamientos cercanos
        fetch('/api/nearby-content.php?type=alojamientos&lat=' + lug.latitude + '&lng=' + lug.longitude + '&radius=25')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    markers.alojamientos = [];
                    data.data.forEach(function(item) {
                        if (item.latitude && item.longitude) {
                            var marker = L.marker([item.latitude, item.longitude])
                                .bindPopup('<strong>' + item.name + '</strong><br><a href="/alojamiento/' + item.slug + '">Ver más</a>');
                            markers.alojamientos.push(marker);
                        }
                    });
                }
            })
            .catch(function(e) { console.log('Error loading alojamientos:', e); });

        // Cargar otros lugares cercanos
        fetch('/api/nearby-content.php?type=lugares&lat=' + lug.latitude + '&lng=' + lug.longitude + '&radius=25')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    markers.lugares = [];
                    data.data.forEach(function(item) {
                        if (item.latitude && item.longitude && item.slug !== lug.slug) {
                            var marker = L.marker([item.latitude, item.longitude])
                                .bindPopup('<strong>' + item.name + '</strong><br><a href="/lugar/' + item.slug + '">Ver más</a>');
                            markers.lugares.push(marker);
                        }
                    });
                }
            })
            .catch(function(e) { console.log('Error loading lugares:', e); });

        // Cargar actividades cercanas
        fetch('/api/nearby-content.php?type=actividades&lat=' + lug.latitude + '&lng=' + lug.longitude + '&radius=25')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    markers.actividades = [];
                    data.data.forEach(function(item) {
                        if (item.latitude && item.longitude) {
                            var marker = L.marker([item.latitude, item.longitude])
                                .bindPopup('<strong>' + item.name + '</strong><br><a href="/actividad/' + item.slug + '">Ver más</a>');
                            markers.actividades.push(marker);
                        }
                    });
                }
            })
            .catch(function(e) { console.log('Error loading actividades:', e); });
    }

    window.toggleMapLayer = function(layer) {
        if (!map) return;

        var btn = document.getElementById('btn-' + layer);
        var isActive = btn && btn.classList.contains('active');

        if (isActive) {
            // Ocultar capa
            btn.classList.remove('active');
            if (markers[layer]) {
                if (Array.isArray(markers[layer])) {
                    markers[layer].forEach(function(marker) {
                        map.removeLayer(marker);
                    });
                } else {
                    map.removeLayer(markers[layer]);
                }
            }
        } else {
            // Mostrar capa
            btn.classList.add('active');
            if (markers[layer]) {
                if (Array.isArray(markers[layer])) {
                    markers[layer].forEach(function(marker) {
                        marker.addTo(map);
                    });
                } else {
                    markers[layer].addTo(map);
                }
            }
        }
    };

    // ─── UTILIDADES ──────────────────────────────────────────────────────────

    function loadCSS(url) {
        if (document.querySelector('link[href="' + url + '"]')) return;
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = url;
        document.head.appendChild(link);
    }

    function loadJS(url, callback) {
        if (document.querySelector('script[src="' + url + '"]')) {
            if (callback) callback();
            return;
        }
        var script = document.createElement('script');
        script.src = url;
        script.onload = callback;
        document.head.appendChild(script);
    }

    function showToast(message) {
        var toast = document.getElementById('toast') || document.createElement('div');
        toast.id = 'toast';
        toast.className = 'toast';
        toast.textContent = message;
        
        if (!toast.parentNode) {
            document.body.appendChild(toast);
        }
        
        toast.classList.add('show');
        
        setTimeout(function() {
            toast.classList.remove('show');
        }, 3000);
    }

    // ─── INICIALIZACIÓN ──────────────────────────────────────────────────────

    function init() {
        initGallery();
        initDescToggle();
    }

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();