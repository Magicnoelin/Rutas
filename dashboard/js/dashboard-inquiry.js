/* ============================================================
   dashboard-inquiry.js — Envío de Consultas y Tablón (REQ 3)
   Funciones: showNewConversationModal, submitNewChat,
              refreshTplPreview, searchAccommodations,
              loadBulletinRequests, respondToBulletin
   ============================================================ */

// ============================================================
// MODAL DE NUEVA CONSULTA
// ============================================================
function showNewConversationModal() {
    const modal = document.getElementById('newConversationModal');
    if (modal) {
        modal.classList.add('active');
        // Resetear formulario
        const form = document.getElementById('newChatForm');
        if (form) form.reset();
        document.getElementById('tplPreview').textContent = '';
        document.getElementById('searchResults').innerHTML = '';
    }
}

function closeNewConversationModal() {
    const modal = document.getElementById('newConversationModal');
    if (modal) modal.classList.remove('active');
}

// ============================================================
// PLANTILLAS DE MENSAJE (Turista)
// ============================================================
const messageTemplates = {
    'informacion': 'Hola, me gustaría recibir más información sobre este alojamiento. ¿Podrían indicarme los detalles de disponibilidad y precios? Muchas gracias.',
    'reserva': 'Buenos días, estoy interesado en hacer una reserva. ¿Me podrían informar sobre las fechas disponibles y el proceso de reserva?',
    'actividades': 'Hola, me gustaría saber qué actividades ofrecen en la zona. ¿Tienen rutas de senderismo, visitas guiadas u otras experiencias?',
    'personalizado': ''
};

function selectTemplate(templateKey) {
    // Actualizar visual
    document.querySelectorAll('.tpl-opt').forEach(el => el.classList.remove('selected'));
    const selectedEl = document.querySelector(`.tpl-opt[data-template="${templateKey}"]`);
    if (selectedEl) selectedEl.classList.add('selected');
    
    // Actualizar preview
    const preview = document.getElementById('tplPreview');
    const messageField = document.getElementById('inquiryMessage');
    
    if (templateKey === 'personalizado') {
        preview.textContent = 'Escribe tu mensaje personalizado...';
        if (messageField) {
            messageField.value = '';
            messageField.focus();
        }
    } else {
        const template = messageTemplates[templateKey] || '';
        preview.textContent = template;
        if (messageField) messageField.value = template;
    }
}

function refreshTplPreview() {
    const messageField = document.getElementById('inquiryMessage');
    const preview = document.getElementById('tplPreview');
    if (messageField && preview) {
        preview.textContent = messageField.value || 'Escribe tu mensaje...';
    }
}

// ============================================================
// BÚSQUEDA DE ALOJAMIENTOS (para asociar consulta)
// ============================================================
async function searchAccommodations(query) {
    if (query.length < 2) {
        document.getElementById('searchResults').innerHTML = '';
        return;
    }
    
    try {
        const response = await fetch(`api/search_accommodation.php?q=${encodeURIComponent(query)}&limit=5`);
        const data = await response.json();
        
        const resultsContainer = document.getElementById('searchResults');
        if (!resultsContainer) return;
        
        if (data.success && data.data && data.data.length > 0) {
            resultsContainer.innerHTML = data.data.map(acc => `
                <div class="search-item" onclick="selectAccommodation(${acc.id}, '${acc.name.replace(/'/g, "\\'")}')">
                    <i class="fas fa-bed"></i>
                    <div>
                        <strong>${acc.name}</strong>
                        <div style="font-size: 0.8rem; color: #888;">${acc.municipality || ''}, ${acc.province || ''}</div>
                    </div>
                </div>
            `).join('');
        } else {
            resultsContainer.innerHTML = '<div style="padding: 1rem; color: #888; text-align: center;">Sin resultados. Se enviará como consulta general.</div>';
        }
    } catch (error) {
        console.error('Error buscando alojamientos:', error);
    }
}

let selectedAccommodationId = null;
let selectedAccommodationName = '';

function selectAccommodation(id, name) {
    selectedAccommodationId = id;
    selectedAccommodationName = name;
    
    const searchInput = document.getElementById('searchAccommodation');
    if (searchInput) searchInput.value = name;
    
    document.getElementById('searchResults').innerHTML = '';
}

