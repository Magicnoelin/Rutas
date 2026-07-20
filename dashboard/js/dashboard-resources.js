/* ============================================================
   dashboard-resources.js — Recursos del Usuario (Alojamientos,
   Actividades, Lugares, Eventos)
   Funciones: loadUserResources, actualizarResumenInicioRecursos,
              createActionCard, cargarMisAlojamientos,
              cargarMisActividades, cargarMisLugares, cargarMisEventos
   ============================================================ */

// ============================================================
// CARGA DE RECURSOS DEL USUARIO
// ============================================================
async function loadUserResources() {
    try {
        const response = await fetch('api/get_user_resources.php');
        const data = await response.json();
        
        if (data.success && data.data) {
            const resources = data.data.resources || {};
            
            // Llamar siempre para limpiar estados de carga o mostrar vacíos
            cargarMisAlojamientos(resources.accommodation || []);
            cargarMisActividades(resources.activity || []);
            cargarMisLugares(resources.place || []);
            cargarMisEventos(resources.event || []);

            // Actualizar resumen en el Inicio (acciones de gestor)
            actualizarResumenInicioRecursos(data.data);
        }
    } catch (error) {
        console.error('Error cargando recursos:', error);
    }
}

// ============================================================
// RESUMEN DE GESTIÓN EN PANTALLA INICIO
// ============================================================
function actualizarResumenInicioRecursos(data) {
    const container = document.getElementById('provider-actions');
    const grid = document.getElementById('provider-actions-grid');
    if (!container || !grid) return;

    const summary = data.summary || {};
    if (summary.total_resources > 0) {
        container.style.display = 'block';
        grid.innerHTML = '';

        if (summary.accommodations > 0) {
            grid.innerHTML += createActionCard('Mis Alojamientos', 'Gestiona tus casas, hoteles y apartamentos.', 'fa-bed', () => showSection('mis-alojamientos'));
        }
        if (summary.activities > 0) {
            grid.innerHTML += createActionCard('Mis Actividades', 'Gestiona tus rutas y experiencias.', 'fa-hiking', () => showSection('mis-actividades'));
        }
        // Botón para subir fotos siempre útil para gestores
        grid.innerHTML += createActionCard('Mis Fotos', 'Gestiona la galería de tus recursos.', 'fa-camera', abrirGestionFotos);
    } else {
        container.style.display = 'none';
    }
}

function createActionCard(title, desc, icon, action) {
    return `
        <div class="role-card" onclick="(${action.toString()})()">
            <div class="role-icon"><i class="fas ${icon}"></i></div>
            <h3>${title}</h3>
            <p>${desc}</p>
            <span class="btn-select-role">Gestionar</span>
        </div>
    `;
}

