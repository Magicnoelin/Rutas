/* ============================================================
   dashboard-favorites.js — Favoritos del Usuario
   Funciones: loadFavorites, renderFavoriteCard
   ============================================================ */

async function loadFavorites() {
    try {
        const response = await fetch('api/favorites.php');
        const data = await response.json();
        
        const container = document.getElementById('favorites-list');
        if (!container) return;
        
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                    ${data.data.map(item => renderFavoriteCard(item)).join('')}
                </div>
            `;
        } else {
            container.innerHTML = `
                <div style="text-align: center; padding: 3rem; background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <i class="fas fa-heart" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <p style="color: #666;">No tienes favoritos guardados aún.</p>
                    <p style="color: #999; font-size: 0.9rem;">Explora nuestros alojamientos y actividades para guardar tus favoritos.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error cargando favoritos:', error);
    }
}

function renderFavoriteCard(item) {
    const type = item.resource_type || item.type || 'accommodation';
    const data = item.data || item;
    const name = data.name || 'Sin nombre';
    const slug = data.slug || '';
    const photo = data.photo || data.photo1 || null;
    const location = [data.municipality, data.province].filter(Boolean).join(', ');
    
    const icons = {
        'accommodation': 'fa-bed',
        'activity': 'fa-hiking',
        'place': 'fa-map-marker-alt',
        'event': 'fa-calendar-alt'
    };
    const icon = icons[type] || 'fa-star';
    
    const urls = {
        'accommodation': `/detalle.html?slug=${slug}`,
        'activity': `/actividad/${slug}`,
        'place': `/lugar/${slug}`,
        'event': `/evento/${slug}`
    };
    const url = urls[type] || '#';
    
    return `
        <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <div style="height: 180px; background: ${photo ? `url('${photo}') center/cover` : '#eee'};">
                ${!photo ? `<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#999;"><i class="fas ${icon}" style="font-size:3rem;"></i></div>` : ''}
            </div>
            <div style="padding: 1.2rem;">
                <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-color);">${name}</h4>
                ${location ? `<p style="margin: 0 0 1rem 0; color: #666; font-size: 0.9rem;"><i class="fas fa-map-marker-alt"></i> ${location}</p>` : ''}
                <a href="${url}" target="_blank" style="display:inline-block;background:var(--primary-color);color:white;padding:0.5rem 1rem;border-radius:8px;text-decoration:none;font-size:0.85rem;">
                    <i class="fas fa-eye"></i> Ver
                </a>
            </div>
        </div>
    `;
}
