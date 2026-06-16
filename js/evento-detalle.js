// ============================================================
// FUNCIONES CRÍTICAS PARA LA PÁGINA DE DETALLE DE EVENTO
// ============================================================

// Variables globales
let currentImageIndex = 0;
let currentPhotos = [];
let accommodationsData = [];
let currentCarouselIndex = 0;
let carouselItemsPerView = 3;
let currentEventSlug = '';
let currentCommentPage = 1;
let selectedRating = null;

// Función para cambiar foto en la galería
function changePhoto(dir) {
    const imgs = document.querySelectorAll('.gallery-img');
    if (imgs.length <= 1) return;
    imgs[currentImageIndex].classList.remove('active');
    currentImageIndex = (currentImageIndex + dir + imgs.length) % imgs.length;
    imgs[currentImageIndex].classList.add('active');
    updatePhotoCredit(currentImageIndex);
}

// Función para actualizar crédito de foto
function updatePhotoCredit(index) {
    const creditEl  = document.getElementById('photoCredit');
    const counterEl = document.getElementById('galleryCounter');
    if (!creditEl) return;

    const photo = currentPhotos[index];
    if (photo && photo.author) {
        const instaHtml = photo.instagram
            ? ` · <a href="https://instagram.com/${photo.instagram.replace('@','')}" target="_blank" rel="noopener"><i class="fab fa-instagram"></i> ${photo.instagram}</a>`
            : '';
        creditEl.innerHTML = `<i class="fas fa-camera"></i> <span>Foto: ${photo.author}${instaHtml}</span>`;
        creditEl.classList.add('show');
    } else {
        creditEl.innerHTML = '';
        creditEl.classList.remove('show');
    }

    if (counterEl && currentPhotos.length > 1) {
        counterEl.textContent = (index + 1) + ' / ' + currentPhotos.length;
    }
}

// Función para añadir evento a la ruta personal
function addToMyRouteFromDetail() {
    const item = window.currentEvent;
    if (!item) {
        alert('Error: No se pudo obtener la información del evento');
        return;
    }

    let myRoute = JSON.parse(localStorage.getItem('myPersonalRoute') || '[]');

    const id = item.id;
    const tipo = 'evento';

    if (myRoute.some(i => i.id === id && i.tipo === tipo)) {
        alert('Este evento ya está en tu ruta');
        return;
    }

    myRoute.push({
        id: id,
        nombre: item.titulo,
        tipo: tipo,
        lat: item.latitud || item.latitude,
        lng: item.longitud || item.longitude,
        slug: item.slug,
        foto: (item.fotos && item.fotos.length > 0) ? item.fotos[0] : 'https://rutasrurales.io/menu_images/evento-default.jpg',
        localidad: item.localidad,
        description: (item.descripcion || '').substring(0, 300) + '...'
    });

    localStorage.setItem('myPersonalRoute', JSON.stringify(myRoute));
    
    // Mostrar confirmación y opción de ir al itinerario
    if (confirm(`✅ Añadido a tu itinerario (${myRoute.length} ${myRoute.length === 1 ? 'parada' : 'paradas'})\n\n¿Quieres ver tu itinerario completo?`)) {
        window.location.href = '/mi-ruta.html';
    }
    
    const toast = document.createElement('div');
    toast.style = "position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: var(--primary-color); color: white; padding: 1rem 2rem; border-radius: 30px; z-index: 10000; box-shadow: 0 4px 15px rgba(0,0,0,0.3); font-weight: bold;";
    toast.innerHTML = `<i class="fas fa-check-circle"></i> ¡Añadido a tu ruta! <a href="/mi-ruta.html" style="color: white; text-decoration: underline; margin-left: 10px;">Ver mi ruta</a>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// Callback global para la API de Google Maps
window.initMapCallback = function() {
    window.mapsApiLoaded = true;
    if (window.pendingMapData) {
        initGoogleMap(window.pendingMapData);
    }
};

// Función para inicializar mapa de Google
function initGoogleMap(data) {
    const mapDiv = document.getElementById('google-map');
    if (!mapDiv || typeof google === 'undefined') {
        console.error('Google Maps API no disponible o div del mapa no encontrado');
        return;
    }

    // Usar mapa clásico sin mapId (más compatible)
    const map = new google.maps.Map(mapDiv, {
        zoom: 16,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControl: false,
        streetViewControl: true,
        fullscreenControl: false,
        center: { lat: 0, lng: 0 } // Centro inicial
    });

    const geocoder = new google.maps.Geocoder();
    const lat = parseFloat(data.lat);
    const lng = parseFloat(data.lng);

    const setLocation = (location) => {
        map.setCenter(location);
        map.panTo(location);
        addMarker(map, location, data.title);
    };

    // 1. Intentar con coordenadas si son válidas (evitar 0,0 que apunta a África)
    if (!isNaN(lat) && !isNaN(lng) && Math.abs(lat) > 1) {
        setLocation({ lat, lng });
    } 
    // 2. Fallback a Geocoding (Búsqueda por texto)
    else {
        // Construimos una consulta robusta: Lugar + Dirección + Soria + España
        let queryParts = [data.venue, data.address];
        if (!queryParts.some(p => p && p.toLowerCase().includes('soria'))) queryParts.push('Soria');
        queryParts.push('España');
        
        const query = queryParts.filter(p => p && p.trim().length > 1).join(', ');
        
        if (query.length > 10) { // Evitar búsquedas demasiado genéricas
            geocoder.geocode({ address: query }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    setLocation(results[0].geometry.location);
                } else {
                    // Último intento solo con la dirección
                    const fallbackQuery = (data.address || '') + ', Soria, España';
                    geocoder.geocode({ address: fallbackQuery }, (results2, status2) => {
                        if (status2 === 'OK' && results2[0]) {
                            setLocation(results2[0].geometry.location);
                        } else {
                            mapDiv.innerHTML = '<div class="map-error">No se pudo encontrar la ubicación en el mapa.</div>';
                        }
                    });
                }
            });
        } else {
            mapDiv.innerHTML = '<div class="map-error">Dirección no disponible para el mapa.</div>';
        }
    }
}

// Función para añadir marcador al mapa
function addMarker(map, position, title) {
    // Usar marcador estándar de Google Maps (más compatible)
    const marker = new google.maps.Marker({
        position: position,
        map: map,
        title: title,
        animation: google.maps.Animation.DROP
    });
    
    const infoWindow = new google.maps.InfoWindow({ 
        content: `<div style="color:var(--primary-color);font-weight:bold;padding:5px;">${title}</div>`
    });
    
    marker.addListener('click', () => {
        infoWindow.open(map, marker);
    });
    
    // Abrir infoWindow automáticamente después de un breve retraso
    setTimeout(() => {
        infoWindow.open(map, marker);
    }, 1000);
}

// ============================================================
// FUNCIONES DE COMENTARIOS
// ============================================================

// Función para establecer valoración
function setRating(value) {
    selectedRating = value;
    const stars = document.querySelectorAll('#star-rating .star-btn');
    const label = document.getElementById('star-label');
    const labels = ['', 'Malo', 'Regular', 'Bueno', 'Muy bueno', 'Excelente'];
    
    stars.forEach((star, i) => {
        star.classList.toggle('active', i < value);
    });
    
    if (label) label.textContent = labels[value] || '';
}

// Función para actualizar contador de caracteres
function updateCharCount() {
    const textarea = document.getElementById('comment-text');
    const counter = document.getElementById('char-count');
    if (!textarea || !counter) return;
    
    const len = textarea.value.length;
    counter.textContent = `${len} / 2000`;
    counter.classList.toggle('warning', len > 1800);
}

// Función para enviar comentario
async function submitComment() {
    const nameEl = document.getElementById('comment-name');
    const emailEl = document.getElementById('comment-email');
    const textEl = document.getElementById('comment-text');
    const btnEl = document.getElementById('btn-submit-comment');
    const eventId = window.currentEventIdForComments;

    if (!eventId) return;

    const name = nameEl.value.trim();
    const email = emailEl.value.trim();
    const text = textEl.value.trim();

    // Validaciones
    if (!name || name.length < 2) {
        nameEl.focus();
        nameEl.style.borderColor = '#e74c3c';
        return;
    }
    if (!text || text.length < 5) {
        textEl.focus();
        textEl.style.borderColor = '#e74c3c';
        return;
    }

    // Deshabilitar botón
    btnEl.disabled = true;
    btnEl.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Enviando...`;

    try {
        const resp = await fetch('/api/evento-comentarios.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                event_id: eventId,
                author_name: name,
                author_email: email || null,
                comment_text: text,
                rating: selectedRating
            })
        });

        const data = await resp.json();

        if (data.success) {
            // Guardar nombre en localStorage para próximas veces
            localStorage.setItem('commentAuthorName', name);
            if (email) localStorage.setItem('commentAuthorEmail', email);

            // Limpiar formulario
            textEl.value = '';
            selectedRating = null;
            document.querySelectorAll('#star-rating .star-btn').forEach(s => s.classList.remove('active'));
            document.getElementById('star-label').textContent = '';
            updateCharCount();

            // Mostrar toast
            showCommentToast('✅ ¡Comentario publicado!');

            // Recargar comentarios
            loadComments(eventId, 1);

            // Actualizar barra de stats
            loadEventStats(eventId);
        } else {
            showCommentToast('❌ ' + (data.error || 'Error al publicar'));
        }
    } catch (e) {
        console.error('Error enviando comentario:', e);
        showCommentToast('❌ Error de conexión');
    } finally {
        btnEl.disabled = false;
        btnEl.innerHTML = `<i class="fas fa-paper-plane"></i> <span>Publicar comentario</span>`;
    }
}

