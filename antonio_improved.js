/* ===============================================
   ANTONIO - EL EXPERTO LOCAL (Mobile-First v3)
   JavaScript mejorado para turistas en movimiento
   =============================================== */

// ESTADO DE LA CONVERSACIÓN
let antonioState = {
    // Información del usuario
    dias: null,
    intereses: [],
    presupuesto: null,
    alojamiento: null,
    temporada: null,
    
    // Historial de conversación
    historial: [],
    
    // Fuentes de información permitidas
    fuentesPermitidas: ['accommodations', 'places_of_interest', 'tourist_activities', 'cultural_events'],
    
    // Categorías disponibles
    categorias: {
        'accommodations': { nombre: 'Alojamientos', icono: '🏨', color: '#2c5f2d' },
        'places_of_interest': { nombre: 'Lugares de interés', icono: '🏛️', color: '#4a8c4b' },
        'tourist_activities': { nombre: 'Actividades turísticas', icono: '🥾', color: '#d4a574' },
        'cultural_events': { nombre: 'Eventos culturales', icono: '🎭', color: '#e74c3c' }
    }
};

// DATOS DE EJEMPLO (simulando las 4 tablas)
const antonioDatabase = {
    accommodations: [
        {
            id: 1,
            nombre: 'Casa Rural El Roble',
            descripcion: 'Casa tradicional con chimenea y vistas espectaculares a la montaña. Capacidad para 6 personas.',
            ubicacion: 'Valle de Hoyocasero',
            precio: 'Desde 120€/noche',
            icono: '🏨',
            categoria: 'accommodations'
        },
        {
            id: 2,
            nombre: 'Hotel Rural Numantino',
            descripcion: 'Hotel con encanto histórico junto al yacimiento de Numancia. Desayuno incluido.',
            ubicacion: 'Garray',
            precio: 'Desde 85€/noche',
            icono: '🏨',
            categoria: 'accommodations'
        },
        {
            id: 3,
            nombre: 'Apartamento La Plaza',
            descripcion: 'Apartamento céntrico en Vinuesa, ideal para parejas. Parking gratuito.',
            ubicacion: 'Vinuesa',
            precio: 'Desde 75€/noche',
            icono: '🏨',
            categoria: 'accommodations'
        }
    ],
    
    places_of_interest: [
        {
            id: 1,
            nombre: 'Yacimiento de Numancia',
            descripcion: 'Ciudad celtíbera que resistió heroicamente al Imperio Romano. Visita guiada disponible.',
            ubicacion: 'Garray',
            entrada: '5€',
            icono: '🏛️',
            categoria: 'places_of_interest'
        },
        {
            id: 2,
            nombre: 'Cañón del Río Lobos',
            descripcion: 'Parque Natural con formaciones rocosas espectaculares y la ermita de San Bartolomé.',
            ubicacion: 'Ucero',
            entrada: 'Gratuita',
            icono: '🏛️',
            categoria: 'places_of_interest'
        },
        {
            id: 3,
            nombre: 'Laguna Negra de Urbión',
            descripcion: 'Laguna glaciar de ensueño en los Picos de Urbión. Ruta circular de 3-4 horas.',
            ubicacion: 'Vinuesa',
            entrada: 'Gratuita',
            icono: '🏛️',
            categoria: 'places_of_interest'
        }
    ],
    
    tourist_activities: [
        {
            id: 1,
            nombre: 'Senderismo Laguna Negra',
            descripcion: 'Ruta circular de alta montaña con paisajes espectaculares. Dificultad media.',
            duracion: '3-4 horas',
            precio: 'Gratuita',
            icono: '🥾',
            categoria: 'tourist_activities'
        },
        {
            id: 2,
            nombre: 'Ruta Micológica',
            descripcion: 'Recogida de setas con guía experto. Temporada de otoño (septiembre-noviembre).',
            duracion: '4 horas',
            precio: 'Desde 25€/persona',
            icono: '🥾',
            categoria: 'tourist_activities'
        },
        {
            id: 3,
            nombre: 'Observación Astronómica',
            descripcion: 'Experiencia nocturna con telescopios profesionales en zona certificada Starlight.',
            duracion: '2-3 horas',
            precio: 'Desde 30€/persona',
            icono: '🥾',
            categoria: 'tourist_activities'
        }
    ],
    
    cultural_events: [
        {
            id: 1,
            nombre: 'Festival de Teatro Clásico',
            descripcion: 'Obras clásicas en los teatros históricos de la provincia. Programación variada.',
            fecha: '15-30 agosto',
            ubicacion: 'Varios lugares',
            precio: 'Desde 10€',
            icono: '🎭',
            categoria: 'cultural_events'
        },
        {
            id: 2,
            nombre: 'Jornadas Micológicas',
            descripcion: 'Degustación, talleres y rutas guiadas para amantes de las setas.',
            fecha: 'Octubre',
            ubicacion: 'Vinuesa',
            precio: 'Gratuito',
            icono: '🎭',
            categoria: 'cultural_events'
        },
        {
            id: 3,
            nombre: 'Concierto de Verano',
            descripcion: 'Música clásica al aire libre en un entorno natural incomparable.',
            fecha: '15 agosto',
            ubicacion: 'Monasterio de San Juan de Duero',
            precio: '15€',
            icono: '🎭',
            categoria: 'cultural_events'
        }
    ]
};