// ============================================================
// MOSTRAR ALOJAMIENTOS DEL USUARIO
// ============================================================
function cargarMisAlojamientos(alojamientos) {
    const container = document.getElementById('mis-alojamientos-list');
    if (!container) return;
    
    if (alojamientos.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <i class="fas fa-bed" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                <p style="color: #666;">No tienes alojamientos registrados aún.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            ${alojamientos.map(alojamiento => {
                // Robustez: Detectar si los datos vienen en .data o en la raíz
                const d = alojamiento.data || alojamiento;
                const stats = alojamiento.stats || {};
                const photo = d.photo || d.photo1 || null;
                const name = d.name || 'Sin nombre';
                const slug = d.slug || '';
                const id = d.id || '';
                const isActive = d.is_active == 1;
                const isPremium = d.is_premium == 1;
                const tokenPublico = d.token_publico || '';
                
                // Generar badge de premium si aplica
                const premiumBadge = isPremium ? '<span style="background: linear-gradient(135deg,#f59e0b,#d97706); color: white; padding: 3px 8px; border-radius: 5px; font-size: 0.7rem; font-weight: 700; margin-right: 5px;"><i class="fas fa-crown"></i> Premium</span>' : '';
                
                // Generar enlace de checking si es premium y tiene token
                const checkingLink = (isPremium && tokenPublico) ? `<a href="/checking_accommodations/panel.php" target="_blank" style="flex:1;text-align:center;background:#10b981;color:white;padding:0.6rem;border-radius:8px;text-decoration:none;font-size:0.85rem;cursor:pointer;min-width:80px;"><i class="fas fa-clipboard-check"></i> Checking</a>` : '';

                return `
                <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <div style="height: 180px; background: ${photo ? `url('${photo}') center/cover` : '#eee'}; position: relative;">
                        ${!photo ? '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#999;"><i class="fas fa-bed" style="font-size:3rem;"></i></div>' : ''}
                        <span style="position: absolute; top: 10px; right: 10px; background: ${isActive ? '#28a745' : '#dc3545'}; color: white; padding: 4px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;">
                            ${isActive ? 'Activo' : 'Inactivo'}
                        </span>
                    </div>
                    <div style="padding: 1.2rem;">
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-color); font-size: 1.1rem;">${premiumBadge}${name}</h4>
                        <p style="margin: 0 0 1rem 0; color: #666; font-size: 0.9rem;">
                            <i class="fas fa-map-marker-alt"></i> ${d.municipality || ''}, ${d.province || ''}
                        </p>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                            <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;"><i class="fas fa-eye"></i> ${stats.views || 0}</span>
                            <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;"><i class="fas fa-heart"></i> ${stats.favorites || 0}</span>
                            <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;"><i class="fas fa-envelope"></i> ${stats.messages || 0}</span>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="/detalle.html?slug=${slug}" target="_blank" style="flex:1;text-align:center;background:var(--primary-color);color:white;padding:0.6rem;border-radius:8px;text-decoration:none;font-size:0.85rem;cursor:pointer;min-width:80px;"><i class="fas fa-eye"></i> Ver</a>
                            ${checkingLink}
                            <a href="/dashboard/editar-mi-alojamiento.php?id=${id}" target="_blank" style="flex:1;text-align:center;background:#666;color:white;padding:0.6rem;border-radius:8px;text-decoration:none;font-size:0.85rem;cursor:pointer;min-width:80px;"><i class="fas fa-edit"></i> Editar</a>
                            <a href="/gestion-fotos-alojamiento.html?slug=${slug}" target="_blank" style="flex:1;text-align:center;background:#e67e22;color:white;padding:0.6rem;border-radius:8px;text-decoration:none;font-size:0.85rem;cursor:pointer;min-width:80px;"><i class="fas fa-camera"></i> Fotos</a>
                        </div>
                    </div>
                </div>
            `;
            }).join('')}
        </div>
    `;
}

// ============================================================
// MOSTRAR ACTIVIDADES DEL USUARIO
// ============================================================
function cargarMisActividades(actividades) {
    const container = document.getElementById('mis-actividades-list');
    if (!container) return;
    
    if (actividades.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <i class="fas fa-hiking" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                <p style="color: #666;">No tienes actividades registradas aún.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            ${actividades.map(actividad => {
                const d = actividad.data || actividad;
                const stats = actividad.stats || {};
                const photo = d.photo || d.photo1 || null;
                const name = d.name || 'Sin nombre';
                const slug = d.slug || '';
                const id = d.id || '';

                return `
                <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <div style="height: 180px; background: ${photo ? `url('${photo}') center/cover` : '#eee'}; position: relative;">
                        ${!photo ? '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#999;"><i class="fas fa-hiking" style="font-size:3rem;"></i></div>' : ''}
                        <span style="position: absolute; top: 10px; right: 10px; background: ${d.is_active == 1 ? '#28a745' : '#dc3545'}; color: white; padding: 4px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;">
                            ${d.is_active == 1 ? 'Activo' : 'Inactivo'}
                        </span>
                    </div>
                    <div style="padding: 1.2rem;">
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-color); font-size: 1.1rem;">${name}</h4>
                        <p style="margin: 0 0 1rem 0; color: #666; font-size: 0.9rem;">
                            <i class="fas fa-map-marker-alt"></i> ${d.municipality || ''}, ${d.province || ''}
                        </p>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                            <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;"><i class="fas fa-eye"></i> ${stats.views || 0}</span>
                            <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;"><i class="fas fa-heart"></i> ${stats.favorites || 0}</span>
                            <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;"><i class="fas fa-envelope"></i> ${stats.messages || 0}</span>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="/actividad/${slug}" target="_blank" style="flex:1;text-align:center;background:var(--primary-color);color:white;padding:0.6rem;border-radius:8px;text-decoration:none;font-size:0.85rem;cursor:pointer;min-width:80px;"><i class="fas fa-eye"></i> Ver</a>
                            <a href="/dashboard/editar-mi-actividad.php?id=${id}" target="_blank" style="flex:1;text-align:center;background:#666;color:white;padding:0.6rem;border-radius:8px;text-decoration:none;font-size:0.85rem;cursor:pointer;min-width:80px;"><i class="fas fa-edit"></i> Editar</a>
                            <a href="/gestion-fotos-actividades.html?slug=${slug}" target="_blank" style="flex:1;text-align:center;background:#e67e22;color:white;padding:0.6rem;border-radius:8px;text-decoration:none;font-size:0.85rem;cursor:pointer;min-width:80px;"><i class="fas fa-camera"></i> Fotos</a>
                        </div>
                    </div>
                </div>
            `;
            }).join('')}
        </div>
    `;
}

