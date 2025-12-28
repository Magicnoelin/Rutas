/**
 * crear-ruta.js
 * JavaScript para la página de crear rutas turísticas
 */

// Variables globales
let map;
let markers = [];
let selectedItems = {
    alojamientos: [],
    actividades: [],
    lugares: [],
    eventos: []
};

// Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    loadItems();
    setupEventListeners();
    setupFileUploads();
});

/**
 * Inicializar el mapa de Leaflet
 */
function initMap() {
    // Centro en Castilla y León (aproximadamente Soria)
    map = L.map('map').setView([41.7665, -2.4790], 10);

    // Añadir capa de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(map);

    // Permitir añadir marcadores al hacer clic
    map.on('click', function(e) {
        addMarker(e.latlng);
    });
}

/**
 * Añadir marcador al mapa
 */
function addMarker(latlng, itemData = null) {
    const marker = L.marker(latlng, {
        draggable: true
    }).addTo(map);

    if (itemData) {
        marker.bindPopup(`
            <div style="min-width: 200px;">
                <h4 style="margin: 0 0 0.5rem 0; color: #2c5f2d;">${itemData.name}</h4>
                <p style="margin: 0; font-size: 0.9rem;">${itemData.type}</p>
                ${itemData.price ? `<p style="margin: 0.5rem 0 0 0; font-weight: 600; color: #d4a574;">${itemData.price}€</p>` : ''}
            </div>
        `);
    } else {
        marker.bindPopup('Punto de interés personalizado');
    }

    markers.push({
        marker: marker,
        latlng: latlng,
        itemData: itemData
    });

    // Actualizar al arrastrar
    marker.on('dragend', function(e) {
        const newLatLng = e.target.getLatLng();
        updateMarkerPosition(marker, newLatLng);
    });

    return marker;
}

/**
 * Actualizar posición del marcador
 */
function updateMarkerPosition(marker, newLatLng) {
    const index = markers.findIndex(m => m.marker === marker);
    if (index !== -1) {
        markers[index].latlng = newLatLng;
    }
}

/**
 * Cargar elementos de las diferentes categorías
 */
async function loadItems() {
    await loadAlojamientos();
    await loadActividades();
    await loadLugares();
    await loadEventos();
}

/**
 * Cargar alojamientos desde la API
 */
async function loadAlojamientos() {
    try {
        const response = await fetch(API_BASE_URL + 'accommodations.php?status=active&limit=20');
        const data = await response.json();
        
        if (data.success && data.accommodations) {
            renderItems('alojamientos', data.accommodations, 'alojamiento');
        }
    } catch (error) {
        console.error('Error cargando alojamientos:', error);
    }
}

/**
 * Cargar actividades turísticas
 */
async function loadActividades() {
    try {
        const response = await fetch(API_BASE_URL + 'tourist-activities.php?status=active&limit=20');
        const data = await response.json();
        
        if (data.success && data.activities) {
            renderItems('actividades', data.activities, 'actividad');
        } else {
            // Datos de ejemplo si no hay API
            const ejemplos = [
                {
                    id: 1,
                    name: 'Senderismo Laguna Negra',
                    description: 'Ruta de montaña hasta la mítica Laguna Negra',
                    photo1: 'https://images.unsplash.com/photo-1551632811-561732d1e306?w=400',
                    price_per_person: 15,
                    latitude: 41.9893,
                    longitude: -2.8508
                },
                {
                    id: 2,
                    name: 'Cañón del Río Lobos',
                    description: 'Descubre el espectacular cañón y su ermita',
                    photo1: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=400',
                    price_per_person: 20,
                    latitude: 41.7543,
                    longitude: -3.0675
                }
            ];
            renderItems('actividades', ejemplos, 'actividad');
        }
    } catch (error) {
        console.error('Error cargando actividades:', error);
    }
}

/**
 * Cargar lugares de interés
 */
