/* ===============================================
   ANTONIO - EL EXPERTO LOCAL (Mobile-First v4)
   JavaScript mejorado con datos reales, fotos, rutas y flujo natural
   =============================================== */

// ESTADO DE LA CONVERSACIÓN
let antonioState = {
    provincia: null,
    dias: null,
    intereses: [],
    presupuesto: null,
    alojamiento: null,
    temporada: null,
    historial: [],
    fuentesPermitidas: ['accommodations', 'places_of_interest', 'tourist_activities', 'cultural_events', 'routes'],
    categorias: {
        'accommodations':     { nombre: 'Alojamientos',     icono: '🏨', color: '#2c5f2d' },
        'places_of_interest': { nombre: 'Lugares de interés', icono: '🏛️', color: '#4a8c4b' },
        'tourist_activities': { nombre: 'Actividades',      icono: '🥾', color: '#d4a574' },
        'cultural_events':    { nombre: 'Eventos culturales', icono: '🎭', color: '#e74c3c' },
        'routes':             { nombre: 'Rutas temáticas',  icono: '🗺️', color: '#8e44ad' }
    }
};

// BASE DE DATOS - Se llena con datos reales desde la API
const antonioDatabase = {
    accommodations: [],
    places_of_interest: [],
    tourist_activities: [],
    cultural_events: [],
    routes: []
};

// Provincias más relevantes (ordenadas por popularidad turística)
const provinciasPopulares = [
    'Soria', 'Ávila', 'Segovia', 'Burgos', 'León', 'Asturias', 'Cantabria',
    'Huesca', 'Gerona', 'Lérida', 'Navarra', 'La Rioja', 'Zamora', 'Salamanca',
    'Cáceres', 'Toledo', 'Cuenca', 'Teruel', 'Jaén', 'Granada', 'Málaga'
];

// ===============================================
// INICIALIZACIÓN
// ===============================================

document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('antonio-widget')) {
        crearWidgetAntonio();
    }
    configurarEventos();
    cargarDatosReales();
    setTimeout(() => {
        mostrarMensajeBienvenida();
    }, 1200);
});