// Función para mostrar formulario de respuesta
function showReplyForm(parentId) {
    // Cerrar otros formularios de respuesta abiertos
    document.querySelectorAll('.reply-form-inline').forEach(f => f.remove());

    const container = document.getElementById(`reply-form-${parentId}`);
    if (!container) return;

    const savedName = localStorage.getItem('commentAuthorName') || '';

    container.innerHTML = `
        <div class="reply-form-inline">
            <input type="text" placeholder="Tu nombre *" value="${escapeHtml(savedName)}" id="reply-name-${parentId}" maxlength="100">
            <textarea placeholder="Tu respuesta... *" id="reply-text-${parentId}" maxlength="2000"></textarea>
            <div class="reply-form-actions">
                <button class="btn-cancel-reply" onclick="cancelReply(${parentId})">Cancelar</button>
                <button class="btn-send-reply" onclick="submitReply(${parentId})">
                    <i class="fas fa-reply"></i> Responder
                </button>
            </div>
        </div>
    `;

    // Focus en el textarea
    setTimeout(() => {
        const textarea = document.getElementById(`reply-text-${parentId}`);
        if (textarea) textarea.focus();
    }, 100);
}

// Función para cancelar respuesta
function cancelReply(parentId) {
    const container = document.getElementById(`reply-form-${parentId}`);
    if (container) container.innerHTML = '';
}

// Función para enviar respuesta
async function submitReply(parentId) {
    const nameEl = document.getElementById(`reply-name-${parentId}`);
    const textEl = document.getElementById(`reply-text-${parentId}`);
    const eventId = window.currentEventIdForComments;

    if (!eventId) return;

    const name = nameEl.value.trim();
    const text = textEl.value.trim();

    if (!name || name.length < 2) { nameEl.focus(); return; }
    if (!text || text.length < 5) { textEl.focus(); return; }

    try {
        const resp = await fetch('/api/evento-comentarios.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                event_id: eventId,
                author_name: name,
                comment_text: text,
                parent_id: parentId
            })
        });

        const data = await resp.json();

        if (data.success) {
            localStorage.setItem('commentAuthorName', name);
            showCommentToast('✅ ¡Respuesta publicada!');
            loadComments(eventId, currentCommentPage);
        } else {
            showCommentToast('❌ ' + (data.error || 'Error'));
        }
    } catch (e) {
        showCommentToast('❌ Error de conexión');
    }
}

// Función para mostrar toast de notificación
function showCommentToast(message) {
    // Eliminar toast anterior si existe
    const existing = document.querySelector('.comment-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = 'comment-toast';
    toast.innerHTML = message;
    document.body.appendChild(toast);

    // Animar entrada
    requestAnimationFrame(() => {
        toast.classList.add('show');
    });

    // Eliminar después de 3 segundos
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// Función para escapar HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================================
// FUNCIONES AUXILIARES
// ============================================================

// Función para formatear descripciones
function formatearDescripcion(texto) {
    if (!texto) return '<p>Sin descripción disponible.</p>';
    
    // Verificar si el texto ya contiene HTML
    const hasHtml = /<\/?[a-z][\s\S]*>/i.test(texto);
    
    if (hasHtml) {
        // Si ya tiene HTML, solo añadir emojis a palabras clave
        const busquedas = ["Horario", "Ubicación", "Entrada", "Precio", "Organiza", "Programa", "Fecha", "Lugar"];
        const emojis = ["🕐", "📍", "🎫", "💰", "👥", "📋", "📅", "🏛️"];
        
        let resultado = texto;
        busquedas.forEach((palabra, index) => {
            const regex = new RegExp(`(?<![a-zA-Z/])${palabra}(?![a-zA-Z=])`, 'gi');
            resultado = resultado.replace(regex, `${emojis[index]} ${palabra}`);
        });
        
        resultado = resultado.replace(/(?<!<br\s*\/?>)\n(?!\s*<)/g, '<br>');
        
        return resultado;
    } else {
        // Si no tiene HTML, aplicar formato completo
        const busquedas = ["Horario", "Ubicación", "Entrada", "Precio", "Organiza", "Programa", "Fecha", "Lugar"];
        const emojis = ["🕐", "📍", "🎫", "💰", "👥", "📋", "📅", "🏛️"];
        
        busquedas.forEach((palabra, index) => {
            const regex = new RegExp(`\\b${palabra}\\b`, 'gi');
            texto = texto.replace(regex, `${emojis[index]} ${palabra}`);
        });
        
        texto = texto.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        return texto.split(/\n+/).filter(p => p.trim()).map(p => `<p>${p.trim()}</p>`).join('');
    }
}

// Función para obtener nombre del mes
function getMonthName(num) {
    const lang = window.currentLang || 'es';
    const monthsByLang = {
        es: ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
        de: ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
        en: ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        fr: ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
        zh: ['', '一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
    };
    const months = monthsByLang[lang] || monthsByLang['es'];
    return months[parseInt(num)] || '';
}

// Función para cargar estadísticas del evento
async function loadEventStats(eventId) {
    try {
        const [statsResp, commentsResp] = await Promise.all([
            fetch(`/api/evento-visitas.php?event_id=${eventId}`).then(r => r.json()).catch(() => null),
            fetch(`/api/evento-comentarios.php?event_id=${eventId}&count`).then(r => r.json()).catch(() => null)
        ]);

        const views = statsResp?.data?.views_count || 0;
        const commentsTotal = commentsResp?.data?.total || 0;
        const avgRating = commentsResp?.data?.avg_rating || null;
        const ratingCount = commentsResp?.data?.rating_count || 0;

        // Insertar barra de estadísticas después del header del detalle
        const detailHeader = document.querySelector('.detail-header');
        if (detailHeader) {
            const statsBar = document.createElement('div');
            statsBar.className = 'event-stats-bar';
            
            let statsHtml = `
                <div class="stat-item">
                    <i class="fas fa-eye"></i>
                    <span><span class="stat-number">${formatNumber(views)}</span> visitas</span>
                </div>
            `;

            if (commentsTotal > 0) {
                statsHtml += `
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <i class="fas fa-comments"></i>
                        <a href="#comments-section" style="color: inherit; text-decoration: none;">
                            <span class="stat-number">${commentsTotal}</span> ${commentsTotal !== 1 ? 'comentarios' : 'comentario'}
                        </a>
                    </div>
                `;
            }

            if (avgRating && ratingCount > 0) {
                statsHtml += `
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="avg-rating-stars">${renderStarsHtml(avgRating)}</span>
                        <span>${avgRating} (${ratingCount})</span>
                    </div>
                `;
            }

            statsBar.innerHTML = statsHtml;
            detailHeader.appendChild(statsBar);
        }
    } catch (e) {
        console.error('Error cargando estadísticas:', e);
    }
}

// Función para formatear números
function formatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
}

// Función para renderizar estrellas HTML
function renderStarsHtml(rating) {
    let html = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= Math.floor(rating)) {
            html += '<i class="fas fa-star"></i>';
        } else if (i - 0.5 <= rating) {
            html += '<i class="fas fa-star-half-alt"></i>';
        } else {
            html += '<i class="fas fa-star empty-star"></i>';
        }
    }
    return html;
}