// ===============================================
// INICIALIZACIÓN DEL WIDGET
// ===============================================

document.addEventListener('DOMContentLoaded', function() {
    // Crear estructura del widget si no existe
    if (!document.getElementById('antonio-widget')) {
        crearWidgetAntonio();
    }
    
    // Configurar eventos
    configurarEventos();
    
    // Mostrar mensaje de bienvenida
    setTimeout(() => {
        mostrarMensajeBienvenida();
    }, 1000);
});

function crearWidgetAntonio() {
    const widgetHTML = `
        <div id="antonio-widget">
            <!-- Floating Action Button -->
            <button id="antonio-fab" aria-label="Abrir asistente Antonio">
                <img src="antonio.jpg" alt="Antonio - Experto local" onerror="this.src='favicon.png'">
                <span class="antonio-badge" id="antonio-badge">1</span>
            </button>
            
            <!-- Panel principal -->
            <div id="antonio-panel">
                <!-- Header compacto (60px máximo) -->
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
                
                <!-- Área de mensajes -->
                <div id="antonio-messages"></div>
                
                <!-- Opciones rápidas -->
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
                </div>
                
                <!-- Área de entrada -->
                <div id="antonio-input-area">
                    <input type="text" id="antonio-input" placeholder="Pregúntame sobre alojamientos, lugares, actividades o eventos..." maxlength="500">
                    <button id="antonio-send" aria-label="Enviar mensaje">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                
                <!-- Footer compacto -->
                <div id="antonio-footer">
                    <p>💡 Solo uso nuestras bases de datos internas</p>
                </div>
            </div>
        </div>
    `;
    
    // Añadir al body
    document.body.insertAdjacentHTML('beforeend', widgetHTML);
    
    // Añadir estilos
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'antonio_improved.css';
    document.head.appendChild(link);
}

function configurarEventos() {
    // FAB para abrir/cerrar
    document.getElementById('antonio-fab').addEventListener('click', togglePanel);
    document.getElementById('antonio-close').addEventListener('click', togglePanel);
    
    // Enviar mensaje
    document.getElementById('antonio-send').addEventListener('click', enviarMensajeUsuario);
    document.getElementById('antonio-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            enviarMensajeUsuario();
        }
    });
    
    // Opciones rápidas
    document.querySelectorAll('.antonio-quick-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const option = this.getAttribute('data-option');
            manejarOpcionRapida(option);
        });
    });
    
    // Cerrar al hacer clic fuera (solo en desktop)
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
// FUNCIONES PRINCIPALES
// ===============================================