async function loadLugares() {
    try {
        const response = await fetch(API_BASE_URL + 'places.php?status=active&limit=20');
        const data = await response.json();
        
        if (data.success && data.places) {
            renderItems('lugares', data.places, 'lugar');
        } else {
            // Datos de ejemplo
            const ejemplos = [
                {
                    id: 1,
                    name: 'Yacimiento de Numancia',
                    description: 'Ruinas de la antigua ciudad celtíbera',
                    photo1: 'https://images.unsplash.com/photo-1590073242678-70ee3fc28e8e?w=400',
                    entry_fee: 5,
                    latitude: 41.8014,
                    longitude: -2.4472
                },
                {
                    id: 2,
                    name: 'Monasterio de San Juan de Duero',
                    description: 'Joya del románico con claustro único',
                    photo1: 'https://images.unsplash.com/photo-1605729925183-3b0c65abb45c?w=400',
                    entry_fee: 3,
                    latitude: 41.7636,
                    longitude: -2.4654
                }
            ];
            renderItems('lugares', ejemplos, 'lugar');
        }
    } catch (error) {
        console.error('Error cargando lugares:', error);
    }
}

/**
 * Cargar eventos culturales
 */
async function loadEventos() {
    try {
        const response = await fetch(API_BASE_URL + 'cultural-events.php?status=active&limit=20');
        const data = await response.json();
        
        if (data.success && data.events) {
            renderItems('eventos', data.events, 'evento');
        } else {
            // Datos de ejemplo
            const ejemplos = [
                {
                    id: 1,
                    title: 'Festival de Teatro',
                    description: 'Obras clásicas y contemporáneas',
                    poster_image: 'https://images.unsplash.com/photo-1503095396549-807759245b35?w=400',
                    ticket_price: 12,
                    municipality: 'Soria'
                },
                {
                    id: 2,
                    title: 'Concierto de Música Clásica',
                    description: 'Orquesta Sinfónica de Soria',
                    poster_image: 'https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?w=400',
                    ticket_price: 15,
                    municipality: 'Soria'
                }
            ];
            renderItems('eventos', ejemplos, 'evento');
        }
    } catch (error) {
        console.error('Error cargando eventos:', error);
    }
}

/**
 * Renderizar items en el grid correspondiente
 */
function renderItems(categoria, items, tipo) {
    const grid = document.getElementById(`grid-${categoria}`);
    grid.innerHTML = '';

    items.forEach(item => {
        const card = createItemCard(item, tipo, categoria);
        grid.appendChild(card);
    });
}

/**
 * Crear tarjeta de item
 */
function createItemCard(item, tipo, categoria) {
    const div = document.createElement('div');
    div.className = 'item-card';
    div.dataset.id = item.id;
    div.dataset.tipo = tipo;
    div.dataset.categoria = categoria;

    // Determinar imagen
    let imagen = item.photo1 || item.poster_image || 'https://via.placeholder.com/400x300?text=Sin+Imagen';
    
    // Determinar precio
    let precio = '';
    if (item.price_per_night) precio = `${item.price_per_night}€/noche`;
    else if (item.price_per_person) precio = `${item.price_per_person}€/persona`;
    else if (item.entry_fee) precio = `${item.entry_fee}€ entrada`;
    else if (item.ticket_price) precio = `${item.ticket_price}€`;
    else precio = 'Gratis';

    // Determinar nombre
    const nombre = item.name || item.title || 'Sin nombre';
    
    // Determinar descripción
    const descripcion = (item.description || item.short_description || '').substring(0, 80) + '...';

    div.innerHTML = `
        <img src="${imagen}" alt="${nombre}" onerror="this.src='https://via.placeholder.com/400x300?text=Sin+Imagen'">
        <h4>${nombre}</h4>
        <p>${descripcion}</p>
        <p class="price"><i class="fas fa-tag"></i> ${precio}</p>
    `;

    // Evento click para seleccionar/deseleccionar
    div.addEventListener('click', function() {
        toggleItemSelection(this, item, tipo, categoria);
    });

    return div;
}

/**
 * Alternar selección de item
 */