// Función para cargar comentarios
async function loadComments(eventId, page) {
    const listEl = document.getElementById('comments-list');
    const paginationEl = document.getElementById('comments-pagination');
    
    listEl.innerHTML = `<div class="comments-loading"><i class="fas fa-spinner fa-spin"></i><p>Cargando comentarios...</p></div>`;

    try {
        const resp = await fetch(`/api/evento-comentarios.php?event_id=${eventId}&page=${page}&limit=10`);
        const data = await resp.json();

        if (data.success && data.data) {
            const { comments, total, total_pages } = data.data;
            currentCommentPage = page;

            if (comments.length === 0) {
                listEl.innerHTML = `
                    <div class="comments-empty">
                        <i class="fas fa-comment-dots"></i>
                        <p>Aún no hay comentarios. ¡Sé el primero en opinar!</p>
                    </div>
                `;
                paginationEl.style.display = 'none';
                return;
            }

            listEl.innerHTML = comments.map(c => renderCommentHtml(c)).join('');

            // Paginación
            if (total_pages > 1) {
                paginationEl.style.display = 'flex';
                let pagHtml = '';
                pagHtml += `<button ${page <= 1 ? 'disabled' : ''} onclick="loadComments(${eventId}, ${page - 1})"><i class="fas fa-chevron-left"></i></button>`;
                for (let i = 1; i <= total_pages; i++) {
                    pagHtml += `<button class="${i === page ? 'active' : ''}" onclick="loadComments(${eventId}, ${i})">${i}</button>`;
                }
                pagHtml += `<button ${page >= total_pages ? 'disabled' : ''} onclick="loadComments(${eventId}, ${page + 1})"><i class="fas fa-chevron-right"></i></button>`;
                paginationEl.innerHTML = pagHtml;
            } else {
                paginationEl.style.display = 'none';
            }
        } else {
            listEl.innerHTML = `<div class="comments-empty"><i class="fas fa-comment-dots"></i><p>Aún no hay comentarios. ¡Sé el primero en opinar!</p></div>`;
            paginationEl.style.display = 'none';
        }
    } catch (e) {
        console.error('Error cargando comentarios:', e);
        listEl.innerHTML = '<div class="comments-empty"><i class="fas fa-exclamation-triangle"></i><p>Error al cargar comentarios</p></div>';
    }
}

// Función para renderizar un comentario
function renderCommentHtml(comment) {
    const initial = comment.author_name ? comment.author_name.charAt(0).toUpperCase() : '?';
    const avatarColor = comment.author_avatar || '#2F5233';
    const date = formatCommentDate(comment.created_at);
    const ratingHtml = comment.rating ? `<span class="comment-rating">${renderStarsHtml(comment.rating)}</span>` : '';

    let repliesHtml = '';
    if (comment.replies && comment.replies.length > 0) {
        repliesHtml = `<div class="comment-replies">${comment.replies.map(r => {
            const rInitial = r.author_name ? r.author_name.charAt(0).toUpperCase() : '?';
            const rColor = r.author_avatar || '#6B8E6B';
            const rDate = formatCommentDate(r.created_at);
            return `
                <div class="comment-item">
                    <div class="comment-avatar" style="background: ${rColor};">${rInitial}</div>
                    <div class="comment-body">
                        <div class="comment-header">
                            <span class="comment-author">${escapeHtml(r.author_name)}</span>
                            <span class="comment-date">${rDate}</span>
                        </div>
                        <div class="comment-text">${escapeHtml(r.comment_text)}</div>
                    </div>
                </div>
            `;
        }).join('')}</div>`;
    }

    return `
        <div class="comment-item" id="comment-${comment.id}">
            <div class="comment-avatar" style="background: ${avatarColor};">${initial}</div>
            <div class="comment-body">
                <div class="comment-header">
                    <span class="comment-author">${escapeHtml(comment.author_name)}</span>
                    ${ratingHtml}
                    <span class="comment-date">${date}</span>
                </div>
                <div class="comment-text">${escapeHtml(comment.comment_text)}</div>
                <div class="comment-actions">
                    <button class="btn-reply" onclick="showReplyForm(${comment.id})">
                        <i class="fas fa-reply"></i> Responder
                    </button>
                </div>
                <div id="reply-form-${comment.id}"></div>
                ${repliesHtml}
            </div>
        </div>
    `;
}

// Función para formatear fecha de comentario
function formatCommentDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Ahora mismo';
    if (diffMins < 60) return `Hace ${diffMins} min`;
    if (diffHours < 24) return `Hace ${diffHours}h`;
    if (diffDays < 7) return diffDays > 1 ? `Hace ${diffDays} días` : `Hace ${diffDays} día`;
    
    const lang = window.currentLang || 'es';
    const localeMap = { es: 'es-ES', de: 'de-DE', en: 'en-GB', fr: 'fr-FR', zh: 'zh-CN' };
    return date.toLocaleDateString(localeMap[lang] || 'es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
}

// Función para inicializar sección de comentarios
function initCommentsSection(eventId) {
    const section = document.getElementById('comments-section');
    if (!section) return;
    
    section.style.display = 'block';
    window.currentEventIdForComments = eventId;

    // Restaurar nombre del localStorage si existe
    const savedName = localStorage.getItem('commentAuthorName');
    const savedEmail = localStorage.getItem('commentAuthorEmail');
    if (savedName) document.getElementById('comment-name').value = savedName;
    if (savedEmail) document.getElementById('comment-email').value = savedEmail;

    // Cargar comentarios
    loadComments(eventId, 1);
}

