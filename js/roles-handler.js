// js/roles-handler.js

// Función Maestra: Refresca toda la interfaz según los roles
async function refrescarInterfazRoles() {
    try {
        const resp = await fetch('api/roles.php?action=mis_roles');
        const r = await resp.json();
        if (!r.success) return;

        const slugs = r.data.map(rol => rol.slug);

        // 1. Mostrar/Ocultar Menús laterales
        const menuMapping = {
            'alojamiento': 'nav-mis-alojamientos',
            'actividad_cultural': 'nav-mis-actividades',
            'promotor_eventos': 'nav-mis-eventos'
        };

        Object.keys(menuMapping).forEach(slug => {
            const el = document.getElementById(menuMapping[slug]);
            if (el) el.style.display = slugs.includes(slug) ? 'block' : 'none';
        });

        // 2. Pintar las etiquetas de rol en el perfil
        const badgeContainer = document.getElementById('inicio-roles-badges');
        if (badgeContainer) {
            badgeContainer.innerHTML = r.data.map(rol => 
                `<span class="badge" style="background: var(--primary-color)">${rol.nombre}</span>`
            ).join(' ');
        }

    } catch (e) { console.error("Error cargando roles:", e); }
}

// Función para guardar desde el modal
async function guardarRolesModal() {
    const checks = document.querySelectorAll('#modal-roles-grid input[type="checkbox"]:checked');
    const roles = Array.from(checks).map(c => c.value);

    const resp = await fetch('api/update_role.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ roles: roles })
    });

    const res = await resp.json();
    if (res.success) {
        location.reload();
    } else {
        alert("Error: " + res.message);
    }
}