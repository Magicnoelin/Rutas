let todosLosAlojamientos = [];
let alojamientosFiltrados = [];
let categoriaActual = ''; // Mantener si se usa en otras partes, aunque no en este flujo principal
let currentPage = 1; // Para paginación, si se implementa
const itemsPerPage = 20; // Para paginación, si se implementa

// Función para cambiar la foto del carrusel (copiada del HTML original)
function cambiarFoto(carouselId, direction) {
    const carousel = document.getElementById(carouselId);
    if (!carousel) return;

    const slides = carousel.querySelector('.carousel-slides');
    const images = slides.querySelectorAll('img');
    const indicators = carousel.querySelectorAll('.indicator');

    if (images.length <= 1) return;

    const currentTransform = slides.style.transform || 'translateX(0%)';
    const transformMatch = currentTransform.match(/translateX\((-?\d+)%\)/);
    const currentOffset = transformMatch ? parseInt(transformMatch[1]) : 0;
    const currentIndex = Math.abs(currentOffset) / 100;

    let newIndex = currentIndex + direction;
    if (newIndex < 0) newIndex = images.length - 1;
    if (newIndex >= images.length) newIndex = 0;

    slides.style.transform = `translateX(-${newIndex * 100}%)`;

    indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === newIndex);
    });
}

// Función para ir a una foto específica del carrusel (copiada del HTML original)
function irAFoto(carouselId, index) {
    const carousel = document.getElementById(carouselId);
    if (!carousel) return;

    const slides = carousel.querySelector('.carousel-slides');
    const indicators = carousel.querySelectorAll('.indicator');

    slides.style.transform = `translateX(-${index * 100}%)`;

    indicators.forEach((indicator, i) => {
        indicator.classList.toggle('active', i === index);
    });
}

// Función para generar slugs (copiada del HTML original)
function generarSlug(texto) {
    if (!texto) return '';
    return texto
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/ñ/g, 'n')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