// Función para cargar alojamientos por provincia
async function loadAccommodationsByProvince(provincia) {
    const carouselSection = document.getElementById('accommodations-carousel');
    const track = document.getElementById('carousel-track');
    const provinceNameEl = document.getElementById('carousel-province-name');
    
    if (!carouselSection || !track || !provincia) return;
    
    // Mostrar sección con indicador de carga
    carouselSection.style.display = 'block';
    track.innerHTML = '<div class="carousel-loading"><i class="fas fa-spinner"></i><br>Cargando alojamientos...</div>';
    
    // Actualizar nombre de provincia en el título
    if (provinceNameEl) {
        provinceNameEl.textContent = provincia;
    }
    
    try {
        // Llamada a la API
        const apiUrl = `/api/get_accommodations_by_province.php?provincia=${encodeURIComponent(provincia)}&limit=10`;
        const response = await fetch(apiUrl);
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            accommodationsData = data.data;
            renderCarousel();
        } else {
            carouselSection.style.display = 'none';
        }
    } catch (error) {
        console.error('Error al cargar alojamientos:', error);
        carouselSection.style.display = 'none';
    }
}

// Función para cargar carrusel de alojamientos del evento
function loadEventAccommodationsCarousel(evento) {
    const provincia = evento.provincia;
    const carouselSection = document.getElementById('accommodations-carousel');
    
    if (provincia && provincia.trim() !== '' && carouselSection) {
        carouselSection.style.display = 'block';
        // Cargar carrusel
        setTimeout(function() {
            loadAccommodationsByProvince(provincia);
        }, 800);
    }
}

// Función principal para renderizar evento
async function renderEvento(evento) {
    window.currentEvent = evento;
    document.getElementById('loading').style.display = 'none';
    const container = document.getElementById('detail-content');
    container.style.display = 'block';
    
    // Actualizar <title> con meta_title traducido
    if (evento.meta_title && evento.meta_title.trim() !== '') {
        document.title = evento.meta_title.trim();
    } else {
        document.title = evento.titulo + ' en ' + evento.localidad;
    }

    // Actualizar <meta name="description">
    const metaDesc = document.querySelector('meta[name="description"]');
    if (metaDesc) {
        if (evento.meta_description && evento.meta_description.trim() !== '') {
            metaDesc.setAttribute('content', evento.meta_description.trim());
        } else if (evento.descripcion_corta && evento.descripcion_corta.trim() !== '') {
            metaDesc.setAttribute('content', evento.descripcion_corta.trim().substring(0, 160));
        }
    }

    // Actualizar canonical URL
    const canonicalEl = document.getElementById('dynamic-canonical');
    if (canonicalEl && evento.slug) {
        const lang = window.currentLang || 'es';
        const canonicalUrl = lang !== 'es'
            ? 'https://rutasrurales.io/' + lang + '/evento/' + evento.slug
            : 'https://rutasrurales.io/evento/' + evento.slug;
        canonicalEl.setAttribute('href', canonicalUrl);
    }

    // Verificar si el evento ha finalizado
    const eventDate = new Date(evento.fecha_evento);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let finishedNoticeHtml = '';
    if (eventDate < today) {
        finishedNoticeHtml = `
            <div class="finished-notice">
                <i class="fas fa-info-circle"></i>
                <span>Este evento ya ha finalizado, <a href="https://rutasrurales.io/eventos-culturales-paginacion.html">ver los eventos actuales</a></span>
            </div>
        `;
    }

    // Fotos propias del evento
    const ownPhotos = (evento.fotos && evento.fotos.length > 0)
        ? evento.fotos.map(url => ({ url, author: null, instagram: null, community: false }))
        : [{ url: 'https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?w=800&h=600&fit=crop', author: null, instagram: null, community: false }];

    // Fotos de la comunidad
    const communityPhotos = evento.id ? await fetchCommunityPhotos(evento.id) : [];

    // Combinar fotos
    currentPhotos = [...ownPhotos, ...communityPhotos];

    const galleryHtml = `
        <div class="gallery-container" id="galleryContainer">
            ${currentPhotos.map((foto, i) => {
                const photoNum = i + 1;
                const altText = `${evento.titulo} - Foto ${photoNum} de ${currentPhotos.length} - ${evento.localidad || ''} ${evento.provincia || ''} - Evento cultural en Rutas Rurales`;
                return `<img src="${foto.url}" class="gallery-img ${i === 0 ? 'active' : ''}" alt="${altText}" loading="${i === 0 ? 'eager' : 'lazy'}" title="${evento.titulo}">`;
            }).join('')}
            ${currentPhotos.length > 1 ? `
                <div class="gallery-nav">
                    <button class="nav-btn" onclick="changePhoto(-1)"><i class="fas fa-chevron-left"></i></button>
                    <button class="nav-btn" onclick="changePhoto(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="gallery-counter" id="galleryCounter" style="display:block;">${currentPhotos.length > 1 ? '1 / ' + currentPhotos.length : ''}</div>
            ` : ''}
            <div class="photo-credit" id="photoCredit"></div>
        </div>
    `;

    const fechaParts = evento.fecha_formateada ? evento.fecha_formateada.split('/') : ['', '', ''];
    const dia = fechaParts[0];
    const mes = getMonthName(fechaParts[1]);

    const descriptionHtml = formatearDescripcion(evento.descripcion);
    const programHtml = evento.program ? `
        <div class="detail-section" style="margin-top: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Programa</h3>
            <div class="event-description">${formatearDescripcion(evento.program)}</div>
        </div>
    ` : '';

    const targetAudienceHtml = evento.target_audience ? `
        <div class="info-item">
            <i class="fas fa-users"></i>
            <span>${evento.target_audience}</span>
        </div>
    ` : '';

    const accessibilityHtml = evento.accessibility ? `
        <div class="info-item">
            <i class="fas fa-wheelchair"></i>
            <span>${evento.accessibility}</span>
        </div>
    ` : '';

    // Preparar datos para el mapa
    console.log('Coordenadas del evento:', evento.latitud, evento.longitud, '| Provincia:', evento.provincia);
    const mapData = {
        lat: evento.latitud,
        lng: evento.longitud,
        address: (evento.direccion || '') + ' ' + (evento.localidad || '') + ' ' + (evento.provincia || ''),
        venue: evento.ubicacion || '',
        title: evento.titulo
    };

    container.innerHTML = `
        <div class="detail-container">
            ${finishedNoticeHtml}
            <div class="detail-header">
                <span class="event-badge">${evento.categoria_nombre || 'Evento'}</span>
                <h1>${evento.titulo}</h1>
                <p><i class="fas fa-map-marker-alt"></i> ${evento.ubicacion || evento.localidad || ''}</p>
            </div>

            ${galleryHtml}

            <div class="detail-grid">
                <div class="main-col">
                    <div class="detail-section">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Descripción</h3>
                        <div class="event-description">${descriptionHtml}</div>
                    </div>

                    ${programHtml}
                    
                    <div class="detail-section">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Ubicación</h3>
                        <div class="google-map-container">
                            <div id="google-map"></div>
                        </div>
                        <div style="text-align: right; margin-top: 10px;">
                            <a href="https://www.google.com/maps?q=${encodeURIComponent(mapData.venue || mapData.address)}" target="_blank" style="color: var(--primary-color); text-decoration: none; font-size: 0.9rem;">
                                <i class="fas fa-external-link-alt"></i> Ver en Google Maps
                            </a>
                        </div>
                    </div>
                </div>

                <aside class="side-col">
                    <div class="event-date-box">
                        <div class="day">${dia}</div>
                        <div class="month">${mes}</div>
                        ${evento.hora_formateada ? `<div style="margin-top:10px; font-size: 1.2rem;"><i class="fas fa-clock"></i> ${evento.hora_formateada}</div>` : ''}
                    </div>

                    <div class="info-card">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">Información</h3>
                        ${evento.precio ? `<div class="info-item"><i class="fas fa-euro-sign"></i> <strong>Precio:</strong> ${evento.precio}</div>` : ''}
                        ${evento.organizador ? `<div class="info-item"><i class="fas fa-user"></i> <strong>Organiza:</strong> ${evento.organizador}</div>` : ''}
                        ${evento.telefono ? `<div class="info-item"><i class="fas fa-phone"></i> ${evento.telefono}</div>` : ''}
                        ${evento.email ? `<div class="info-item"><i class="fas fa-envelope"></i> ${evento.email}</div>` : ''}
                        ${targetAudienceHtml}
                        ${accessibilityHtml}
                        <button onclick="addToMyRouteFromDetail()" class="btn-primary" style="width: 100%; margin-top: 1rem; background: var(--accent-color); border: none; cursor: pointer; padding: 10px; border-radius: 8px; color: white; font-weight: bold;">
                            <i class="fas fa-plus-circle"></i> Añadir a mi Itinerario
                        </button>
                    </div>

                    ${(evento.provincia || evento.latitud) ? (() => {
                        const lat = parseFloat(evento.latitud);
                        const lng = parseFloat(evento.longitud);
                        const hasCoords = !isNaN(lat) && !isNaN(lng) && Math.abs(lat) > 0.1 && Math.abs(lng) > 0.1;
                        const coordParams = hasCoords ? `lat=${lat}&lng=${lng}&` : '';
                        return `<a href="/rutas.php?${coordParams}provincia=${encodeURIComponent(evento.provincia || '')}&radius=100&alojamientos=1&lugares=1&actividades=1&eventos=1" class="btn-primary" style="display: block; text-align: center; text-decoration: none; background: linear-gradient(135deg, #e67e22, #d35400); color: white; padding: 14px; border-radius: 10px; font-weight: 700; margin-top: 12px; box-shadow: 0 4px 15px rgba(230, 126, 34, 0.4); transition: all 0.3s ease;">
                            <i class="fas fa-compass"></i> 🌟 ¡No te pierdas todo lo que hay cerca!
                            <span style="display: block; font-size: 0.75rem; font-weight: 500; margin-top: 4px; opacity: 0.95;">
                                Alojamientos, actividades, lugares únicos y más eventos en 100km a la redonda
                            </span>
                        </a>`;
                    })() : ''}

                    <a href="#" id="btnPresumeFotos" target="_blank" class="action-button" style="display: block; text-decoration: none; background: linear-gradient(135deg, #E1306C, #C13584); color: white; padding: 14px; border-radius: 10px; font-weight: 700; margin-top: 16px; box-shadow: 0 4px 15px rgba(193, 53, 132, 0.4); transition: all 0.3s ease; text-align: center;">
                        <i class="fas fa-camera"></i> 📸 ¡Presume tus fotos!
                        <span style="display: block; font-size: 0.7rem; font-weight: 500; margin-top: 4px; opacity: 0.9;">
                            Te publicamos mencionando tu Instagram
                        </span>
                    </a>

                    <a href="https://rutasrurales.io/login.html?role=turista" target="_blank" class="action-button" style="display: block; text-decoration: none; background: linear-gradient(135deg, #FFD700, #FFA500); color: #333; padding: 14px; border-radius: 10px; font-weight: 700; margin-top: 12px; box-shadow: 0 4px 15px rgba(255, 165, 0, 0.4); transition: all 0.3s ease; text-align: center;">
                        <i class="fas fa-gift"></i> 🎁 Promos solo para ti
                        <span style="display: block; font-size: 0.7rem; font-weight: 500; margin-top: 4px; opacity: 0.85;">
                            Regístrate gratis y sin spam
                        </span>
                    </a>
                </aside>
            </div>
        </div>
    `;

    // Inicializar mapa
    if (window.mapsApiLoaded) {
        initGoogleMap(mapData);
    } else {
        window.pendingMapData = mapData;
    }

    // Generar JSON-LD para Schema.org
    generateEventSchema(evento);
}