function crearWidgetAntonio() {
    const widgetHTML = `
        <div id="antonio-widget">
            <button id="antonio-fab" aria-label="Abrir asistente Antonio">
                <img src="antonio.jpg" alt="Antonio - Experto local" onerror="this.src='favicon.png'">
                <span class="antonio-badge" id="antonio-badge">1</span>
            </button>
            <div id="antonio-panel">
                <div id="antonio-header">
                    <div class="antonio-header-info">
                        <img src="antonio.jpg" alt="Antonio" onerror="this.src='favicon.png'">
                        <div>
                            <strong>Antonio, el experto local</strong>
                            <div class="antonio-status">
                                <span class="antonio-dot"></span>
                                <span>En línea</span>
                            </div>
                        </div>
                    </div>
                    <button id="antonio-close" aria-label="Cerrar panel">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="antonio-messages"></div>
                <div id="antonio-quick-options">
                    <button class="antonio-quick-btn" data-option="alojamientos">
                        <i class="fas fa-bed"></i> Alojamientos
                    </button>
                    <button class="antonio-quick-btn" data-option="lugares">
                        <i class="fas fa-landmark"></i> Lugares
                    </button>
                    <button class="antonio-quick-btn" data-option="actividades">
                        <i class="fas fa-hiking"></i> Actividades
                    </button>
                    <button class="antonio-quick-btn" data-option="eventos">
                        <i class="fas fa-calendar-alt"></i> Eventos
                    </button>
                    <button class="antonio-quick-btn" data-option="rutas">
                        <i class="fas fa-route"></i> Rutas
                    </button>
                </div>
                <div id="antonio-input-area">
                    <input type="text" id="antonio-input" placeholder="Pregúntame sobre alojamientos, rutas, lugares..." maxlength="500">
                    <button id="antonio-send" aria-label="Enviar mensaje">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div id="antonio-footer">
                    <p>💡 Información verificada de nuestras bases de datos</p>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', widgetHTML);
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'antonio_improved.css';
    document.head.appendChild(link);
}

function configurarEventos() {
    document.getElementById('antonio-fab').addEventListener('click', togglePanel);
    document.getElementById('antonio-close').addEventListener('click', togglePanel);
    document.getElementById('antonio-send').addEventListener('click', enviarMensajeUsuario);
    document.getElementById('antonio-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') enviarMensajeUsuario();
    });
    document.querySelectorAll('.antonio-quick-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            manejarOpcionRapida(this.getAttribute('data-option'));
        });
    });
    document.addEventListener('click', function(event) {
        const panel = document.getElementById('antonio-panel');
        const fab = document.getElementById('antonio-fab');
        if (panel.classList.contains('antonio-open') &&
            !panel.contains(event.target) &&
            !fab.contains(event.target) &&
            window.innerWidth >= 769) {
            togglePanel();
        }
    });
}

// ===============================================
// CARGA DE DATOS REALES
// ===============================================

function cargarDatosReales() {
    fetch('api/get_antonio_data.php')
        .then(response => response.json())
        .then(data => {
            if (data.accommodations)     antonioDatabase.accommodations = data.accommodations;
            if (data.places_of_interest) antonioDatabase.places_of_interest = data.places_of_interest;
            if (data.tourist_activities) antonioDatabase.tourist_activities = data.tourist_activities;
            if (data.cultural_events)    antonioDatabase.cultural_events = data.cultural_events;
            if (data.routes)             antonioDatabase.routes = data.routes;
            console.log('✅ Antonio: Datos cargados correctamente');
        })
        .catch(error => {
            console.error('Error cargando datos de Antonio:', error);
            // Fallback: datos de ejemplo mínimos
            cargarDatosEjemplo();
        });
}

function cargarDatosEjemplo() {
    antonioDatabase.accommodations = [
        { id: 1, nombre: 'Posada Real de Soria', descripcion: 'Un remanso de paz con techos de madera.', ubicacion: 'Soria', precio: '85€/noche', icono: '🏨', url: 'alojamientos-turisticos.html', foto: '' },
        { id: 2, nombre: 'Casa Rural El Mirador', descripcion: 'Vistas increíbles, ideal para parejas.', ubicacion: 'Ávila', precio: '70€/noche', icono: '🏠', url: 'alojamientos-turisticos.html', foto: '' }
    ];
    antonioDatabase.places_of_interest = [
        { id: 101, nombre: 'Cañón del Río Lobos', descripcion: 'Espectáculo natural de piedra y buitres.', ubicacion: 'Soria', precio: 'Gratis', icono: '🏞️', url: 'lugares-interes-paginacion.html', foto: '' }
    ];
    antonioDatabase.tourist_activities = [
        { id: 201, nombre: 'Ruta de las Estrellas', descripcion: 'Observación astronómica Starlight.', ubicacion: 'Soria', precio: '15€', icono: '⭐', url: 'actividades-turisticas.html', foto: '' }
    ];
    antonioDatabase.cultural_events = [
        { id: 301, nombre: 'Festival de las Ánimas', descripcion: 'Leyendas de Bécquer en Soria.', ubicacion: 'Soria', fecha: 'Noviembre', precio: '10€', icono: '🎭', url: 'eventos-culturales-paginacion.html', foto: '' }
    ];
    antonioDatabase.routes = [
        { id: 1, nombre: 'Puente 1 de Mayo en Soria', descripcion: 'Escapada de 3 días a Soria.', ubicacion: 'Soria', duracion: '3 días', icono: '🗺️', url: 'rutas/puente-1-mayo-soria', foto: '', color: '#2F5233' }
    ];
}

// ===============================================
// PANEL
// ===============================================

function togglePanel() {
    const panel = document.getElementById('antonio-panel');
    const fab = document.getElementById('antonio-fab');
    panel.classList.toggle('antonio-open');
    if (panel.classList.contains('antonio-open')) {
        document.getElementById('antonio-badge').style.display = 'none';
        setTimeout(() => document.getElementById('antonio-input').focus(), 300);
        setTimeout(() => {
            const messages = document.getElementById('antonio-messages');
            messages.scrollTop = messages.scrollHeight;
        }, 350);
    }
}

// ===============================================
// MENSAJES DE BIENVENIDA (FLUJO NATURAL)
// ===============================================

function mostrarMensajeBienvenida() {
    const mensaje = `
        <p>¡Hola! Soy <strong>Antonio</strong>, tu experto local de turismo 🌄</p>
        <p>Puedo ayudarte a descubrir:</p>
        <ul>
            <li>🏨 <strong>Alojamientos</strong> - Casas rurales, hoteles, apartamentos</li>
            <li>🏛️ <strong>Lugares de interés</strong> - Patrimonio, naturaleza, monumentos</li>
            <li>🥾 <strong>Actividades</strong> - Senderismo, rutas, experiencias</li>
            <li>🎭 <strong>Eventos culturales</strong> - Festivales, conciertos</li>
            <li>🗺️ <strong>Rutas temáticas</strong> - Itinerarios completos</li>
        </ul>
        <p>¿Qué te apetece explorar? Puedes preguntarme directamente o usar los botones de abajo 👇</p>
    `;
    agregarMensajeBot(mensaje);

    // Preguntar provincia de forma natural, no obligatoria
    setTimeout(() => {
        preguntarProvinciaNatural();
    }, 800);
}

function preguntarProvinciaNatural() {
    const mensaje = `
        <p>Por cierto, si me dices qué provincia te interesa, puedo filtrarte mejor los resultados 😊</p>
        <p>Pero si prefieres, podemos explorar <strong>todo lo disponible</strong> sin filtrar.</p>
        <div class="antonio-suggestions">
            <div class="antonio-suggestions-title">
                <i class="fas fa-map-marker-alt"></i> ¿Buscas algo en alguna provincia?
            </div>
            <div class="antonio-suggestions-list">
                <button class="antonio-suggestion-btn" onclick="saltarProvincia()">
                    🌍 Ver todo sin filtrar
                </button>
    `;

    // Mostrar solo las provincias más populares (no las 50)
    const provinciasSugeridas = provinciasPopulares.slice(0, 8);
    provinciasSugeridas.forEach(p => {
        mensaje += `<button class="antonio-suggestion-btn" onclick="seleccionarProvincia('${p}')">📍 ${p}</button>\n`;
    });

    mensaje += `
            </div>
            <div style="margin-top:8px;font-size:0.8rem;color:#888;">
                <button class="antonio-suggestion-btn" onclick="mostrarTodasProvincias()" style="font-size:0.8rem;">
                    📋 Ver todas las provincias
                </button>
            </div>
        </div>
    `;

    agregarMensajeBot(mensaje);
}

function saltarProvincia() {
    antonioState.provincia = null;
    agregarMensajeBot(`<p>¡Perfecto! Te muestro información de <strong>todas las provincias</strong> 🌍</p>
        <p>¿Qué te gustaría ver? Pregúntame o elige una opción rápida 👇</p>`);
}

function mostrarTodasProvincias() {
    const provinciasEspana = [
        'Álava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona',
        'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ciudad Real', 'Córdoba', 'Cuenca',
        'Gerona', 'Granada', 'Guadalajara', 'Guipúzcoa', 'Huelva', 'Huesca', 'Islas Baleares',
        'Jaén', 'La Coruña', 'La Rioja', 'Las Palmas', 'León', 'Lérida', 'Lugo', 'Madrid', 'Málaga',
        'Murcia', 'Navarra', 'Orense', 'Palencia', 'Pontevedra', 'Salamanca', 'Santa Cruz de Tenerife',
        'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Teruel', 'Toledo', 'Valencia', 'Valladolid',
        'Vizcaya', 'Zamora', 'Zaragoza'
    ];

    let selector = `<div class="antonio-suggestions">
        <div class="antonio-suggestions-title"><i class="fas fa-map-marker-alt"></i> Todas las provincias:</div>
        <div class="antonio-suggestions-list" style="max-height:250px;overflow-y:auto;">`;

    const porLetra = {};
    provinciasEspana.forEach(p => {
        const l = p.charAt(0).toUpperCase();
        if (!porLetra[l]) porLetra[l] = [];
        porLetra[l].push(p);
    });

    Object.keys(porLetra).sort().forEach(letra => {
        selector += `<div style="margin-bottom:6px;">
            <div style="font-size:0.75rem;color:#666;font-weight:600;">${letra}</div>
            <div style="display:flex;flex-wrap:wrap;gap:4px;">`;
        porLetra[letra].forEach(p => {
            selector += `<button class="antonio-suggestion-btn" onclick="seleccionarProvincia('${p}')" style="font-size:0.75rem;padding:4px 8px;">${p}</button>`;
        });
        selector += `</div></div>`;
    });

    selector += `</div></div>`;
    agregarMensajeBot(selector);
}

function seleccionarProvincia(provincia) {
    antonioState.provincia = provincia;
    agregarMensajeBot(`<p>¡Perfecto! Has seleccionado <strong>${provincia}</strong> 🗺️</p>
        <p>Ahora puedo mostrarte información específica para esta provincia.</p>
        <p>¿Qué te gustaría explorar en ${provincia}?</p>`);

    setTimeout(() => {
        agregarMensajeBot(`
            <div class="antonio-suggestions">
                <div class="antonio-suggestions-title"><i class="fas fa-bolt"></i> Opciones para ${provincia}:</div>
                <div class="antonio-suggestions-list">
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('alojamientos')">🏨 Alojamientos</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('lugares')">🏛️ Lugares</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('actividades')">🥾 Actividades</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('eventos')">🎭 Eventos</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('rutas')">🗺️ Rutas</button>
                </div>
            </div>
        `);
    }, 500);
}

// ===============================================
// ENVÍO Y PROCESAMIENTO DE MENSAJES
// ===============================================

function enviarMensajeUsuario() {
    const input = document.getElementById('antonio-input');
    const mensaje = input.value.trim();
    if (!mensaje) return;
    agregarMensajeUsuario(mensaje);
    input.value = '';
    setTimeout(() => procesarMensajeUsuario(mensaje), 500);
}

function manejarOpcionRapida(opcion) {
    const mapOpciones = {
        'alojamientos': 'Quiero ver alojamientos disponibles',
        'lugares': 'Muéstrame lugares de interés',
        'actividades': '¿Qué actividades turísticas hay?',
        'eventos': '¿Hay eventos culturales próximos?',
        'rutas': '¿Qué rutas temáticas tenéis?'
    };
    const mensaje = mapOpciones[opcion] || opcion;
    agregarMensajeUsuario(mensaje);
    procesarMensajeUsuario(mensaje);
}

function procesarMensajeUsuario(mensaje) {
    const mensajeLower = mensaje.toLowerCase();
    mostrarTypingIndicator();

    if (esPreguntaNoPermitida(mensajeLower)) {
        setTimeout(() => {
            ocultarTypingIndicator();
            redirigirPreguntaNoPermitida(mensajeLower);
        }, 1000);
        return;
    }

    const intencion = analizarIntencion(mensajeLower);

    setTimeout(() => {
        ocultarTypingIndicator();
        switch(intencion.tipo) {
            case 'saludo':         responderSaludo(); break;
            case 'categoria':      mostrarCategoria(intencion.categoria); break;
            case 'busqueda':       buscarEnCategoria(intencion.categoria, intencion.terminos); break;
            case 'busqueda_global': busquedaGlobal(intencion.termino); break;
            case 'provincia':      mostrarTodasProvincias(); break;
            case 'provincia_sola':  responderProvinciaSola(intencion.provincia); break;
            case 'ayuda':          mostrarAyuda(); break;
            case 'desconocido':    responderDesconocido(); break;
        }
    }, 1500);


}


function esPreguntaNoPermitida(mensaje) {
    const palabrasNoPermitidas = [
        'restaurante', 'bar', 'cafetería', 'comer', 'cenar', 'almorzar',
        'tienda', 'compras', 'supermercado', 'mercado',
        'transporte', 'autobús', 'tren', 'taxi', 'coche', 'autocar',
        'hospital', 'farmacia', 'médico', 'urgencias',
        'policía', 'emergencias', 'bomberos',
        'banco', 'cajero', 'dinero', 'cambio',
        'wifi', 'internet', 'conexión'
    ];
    return palabrasNoPermitidas.some(palabra => mensaje.includes(palabra));
}

function redirigirPreguntaNoPermitida(mensaje) {
    let respuesta = `<p>Lo siento, solo puedo responder basándome en nuestras <strong>bases de datos internas</strong>:</p>`;
    respuesta += `<div class="antonio-suggestions">
        <div class="antonio-suggestions-title"><i class="fas fa-lightbulb"></i> Te sugiero:</div>
        <div class="antonio-suggestions-list">
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('alojamientos')">🏨 Alojamientos</button>
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('lugares')">🏛️ Lugares</button>
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('actividades')">🥾 Actividades</button>
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('eventos')">🎭 Eventos</button>
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('rutas')">🗺️ Rutas</button>
        </div>
    </div>`;
    agregarMensajeBot(respuesta);
}

// Lista completa de provincias para detección en mensajes
const provinciasEspana = [
    'Álava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona',
    'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ciudad Real', 'Córdoba', 'Cuenca',
    'Gerona', 'Granada', 'Guadalajara', 'Guipúzcoa', 'Huelva', 'Huesca', 'Islas Baleares',
    'Jaén', 'La Coruña', 'La Rioja', 'Las Palmas', 'León', 'Lérida', 'Lugo', 'Madrid', 'Málaga',
    'Murcia', 'Navarra', 'Orense', 'Palencia', 'Pontevedra', 'Salamanca', 'Santa Cruz de Tenerife',
    'Segovia', 'Sevilla', 'Soria', 'Tarragona', 'Teruel', 'Toledo', 'Valencia', 'Valladolid',
    'Vizcaya', 'Zamora', 'Zaragoza'
];

// Provincias en minúscula para búsqueda
const provinciasLower = provinciasEspana.map(p => p.toLowerCase());

function detectarProvinciaEnMensaje(mensaje) {
    // Normalizar: quitar tildes para comparación
    const mensajeNorm = mensaje.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    for (let i = 0; i < provinciasLower.length; i++) {
        const provNorm = provinciasLower[i].normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        // Buscar la provincia como palabra completa (no parte de otra palabra)
        const regex = new RegExp('\\b' + provNorm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'i');
        if (regex.test(mensajeNorm)) {
            return provinciasEspana[i];
        }
    }
    return null;
}


function analizarIntencion(mensaje) {
    // 1. Detectar provincia mencionada en el mensaje
    const provinciaDetectada = detectarProvinciaEnMensaje(mensaje);
    if (provinciaDetectada) {
        antonioState.provincia = provinciaDetectada;
    }

    // 2. Saludos
    if (mensaje.match(/hola|buenas|hey|hi|hello|qué tal|como estás|buenos días|buenas tardes/)) {
        return { tipo: 'saludo' };
    }

    // 3. Detectar categoría + provincia (ej: "alojamientos en Soria")
    let categoria = null;

    // Rutas
    if (mensaje.match(/ruta|itinerario|escapada|puente|viaje organizado|plan de viaje/)) {
        categoria = 'routes';
    }
    // Alojamientos
    else if (mensaje.match(/alojamiento|hotel|casa rural|apartamento|dormir|pernoctar|hospedaje/)) {
        categoria = 'accommodations';
    }
    // Lugares
    else if (mensaje.match(/lugar|sitio|monumento|patrimonio|naturaleza|parque|mirador|qué visitar|qué ver/)) {
        categoria = 'places_of_interest';
    }
    // Actividades
    else if (mensaje.match(/actividad|excursión|senderismo|deporte|aventura|experiencia|qué hacer/)) {
        categoria = 'tourist_activities';
    }
    // Eventos
    else if (mensaje.match(/evento|festival|concierto|teatro|exposición|fiesta|cultural|qué hay/)) {
        categoria = 'cultural_events';
    }

    if (categoria) {
        return { tipo: 'categoria', categoria: categoria };
    }

    // 4. Provincia sola (sin categoría)
    if (provinciaDetectada) {
        // Si solo menciona provincia, preguntar qué quiere ver allí
        return { tipo: 'provincia_sola', provincia: provinciaDetectada };
    }

    // 5. Búsqueda con términos específicos
    const terminos = extraerTerminosBusqueda(mensaje);
    if (terminos.length > 0) {
        let cat = determinarCategoriaPorTerminos(terminos);
        return { tipo: 'busqueda', categoria: cat, terminos: terminos };
    }

    // 6. Ayuda
    if (mensaje.match(/ayuda|qué puedes|qué sabes|información|capacidades/)) {
        return { tipo: 'ayuda' };
    }

    // 7. Si el mensaje tiene al menos 3 caracteres y no es una palabra vacía,
    //    hacer búsqueda global en todas las categorías
    const palabras = mensaje.split(/\s+/).filter(p => p.length > 2);
    if (palabras.length > 0 && palabras.length <= 4) {
        const termino = mensaje.replace(/^(busca|encuentra|dime|sabes|conoces|qué|como|donde|dónde)\s+/i, '').trim();
        if (termino.length >= 3) {
            return { tipo: 'busqueda_global', termino: termino };
        }
    }

    // 8. Desconocido
    return { tipo: 'desconocido' };

}


function extraerTerminosBusqueda(mensaje) {
    const palabrasClave = [
        'numancia', 'laguna negra', 'urbión', 'cañón', 'río lobos',
        'senderismo', 'setas', 'micología', 'astronomía', 'starlight',
        'teatro', 'concierto', 'festival', 'jornadas',
        'casa rural', 'hotel', 'apartamento', 'camping',
        'románico', 'castillo', 'monasterio', 'iglesia',
        'gastronomía', 'vino', 'enoturismo'
    ];
    return palabrasClave.filter(palabra => mensaje.includes(palabra));
}

function determinarCategoriaPorTerminos(terminos) {
    const categoriasPorTermino = {
        'numancia': 'places_of_interest', 'laguna negra': 'places_of_interest',
        'urbión': 'places_of_interest', 'cañón': 'places_of_interest',
        'río lobos': 'places_of_interest', 'románico': 'places_of_interest',
        'castillo': 'places_of_interest', 'monasterio': 'places_of_interest',
        'iglesia': 'places_of_interest',
        'senderismo': 'tourist_activities', 'setas': 'tourist_activities',
        'micología': 'tourist_activities', 'astronomía': 'tourist_activities',
        'starlight': 'tourist_activities',
        'teatro': 'cultural_events', 'concierto': 'cultural_events',
        'festival': 'cultural_events', 'jornadas': 'cultural_events',
        'casa rural': 'accommodations', 'hotel': 'accommodations',
        'apartamento': 'accommodations', 'camping': 'accommodations',
        'gastronomía': 'tourist_activities', 'vino': 'tourist_activities',
        'enoturismo': 'tourist_activities'
    };
    for (const termino of terminos) {
        if (categoriasPorTermino[termino]) return categoriasPorTermino[termino];
    }
    return 'accommodations';
}

// ===============================================
// RESPUESTAS
// ===============================================

function responderSaludo() {
    agregarMensajeBot(`<p>¡Hola de nuevo! 😊 ¿En qué puedo ayudarte hoy?</p>
        <p>Recuerda que tengo información sobre:</p>
        <ul>
            <li>🏨 Alojamientos turísticos</li>
            <li>🏛️ Lugares de interés</li>
            <li>🥾 Actividades turísticas</li>
            <li>🎭 Eventos culturales</li>
            <li>🗺️ Rutas temáticas</li>
        </ul>
        <p>¿Qué te interesa explorar?</p>`);
}

function mostrarCategoria(categoria) {
    const datos = antonioDatabase[categoria];
    const infoCategoria = antonioState.categorias[categoria];

    if (!datos || datos.length === 0) {
        agregarMensajeBot(`<p>¡Qué buena elección! Estoy actualizando mi guía de ${infoCategoria.nombre.toLowerCase()} ✍️</p>
            <p>Mientras termino, puedes echar un vistazo aquí:</p>`);

        let urlPrincipal = '#';
        switch(categoria) {
            case 'accommodations':     urlPrincipal = 'alojamientos-turisticos.html'; break;
            case 'places_of_interest': urlPrincipal = 'lugares-interes-paginacion.html'; break;
            case 'tourist_activities': urlPrincipal = 'actividades-turisticas.html'; break;
            case 'cultural_events':    urlPrincipal = 'eventos-culturales-paginacion.html'; break;
            case 'routes':             urlPrincipal = 'rutas/'; break;
        }

        agregarMensajeBot(`<div class="antonio-suggestions">
            <div class="antonio-suggestions-list">
                <a href="${urlPrincipal}" class="antonio-suggestion-btn" style="text-decoration:none;display:inline-block;background:var(--antonio-accent);color:white;">
                    ✨ Explorar ${infoCategoria.nombre.toLowerCase()} ahora
                </a>
            </div>
        </div>`);
        return;
    }

    // Filtrar por provincia si está seleccionada
    let datosFiltrados = datos;
    if (antonioState.provincia) {
        const provLower = antonioState.provincia.toLowerCase();
        datosFiltrados = datos.filter(item => {
            // Comparar con el campo province (que es la provincia exacta)
            const provinciaItem = (item.province || '').toLowerCase();
            // También comprobar ubicacion por si acaso
            const ubicacionItem = (item.ubicacion || '').toLowerCase();
            return provinciaItem === provLower || ubicacionItem.includes(provLower);
        });
    }


    if (datosFiltrados.length === 0) {
        const msgProv = antonioState.provincia ? ` en ${antonioState.provincia}` : '';
        agregarMensajeBot(`<p>No tengo ${infoCategoria.nombre.toLowerCase()} registrados${msgProv} actualmente.</p>`);
        if (antonioState.provincia) {
            agregarMensajeBot(`<p>¿Te gustaría ver los que tengo disponibles en otras zonas?</p>`);
            const boton = `<button class="antonio-suggestion-btn" onclick="mostrarCategoriaSinFiltro('${categoria}')">
                ${infoCategoria.icono} Ver todos los ${infoCategoria.nombre.toLowerCase()}
            </button>`;
            agregarMensajeBot(`<div class="antonio-suggestions-list" style="margin-top:10px;">${boton}</div>`);
        }
        return;
    }

    const msgProv = antonioState.provincia ? ` en <strong>${antonioState.provincia}</strong>` : ' disponibles';
    let respuesta = `<p>Te muestro los <strong>${infoCategoria.nombre}</strong>${msgProv} ${infoCategoria.icono}:</p>`;
    respuesta += `<div class="antonio-cards-grid">`;
    datosFiltrados.forEach(item => {
        respuesta += crearTarjetaItem(item, infoCategoria);
    });
    respuesta += `</div>`;
    respuesta += generarSugerenciaRelacionada(categoria);
    agregarMensajeBot(respuesta);
}

function mostrarCategoriaSinFiltro(categoria) {
    antonioState.provincia = null;
    mostrarCategoria(categoria);
}

function crearTarjetaItem(item, categoriaInfo) {
    const url = item.url || '#';
    const foto = item.foto || '';
    const tieneFoto = foto && foto.length > 10;

    // Metadatos dinámicos según tipo
    let metaIzq = '';
    let metaDer = '';

    if (item.ubicacion) metaIzq = `📍 ${item.ubicacion}`;
    else if (item.fecha) metaIzq = `📅 ${item.fecha}`;
    else if (item.duracion) metaIzq = `⏱️ ${item.duracion}`;

    if (item.precio) metaDer = `💰 ${item.precio}`;
    else if (item.entrada) metaDer = `💰 ${item.entrada}`;
    else if (item.dificultad) metaDer = `🏔️ ${item.dificultad}`;

    return `
        <div class="antonio-card" onclick="window.open('${url}','_blank')" style="cursor:pointer;">
            ${tieneFoto ? `<img class="antonio-card-img" src="${foto}" alt="${item.nombre}" loading="lazy" onerror="this.style.display='none'">` : ''}
            <div class="antonio-card-header">
                <div class="antonio-card-icon" style="background:${categoriaInfo.color}20;color:${categoriaInfo.color};">
                    ${item.icono || categoriaInfo.icono}
                </div>
                <h3 class="antonio-card-title">${item.nombre}</h3>
            </div>
            <p class="antonio-card-desc">${item.descripcion || ''}</p>
            <div class="antonio-card-meta">
                <span>${metaIzq}</span>
                <span>${metaDer}</span>
            </div>
            <div style="margin-top:6px;font-size:0.8rem;color:var(--antonio-primary);">
                <i class="fas fa-external-link-alt"></i> Ver más
            </div>
        </div>
    `;
}

function buscarEnCategoria(categoria, terminos) {
    const datos = antonioDatabase[categoria];
    const infoCategoria = antonioState.categorias[categoria];

    if (!datos || datos.length === 0) {
        agregarMensajeBot(`<p>No tengo información disponible sobre eso en ${infoCategoria.nombre.toLowerCase()}.</p>`);
        return;
    }

    let resultados = datos.filter(item => {
        const textoBusqueda = `${item.nombre} ${item.descripcion} ${item.ubicacion || ''} ${item.province || ''}`.toLowerCase();
        const tieneTerminos = terminos.some(termino => textoBusqueda.includes(termino));
        if (antonioState.provincia) {
            const provLower = antonioState.provincia.toLowerCase();
            const provinciaItem = (item.province || '').toLowerCase();
            const ubicacionItem = (item.ubicacion || '').toLowerCase();
            return tieneTerminos && (provinciaItem === provLower || ubicacionItem.includes(provLower));
        }
        return tieneTerminos;
    });


    if (resultados.length === 0) {
        const msgProv = antonioState.provincia ? ` en ${antonioState.provincia}` : '';
        agregarMensajeBot(`<p>No encontré resultados para tu búsqueda en ${infoCategoria.nombre.toLowerCase()}${msgProv}.</p>`);
        const boton = `<button class="antonio-suggestion-btn" onclick="mostrarCategoria('${categoria}')">
            ${infoCategoria.icono} Ver todos los ${infoCategoria.nombre.toLowerCase()}
        </button>`;
        agregarMensajeBot(`<div class="antonio-suggestions-list" style="margin-top:10px;">${boton}</div>`);
        return;
    }

    let respuesta = `<p>Encontré <strong>${resultados.length} resultado(s)</strong> en ${infoCategoria.nombre.toLowerCase()} ${infoCategoria.icono}:</p>`;
    respuesta += `<div class="antonio-cards-grid">`;
    resultados.forEach(item => {
        respuesta += crearTarjetaItem(item, infoCategoria);
    });
    respuesta += `</div>`;
    respuesta += generarSugerenciaRelacionada(categoria);
    agregarMensajeBot(respuesta);
}

function generarSugerenciaRelacionada(categoriaActual) {
    let categoriasRelacionadas = [];
    switch(categoriaActual) {
        case 'accommodations':     categoriasRelacionadas = ['places_of_interest', 'tourist_activities']; break;
        case 'places_of_interest': categoriasRelacionadas = ['tourist_activities', 'routes']; break;
        case 'tourist_activities': categoriasRelacionadas = ['places_of_interest', 'accommodations']; break;
        case 'cultural_events':    categoriasRelacionadas = ['places_of_interest', 'accommodations']; break;
        case 'routes':             categoriasRelacionadas = ['accommodations', 'places_of_interest']; break;
    }
    if (categoriasRelacionadas.length === 0) return '';

    let html = `<div class="antonio-related-suggestions">
        <p style="font-size:0.85rem;color:#666;margin:12px 0 6px;"><i class="fas fa-lightbulb"></i> También te puede interesar:</p>
        <div class="antonio-suggestions-list">`;

    categoriasRelacionadas.forEach(cat => {
        const info = antonioState.categorias[cat];
        if (info) {
            html += `<button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('${cat === 'accommodations' ? 'alojamientos' : cat === 'places_of_interest' ? 'lugares' : cat === 'tourist_activities' ? 'actividades' : cat === 'cultural_events' ? 'eventos' : 'rutas'}')">
                ${info.icono} ${info.nombre}
            </button>`;
        }
    });

    html += `</div></div>`;
    return html;
}

function mostrarAyuda() {
    agregarMensajeBot(`<p>¡Claro! Soy <strong>Antonio</strong>, tu guía local 🤠</p>
        <p>Puedo ayudarte con:</p>
        <ul>
            <li>🏨 <strong>Alojamientos</strong> - Casas rurales, hoteles, apartamentos</li>
            <li>🏛️ <strong>Lugares de interés</strong> - Monumentos, naturaleza, patrimonio</li>
            <li>🥾 <strong>Actividades</strong> - Senderismo, experiencias, aventura</li>
            <li>🎭 <strong>Eventos culturales</strong> - Festivales, conciertos, teatro</li>
            <li>🗺️ <strong>Rutas temáticas</strong> - Itinerarios completos de viaje</li>
        </ul>
        <p>Puedes preguntarme cosas como:</p>
        <ul>
            <li>"<em>¿Qué alojamientos hay en Soria?</em>"</li>
            <li>"<em>Muéstrame actividades de senderismo</em>"</li>
            <li>"<em>¿Hay eventos culturales este mes?</em>"</li>
            <li>"<em>Qué rutas temáticas tenéis</em>"</li>
        </ul>
        <p>¡Pregúntame lo que quieras! 😊</p>`);
}

function responderDesconocido() {
    agregarMensajeBot(`<p>No estoy seguro de haber entendido bien tu pregunta 🤔</p>
        <p>Recuerda que puedo ayudarte con información sobre:</p>
        <ul>
            <li>🏨 Alojamientos turísticos</li>
            <li>🏛️ Lugares de interés</li>
            <li>🥾 Actividades turísticas</li>
            <li>🎭 Eventos culturales</li>
            <li>🗺️ Rutas temáticas</li>
        </ul>
        <p>¿Por qué no pruebas a preguntarme de otra forma? 👇</p>`);

    setTimeout(() => {
        agregarMensajeBot(`
            <div class="antonio-suggestions">
                <div class="antonio-suggestions-title"><i class="fas fa-bolt"></i> Prueba con:</div>
                <div class="antonio-suggestions-list">
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('alojamientos')">🏨 Alojamientos</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('lugares')">🏛️ Lugares</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('actividades')">🥾 Actividades</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('eventos')">🎭 Eventos</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('rutas')">🗺️ Rutas</button>
                </div>
            </div>
        `);
    }, 500);
}

// ===============================================
// BÚSQUEDA GLOBAL EN TODAS LAS CATEGORÍAS
// ===============================================

function busquedaGlobal(termino) {
    const terminoLower = termino.toLowerCase();
    let todosResultados = [];
    const categoriasConDatos = ['accommodations', 'places_of_interest', 'tourist_activities', 'cultural_events', 'routes'];

    categoriasConDatos.forEach(cat => {
        const datos = antonioDatabase[cat];
        if (!datos || datos.length === 0) return;

        datos.forEach(item => {
            const textoBusqueda = `${item.nombre} ${item.descripcion} ${item.ubicacion || ''} ${item.province || ''}`.toLowerCase();
            if (textoBusqueda.includes(terminoLower)) {
                const infoCat = antonioState.categorias[cat];
                todosResultados.push({
                    item: item,
                    categoria: cat,
                    infoCategoria: infoCat
                });
            }
        });
    });

    if (todosResultados.length === 0) {
        agregarMensajeBot(`<p>No encontré nada sobre "<strong>${escapeHTML(termino)}</strong>" en mi base de datos 🤔</p>
            <p>¿Quieres probar con otra palabra o explorar alguna categoría?</p>`);
        setTimeout(() => {
            agregarMensajeBot(`
                <div class="antonio-suggestions">
                    <div class="antonio-suggestions-title"><i class="fas fa-bolt"></i> Prueba con:</div>
                    <div class="antonio-suggestions-list">
                        <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('alojamientos')">🏨 Alojamientos</button>
                        <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('lugares')">🏛️ Lugares</button>
                        <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('actividades')">🥾 Actividades</button>
                        <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('eventos')">🎭 Eventos</button>
                        <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('rutas')">🗺️ Rutas</button>
                    </div>
                </div>
            `);
        }, 500);
        return;
    }

    // Agrupar resultados por categoría
    const agrupados = {};
    todosResultados.forEach(r => {
        if (!agrupados[r.categoria]) agrupados[r.categoria] = [];
        agrupados[r.categoria].push(r);
    });

    let respuesta = `<p>He encontrado <strong>${todosResultados.length} resultado(s)</strong> sobre "<strong>${escapeHTML(termino)}</strong>" 🔍</p>`;

    Object.keys(agrupados).forEach(cat => {
        const items = agrupados[cat];
        const infoCat = items[0].infoCategoria;
        respuesta += `<div style="margin:10px 0 4px;">
            <span style="font-weight:600;color:${infoCat.color};">${infoCat.icono} ${infoCat.nombre} (${items.length})</span>
        </div>`;
        respuesta += `<div class="antonio-cards-grid">`;
        items.forEach(r => {
            respuesta += crearTarjetaItem(r.item, r.infoCategoria);
        });
        respuesta += `</div>`;
    });

    agregarMensajeBot(respuesta);
}

// ===============================================
// RESPUESTA CUANDO SOLO MENCIONA PROVINCIA
// ===============================================

function responderProvinciaSola(provincia) {

    agregarMensajeBot(`<p>¡Estupendo! Te interesa <strong>${provincia}</strong> 🗺️</p>
        <p>¿Qué te gustaría conocer de ${provincia}? Puedo ayudarte con:</p>`);

    setTimeout(() => {
        agregarMensajeBot(`
            <div class="antonio-suggestions">
                <div class="antonio-suggestions-title"><i class="fas fa-bolt"></i> Elige una opción:</div>
                <div class="antonio-suggestions-list">
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('alojamientos')">🏨 Alojamientos en ${provincia}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('lugares')">🏛️ Lugares en ${provincia}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('actividades')">🥾 Actividades en ${provincia}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('eventos')">🎭 Eventos en ${provincia}</button>
                    <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('rutas')">🗺️ Rutas en ${provincia}</button>
                </div>
            </div>
        `);
    }, 500);
}

// ===============================================
// MANEJO DE MENSAJES EN EL CHAT
// ===============================================

function agregarMensajeUsuario(texto) {

    const messages = document.getElementById('antonio-messages');
    const div = document.createElement('div');
    div.className = 'antonio-message antonio-user';
    div.innerHTML = `<div class="antonio-bubble">${escapeHTML(texto)}</div>`;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
}

function agregarMensajeBot(html) {
    const messages = document.getElementById('antonio-messages');
    const div = document.createElement('div');
    div.className = 'antonio-message antonio-bot';
    div.innerHTML = `<div class="antonio-bubble">${html}</div>`;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
}

function mostrarTypingIndicator() {
    const messages = document.getElementById('antonio-messages');
    const div = document.createElement('div');
    div.className = 'antonio-message antonio-bot';
    div.id = 'antonio-typing';
    div.innerHTML = `<div class="antonio-bubble">
        <div class="antonio-typing-indicator">
            <span></span><span></span><span></span>
        </div>
    </div>`;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
}

function ocultarTypingIndicator() {
    const typing = document.getElementById('antonio-typing');
    if (typing) typing.remove();
}

function escapeHTML(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

