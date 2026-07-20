/* ============================================================
   dashboard-pasaporte.js — Pasaporte Rural
   Funciones: cargarPasaporteUser
   ============================================================ */

async function cargarPasaporteUser() {
    try {
        const response = await fetch('api/get_user_resources.php');
        const data = await response.json();
        
        const container = document.getElementById('pasaporte-content');
        if (!container) return;
        
        if (data.success && data.data) {
            const summary = data.data.summary || {};
            const resources = data.data.resources || {};
            
            const totalResources = summary.total_resources || 0;
            const totalViews = Object.values(resources).reduce((acc, arr) => {
                return acc + (arr || []).reduce((sum, item) => sum + ((item.stats && item.stats.views) || 0), 0);
            }, 0);
            
            container.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                    <div style="background: white; border-radius: 15px; padding: 1.5rem; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                        <i class="fas fa-map-marked-alt" style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                        <h3 style="margin: 0; color: var(--primary-color); font-size: 2rem;">${totalResources}</h3>
                        <p style="color: #666; margin: 0.5rem 0 0;">Recursos</p>
                    </div>
                    <div style="background: white; border-radius: 15px; padding: 1.5rem; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                        <i class="fas fa-eye" style="font-size: 2.5rem; color: var(--secondary-color); margin-bottom: 1rem;"></i>
                        <h3 style="margin: 0; color: var(--primary-color); font-size: 2rem;">${totalViews}</h3>
                        <p style="color: #666; margin: 0.5rem 0 0;">Visitas</p>
                    </div>
                    <div style="background: white; border-radius: 15px; padding: 1.5rem; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                        <i class="fas fa-heart" style="font-size: 2.5rem; color: #e74c3c; margin-bottom: 1rem;"></i>
                        <h3 style="margin: 0; color: var(--primary-color); font-size: 2rem;">${summary.unread_messages || 0}</h3>
                        <p style="color: #666; margin: 0.5rem 0 0;">Mensajes</p>
                    </div>
                </div>
            `;
        } else {
            container.innerHTML = `
                <div style="text-align: center; padding: 3rem; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <i class="fas fa-passport" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <p style="color: #666;">Tu Pasaporte Rural se irá llenando a medida que explores.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error cargando pasaporte:', error);
    }
}
