/* ============================================================
   dashboard-profile.js — Perfil, Roles y Preferencias
   Funciones: loadUserProfile, loadUserRoles, abrirModalRoles,
              cerrarModalRoles, cargarRolesEnModal, guardarRoles,
              getRoleIcon, avatar upload, profile form, preferences
   ============================================================ */

// ============================================================
// CARGA DEL PERFIL DEL USUARIO
// ============================================================
async function loadUserProfile() {
    try {
        const response = await fetch('api/get_profile.php');
        const data = await response.json();
        
        if (data.success) {
            const user = data.data;
            
            // Guardar ID del usuario para el sistema de mensajes
            if (user.id) {
                currentUserId = user.id;
            }
            
            // Actualizar nombre en el header
            const userNameDisplay = document.getElementById('userNameDisplay');
            if (userNameDisplay) {
                userNameDisplay.textContent = user.first_name || 'Viajero';
            }
            
            // Actualizar datos del perfil
            const profileName = document.getElementById('profileName');
            if (profileName) {
                profileName.textContent = (user.first_name || '') + ' ' + (user.last_name || '');
            }
            
            // Cargar y mostrar el avatar si existe
            const avatarDisplay = document.getElementById('avatarDisplay');
            if (avatarDisplay && user.avatar_url) {
                avatarDisplay.innerHTML = `<img src="${user.avatar_url}" alt="Foto de perfil" style="width: 100%; height: 100%; object-fit: cover;">`;
            } else if (avatarDisplay) {
                avatarDisplay.innerHTML = '<i class="fas fa-user"></i>';
            }

            const profileEmail = document.getElementById('profileEmail');
            if (profileEmail) {
                profileEmail.textContent = user.email || '';
            }
            
            // Actualizar campos del formulario
            const firstName = document.getElementById('firstName');
            if (firstName) firstName.value = user.first_name || '';
            
            const lastName = document.getElementById('lastName');
            if (lastName) lastName.value = user.last_name || '';
            
            const phone = document.getElementById('phone');
            if (phone) phone.value = user.phone || '';
            
            const email = document.getElementById('email');
            if (email) email.value = user.email || '';
            
            console.log('Usuario cargado:', user);
        } else {
            console.warn('No se pudo cargar el perfil:', data.error);
        }
        
        // Cargar los roles del sistema de roles
        await loadUserRoles();
        
    } catch (error) {
        console.error('Error cargando perfil:', error);
    }
}

// ============================================================
// CARGA DE ROLES DEL USUARIO
// ============================================================
async function loadUserRoles() {
    try {
        const respRoles = await fetch('api/roles.php?action=mis_roles');
        const datosRoles = await respRoles.json();
        
        if (datosRoles.success && datosRoles.data && datosRoles.data.length > 0) {
            // Actualizar badge de rol en el perfil
            const profileRole = document.getElementById('profileRole');
            
            // Guardar roles globalmente para el sistema de mensajes
            window.myRolesList = datosRoles.data;

            if (profileRole) {
                const rolesNombres = datosRoles.data.map(r => r.nombre).join(', ');
                profileRole.textContent = rolesNombres;
            }
            
            // Actualizar badges de roles en el inicio
            const inicioRolesBadges = document.getElementById('inicio-roles-badges');
            if (inicioRolesBadges) {
                inicioRolesBadges.innerHTML = datosRoles.data.map(rol => {
                    const icon = getRoleIcon(rol.slug);
                    return '<span style="background:var(--primary-color);color:white;padding:.3rem .8rem;border-radius:15px;font-size:.85rem;margin-right:.3rem;">' + icon + ' ' + rol.nombre + '</span>';
                }).join('');
            }
            
            // Actualizar lista de roles en el perfil
            const profileRolesList = document.getElementById('profile-roles-list');
            if (profileRolesList) {
                profileRolesList.innerHTML = datosRoles.data.map(rol => {
                    const icon = getRoleIcon(rol.slug);
                    return '<span style="background:#e8f5e9;color:var(--primary-color);padding:.4rem .8rem;border-radius:15px;font-size:.85rem;margin-right:.3rem;font-weight:600;">' + icon + ' ' + rol.nombre + '</span>';
                }).join('');
            }
            
            console.log('Roles cargados:', datosRoles.data);
        } else {
            // No tiene roles, mostrar Turista por defecto
            const profileRole = document.getElementById('profileRole');
            if (profileRole) {
                profileRole.textContent = 'Turista';
            }
            
            const inicioRolesBadges = document.getElementById('inicio-roles-badges');
            if (inicioRolesBadges) {
                inicioRolesBadges.innerHTML = '<span style="background:#eee;color:#888;padding:.3rem .8rem;border-radius:15px;font-size:.85rem;">Sin roles asignados</span>';
            }
            
            const profileRolesList = document.getElementById('profile-roles-list');
            if (profileRolesList) {
                profileRolesList.innerHTML = '<span style="background:#e8f5e9;color:var(--primary-color);padding:.4rem .8rem;border-radius:15px;font-size:.85rem;font-weight:600;"><i class="fas fa-hiking"></i> Turista</span>';
            }
        }
    } catch (error) {
        console.error('Error cargando roles:', error);
        const profileRole = document.getElementById('profileRole');
        if (profileRole) {
            profileRole.textContent = 'Turista';
        }
    }
}