function togglePanel() {
    const panel = document.getElementById('antonio-panel');
    const fab = document.getElementById('antonio-fab');
    
    panel.classList.toggle('antonio-open');
    
    if (panel.classList.contains('antonio-open')) {
        // Ocultar badge cuando se abre
        document.getElementById('antonio-badge').style.display = 'none';
        
        // Enfocar input
        setTimeout(() => {
            document.getElementById('antonio-input').focus();
        }, 300);
        
        // Scroll al final
        setTimeout(() => {
            const messages = document.getElementById('antonio-messages');
            messages.scrollTop = messages.scrollHeight;
        }, 350);
    }
}

function mostrarMensajeBienvenida() {
    const mensaje = `
        <p>¡Hola! Soy <strong>Antonio</strong>, tu experto local de turismo 🌄</p>
        <p>Te ayudo a descubrir lo mejor de nuestro destino usando <strong>exclusivamente nuestras bases de datos internas</strong>:</p>
        <ul>
            <li>🏨 <strong>Alojamientos</strong> - Casas rurales, hoteles, apartamentos</li>
            <li>🏛️ <strong>Lugares de interés</strong> - Patrimonio, naturaleza, monumentos</li>
            <li>🥾 <strong>Actividades turísticas</strong> - Senderismo, rutas, experiencias</li>
            <li>🎭 <strong>Eventos culturales</strong> - Festivales, conciertos, exposiciones</li>
        </ul>
        <p>¿En qué puedo ayudarte hoy? 😊</p>
    `;
    
    agregarMensajeBot(mensaje);
}

function enviarMensajeUsuario() {
    const input = document.getElementById('antonio-input');
    const mensaje = input.value.trim();
    
    if (!mensaje) return;
    
    // Agregar mensaje del usuario
    agregarMensajeUsuario(mensaje);
    
    // Limpiar input
    input.value = '';
    
    // Procesar mensaje
    setTimeout(() => {
        procesarMensajeUsuario(mensaje);
    }, 500);
}

function manejarOpcionRapida(opcion) {
    let mensaje = '';
    
    switch(opcion) {
        case 'alojamientos':
            mensaje = 'Quiero ver alojamientos disponibles';
            break;
        case 'lugares':
            mensaje = 'Muéstrame lugares de interés';
            break;
        case 'actividades':
            mensaje = '¿Qué actividades turísticas hay?';
            break;
        case 'eventos':
            mensaje = '¿Hay eventos culturales próximos?';
            break;
    }
    
    agregarMensajeUsuario(mensaje);
    procesarMensajeUsuario(mensaje);
}

// ===============================================
// MANEJO DE MENSAJES
// ===============================================

function agregarMensajeUsuario(texto) {
    const messagesDiv = document.getElementById('antonio-messages');
    
    const msgDiv = document.createElement('div');
    msgDiv.className = 'antonio-msg antonio-msg-user';
    
    msgDiv.innerHTML = `
        <div class="antonio-msg-bubble antonio-msg-user-bubble">
            ${texto}
        </div>
        <img src="https://ui-avatars.com/api/?name=Usuario&background=2c5f2d&color=fff" 
             alt="Usuario" class="antonio-msg-avatar">
    `;
    
    messagesDiv.appendChild(msgDiv);
    scrollToBottom();
    
    // Añadir al historial
    antonioState.historial.push({ tipo: 'usuario', texto: texto, timestamp: new Date() });
}

function agregarMensajeBot(texto) {
    const messagesDiv = document.getElementById('antonio-messages');
    
    const msgDiv = document.createElement('div');
    msgDiv.className = 'antonio-msg antonio-msg-bot';
    
    msgDiv.innerHTML = `
        <img src="antonio.jpg" alt="Antonio" class="antonio-msg-avatar" onerror="this.src='favicon.png'">
        <div class="antonio-msg-bubble">
            ${texto}
        </div>
    `;
    
    messagesDiv.appendChild(msgDiv);
    scrollToBottom();
    
    // Añadir al historial
    antonioState.historial.push({ tipo: 'bot', texto: texto, timestamp: new Date() });
}