// Función para crear una tarjeta de alojamiento (adaptada del HTML original)
// Esta función se usará cuando el JS cargue nuevos alojamientos (ej. al filtrar)
function crearTarjetaAlojamiento(alojamiento) {
    const card = document.createElement('div');
    card.className = 'card';

    // Nombre y ubicación
    const nombre = alojamiento.name || alojamiento.Nombre || 'Alojamiento sin nombre';
    const localidad = alojamiento.municipality || alojamiento.Localidad || 'Sin localidad';
    const province = alojamiento.province || alojamiento.Provincia || 'Soria';
    const registrationNumber = alojamiento.registration_number || '';
    const capacity = alojamiento.capacity || alojamiento.Plazas || alojamiento.plazas || 1;
    const type = alojamiento.accommodation_type || alojamiento.Tipo || alojamiento.type || 'Sin tipo';
    const slug = alojamiento.slug || alojamiento.Slug || generarSlug(nombre);
    const url = `/alojamiento/${slug}`;

    // Recopilar todas las fotos disponibles
    let fotos = [];
    if (alojamiento.main_image) {
        fotos.push(alojamiento.main_image);
    }
    // Si tu API devuelve un array de fotos, lo procesarías aquí
    // if (alojamiento.photos && Array.isArray(alojamiento.photos) && alojamiento.photos.length > 0) {
    //     fotos = alojamiento.photos;
    // } else if (alojamiento.Foto1) { // Fallback para estructura antigua
    //     if (alojamiento.Foto1) fotos.push(alojamiento.Foto1);
    //     if (alojamiento.Foto2) fotos.push(alojamiento.Foto2);
    //     if (alojamiento.Foto3) fotos.push(alojamiento.Foto3);
    //     if (alojamiento.Foto4) fotos.push(alojamiento.Foto4);
    // }

    if (fotos.length === 0) {
        fotos = ['/menu_images/image_not_found.webp'];
    }

    const carouselId = `carousel-${alojamiento.id || Math.random().toString(36).substr(2, 9)}`;
    let carouselHTML = `
        <div class="photo-carousel" id="${carouselId}" style="position: relative; height: 250px; overflow: hidden; border-radius: 15px 15px 0 0;">
            <div class="carousel-slides" style="display: flex; height: 100%; transition: transform 0.3s ease;">
    `;

    fotos.forEach((foto, index) => {
        carouselHTML += `
            <img src="${foto}" alt="Foto ${index + 1} de ${nombre}" class="card-image" loading="lazy" decoding="async" width="400" height="250"
                 onerror="this.src='/menu_images/image_not_found.webp'" style="width: 100%; height: 100%; object-fit: cover; flex-shrink: 0;">
        `;
    });

    carouselHTML += `</div>`;

    if (fotos.length > 1) { // Solo añadir controles si hay más de una foto
        carouselHTML += `
            <button class="carousel-prev" onclick="cambiarFoto('${carouselId}', -1)" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="carousel-next" onclick="cambiarFoto('${carouselId}', 1)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-chevron-right"></i>
            </button>
            <div class="carousel-indicators" style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: flex; gap: 5px;">
        `;

        fotos.forEach((_, index) => {
            carouselHTML += `
                <span class="indicator ${index === 0 ? 'active' : ''}" onclick="irAFoto('${carouselId}', ${index})" style="width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.5); cursor: pointer; transition: background 0.3s;"></span>
            `;
        });

        carouselHTML += `</div>`;
    }

    carouselHTML += `</div>`;

    const caracteristicas = alojamiento.caracteristicas || alojamiento.Caracteristicas || [];
    const caracteristicasHTML = Array.isArray(caracteristicas) && caracteristicas.length > 0
        ? caracteristicas.slice(0, 3).map(c => `<span><i class="fas fa-check"></i> ${c}</span>`).join('')
        : '';

    const price_per_night = alojamiento.price_per_night || alojamiento.Precio || 0;
    const precioHTML = price_per_night > 0
        ? `<p class="price">Desde ${price_per_night}€/noche</p>`
        : `<p class="price">Consultar precio</p>`;

    let botonesHTML = `<a href="${url}" class="btn-primary" style="margin-right: 0.5rem;"><i class="fas fa-eye"></i> Ver detalle</a>`;
    const web = alojamiento.website || alojamiento.Web;
    const telefono = alojamiento.phone || alojamiento.Telefono1 || alojamiento.Telefono;

    if (web) {
        botonesHTML += `<a href="${web}" target="_blank" class="btn-secondary" style="margin-right: 0.5rem;"><i class="fas fa-globe"></i> Web</a>`;
    }
    if (telefono) {
        botonesHTML += `<a href="tel:${telefono}" class="btn-secondary"><i class="fas fa-phone"></i> Llamar</a>`;
    }

    card.innerHTML = `
        ${carouselHTML}
        <div class="card-content">
            <h3>${nombre}</h3>
            ${registrationNumber ? `<p style="margin: 0.5rem 0; color: var(--primary-color); font-weight: 500;">Nº Registro: ${registrationNumber}</p>` : ''}
            <p class="location"><i class="fas fa-map-marker-alt"></i> ${localidad}, ${province}</p>
            <div class="card-features">
                <span><i class="fas fa-users"></i> ${capacity} plazas</span>
                <span><i class="fas fa-home"></i> ${type}</span>
                ${caracteristicasHTML}
            </div>
            ${precioHTML}
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem;">
                ${botonesHTML}
            </div>
        </div>
    `;

    return card;
}

// Función para obtener alojamientos de la API (cuando se usan filtros)
async function fetchAccommodations(page = 1) {
    const provincia = document.getElementById('filterProvincia').value;
    const localidad = document.getElementById('filterLocalidad').value;
    const tipo = document.getElementById('filterTipo').value;
    const plazasMin = parseInt(document.getElementById('filterPlazas').value) || 1;

    document.getElementById('loading').style.display = 'block';
    document.getElementById('alojamientosGrid').style.display = 'none';
    document.getElementById('noResults').style.display = 'none';

    try {
        let apiUrl = `/api/alojamientos.php?page=${page}&limit=${itemsPerPage}`;
        if (provincia) apiUrl += `&provincia=${encodeURIComponent(provincia)}`;
        if (localidad) apiUrl += `&localidad=${encodeURIComponent(localidad)}`;
        if (tipo) apiUrl += `&tipo=${encodeURIComponent(tipo)}`;
        if (plazasMin) apiUrl += `&plazas_min=${encodeURIComponent(plazasMin)}`;

        const response = await fetch(apiUrl);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();

        if (data.success && data.data && data.data.alojamientos) {
            alojamientosFiltrados = data.data.alojamientos;
            // Aquí podrías actualizar `todosLosAlojamientos` si la API devuelve todos los datos filtrados
            // o si quieres que los filtros posteriores trabajen sobre este subconjunto.
            // Por ahora, `todosLosAlojamientos` se mantiene como el dataset completo inicial.
            mostrarAlojamientos(data.data.total_results); // Pasar el total para las estadísticas
        } else {
            alojamientosFiltrados = [];
            mostrarAlojamientos(0);
        }
    } catch (error) {
        console.error('Error al cargar alojamientos desde la API:', error);
        document.getElementById('statsText').textContent = 'Error al cargar alojamientos.';
        document.getElementById('alojamientosGrid').innerHTML = '<p class="error-message">No se pudieron cargar los alojamientos. Inténtalo de nuevo más tarde.</p>';
        document.getElementById('alojamientosGrid').style.display = 'block';
    } finally {
        document.getElementById('loading').style.display = 'none';
    }
}