// ============================================================
// ENVIAR CONSULTA (con lógica de Tablón si no hay destino)
// ============================================================
async function submitNewChat() {
    const message = document.getElementById('inquiryMessage').value.trim();
    const zone = document.getElementById('inquiryZone').value.trim();
    const persons = document.getElementById('inquiryPersons').value || 2;
    const checkIn = document.getElementById('inquiryCheckIn').value;
    const checkOut = document.getElementById('inquiryCheckOut').value;
    
    if (!message) {
        alert('Por favor, escribe un mensaje para tu consulta.');
        return;
    }
    
    const submitBtn = document.getElementById('submitInquiryBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    }
    
    try {
        const response = await fetch('api/submit_inquiry.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: message,
                zone: zone,
                persons: persons,
                check_in: checkIn,
                check_out: checkOut
            })
        });
        const data = await response.json();
        
        if (data.success) {
            // Mostrar mensaje según resultado
            let msg = data.message || 'Consulta enviada correctamente';
            
            // Si es una consulta sin destino (bulletin), mostrar mensaje específico
            if (data.data && data.data.pending_inquiry) {
                msg = data.data.message || 'Tu consulta se ha publicado en el Tablón de Solicitudes. Los propietarios de la zona podrán responderte.';
            }
            
            alert(msg);
            closeNewConversationModal();
            
            // Recargar conversaciones
            if (typeof loadConversations === 'function') {
                await loadConversations();
            }
        } else {
            alert('Error: ' + (data.message || 'Error al enviar la consulta'));
        }
    } catch (error) {
        console.error('Error enviando consulta:', error);
        alert('Error de conexión. Por favor, inténtalo de nuevo.');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Consulta';
        }
    }
}

// ============================================================
// TABLÓN DE SOLICITUDES (REQ 3 — Para Propietarios)
// ============================================================
async function loadBulletinRequests() {
    try {
        const response = await fetch('api/bulletin_requests.php');
        const data = await response.json();
        
        const container = document.getElementById('bulletin-board-list');
        if (!container) return;
        
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = data.data.map(req => `
                <div class="bulletin-card">
                    <div class="bulletin-header">
                        <div>
                            <strong style="color: var(--primary-color);">${req.first_name || 'Viajero'} ${req.last_name || ''}</strong>
                            <span class="bulletin-meta"> · ${getTimeAgo(req.created_at)}</span>
                        </div>
                        ${req.zone ? `<span style="background: #e8f5e9; color: var(--primary-color); padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;"><i class="fas fa-map-marker-alt"></i> ${req.zone}</span>` : ''}
                    </div>
                    <p style="color: #555; margin: 0.5rem 0;">${req.message}</p>
                    <div class="bulletin-meta">
                        ${req.persons ? `<span><i class="fas fa-users"></i> ${req.persons} personas</span>` : ''}
                        ${req.check_in ? ` · <span><i class="fas fa-calendar-check"></i> ${req.check_in}${req.check_out ? ` → ${req.check_out}` : ''}</span>` : ''}
                    </div>
                    <div class="bulletin-actions">
                        <button class="btn-respond" onclick="respondToBulletin(${req.id}, ${req.user_id})">
                            <i class="fas fa-reply"></i> Responder
                        </button>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div style="text-align: center; padding: 3rem; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <i class="fas fa-bullhorn" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <p style="color: #666;">No hay solicitudes abiertas en este momento.</p>
                    <p style="color: #999; font-size: 0.9rem;">Las solicitudes de viajeros aparecerán aquí cuando no especifiquen un destino concreto.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error cargando solicitudes:', error);
    }
}

async function respondToBulletin(requestId, touristId) {
    if (!confirm('¿Quieres responder a esta solicitud? Se abrirá una conversación con el viajero.')) return;
    
    try {
        const response = await fetch('api/chat.php?action=start_conversation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                recipient_id: touristId,
                entity_type: 'bulletin_response',
                entity_id: requestId,
                initiator_role: 'anfitrion'
            })
        });
        const data = await response.json();
        
        if (data.success) {
            alert('¡Conversación iniciada! Puedes verla en la sección de Mensajes.');
            // Cambiar a la sección de mensajes
            showSection('mensajes');
            // Seleccionar la conversación creada
            if (data.data && data.data.conversation_id) {
                setTimeout(() => selectConversation(data.data.conversation_id), 500);
            }
        } else {
            alert('Error: ' + (data.message || 'No se pudo iniciar la conversación'));
        }
    } catch (error) {
        console.error('Error respondiendo a solicitud:', error);
        alert('Error de conexión');
    }
}

// ============================================================
// INICIALIZACIÓN
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar modal al hacer clic fuera
    const modal = document.getElementById('newConversationModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeNewConversationModal();
        });
    }
    
    // Búsqueda de alojamientos con debounce
    const searchInput = document.getElementById('searchAccommodation');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => searchAccommodations(this.value), 300);
        });
    }
});