// ============================================================
// MODAL DE ROLES
// ============================================================
async function abrirModalRoles() {
    const modal = document.getElementById('modalRolesUsuario');
    if (modal) {
        modal.style.display = 'flex';
        await cargarRolesEnModal();
    } else {
        console.error("No se encontró el modal");
    }
}

function cerrarModalRoles() {
    const modal = document.getElementById('modalRolesUsuario');
    if (modal) modal.style.display = 'none';
}

async function cargarRolesEnModal() {
    const grid = document.getElementById('modal-roles-grid');
    if (!grid) return;
    
    grid.innerHTML = '<div style="text-align:center;padding:2rem;color:#aaa;grid-column:1/-1;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
    
    try {
        const respRoles = await fetch('api/roles.php?action=list_roles');
        const datosRoles = await respRoles.json();
        const respMisRoles = await fetch('api/roles.php?action=mis_roles');
        const datosMisRoles = await respMisRoles.json();
        
        if (!datosRoles.success || !datosMisRoles.success) {
            grid.innerHTML = '<div style="text-align:center;padding:2rem;color:#f88;grid-column:1/-1;">Error cargando roles</div>';
            return;
        }
        
        const rolesDisponibles = datosRoles.data;
        const misRoles = datosMisRoles.data.map(r => r.slug);
        
        grid.innerHTML = rolesDisponibles.map(rol => {
            const checked = misRoles.includes(rol.slug) ? 'checked' : '';
            const icon = getRoleIcon(rol.slug);
            return '<label class="role-option" style="display:flex;align-items:center;gap:10px;padding:1rem;border:2px solid ' + (checked ? 'var(--primary-color)' : '#eee') + ';border-radius:10px;cursor:pointer;transition:all 0.2s;">' +
                '<input type="checkbox" value="' + rol.slug + '" ' + checked + ' style="width:18px;height:18px;accent-color:var(--primary-color);">' +
                '<div style="flex:1;"><div style="font-weight:600;color:var(--primary-color);">' + icon + ' ' + rol.nombre + '</div><div style="font-size:0.8rem;color:#888;">' + (rol.descripcion || '') + '</div></div>' +
                '</label>';
        }).join('');
    } catch (error) {
        console.error("Error:", error);
        grid.innerHTML = '<div style="text-align:center;padding:2rem;color:#f88;grid-column:1/-1;">Error de conexión</div>';
    }
}

function getRoleIcon(slug) {
    const icons = { 
        'turista': '<i class="fas fa-hiking"></i>', 
        'alojamiento': '<i class="fas fa-bed"></i>', 
        'actividad_cultural': '<i class="fas fa-music"></i>', 
        'promotor_eventos': '<i class="fas fa-calendar-star"></i>', 
        'restaurante': '<i class="fas fa-utensils"></i>', 
        'bodega': '<i class="fas fa-wine-glass"></i>', 
        'guia_turistico': '<i class="fas fa-map-signs"></i>',
        'empresa_actividades': '<i class="fas fa-mountain"></i>',
        'transporte_turistico': '<i class="fas fa-bus"></i>',
        'agricultor_ganadero': '<i class="fas fa-tractor"></i>',
        'ayuntamiento': '<i class="fas fa-landmark"></i>',
        'organizador_eventos': '<i class="fas fa-calendar-check"></i>'
    };
    return icons[slug] || '<i class="fas fa-user-tag"></i>';
}