// Función para generar Schema.org
function generateEventSchema(evento) {
    // Validar que tenemos los datos mínimos necesarios
    if (!evento || !evento.fecha_evento) {
        console.error('No se puede generar schema: faltan datos del evento');
        return;
    }

    // Si end_date está vacío, usar start_date como endDate
    const endDate = evento.fecha_fin || evento.fecha_evento;
    
    // Construir startDate con hora si está disponible
    let startDateTime = evento.fecha_evento;
    if (evento.hora_evento && evento.hora_evento !== '00:00:00') {
        startDateTime = evento.fecha_evento + 'T' + evento.hora_evento;
    } else {
        startDateTime = evento.fecha_evento + 'T10:00:00';
    }
    
    // Construir endDate con hora si está disponible
    let endDateTime = endDate;
    if (evento.hora_fin && evento.hora_fin !== '00:00:00') {
        endDateTime = endDate + 'T' + evento.hora_fin;
    } else if (evento.hora_evento && evento.hora_evento !== '00:00:00') {
        // Si no hay hora de fin pero sí de inicio, asumir 2 horas de duración
        const startTime = new Date(startDateTime);
        startTime.setHours(startTime.getHours() + 2);
        endDateTime = endDate + 'T' + startTime.toTimeString().substring(0, 8);
    } else {
        endDateTime = endDate + 'T18:00:00';
    }
    
    // Añadir zona horaria de España
    startDateTime += '+01:00';
    endDateTime += '+01:00';

    // Imagen principal
    const mainImage = (evento.fotos && evento.fotos.length > 0) 
        ? evento.fotos[0] 
        : 'https://rutasrurales.io/menu_images/Logo%20transparente.webp';

    // Determinar nombre de ubicación (OBLIGATORIO)
    const locationName = evento.ubicacion || evento.localidad || evento.provincia || 'Soria';
    
    // Construir el objeto Schema.org con TODOS los campos obligatorios
    const schema = {
        "@context": "https://schema.org",
        "@type": "Event",
        "name": evento.titulo || 'Evento Cultural',
        "description": evento.descripcion_corta || (evento.descripcion ? evento.descripcion.substring(0, 200) : 'Evento cultural en Soria'),
        "startDate": startDateTime,
        "endDate": endDateTime,
        "eventStatus": "https://schema.org/EventScheduled",
        "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
        "location": {
            "@type": "Place",
            "name": locationName,
            "address": {
                "@type": "PostalAddress",
                "streetAddress": evento.direccion || '',
                "addressLocality": evento.localidad || 'Soria',
                "addressRegion": evento.provincia || 'Soria',
                "addressCountry": "ES"
            }
        },
        "image": [mainImage],
        "url": evento.canonical_url || window.location.href,
        "organizer": {
            "@type": "Organization",
            "name": evento.organizador || "Organizador del evento"
        },
        "offers": {
            "@type": "Offer",
            "price": evento.precio_numerico || "0",
            "priceCurrency": "EUR",
            "availability": "https://schema.org/InStock",
            "url": evento.url_entradas || window.location.href
        }
    };

    // Añadir email y teléfono al organizador si existen
    if (evento.email) {
        schema.organizer.email = evento.email;
    }
    if (evento.telefono) {
        schema.organizer.telephone = evento.telefono;
    }

    // Añadir coordenadas si existen
    if (evento.latitud && evento.longitud && Math.abs(parseFloat(evento.latitud)) > 1) {
        schema.location.geo = {
            "@type": "GeoCoordinates",
            "latitude": parseFloat(evento.latitud),
            "longitude": parseFloat(evento.longitud)
        };
    }

    // Insertar el JSON-LD en el documento
    const schemaScript = document.getElementById('event-schema');
    if (schemaScript) {
        schemaScript.textContent = JSON.stringify(schema, null, 2);
    }
    
    console.log('Schema.org generado correctamente:', schema);
}

