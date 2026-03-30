// ===============================================
// RUTAS - JAVASCRIPT + AGENTE IA
// Red Unificada de Turistas, Alojamientos y Servicios
// ===============================================

// Estado de la conversación
let conversationState = {
    dias: null,
    intereses: [],
    presupuesto: null,
    alojamiento: null,
    temporada: null,
    contexto: []
};

// ===============================================
// FUNCIONES DE NAVEGACIÓN
// ===============================================

// Scroll suave a las secciones
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        // Solo prevenir comportamiento por defecto si hay un href válido
        if (href && href !== '#') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
        // Cerrar menú móvil al hacer clic en un enlace
        closeMobileMenu();
    });
});

// Función para alternar el menú móvil
function toggleMobileMenu() {
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('navMenu');

    if (hamburger && navMenu) {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
    }
}

// Función para cerrar el menú móvil
function closeMobileMenu() {
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('navMenu');

    if (hamburger && navMenu) {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
    }
}

// Función para abrir el asistente
function abrirAsistente() {
    const asistenteSection = document.getElementById('asistente');
    asistenteSection.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
    setTimeout(() => {
        document.getElementById('userInput').focus();
    }, 500);
}

// ===============================================
// SISTEMA DE CHAT
// ===============================================

function enviarMensaje() {
    const input = document.getElementById('userInput');
    const mensaje = input.value.trim();
    
    if (mensaje === '') return;
    
    // Mostrar mensaje del usuario
    agregarMensaje(mensaje, 'user');
    
    // Limpiar input
    input.value = '';
    
    // Ocultar opciones rápidas después del primer mensaje
    document.getElementById('quickOptions').style.display = 'none';
    
    // Procesar mensaje con el agente de IA
    procesarConIA(mensaje);
}

function enviarQuickOption(opcion) {
    agregarMensaje(opcion, 'user');
    document.getElementById('quickOptions').style.display = 'none';
    procesarConIA(opcion);
}

