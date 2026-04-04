// JavaScript no crítico para funcionalidad completa del detalle de alojamiento
// Cargado diferidamente después del LCP

(function() {
    'use strict';

    // Función para extraer el ID del video de YouTube de una URL
    function getYouTubeVideoId(url) {
        if (!url) return null;
        
        // Patrones para diferentes formatos de URL de YouTube
        const patterns = [
            /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/v\/)([^&\?\/]+)/,
            /youtube\.com\/shorts\/([^&\?\/]+)/
        ];
        
        for (const pattern of patterns) {
            const match = url.match(pattern);
            if (match && match[1]) {
                return match[1];
            }
        }
        
        // Si no coincide con ningún patrón, verificar si es solo un ID de YouTube
        if (/^[a-zA-Z0-9_-]{11}$/.test(url)) {
            return url;
        }
        
        return null;
    }

    // Función para abrir el modal de video
    function openVideoModal(videoUrl) {
        const videoId = getYouTubeVideoId(videoUrl);
        if (!videoId) {
            console.error('No se pudo extraer el ID del video de YouTube:', videoUrl);
            return;
        }
        
        const iframe = document.getElementById('videoIframe');
        iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
        
        const modal = document.getElementById('videoModal');
        modal.classList.add('active');
        
        // Prevenir scroll del body
        document.body.style.overflow = 'hidden';
    }

    // Función para cerrar el modal de video
    function closeVideoModal() {
        const iframe = document.getElementById('videoIframe');
        iframe.src = '';
        
        const modal = document.getElementById('videoModal');
        modal.classList.remove('active');
        
        // Restaurar scroll del body
        document.body.style.overflow = '';
    }

    // Cerrar modal al hacer clic fuera del contenido
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('videoModal');
        if (e.target === modal) {
            closeVideoModal();
        }
    });

    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeVideoModal();
        }
    });

    // Nueva función para verificar eclipse usando la API y regions table
    async function checkEclipseRegion(accommodationId) {
        if (!accommodationId) return false;
        
        try {
            const response = await fetch(`/api/check_eclipse_region.php?accommodation_id=${accommodationId}`);
            const data = await response.json();
            
            if (data.success && data.data.has_eclipse) {
                return data.data;
            }
            return false;
        } catch (error) {
            console.error('Error al verificar región de eclipse:', error);
            return false;
        }
    }

    // Función para mostrar el banner del eclipse (nueva versión optimizada)
    async function showEclipseBannerOptimized(accommodationId, province) {
        // Verificar si el usuario ya cerró el banner
        const bannerClosed = localStorage.getItem('eclipse_banner_closed');
        if (bannerClosed) return;
        
        // Primero intentar con la nueva API
        const eclipseData = await checkEclipseRegion(accommodationId);
        
        if (!eclipseData) {
            // Fallback: verificar provincia (lógica antigua simplificada)
            const eclipseProvinces = [
                'A Coruña', 'La Coruña', 'Almería', 'Cádiz', 'Córdoba', 'Huelva', 'Jaén', 'Málaga', 'Sevilla',
                'Huesca', 'Teruel', 'Zaragoza', 'Asturias', 'Islas Baleares', 'Álava', 'Vizcaya', 'Guipúzcoa',
                'Cantabria', 'Albacete', 'Ciudad Real', 'Cuenca', 'Guadalajara', 'Toledo', 'Ávila', 'Burgos',
                'León', 'Palencia', 'Salamanca', 'Segovia', 'Soria', 'Valladolid', 'Zamora', 'Barcelona',
                'Girona', 'Lleida', 'Tarragona', 'Lugo', 'Ourense', 'Pontevedra', 'La Rioja', 'Madrid',
                'Navarra', 'Alicante', 'Castellón', 'Valencia'
            ];
            
            const hasEclipse = eclipseProvinces.some(p => 
                province && province.toLowerCase().includes(p.toLowerCase())
            );
            
            if (!hasEclipse) return;
        }
        
        const banner = document.createElement('div');
        banner.className = 'eclipse-banner';
        banner.id = 'eclipse-banner';
        
        // Usar datos de la API si están disponibles, sino usar datos por defecto
        const regionName = eclipseData?.region?.name || 'Zona Eclipse 2026';
        const bannerTitle = eclipseData?.region?.banner_title || '🌟 Eclipse Solar Total - 12 de Agosto 2026';
        const bannerText = eclipseData?.region?.banner_text || `${province} será uno de los mejores lugares del mundo para observar este fenómeno único`;
        const bannerIcon = eclipseData?.region?.banner_icon || '🌑';
        const eclipseDate = '12 de agosto de 2026';
        
        banner.innerHTML = `
            <button class="eclipse-banner-close" onclick="closeEclipseBanner()">
                <i class="fas fa-times"></i>
            </button>
            <div class="eclipse-banner-content">
                <div class="eclipse-banner-icon">${bannerIcon}</div>
                <div class="eclipse-banner-text">
                    <h3>${bannerTitle}</h3>
                    <p>${bannerText}</p>
                </div>
                <div class="eclipse-banner-date">
                    <i class="fas fa-calendar-alt"></i> ${eclipseDate}
                </div>
            </div>
        `;
        
        // Insertar después del header
        const header = document.querySelector('header');
        if (header && header.nextSibling) {
            header.parentNode.insertBefore(banner, header.nextSibling);
        } else {
            document.body.insertBefore(banner, document.body.firstChild);
        }
    }

    // Función para cerrar el banner (mantenida)
    function closeEclipseBanner() {
        const banner = document.getElementById('eclipse-banner');
        if (banner) {
            banner.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => banner.remove(), 300);
            // Recordar que el usuario cerró el banner
            localStorage.setItem('eclipse_banner_closed', 'true');
        }
    }

    // Función para agregar datos estructurados JSON-LD
    function addStructuredData(alojamiento) {
        // Eliminar script anterior si existe
        const existingScript = document.getElementById('structured-data');
        if (existingScript) {
            existingScript.remove();
        }

        const mainImage = (alojamiento.Fotos && alojamiento.Fotos.length > 0) ? alojamiento.Fotos[0] : 
                         (alojamiento.photo1 || alojamiento.image1 || 'https://rutasrurales.io/menu_images/Logo%20transparente.webp');

        const structuredData = {
            "@context": "https://schema.org",
            "@type": "LodgingBusiness",
            "name": alojamiento.name || alojamiento.Nombre,
            "description": alojamiento.description || alojamiento.Notaspublicas || 'Alojamiento rural',
            "image": mainImage,
            "address": {
                "@type": "PostalAddress",
                "streetAddress": alojamiento.address || alojamiento.Direccion || alojamiento.direccion || "",
                "addressLocality": alojamiento.municipality || alojamiento.Localidad,
                "addressRegion": alojamiento.province || alojamiento.Provincia,
                "postalCode": alojamiento.postal_code || alojamiento.CodigoPostal || alojamiento.codigo_postal || "",
                "addressCountry": "ES"
            },
            "priceRange": alojamiento.price_per_night ? `${alojamiento.price_per_night}€` : "Consultar",
            "telephone": alojamiento.phone || alojamiento.Telefono || "",
            "url": window.location.href
        };

        // Agregar coordenadas si existen
        if (alojamiento.latitude && alojamiento.longitude) {
            structuredData.geo = {
                "@type": "GeoCoordinates",
                "latitude": alojamiento.latitude,
                "longitude": alojamiento.longitude
            };
        }

        // Agregar sitio web si existe
        if (alojamiento.website) {
            structuredData.sameAs = [alojamiento.website];
        }

        const script = document.createElement('script');
        script.id = 'structured-data';
        script.type = 'application/ld+json';
        script.textContent = JSON.stringify(structuredData);
        document.head.appendChild(script);

        console.log('Datos estructurados agregados:', structuredData);
    }

    // Función para trackear vistas de alojamientos
    function trackAccommodationView(accommodationId) {
        if (!accommodationId) return;
        
        fetch('/api/track_resource_stat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                resource_type: 'accommodation',
                resource_id: accommodationId,
                stat_type: 'view'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Vista registrada para alojamiento:', accommodationId, data.data);
            } else {
                console.error('Error al registrar vista:', data.error);
            }
        })
        .catch(error => {
            console.error('Error al trackear vista:', error);
        });
    }

    // Función para trackear favoritos de alojamientos
    function trackAccommodationFavorite(accommodationId) {
        if (!accommodationId) return;

        fetch('/api/track_resource_stat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                resource_type: 'accommodation',
                resource_id: accommodationId,
                stat_type: 'favorite'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Favorito registrado para alojamiento:', accommodationId);
            } else {
                console.error('Error al registrar favorito:', data.error);
            }
        })
        .catch(error => {
            console.error('Error al trackear favorito:', error);
        });
    }

    // Función para alternar favorito con feedback visual
    function toggleFavorite(accommodationId, btn) {
        const isFav = btn.classList.contains('active');
        if (isFav) {
            // Quitar favorito (solo visual, no decrementamos en BD)
            btn.classList.remove('active');
            btn.innerHTML = '<i class="fas fa-heart"></i> Guardar';
            btn.style.background = 'white';
            btn.style.color = 'var(--primary-color)';
            btn.style.border = '2px solid var(--primary-color)';
            localStorage.removeItem('fav_accommodation_' + accommodationId);
        } else {
            // Añadir favorito
            btn.classList.add('active');
            btn.innerHTML = '<i class="fas fa-heart" style="color:#e74c3c;"></i> Guardado';
            btn.style.background = '#fff0f0';
            btn.style.color = '#e74c3c';
            btn.style.border = '2px solid #e74c3c';
            localStorage.setItem('fav_accommodation_' + accommodationId, '1');
            trackAccommodationFavorite(accommodationId);
        }
    }

    // Función para añadir a mi ruta personal
    function addToMyRouteFromDetail() {
        const item = window.currentAccommodation;
        if (!item) {
            alert('Error: No se pudo obtener la información del alojamiento');
            return;
        }

        let myRoute = JSON.parse(localStorage.getItem('myPersonalRoute') || '[]');

        const id = item.id || item.ID;
        const tipo = 'alojamiento';

        if (myRoute.some(i => i.id === id && i.tipo === tipo)) {
            alert('Este alojamiento ya está en tu ruta');
            return;
        }

        myRoute.push({
            id: id,
            nombre: item.name || item.Nombre,
            tipo: tipo,
            lat: item.latitude || item.latitud,
            lng: item.longitude || item.longitud,
            slug: item.slug,
            foto: (item.Fotos && item.Fotos.length > 0) ? item.Fotos[0] : (item.photo1 || item.image1),
            localidad: item.municipality || item.Localidad,
            description: (item.description || item.Notaspublicas || '').substring(0, 300) + '...'
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

    // Función para cargar contenido relacionado
    async function loadNearbyContent(alojamiento) {
        try {
            console.log('Alojamiento recibido:', alojamiento);
            
            const accommodationId = alojamiento.id || alojamiento.ID;
            const municipality = alojamiento.municipality || alojamiento.Localidad || alojamiento.localidad;
            const province = alojamiento.province || alojamiento.Provincia || alojamiento.provincia;
            
            console.log('Datos extraídos:', { accommodationId, municipality, province });
            
            if (!accommodationId && !municipality && !province) {
                console.log('No hay datos de ubicación para cargar contenido relacionado');
                return;
            }
            
            let apiUrl = 'api/get_nearby_content.php?';
            if (accommodationId) {
                apiUrl += `accommodation_id=${accommodationId}`;
            } else if (municipality || province) {
                if (municipality) apiUrl += `municipality=${encodeURIComponent(municipality)}`;
                if (province) {
                    if (municipality) apiUrl += '&';
                    apiUrl += `province=${encodeURIComponent(province)}`;
                }
            }
            
            console.log('Llamando a API:', apiUrl);
            
            const response = await fetch(apiUrl);
            const data = await response.json();
            
            console.log('Respuesta API:', data);
            
            if (data.success) {
                renderNearbyContent(data.data);
            } else {
                console.error('Error en API:', data.error);
            }
        } catch (error) {
            console.error('Error al cargar contenido relacionado:', error);
        }
    }

    // Función para renderizar contenido relacionado
    function renderNearbyContent(data) {
        const nearbyContainer = document.getElementById('nearby-content');
        
        // Renderizar lugares de interés
        if (data.places_of_interest && data.places_of_interest.length > 0) {
            renderPlaces(data.places_of_interest);
            document.getElementById('places-section').style.display = 'block';
        }
        
        // Renderizar actividades turísticas
        if (data.tourist_activities && data.tourist_activities.length > 0) {
            renderActivities(data.tourist_activities);
            document.getElementById('activities-section').style.display = 'block';
        }
        
        // Renderizar eventos culturales
        if (data.cultural_events && data.cultural_events.length > 0) {
            renderEvents(data.cultural_events);
            document.getElementById('events-section').style.display = 'block';
        }
        
        // Mostrar el contenedor si hay algo que mostrar
        if (data.places_of_interest.length > 0 || data.tourist_activities.length > 0 || data.cultural_events.length > 0) {
            nearbyContainer.style.display = 'block';
        }
    }

    // Función para renderizar lugares de interés
    function renderPlaces(places) {
        const grid = document.getElementById('places-grid');
        grid.innerHTML = places.map(place => `
            <div class="nearby-card" onclick="window.location.href='${place.slug ? `/lugar-interes.html?slug=${place.slug}` : '#lugares-de-interes'}'">
                <div class="nearby-card-image-container">
                    <img src="${place.main_photo}" alt="${place.name}" loading="lazy"
                         onerror="this.src='https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'">
                </div>
                <div class="nearby-card-content">
                    <h3>${place.name}</h3>
                    <div class="nearby-card-location">
                        <i class="fas fa-map-marker-alt"></i>
                        ${place.municipality}${place.province ? ', ' + place.province : ''}
                    </div>
                    <p class="nearby-card-description">${place.short_description || place.description || 'Descubre este lugar de interés'}</p>
                    ${place.opening_hours ? `
                        <div class="nearby-card-meta">
                            <span><i class="fas fa-clock"></i> ${place.opening_hours}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    // Función para renderizar actividades turísticas
    function renderActivities(activities) {
        const grid = document.getElementById('activities-grid');
        grid.innerHTML = activities.map(activity => `
            <div class="nearby-card" onclick="window.location.href='${activity.slug ? `/actividad.html?slug=${activity.slug}` : '#actividades-turisticas'}'">
                <div class="nearby-card-image-container">
                    <img src="${activity.main_photo}" alt="${activity.name}" loading="lazy"
                         onerror="this.src='https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'">
                </div>
                <div class="nearby-card-content">
                    <h3>${activity.name}</h3>
                    <div class="nearby-card-location">
                        <i class="fas fa-map-marker-alt"></i>
                        ${activity.municipality}${activity.province ? ', ' + activity.province : ''}
                    </div>
                    <p class="nearby-card-description">${activity.short_description || activity.description || 'Descubre esta actividad'}</p>
                    <div class="nearby-card-meta">
                        ${activity.duration ? `<span><i class="fas fa-clock"></i> ${activity.duration}</span>` : ''}
                        ${activity.difficulty_level ? `<span><i class="fas fa-chart-line"></i> ${activity.difficulty_level}</span>` : ''}
                        ${activity.price ? `<span><i class="fas fa-euro-sign"></i> ${activity.price}</span>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Función para renderizar eventos culturales
    function renderEvents(events) {
        const grid = document.getElementById('events-grid');
        if (!events || events.length === 0) {
            grid.innerHTML = '<p class="no-content-msg">No hay eventos culturales próximos en esta zona</p>';
            return;
        }
        
        grid.innerHTML = events.map(event => {
            const eventDate = event.start_date_formatted || event.event_date_formatted || 
                            (event.start_date ? formatDate(event.start_date) : '');
            const eventTime = event.event_time || '';
            // Usar title primero, luego name, si ambos vacíos mostrar 'Evento'
            const title = event.title || event.name || 'Evento sin nombre';
            const venue = event.venue || event.municipality || '';
            const province = event.province || '';
            const description = event.short_description || event.description || '';
            
            // Determinar precio correctamente según la estructura de la BD
            // is_free = 1 → Gratis, ticket_price = precio numérico
            let priceDisplay = '';
            if (event.is_free == 1) {
                priceDisplay = 'Gratis';
            } else if (event.ticket_price && parseFloat(event.ticket_price) > 0) {
                priceDisplay = parseFloat(event.ticket_price).toFixed(2).replace('.00', '') + ' EUR';
            } else if (event.ticket_price_range) {
                priceDisplay = event.ticket_price_range;
            }
            
            return `
                <div class="nearby-card" onclick="window.location.href='/evento/${event.slug || '#'}">
                    <div class="nearby-card-image-container">
                        <img src="${event.main_photo || 'https://rutasrurales.io/menu_images/evento-default.jpg'}" 
                             alt="${title}" loading="lazy"
                             onerror="this.src='https://rutasrurales.io/menu_images/evento-default.jpg'">
                    </div>
                    <div class="nearby-card-content">
                        ${eventDate ? `
                            <div class="event-date-badge">
                                <i class="fas fa-calendar"></i> ${eventDate}
                                ${eventTime ? ` - ${eventTime}` : ''}
                            </div>
                        ` : ''}
                        <h3>${title}</h3>
                        ${venue || province ? `
                            <div class="nearby-card-location">
                                <i class="fas fa-map-marker-alt"></i>
                                ${venue}${province && venue ? ', ' : ''}${province}
                            </div>
                        ` : ''}
                        ${description ? `
                            <p class="nearby-card-description">${description}</p>
                        ` : ''}
                        ${priceDisplay ? `
                            <div class="nearby-card-meta">
                                <span><i class="fas fa-tag"></i> ${priceDisplay}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    // Función auxiliar para formatear fechas
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    // Función para cargar contenido completo (llamada desde el HTML principal)
    window.loadContenidoCompleto = function(alojamiento) {
        // Agregar datos estructurados
        addStructuredData(alojamiento);
        
        // Trackear la vista del alojamiento
        const accommodationId = alojamiento.id || alojamiento.ID;
        trackAccommodationView(accommodationId);
        
        // Mostrar banner del eclipse solar usando la nueva función optimizada
        const province = alojamiento.province || alojamiento.Provincia || '';
        
        if (province && accommodationId) {
            // Llamar a la función optimizada de forma asíncrona
            showEclipseBannerOptimized(accommodationId, province);
        }
        
        // Cargar contenido relacionado
        loadNearbyContent(alojamiento);
    };

    // Exponer funciones globales necesarias
    window.openVideoModal = openVideoModal;
    window.closeVideoModal = closeVideoModal;
    window.closeEclipseBanner = closeEclipseBanner;
    window.toggleFavorite = toggleFavorite;
    window.addToMyRouteFromDetail = addToMyRouteFromDetail;

    console.log('JavaScript no crítico cargado correctamente');
})();
