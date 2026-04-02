<?php 
    $page_title = "Rutas Cercanas | Rutas Rurales";
    $page_canonical = "https://www.rutasrurales.io/rutas.php";
    include 'header.php'; 
    
    // Parámetros URL para pre-seleccionar valores
    $preset_lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
    $preset_lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
    $preset_radius = isset($_GET['radius']) ? intval($_GET['radius']) : 100;
    $preset_provincia = isset($_GET['provincia']) ? $_GET['provincia'] : '';
    $preset_alojamientos = isset($_GET['alojamientos']) ? $_GET['alojamientos'] : '1';
    $preset_lugares = isset($_GET['lugares']) ? $_GET['lugares'] : '1';
    $preset_actividades = isset($_GET['actividades']) ? $_GET['actividades'] : '1';
    $preset_eventos = isset($_GET['eventos']) ? $_GET['eventos'] : '1';
    
    // Validar radio (entre 5 y 100)
    if ($preset_radius < 5) $preset_radius = 5;
    if ($preset_radius > 100) $preset_radius = 100;
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    :root {
        --card-height: 110px;
    }
    
    /* Prevenir desbordamientos globales */
    html, body {
        max-width: 100% !important;
        overflow-x: hidden !important;
    }

    .rutas-main { 
        display: grid; 
        grid-template-columns: 420px 1fr; 
        gap: 0; 
        min-height: calc(100vh - 80px);
        overflow: visible;
        margin-top: 80px;
    }
    
    .sidebar { 
        height: 100%; 
        overflow-y: auto; 
        padding: 1.2rem; 
        background: #f8f9fa;
        border-right: 1px solid #e0e0e0;
        display: flex;
        flex-direction: column;
    }

    .map-container { 
        height: 100%; 
        position: relative;
    }
    .map-container #map { height: 100%; width: 100%; }

    .controls-wrapper {
        position: sticky;
        top: 0;
        z-index: 100;
        background: #f8f9fa;
        padding: 0.5rem 0 1rem 0;
        margin-bottom: 0.5rem;
        border-bottom: 1px solid #eee;
    }

    .controls { 
        background: white; 
        padding: 1rem; 
        border-radius: 12px; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
    }
    
    .control-group { margin-bottom: 0.8rem; }
    .control-group:last-child { margin-bottom: 0; }
    .control-group label { font-weight: 700; color: var(--primary-color); display: block; margin-bottom: 0.3rem; font-size: 0.85rem; }
    
    .location-search { position: relative; }
    .location-search i { position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); color: #999; font-size: 0.9rem; }
    .location-search input[type="text"] { 
        width: 100%; 
        padding: 0.6rem 0.8rem 0.6rem 2.2rem; 
        border: 1px solid #ddd; 
        border-radius: 8px; 
        font-size: 16px; /* Evita zoom automático en móviles */
        transition: all 0.3s; 
    }
    .location-search input[type="text"]:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(47, 82, 51, 0.1); }
    
    .radius-control { display: flex; align-items: center; gap: 0.8rem; }
    .radius-control input { flex: 1; accent-color: var(--primary-color); }
    #radiusValue { font-weight: bold; min-width: 55px; color: var(--primary-color); font-size: 0.85rem; }

    .category-filters { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 0.4rem; 
    }
    .category-item { 
        display: flex; 
        align-items: center; 
        gap: 0.4rem; 
        padding: 0.4rem 0.6rem; 
        border-radius: 6px; 
        background: #f0f2f0; 
        cursor: pointer; 
        font-size: 0.75rem; 
        transition: all 0.2s;
        border: 1px solid transparent;
        user-select: none;
        font-weight: 600;
    }
    .category-item input { cursor: pointer; }
    .category-item:hover { background: #e4e8e4; }
    .category-item.active { background: white; border-color: var(--primary-color); color: var(--primary-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    
    .btn-buscar { 
        background: var(--primary-color); 
        color: white; 
        border: none; 
        padding: 0.7rem; 
        border-radius: 8px; 
        font-size: 0.9rem; 
        font-weight: bold; 
        cursor: pointer; 
        width: 100%; 
        transition: all 0.3s ease; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        gap: 0.5rem; 
        margin-top: 0.5rem; 
    }
    .btn-buscar:hover { background: #1e8449; transform: translateY(-1px); }
    
    .results-container { flex: 1; width: 100%; }
    .stats { font-size: 0.8rem; color: #666; margin-bottom: 0.8rem; display: flex; align-items: center; gap: 0.4rem; font-weight: 600; }
    
    /* Tarjetas Rectangulares y Pequeñas */
    .result-card { 
        background: white; 
        border-radius: 10px; 
        box-shadow: 0 2px 6px rgba(0,0,0,0.05); 
        margin-bottom: 0.8rem; 
        cursor: pointer; 
        transition: all 0.2s ease; 
        display: flex; 
        height: var(--card-height);
        overflow: hidden;
        border: 1px solid #eee;
        width: 100%;
    }
    .result-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-color: var(--primary-color); }
    
    .result-image-wrapper { width: 110px; height: 100%; flex-shrink: 0; }
    .result-image { width: 100%; height: 100%; object-fit: cover; }
    
    .result-content { flex: 1; padding: 0.6rem 0.8rem; display: flex; flex-direction: column; justify-content: space-between; min-width: 0; }
    .result-title { font-size: 0.9rem; font-weight: 700; color: #333; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; word-break: break-word; }
    
    .result-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 0.4rem; font-size: 0.7rem; color: #777; margin: 0.1rem 0; }
    .badge { padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.6rem; font-weight: 800; text-transform: uppercase; }
    .badge-alojamiento { background: #fff3e0; color: #e67e22; }
    .badge-lugar { background: #e8f5e9; color: #27ae60; }
    .badge-actividad { background: #e3f2fd; color: #3498db; }
    .badge-evento { background: #f3e5f5; color: #9b59b6; }
    
    .result-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
    .result-distance { font-size: 0.75rem; font-weight: 700; color: var(--primary-color); }
    .result-price { font-size: 0.85rem; font-weight: 800; color: #333; }

    .loading { text-align: center; padding: 2rem; color: var(--primary-color); font-weight: 600; }
    .no-results { text-align: center; padding: 2rem; color: #999; }
    
    /* Custom Scrollbar */
    .sidebar::-webkit-scrollbar { width: 5px; }
    .sidebar::-webkit-scrollbar-track { background: #f1f1f1; }
    .sidebar::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
    .sidebar::-webkit-scrollbar-thumb:hover { background: #bbb; }

    @media (max-width: 1024px) {
        .rutas-main { grid-template-columns: 350px 1fr; }
    }

    @media (max-width: 992px) {
        .rutas-main { 
            display: flex !important;
            flex-direction: column !important;
            height: auto !important; 
            overflow: visible !important; 
            margin-top: 80px !important; 
            width: 100% !important;
        }
        .sidebar { 
            width: 100% !important; 
            height: auto !important;
            overflow: visible !important; 
            border-right: none !important; 
            padding: 10px !important;
        }
        .map-container { 
            height: 50vh !important;
            min-height: 250px !important;
            max-height: 400px !important;
            width: 100% !important;
            order: -1 !important; 
        }
        .result-card {
            max-width: calc(100vw - 20px) !important;
        }
        .controls-wrapper { position: relative; top: 0; }
    }

    /* Ajustes para móviles en horizontal: diseño dividido para no bloquear el scroll */
    @media (max-width: 992px) and (orientation: landscape) {
        .rutas-main { 
            display: grid !important;
            grid-template-columns: 300px 1fr !important;
            height: calc(100vh - 80px) !important; 
            overflow: hidden !important; 
            margin-top: 80px !important;
        }
        .sidebar { 
            width: 100% !important; 
            height: 100% !important;
            overflow-y: auto !important; 
            border-right: 1px solid #e0e0e0 !important;
        }
        .map-container { 
            height: 100% !important; 
            min-height: auto !important;
            max-height: none !important;
            width: 100% !important;
            order: unset !important; 
        }
        .controls-wrapper { position: sticky !important; top: 0 !important; }
    }

    /* Títulos de los iconos en PC (Beige Corporativo) */
    @media (min-width: 993px) {
        .sidebar h2,
        .control-group label {
            color: var(--accent-color) !important;
        }
        .sidebar h2 i,
        .control-group label i {
            color: var(--accent-color) !important;
        }
    }
</style>

<main class="rutas-main">
    <aside class="sidebar">
        <h2 style="font-size: 1.2rem; color: var(--primary-color); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-route"></i> Rutas Cercanas
        </h2>
        <div class="controls-wrapper">
            <div class="controls">
                <div class="control-group">
                    <label for="locationInput"><i class="fas fa-map-marker-alt"></i> Ubicación</label>
                    <div class="location-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="locationInput" placeholder="Buscar lugar o usar mi ubicación...">
                    </div>
                    <button id="useCurrentLocation" class="btn-buscar" style="margin-top: 0.5rem; padding: 0.5rem; font-size: 0.8rem;">
                        <i class="fas fa-location-arrow"></i> Usar mi ubicación
                    </button>
                </div>

                <div class="control-group">
                    <label for="radiusInput"><i class="fas fa-expand-arrows-alt"></i> Radio de búsqueda</label>
                    <div class="radius-control">
                        <input type="range" id="radiusInput" min="5" max="100" value="50">
                        <span id="radiusValue">50 km</span>
                    </div>
                </div>

                <div class="control-group">
                    <label><i class="fas fa-filter"></i> Categorías</label>
                    <div class="category-filters">
                        <label class="category-item active">
                            <input type="checkbox" class="category-filter" value="alojamientos" checked> Alojamientos
                        </label>
                        <label class="category-item active">
                            <input type="checkbox" class="category-filter" value="lugares" checked> Lugares
                        </label>
                        <label class="category-item active">
                            <input type="checkbox" class="category-filter" value="actividades" checked> Actividades
                        </label>
                        <label class="category-item active">
                            <input type="checkbox" class="category-filter" value="eventos" checked> Eventos
                        </label>
                    </div>
                </div>

                <button id="searchButton" class="btn-buscar">
                    <i class="fas fa-sync-alt"></i> Actualizar resultados
                </button>
            </div>
        </div>

        <div id="loading" class="loading" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i> Buscando...
        </div>

        <div id="noResults" class="no-results" style="display: none;">
            <i class="fas fa-search"></i>
            <p>No se encontraron resultados en esta zona.</p>
        </div>

        <div id="resultsContainer" class="results-container" style="display: none;">
            <div class="stats">
                <i class="fas fa-map-pin"></i> <span id="resultsCount">0</span> resultados encontrados
            </div>
            <div id="resultsList"></div>
        </div>
    </aside>

    <div class="map-container">
        <div id="map"></div>
    </div>
</main>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Nominatim Geocoding -->
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<script>
    // Variables globales
    let map;
    let currentLocation = null;
    let markers = [];
    let userMarker = null;
    let currentResults = [];

    // Inicializar mapa
    function initMap() {
        if (map) return;
        
        map = L.map('map').setView([41.765, -2.468], 8); // Centro en Soria por defecto

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Añadir control de geocodificación
        const geocoder = L.Control.geocoder({
            defaultMarkGeocode: false,
            geocoder: new L.Control.Geocoder.nominatim()
        }).on('markgeocode', function(e) {
            const center = e.geocode.center;
            map.setView(center, 12);
            if (userMarker) {
                map.removeLayer(userMarker);
            }
            userMarker = L.marker(center, {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    shadowSize: [41, 41]
                })
            }).addTo(map).bindPopup(e.geocode.name || 'Ubicación seleccionada');
            currentLocation = center;
            searchNearby();
        }).addTo(map);

        // Event listeners
        document.getElementById('useCurrentLocation').addEventListener('click', function() {
            const btn = this;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Obteniendo...';
            btn.disabled = true;

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const center = L.latLng(lat, lng);
                    map.setView(center, 12);

                    if (userMarker) {
                        map.removeLayer(userMarker);
                    }
                    userMarker = L.marker(center, {
                        icon: L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                            shadowSize: [41, 41]
                        })
                    }).addTo(map).bindPopup('Tu ubicación actual');
                    currentLocation = center;
                    
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                    searchNearby(); // Auto-search
                }, function(error) {
                    alert('No se pudo obtener tu ubicación: ' + error.message);
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                });
            } else {
                alert('Geolocalización no soportada por tu navegador');
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        });

        document.getElementById('searchButton').addEventListener('click', searchNearby);
        
        let radiusTimeout;
        document.getElementById('radiusInput').addEventListener('input', function() {
            document.getElementById('radiusValue').textContent = this.value + ' km';
            clearTimeout(radiusTimeout);
            radiusTimeout = setTimeout(() => {
                if (currentLocation) searchNearby();
            }, 400);
        });

        // Buscar cuando se mueve el mapa
        map.on('moveend', function() {
            if (userMarker) {
                const center = map.getCenter();
                userMarker.setLatLng(center);
                currentLocation = center;
            }
        });

        // Forzar reajuste de tamaño para evitar mapa gris en carga inicial
        setTimeout(() => {
            map.invalidateSize();
        }, 500);

        // Reajustar mapa al cambiar orientación o tamaño (útil cuando sale el teclado)
        window.addEventListener('resize', () => {
            if (map) map.invalidateSize();
        });
    }

    // Buscar lugares cercanos
    async function searchNearby() {
        if (!currentLocation) {
            alert('Por favor, selecciona una ubicación primero');
            return;
        }

        // Mostrar loading
        document.getElementById('loading').style.display = 'block';
        document.getElementById('noResults').style.display = 'none';
        document.getElementById('resultsContainer').style.display = 'none';

        // Limpiar marcadores anteriores
        markers.forEach(marker => map.removeLayer(marker));
        markers = [];

        // Obtener categorías seleccionadas
        const selectedCategories = Array.from(document.querySelectorAll('.category-filter:checked'))
            .map(checkbox => checkbox.value);

        if (selectedCategories.length === 0) {
            alert('Por favor, selecciona al menos una categoría');
            document.getElementById('loading').style.display = 'none';
            return;
        }

        const radius = document.getElementById('radiusInput').value;

        try {
            const response = await fetch(`/api/rutas-cercanas.php?lat=${currentLocation.lat}&lng=${currentLocation.lng}&radius=${radius}&categories=${selectedCategories.join(',')}`);
            const data = await response.json();

            if (!data.success || !data.data || data.data.length === 0) {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('noResults').style.display = 'block';
                return;
            }

            currentResults = data.data;

            // Mostrar resultados
            const resultsList = document.getElementById('resultsList');
            resultsList.innerHTML = '';

            data.data.forEach((item, index) => {
                // Validar coordenadas antes de crear marcador
                const lat = parseFloat(item.latitud);
                const lng = parseFloat(item.longitud);
                
                if (isNaN(lat) || isNaN(lng) || Math.abs(lat) < 0.1) {
                    console.warn("Saltando marcador sin coordenadas para:", item.nombre);
                    return; // Saltar este item en el mapa
                }

                // Crear marcador en el mapa
                const iconUrl = getIconForType(item.tipo);
                const marker = L.marker([lat, lng], {
                    icon: L.icon({
                        iconUrl: iconUrl,
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        shadowSize: [41, 41]
                    })
                }).addTo(map).bindPopup(`
                    <div style="width: 200px;">
                        <img src="${item.foto || '/tourist_activities_images/Patrocinio.webp'}" style="width: 100%; height: 100px; object-fit: cover; border-radius: 8px; margin-bottom: 8px;">
                        <b style="color: var(--primary-color);">${item.nombre}</b><br>
                        <span class="badge badge-${item.tipo}">${item.tipo}</span>
                        ${item.tipo === 'evento' && item.fecha ? `<span style="font-size: 0.7rem; color: #8e44ad; font-weight: 700; background: #f3e5f5; padding: 2px 6px; border-radius: 4px; margin-left: 5px; border: 1px solid #e1bee7;"><i class="far fa-calendar-alt"></i> ${new Date(item.fecha).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })}</span>` : ''}<br>
                        <small>${item.localidad}</small><br>
                        <a href="${getUrlForItem(item)}" target="_blank" style="display: block; margin-top: 8px; color: var(--primary-color); font-weight: bold; text-decoration: none;">Ver detalles →</a>
                    </div>
                `);

                markers.push(marker);

                // Crear tarjeta de resultado
                const card = document.createElement('div');
                card.className = 'result-card';
                card.onclick = function() {
                    window.open(getUrlForItem(item), '_blank');
                };
                
                // Hover effect on map
                card.onmouseenter = () => marker.openPopup();

                const badgeClass = `badge-${item.tipo}`;

                card.innerHTML = `
                    <div class="result-image-wrapper">
                        <img src="${item.foto || '/tourist_activities_images/Patrocinio.webp'}" alt="${item.nombre}" class="result-image" onerror="this.src='/tourist_activities_images/Patrocinio.webp'">
                    </div>
                    <div class="result-content">
                        <div>
                            <div class="result-title">${item.nombre}</div>
                            <div class="result-meta">
                                <span class="badge ${badgeClass}">${item.tipo}</span>
                                <span>${item.localidad}</span>
                                ${item.tipo === 'evento' && item.fecha ? `<span style="display: flex; align-items: center; gap: 4px; background: #f3e5f5; border: 1px solid #e1bee7; padding: 0.15rem 0.5rem; border-radius: 6px; font-weight: 700; color: #8e44ad; font-size: 0.65rem; text-transform: uppercase; margin-left: auto;"><i class="far fa-calendar-alt"></i> ${new Date(item.fecha).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })}</span>` : ''}
                            </div>
                        </div>
                        <div class="result-footer">
                            <div class="result-distance"><i class="fas fa-location-arrow" style="font-size: 0.7rem;"></i> ${item.distancia} km</div>
                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                                <div class="result-price">${item.precio}</div>
                                <button onclick="event.stopPropagation(); addToItineraryFromMap(${index})" title="Añadir a mi ruta" style="background: var(--primary-color); border: none; color: white; cursor: pointer; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button onclick="event.stopPropagation(); window.location.href='user-dashboard.html?chat_with=${item.propietario_id || 1}#mensajes'" title="Contactar" style="background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 1.1rem; padding: 0; display: flex;">
                                    <i class="fas fa-comment-dots"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                resultsList.appendChild(card);
            });

            // Mostrar resultados
            document.getElementById('loading').style.display = 'none';
            document.getElementById('resultsContainer').style.display = 'block';
            document.getElementById('resultsCount').textContent = data.count;

            // Ajustar vista del mapa
            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds());
            }

        } catch (error) {
            console.error('Error:', error);
            document.getElementById('loading').style.display = 'none';
            document.getElementById('noResults').style.display = 'block';
        }
    }

    // Obtener icono para tipo de item
    function getIconForType(tipo) {
        const baseUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-';
        const icons = {
            'alojamiento': baseUrl + 'orange.png',
            'lugar': baseUrl + 'green.png',
            'actividad': baseUrl + 'blue.png',
            'evento': baseUrl + 'violet.png'
        };
        return icons[tipo] || baseUrl + 'blue.png';
    }

    // Función para añadir al itinerario desde el mapa
    function addToItineraryFromMap(index) {
        const item = currentResults[index];
        if (!item) return;

        let myRoute = JSON.parse(localStorage.getItem('myPersonalRoute') || '[]');
        
        // Evitar duplicados
        if (myRoute.some(i => i.id === item.id && i.tipo === item.tipo)) {
            alert('Este punto ya está en tu itinerario');
            return;
        }

        myRoute.push({
            id: item.id,
            nombre: item.nombre,
            tipo: item.tipo,
            lat: item.latitud,
            lng: item.longitud,
            slug: item.slug,
            foto: item.foto,
            localidad: item.localidad
        });

        localStorage.setItem('myPersonalRoute', JSON.stringify(myRoute));
        
        // Feedback visual
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.style.background = '#27ae60';
        
        if (confirm(`✅ ¡Añadido! Tienes ${myRoute.length} paradas.\n\n¿Quieres ver tu itinerario ahora?`)) {
            window.location.href = '/mi-ruta.html';
        }
        
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.style.background = 'var(--primary-color)';
        }, 2000);
    }

    // Obtener URL para item
    function getUrlForItem(item) {
        switch (item.tipo) {
            case 'alojamiento': return `/alojamiento/${item.slug}`;
            case 'lugar': return `/lugar/${item.slug}`;
            case 'actividad': return `/actividad/${item.slug}`;
            case 'evento': return `/evento/${item.slug}`;
            default: return '/';
        }
    }

    // Valores pre-seleccionados desde PHP
    const presetData = {
        lat: <?php echo $preset_lat ? $preset_lat : 'null'; ?>,
        lng: <?php echo $preset_lng ? $preset_lng : 'null'; ?>,
        radius: <?php echo $preset_radius; ?>,
        provincia: "<?php echo addslashes($preset_provincia); ?>",
        alojamientos: "<?php echo $preset_alojamientos; ?>" === '1',
        lugares: "<?php echo $preset_lugares; ?>" === '1',
        actividades: "<?php echo $preset_actividades; ?>" === '1',
        eventos: "<?php echo $preset_eventos; ?>" === '1'
    };

    // Función para aplicar los valores pre-seleccionados
    function applyPresetValues() {
        // Aplicar radio
        const radiusInput = document.getElementById('radiusInput');
        const radiusValue = document.getElementById('radiusValue');
        if (presetData.radius) {
            radiusInput.value = presetData.radius;
            radiusValue.textContent = presetData.radius + ' km';
        }

        // Aplicar categorías
        const categoryFilters = document.querySelectorAll('.category-filter');
        categoryFilters.forEach(checkbox => {
            const value = checkbox.value;
            let checked = false;
            if (value === 'alojamientos') checked = presetData.alojamientos;
            else if (value === 'lugares') checked = presetData.lugares;
            else if (value === 'actividades') checked = presetData.actividades;
            else if (value === 'eventos') checked = presetData.eventos;
            
            checkbox.checked = checked;
            checkbox.parentElement.classList.toggle('active', checked);
        });

        // Si hay provincia o coordenadas, buscar automáticamente
        if (presetData.provincia || (presetData.lat && presetData.lng)) {
            // Si tenemos coordenadas, usarlas directamente
            if (presetData.lat && presetData.lng) {
                const center = L.latLng(presetData.lat, presetData.lng);
                map.setView(center, 11);
                
                if (userMarker) {
                    map.removeLayer(userMarker);
                }
                userMarker = L.marker(center, {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        shadowSize: [41, 41]
                    })
                }).addTo(map).bindPopup(presetData.provincia || 'Ubicación del evento');
                currentLocation = center;
                
                // Buscar automáticamente
                setTimeout(() => searchNearby(), 500);
            } 
            // Si solo tenemos provincia, geocodificarla
            else if (presetData.provincia) {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(presetData.provincia + ', España')}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            const result = data[0];
                            const center = L.latLng(parseFloat(result.lat), parseFloat(result.lon));
                            map.setView(center, 10);

                            if (userMarker) {
                                map.removeLayer(userMarker);
                            }
                            userMarker = L.marker(center, {
                                icon: L.icon({
                                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                                    iconSize: [25, 41],
                                    iconAnchor: [12, 41],
                                    popupAnchor: [1, -34],
                                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                    shadowSize: [41, 41]
                                })
                            }).addTo(map).bindPopup(presetData.provincia);
                            currentLocation = center;
                            
                            // Buscar automáticamente
                            setTimeout(() => searchNearby(), 500);
                        }
                    })
                    .catch(error => {
                        console.error('Error en geocodificación de provincia:', error);
                    });
            }
        }
    }

    // Inicializar al cargar la página
    window.addEventListener('DOMContentLoaded', function() {
        initMap();

        // Aplicar valores pre-seleccionados después de un pequeño delay para asegurar que el mapa está listo
        setTimeout(applyPresetValues, 300);

        // Toggle active class on category filters
        document.querySelectorAll('.category-filter').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                this.parentElement.classList.toggle('active', this.checked);
                searchNearby(); // Auto-search on filter change
            });
        });

        // Geocodificación para el input de ubicación
        const locationInput = document.getElementById('locationInput');
        let timeout = null;

        locationInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                const query = locationInput.value;
                if (query.length > 3) {
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.length > 0) {
                                const result = data[0];
                                const center = L.latLng(parseFloat(result.lat), parseFloat(result.lon));
                                map.setView(center, 12);

                                if (userMarker) {
                                    map.removeLayer(userMarker);
                                }
                                userMarker = L.marker(center, {
                                    icon: L.icon({
                                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                                        iconSize: [25, 41],
                                        iconAnchor: [12, 41],
                                        popupAnchor: [1, -34],
                                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                        shadowSize: [41, 41]
                                    })
                                }).addTo(map).bindPopup(result.display_name);
                                currentLocation = center;
                                searchNearby(); // Auto-search when location is found
                            }
                        })
                        .catch(error => {
                            console.error('Error en geocodificación:', error);
                        });
                }
            }, 500);
        });
    });
</script>

<footer class="footer">
    <div class="container">
        <div class="footer-content-simple">
            <div class="footer-info">
                <span><i class="fas fa-envelope"></i> olgamarin@rutasrurales.io</span>
                <span><i class="fas fa-phone"></i> +34 605 249 696</span>
            </div>
            <div class="footer-links">
                <a href="/aviso-legal.html">Aviso Legal</a>
                <a href="/politica-cookies.html">Política de Cookies</a>
                <a href="/agradecimientos.html">Agradecimientos</a>
            </div>
            <div class="footer-social">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="https://www.instagram.com/rutas_rurales/" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
        <div class="footer-copyright">
            <p>&copy; 2026 rutasrurales.io. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>

<script src="script.js?v=20260114"></script>

</body>
</html>