// Función para cargar fotos de la comunidad
async function fetchCommunityPhotos(entityId) {
    // 1. Si no hay ID o si detectamos un bot común, salimos rápido
    if (!entityId || /bot|googlebot|bingbot|crawler|spider/i.test(navigator.userAgent)) {
        return [];
    }

    try {
        const resp = await fetch(`/api/get_entity_photos.php?entity_type=cultural_events&entity_id=${entityId}`);
        const data = await resp.json();
        if (data.success && data.data.all && data.data.all.length > 0) {
            return data.data.all.map(p => ({
                url:       p.file_url || p.file_path || '',
                author:    p.author_name || ((p.first_name || '') + ' ' + (p.last_name || '')).trim() || null,
                instagram: p.author_instagram || null,
                community: true,
            })).filter(p => p.url);
        }
    } catch(e) { /* silencioso */ }
    return [];
}

// Función para trackear vistas de eventos culturales
function trackEventView(eventId) {
    if (!eventId) return;
    
    fetch('/api/track_resource_stat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            resource_type: 'event',
            resource_id: eventId,
            stat_type: 'view'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Vista registrada para evento:', eventId, data.data);
        } else {
            console.error('Error al registrar vista:', data.error);
        }
    })
    .catch(error => {
        console.error('Error al trackear vista:', error);
    });
}

// Función para obtener evento desde la API
async function fetchEvento(slug, lang) {
    try {
        // Pasar el idioma a la API para que devuelva la traducción correcta
        const langParam = lang && lang !== 'es' ? '&lang=' + lang : '';
        const response = await fetch('/api/evento-detalle.php?slug=' + encodeURIComponent(slug) + langParam);
        const data = await response.json();
        if (data.success && data.data) {
            renderEvento(data.data);
            // Trackear la vista del evento
            trackEventView(data.data.id);
        } else {
            throw new Error('Evento no encontrado');
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('loading').style.display = 'none';
        document.getElementById('error-msg').style.display = 'block';
    }
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    let slug = urlParams.get('slug');
    // Leer idioma: puede venir como ?lang=de o desde la ruta /{lang}/evento/{slug}
    let lang = urlParams.get('lang') || 'es';
    
    if (!slug) {
        const path = window.location.pathname;
        // Detectar slug desde /evento/slug o /de/evento/slug
        const match = path.match(/\/(?:[a-z]{2}\/)?evento\/([^\/]+)/);
        if (match) slug = match[1];
        // Detectar idioma desde la ruta si no vino por query string
        if (!urlParams.get('lang')) {
            const langMatch = path.match(/^\/([a-z]{2})\/evento\//);
            if (langMatch) lang = langMatch[1];
        }
    }

    if (!slug) {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('error-msg').style.display = 'block';
        return;
    }

    // Guardar idioma globalmente para usarlo en el resto de la página
    window.currentLang = lang;

    // Aplicar idioma al HTML
    document.documentElement.lang = lang;

    fetchEvento(slug, lang);
});

// Función para aplicar traducciones estáticas
function applyStaticTranslations(lang) {
    window.currentLang = lang;

    // Loading
    const loadingEl = document.querySelector('#loading p');
    if (loadingEl) loadingEl.textContent = 'Cargando detalles del evento...';

    // Error
    const errorTitle = document.querySelector('#error-msg h2');
    if (errorTitle) errorTitle.textContent = 'No pudimos encontrar este evento';
    const errorBtn = document.querySelector('#error-msg a');
    if (errorBtn) errorBtn.textContent = 'Ver todos los eventos';

    // Header nav
    const navLinks = document.querySelectorAll('.nav-row li a span');
    const navKeys = ['Alojamientos', 'Lugares', 'Eventos', 'Actividades', 'Inicio', 'Rutas', 'Acceso'];
    navLinks.forEach((el, i) => {
        if (navKeys[i]) el.textContent = navKeys[i];
    });

    // Carrusel header
    const carouselTitle = document.querySelector('.accommodations-carousel-header h3');
    if (carouselTitle) carouselTitle.innerHTML = `<i class="fas fa-bed"></i> ¿Ya tienes dónde dormir?`;
    const carouselDesc = document.querySelector('.accommodations-carousel-header p');
    if (carouselDesc) {
        const span = carouselDesc.querySelector('span');
        const provinceName = span ? span.textContent : '';
        carouselDesc.innerHTML = `No dejes tu reserva para última hora. Encuentra el sitio perfecto para descansar después del evento. <span id="carousel-province-name">${provinceName}</span>`;
    }

    // Eclipse banner
    const eclipseTitle = document.querySelector('#eclipse-accommodations-banner h3');
    if (eclipseTitle) eclipseTitle.innerHTML = `<i class="fas fa-moon" style="color: #f4d03f;"></i> ¡Prepárate para el Eclipse Solar 2026!`;
    const eclipseDesc = document.querySelector('#eclipse-accommodations-banner p');
    if (eclipseDesc) eclipseDesc.textContent = 'Descubre los mejores alojamientos en zonas con cielo oscuro para observar el eclipse';
    const eclipseBtn = document.getElementById('eclipse-accommodations-link');
    if (eclipseBtn) eclipseBtn.innerHTML = `<i class="fas fa-bed"></i> Ver Alojamientos para el Eclipse`;

    // Footer
    const footerP = document.querySelector('.footer p');
    if (footerP) footerP.innerHTML = `&copy; 2026 rutasrurales.io. Todos los derechos reservados.`;
}

// Función para renderizar carrusel
function renderCarousel() {
    const track = document.getElementById('carousel-track');
    const dotsContainer = document.getElementById('carousel-dots');
    const prevBtn = document.getElementById('carousel-prev');
    const nextBtn = document.getElementById('carousel-next');
    
    if (!track || accommodationsData.length === 0) return;
    
    // Calcular elementos visibles según el ancho de pantalla
    updateCarouselSettings();
    
    // Generar HTML de las tarjetas
    const eventoTitulo = window.currentEvent ? window.currentEvent.titulo : '';
    track.innerHTML = accommodationsData.map((acc, index) => createAccommodationCard(acc, index, eventoTitulo)).join('');
    
    // Generar puntos de navegación
    const totalDots = Math.ceil(accommodationsData.length / carouselItemsPerView);
    dotsContainer.innerHTML = '';
    for (let i = 0; i < totalDots; i++) {
        const dot = document.createElement('button');
        dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
        dot.setAttribute('aria-label', `Ir a grupo ${i + 1}`);
        dot.onclick = () => goToSlide(i);
        dotsContainer.appendChild(dot);
    }
    
    // Configurar botones de navegación
    prevBtn.onclick = () => navigateCarousel(-1);
    nextBtn.onclick = () => navigateCarousel(1);
    
    // Actualizar estado de botones
    updateCarouselButtons();
    
    // Aplicar lazy loading a las imágenes
    applyLazyLoading();
}

// Función para crear tarjeta de alojamiento
function createAccommodationCard(accommodation, index, eventoTitulo) {
    const tipo = accommodation.tipo || accommodation.Tipo || 'Casa Rural';
    const nombre = accommodation.nombre || accommodation.Nombre || 'Alojamiento';
    const localidad = accommodation.localidad || accommodation.Localidad || '';
    const provincia = accommodation.provincia || accommodation.Provincia || '';
    const precio = accommodation.precio || accommodation.Precio || 0;
    const caracteristicas = accommodation.caracteristicas || accommodation.Caracteristicas || [];
    
    // Obtener la foto principal
    let foto = '';
    if (accommodation.foto) {
        foto = accommodation.foto;
    } else if (accommodation.fotos && Array.isArray(accommodation.fotos) && accommodation.fotos.length > 0) {
        foto = accommodation.fotos[0];
    } else if (accommodation.Foto1) {
        foto = accommodation.Foto1;
    } else if (accommodation.foto1) {
        foto = accommodation.foto1;
    }
    
    // Si no hay foto, usar imagen por defecto
    if (!foto) {
        foto = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop';
    }
    
    // Generar badge de tipo
    let tipoBadge = tipo;
    if (tipo.toLowerCase().includes('casa')) tipoBadge = 'Casa Rural';
    else if (tipo.toLowerCase().includes('piso') || tipo.toLowerCase().includes('apartamento')) tipoBadge = 'Apartamento';
    else if (tipo.toLowerCase().includes('hotel')) tipoBadge = 'Hotel';
    else if (tipo.toLowerCase().includes('hostal')) tipoBadge = 'Hostal';
    
    // Generar precio
    const precioHTML = precio > 0 
        ? `<span class="accommodation-price">${precio}€ <span>/noche</span></span>`
        : `<span class="accommodation-price"><span>Consultar</span></span>`;
    
    // Generar características (máximo 3)
    const featuresHTML = caracteristicas.slice(0, 3).map(f => 
        `<span class="accommodation-feature">${f}</span>`
    ).join('');
    
    // Generar enlace al detalle del alojamiento
    let slug = accommodation.slug || accommodation.Slug || '';
    if (slug.length > 80 || slug.includes('alojamiento') || slug.includes('undefined')) {
        slug = '';
    }
    if (!slug && (accommodation.nombre || accommodation.name || accommodation.Nombre)) {
        const nombre = accommodation.nombre || accommodation.name || accommodation.Nombre;
        slug = nombre.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/ñ/g, 'n')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }
    const accommodationLink = slug 
        ? `/alojamiento/${slug}` 
        : `/alojamientos-turisticos.html`;
    
    // SEO: Alt dinámico
    let altText;
    if (eventoTitulo) {
        altText = `Alojamiento rural ${nombre} cerca de ${eventoTitulo}`;
    } else {
        altText = `Alojamiento rural ${nombre} en ${localidad}${provincia ? ', ' + provincia : ''}`;
    }
    
    return `
        <a href="${accommodationLink}" class="accommodation-card" data-index="${index}">
            <div class="accommodation-card-image">
                <span class="accommodation-type-badge">${tipoBadge}</span>
                <img data-src="${foto}" alt="${altText}" class="lazy-img">
                <noscript><img src="${foto}" alt="${altText}" class="lazy-img-noscript"></noscript>
                <div class="image-placeholder"></div>
            </div>
            <div class="accommodation-card-content">
                <h4>${nombre}</h4>
                <p class="accommodation-location">
                    <i class="fas fa-map-marker-alt"></i>
                    ${localidad}${localidad && provincia ? ', ' : ''}${provincia}
                </p>
                <div class="accommodation-features">${featuresHTML}</div>
                ${precioHTML}
            </div>
        </a>
    `;
}