function mostrarTypingIndicator() {
    const messagesDiv = document.getElementById('antonio-messages');
    
    const typingDiv = document.createElement('div');
    typingDiv.className = 'antonio-msg antonio-msg-bot';
    typingDiv.id = 'antonio-typing';
    
    typingDiv.innerHTML = `
        <img src="antonio.jpg" alt="Antonio" class="antonio-msg-avatar" onerror="this.src='favicon.png'">
        <div class="antonio-msg-bubble antonio-typing-bubble">
            <span></span>
            <span></span>
            <span></span>
        </div>
    `;
    
    messagesDiv.appendChild(typingDiv);
    scrollToBottom();
}

function ocultarTypingIndicator() {
    const typingDiv = document.getElementById('antonio-typing');
    if (typingDiv) {
        typingDiv.remove();
    }
}

function scrollToBottom() {
    const messagesDiv = document.getElementById('antonio-messages');
    setTimeout(() => {
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }, 100);
}

// ===============================================
// PROCESAMIENTO DE MENSAJES
// ===============================================

function procesarMensajeUsuario(mensaje) {
    const mensajeLower = mensaje.toLowerCase();
    
    // Mostrar indicador de escritura
    mostrarTypingIndicator();
    
    // Verificar si es una pregunta fuera de las fuentes permitidas
    if (esPreguntaNoPermitida(mensajeLower)) {
        setTimeout(() => {
            ocultarTypingIndicator();
            redirigirPreguntaNoPermitida(mensajeLower);
        }, 1000);
        return;
    }
    
    // Analizar intención
    const intencion = analizarIntencion(mensajeLower);
    
    // Procesar según intención
    setTimeout(() => {
        ocultarTypingIndicator();
        
        switch(intencion.tipo) {
            case 'saludo':
                responderSaludo();
                break;
            case 'categoria':
                mostrarCategoria(intencion.categoria);
                break;
            case 'busqueda':
                buscarEnCategoria(intencion.categoria, intencion.terminos);
                break;
            case 'ayuda':
                mostrarAyuda();
                break;
            case 'desconocido':
                responderDesconocido();
                break;
        }
    }, 1500);
}

function esPreguntaNoPermitida(mensaje) {
    // Palabras clave que indican preguntas fuera de las fuentes permitidas
    const palabrasNoPermitidas = [
        'restaurante', 'bar', 'cafetería', 'comer', 'cenar', 'almorzar',
        'tienda', 'compras', 'supermercado', 'mercado',
        'transporte', 'autobús', 'tren', 'taxi', 'coche',
        'hospital', 'farmacia', 'médico', 'urgencias',
        'policía', 'emergencias', 'bomberos',
        'banco', 'cajero', 'dinero', 'cambio',
        'wifi', 'internet', 'conexión'
    ];
    
    return palabrasNoPermitidas.some(palabra => mensaje.includes(palabra));
}

function redirigirPreguntaNoPermitida(mensaje) {
    let respuesta = `<p>Lo siento, solo puedo responder basándome en nuestras <strong>4 bases de datos internas</strong>:</p>`;
    
    respuesta += `<div class="antonio-suggestions">
        <div class="antonio-suggestions-title">
            <i class="fas fa-lightbulb"></i> Te sugiero:
        </div>
        <div class="antonio-suggestions-list">
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('alojamientos')">
                🏨 Alojamientos
            </button>
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('lugares')">
                🏛️ Lugares de interés
            </button>
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('actividades')">
                🥾 Actividades
            </button>
            <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('eventos')">
                🎭 Eventos
            </button>
        </div>
    </div>`;
    
    agregarMensajeBot(respuesta);
}