// ============================================================
// MOSTRAR LUGARES DEL USUARIO
// ============================================================
function cargarMisLugares(lugares) {
    const container = document.getElementById('mis-lugares-list');
    if (!container) return;
    
    if (lugares.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <i class="fas fa-map-marker-alt" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                <p style="color: #666;">No tienes lugares registrados aún.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            ${lugares.map(lugar => {
                const d = lugar.data || lugar;
                const stats = lugar.stats || {};
                const photo = d.photo || d.photo1 || null;
                const name = d.name || 'Sin nombre';
                const slug = d.slug || '';
                const id = d.id || '';

                return `
                <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <div style="height: 180px; background: ${photo ? `url('${photo}') center/cover` : '#eee'}; position: relative;">
                        ${!photo ? '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#999;"><i class="fas fa-map-marker-alt" style="font-size:3rem;"></i></div>' : ''}
                        <span style="position: absolute; top: 10px; right: 10px; background: ${d.is_active == 1 ? '#28a745' : '#dc3545'}; color: white; padding: 4px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;">
                            ${d.is_active == 1 ? 'Activo' : 'Inactivo'}
                        </span>
                    </div>
                    <div style="padding: 1.2rem;">
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-color); font-size: 1.1rem;">${name}</h4>
                        <p style="margin: 0 0 1rem 0; color: #666; font-size: 0.9rem;">
                            <i class="fas fa-map-marker-alt"></i> ${d.municipality || ''}, ${d.province || ''}
                        </p>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                            <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;"><i class="fas fa-eye"></i> ${stats.views || 0}</span>
                            <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;"><i class="fas fa-heart"></i> ${stats.favorites || 0}</span>
                            <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;"><i class="fas fa-envelope"></i> ${stats.messages || 0}</span>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="/lugar/${slug}" target="_blank" style="flex:1;text-align:center;background:var(--primary-color);color:white;padding:0.6rem;border-radius:8px;text-decoration:none;font-size:0.85rem;cursor:pointer;min-width:80px;"><i class="fas fa-eye"></i> Ver</a>
                            <a href="/dashboard/editar-mi-lugar.php?id=${id}" target="_blank" style="flex:1;text-align:center;background:#666;color:white;padding:0.6rem;border-radius:8px;text-decoration:none;font-size:0.85rem;cursor:pointer;min-width:80px;"><i class="fas fa-edit"></i> Editar</a>
                        </div>
                    </div>
                </div>
            `;
            }).join('')}
        </div>
    `;
}

// ============================================================
// MOSTRAR EVENTOS DEL USUARIO
// ============================================================
function cargarMisEventos(eventos) {
    const container = document.getElementById('mis-eventos-list');
    if (!container) return;
    
    if (eventos.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <i class="fas fa-calendar-alt" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                <p style="color: #666;">No tienes eventos registrados aún.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            ${eventos.map(evento => {
                const d = evento.data || evento;
                const stats = evento.stats || {};
                const photo = d.photo || d.photo1 || null;
                const name = d.name || 'Sin nombre';
                const slug = d.slug || '';
                const id = d.id || '';

                return `
                <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <div style="height: 180px; background: ${photo ? `url('${photo}') center/cover` : '#eee'}; position: relative;">
                        ${!photo ? '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#999;"><i class="fas fa-calendar-alt" style="font-size:3rem;"></i></div>' : ''}
                        <span style="position: absolute; top: 10px; right: 10px; background: ${d.is_active == 1 ? '#28a745' : '#dc3545'}; color: white; padding: 4px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;">
                            ${d.is_active == 1 ? 'Activo' : 'Inactivo'}
                        </span>
                    </div>
                    <div style="padding: 1.2rem;">
                        <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-color); font-size: 1.1rem;">${name}</h4>
                        <p style="margin: 0 0 1rem 0; color: #666; font-size: 0.9rem;">
                            <i class="fas fa-map-marker-alt"></i> ${d.municipality || ''}, ${d.province || ''}
                        </p>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                            <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;"><i class="fas fa-eye"></i> ${stats.views || 0}</span>
                            <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;"><i class="fas fa-heart"></i> ${stats.favorites || 0}</span>
                            <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;"><i class="fas fa-envelope"></i> ${stats.messages || 0}</span>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="/evento/${slug}" target="_blank" style="flex:1;text-align:center;background:var(--primary-color);color:white;padding:0.6rem;border-radius:8px;text-decoration:none;font-size:0.85rem;cursor:pointer;min-width:80px;"><i class="fas fa-eye"></i> Ver</a>
                            <a href="/dashboard/editar-mi-evento.php?id=${id}" target="_blank" style="flex:1;text-align:center;background:#666;color:white;padding:0.6rem;border-radius:8px;text-decoration:none;font-size:0.85rem;cursor:pointer;min-width:80px;"><i class="fas fa-edit"></i> Editar</a>
                        </div>
                    </div>
                </div>
            `;
            }).join('')}
        </div>
    `;
}