async function guardarRoles() {
    const checks = document.querySelectorAll('#modal-roles-grid input[type="checkbox"]:checked');
    const roles = Array.from(checks).map(c => c.value);
    
    if (roles.length === 0) { 
        alert("Debes seleccionar al menos un rol."); 
        return; 
    }
    
    // Límite de 2 roles para plan gratuito
    if (roles.length > 2) {
        alert("El plan gratuito solo permite tener 2 roles activos. Para tener más roles, actualiza a un plan Premium en 'Mi Membresía'.");
        return;
    }
    
    try {
        const response = await fetch('api/roles.php', {
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'actualizar_mis_roles', roles: roles })
        });
        const data = await response.json();
        if (data.success) { 
            alert("¡Roles actualizados correctamente!"); 
            cerrarModalRoles(); 
            location.reload(); 
        } else { 
            alert("Error: " + (data.message || "Error")); 
        }
    } catch (error) { 
        alert("Error de conexión"); 
    }
}

// ============================================================
// AVATAR UPLOAD
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Validar tamaño (ej. máx 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('La imagen es demasiado grande. Máximo 2MB.');
                return;
            }

            const formData = new FormData();
            formData.append('avatar', file);

            // Feedback visual
            const avatarDisplay = document.getElementById('avatarDisplay');
            const originalContent = avatarDisplay.innerHTML;
            avatarDisplay.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const response = await fetch('api/upload_avatar.php', { method: 'POST', body: formData });
                const uploadResult = await response.json();

                if (uploadResult.success) {
                    const avatarUrl = uploadResult.data.url;
                    avatarDisplay.innerHTML = `<img src="${avatarUrl}" alt="Foto de perfil" style="width: 100%; height: 100%; object-fit: cover;">`;
                    
                    // Ahora, guardar la URL en el perfil del turista
                    const saveResponse = await fetch('api/profile_turista.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ avatar_url: avatarUrl })
                    });
                    const saveResult = await saveResponse.json();

                    if (saveResult.success) {
                        console.log('Avatar guardado en el perfil.');
                    } else {
                        alert('Error al guardar el avatar en tu perfil: ' + (saveResult.error || 'Error desconocido'));
                    }

                } else {
                    alert('Error al subir la imagen: ' + (uploadResult.error || 'Error desconocido'));
                    avatarDisplay.innerHTML = originalContent;
                }
            } catch (error) {
                alert('Error al subir la imagen');
                avatarDisplay.innerHTML = originalContent;
            }
        });
    }
});

// ============================================================
// FORMULARIO DE PERFIL
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                first_name: document.getElementById('firstName').value,
                last_name: document.getElementById('lastName').value,
                phone: document.getElementById('phone').value
            };
            
            try {
                const response = await fetch('api/get_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('Perfil actualizado correctamente');
                    // Recargar para reflejar cambios
                    location.reload();
                } else {
                    alert('Error al actualizar: ' + (data.error || 'Error desconocido'));
                }
            } catch (error) {
                alert('Error de conexión al actualizar el perfil');
            }
        });
    }
});

// ============================================================
// FORMULARIO DE PREFERENCIAS
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    const preferencesForm = document.getElementById('preferencesForm');
    if (preferencesForm) {
        preferencesForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const interests = Array.from(document.querySelectorAll('input[name="interests"]:checked')).map(cb => cb.value);
            const budget = document.getElementById('prefBudget').value;
            const duration = document.getElementById('prefDuration').value;
            
            try {
                const response = await fetch('api/save-preferences.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ interests, budget, duration })
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('Preferencias guardadas correctamente');
                } else {
                    alert('Error al guardar: ' + (data.error || 'Error desconocido'));
                }
            } catch (error) {
                alert('Error de conexión al guardar preferencias');
            }
        });
    }
});