// Función para aplicar lazy loading a las imágenes
function applyLazyLoading() {
    const images = document.querySelectorAll('.lazy-img');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.getAttribute('data-src');
                    if (src) {
                        img.src = src;
                        img.onload = () => {
                            img.classList.add('loaded');
                            // Ocultar placeholder
                            const placeholder = img.parentElement.querySelector('.image-placeholder');
                            if (placeholder) placeholder.style.display = 'none';
                        };
                        img.onerror = () => {
                            // Imagen por defecto en caso de error
                            img.src = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop';
                            img.classList.add('loaded');
                        };
                    }
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.1
        });
        
        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback para navegadores sin IntersectionObserver
        images.forEach(img => {
            const src = img.getAttribute('data-src');
            if (src) {
                img.src = src;
                img.classList.add('loaded');
            }
        });
    }
}

// Función para actualizar configuración según ancho de pantalla
function updateCarouselSettings() {
    const width = window.innerWidth;
    if (width < 600) {
        carouselItemsPerView = 1;
    } else if (width < 900) {
        carouselItemsPerView = 2;
    } else {
        carouselItemsPerView = 3;
    }
}

// Función para navegar entre slides
function navigateCarousel(direction) {
    const maxIndex = Math.ceil(accommodationsData.length / carouselItemsPerView) - 1;
    currentCarouselIndex += direction;
    
    if (currentCarouselIndex < 0) currentCarouselIndex = 0;
    if (currentCarouselIndex > maxIndex) currentCarouselIndex = maxIndex;
    
    updateCarouselPosition();
}

// Función para ir a un slide específico
function goToSlide(index) {
    currentCarouselIndex = index;
    updateCarouselPosition();
}

// Función para actualizar la posición del carrusel
function updateCarouselPosition() {
    const track = document.getElementById('carousel-track');
    const cards = track.querySelectorAll('.accommodation-card');
    
    if (cards.length === 0) return;
    
    const cardWidth = cards[0].offsetWidth + 24; // Ancho + gap
    const offset = currentCarouselIndex * cardWidth * carouselItemsPerView;
    track.style.transform = `translateX(-${offset}px)`;
    
    // Actualizar botones
    updateCarouselButtons();
    
    // Actualizar puntos
    updateCarouselDots();
}

// Función para actualizar estado de botones
function updateCarouselButtons() {
    const prevBtn = document.getElementById('carousel-prev');
    const nextBtn = document.getElementById('carousel-next');
    const maxIndex = Math.ceil(accommodationsData.length / carouselItemsPerView) - 1;
    
    if (prevBtn) prevBtn.disabled = currentCarouselIndex === 0;
    if (nextBtn) nextBtn.disabled = currentCarouselIndex >= maxIndex;
}

// Función para actualizar puntos de navegación
function updateCarouselDots() {
    const dots = document.querySelectorAll('.carousel-dot');
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentCarouselIndex);
    });
}

// Función para verificar si es evento del eclipse solar
function isEclipseEvent(slug, titulo) {
    const textToCheck = (slug + ' ' + (titulo || '')).toLowerCase();
    const keywords = ['eclipse-solar', 'eclipse-solar-total', 'eclipse-agosto-2026', 'eclipse', 'solar'];
    return keywords.some(keyword => 
        textToCheck.includes(keyword.toLowerCase())
    );
}

