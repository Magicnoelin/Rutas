/* ============================================================
   dashboard-role-context.js — Role Context Bar (REQ 1)
   Funciones: initRoleContextBar, setActiveRole, hasProviderRoles,
              showCreateServiceCTA
   ============================================================ */

let currentActiveRole = 'turista'; // 'turista' | 'anfitrion'

// ============================================================
// INICIALIZAR ROLE CONTEXT BAR
// ============================================================
async function initRoleContextBar() {
    const bar = document.getElementById('roleContextBar');
    if (!bar) return;
    
    // Determinar si el usuario tiene roles de proveedor
    const roles = window.myRolesList || [];
    const hasProvider = roles.some(r => r.slug !== 'turista');
    
    // Establecer modo inicial
    if (hasProvider) {
        // Si tiene roles de proveedor, preguntar al sistema cuál fue el último usado
        const savedRole = localStorage.getItem('dashboard_active_role') || 'turista';
        currentActiveRole = savedRole;
    } else {
        currentActiveRole = 'turista';
    }
    
    // Actualizar body con data-mode
    document.body.setAttribute('data-mode', currentActiveRole);
    
    // Renderizar la barra
    renderRoleContextBar(hasProvider);
    
    // Mostrar/ocultar CTA de crear servicio
    showCreateServiceCTA(!hasProvider);
}

// ============================================================
// RENDERIZAR BARRA DE CONTEXTO
// ============================================================
function renderRoleContextBar(hasProvider) {
    const bar = document.getElementById('roleContextBar');
    if (!bar) return;
    
    const isTurista = currentActiveRole === 'turista';
    
    bar.innerHTML = `
        <div class="role-mode-indicator">
            <i class="fas ${isTurista ? 'fa-hiking' : 'fa-bed'}" 
               style="color: var(--primary-color); font-size: 1.2rem;"></i>
            <span style="font-weight: 600; color: var(--primary-color);">
                ${isTurista ? '🌿 Navegando como Turista' : '🏡 Navegando como Anfitrión'}
            </span>
        </div>
        <div class="role-toggle-group">
            <button class="role-toggle-btn ${isTurista ? 'active' : ''}" 
                    onclick="setActiveRole('turista')"
                    ${isTurista ? '' : ''}>
                <i class="fas fa-hiking"></i> Turista
            </button>
            <button class="role-toggle-btn ${!isTurista ? 'active' : ''}" 
                    onclick="setActiveRole('anfitrion')"
                    ${hasProvider ? '' : 'disabled'}
                    style="${!hasProvider ? 'opacity: 0.4; cursor: not-allowed;' : ''}">
                <i class="fas fa-bed"></i> Anfitrión
            </button>
        </div>
        ${!hasProvider ? `
            <button class="cta-btn" onclick="showCreateServiceCTA()">
                <i class="fas fa-plus-circle"></i> Dar de alta mi alojamiento o servicio rural
            </button>
        ` : `
            <button class="cta-btn" onclick="showSection('mis-alojamientos')" style="background: var(--primary-color);">
                <i class="fas fa-cog"></i> Gestionar mis servicios
            </button>
        `}
    `;
}

// ============================================================
// CAMBIAR ROL ACTIVO
// ============================================================
function setActiveRole(role) {
    if (role === currentActiveRole) return;
    
    // Si intenta cambiar a anfitrión pero no tiene roles, redirigir
    const roles = window.myRolesList || [];
    const hasProvider = roles.some(r => r.slug !== 'turista');
    
    if (role === 'anfitrion' && !hasProvider) {
        showCreateServiceCTA();
        return;
    }
    
    currentActiveRole = role;
    localStorage.setItem('dashboard_active_role', role);
    
    // Actualizar body con data-mode (para CSS visibility)
    document.body.setAttribute('data-mode', role);
    
    // Re-renderizar la barra
    renderRoleContextBar(hasProvider);
    
    // Disparar evento personalizado para que otros módulos reaccionen
    const event = new CustomEvent('roleChanged', { detail: { role: role } });
    document.dispatchEvent(event);
    
    // Si cambia a anfitrión, mostrar sección de gestión
    if (role === 'anfitrion') {
        showSection('mis-alojamientos');
    } else {
        showSection('inicio');
    }
}

// ============================================================
// CTA: DAR DE ALTA SERVICIO
// ============================================================
function showCreateServiceCTA() {
    // Abrir modal de roles para que el usuario seleccione un rol de proveedor
    if (typeof abrirModalRoles === 'function') {
        abrirModalRoles();
    } else {
        // Fallback: redirigir a página de registro de alojamiento
        window.open('/agregar-alojamiento.html', '_blank');
    }
}

// ============================================================
// ESCUCHAR CAMBIOS DE ROL DESDE OTROS MÓDULOS
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Si el usuario actualiza roles, refrescar la barra
    document.addEventListener('rolesUpdated', function() {
        initRoleContextBar();
    });
});
