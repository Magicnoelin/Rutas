/* ============================================================
   dashboard-messages.js — Sistema de Mensajería con Doble Rol
   Funciones: loadConversations, selectConversation, loadMessages,
              renderMessages, setupMessageSending, handleSectionChange,
              initChatTabs, switchChatTab, startPolling, stopPolling
   ============================================================ */

let allConversations = [];
let activeChatTab = 'turista'; // 'turista' | 'anfitrion'

// ============================================================
// HANDLE SECTION CHANGE — se llama desde dashboard-core.js
// ============================================================
function handleSectionChange(sectionId) {
    if (sectionId === 'mensajes') {
        loadConversations();
        startPolling();
    } else {
        stopPolling();
    }
}

// ============================================================
// INICIALIZAR TABS DE CHAT (REQ 2)
// ============================================================
function initChatTabs() {
    const tabTurista = document.getElementById('chatTabTurista');
    const tabAnfitrion = document.getElementById('chatTabAnfitrion');
    
    if (!tabTurista || !tabAnfitrion) return;
    
    // Mostrar/ocultar tab de anfitrión según roles
    const roles = window.myRolesList || [];
    const hasProviderRole = roles.some(r => r.slug !== 'turista');
    
    if (!hasProviderRole) {
        tabAnfitrion.style.display = 'none';
    }
    
    tabTurista.addEventListener('click', () => switchChatTab('turista'));
    tabAnfitrion.addEventListener('click', () => switchChatTab('anfitrion'));
}

function switchChatTab(tab) {
    activeChatTab = tab;
    
    const tabTurista = document.getElementById('chatTabTurista');
    const tabAnfitrion = document.getElementById('chatTabAnfitrion');
    
    if (tabTurista) tabTurista.classList.toggle('active', tab === 'turista');
    if (tabAnfitrion) tabAnfitrion.classList.toggle('active', tab === 'anfitrion');
    
    // Filtrar conversaciones según tab activo
    filterConversationsByTab();
}

function filterConversationsByTab() {
    const chatList = document.getElementById('chatList');
    if (!chatList) return;
    
    const filtered = allConversations.filter(conv => {
        if (activeChatTab === 'turista') {
            // Como turista: conversaciones donde yo soy user_1_id (yo inicié)
            // o donde entity_type es 'inquiry' (consultas que hice)
            return parseInt(conv.user_1_id) === currentUserId || 
                   conv.entity_type === 'inquiry';
        } else {
            // Como anfitrión: conversaciones donde yo soy el provider/other_user
            return parseInt(conv.other_user_id) === currentUserId ||
                   parseInt(conv.provider_id) === currentUserId;
        }
    });
    
    renderConversationList(filtered);
}

// ============================================================
// CARGAR CONVERSACIONES
// ============================================================
async function loadConversations() {
    try {
        const response = await fetch('api/chat.php?action=list_conversations');
        const data = await response.json();
        
        if (data.success) {
            allConversations = data.data || [];
            filterConversationsByTab();
            
            // Actualizar badges en tabs
            updateChatBadges();
        }
    } catch (error) {
        console.error('Error cargando conversaciones:', error);
    }
}