function analizarIntencion(mensaje) {
    // Saludos
    if (mensaje.match(/hola|buenas|hey|hi|hello|qué tal|como estás/)) {
        return { tipo: 'saludo' };
    }
    
    // Categorías específicas
    if (mensaje.match(/alojamiento|hotel|casa rural|apartamento|dormir|pernoctar/)) {
        return { tipo: 'categoria', categoria: 'accommodations' };
    }
    
    if (mensaje.match(/lugar|sitio|monumento|patrimonio|naturaleza|parque|mirador/)) {
        return { tipo: 'categoria', categoria: 'places_of_interest' };
    }
    
    if (mensaje.match(/actividad|excursión|ruta|senderismo|deporte|aventura|experiencia/)) {
        return { tipo: 'categoria', categoria: 'tourist_activities' };
    }
    
    if (mensaje.match(/evento|festival|concierto|teatro|exposición|fiesta|cultural/)) {
        return { tipo: 'categoria', categoria: 'cultural_events' };
    }
    
    // Búsqueda con términos específicos
    const terminos = extraerTerminosBusqueda(mensaje);
    if (terminos.length > 0) {
        // Determinar categoría basada en términos
        let categoria = determinarCategoriaPorTerminos(terminos);
        return { tipo: 'busqueda', categoria: categoria, terminos: terminos };
    }
    
    // Ayuda
    if (mensaje.match(/ayuda|qué puedes|qué sabes|información|qué hay/)) {
        return { tipo: 'ayuda' };
    }
    
    // Desconocido
    return { tipo: 'desconocido' };
}

function extraerTerminosBusqueda(mensaje) {
    const palabrasClave = [
        'numancia', 'laguna', 'urbión', 'cañón', 'río lobos',
        'senderismo', 'setas', 'micología', 'astronomía',
        'teatro', 'concierto', 'festival', 'jornadas',
        'casa rural', 'hotel', 'apartamento', 'camping'
    ];
    
    return palabrasClave.filter(palabra => mensaje.includes(palabra));
}

function determinarCategoriaPorTerminos(terminos) {
    const categoriasPorTermino = {
        'numancia': 'places_of_interest',
        'laguna': 'places_of_interest',
        'urbión': 'places_of_interest',
        'cañón': 'places_of_interest',
        'río lobos': 'places_of_interest',
        'senderismo': 'tourist_activities',
        'setas': 'tourist_activities',
        'micología': 'tourist_activities',
        'astronomía': 'tourist_activities',
        'teatro': 'cultural_events',
        'concierto': 'cultural_events',
        'festival': 'cultural_events',
        'jornadas': 'cultural_events',
        'casa rural': 'accommodations',
        'hotel': 'accommodations',
        'apartamento': 'accommodations',
        'camping': 'accommodations'
    };
    
    for (const termino of terminos) {
        if (categoriasPorTermino[termino]) {
            return categoriasPorTermino[termino];
        }
    }
    
    return 'accommodations'; // Por defecto
}

// ===============================================
// RESPUESTAS
// ===============================================

function responderSaludo() {
    const respuesta = `
        <p>¡Hola de nuevo! 😊 ¿En qué puedo ayudarte hoy?</p>
        <p>Recuerda que solo uso nuestras <strong>4 bases de datos internas</strong>:</p>
        <ul>
            <li>🏨 Alojamientos turísticos</li>
            <li>🏛️ Lugares de interés</li>
            <li>🥾 Actividades turísticas</li>
            <li>🎭 Eventos culturales</li>
        </ul>
        <p>¿Qué te interesa explorar?</p>
    `;
    
    agregarMensajeBot(respuesta);
}

function mostrarCategoria(categoria) {
    const datos = antonioDatabase[categoria];
    const infoCategoria = antonioState.categorias[categoria];
    
    if (!datos || datos.length === 0) {
        agregarMensajeBot(`<p>No tengo información disponible en este momento sobre ${infoCategoria.nombre.toLowerCase()}.</p>`);
        return;
    }
    
    let respuesta = `<p>¡Perfecto! Te muestro los <strong>${infoCategoria.nombre}</strong> disponibles ${infoCategoria.icono}:</p>`;
    
    // Crear grid de tarjetas
    respuesta += `<div class="antonio-cards-grid">`;
    
    datos.forEach(item => {
        respuesta += crearTarjetaItem(item, infoCategoria);
    });
    
    respuesta += `</div>`;
    
    // Añadir sugerencia relacionada
    respuesta += generarSugerenciaRelacionada(categoria);
    
    agregarMensajeBot(respuesta);
}

