/**
 * lugar.js — Módulo JS para página de detalle de Lugar de Interés
 * Versión 1.0 — Galería, Lightbox, Mapa diferido, Contenido cercano, Skeleton screens
 *
 * Depende de window.LUG_DATA inyectado por index.php
 */

(function () {
    'use strict';

    var lug   = window.LUG_DATA;
    // Compatibilidad: el PHP puede pasar 'photos' o 'fotos'
    var T     = window.LUG_T || {}; // Translations for JS
    var fotos = (lug && lug.photos) ? lug.photos : ((lug && lug.fotos) ? lug.fotos : []);
    var API   = '/lugar-modular/api/lugar-data.php';

    /* ══════════════════════════════════════════════════════
       CTA TURISTA — Búsqueda de alojamiento cerca
       ══════════════════════════════════════════════════════ */
    window.lugBuscarAloj = function(event, source) {
        event.preventDefault();
        var llegada, salida, personas;
        if (source === 'mobile') {
            llegada  = document.getElementById('lug-llegada-mob');
            salida   = document.getElementById('lug-salida-mob');
            personas = document.getElementById('lug-personas-mob');
        } else {
            llegada  = document.getElementById('lug-llegada-sb');
            salida   = document.getElementById('lug-salida-sb');
            personas = document.getElementById('lug-personas-sb');
        }
        if (!llegada || !llegada.value) { llegada && llegada.focus(); return; }
        if (!salida  || !salida.value)  { salida  && salida.focus();  return; }

        var prov = (lug && lug.province) ? encodeURIComponent(lug.province) : '';
        var muni = (lug && lug.municipality) ? encodeURIComponent(lug.municipality) : '';
        var per  = (personas && personas.value) ? personas.value : '2';
        var ll   = encodeURIComponent(llegada.value);
        var sal  = encodeURIComponent(salida.value);

        // Capturar email si lo ha escrito el usuario
        var emailEl = document.getElementById(source === 'mobile' ? 'lug-email-mob' : 'lug-email-sb');
        var email   = emailEl && emailEl.value.trim() ? emailEl.value.trim() : '';

        // Guardar email en localStorage para re-usarlo y enviarlo a la API
        if (email) {
            localStorage.setItem('cta_email', email);
            // Envío silencioso a la API para captura de lead
            try {
                fetch('/api/cta-lead.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email:    email,
                        provincia: decodeURIComponent(prov),
                        municipio: decodeURIComponent(muni),
                        lugar:     lug ? lug.name : '',
                        llegada:   llegada.value,
                        salida:    salida.value,
                        personas:  per,
                        ref:       'lugar-cta'
                    })
                }).catch(function(){});
            } catch(e){}
        }

        // Construir URL hacia el landing de alojamientos de la provincia
        // Formato: /alojamientos/{provincia-slug}  → alojamientos-landing/index.php
        var provSlug = decodeURIComponent(prov).toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // quitar tildes
            .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        var base  = '/alojamientos/' + (provSlug || 'soria');
        var query = '?desde=' + ll + '&hasta=' + sal + '&personas=' + per;
        if (muni)  query += '&municipio=' + muni;
        query += '&ref=lugar-cta';

        // Cerrar bottom-sheet si aplica
        var overlay = document.getElementById('lug-mob-overlay');
        if (overlay) { overlay.classList.remove('open'); document.body.style.overflow = ''; }

        window.location.href = base + query;
    };

    /* ══════════════════════════════════════════════════════
       GALERÍA
       ══════════════════════════════════════════════════════ */
    window.currentGalleryIdx = 0;

    window.setGalleryPhoto = function(idx) {
        if (!fotos[idx]) return;
        currentGalleryIdx = idx;
        var mainImg = document.getElementById('gallery-main-img');
        var counter = document.getElementById('gallery-counter');
        var thumbs  = document.querySelectorAll('.gallery-thumb');
        if (mainImg) {
            mainImg.style.opacity = '0.6';
            mainImg.src = fixUrl(fotos[idx]);
            mainImg.onload = function() { mainImg.style.opacity = '1'; };
        }
        thumbs.forEach(function(t) {
            t.classList.toggle('active', parseInt(t.dataset.index) === idx);
        });
        if (counter) counter.textContent = (idx + 1) + ' / ' + fotos.length;
    };

    /* ══════════════════════════════════════════════════════
       LIGHTBOX
       ══════════════════════════════════════════════════════ */
    window.openLightbox = function(idx) {
        if (!fotos.length) return;
        currentGalleryIdx = idx;
        var overlay = document.getElementById('lightbox');
        var img     = document.getElementById('lightbox-img');
        var caption = document.getElementById('lightbox-caption');
        if (!overlay || !img) return;
        overlay.classList.add('active');
        img.src = fixUrl(fotos[idx]);
        img.alt = (lug ? lug.name : '') + ' — foto ' + (idx + 1);
        if (caption) caption.textContent = (idx + 1) + ' / ' + fotos.length;
        document.body.style.overflow = 'hidden';
        // Ocultar navegación si sólo hay 1 foto
        var prev = overlay.querySelector('.lbox-prev');
        var next = overlay.querySelector('.lbox-next');
        var show = fotos.length > 1 ? '' : 'none';
        if (prev) prev.style.display = show;
        if (next) next.style.display = show;
    };

    window.closeLightbox = function() {
        var overlay = document.getElementById('lightbox');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    };

    window.closeLightboxOnOverlay = function(e) {
        if (e.target === document.getElementById('lightbox')) closeLightbox();
    };

    window.lightboxNav = function(dir) {
        if (!fotos.length) return;
        currentGalleryIdx = (currentGalleryIdx + dir + fotos.length) % fotos.length;
        openLightbox(currentGalleryIdx);
    };

    document.addEventListener('keydown', function(e) {
        var overlay = document.getElementById('lightbox');
        if (!overlay || !overlay.classList.contains('active')) return;
        if (e.key === 'Escape')     closeLightbox();
        if (e.key === 'ArrowLeft')  lightboxNav(-1);
        if (e.key === 'ArrowRight') lightboxNav(1);
    });

    // Soporte touch en el lightbox
    var lboxTouchX = 0;
    var overlay = document.getElementById('lightbox');
    if (overlay) {
        overlay.addEventListener('touchstart', function(e) {
            lboxTouchX = e.touches[0].clientX;
        }, { passive: true });
        overlay.addEventListener('touchend', function(e) {
            var diff = lboxTouchX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) lightboxNav(1); else lightboxNav(-1);
            }
        });
    }

    /* ══════════════════════════════════════════════════════
       DESCRIPCIÓN EXPANDIBLE
       ══════════════════════════════════════════════════════ */
    window.expandDesc = function() {
        var text = document.getElementById('desc-text');
        var btn  = document.getElementById('desc-expand-btn');
        if (text) text.classList.remove('collapsed');
        if (btn)  btn.remove();
    };

    // toggleDesc: usado por descripcion.php (btn con aria-expanded)
    window.toggleDesc = function() {
        var text    = document.getElementById('desc-text');
        var btn     = document.getElementById('desc-toggle');
        if (!text || !btn) return;
        var isCollapsed = text.classList.contains('collapsed');
        if (isCollapsed) {
            text.classList.remove('collapsed');
            btn.setAttribute('aria-expanded', 'true');
            // El texto del botón viene de las traducciones PHP; lo alternamos manualmente
            btn.dataset.txtMore = btn.dataset.txtMore || btn.textContent;
            if (btn.dataset.txtLess) btn.textContent = btn.dataset.txtLess;
        } else {
            text.classList.add('collapsed');
            btn.setAttribute('aria-expanded', 'false');
            if (btn.dataset.txtMore) btn.textContent = btn.dataset.txtMore;
        }
    };

    // Inicializar: colapsar descripción si es larga
    (function initDescToggle() {
        var text = document.getElementById('desc-text');
        var btn  = document.getElementById('desc-toggle');
        if (!text || !btn) return;
        if (text.scrollHeight > 160) {
            text.classList.add('collapsed');
            // Guardar etiqueta "leer menos" del data-txt-less si existe, o fallback
            btn.dataset.txtMore = btn.textContent;
            btn.dataset.txtLess = btn.dataset.txtLess || '↑ Leer menos';
            btn.style.display = '';
        } else {
            // Si la descripción es corta no necesitamos el botón
            btn.style.display = 'none';
        }
    })();

    /* ══════════════════════════════════════════════════════
       MAPA DIFERIDO (Leaflet — carga al clic o al ser visible)
       ══════════════════════════════════════════════════════ */
    var mapLoaded = false;
    var leafletMap = null;
    var nearbyLayers = {
        alojamientos: null,
        lugares: null,
        actividades: null,
        eventos: null,
    };

    // initMap acepta parámetros opcionales (compatibilidad con onclick del HTML)
    // o los lee de window.LUG_DATA si no se pasan
    window.initMap = function(lat, lng, name) {
        var mapLat  = lat  || (lug && lug.latitude)  || (lug && lug.lat);
        var mapLng  = lng  || (lug && lug.longitude) || (lug && lug.lng);
        var mapName = name || (lug && lug.name) || '';
        if (mapLoaded || !mapLat || !mapLng) return;
        mapLoaded = true;

        var placeholder = document.getElementById('map-placeholder');
        var mapEl       = document.getElementById('map');          // ID en descripcion.php
        if (!mapEl) mapEl = document.getElementById('lug-map');   // fallback alias
        if (!mapEl) return;

        if (placeholder) {
            placeholder.innerHTML = '<div style="font-size:2rem">⏳</div><p style="margin-top:8px;font-size:0.85rem;color:#2F5233;">Cargando mapa…</p>';
        }

        // Mostrar el div del mapa ANTES de cargar Leaflet para que tenga dimensiones
        if (placeholder) placeholder.style.display = 'none';
        mapEl.style.cssText = 'display:block!important;height:380px!important;width:100%!important;';

        // Cargar CSS de Leaflet si no está ya cargado
        if (!document.querySelector('link[href*="leaflet"]')) {
            var leafletCSS = document.createElement('link');
            leafletCSS.rel  = 'stylesheet';
            leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(leafletCSS);
        }

        var script  = document.createElement('script');
        script.src  = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/4K9+sNF0Ncn5BlYETcCc=';
        script.crossOrigin = '';
        script.onload = function() {
            // Initialize map and store it
            leafletMap = window.L.map(mapEl, { zoomControl: true, scrollWheelZoom: false })
                .setView([mapLat, mapLng], 14);

            // Initialize layer groups
            nearbyLayers.alojamientos = L.layerGroup().addTo(leafletMap);
            nearbyLayers.lugares      = L.layerGroup().addTo(leafletMap);
            nearbyLayers.actividades  = L.layerGroup().addTo(leafletMap);
            nearbyLayers.eventos      = L.layerGroup().addTo(leafletMap);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 18
            }).addTo(leafletMap);

            // Marcador principal del lugar
            var icon = L.divIcon({
                className: '',
                html: '<div style="background:#2F5233;color:#fff;border-radius:50% 50% 50% 0;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:18px;transform:rotate(-45deg);box-shadow:0 2px 8px rgba(0,0,0,0.3);border:2px solid #fff;"><span style="transform:rotate(45deg)">🏛️</span></div>',
                iconSize: [36, 36],
                iconAnchor: [18, 36],
                popupAnchor: [0, -36]
            });

            var popup = '<div style="padding:6px 2px;min-width:160px;">'
                + '<strong style="color:#2F5233;font-size:0.95rem;">' + escHtml(mapName) + '</strong>'
                + (lug && lug.address    ? '<br><small style="color:#666">' + escHtml(lug.address) + '</small>' : '')
                + (lug && lug.municipality ? '<br><small style="color:#666">📍 ' + escHtml(lug.municipality) + '</small>' : '')
                + '</div>';

            L.marker([mapLat, mapLng], { icon: icon })
                .addTo(leafletMap)
                .bindPopup(popup)
                .openPopup();

            // Show map controls
            var mapControls = document.getElementById('map-controls');
            if (mapControls) mapControls.style.display = 'block';

            // Add event listeners for toggle buttons
            document.querySelectorAll('.map-toggle-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    var layerType = this.dataset.layer;
                    toggleLayer(layerType, this);
                });
            });

            // If nearby data is already loaded, add markers to layers
            if (window._nearbyDataLug) {
                addNearbyMarkersToLayers(window._nearbyDataLug);
            }
            window._nearbyMapLug = leafletMap; // Store map instance for nearby data loading later
        };
        script.onerror = function() {
            mapLoaded = false;
            if (placeholder) {
                placeholder.style.display = 'flex';
                placeholder.innerHTML = '<div class="map-ph-icon">🗺️</div><strong>Error al cargar el mapa</strong><span class="map-ph-hint" style="cursor:pointer;" onclick="initMap()">Haz clic para reintentar</span>'; // Use T for translations here
            }
        };
        document.head.appendChild(script);
    };

    function toggleLayer(layerType, button) {
        if (!leafletMap || !nearbyLayers[layerType]) return;

        if (leafletMap.hasLayer(nearbyLayers[layerType])) {
            leafletMap.removeLayer(nearbyLayers[layerType]);
            if (button) button.classList.remove('active');
        } else {
            leafletMap.addLayer(nearbyLayers[layerType]);
            if (button) button.classList.add('active');
        }
    }

    function addNearbyMarkersToLayers(data) {
        if (!leafletMap) return; // Map not initialized yet

        var configs = [
            { type: 'alojamientos', items: data.alojamientos || [],      emoji: '🏠', color: '#2F5233' },
            { type: 'lugares',      items: data.lugares || [],           emoji: '🏛️', color: '#1565C0' },
            { type: 'actividades',  items: data.actividades || [],       emoji: '🎯', color: '#E65100' },
            { type: 'eventos',      items: data.eventos_similares || [], emoji: '🎭', color: '#6A1B9A' }
        ];

        configs.forEach(function(cfg) {
            if (!nearbyLayers[cfg.type]) {
                nearbyLayers[cfg.type] = L.layerGroup().addTo(leafletMap);
            }
            cfg.items.forEach(function(item) {
                if (!item.latitude || !item.longitude) return;
                var ic = L.divIcon({
                    className: '',
                    html: '<div style="background:' + cfg.color + ';color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:14px;box-shadow:0 2px 6px rgba(0,0,0,0.25);border:2px solid #fff;">' + cfg.emoji + '</div>',
                    iconSize: [28, 28], iconAnchor: [14, 14], popupAnchor: [0, -14]
                });
                var dist = item.distance > 0 ? ' · ' + item.distance + ' km' : '';
                L.marker([item.latitude, item.longitude], { icon: ic })
                    .addTo(nearbyLayers[cfg.type])
                    .bindPopup('<b>' + escHtml(item.name) + '</b><br><small>' + escHtml(item.municipality || '') + dist + '</small>');
            });
        });
    }

    // Auto-cargar mapa cuando sea visible (sin clic)
    if (lug && lug.latitude && lug.longitude) {
        var mapContainer = document.getElementById('lug-map-container');
        if (mapContainer && 'IntersectionObserver' in window) {
            var mapObs = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        mapObs.disconnect();
                        initMap();
                    }
                });
            }, { rootMargin: '200px', threshold: 0.1 });
            mapObs.observe(mapContainer);
        }
    }

    /* ══════════════════════════════════════════════════════
       CONTENIDO CERCANO — Skeleton → Cards
       ══════════════════════════════════════════════════════ */
    var nearbyData   = null;
    var nearbyLoaded = false;
    var nearbyAllItems = {};

    // IDs deben coincidir EXACTAMENTE con los del HTML de cercanos.php
    var nearbyConfig = {
        alojamientos: { section: 'nearby-aloj',          grid: 'nearby-aloj-content',     more: 'nearby-aloj-more',     key: 'alojamientos',     emoji: '🏠' },
        actividades:  { section: 'nearby-activ',         grid: 'nearby-activ-content',    more: 'nearby-activ-more',    key: 'actividades',      emoji: '🎯' },
        eventos:      { section: 'nearby-eventos',       grid: 'nearby-eventos-content',  more: 'nearby-eventos-more',  key: 'eventos_similares',emoji: '🎭' },
        lugares:      { section: 'nearby-lugares',       grid: 'nearby-lugares-content',  more: 'nearby-lugares-more',  key: 'lugares',          emoji: '🏛️' }
    };

    function loadNearby() {
        if (nearbyLoaded || !lug) return;
        nearbyLoaded = true;

        var url = API + '?slug=' + encodeURIComponent(lug.slug)
            + '&prov='   + encodeURIComponent(lug.province || '')
            + '&muni='   + encodeURIComponent(lug.municipality || '')
            + '&radius=50'
            + '&mode=nearby';

        if (lug.latitude && lug.longitude) {
            url += '&lat=' + lug.latitude + '&lng=' + lug.longitude;
        }

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (!resp.success || !resp.data) return;
                nearbyData = resp.data;
                window._nearbyDataLug = nearbyData;

                // Añadir marcadores si el mapa ya está listo
                if (window._nearbyMapLug) addNearbyMarkersToLayers(nearbyData);

                // Renderizar cada sección
                Object.keys(nearbyConfig).forEach(function(type) {
                    var cfg   = nearbyConfig[type];
                    var items = nearbyData[cfg.key] || [];
                    nearbyAllItems[type] = items;
                    renderNearbySection(type, items);
                });
            })
            .catch(function(err) {
                console.error('[lugar-modular] Error cargando contenido cercano:', err);
            });
    }

    function renderNearbySection(type, items) {
        var cfg     = nearbyConfig[type];
        var section = document.getElementById(cfg.section);
        var grid    = document.getElementById(cfg.grid);
        var moreBtn = document.getElementById(cfg.more);
        if (!section || !grid) return;

        if (!items || items.length === 0) {
            // Ocultar si no hay resultados
            section.style.display = 'none';
            return;
        }

        section.style.display = '';
        // Limpiar contenedor y añadir un wrapper con clase nearby-grid
        grid.innerHTML = '';
        var gridWrapper = document.createElement('div');
        gridWrapper.className = 'nearby-grid';
        gridWrapper.style.cssText = 'display:grid!important;grid-template-columns:repeat(auto-fill,minmax(200px,1fr))!important;gap:12px!important;';

        var shown = items.slice(0, 4);
        shown.forEach(function(item) {
            gridWrapper.appendChild(createNearbyCard(item, type, cfg.emoji));
        });
        grid.appendChild(gridWrapper);

        if (items.length > 4 && moreBtn) {
            moreBtn.style.display = 'block';
        }
    }

    window.showMoreNearby = function(type) {
        var cfg     = nearbyConfig[type];
        var grid    = document.getElementById(cfg.grid);
        var moreBtn = document.getElementById(cfg.more);
        var items   = nearbyAllItems[type] || [];
        if (!grid) return;
        // Añadir al mismo wrapper grid que ya existe
        var wrapper = grid.querySelector('.nearby-grid') || grid;
        items.slice(4).forEach(function(item) {
            wrapper.appendChild(createNearbyCard(item, type, cfg.emoji));
        });
        if (moreBtn) moreBtn.style.display = 'none';
    };

    function createNearbyCard(item, type, emoji) {
        var card = document.createElement('a');
        card.className = 'nearby-card';
        card.href      = item.url || '#';
        // Estilos inline como garantía absoluta ante cualquier CSS global
        card.style.cssText = 'display:block!important;border-radius:8px!important;overflow:hidden!important;border:1px solid #eee!important;background:#fff!important;text-decoration:none!important;color:#333!important;transition:box-shadow .2s,transform .2s!important;';

        // Imagen
        var imgWrap = document.createElement('div');
        imgWrap.className = 'nearby-card-img';
        imgWrap.style.cssText = 'height:120px!important;overflow:hidden!important;position:relative!important;display:block!important;background:#e8f0e8!important;';

        if (item.main_image) {
            var img = document.createElement('img');
            img.src     = fixUrl(item.main_image.trim ? item.main_image.trim() : item.main_image);
            img.alt     = item.name || '';
            img.loading = 'lazy';
            img.style.cssText = 'width:100%!important;height:100%!important;object-fit:cover!important;display:block!important;';
            img.onerror = function() {
                imgWrap.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;background:linear-gradient(135deg,#e8f0e8,#d0e4d0);">' + emoji + '</div>';
            };
            imgWrap.appendChild(img);
        } else {
            imgWrap.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;background:linear-gradient(135deg,#e8f0e8,#d0e4d0);">' + emoji + '</div>';
        }

        if (item.distance > 0) {
            var dist = document.createElement('span');
            dist.className   = 'nearby-card-dist';
            dist.style.cssText = 'position:absolute;bottom:6px;right:8px;background:rgba(0,0,0,.55);color:#fff;font-size:.7rem;font-weight:700;padding:2px 7px;border-radius:10px;z-index:10;';
            dist.textContent = item.distance + ' km';
            imgWrap.appendChild(dist);
        }

        // Cuerpo
        var body = document.createElement('div');
        body.className = 'nearby-card-body';
        body.style.cssText = 'padding:10px 12px!important;display:block!important;';

        var name = document.createElement('div');
        name.className   = 'nearby-card-name';
        name.style.cssText = 'font-size:.85rem!important;font-weight:700!important;color:#333!important;margin-bottom:4px!important;overflow:hidden!important;display:-webkit-box!important;-webkit-line-clamp:2!important;-webkit-box-orient:vertical!important;';
        name.textContent = item.name || '';
        body.appendChild(name);

        if (item.municipality) {
            var meta = document.createElement('div');
            meta.className   = 'nearby-card-meta';
            meta.style.cssText = 'font-size:.75rem!important;color:#666!important;margin-bottom:4px!important;display:block!important;';
            meta.textContent = '📍 ' + item.municipality;
            body.appendChild(meta);
        }

        // Precio / info extra según tipo
        if (type === 'alojamientos' && item.price_per_night > 0) {
            var p = document.createElement('div');
            p.className   = 'nearby-card-price';
            p.textContent = item.price_per_night + '€ / noche';
            body.appendChild(p);
        } else if (type === 'actividades' && item.price > 0) {
            var p2 = document.createElement('div');
            p2.className   = 'nearby-card-price';
            p2.textContent = 'desde ' + item.price + '€';
            body.appendChild(p2);
        } else if (type === 'eventos') {
            if (item.is_free == 1) {
                var fr = document.createElement('span');
                fr.className   = 'nearby-card-free'; // Rely on CSS for styling
                fr.textContent = 'Gratis';
                body.appendChild(fr);
            } else if (item.ticket_price > 0) {
                var tp = document.createElement('div');
                tp.className   = 'nearby-card-price';
                tp.textContent = item.ticket_price + '€';
                body.appendChild(tp);
            }
            if (item.start_date) {
                var dt = document.createElement('div');
                dt.className   = 'nearby-card-meta';
                dt.textContent = '📅 ' + fmtDate(item.start_date);
                body.appendChild(dt);
            }
        }

        card.appendChild(imgWrap);
        card.appendChild(body);
        return card;
    }

    // Cargar contenido cercano tras 1.5s (sin esperar scroll)
    setTimeout(loadNearby, 1500);

    /* ══════════════════════════════════════════════════════
       COMPARTIR
       ══════════════════════════════════════════════════════ */
    window.shareLug = function(platform) {
        var url   = window.location.href;
        var title = lug ? lug.name : document.title;
        var links = {
            whatsapp: 'https://wa.me/?text=' + encodeURIComponent(title + ' ' + url),
            facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url),
            twitter:  'https://twitter.com/intent/tweet?text=' + encodeURIComponent(title) + '&url=' + encodeURIComponent(url),
        };
        if (platform === 'copy') {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() { showToast('✅ Enlace copiado'); });
            } else {
                showToast('✅ ' + url);
            }
            return;
        }
        if (navigator.share && platform === 'whatsapp') {
            navigator.share({ title: title, url: url }).catch(function(){});
            return;
        }
        window.open(links[platform], '_blank', 'width=600,height=400');
    };

    // Botón compartir del hero
    var btnShare = document.getElementById('btn-share');
    if (btnShare) {
        btnShare.addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({ title: lug ? lug.name : document.title, url: window.location.href }).catch(function(){});
            } else {
                shareLug('copy');
            }
        });
    }

    /* ══════════════════════════════════════════════════════
       FAVORITO
       ══════════════════════════════════════════════════════ */
    var btnFav = document.getElementById('btn-fav');
    if (btnFav && lug) {
        var favKey = 'fav_lugar_' + lug.id;
        if (localStorage.getItem(favKey) === '1') btnFav.textContent = '❤️';
        btnFav.addEventListener('click', function() {
            if (localStorage.getItem(favKey) === '1') {
                localStorage.removeItem(favKey);
                btnFav.textContent = '🤍';
                showToast('Eliminado de favoritos');
            } else {
                localStorage.setItem(favKey, '1');
                btnFav.textContent = '❤️';
                showToast('❤️ ¡Guardado en favoritos!');
                btnFav.style.transform = 'scale(1.3)';
                setTimeout(function() { btnFav.style.transform = ''; }, 300);
            }
        });
    }

    /* ══════════════════════════════════════════════════════
       HERO PARALLAX — sin reflow forzado
       Cachear heroHeight una vez (fuera del loop de scroll)
       ══════════════════════════════════════════════════════ */
    var heroBg    = document.getElementById('heroBg');
    var heroEl    = document.getElementById('lug-hero');
    // Leer offsetHeight UNA SOLA VEZ (no dentro del rAF = sin forced reflow)
    var heroHeight = heroEl ? heroEl.offsetHeight : 0;

    if (heroBg && heroEl && heroHeight > 0 &&
        !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {

        // Actualizar cache si la ventana cambia de tamaño
        window.addEventListener('resize', function() {
            heroHeight = heroEl.offsetHeight;
        }, { passive: true });

        var ticking = false;
        window.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(function() {
                    // Usar heroHeight cacheado: CERO lecturas de layout en el loop
                    if (window.scrollY < heroHeight) {
                        heroBg.style.transform = 'scale(1.04) translateY(' + (window.scrollY * 0.25) + 'px)';
                    }
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    }

    /* ══════════════════════════════════════════════════════
       TOAST
       ══════════════════════════════════════════════════════ */
    function showToast(msg) {
        var toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('show');
        setTimeout(function() { toast.classList.remove('show'); }, 3000);
    }

    /* ══════════════════════════════════════════════════════
       SMOOTH SCROLL para anclas
       ══════════════════════════════════════════════════════ */
    document.querySelectorAll('a[href^="#"]').forEach(function(a) {
        a.addEventListener('click', function(e) {
            var id = a.getAttribute('href').slice(1);
            if (!id) return;
            var target = document.getElementById(id);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    /* ══════════════════════════════════════════════════════
       UTILIDADES
       ══════════════════════════════════════════════════════ */
    function fixUrl(url) {
        if (!url) return '';
        if (/^https?:\/\//.test(url)) return url;
        return '/' + url.replace(/^\/+/, '');
    }

    function fmtDate(s) {
        try {
            return new Date(s).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
        } catch(e) { return s; }
    }

    function escHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

})();
