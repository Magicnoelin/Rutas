/**
 * Integración de Pasarela de Pagos - Stripe
 * RutasRurales.io
 * 
 * Este archivo contiene las funciones necesarias para integrar
 * la pasarela de pagos en el frontend
 */

// Configuración
const PAYMENT_CONFIG = {
    apiBaseUrl: '/api',
    stripePublicKey: null // Se cargará dinámicamente
};

/**
 * Inicializar Stripe
 */
let stripeInstance = null;

async function initializeStripe() {
    try {
        // Obtener la clave pública de Stripe desde el backend
        const response = await fetch(`${PAYMENT_CONFIG.apiBaseUrl}/get_membership_options.php`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            // La clave pública se puede obtener del backend si es necesario
            // Por ahora, asumimos que Stripe.js ya está cargado
            if (typeof Stripe !== 'undefined') {
                console.log('Stripe.js cargado correctamente');
            }
        }
    } catch (error) {
        console.error('Error al inicializar Stripe:', error);
    }
}

/**
 * Obtener planes de membresía disponibles
 */
async function getMembershipPlans() {
    try {
        const response = await fetch(`${PAYMENT_CONFIG.apiBaseUrl}/get_membership_options.php`);
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            throw new Error(data.error || 'Error al obtener planes');
        }
    } catch (error) {
        console.error('Error al obtener planes:', error);
        throw error;
    }
}

/**
 * Crear sesión de checkout de Stripe
 * @param {number} planId - ID del plan (2 para Premium)
 * @param {string} billingCycle - 'monthly' o 'yearly'
 */
async function createCheckoutSession(planId, billingCycle) {
    try {
        const response = await fetch(`${PAYMENT_CONFIG.apiBaseUrl}/create_checkout_session.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include', // Importante para enviar cookies de sesión
            body: JSON.stringify({
                plan_id: planId,
                billing_cycle: billingCycle
            })
        });

        const data = await response.json();

        if (data.success) {
            return data.data;
        } else {
            throw new Error(data.error || 'Error al crear sesión de pago');
        }
    } catch (error) {
        console.error('Error al crear checkout session:', error);
        throw error;
    }
}

/**
 * Procesar upgrade a Premium
 * @param {string} billingCycle - 'monthly' o 'yearly'
 */
async function upgradeToPremium(billingCycle) {
    try {
        // Mostrar loading
        showPaymentLoading(true);

        // Crear sesión de checkout
        const checkoutData = await createCheckoutSession(2, billingCycle); // 2 = Premium

        // Si hay URL de checkout de Stripe, redirigir
        if (checkoutData.stripe_checkout_url) {
            window.location.href = checkoutData.stripe_checkout_url;
            return;
        }

        // Si hay session_id pero no URL, usar Stripe.js
        if (checkoutData.stripe_session_id && typeof Stripe !== 'undefined') {
            const stripe = Stripe(checkoutData.stripe_public_key);
            const { error } = await stripe.redirectToCheckout({
                sessionId: checkoutData.stripe_session_id
            });

            if (error) {
                throw new Error(error.message);
            }
            return;
        }

        // Si no hay integración completa de Stripe, mostrar información
        showPaymentInfo(checkoutData);

    } catch (error) {
        console.error('Error al procesar upgrade:', error);
        showPaymentError(error.message);
    } finally {
        showPaymentLoading(false);
    }
}

/**
 * Mostrar/ocultar loading
 */
function showPaymentLoading(show) {
    const loadingElement = document.getElementById('payment-loading');
    if (loadingElement) {
        loadingElement.style.display = show ? 'block' : 'none';
    }
}

/**
 * Mostrar error de pago
 */
function showPaymentError(message) {
    const errorElement = document.getElementById('payment-error');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        
        // Ocultar después de 5 segundos
        setTimeout(() => {
            errorElement.style.display = 'none';
        }, 5000);
    } else {
        alert('Error: ' + message);
    }
}

/**
 * Mostrar información de pago (cuando no hay Stripe completo)
 */
function showPaymentInfo(data) {
    const message = `
        Sesión de pago creada:
        - Plan: ${data.plan_name}
        - Ciclo: ${data.billing_cycle}
        - Precio: ${data.price}€
        - Intent ID: ${data.intent_id}
        
        Para completar la integración, necesitas:
        1. Instalar Stripe PHP en el servidor
        2. Configurar las claves de Stripe
        3. Descomentar el código de Stripe en create_checkout_session.php
    `;
    
    alert(message);
    console.log('Checkout Data:', data);
}

/**
 * Renderizar planes de membresía en el DOM
 * @param {string} containerId - ID del contenedor donde renderizar
 */
async function renderMembershipPlans(containerId) {
    try {
        const plans = await getMembershipPlans();
        const container = document.getElementById(containerId);
        
        if (!container) {
            console.error('Contenedor no encontrado:', containerId);
            return;
        }

        container.innerHTML = plans.map(plan => `
            <div class="membership-plan ${plan.is_popular ? 'popular' : ''}">
                ${plan.is_popular ? '<div class="badge-popular">Más Popular</div>' : ''}
                <h3>${plan.name}</h3>
                <p class="plan-description">${plan.description}</p>
                
                <div class="plan-pricing">
                    <div class="price-option">
                        <span class="price">${plan.price_monthly}€</span>
                        <span class="period">/mes</span>
                    </div>
                    ${plan.price_yearly > 0 ? `
                        <div class="price-option">
                            <span class="price">${plan.price_yearly}€</span>
                            <span class="period">/año</span>
                            <span class="savings">(2 meses gratis)</span>
                        </div>
                    ` : ''}
                </div>

                <ul class="plan-features">
                    ${plan.features.map(feature => `<li>✓ ${feature}</li>`).join('')}
                </ul>

                ${plan.price_monthly > 0 ? `
                    <div class="plan-actions">
                        <button onclick="upgradeToPremium('monthly')" class="btn-select-plan">
                            Seleccionar Mensual
                        </button>
                        ${plan.price_yearly > 0 ? `
                            <button onclick="upgradeToPremium('yearly')" class="btn-select-plan btn-yearly">
                                Seleccionar Anual
                            </button>
                        ` : ''}
                    </div>
                ` : `
                    <button class="btn-select-plan btn-current" disabled>
                        Plan Actual
                    </button>
                `}
            </div>
        `).join('');

    } catch (error) {
        console.error('Error al renderizar planes:', error);
    }
}

/**
 * Verificar estado del pago después de redirección
 */
function checkPaymentStatus() {
    const urlParams = new URLSearchParams(window.location.search);
    const sessionId = urlParams.get('session_id');
    
    if (sessionId) {
        console.log('Pago completado. Session ID:', sessionId);
        
        // Aquí podrías hacer una llamada al backend para verificar el estado
        // y actualizar la UI en consecuencia
        
        return true;
    }
    
    return false;
}

/**
 * Inicialización cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Stripe
    initializeStripe();
    
    // Verificar si venimos de un pago
    if (checkPaymentStatus()) {
        console.log('Usuario viene de completar un pago');
    }
    
    // Si existe el contenedor de planes, renderizarlos
    const plansContainer = document.getElementById('membership-plans-container');
    if (plansContainer) {
        renderMembershipPlans('membership-plans-container');
    }
});

// Exportar funciones para uso global
window.PaymentIntegration = {
    upgradeToPremium,
    getMembershipPlans,
    renderMembershipPlans,
    createCheckoutSession
};
