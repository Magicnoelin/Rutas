/* ============================================================
   dashboard-core.js — Núcleo del Dashboard
   Funciones: showSection, init, handleSectionChange, logout,
              abrirGestionFotos, togglePreference, getTimeAgo
   ============================================================ */

// Variables globales compartidas entre módulos
let currentUserId = null;
let messagePollInterval = null;
let currentConversationId = null;

// ============================================================
// NAVEGACIÓN ENTRE SECCIONES
// ============================================================
function showSection(sectionId) {
    document.querySelectorAll('.dashboard-section').forEach(section => {
        section.classList.remove('active');
    });
    const targetSection = document.getElementById(sectionId + '-section');
    if (targetSection) {
        targetSection.classList.add('active');
    }

    // Notificar a otros módulos del cambio de sección
    if (typeof handleSectionChange === 'function') {
        handleSectionChange(sectionId);
    }
}

// ============================================================
// GESTIÓN DE FOTOS
// ============================================================
function abrirGestionFotos() {
    window.open('/gestion-fotos-universal.html', '_blank');
}

// ============================================================
// PREFERENCIAS — toggle visual
// ============================================================
function togglePreference(el) {
    el.classList.toggle('selected');
    const checkbox = el.querySelector('input[type="checkbox"]');
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
    }
}

// ============================================================
// UTILIDAD: tiempo transcurrido
// ============================================================
function getTimeAgo(timestamp) {
    const now = new Date();
    const past = new Date(timestamp);
    const diffMs = now - past;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Ahora';
    if (diffMins < 60) return `Hace ${diffMins} min`;
    if (diffHours < 24) return `Hace ${diffHours} h`;
    if (diffDays < 7) return `Hace ${diffDays} d`;
    return past.toLocaleDateString('es-ES');
}

// ============================================================
// INICIALIZACIÓN PRINCIPAL
// ============================================================
document.addEventListener('DOMContentLoaded', async function() {
    console.log('🚀 Dashboard inicializando...');

    // 1. Cargar perfil del usuario
    if (typeof loadUserProfile === 'function') {
        await loadUserProfile();
    }

    // 2. Cargar membresía
    if (typeof loadMembershipData === 'function') {
        await loadMembershipData();
    }

    // 3. Cargar recursos del usuario (alojamientos, etc.)
    if (typeof loadUserResources === 'function') {
        await loadUserResources();
    }

    // 4. Cargar favoritos
    if (typeof loadFavorites === 'function') {
        await loadFavorites();
    }

    // 5. Cargar Pasaporte Rural
    if (typeof cargarPasaporteUser === 'function') {
        await cargarPasaporteUser();
    }

    // 6. Inicializar Role Context Bar
    if (typeof initRoleContextBar === 'function') {
        await initRoleContextBar();
    }

    // 7. Manejar parámetros de URL (contact desde ficha pública)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'contact') {
        showSection('mensajes');
    }

    console.log('✅ Dashboard listo');
});

// ============================================================
// LOGOUT
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            try {
                await fetch('api/logout.php');
                window.location.href = '/index.html';
            } catch (error) {
                console.error('Error al cerrar sesión:', error);
                window.location.href = '/index.html';
            }
        });
    }
});