// Función para mostrar los alojamientos en la cuadrícula
function mostrarAlojamientos(totalResults = alojamientosFiltrados.length) {
    const grid = document.getElementById('alojamientosGrid');
    const noResults = document.getElementById('noResults');

    grid.innerHTML = '';

    if (alojamientosFiltrados.length === 0) {
        grid.style.display = 'none';
        noResults.style.display = 'block';
    } else {
        grid.style.display = 'grid';
        noResults.style.display = 'none';
        alojamientosFiltrados.forEach(alojamiento => {
            const card = crearTarjetaAlojamiento(alojamiento);
            grid.appendChild(card);
        });
    }
    actualizarEstadisticas(alojamientosFiltrados.length, totalResults);
    // Aquí iría la lógica de paginación si se implementa
}

// Función para actualizar las estadísticas
function actualizarEstadisticas(currentCount, totalCount) {
    const statsText = document.getElementById('statsText');
    if (statsText) {
        const plazasTotales = alojamientosFiltrados.reduce((sum, a) => {
            const plazas = a.capacity || a.Plazas || a.plazas || 0;
            return sum + plazas;
        }, 0);
        statsText.innerHTML = `
            <i class="fas fa-hotel"></i> ${currentCount} alojamiento${currentCount !== 1 ? 's' : ''} turístico${currentCount !== 1 ? 's' : ''} encontrado${currentCount !== 1 ? 's' : ''}
            ${totalCount > currentCount ? ` (de ${totalCount} total)` : ''}
            | <i class="fas fa-users"></i> ${plazasTotales} plazas totales
        `;
    }
}