function toggleItemSelection(cardElement, item, tipo, categoria) {
    const itemId = item.id;
    const isSelected = cardElement.classList.contains('selected');

    if (isSelected) {
        // Deseleccionar
        cardElement.classList.remove('selected');
        selectedItems[categoria] = selectedItems[categoria].filter(i => i.id !== itemId);
        
        // Remover marcador del mapa
        removeMarkerForItem(itemId, tipo);
    } else {
        // Seleccionar
        cardElement.classList.add('selected');
        selectedItems[categoria].push({
            id: itemId,
            name: item.name || item.title,
            tipo: tipo,
            categoria: categoria,
            data: item
        });

        // Añadir marcador al mapa si tiene coordenadas
        if (item.latitude && item.longitude) {
            const latlng = L.latLng(item.latitude, item.longitude);
            addMarker(latlng, {
                name: item.name || item.title,
                type: tipo,
                price: item.price_per_night || item.price_per_person || item.entry_fee || item.ticket_price,
                id: itemId,
                categoria: categoria
            });
            
            // Ajustar vista del mapa para incluir todos los marcadores
            if (markers.length > 0) {
                const bounds = L.latLngBounds(markers.map(m => m.latlng));
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        }
    }

    updateSelectedItemsDisplay();
}

/**
 * Remover marcador del mapa para un item específico
 */
function removeMarkerForItem(itemId, tipo) {
    const markerIndex = markers.findIndex(m => 
        m.itemData && m.itemData.id === itemId && m.itemData.type === tipo
    );
    
    if (markerIndex !== -1) {
        map.removeLayer(markers[markerIndex].marker);
        markers.splice(markerIndex, 1);
    }
}

/**
 * Actualizar display de items seleccionados
 */
function updateSelectedItemsDisplay() {
    const container = document.getElementById('itemsSeleccionados');
    const list = document.getElementById('selectedItemsList');
    const count = document.getElementById('countSelected');

    // Contar total de items seleccionados
    const total = Object.values(selectedItems).reduce((sum, arr) => sum + arr.length, 0);
    count.textContent = total;

    if (total === 0) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'block';
    list.innerHTML = '';

    // Renderizar tags de items seleccionados
    Object.keys(selectedItems).forEach(categoria => {
        selectedItems[categoria].forEach(item => {
            const tag = document.createElement('div');
            tag.className = 'selected-item-tag';
            tag.innerHTML = `
                <i class="fas fa-${getIconForTipo(item.tipo)}"></i>
                <span>${item.name}</span>
                <button type="button" class="remove-btn" onclick="removeSelectedItem('${categoria}', ${item.id})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            list.appendChild(tag);
        });
    });
}

/**
 * Obtener icono para tipo de item
 */
function getIconForTipo(tipo) {
    const iconos = {
        'alojamiento': 'bed',
        'actividad': 'hiking',
        'lugar': 'landmark',
        'evento': 'calendar-alt'
    };
    return iconos[tipo] || 'map-marker';
}

/**
 * Remover item seleccionado
 */
function removeSelectedItem(categoria, itemId) {
    selectedItems[categoria] = selectedItems[categoria].filter(i => i.id !== itemId);
    
    // Actualizar tarjeta en el grid
    const card = document.querySelector(`.item-card[data-id="${itemId}"][data-categoria="${categoria}"]`);
    if (card) {
        card.classList.remove('selected');
    }

    // Remover marcador del mapa
    const item = selectedItems[categoria].find(i => i.id === itemId);
    if (item) {
        removeMarkerForItem(itemId, item.tipo);
    }

    updateSelectedItemsDisplay();
}

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Tabs
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            switchTab(tabName);
        });
    });

    // Formulario
    const form = document.getElementById('rutaForm');
    form.addEventListener('submit', handleFormSubmit);
}

/**
 * Cambiar tab activa
 */
function switchTab(tabName) {
    // Actualizar botones
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

    // Actualizar contenido
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`tab-${tabName}`).classList.add('active');
}

/**
 * Configurar file uploads
 */
function setupFileUploads() {
    // Audio
    const audioInput = document.getElementById('audioInput');
    audioInput.addEventListener('change', function(e) {
        handleFileUpload(e, 'audio');
    });

    // Video
    const videoInput = document.getElementById('videoInput');
    videoInput.addEventListener('change', function(e) {
        handleFileUpload(e, 'video');
    });
}

/**
 * Manejar file upload
 */
function handleFileUpload(event, type) {
    const file = event.target.files[0];
    if (!file) return;

    const preview = document.getElementById(`${type}Preview`);
    preview.classList.add('active');

    const fileName = file.name;
    const fileSize = (file.size / 1024 / 1024).toFixed(2);

    preview.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <i class="fas fa-${type === 'audio' ? 'music' : 'film'}" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                <strong>${fileName}</strong> (${fileSize} MB)
            </div>
            <button type="button" onclick="removeFile('${type}')" style="background: #e74c3c; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">
                Eliminar
            </button>
        </div>
    `;
}

/**
 * Remover archivo
 */
function removeFile(type) {
    const input = document.getElementById(`${type}Input`);
    const preview = document.getElementById(`${type}Preview`);
    
    input.value = '';
    preview.classList.remove('active');
    preview.innerHTML = '';
}

/**
 * Previsualizar ruta
 */
function previsualizarRuta() {
    const titulo = document.getElementById('titulo').value;
    const descripcion = document.getElementById('descripcion').value;

    if (!titulo || !descripcion) {
        alert('Por favor, completa al menos el título y la descripción de la ruta.');
        return;
    }

    // Contar items seleccionados
    const total = Object.values(selectedItems).reduce((sum, arr) => sum + arr.length, 0);

    let mensaje = `PREVISUALIZACIÓN DE RUTA\n\n`;
    mensaje += `Título: ${titulo}\n`;
    mensaje += `Descripción: ${descripcion}\n`;
    mensaje += `Elementos seleccionados: ${total}\n\n`;

    Object.keys(selectedItems).forEach(categoria => {
        if (selectedItems[categoria].length > 0) {
            mensaje += `${categoria.toUpperCase()}: ${selectedItems[categoria].length}\n`;
            selectedItems[categoria].forEach(item => {
                mensaje += `  - ${item.name}\n`;
            });
        }
    });

    alert(mensaje);
}

/**
 * Manejar envío del formulario
 */
async function handleFormSubmit(e) {
    e.preventDefault();

    // Validar
    const titulo = document.getElementById('titulo').value;
    const descripcion = document.getElementById('descripcion').value;

    if (!titulo || !descripcion) {
        alert('Por favor, completa los campos obligatorios.');
        return;
    }

    // Mostrar loading
    document.getElementById('loadingOverlay').classList.add('active');

    // Preparar datos
    const formData = new FormData();
    formData.append('title', titulo);
    formData.append('description', descripcion);
    formData.append('duration', document.getElementById('duracion').value);
    formData.append('difficulty', document.getElementById('dificultad').value);
    formData.append('estimated_price', document.getElementById('precio_estimado').value || 0);
    formData.append('youtube_url', document.getElementById('youtubeUrl').value || '');

    // Audio y video
    const audioFile = document.getElementById('audioInput').files[0];
    if (audioFile) {
        formData.append('audio', audioFile);
    }

    const videoFile = document.getElementById('videoInput').files[0];
    if (videoFile) {
        formData.append('video', videoFile);
    }

    // Items seleccionados
    formData.append('items', JSON.stringify(selectedItems));

    // Marcadores del mapa
    const mapPoints = markers.map(m => ({
        lat: m.latlng.lat,
        lng: m.latlng.lng,
        itemData: m.itemData
    }));
    formData.append('map_points', JSON.stringify(mapPoints));

    // Descuentos
    const descuentos = [];
    document.querySelectorAll('input[name="descuentos[]"]:checked').forEach(checkbox => {
        descuentos.push(checkbox.value);
    });
    formData.append('discounts', JSON.stringify(descuentos));

    try {
        const response = await fetch(API_BASE_URL + 'routes.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        document.getElementById('loadingOverlay').classList.remove('active');

        if (result.success) {
            alert('¡Ruta creada exitosamente! 🎉');
            window.location.href = 'rutas-turisticas.html';
        } else {
            alert('Error al crear la ruta: ' + (result.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('loadingOverlay').classList.remove('active');
        alert('Error al guardar la ruta. Por favor, inténtalo de nuevo.');
    }
}
