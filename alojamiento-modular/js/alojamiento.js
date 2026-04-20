/**
 * alojamiento.js — Módulo JS para página de detalle de alojamiento
 * Versión 2.0 — Corregido y optimizado
 *
 * Depende de variables globales inyectadas por index.php:
 *   ALO       → datos del alojamiento (o null)
 *   ALO_LANG  → traducciones de UI
 *   ALO_FOTOS → array de URLs de fotos
 */

(function () {
    'use strict';

    if (!window.ALO) return;

    var API    = '/alojamiento-modular/api/alojamiento-data.php';
    var RADIUS = 50;
    // Leaflet servido localmente — sin dependencia de CDN externo
    var L_CSS  = '/alojamiento-modular/js/leaflet/leaflet.css';
    var L_JS   = '/alojamiento-modular/js/leaflet/leaflet.js';

    var alo   = window.ALO;
    var lang  = window.ALO_LANG  || {};
    var fotos = window.ALO_FOTOS || [];

    /* ── Cache de datos cercanos (una sola llamada API) ── */
    var nearbyCache     = null;
    var nearbyLoading   = false;
    var nearbyCallbacks = [];

    function getNearbyData() {
        return new Promise(function(resolve, reject) {
            if (nearbyCache)   { resolve(nearbyCache); return; }
            if (nearbyLoading) { nearbyCallbacks.push({resolve:resolve, reject:reject}); return; }

            nearbyLoading = true;
            nearbyCallbacks.push({resolve:resolve, reject:reject});

            var url = API + '?slug=' + encodeURIComponent(alo.slug)
                + '&lat='    + alo.latitude
                + '&lng='    + alo.longitude
                + '&radius=' + RADIUS
                + '&mode=nearby';

            fetch(url)
                .then(function(r){ return r.json(); })
                .then(function(data){
                    nearbyCache   = (data.success && data.data) ? data.data : {};
                    nearbyLoading = false;
                    nearbyCallbacks.forEach(function(cb){ cb.resolve(nearbyCache); });
                    nearbyCallbacks = [];
                })
                .catch(function(err){
                    nearbyLoading = false;
                    nearbyCallbacks.forEach(function(cb){ cb.reject(err); });
                    nearbyCallbacks = [];
                });
        });
    }

    /* ══════════════════════════════════════════════════════
       GALERÍA
       ══════════════════════════════════════════════════════ */
    var Gallery = {
        currentIndex: 0,

        init: function() {
            var self     = this;
            var mainEl   = document.getElementById('galleryMain');
            var mainImg  = document.getElementById('galleryMainImg');
            var thumbs   = document.querySelectorAll('.gallery-thumb');
            var counter  = document.getElementById('galleryCounter');
            var expandBtn = document.getElementById('galleryExpandBtn');

            if (!mainImg) return;

            thumbs.forEach(function(thumb) {
                thumb.addEventListener('click', function() {
                    var idx = parseInt(thumb.dataset.index, 10);
                    self.setActive(idx, mainImg, thumbs, counter);
                });
                thumb.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); thumb.click(); }
                });
            });

            if (mainEl) {
                mainEl.addEventListener('click', function() { Lightbox.open(self.currentIndex); });
                mainEl.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') Lightbox.open(self.currentIndex);
                });
            }

            if (expandBtn) {
                expandBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    Lightbox.open(self.currentIndex);
                });
            }

            if (fotos.length > 1) {
                setTimeout(function() {
                    fotos.slice(1, 4).forEach(function(src) {
                        var img = new Image(); img.src = src;
                    });
                }, 600);
            }
        },

        setActive: function(idx, mainImg, thumbs, counter) {
            if (!fotos[idx]) return;
            this.currentIndex = idx;
            mainImg.style.opacity = '0.6';
            mainImg.src = fotos[idx];
            mainImg.onload = function() { mainImg.style.opacity = '1'; };
            thumbs.forEach(function(t) {
                t.classList.toggle('active', parseInt(t.dataset.index, 10) === idx);
            });
            if (counter) counter.textContent = (idx + 1) + ' / ' + fotos.length;
        }
    };

    /* ══════════════════════════════════════════════════════
       LIGHTBOX
       ══════════════════════════════════════════════════════ */
    var Lightbox = {
        currentIndex: 0,

        init: function() {
            var self    = this;
            var overlay = document.getElementById('lightbox');
            if (!overlay) return;

            var closeBtn = document.getElementById('lightboxClose');
            var prevBtn  = document.getElementById('lightboxPrev');
            var nextBtn  = document.getElementById('lightboxNext');

            if (closeBtn) closeBtn.addEventListener('click', function() { self.close(); });
            if (prevBtn)  prevBtn.addEventListener('click',  function() { self.prev(); });
            if (nextBtn)  nextBtn.addEventListener('click',  function() { self.next(); });

            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) self.close();
            });

            document.addEventListener('keydown', function(e) {
                if (!overlay.classList.contains('active')) return;
                if (e.key === 'Escape')     self.close();
                if (e.key === 'ArrowLeft')  self.prev();
                if (e.key === 'ArrowRight') self.next();
            });

            var touchStartX = 0;
            overlay.addEventListener('touchstart', function(e) {
                touchStartX = e.touches[0].clientX;
            }, {passive: true});
            overlay.addEventListener('touchend', function(e) {
                var diff = touchStartX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 50) { if (diff > 0) self.next(); else self.prev(); }
            });
        },

        open: function(idx) {
            if (!fotos.length) return;
            this.currentIndex = idx;
            this.show();
            document.body.style.overflow = 'hidden';
        },

        close: function() {
            var overlay = document.getElementById('lightbox');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
        },

        prev: function() {
            this.currentIndex = (this.currentIndex - 1 + fotos.length) % fotos.length;
            this.show();
        },

        next: function() {
            this.currentIndex = (this.currentIndex + 1) % fotos.length;
            this.show();
        },

        show: function() {
            var overlay = document.getElementById('lightbox');
            var img     = document.getElementById('lightboxImg');
            var caption = document.getElementById('lightboxCaption');
            if (!overlay || !img) return;

            overlay.classList.add('active');
            img.src = fotos[this.currentIndex];
            img.alt = alo.name + ' — foto ' + (this.currentIndex + 1);
            if (caption) caption.textContent = (this.currentIndex + 1) + ' / ' + fotos.length;

            var prev = document.getElementById('lightboxPrev');
            var next = document.getElementById('lightboxNext');
            var show = fotos.length > 1 ? '' : 'none';
            if (prev) prev.style.display = show;
            if (next) next.style.display = show;
        }
    };

    /* ══════════════════════════════════════════════════════
       MAPA (Leaflet lazy — solo al hacer clic)
       ══════════════════════════════════════════════════════ */
    var MapModule = {
        loaded: false,
        map: null,

        init: function() {
            if (!alo.latitude || !alo.longitude) return;
            var self        = this;
            var placeholder = document.getElementById('mapPlaceholder');
            if (!placeholder) return;

            // Solo carga al hacer clic o pulsar Enter/Espacio — NO auto-load
            placeholder.addEventListener('click', function() { self.load(); });
            placeholder.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); self.load(); }
            });
        },

        load: function() {
            if (this.loaded) return;
            this.loaded = true;
            var self        = this;
            var placeholder = document.getElementById('mapPlaceholder');
            var mapEl       = document.getElementById('alo-map');
            if (!mapEl) return;

            if (placeholder) {
                placeholder.innerHTML = '<div style="font-size:2rem">⏳</div><p style="margin-top:8px;font-size:0.85rem">Cargando mapa…</p>';
            }

            this.loadLeaflet()
                .then(function() {
                    if (placeholder) placeholder.style.display = 'none';
                    mapEl.style.display = 'block';
                    self.initMap(mapEl);
                    self.addMainMarker();
                    getNearbyData()
                        .then(function(data) { self.addNearbyMarkers(data); })
                        .catch(function() {});
                })
                .catch(function() {
                    self.loaded = false;
                    if (placeholder) {
                        placeholder.style.display = 'flex';
                        placeholder.innerHTML = '<div class="map-placeholder-icon">🗺️</div>'
                            + '<h3>Error al cargar el mapa</h3>'
                            + '<span class="map-hint">Haz clic para reintentar</span>';
                    }
                });
        },

        loadLeaflet: function() {
            return new Promise(function(resolve, reject) {
                if (window.L) { resolve(); return; }

                // CSS de Leaflet (local, sin integrity)
                var css = document.createElement('link');
                css.rel  = 'stylesheet';
                css.href = L_CSS;
                document.head.appendChild(css);

                // JS de Leaflet (local, sin integrity)
                var js = document.createElement('script');
                js.src    = L_JS;
                js.onload  = resolve;
                js.onerror = reject;
                document.head.appendChild(js);
            });
        },

        initMap: function(mapEl) {
            this.map = L.map(mapEl, {zoomControl: true, scrollWheelZoom: false})
                .setView([alo.latitude, alo.longitude], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 18
            }).addTo(this.map);
        },

        addMainMarker: function() {
            if (!this.map) return;
            var icon = L.divIcon({
                className: '',
                html: '<div style="background:#2F5233;color:#fff;border-radius:50% 50% 50% 0;'
                    + 'width:36px;height:36px;display:flex;align-items:center;justify-content:center;'
                    + 'font-size:18px;transform:rotate(-45deg);box-shadow:0 2px 8px rgba(0,0,0,0.3);'
                    + 'border:2px solid #fff;"><span style="transform:rotate(45deg)">🏠</span></div>',
                iconSize: [36, 36],
                iconAnchor: [18, 36],
                popupAnchor: [0, -36]
            });

            var popup = '<div style="padding:6px 2px;min-width:160px;">'
                + '<strong style="color:#2F5233;font-size:0.95rem;">' + this.esc(alo.name) + '</strong>'
                + (alo.address ? '<br><small style="color:#666">' + this.esc(alo.address) + '</small>' : '')
                + (alo.precio_noche > 0 ? '<br><span style="color:#2F5233;font-weight:700">' + alo.precio_noche + '€ / noche</span>' : '')
                + '</div>';

            L.marker([alo.latitude, alo.longitude], {icon: icon})
                .addTo(this.map)
                .bindPopup(popup)
                .openPopup();
        },

        addNearbyMarkers: function(data) {
            if (!this.map) return;
            var self = this;
            var configs = [
                {items: data.alojamientos || [],                    emoji: '🏠', color: '#2F5233'},
                {items: (data.lugares || []).slice(0, 5),           emoji: '🏛️', color: '#1565C0'},
                {items: (data.actividades || []).slice(0, 4),       emoji: '🎯', color: '#E65100'},
                {items: (data.eventos_similares || []).slice(0, 4), emoji: '🎭', color: '#6A1B9A'}
            ];

            configs.forEach(function(cfg) {
                cfg.items.forEach(function(item) {
                    if (!item.latitude || !item.longitude) return;
                    var icon = L.divIcon({
                        className: '',
                        html: '<div style="background:' + cfg.color + ';color:#fff;border-radius:50%;'
                            + 'width:28px;height:28px;display:flex;align-items:center;justify-content:center;'
                            + 'font-size:14px;box-shadow:0 2px 6px rgba(0,0,0,0.25);border:2px solid #fff;">'
                            + cfg.emoji + '</div>',
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                        popupAnchor: [0, -14]
                    });
                    var dist = item.distance > 0 ? ' · ' + item.distance + ' km' : '';
                    L.marker([item.latitude, item.longitude], {icon: icon})
                        .addTo(self.map)
                        .bindPopup('<b>' + self.esc(item.name) + '</b><br><small>'
                            + self.esc(item.municipality || '') + dist + '</small>');
                });
            });
        },

        esc: function(str) {
            return String(str || '')
                .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
    };

    /* ══════════════════════════════════════════════════════
       CONTENIDO CERCANO (tabs)
       ══════════════════════════════════════════════════════ */
    var NearbyModule = {
        rendered: {},

        init: function() {
            var self = this;

            document.querySelectorAll('.nearby-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    self.activateTab(tab.dataset.tab);
                });
            });

            var section = document.getElementById('secCercanos');
            if (!section) return;

            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            observer.disconnect();
                            // Pequeño delay para no bloquear el hilo principal
                            setTimeout(function() { self.loadAndRender('alojamientos'); }, 100);
                        }
                    });
                }, {rootMargin: '0px', threshold: 0.1});
                observer.observe(section);
            } else {
                setTimeout(function() { self.loadAndRender('alojamientos'); }, 2000);
            }
        },

        activateTab: function(tabName) {
            var self = this;
            document.querySelectorAll('.nearby-tab').forEach(function(t) {
                var active = t.dataset.tab === tabName;
                t.classList.toggle('active', active);
                t.setAttribute('aria-selected', active);
            });
            document.querySelectorAll('.nearby-panel').forEach(function(p) {
                p.classList.toggle('active', p.id === 'nearby-' + tabName);
            });
            if (!this.rendered[tabName]) {
                this.loadAndRender(tabName);
            }
        },

        loadAndRender: function(tabName) {
            var self = this;
            if (!alo.latitude || !alo.longitude) {
                this.renderEmpty(tabName);
                return;
            }
            getNearbyData()
                .then(function(data) {
                    var map = {
                        alojamientos: data.alojamientos      || [],
                        lugares:      data.lugares            || [],
                        eventos:      data.eventos_similares  || [],
                        actividades:  data.actividades        || []
                    };
                    Object.keys(map).forEach(function(key) {
                        if (!self.rendered[key]) self.renderTab(key, map[key]);
                    });
                })
                .catch(function() { self.renderError(tabName); });
        },

        renderTab: function(tabName, items) {
            this.rendered[tabName] = true;
            var self  = this;
            var panel = document.getElementById('nearby-' + tabName);
            if (!panel) return;

            var grid = document.createElement('div');
            grid.className = 'nearby-grid';

            if (!items || items.length === 0) {
                grid.innerHTML = '<div class="nearby-empty"><i class="fas fa-search"></i>'
                    + (lang.sin_resultados || 'Sin resultados') + '</div>';
                panel.innerHTML = '';
                panel.appendChild(grid);
                return;
            }

            items.slice(0, 6).forEach(function(item) {
                grid.appendChild(self.createCard(item, tabName));
            });

            panel.innerHTML = '';
            panel.appendChild(grid);

            if (items.length > 6) {
                var btn = document.createElement('button');
                btn.className   = 'nearby-show-more';
                btn.textContent = lang.ver_mas || 'Ver más';
                btn.addEventListener('click', function() {
                    items.slice(6).forEach(function(item) {
                        grid.appendChild(self.createCard(item, tabName));
                    });
                    btn.remove();
                });
                panel.appendChild(btn);
            }
        },

        createCard: function(item, type) {
            var self = this;
            var card = document.createElement('a');
            card.className = 'nearby-card';
            card.href = item.url || '#';

            /* Imagen */
            var imgWrap = document.createElement('div');
            imgWrap.className = 'nearby-card-img';

            if (item.main_image) {
                var img = document.createElement('img');
                img.src     = self.fixUrl(item.main_image);
                img.alt     = item.name || '';
                img.loading = 'lazy';
                img.onerror = function() {
                    imgWrap.innerHTML = '<div class="nearby-card-img-placeholder">'
                        + self.typeEmoji(type) + '</div>';
                };
                imgWrap.appendChild(img);
            } else {
                imgWrap.innerHTML = '<div class="nearby-card-img-placeholder">'
                    + self.typeEmoji(type) + '</div>';
            }

            if (item.distance > 0) {
                var dist = document.createElement('span');
                dist.className   = 'nearby-card-dist';
                dist.textContent = item.distance + ' ' + (lang.km || 'km');
                imgWrap.appendChild(dist);
            }

            /* Cuerpo */
            var body = document.createElement('div');
            body.className = 'nearby-card-body';

            var name = document.createElement('div');
            name.className   = 'nearby-card-name';
            name.textContent = item.name || '';
            body.appendChild(name);

            if (item.municipality) {
                var meta = document.createElement('div');
                meta.className = 'nearby-card-meta';
                meta.innerHTML = '<i class="fas fa-map-marker-alt"></i> ' + self.esc(item.municipality);
                body.appendChild(meta);
            }

            /* Precio / fecha */
            if (type === 'alojamientos' && item.price_per_night > 0) {
                var p = document.createElement('div');
                p.className   = 'nearby-card-price';
                p.textContent = item.price_per_night + '€ / ' + (lang.noche || 'noche');
                body.appendChild(p);
            } else if (type === 'actividades' && item.price > 0) {
                var p2 = document.createElement('div');
                p2.className   = 'nearby-card-price';
                p2.textContent = (lang.desde || 'desde') + ' ' + item.price + '€';
                body.appendChild(p2);
            } else if (type === 'eventos') {
                if (item.is_free == 1) {
                    var fr = document.createElement('span');
                    fr.className   = 'nearby-card-free';
                    fr.textContent = lang.gratis || 'Gratis';
                    body.appendChild(fr);
                } else if (item.ticket_price > 0) {
                    var tp = document.createElement('div');
                    tp.className   = 'nearby-card-price';
                    tp.textContent = item.ticket_price + '€';
                    body.appendChild(tp);
                }
                if (item.start_date) {
                    var dt = document.createElement('div');
                    dt.className   = 'nearby-card-date';
                    dt.textContent = self.fmtDate(item.start_date);
                    body.appendChild(dt);
                }
            }

            card.appendChild(imgWrap);
            card.appendChild(body);
            return card;
        },

        renderEmpty: function(tabName) {
            this.rendered[tabName] = true;
            var panel = document.getElementById('nearby-' + tabName);
            if (!panel) return;
            panel.innerHTML = '<div class="nearby-grid"><div class="nearby-empty">'
                + '<i class="fas fa-search"></i>'
                + (lang.sin_resultados || 'Sin resultados') + '</div></div>';
        },

        renderError: function(tabName) {
            this.rendered[tabName] = true;
            var panel = document.getElementById('nearby-' + tabName);
            if (!panel) return;
            panel.innerHTML = '<div class="nearby-grid"><div class="nearby-empty">'
                + '<i class="fas fa-exclamation-circle"></i>Error al cargar.</div></div>';
        },

        fixUrl: function(url) {
            if (!url) return '';
            if (/^https?:\/\//.test(url)) return url;
            return '/' + url.replace(/^\/+/, '');
        },

        typeEmoji: function(type) {
            var m = {alojamientos:'🏠', lugares:'🏛️', eventos:'🎭', actividades:'🎯'};
            return m[type] || '📍';
        },

        fmtDate: function(s) {
            try {
                return new Date(s).toLocaleDateString('es-ES', {day:'numeric', month:'short', year:'numeric'});
            } catch(e) { return s; }
        },

        esc: function(str) {
            return String(str || '')
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }
    };

    /* ══════════════════════════════════════════════════════
       DESCRIPCIÓN EXPANDIBLE
       ══════════════════════════════════════════════════════ */
    function initDescExpand() {
        var btn  = document.getElementById('descExpandBtn');
        var text = document.getElementById('descText');
        if (!btn || !text) return;
        btn.addEventListener('click', function() {
            text.classList.remove('collapsed');
            btn.remove();
        });
    }

    /* ══════════════════════════════════════════════════════
       COMPARTIR
       ══════════════════════════════════════════════════════ */
    function initShare() {
        var btn = document.getElementById('btnShare');
        if (!btn) return;
        btn.addEventListener('click', function() {
            var shareData = {title: alo.name, text: alo.name + ' — ' + (alo.municipality || ''), url: window.location.href};
            if (navigator.share) {
                navigator.share(shareData).catch(function(){});
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(window.location.href).then(function() {
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(function() { btn.innerHTML = '<i class="fas fa-share-alt"></i>'; }, 2000);
                }).catch(function(){});
            }
        });
    }

    /* ══════════════════════════════════════════════════════
       FAVORITO
       ══════════════════════════════════════════════════════ */
    function initFav() {
        var btn = document.getElementById('btnFav');
        if (!btn) return;
        var key = 'fav_alo_' + alo.id;
        if (localStorage.getItem(key) === '1') {
            btn.innerHTML = '<i class="fas fa-heart" style="color:#ff6b6b"></i>';
        }
        btn.addEventListener('click', function() {
            if (localStorage.getItem(key) === '1') {
                localStorage.removeItem(key);
                btn.innerHTML = '<i class="far fa-heart"></i>';
            } else {
                localStorage.setItem(key, '1');
                btn.innerHTML = '<i class="fas fa-heart" style="color:#ff6b6b"></i>';
                btn.style.transform = 'scale(1.3)';
                setTimeout(function() { btn.style.transform = ''; }, 300);
            }
        });
    }

    /* ══════════════════════════════════════════════════════
       HERO PARALLAX
       ══════════════════════════════════════════════════════ */
    function initHeroParallax() {
        var bg = document.getElementById('heroBg');
        if (!bg) return;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        var ticking = false;
        window.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(function() {
                    var hero = document.getElementById('alo-hero');
                    if (hero && window.scrollY < hero.offsetHeight) {
                        bg.style.transform = 'scale(1.04) translateY(' + (window.scrollY * 0.25) + 'px)';
                    }
                    ticking = false;
                });
                ticking = true;
            }
        }, {passive: true});
    }

    /* ══════════════════════════════════════════════════════
       SMOOTH SCROLL
       ══════════════════════════════════════════════════════ */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function(a) {
            a.addEventListener('click', function(e) {
                var id = a.getAttribute('href').slice(1);
                if (!id) return;
                var target = document.getElementById(id);
                if (target) { e.preventDefault(); target.scrollIntoView({behavior:'smooth', block:'start'}); }
            });
        });
    }

    /* ══════════════════════════════════════════════════════
       INIT
       ══════════════════════════════════════════════════════ */
    document.addEventListener('DOMContentLoaded', function() {
        Gallery.init();
        Lightbox.init();
        MapModule.init();
        NearbyModule.init();
        initDescExpand();
        initShare();
        initFav();
        initHeroParallax();
        initSmoothScroll();
    });

})();
