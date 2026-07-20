/* ============================================================
   dashboard-membership.js — Membresía y Pasarela Stripe
   Funciones: loadMembershipData, renderMembershipPlans,
              selectPlan, redirectToCheckout, openBillingPortal
   ============================================================ */

let selectedPlanId = null;

async function loadMembershipData() {
    try {
        const response = await fetch('api/get_membership_options.php');
        const data = await response.json();
        
        if (data.success && data.data) {
            const membership = data.data;
            
            // Actualizar badge de membresía en el perfil
            const membershipBadge = document.getElementById('membershipBadge');
            if (membershipBadge) {
                const type = membership.type || 'free';
                const labels = { 'free': 'Gratuito', 'premium': 'Premium', 'enterprise': 'Enterprise' };
                const colors = { 'free': '#6c757d', 'premium': '#f59e0b', 'enterprise': '#2d3748' };
                membershipBadge.textContent = labels[type] || 'Gratuito';
                membershipBadge.style.backgroundColor = colors[type] || '#6c757d';
            }
            
            // Actualizar estado de membresía en el perfil
            const membershipStatus = document.getElementById('membershipStatus');
            if (membershipStatus) {
                membershipStatus.textContent = membership.status || 'Activo';
            }
            
            // Renderizar planes disponibles
            if (membership.plans) {
                renderMembershipPlans(membership.plans, membership.type);
            }
        }
    } catch (error) {
        console.error('Error cargando membresía:', error);
    }
}

function renderMembershipPlans(plans, currentType) {
    const container = document.getElementById('membership-plans');
    if (!container) return;
    
    container.innerHTML = plans.map(plan => {
        const isCurrent = plan.slug === currentType;
        const price = plan.price_monthly || plan.price || 0;
        const features = plan.features || [];
        
        return `
            <div style="background: white; border-radius: 15px; padding: 2rem; text-align: center; box-shadow: ${isCurrent ? '0 8px 25px rgba(47,82,51,0.15)' : '0 4px 15px rgba(0,0,0,0.05)'}; border: ${isCurrent ? '2px solid var(--primary-color)' : '1px solid #eee'}; position: relative;">
                ${isCurrent ? '<div style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--primary-color); color: white; padding: 0.3rem 1rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600;">Plan Actual</div>' : ''}
                <div style="font-size: 2.5rem; color: var(--primary-color); margin-bottom: 1rem;">
                    <i class="fas ${plan.icon || 'fa-crown'}"></i>
                </div>
                <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">${plan.name}</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--primary-color); margin-bottom: 1rem;">
                    ${price > 0 ? `${price}€` : 'Gratis'}
                    ${price > 0 ? '<span style="font-size: 0.9rem; font-weight: 400; color: #888;">/mes</span>' : ''}
                </div>
                <ul style="list-style: none; padding: 0; margin: 0 0 1.5rem 0; text-align: left;">
                    ${features.map(f => `<li style="padding: 0.5rem 0; border-bottom: 1px solid #f0f0f0; color: #555;"><i class="fas fa-check" style="color: var(--primary-color); margin-right: 10px;"></i>${f}</li>`).join('')}
                </ul>
                ${!isCurrent ? `<button onclick="selectPlan('${plan.slug}')" style="background: var(--primary-color); color: white; border: none; padding: 0.8rem 2rem; border-radius: 25px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s;">${price > 0 ? 'Actualizar' : 'Plan Gratuito'}</button>` : ''}
            </div>
        `;
    }).join('');
}

async function selectPlan(planSlug) {
    selectedPlanId = planSlug;
    
    try {
        const response = await fetch('api/create_checkout_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ plan_slug: planSlug })
        });
        const data = await response.json();
        
        if (data.success && data.data && data.data.url) {
            // Redirigir a Stripe Checkout
            window.location.href = data.data.url;
        } else {
            alert('Error al crear sesión de pago: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error en selectPlan:', error);
        alert('Error de conexión. Por favor, inténtalo de nuevo.');
    }
}

async function redirectToCheckout(priceId) {
    try {
        const response = await fetch('api/create_checkout_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ price_id: priceId })
        });
        const data = await response.json();
        
        if (data.success && data.data && data.data.url) {
            window.location.href = data.data.url;
        } else {
            alert('Error al crear sesión de pago: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error en redirectToCheckout:', error);
        alert('Error de conexión. Por favor, inténtalo de nuevo.');
    }
}

async function openBillingPortal() {
    const loadingDiv = document.createElement('div');
    loadingDiv.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.8);display:flex;align-items:center;justify-content:center;z-index:99999;';
    loadingDiv.innerHTML = '<div style="text-align:center;"><i class="fas fa-spinner fa-spin" style="font-size:3rem;color:var(--primary-color);"></i><p style="margin-top:1rem;color:#666;">Redirigiendo al portal de facturación...</p></div>';
    document.body.appendChild(loadingDiv);
    
    try {
        const response = await fetch('api/create_onetime_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'billing_portal' })
        });
        const data = await response.json();
        
        if (data.success && data.data && data.data.url) {
            window.location.href = data.data.url;
        } else {
            loadingDiv.remove();
            alert('Error al abrir el portal de facturación: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {
        loadingDiv.remove();
        console.error('Error abriendo portal de facturación:', error);
        alert('Error de conexión. Por favor, inténtalo de nuevo o contacta con olgamarin@rutasrurales.io');
    }
}