function agregarMensaje(contenido, tipo) {
    const chatMessages = document.getElementById('chatMessages');
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${tipo}-message`;
    
    const avatar = document.createElement('div');
    avatar.className = 'message-avatar';
    avatar.innerHTML = tipo === 'user' ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
    
    const content = document.createElement('div');
    content.className = 'message-content';
    content.innerHTML = contenido;
    
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(content);
    
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function mostrarTypingIndicator() {
    const chatMessages = document.getElementById('chatMessages');
    const typingDiv = document.createElement('div');
    typingDiv.id = 'typing-indicator';
    typingDiv.className = 'message bot-message';
    typingDiv.innerHTML = `
        <div class="message-avatar">
            <i class="fas fa-robot"></i>
        </div>
        <div class="message-content">
            <div class="typing-indicator">
                <span></span><span></span><span></span>
            </div>
        </div>
    `;
    chatMessages.appendChild(typingDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function ocultarTypingIndicator() {
    const typingIndicator = document.getElementById('typing-indicator');
    if (typingIndicator) {
        typingIndicator.remove();
    }
}

// ===============================================
// AGENTE DE IA - MOTOR DE RECOMENDACIONES
// ===============================================

function procesarConIA(mensaje) {
    // Mostrar indicador de escritura
    mostrarTypingIndicator();
    
    // Analizar el mensaje
    const analisis = analizarMensaje(mensaje);
    
    // Actualizar estado de la conversación
    actualizarEstado(analisis);
    
    // Generar respuesta después de un delay (simula procesamiento)
    setTimeout(() => {
        ocultarTypingIndicator();
        const respuesta = generarRespuesta(analisis);
        agregarMensaje(respuesta, 'bot');
    }, 1500);
}

function analizarMensaje(mensaje) {
    const mensajeLower = mensaje.toLowerCase();
    
    const analisis = {
        dias: null,
        intereses: [],
        presupuesto: null,
        alojamiento: null,
        temporada: null,
        preguntas: []
    };
    
    // Detectar duración
    if (mensajeLower.match(/1 d[ií]a/)) analisis.dias = 1;
    else if (mensajeLower.match(/2[\s-]?3 d[ií]as/)) analisis.dias = 2.5;
    else if (mensajeLower.match(/fin de semana|finde/)) analisis.dias = 2;
    else if (mensajeLower.match(/una semana|7 d[ií]as/)) analisis.dias = 7;
    else if (mensajeLower.match(/3 d[ií]as/)) analisis.dias = 3;
    else if (mensajeLower.match(/4 d[ií]as/)) analisis.dias = 4;
    else if (mensajeLower.match(/5 d[ií]as/)) analisis.dias = 5;
    
    // Detectar intereses
    if (mensajeLower.match(/naturaleza|senderismo|monta[ñn]a|hiking|trekking|ruta/)) {
        analisis.intereses.push('naturaleza');
    }
    if (mensajeLower.match(/cultura|historia|monumento|patrimonio|arte|románico/)) {
        analisis.intereses.push('cultura');
    }
    if (mensajeLower.match(/relax|descanso|tranquil|spa|desconectar/)) {
        analisis.intereses.push('relax');
    }
    if (mensajeLower.match(/gastronom[ií]a|comida|restaurante|vino|bodega/)) {
        analisis.intereses.push('gastronomia');
    }
    if (mensajeLower.match(/aventura|deporte|activ|bici|piragua/)) {
        analisis.intereses.push('aventura');
    }
    if (mensajeLower.match(/fotograf[ií]a|foto|paisaje/)) {
        analisis.intereses.push('fotografia');
    }
    if (mensajeLower.match(/ni[ñn]os|familia|infantil/)) {
        analisis.intereses.push('familia');
    }
    if (mensajeLower.match(/astro|estrella|cielo/)) {
        analisis.intereses.push('astronomia');
    }
    
    // Detectar presupuesto
    if (mensajeLower.match(/econ[óo]mico|barato|ajustado|poco dinero/)) {
        analisis.presupuesto = 'bajo';
    } else if (mensajeLower.match(/medio|moderado|normal/)) {
        analisis.presupuesto = 'medio';
    } else if (mensajeLower.match(/alto|lujo|premium|sin l[ií]mite/)) {
        analisis.presupuesto = 'alto';
    }
    
    // Detectar tipo de alojamiento
    if (mensajeLower.match(/casa rural|casa/)) {
        analisis.alojamiento = 'casa_rural';
    } else if (mensajeLower.match(/hotel|posada/)) {
        analisis.alojamiento = 'hotel';
    } else if (mensajeLower.match(/camping|tienda/)) {
        analisis.alojamiento = 'camping';
    }
    
    // Detectar temporada
    if (mensajeLower.match(/verano|julio|agosto/)) {
        analisis.temporada = 'verano';
    } else if (mensajeLower.match(/invierno|diciembre|enero|febrero|nieve/)) {
        analisis.temporada = 'invierno';
    } else if (mensajeLower.match(/oto[ñn]o|septiembre|octubre|noviembre|setas/)) {
        analisis.temporada = 'otono';
    } else if (mensajeLower.match(/primavera|marzo|abril|mayo/)) {
        analisis.temporada = 'primavera';
    }
    
    return analisis;
}

function actualizarEstado(analisis) {
    if (analisis.dias) conversationState.dias = analisis.dias;
    if (analisis.intereses.length > 0) {
        conversationState.intereses = [...new Set([...conversationState.intereses, ...analisis.intereses])];
    }
    if (analisis.presupuesto) conversationState.presupuesto = analisis.presupuesto;
    if (analisis.alojamiento) conversationState.alojamiento = analisis.alojamiento;
    if (analisis.temporada) conversationState.temporada = analisis.temporada;
}

function generarRespuesta(analisis) {
    // Si tenemos suficiente información, generar ruta completa
    if (conversationState.dias && conversationState.intereses.length > 0) {
        return generarRutaPersonalizada();
    }
    
    // Si falta información, hacer preguntas de seguimiento
    if (!conversationState.dias) {
        return `<p>Entiendo que te interesa ${conversationState.intereses.join(', ') || 'visitar Soria'}. 😊</p>
                <p>¿Cuántos días planeas quedarte? Esto me ayudará a diseñar la ruta perfecta para ti.</p>`;
    }
    
    if (conversationState.intereses.length === 0) {
        return `<p>Perfecto, ${conversationState.dias} día${conversationState.dias > 1 ? 's' : ''} en Soria. ¡Excelente elección! 🎉</p>
                <p>¿Qué tipo de experiencias te interesan más?</p>
                <ul>
                    <li>🥾 Naturaleza y senderismo</li>
                    <li>🏛️ Cultura e historia</li>
                    <li>🍷 Gastronomía y enoturismo</li>
                    <li>📸 Fotografía de paisajes</li>
                    <li>✨ Observación astronómica</li>
                    <li>👨‍👩‍👧‍👦 Actividades en familia</li>
                </ul>`;
    }
    
    // Respuesta general
    return `<p>Interesante... Estoy preparando una ruta personalizada para ti. 🗺️</p>
            <p>¿Hay algo más que te gustaría incluir en tu viaje?</p>`;
}

function generarRutaPersonalizada() {
    const dias = conversationState.dias;
    const intereses = conversationState.intereses;
    
    let respuesta = `<p>¡Excelente! He diseñado una ruta personalizada de <strong>${dias} día${dias > 1 ? 's' : ''}</strong> 
                     para ti basada en tus intereses: <strong>${intereses.join(', ')}</strong>. 🎯</p>`;
    
    // Generar itinerario según días e intereses
    respuesta += '<div class="route-recommendation">';
    respuesta += '<h4>📋 Tu Itinerario Personalizado en Soria</h4>';
    
    if (dias >= 1) {
        respuesta += generarDia1(intereses);
    }
    if (dias >= 2) {
        respuesta += generarDia2(intereses);
    }
    if (dias >= 3) {
        respuesta += generarDia3(intereses);
    }
    if (dias >= 4) {
        respuesta += generarDia4(intereses);
    }
    if (dias >= 5) {
        respuesta += generarDia5(intereses);
    }
    if (dias >= 7) {
        respuesta += generarDiasExtras(intereses);
    }
    
    respuesta += '</div>';
    
    // Recomendaciones de alojamiento
    respuesta += generarRecomendacionesAlojamiento(intereses);
    
    // Consejos adicionales
    respuesta += generarConsejos(intereses);
    
    // Enlaces promocionales
    respuesta += generarEnlacesPromocionales();
    
    respuesta += '<p>¿Te gustaría más detalles sobre algún lugar en particular o necesitas información adicional? 😊</p>';
    
    return respuesta;
}

function generarDia1(intereses) {
    let contenido = '<div class="day-plan">';
    contenido += '<h5>🗓️ Día 1: Introducción a Soria</h5>';
    
    if (intereses.includes('naturaleza')) {
        contenido += `<p><strong>Mañana:</strong> Laguna Negra de Urbión 🌲 - La joya natural de Soria. Ruta circular (3-4h, dificultad media).</p>
                      <p><strong>Tarde:</strong> Vinuesa - Pueblo con encanto, arquitectura tradicional pinariega.</p>
                      <button class="btn-add-route" onclick="addToRoute('lugar', 'laguna-negra-urbion', 'Laguna Negra de Urbión', 41.8167, -2.8333, 'https://www.rutasrurales.io/menu_images/laguna_negra.jpg')" style="background: #2F5233; color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 0.85rem; margin-top: 8px;">
                          <i class="fas fa-plus-circle"></i> Añadir a mi ruta
                      </button>`;
    } else if (intereses.includes('cultura')) {
        contenido += `<p><strong>Mañana:</strong> Yacimiento de Numancia 🏛️ - Ciudad celtíbera heroica.</p>
                      <p><strong>Tarde:</strong> Soria capital - Monasterio de San Juan de Duero, Concatedral y casco histórico.</p>
                      <button class="btn-add-route" onclick="addToRoute('lugar', 'yacimiento-numancia', 'Yacimiento de Numancia', 41.7589, -2.4683, 'https://www.rutasrurales.io/menu_images/Yacimiento_Numancia.jpg')" style="background: #2F5233; color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 0.85rem; margin-top: 8px;">
                          <i class="fas fa-plus-circle"></i> Añadir a mi ruta
                      </button>`;
    } else {
        contenido += `<p><strong>Mañana:</strong> Soria capital - Paseo por el Duero, parque de la Alameda.</p>
                      <p><strong>Tarde:</strong> Mirador de los Cuatro Vientos y Ruta de las Murallas.</p>`;
    }
    
    contenido += '</div>';
    return contenido;
}

function generarDia2(intereses) {
    let contenido = '<div class="day-plan">';
    contenido += '<h5>🗓️ Día 2: Explorando los Tesoros</h5>';
    
    if (intereses.includes('naturaleza')) {
        contenido += `<p><strong>Mañana:</strong> Cañón del Río Lobos 🦅 - Parque Natural con la ermita de San Bartolomé.</p>
                      <p><strong>Tarde:</strong> Ucero - Pueblo medieval y mirador del castillo.</p>
                      <button class="btn-add-route" onclick="addToRoute('lugar', 'canon-rio-lobos', 'Cañón del Río Lobos', 41.7333, -3.5333, 'https://www.rutasrurales.io/menu_images/canon_rio_lobos.jpg')" style="background: #2F5233; color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 0.85rem; margin-top: 8px;">
                          <i class="fas fa-plus-circle"></i> Añadir a mi ruta
                      </button>`;
    } else if (intereses.includes('cultura')) {
        contenido += `<p><strong>Mañana:</strong> El Burgo de Osma - Catedral gótica impresionante 🏰.</p>
                      <p><strong>Tarde:</strong> Medinaceli - Villa medieval con Arco Romano único en España.</p>
                      <button class="btn-add-route" onclick="addToRoute('lugar', 'burgo-osma', 'El Burgo de Osma', 41.5833, -3.0667, 'https://www.rutasrurales.io/menu_images/El_burgo_de_Osma.jpg')" style="background: #2F5233; color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 0.85rem; margin-top: 8px;">
                          <i class="fas fa-plus-circle"></i> Añadir a mi ruta
                      </button>`;
    } else {
        contenido += `<p><strong>Mañana:</strong> Castroviejo - Mirador espectacular sobre el valle.</p>
                      <p><strong>Tarde:</strong> San Esteban de Gormaz - Iglesias románicas.</p>`;
    }
    
    contenido += '</div>';
    return contenido;
}

function generarDia3(intereses) {
    let contenido = '<div class="day-plan">';
    contenido += '<h5>🗓️ Día 3: Experiencias Auténticas</h5>';
    
    if (intereses.includes('naturaleza') || intereses.includes('aventura')) {
        contenido += `<p><strong>Mañana:</strong> Picos de Urbión - Senderismo a la cima (2228m) para los más aventureros ⛰️.</p>
                      <p><strong>Tarde:</strong> Molinos de Duero - Pueblo serrano tranquilo.</p>
                      <button class="btn-add-route" onclick="addToRoute('lugar', 'picos-urbion', 'Picos de Urbión', 41.8333, -2.8667, 'https://www.rutasrurales.io/menu_images/urbion.jpg')" style="background: #2F5233; color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 0.85rem; margin-top: 8px;">
                          <i class="fas fa-plus-circle"></i> Añadir a mi ruta
                      </button>`;
    } else if (intereses.includes('gastronomia')) {
        contenido += `<p><strong>Mañana:</strong> Ruta del vino de Ribera del Duero 🍷 - Visita a bodegas emblemáticas.</p>
                      <p><strong>Tarde:</strong> Degustación de productos locales (torreznos, morcilla, setas de temporada).</p>`;
    } else if (intereses.includes('cultura')) {
        contenido += `<p><strong>Mañana:</strong> Monasterio de San Juan de Duero - Claustro románico único con arcos mudéjares 🏛️.</p>
                      <p><strong>Tarde:</strong> Ruta del Románico Soriano - Iglesias medievales excepcionales.</p>
                      <button class="btn-add-route" onclick="addToRoute('lugar', 'san-juan-duero', 'Monasterio de San Juan de Duero', 41.7639, -2.4561, 'https://www.rutasrurales.io/menu_images/San_Juan_de_Duero.jpg')" style="background: #2F5233; color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 0.85rem; margin-top: 8px;">
                          <i class="fas fa-plus-circle"></i> Añadir a mi ruta
                      </button>`;
    } else {
        contenido += `<p><strong>Mañana:</strong> Laguna Negra y Lagunas Glaciares 💧.</p>
                      <p><strong>Tarde:</strong> Visita a pueblos con encanto de la Sierra.</p>`;
    }
    
    contenido += '</div>';
    return contenido;
}

function generarDia4(intereses) {
    let contenido = '<div class="day-plan">';
    contenido += '<h5>🗓️ Día 4: Rincones Especiales</h5>';
    
    contenido += `<p><strong>Mañana:</strong> Dehesa de la Villa - Bosque centenario 🌳.</p>
                  <p><strong>Tarde:</strong> Almazán - Villa medieval amurallada con tres iglesias románicas.</p>`;
    
    contenido += '</div>';
    return contenido;
}

function generarDia5(intereses) {
    let contenido = '<div class="day-plan">';
    contenido += '<h5>🗓️ Día 5: Patrimonio y Naturaleza</h5>';
    
    if (intereses.includes('astronomia')) {
        contenido += `<p><strong>Día:</strong> Ruta por pueblos con cielos certificados Starlight ✨.</p>
                      <p><strong>Noche:</strong> Observación astronómica en punto oscuro recomendado.</p>`;
    } else {
        contenido += `<p><strong>Mañana:</strong> Cascada de la Fuentona - Nacimiento kárstico espectacular 💦.</p>
                      <p><strong>Tarde:</strong> Calatañazor - Uno de los pueblos más bonitos de España.</p>`;
    }
    
    contenido += '</div>';
    return contenido;
}

function generarDiasExtras(intereses) {
    let contenido = '<div class="day-plan">';
    contenido += '<h5>🗓️ Días 6-7: Inmersión Total</h5>';
    
    contenido += `<p><strong>Sugerencias adicionales:</strong></p>
                  <ul>
                      <li>🍄 Ruta micológica con guía (si es temporada de setas)</li>
                      <li>🏛️ Ruta de las Icnitas - Huellas de dinosaurios</li>
                      <li>🎨 Ruta Machadiana - Siguiendo los pasos del poeta</li>
                      <li>🌊 Embalse de la Cuerda del Pozo - Deportes acuáticos</li>
                      <li>🦌 Observación de fauna en Sierra de Urbión</li>
                  </ul>`;
    
    contenido += '</div>';
    return contenido;
}

function generarRecomendacionesAlojamiento(intereses) {
    let contenido = '<div class="route-recommendation">';
    contenido += '<h4>🏡 Alojamientos Recomendados</h4>';
    
    // Añadir botón para ver todos los alojamientos
    contenido += `<p>Encuentra el alojamiento perfecto para tu viaje a Soria:</p>`;
    
    if (intereses.includes('naturaleza')) {
        contenido += `<p><strong>🏔️ Para lovers de la naturaleza:</strong></p>
                      <ul>
                      <li><strong>Casa Rural El Roble</strong> - Valle de Hoyocasero (cerca de Laguna Negra)</li>
                      <li><strong>Casa Chaparrete</strong> - Deza (entorno rural auténtico)</li>
                      <li><strong>Casa Enrique</strong> - Santervas de la Sierra (vistas espectaculares)</li>
                      </ul>`;
    }
    
    if (intereses.includes('cultura')) {
        contenido += `<p><strong>🏛️ Para amantes de la historia:</strong></p>
                      <ul>
                      <li><strong>Hotel Rural Numantino</strong> - Garray (junto a Numancia)</li>
                      <li><strong>La Plaza</strong> - Vinuesa (en el centro histórico)</li>
                      </ul>`;
    }
    
    if (intereses.includes('relax')) {
        contenido += `<p><strong>🛋️ Para relax y descanso:</strong></p>
                      <ul>
                      <li><strong>Posada La Laguna Negra</strong> - Vinuesa (pet friendly)</li>
                      <li><strong>Apartamento La Plaza</strong> - Vinuesa (ambiente tranquilo)</li>
                      </ul>`;
    }
    
    // Enlace principal a todos los alojamientos
    contenido += `<p style="margin-top: 1rem;">
                  <a href="https://www.rutasrurales.io/alojamientos-turisticos.html" style="display: inline-block; background: #2F5233; color: white; padding: 12px 24px; border-radius: 25px; text-decoration: none; font-weight: bold; margin-top: 10px;">
                      <i class="fas fa-bed"></i> Ver todos los alojamientos →
                  </a>
                  </p>`;
    
    contenido += '</div>';
    return contenido;
}

function generarConsejos(intereses) {
    let consejos = '<div class="route-recommendation">';
    consejos += '<h4>💡 Consejos Importantes</h4>';
    consejos += '<ul>';
    
    if (intereses.includes('naturaleza')) {
        consejos += '<li>🥾 Lleva calzado de montaña adecuado y ropa por capas</li>';
        consejos += '<li>💧 Siempre lleva agua y algo de comida para las rutas</li>';
    }
    
    if (intereses.includes('gastronomia')) {
        consejos += '<li>🍽️ Reserva con antelación en los mejores restaurantes</li>';
        consejos += '<li>🍷 No te pierdas los vinos de la Ribera del Duero y Rueda</li>';
        consejos += '<li>🧀 Degusta productos típicos: Queso de Soria, Jamón de Treviño</li>';
        consejos += '<li>🍄 Temporada de setas (sept-nov): prueba setas de cardo y shiitake</li>';
    }
    
    if (intereses.includes('cultura')) {
        consejos += '<li>🏛️ Comprueba horarios de museos y monumentos (muchos cierran lunes)</li>';
        consejos += '<li>🎫 Carnet joven y passes culturales disponibles</li>';
        consejos += '<li>📚 Aprovecha las rutas literarias: Machado, Celaya, Bécquer</li>';
        consejos += '<li>⛪ Visita iglesias románicas con guía local para mejor comprensión</li>';
    }
    
    consejos += '<li>🌡️ El clima es continental: frío en invierno, caluroso en verano</li>';
    consejos += '<li>⛽ Recomendable viajar en coche para máxima flexibilidad</li>';
    consejos += '<li>📱 Algunas zonas rurales tienen cobertura limitada</li>';
    
    if (intereses.includes('fotografia')) {
        consejos += '<li>📸 Las mejores luces para fotografía: amanecer y atardecer</li>';
    }
    
    if (intereses.includes('astronomia')) {
        consejos += '<li>Luna nueva es el mejor momento para observar estrellas</li>';
    }
    
    consejos += '</ul>';
    
    // Información específica sobre enoturismo y patrimonio
    if (intereses.includes('gastronomia') || intereses.includes('cultura')) {
        consejos += '<div style="background-color: #f9f9f9; padding: 1rem; border-radius: 8px; margin-top: 1rem;">';
        consejos += '<h5>🍷 Enoturismo en Castilla y León</h5>';
        consejos += '<p><strong>Ribera del Duero:</strong> Bodegas emblemáticas como Protos, Vega Sicilia, Pago de Carraovejas.</p>';
        consejos += '<p><strong>Rueda:</strong> Famosa por vinos blancos, visita Bodegas José Pariente.</p>';
        consejos += '<p><strong>Recomendación:</strong> Reserva visitas con antelación, especialmente en vendimia (septiembre).</p>';
        consejos += '</div>';
        
        consejos += '<div style="background-color: #f9f9f9; padding: 1rem; border-radius: 8px; margin-top: 1rem;">';
        consejos += '<h5>🏛️ Patrimonio de Castilla y León</h5>';
        consejos += '<p><strong>Patrimonio Mundial UNESCO:</strong> Catedral de Burgos, León y Segovia; Acueducto de Segovia.</p>';
        consejos += '<p><strong>Románico:</strong> Ruta del Románico Soriano, San Juan de Duero, Iglesia de San Pedro en Soria.</p>';
        consejos += '<p><strong>Gótico:</strong> Catedrales de Burgos y León, Claustro de la Colegiata de San Miguel.</p>';
        consejos += '</div>';
    }
    
    consejos += '</div>';
    
    return consejos;
}

function generarEnlacesPromocionales() {
    let enlaces = '<div class="route-recommendation" style="background: linear-gradient(135deg, #f0f8f0 0%, #e8f5e9 100%); border: 2px solid #2c5f2d; margin-top: 2rem;">';
    enlaces += '<h4 style="color: #2c5f2d; text-align: center; margin-bottom: 1.5rem;">🌟 Descubre Más en Nuestra Web</h4>';
    enlaces += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">';
    
    enlaces += `
        <a href="https://www.rutasrurales.io/alojamientos-turisticos.html" style="display: block; padding: 1rem; background-color: white; border-radius: 10px; text-decoration: none; color: #2c5f2d; text-align: center; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 15px rgba(44,95,45,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
            <i class="fas fa-bed" style="font-size: 2rem; color: #87a96b; margin-bottom: 0.5rem;"></i><br>
            <strong>Alojamientos Turísticos</strong><br>
            <small style="color: #666;">Reserva tu estancia</small>
        </a>
        
        <a href="https://www.rutasrurales.io/lugares-interes-paginacion.html" style="display: block; padding: 1rem; background-color: white; border-radius: 10px; text-decoration: none; color: #2c5f2d; text-align: center; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 15px rgba(44,95,45,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
            <i class="fas fa-landmark" style="font-size: 2rem; color: #87a96b; margin-bottom: 0.5rem;"></i><br>
            <strong>Lugares de Interés</strong><br>
            <small style="color: #666;">Patrimonio y cultura</small>
        </a>
        
        <a href="https://www.rutasrurales.io/actividades-turisticas.html" style="display: block; padding: 1rem; background-color: white; border-radius: 10px; text-decoration: none; color: #2c5f2d; text-align: center; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 15px rgba(44,95,45,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
            <i class="fas fa-hiking" style="font-size: 2rem; color: #87a96b; margin-bottom: 0.5rem;"></i><br>
            <strong>Actividades Turísticas</strong><br>
            <small style="color: #666;">Naturaleza y aventura</small>
        </a>
        
        <a href="https://www.rutasrurales.io/eventos-culturales-paginacion.html" style="display: block; padding: 1rem; background-color: white; border-radius: 10px; text-decoration: none; color: #2c5f2d; text-align: center; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 15px rgba(44,95,45,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
            <i class="fas fa-calendar-alt" style="font-size: 2rem; color: #87a96b; margin-bottom: 0.5rem;"></i><br>
            <strong>Eventos Culturales</strong><br>
            <small style="color: #666;">Agenda cultural</small>
        </a>
        
        <a href="https://www.rutasrurales.io/rutas-turisticas.html" style="display: block; padding: 1rem; background-color: white; border-radius: 10px; text-decoration: none; color: #2c5f2d; text-align: center; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 15px rgba(44,95,45,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)';">
            <i class="fas fa-route" style="font-size: 2rem; color: #87a96b; margin-bottom: 0.5rem;"></i><br>
            <strong>Rutas Turísticas</strong><br>
            <small style="color: #666;">Itinerarios completos</small>
        </a>
    `;
    
    enlaces += '</div>';
    enlaces += '<p style="text-align: center; margin-top: 1.5rem; color: #2c5f2d; font-weight: 600;">📍 Toda la información que necesitas para tu viaje perfecto a Soria</p>';
    enlaces += '</div>';
    
    return enlaces;
}

// ===============================================
// SELECTOR DE IDIOMAS
// ===============================================

// Función para alternar el dropdown de idiomas desktop
function toggleLanguageDropdown() {
    const dropdown = document.getElementById('languageDropdownNav');
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

// Cerrar dropdown de idiomas al hacer clic fuera
document.addEventListener('click', function(event) {
    const languageBtn = document.getElementById('languageBtnNav');
    const languageDropdown = document.getElementById('languageDropdownNav');

    if (languageBtn && languageDropdown) {
        if (!languageBtn.contains(event.target) && !languageDropdown.contains(event.target)) {
            languageDropdown.classList.remove('active');
        }
    }
});

// ===============================================
// EVENTOS GLOBALES
// ===============================================

// Permitir enviar con Enter y configurar menú móvil
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('userInput');
    if (input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                enviarMensaje();
            }
        });
    }

    // Configurar menú móvil
    const hamburger = document.getElementById('hamburger');
    if (hamburger) {
        hamburger.addEventListener('click', toggleMobileMenu);
    }

    // Configurar selector de idiomas desktop
    const languageBtn = document.getElementById('languageBtnNav');
    if (languageBtn) {
        languageBtn.addEventListener('click', toggleLanguageDropdown);
    }
});

// Animación de aparición de cards al hacer scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '0';
            entry.target.style.transform = 'translateY(20px)';
            setTimeout(() => {
                entry.target.style.transition = 'all 0.6s ease';
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }, 100);
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observar todas las tarjetas
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => observer.observe(card));
});

// ===============================================
// SISTEMA DE AÑADIR A RUTA DESDE ANTONIO
// ===============================================

/**
 * Añade un elemento a la ruta del usuario
 * @param {string} tipo - Tipo de elemento: 'alojamiento', 'lugar', 'actividad', 'evento'
 * @param {string} slug - Slug único del elemento
 * @param {string} nombre - Nombre del elemento
 * @param {number} lat - Latitud
 * @param {number} lng - Longitud
 * @param {string} foto - URL de la foto
 */
function addToRoute(tipo, slug, nombre, lat, lng, foto) {
    // Obtener ruta actual del localStorage
    let myRoute = [];
    try {
        myRoute = JSON.parse(localStorage.getItem('myPersonalRoute') || '[]');
    } catch (e) {
        myRoute = [];
    }
    
    // Verificar si el elemento ya está en la ruta
    const yaExiste = myRoute.some(item => item.slug === slug && item.tipo === tipo);
    if (yaExiste) {
        alert('¡Este elemento ya está en tu ruta! ✅');
        return;
    }
    
    // Crear el objeto del elemento
    const elemento = {
        tipo: tipo,
        slug: slug,
        nombre: nombre,
        lat: lat,
        lng: lng,
        foto: foto || '',
        localidad: 'Soria', // Valor por defecto
        date: '',
        time: '',
        description: ''
    };
    
    // Añadir a la ruta
    myRoute.push(elemento);
    
    // Guardar en localStorage
    localStorage.setItem('myPersonalRoute', JSON.stringify(myRoute));
    
    // Mostrar confirmación
    const confirmado = confirm(`"${nombre}" ha sido añadido a tu ruta ✅\n\n¿Ver ahora tu itinerario en "Mi Ruta"?`);
    if (confirmado) {
        window.location.href = '/mi-ruta.html';
    }
}

/**
 * Versión simplificada de addToRoute para usar con datos de eventos
 * @param {Object} evento - Objeto con los datos del evento
 */
function addEventToRoute(evento) {
    addToRoute(
        'evento',
        evento.slug || 'evento-' + evento.id,
        evento.titulo || evento.nombre,
        evento.latitud || evento.lat,
        evento.longitud || evento.lng,
        evento.imagen || evento.foto
    );
}

/**
 * Versión simplificada de addToRoute para usar con datos de alojamientos
 * @param {Object} alojamiento - Objeto con los datos del alojamiento
 */
function addAlojamientoToRoute(alojamiento) {
    addToRoute(
        'alojamiento',
        alojamiento.slug || 'alojamiento-' + alojamiento.id,
        alojamiento.nombre,
        alojamiento.latitude || alojamiento.lat,
        alojamiento.longitude || alojamiento.lng,
        alojamiento.imagenes?.[0] || alojamiento.foto_principal || alojamiento.foto
    );
}

/**
 * Versión simplificada de addToRoute para usar con datos de lugares
 * @param {Object} lugar - Objeto con los datos del lugar
 */
function addLugarToRoute(lugar) {
    addToRoute(
        'lugar',
        lugar.slug || 'lugar-' + lugar.id,
        lugar.nombre || lugar.titulo,
        lugar.latitud || lugar.lat,
        lugar.longitud || lugar.lng,
        lugar.imagen || lugar.foto_principal || lugar.foto
    );
}

// Sistema cargado correctamente