function crearTarjetaItem(item, categoriaInfo) {
    return `
        <div class="antonio-card">
            <div class="antonio-card-header">
                <div class="antonio-card-icon" style="background: ${categoriaInfo.color}20; color: ${categoriaInfo.color};">
                    ${item.icono}
                </div>
                <h3 class="antonio-card-title">${item.nombre}</h3>
            </div>
            <p class="antonio-card-desc">${item.descripcion}</p>
            <div class="antonio-card-meta">
                <span>📍 ${item.ubicacion || item.duracion || item.fecha || ''}</span>
                <span>💰 ${item.precio || item.entrada || 'Consultar'}</span>
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
    
    // Filtrar datos por términos de búsqueda
    const resultados = datos.filter(item => {
        const textoBusqueda = `${item.nombre} ${item.descripcion} ${item.ubicacion || ''}`.toLowerCase();
        return terminos.some(termino => textoBusqueda.includes(termino));
    });
    
    if (resultados.length === 0) {
        agregarMensajeBot(`<p>No encontré resultados para tu búsqueda en ${infoCategoria.nombre.toLowerCase()}.</p>`);
        agregarMensajeBot(`<p>¿Te interesa ver todos los ${infoCategoria.nombre.toLowerCase()} disponibles?</p>`);
        
        // Botón para ver todos
        const botonVerTodos = `<button class="antonio-suggestion-btn" onclick="mostrarCategoria('${categoria}')">
            ${infoCategoria.icono} Ver todos los ${infoCategoria.nombre.toLowerCase()}
        </button>`;
        
        agregarMensajeBot(`<div class="antonio-suggestions-list" style="margin-top: 10px;">${botonVerTodos}</div>`);
        return;
    }
    
    let respuesta = `<p>Encontré <strong>${resultados.length} resultado(s)</strong> en ${infoCategoria.nombre.toLowerCase()} ${infoCategoria.icono}:</p>`;
    
    respuesta += `<div class="antonio-cards-grid">`;
    
    resultados.forEach(item => {
        respuesta += crearTarjetaItem(item, infoCategoria);
    });
    
    respuesta += `</div>`;
    
    // Añadir sugerencia relacionada
    respuesta += generarSugerenciaRelacionada(categoria);
    
    agregarMensajeBot(respuesta);
}

function generarSugerenciaRelacionada(categoriaActual) {
    // Determinar categorías relacionadas
    let categoriasRelacionadas = [];
    
    switch(categoriaActual) {
        case 'accommodations':
            categoriasRelacionadas = ['places_of_interest', 'tourist_activities'];
            break;
        case 'places_of_interest':
            categoriasRelacionadas = ['tourist_activities', 'cultural_events'];
            break;
        case 'tourist_activities':
            categoriasRelacionadas = ['places_of_interest', 'accommodations'];
            break;
        case 'cultural_events':
            categoriasRelacionadas = ['places_of_interest', 'accommodations'];
            break;
    }
    
    if (categoriasRelacionadas.length === 0) return '';
    
    const infoCategoria1 = antonioState.categorias[categoriasRelacionadas[0]];
    const infoCategoria2 = antonioState.categorias[categoriasRelacionadas[1]];
    
    return `
        <div class="antonio-suggestions">
            <div class="antonio-suggestions-title">
                <i class="fas fa-arrow-right"></i> ¿Te interesaría también...
            </div>
            <div class="antonio-suggestions-list">
                <button class="antonio-suggestion-btn" onclick="mostrarCategoria('${categoriasRelacionadas[0]}')">
                    ${infoCategoria1.icono} ${infoCategoria1.nombre}
                </button>
                <button class="antonio-suggestion-btn" onclick="mostrarCategoria('${categoriasRelacionadas[1]}')">
                    ${infoCategoria2.icono} ${infoCategoria2.nombre}
                </button>
            </div>
        </div>
    `;
}

function mostrarAyuda() {
    const respuesta = `
        <p>¡Claro que sí! Soy <strong>Antonio</strong>, tu experto local de turismo 🌄</p>
        <p>Puedo ayudarte con información sobre:</p>
        
        <div class="antonio-cards-grid">
            <div class="antonio-card">
                <div class="antonio-card-header">
                    <div class="antonio-card-icon" style="background: #2c5f2d20; color: #2c5f2d;">
                        🏨
                    </div>
                    <h3 class="antonio-card-title">Alojamientos</h3>
                </div>
                <p class="antonio-card-desc">Casas rurales, hoteles, apartamentos y más opciones para tu estancia.</p>
            </div>
            
            <div class="antonio-card">
                <div class="antonio-card-header">
                    <div class="antonio-card-icon" style="background: #4a8c4b20; color: #4a8c4b;">
                        🏛️
                    </div>
                    <h3 class="antonio-card-title">Lugares de interés</h3>
                </div>
                <p class="antonio-card-desc">Monumentos, parques naturales, miradores y sitios históricos.</p>
            </div>
            
            <div class="antonio-card">
                <div class="antonio-card-header">
                    <div class="antonio-card-icon" style="background: #d4a57420; color: #d4a574;">
                        🥾
                    </div>
                    <h3 class="antonio-card-title">Actividades</h3>
                </div>
                <p class="antonio-card-desc">Senderismo, rutas guiadas, experiencias y deportes en la naturaleza.</p>
            </div>
            
            <div class="antonio-card">
                <div class="antonio-card-header">
                    <div class="antonio-card-icon" style="background: #e74c3c20; color: #e74c3c;">
                        🎭
                    </div>
                    <h3 class="antonio-card-title">Eventos</h3>
                </div>
                <p class="antonio-card-desc">Festivales, conciertos, exposiciones y eventos culturales próximos.</p>
            </div>
        </div>
        
        <p>¿Qué te gustaría explorar? Puedes preguntarme directamente o usar los botones de abajo 👇</p>
    `;
    
    agregarMensajeBot(respuesta);
}

function responderDesconocido() {
    const respuesta = `
        <p>No estoy seguro de entender tu pregunta 🤔</p>
        <p>Recuerda que solo puedo ayudarte con información sobre nuestras <strong>4 bases de datos internas</strong>:</p>
        
        <div class="antonio-suggestions">
            <div class="antonio-suggestions-title">
                <i class="fas fa-lightbulb"></i> Prueba preguntando sobre:
            </div>
            <div class="antonio-suggestions-list">
                <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('alojamientos')">
                    🏨 Alojamientos disponibles
                </button>
                <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('lugares')">
                    🏛️ Lugares para visitar
                </button>
                <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('actividades')">
                    🥾 Actividades turísticas
                </button>
                <button class="antonio-suggestion-btn" onclick="manejarOpcionRapida('eventos')">
                    🎭 Eventos culturales
                </button>
            </div>
        </div>
        
        <p>O simplemente escribe tu pregunta en el cuadro de abajo 😊</p>
    `;
    
    agregarMensajeBot(respuesta);
}

// ===============================================
// FUNCIONES PÚBLICAS PARA INTEGRACIÓN
// ===============================================

// Función para abrir Antonio desde cualquier parte del sitio
function abrirAntonio() {
    // Asegurarse de que el widget esté creado
    if (!document.getElementById('antonio-widget')) {
        crearWidgetAntonio();
        setTimeout(() => {
            togglePanel();
        }, 100);
    } else {
        togglePanel();
    }
}

// Función para cargar datos reales desde APIs
function cargarDatosReales() {
    // Esta función se puede conectar a las APIs reales del proyecto
    console.log('Cargando datos reales para Antonio...');
    
    // Ejemplo de cómo se podría implementar:
    /*
    fetch('/api/accommodations')
        .then(response => response.json())
        .then(data => {
            antonioDatabase.accommodations = data.map(item => ({
                id: item.id,
                nombre: item.name,
                descripcion: item.description.substring(0, 100) + '...',
                ubicacion: item.location,
                precio: item.price_range,
                icono: '🏨',
                categoria: 'accommodations'
            }));
        });
    */
}

// Inicializar cuando se carga la página
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAntonio);
} else {
    initAntonio();
}

function initAntonio() {
    // Cargar datos reales si están disponibles
    if (typeof window.antonioRealData !== 'undefined') {
        cargarDatosDesdeObjeto(window.antonioRealData);
    }
    
    // Mostrar notificación si hay mensajes no leídos
    const historialGuardado = localStorage.getItem('antonioHistorial');
    if (historialGuardado) {
        const historial = JSON.parse(historialGuardado);
        if (historial.length > 0) {
            const ultimoMensaje = historial[historial.length - 1];
            if (ultimoMensaje.tipo === 'bot') {
                mostrarNotificacion();
            }
        }
    }
}

function cargarDatosDesdeObjeto(datos) {
    // Cargar datos reales desde un objeto proporcionado por el backend
    if (datos.accommodations) {
        antonioDatabase.accommodations = datos.accommodations.map(item => ({
            id: item.id,
            nombre: item.name || item.nombre,
            descripcion: (item.description || item.descripcion || '').substring(0, 120) + '...',
            ubicacion: item.location || item.ubicacion || item.city || '',
            precio: item.price_range || item.price || 'Consultar',
            icono: '🏨',
            categoria: 'accommodations'
        }));
    }
    
    if (datos.places_of_interest) {
        antonioDatabase.places_of_interest = datos.places_of_interest.map(item => ({
            id: item.id,
            nombre: item.name || item.nombre,
            descripcion: (item.description || item.descripcion || '').substring(0, 120) + '...',
            ubicacion: item.location || item.ubicacion || item.city || '',
            entrada: item.price || item.entrada || 'Gratuita',
            icono: '🏛️',
            categoria: 'places_of_interest'
        }));
    }
    
    if (datos.tourist_activities) {
        antonioDatabase.tourist_activities = datos.tourist_activities.map(item => ({
            id: item.id,
            nombre: item.name || item.nombre,
            descripcion: (item.description || item.descripcion || '').substring(0, 120) + '...',
            duracion: item.duration || item.duracion || '',
            precio: item.price || 'Consultar',
            icono: '🥾',
            categoria: 'tourist_activities'
        }));
    }
    
    if (datos.cultural_events) {
        antonioDatabase.cultural_events = datos.cultural_events.map(item => ({
            id: item.id,
            nombre: item.name || item.nombre || item.title,
            descripcion: (item.description || item.descripcion || '').substring(0, 120) + '...',
            fecha: item.date || item.fecha || item.start_date || '',
            ubicacion: item.location || item.ubicacion || item.place || '',
            precio: item.price || item.entrada || 'Consultar',
            icono: '🎭',
            categoria: 'cultural_events'
        }));
    }
}

function mostrarNotificacion() {
    const badge = document.getElementById('antonio-badge');
    if (badge) {
        badge.style.display = 'flex';
        badge.textContent = '!';
        
        // Animación de pulso
        badge.style.animation = 'antonio-pulse 1.5s infinite';
    }
}

// Guardar historial periódicamente
setInterval(() => {
    if (antonioState.historial.length > 0) {
        localStorage.setItem('antonioHistorial', JSON.stringify(antonioState.historial));
    }
}, 30000);

// Exportar funciones para uso global
window.abrirAntonio = abrirAntonio;
window.mostrarCategoria = mostrarCategoria;
window.manejarOpcionRapida = manejarOpcionRapida;

console.log('✅ Antonio - El experto local cargado correctamente');