// Función para actualizar municipios por provincia
async function actualizarMunicipiosPorProvincia(provincia) {
    const selectMunicipio = document.getElementById('filterLocalidad');
    const valorActual = selectMunicipio.value;

    selectMunicipio.innerHTML = '<option value="">Todas</option>';

    if (!provincia) {
        return;
    }

    // Filtrar alojamientos por provincia para obtener las localidades disponibles
    const alojamientosDeProvincia = todosLosAlojamientos.filter(alojamiento => {
        const alojProvincia = alojamiento.province || alojamiento.Provincia || alojamiento.provincia || alojamiento.Province;
        return alojProvincia === provincia;
    });

    // Obtener localidades únicas de los alojamientos de esta provincia
    const localidades = [...new Set(alojamientosDeProvincia.map(a => {
        return a.municipality || a.Localidad || a.localidad || a.Municipality;
    }))].filter(l => l && l.trim() !== '').sort();

    localidades.forEach(localidad => {
        const option = document.createElement('option');
        option.value = localidad;
        option.textContent = localidad;
        if (localidad === valorActual) option.selected = true;
        selectMunicipio.appendChild(option);
    });

    // Fallback a la API si no hay localidades en los datos iniciales
    if (localidades.length === 0) {
        try {
            const response = await fetch(`/api/municipios.php?provincia=${encodeURIComponent(provincia)}`);
            const data = await response.json();

            if (data.success && data.municipios) {
                data.municipios.forEach(municipio => {
                    const option = document.createElement('option');
                    option.value = municipio;
                    option.textContent = municipio;
                    if (municipio === valorActual) option.selected = true;
                    selectMunicipio.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error al cargar municipios desde API:', error);
        }
    }
}

// Función para llenar el filtro de provincias (adaptada)
function llenarFiltroProvincias() {
    // Si ya tenemos datos iniciales, los usamos para poblar el filtro
    const provincias = [...new Set(todosLosAlojamientos.map(a => a.province || a.Provincia || a.provincia || a.Province))].filter(p => p && p.trim() !== '').sort();
    const select = document.getElementById('filterProvincia');

    select.innerHTML = ''; // Limpiar opciones existentes
    select.add(new Option('Todas', '')); // Opción "Todas"

    let provinciaSeleccionada = ''; // Por defecto, ninguna seleccionada
    if (window.provinciaInicial && provincias.includes(window.provinciaInicial)) {
        provinciaSeleccionada = window.provinciaInicial;
    } else if (window.searchInicial && provincias.includes(window.searchInicial)) {
        provinciaSeleccionada = window.searchInicial;
    }

    provincias.forEach(provincia => {
        const option = document.createElement('option');
        option.value = provincia;
        option.textContent = provincia;
        if (provincia === provinciaSeleccionada) {
            option.selected = true;
        }
        select.appendChild(option);
    });

    // Si la provincia inicial no existe en los datos, añadirla temporalmente
    if (window.provinciaInicial && !provincias.includes(window.provinciaInicial)) {
        const option = new Option(window.provinciaInicial, window.provinciaInicial);
        option.selected = true;
        select.appendChild(option);
    } else if (window.searchInicial && !provincias.includes(window.searchInicial) &&
               ['Soria', 'Burgos', 'Segovia', 'Ávila', 'Valladolid', 'Palencia', 'León', 'Zamora', 'Salamanca'].includes(window.searchInicial)) {
        const option = new Option(window.searchInicial, window.searchInicial);
        option.selected = true;
        select.appendChild(option);
    }
}

// Lógica de carga inicial
async function initialLoad() {
    // Verificar parámetros en la URL
    const urlParams = new URLSearchParams(window.location.search);
    window.provinciaInicial = urlParams.get('provincia');
    window.searchInicial = urlParams.get('search');
    window.categoriaInicial = urlParams.get('categoria'); // Si se usa para pre-filtrar

    // Si `window.INITIAL_ACCOMMODATIONS` existe, significa que PHP ya nos dio los datos.
    if (window.INITIAL_ACCOMMODATIONS && window.INITIAL_ACCOMMODATIONS.length > 0) {
        console.log('Hidratando la página con datos SSR.');
        todosLosAlojamientos = window.INITIAL_ACCOMMODATIONS;
        alojamientosFiltrados = [...todosLosAlojamientos]; // Inicialmente, todos los SSR son filtrados
        // El HTML ya está renderizado por PHP, solo necesitamos inicializar los filtros y la UI
        llenarFiltroProvincias();
        await actualizarMunicipiosPorProvincia(document.getElementById('filterProvincia').value);
        // Aplicar filtros iniciales si vienen de la URL
        await aplicarFiltros(); // Esto actualizará las estadísticas y mostrará los alojamientos
    } else {
        // Fallback: si por alguna razón no hay datos SSR, cargamos desde la API
        console.log('No hay datos SSR, cargando desde la API.');
        await cargarAlojamientosDesdeAPI();
    }

    inicializarFiltrosAutomaticos();
}

// Función para cargar alojamientos si no hay SSR (o como fallback)
async function cargarAlojamientosDesdeAPI() {
    document.getElementById('loading').style.display = 'block';
    document.getElementById('alojamientosGrid').style.display = 'none';

    try {
        const isMobile = window.innerWidth <= 768;
        let apiUrl = 'api/alojamientos.php?table=accommodations';

        if (window.provinciaInicial) {
            apiUrl += `&provincia=${encodeURIComponent(window.provinciaInicial)}`;
        }
        if (window.searchInicial) {
            apiUrl += `&search=${encodeURIComponent(window.searchInicial)}`;
        }
        // Si se quiere cargar la primera página de la API por defecto
        apiUrl += `&page=1&limit=${itemsPerPage}`;

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), isMobile ? 5000 : 10000);

        const response = await fetch(apiUrl, { signal: controller.signal });
        clearTimeout(timeoutId);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        if (data.success && data.data && data.data.alojamientos) {
            todosLosAlojamientos = data.data.alojamientos;
            alojamientosFiltrados = [...todosLosAlojamientos];
            console.log('✅ Cargados', data.data.alojamientos.length, 'alojamientos desde la base de datos (API)');
        } else {
            throw new Error('API returned empty or invalid data');
        }
    } catch (apiError) {
        console.warn('❌ API no disponible o error:', apiError.message);
        // Fallback a JSON local si la API falla completamente
        try {
            const response = await fetch('/accommodations.json'); // Asegúrate de que esta ruta sea correcta
            todosLosAlojamientos = await response.json();
            if (window.provinciaInicial) {
                alojamientosFiltrados = todosLosAlojamientos.filter(a => {
                    const prov = a.province || a.Provincia || a.provincia || a.Province;
                    return prov === window.provinciaInicial;
                });
            } else {
                alojamientosFiltrados = [...todosLosAlojamientos];
            }
            console.log('✅ Cargados alojamientos desde accommodations.json (fallback)');
        } catch (jsonError) {
            console.error('Error al cargar alojamientos desde accommodations.json:', jsonError);
            document.getElementById('loading').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error al cargar los alojamientos turísticos';
            return; // Salir si ambos fallan
        }
    }

    llenarFiltroProvincias();
    await aplicarFiltros(); // Esto mostrará los alojamientos y actualizará las estadísticas
    document.getElementById('loading').style.display = 'none';
    document.getElementById('alojamientosGrid').style.display = 'grid';
}


async function aplicarFiltros() {
    const provincia = document.getElementById('filterProvincia').value;
    const selectLocalidad = document.getElementById('filterLocalidad');
    const localidad = selectLocalidad.value;
    const tipo = document.getElementById('filterTipo').value;
    const plazasMin = parseInt(document.getElementById('filterPlazas').value) || 1;

    console.log('Aplicando filtros:', { provincia, localidad, tipo, plazasMin });

    // Primero actualizar las localidades disponibles según la provincia seleccionada
    await actualizarMunicipiosPorProvincia(provincia);

    // Filtrar alojamientos
    alojamientosFiltrados = todosLosAlojamientos.filter(alojamiento => {
        const alojProvincia = alojamiento.province || alojamiento.Provincia || alojamiento.provincia || alojamiento.Province;
        const alojLocalidad = alojamiento.municipality || alojamiento.Localidad || alojamiento.localidad || alojamiento.Municipality;
        const alojTipo = alojamiento.accommodation_type || alojamiento.Tipo || alojamiento.type;
        const alojPlazas = alojamiento.capacity || alojamiento.Plazas || alojamiento.plazas;

        // Verificar que tenga provincia (si no es un filtro vacío)
        if (provincia && (!alojProvincia || alojProvincia.trim() === '')) return false;

        // Aplicar filtros
        const cumpleProvincia = !provincia || alojProvincia === provincia;
        const cumpleLocalidad = !localidad || alojLocalidad === localidad;
        const cumpleTipo = !tipo || alojTipo === tipo;
        const cumplePlazas = alojPlazas >= plazasMin;

        return cumpleProvincia && cumpleLocalidad && cumpleTipo && cumplePlazas;
    });

    console.log(`Alojamientos filtrados: ${alojamientosFiltrados.length} de ${todosLosAlojamientos.length}`);

    mostrarAlojamientos();
}

function inicializarFiltrosAutomaticos() {
    document.getElementById('filterProvincia').addEventListener('change', async function(e) {
        // Resetear el filtro de localidad cuando cambia la provincia
        document.getElementById('filterLocalidad').value = '';
        await aplicarFiltros();
    });

    document.getElementById('filterLocalidad').addEventListener('change', aplicarFiltros);
    document.getElementById('filterTipo').addEventListener('change', aplicarFiltros);

    let plazasTimeout;
    document.getElementById('filterPlazas').addEventListener('input', function() {
        clearTimeout(plazasTimeout);
        plazasTimeout = setTimeout(aplicarFiltros, 500);
    });
}

document.addEventListener('DOMContentLoaded', initialLoad);

// Exportar funciones para que sean accesibles globalmente si es necesario (ej. para onclick en HTML)
window.cambiarFoto = cambiarFoto;
window.irAFoto = irAFoto;
window.generarSlug = generarSlug;
window.crearTarjetaAlojamiento = crearTarjetaAlojamiento;
window.aplicarFiltros = aplicarFiltros;
window.actualizarMunicipiosPorProvincia = actualizarMunicipiosPorProvincia;
window.llenarFiltroProvincias = llenarFiltroProvincias;
window.actualizarEstadisticas = actualizarEstadisticas;
window.mostrarAlojamientos = mostrarAlojamientos;
window.fetchAccommodations = fetchAccommodations; // Si se necesita llamar desde fuera