// Función para cargar alojamientos del eclipse solar
async function loadEclipseAccommodations() {
    const carouselSection = document.getElementById('accommodations-carousel');
    const track = document.getElementById('carousel-track');
    const provinceNameEl = document.getElementById('carousel-province-name');
    const headerEl = document.querySelector('.accommodations-carousel-header h3');
    
    if (!carouselSection || !track) return;
    
    // Actualizar título para el eclipse
    if (provinceNameEl) {
        provinceNameEl.textContent = 'las mejores zonas de España para ver el eclipse';
    }
    if (headerEl) {
        headerEl.innerHTML = '<i class="fas fa-star"></i> Mejores Alojamientos para Ver el Eclipse Solar 2026';
    }
    
    carouselSection.style.display = 'block';
    track.innerHTML = '<div class="carousel-loading"><i class="fas fa-spinner"></i><br>Buscando zonas con cielo oscuro...</div>';
    
    // Provincias principales con eclipse total
    const provincesToLoad = ['Soria', 'León', 'Zamora', 'Ávila', 'Burgos'];
    
    try {
        // Cargar todas las provincias en PARALELO
        const results = await Promise.all(
            provincesToLoad.map(prov => 
                fetch(`/api/get_accommodations_by_province.php?provincia=${encodeURIComponent(prov)}&limit=5`)
                    .then(res => res.json())
                    .catch(() => ({ success: false, data: [] }))
            )
        );
        
        let allAccommodations = [];
        results.forEach(data => {
            if (data.success && data.data) {
                allAccommodations = [...allAccommodations, ...data.data];
            }
        });
        
        if (allAccommodations.length > 0) {
            // Filtrar: excluir capitales de provincia (contaminación lumínica)
            const filteredAccommodations = allAccommodations.filter(acc => {
                const localidad = acc.localidad || acc.Localidad || '';
                const provincia = acc.provincia || acc.Provincia || '';
                return !isCapitalCity(localidad, provincia);
            });
            
            // Tomar hasta 10 resultados aleatorios
            accommodationsData = filteredAccommodations
                .sort(() => Math.random() - 0.5)
                .slice(0, 10);
            
            // Marcar que es el carrusel del eclipse
            window.isEclipseCarousel = true;
            
            renderCarousel();
        } else {
            track.innerHTML = '<div class="carousel-empty"><i class="fas fa-bed"></i><br>No se encontraron alojamientos</div>';
        }
    } catch (error) {
        console.error('Error cargando alojamientos del eclipse:', error);
        track.innerHTML = '<div class="carousel-empty"><i class="fas fa-exclamation-triangle"></i><br>Error al cargar alojamientos</div>';
    }
}

// Función para verificar si la localidad es una capital de provincia
function isCapitalCity(localidad, provincia) {
    if (!localidad) return false;
    const loc = localidad.toLowerCase().trim();
    const prov = (provincia || '').toLowerCase().trim();
    
    // Lista de capitales a excluir
    const excludeCapitals = [
        'A Coruña', 'La Coruña', 'Almería', 'Cádiz', 'Córdoba', 'Huelva', 'Jaén', 'Málaga', 'Sevilla',
        'Huesca', 'Teruel', 'Zaragoza',
        'Oviedo', 'Gijón',
        'Palma', 'Palma de Mallorca',
        'Bilbao', 'San Sebastián', 'Vitoria', 'Gasteiz',
        'Santander',
        'Albacete', 'Ciudad Real', 'Cuenca', 'Guadalajara', 'Toledo',
        'Ávila', 'Burgos', 'León', 'Palencia', 'Salamanca', 'Segovia', 'Soria', 'Valladolid', 'Zamora',
        'Barcelona', 'Girona', 'Lleida', 'Tarragona',
        'Lugo', 'Ourense', 'Pontevedra', 'Santiago de Compostela',
        'Logroño',
        'Madrid', 'Alcalá de Henares',
        'Pamplona', ' Tudela', 'Barañain',
        'Alicante', 'Castellón', 'Valencia'
    ];
    
    // Verificar contra lista de capitales
    const isCapital = excludeCapitals.some(capital => 
        loc.includes(capital.toLowerCase()) || capital.toLowerCase().includes(loc)
    );
    
    // También excluir si la localidad es la misma que la provincia (indica capital)
    if (loc === prov) return true;
    
    return isCapital;
}

// Función para obtener una keyword SEO aleatoria para el eclipse
function getRandomEclipseSEO() {
    const keywords = [
        'eclipse solar 2026', 'ver eclipse solar', 'eclipse total', 'eclipse solar España',
        'observatorio astronomico', 'cielo oscuro', 'turismo astronomico', 'eclipse solar Castilla',
        'eclipse solar Galicia', 'eclipse solar Pais Vasco', 'eclipse solar Asturias'
    ];
    return keywords[Math.floor(Math.random() * keywords.length)];
}

// Función para cargar el carrusel después de renderizar el evento
function loadEventAccommodationsCarousel(evento) {
    // Verificar si es el evento del eclipse solar
    const slug = currentEventSlug || '';
    const titulo = evento.titulo || '';
    
    console.log('DEBUG loadEventAccommodationsCarousel - slug:', slug, 'titulo:', titulo);
    
    if (isEclipseEvent(slug, titulo)) {
        console.log('DEBUG: Detectado evento de Eclipse Solar, mostrando banner...');
        
        // Mostrar banner simple del eclipse
        const banner = document.getElementById('eclipse-accommodations-banner');
        if (banner) {
            banner.style.display = 'block';
        }
        return;
    }
    
    // Para eventos normales, mostrar carrusel por provincia
    const provincia = evento.provincia;
    const carouselSection = document.getElementById('accommodations-carousel');
    
    if (provincia && provincia.trim() !== '' && carouselSection) {
        carouselSection.style.display = 'block';
        // Cargar carrusel
        setTimeout(function() {
            loadAccommodationsByProvince(provincia);
        }, 800);
    }
}

// Modificar la función renderEvento para cargar el carrusel
const originalRenderEvento = renderEvento;
renderEvento = async function(evento) {
    // Ejecutar renderizado original
    await originalRenderEvento.call(this, evento);
    
    // Después de renderizar, cargar el carrusel de alojamientos
    loadEventAccommodationsCarousel(evento);
    
    // Configurar el botón de "Presume tus fotos" con los datos del evento
    const btnPresumeFotos = document.getElementById('btnPresumeFotos');
    if (btnPresumeFotos && evento.id && evento.titulo) {
        const fotosUrl = 'https://rutasrurales.io/gestion-fotos-universal.html?' +
            'type=cultural_events' +
            '&entity_id=' + encodeURIComponent(evento.id) +
            '&entity_name=' + encodeURIComponent(evento.titulo) +
            '&municipality=' + encodeURIComponent(evento.localidad || '') +
            '&province=' + encodeURIComponent(evento.provincia || '');
        btnPresumeFotos.href = fotosUrl;
    }
    
    // Cargar estadísticas de visitas y mostrar barra
    if (evento.id) {
        loadEventStats(evento.id);
        initCommentsSection(evento.id);
    }
};

// Reemplazar la función original
window.renderEvento = renderEvento;

// Inicializar crédito de la primera foto cuando el DOM esté listo
document.addEventListener('click', function initFirstCredit(e) {
    // Esperar a que la galería esté en el DOM
    const creditEl = document.getElementById('photoCredit');
    if (creditEl && currentPhotos.length > 0) {
        updatePhotoCredit(0);
        document.removeEventListener('click', initFirstCredit);
    }
}, { once: false });

// También inicializar tras renderizar (usando MutationObserver)
const _creditObserver = new MutationObserver(() => {
    const creditEl = document.getElementById('photoCredit');
    if (creditEl && currentPhotos.length > 0) {
        updatePhotoCredit(0);
        _creditObserver.disconnect();
    }
});
_creditObserver.observe(document.body, { childList: true, subtree: true });

// Resetear borde rojo al escribir
document.addEventListener('input', function(e) {
    if (e.target.id === 'comment-name' || e.target.id === 'comment-text') {
        e.target.style.borderColor = '#ddd';
    }
});

// Obtener slug de la URL al cargar
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    currentEventSlug = urlParams.get('slug') || '';
    
    if (!currentEventSlug) {
        const path = window.location.pathname;
        const match = path.match(/\/evento\/([^\/]+)/);
        if (match) currentEventSlug = match[1];
    }
    
    console.log('DEBUG: Slug detectado:', currentEventSlug);
});