function updateChatBadges() {
    const badgeTurista = document.getElementById('badgeTurista');
    const badgeAnfitrion = document.getElementById('badgeAnfitrion');
    
    if (badgeTurista) {
        const count = allConversations.filter(c => 
            parseInt(c.user_1_id) === currentUserId || c.entity_type === 'inquiry'
        ).length;
        badgeTurista.textContent = count;
        badgeTurista.style.display = count > 0 ? 'inline-block' : 'none';
    }
    
    if (badgeAnfitrion) {
        const count = allConversations.filter(c => 
            parseInt(c.other_user_id) === currentUserId || parseInt(c.provider_id) === currentUserId
        ).length;
        badgeAnfitrion.textContent = count;
        badgeAnfitrion.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

// ============================================================
// RENDERIZAR LISTA DE CONVERSACIONES
// ============================================================
function renderConversationList(conversations) {
    const chatList = document.getElementById('chatList');
    if (!chatList) return;
    
    if (conversations.length === 0) {
        chatList.innerHTML = `
            <div style="padding: 2rem; text-align: center; color: #888;">
                <i class="fas fa-comments" style="font-size: 2rem; margin-bottom: 1rem; color: #ddd;"></i>
                <p>No hay conversaciones</p>
            </div>
        `;
        return;
    }
    
    chatList.innerHTML = conversations.map(conv => {
        const isActive = currentConversationId === conv.conversation_id;
        const name = conv.first_name + ' ' + conv.last_name;
        const initial = (conv.first_name || '?')[0];
        const avatar = conv.avatar_url 
            ? `<img src="${conv.avatar_url}" alt="${name}">`
            : `<span style="font-size: 1.2rem; font-weight: 600; color: white;">${initial}</span>`;
        const lastMsg = conv.last_message || 'Sin mensajes';
        const unread = conv.unread_count || 0;
        const timeAgo = getTimeAgo(conv.last_message_at);
        
        return `
            <div class="chat-item ${isActive ? 'active' : ''}" onclick="selectConversation(${conv.conversation_id})">
                <div class="chat-avatar" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
                    ${avatar}
                </div>
                <div class="chat-info" style="flex: 1; min-width: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong style="color: var(--primary-color);">${name}</strong>
                        <span style="font-size: 0.75rem; color: #999;">${timeAgo}</span>
                    </div>
                    <p style="margin: 0; color: #888; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ${lastMsg.substring(0, 60)}${lastMsg.length > 60 ? '...' : ''}
                    </p>
                </div>
                ${unread > 0 ? `<span style="background: var(--secondary-color); color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0;">${unread}</span>` : ''}
            </div>
        `;
    }).join('');
}

// ============================================================
// SELECCIONAR UNA CONVERSACIÓN
// ============================================================
async function selectConversation(conversationId) {
    currentConversationId = conversationId;
    
    // Actualizar visual de la lista
    document.querySelectorAll('.chat-item').forEach(el => el.classList.remove('active'));
    const selectedItem = document.querySelector(`.chat-item[onclick*="${conversationId}"]`);
    if (selectedItem) selectedItem.classList.add('active');
    
    // Mostrar ventana de chat
    const chatWindow = document.getElementById('chatWindow');
    if (chatWindow) chatWindow.style.display = 'flex';
    
    // Cargar mensajes
    await loadMessages(conversationId);
}

// ============================================================
// CARGAR MENSAJES DE UNA CONVERSACIÓN
// ============================================================
async function loadMessages(conversationId) {
    try {
        const response = await fetch(`api/chat.php?action=get_messages&conversation_id=${conversationId}`);
        const data = await response.json();
        
        if (data.success) {
            renderMessages(data.data || []);
        }
    } catch (error) {
        console.error('Error cargando mensajes:', error);
    }
}

// ============================================================
// RENDERIZAR MENSAJES
// ============================================================
function renderMessages(messages) {
    const container = document.getElementById('messagesContainer');
    if (!container) return;
    
    if (messages.length === 0) {
        container.innerHTML = '<div style="text-align: center; padding: 2rem; color: #888;">No hay mensajes aún. ¡Escribe el primero!</div>';
        return;
    }
    
    container.innerHTML = messages.map(msg => {
        const isMine = parseInt(msg.sender_id) === currentUserId;
        const time = new Date(msg.created_at).toLocaleString('es-ES', {
            hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'short'
        });
        
        return `
            <div style="display: flex; justify-content: ${isMine ? 'flex-end' : 'flex-start'}; margin-bottom: 1rem;">
                <div style="max-width: 75%; background: ${isMine ? 'var(--primary-color)' : '#f0f0f0'}; color: ${isMine ? 'white' : '#333'}; padding: 0.8rem 1rem; border-radius: ${isMine ? '15px 15px 5px 15px' : '15px 15px 15px 5px'};">
                    <div style="font-size: 0.95rem; line-height: 1.4;">${msg.content}</div>
                    <div style="font-size: 0.7rem; margin-top: 0.3rem; opacity: 0.7; text-align: ${isMine ? 'right' : 'left'};">${time}</div>
                </div>
            </div>
        `;
    }).join('');
    
    // Scroll al último mensaje
    container.scrollTop = container.scrollHeight;
}

// ============================================================
// CONFIGURAR ENVÍO DE MENSAJES
// ============================================================
function setupMessageSending() {
    const sendBtn = document.getElementById('sendMessageBtn');
    const messageInput = document.getElementById('messageInput');
    
    if (!sendBtn || !messageInput) return;
    
    async function sendMessage() {
        const content = messageInput.value.trim();
        if (!content || !currentConversationId) return;
        
        // Deshabilitar botón temporalmente
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        try {
            const response = await fetch('api/chat.php?action=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conversation_id: currentConversationId,
                    content: content
                })
            });
            const data = await response.json();
            
            if (data.success) {
                messageInput.value = '';
                // Recargar mensajes
                await loadMessages(currentConversationId);
                // Recargar conversaciones para actualizar último mensaje
                await loadConversations();
            } else {
                alert('Error al enviar: ' + (data.message || 'Error desconocido'));
            }
        } catch (error) {
            console.error('Error enviando mensaje:', error);
            alert('Error de conexión al enviar el mensaje');
        } finally {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
        }
    }
    
    sendBtn.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
}

// ============================================================
// POLLING — Recargar mensajes periódicamente
// ============================================================
function startPolling() {
    stopPolling();
    messagePollInterval = setInterval(() => {
        if (currentConversationId) {
            loadMessages(currentConversationId);
        }
        loadConversations();
    }, 10000); // cada 10 segundos
}

function stopPolling() {
    if (messagePollInterval) {
        clearInterval(messagePollInterval);
        messagePollInterval = null;
    }
}

// ============================================================
// INICIALIZACIÓN
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    initChatTabs();
    setupMessageSending();
});
