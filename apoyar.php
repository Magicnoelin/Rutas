<?php
$lang = 'es';
$page_title = 'Apoya Rutas Rurales — Invítanos a un café o haz una contribución';
$page_description = 'Apoya el turismo rural sostenible. Invítanos a un café o haz una contribución voluntaria a Rutas Rurales.';
$page_canonical = 'https://rutasrurales.io/apoyar.php';
require_once __DIR__ . '/header.php';
?>

<style>
    :root {
        --primary: #2f5233;
        --primary-light: #4a7c59;
        --accent: #ff8f00;
        --bg: #f8faf8;
        --white: #ffffff;
        --text: #333;
        --text-light: #666;
        --border: #e0e8e0;
        --shadow: 0 4px 20px rgba(47,82,51,0.10);
        --radius: 16px;
    }

    body { padding-top: 80px; }
    @media (max-width: 992px) { body { padding-top: 70px; } }

    /* ── HERO con foto ── */
    .hero {
        background: linear-gradient(135deg, rgba(20,40,22,0.68) 0%, rgba(47,82,51,0.55) 60%, rgba(30,60,35,0.50) 100%),
                    url('/menu_images/laguna_negra.webp') center center / cover no-repeat;
        color: white;
        text-align: center;
        padding: 5rem 1.5rem 4rem;
        position: relative;
        overflow: hidden;
    }
    .hero-emoji { font-size: 4rem; margin-bottom: 1rem; display: block; position: relative; z-index: 1; }
    .hero h1 { font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 800; margin-bottom: 1rem; position: relative; z-index: 1; text-shadow: 0 2px 8px rgba(0,0,0,0.4); }
    .hero p { font-size: 1.1rem; opacity: 0.95; max-width: 600px; margin: 0 auto 2rem; position: relative; z-index: 1; text-shadow: 0 1px 4px rgba(0,0,0,0.3); }
    .hero-stats { display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem; position: relative; z-index: 1; }
    .hero-stat { text-align: center; }
    .hero-stat strong { display: block; font-size: 1.8rem; font-weight: 800; text-shadow: 0 2px 6px rgba(0,0,0,0.3); }
    .hero-stat span { font-size: 0.85rem; opacity: 0.9; }

    /* ── CONTENEDOR ── */
    .apoyar-container { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; }

    /* ── SECCIONES ── */
    .apoyar-section { padding: 4rem 0; }
    .section-title { text-align: center; margin-bottom: 2.5rem; }
    .section-title h2 { font-size: 1.8rem; color: var(--primary); margin-bottom: 0.5rem; }
    .section-title p { color: var(--text-light); font-size: 1rem; }

    /* ── GRID ── */
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
    }

    /* ── TARJETA ── */
    .pay-card {
        background: var(--white);
        border-radius: var(--radius);
        padding: 2rem 1.5rem;
        text-align: center;
        box-shadow: var(--shadow);
        border: 2px solid transparent;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .pay-card:hover { transform: translateY(-6px); box-shadow: 0 12px 35px rgba(47,82,51,0.18); border-color: var(--primary); }
    .pay-card.popular { border-color: var(--accent); }
    .pay-card .badge { position: absolute; top: 12px; right: 12px; background: var(--accent); color: white; font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; }
    .pay-card .icon { font-size: 2.8rem; margin-bottom: 1rem; display: block; }
    .pay-card h3 { font-size: 1.1rem; color: var(--primary); margin-bottom: 0.5rem; font-weight: 700; }
    .pay-card p { font-size: 0.88rem; color: var(--text-light); margin-bottom: 1.2rem; min-height: 2.5rem; }
    .pay-card .price { font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 1.2rem; }

    /* ── TARJETA IMPORTE LIBRE (en el grid) ── */
    .pay-card.custom-card { cursor: default; }
    .pay-card.custom-card:hover { transform: none; border-color: var(--primary); }
    .amount-input-wrap {
        display: flex;
        align-items: center;
        border: 2px solid var(--border);
        border-radius: 30px;
        overflow: hidden;
        margin-bottom: 1rem;
    }
    .amount-input-wrap span {
        padding: 0.7rem 1rem;
        background: var(--bg);
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary);
        border-right: 2px solid var(--border);
    }
    .amount-input-wrap input {
        flex: 1;
        padding: 0.7rem 0.8rem;
        border: none;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--primary);
        outline: none;
        background: white;
        min-width: 0;
    }

    .btn-pay {
        display: inline-block;
        width: 100%;
        padding: 0.85rem 1.5rem;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 30px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s;
    }
    .btn-pay:hover { background: var(--primary-light); }
    .btn-pay:active { transform: scale(0.98); }
    .btn-pay.accent { background: var(--accent); }
    .btn-pay.accent:hover { background: #e67e00; }

    .cafe-section { background: linear-gradient(135deg, #fff8e1, #fff3cd); }
    .cafe-section .section-title h2 { color: #8B6914; }
    .apoyo-section { background: var(--bg); }

    /* ── MODALES ── */
    .payment-result { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
    .payment-result.show { display: flex; }
    .result-box { background: white; border-radius: var(--radius); padding: 3rem 2rem; text-align: center; max-width: 420px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
    .result-box .result-icon { font-size: 4rem; margin-bottom: 1rem; display: block; }
    .result-box h2 { color: var(--primary); margin-bottom: 0.8rem; }
    .result-box p { color: var(--text-light); margin-bottom: 1.5rem; }
    .loading-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center; }
    .loading-overlay.show { display: flex; }
    .loading-box { background: white; border-radius: var(--radius); padding: 2rem; text-align: center; }
    .loading-box i { font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem; display: block; }

    @media (max-width: 600px) {
        .hero { padding: 3rem 1rem 2.5rem; }
        .cards-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 400px) {
        .cards-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- ── HERO ── -->
<section class="hero">
    <span class="hero-emoji">🌿</span>
    <h1>Apoya el turismo rural sostenible</h1>
    <p>Rutas Rurales conecta viajeros con la España rural auténtica. Tu apoyo nos ayuda a seguir creciendo y a dar visibilidad a pequeños negocios locales.</p>
    <div class="hero-stats">
        <div class="hero-stat"><strong>+500</strong><span>Alojamientos</span></div>
        <div class="hero-stat"><strong>+1.200</strong><span>Lugares de interés</span></div>
        <div class="hero-stat"><strong>+300</strong><span>Eventos rurales</span></div>
    </div>
</section>

<!-- ── ☕ INVÍTAME A UN CAFÉ ── -->
<section class="apoyar-section cafe-section">
    <div class="apoyar-container">
        <div class="section-title">
            <h2>☕ Invítame a un café</h2>
            <p>La forma más sencilla de apoyar el proyecto. Cada café nos ayuda a mantener los servidores y seguir mejorando.</p>
        </div>
        <div class="cards-grid" style="max-width:600px;margin:0 auto;">
            <div class="pay-card" onclick="pagar('CAFE_1')">
                <span class="icon">☕</span>
                <h3>Un café</h3>
                <p>El gesto más pequeño con el mayor significado</p>
                <div class="price">1.50€</div>
                <button class="btn-pay accent">Invitar a un café</button>
            </div>
            <div class="pay-card popular" onclick="pagar('CAFE_2')">
                <span class="badge">⭐ Popular</span>
                <span class="icon">☕☕</span>
                <h3>Dos cafés</h3>
                <p>Para el equipo completo. ¡Doble energía!</p>
                <div class="price">3.00€</div>
                <button class="btn-pay accent">Invitar a dos cafés</button>
            </div>
        </div>
    </div>
</section>

<!-- ── 🎯 APOYO VOLUNTARIO ── -->
<section class="apoyar-section apoyo-section">
    <div class="apoyar-container">
        <div class="section-title">
            <h2>🎯 Contribución voluntaria</h2>
            <p>Si Rutas Rurales te ha sido útil, cualquier contribución nos ayuda a seguir creciendo y a dar más visibilidad al turismo rural.</p>
        </div>
        <div class="cards-grid" style="max-width:1100px;margin:0 auto;">
            <div class="pay-card" onclick="pagar('APOYO_5')">
                <span class="icon">🌱</span>
                <h3>Semilla</h3>
                <p>Una pequeña contribución con gran impacto</p>
                <div class="price">5€</div>
                <button class="btn-pay">Contribuir 5€</button>
            </div>
            <div class="pay-card popular" onclick="pagar('APOYO_10')">
                <span class="badge">💚 Gracias</span>
                <span class="icon">🌳</span>
                <h3>Árbol</h3>
                <p>Ayuda a que el proyecto siga creciendo</p>
                <div class="price">10€</div>
                <button class="btn-pay">Contribuir 10€</button>
            </div>
            <div class="pay-card" onclick="pagar('APOYO_20')">
                <span class="icon">🏔️</span>
                <h3>Montaña</h3>
                <p>Una contribución que marca la diferencia</p>
                <div class="price">20€</div>
                <button class="btn-pay">Contribuir 20€</button>
            </div>
            <div class="pay-card" onclick="pagar('APOYO_50')">
                <span class="icon">🦅</span>
                <h3>Héroe Rural</h3>
                <p>¡Eres increíble! El turismo rural te lo agradece</p>
                <div class="price">50€</div>
                <button class="btn-pay">Contribuir 50€</button>
            </div>
            <!-- Importe libre como 5ª tarjeta en el grid -->
            <div class="pay-card custom-card">
                <span class="icon">❤️</span>
                <h3>Elige tu importe</h3>
                <p>Cualquier cantidad es bienvenida (mínimo 1€)</p>
                <div class="amount-input-wrap">
                    <span>€</span>
                    <input type="number" id="customAmount" min="1" step="0.50" placeholder="Ej: 7.50">
                </div>
                <button class="btn-pay" onclick="pagarImporteLibre()">
                    <i class="fas fa-heart"></i> Contribuir
                </button>
            </div>
        </div>
    </div>
</section>

<!-- ── LOADING ── -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-box">
        <i class="fas fa-spinner fa-spin"></i>
        <p style="color:#666;">Redirigiendo al pago seguro...</p>
        <small style="color:#aaa;">Powered by Stripe 🔒</small>
    </div>
</div>

<!-- ── RESULTADO PAGO ── -->
<div class="payment-result" id="paymentResult">
    <div class="result-box">
        <span class="result-icon" id="resultIcon">✅</span>
        <h2 id="resultTitle">¡Gracias!</h2>
        <p id="resultMessage">Tu contribución ha sido procesada correctamente.</p>
        <button class="btn-pay" onclick="cerrarResultado()" style="max-width:200px;margin:0 auto;">Cerrar</button>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const params = new URLSearchParams(window.location.search);
        const payment = params.get('payment');
        const concept = params.get('concept');
        if (payment === 'success') {
            mostrarResultado('success', concept);
            window.history.replaceState({}, '', '/apoyar.php');
        } else if (payment === 'canceled') {
            mostrarResultado('canceled');
            window.history.replaceState({}, '', '/apoyar.php');
        }
    });

    async function pagar(conceptCode) {
        mostrarLoading(true);
        try {
            const response = await fetch('/api/create_onetime_payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    concept_code: conceptCode,
                    success_url: window.location.origin + '/apoyar.php?payment=success',
                    cancel_url:  window.location.origin + '/apoyar.php?payment=canceled'
                })
            });
            const data = await response.json();
            if (data.success && data.data.checkout_url) {
                window.location.href = data.data.checkout_url;
            } else {
                mostrarLoading(false);
                alert('Error: ' + (data.error || 'No se pudo crear el pago'));
            }
        } catch (error) {
            mostrarLoading(false);
            alert('Error de conexión. Por favor, inténtalo de nuevo.');
        }
    }

    async function pagarImporteLibre() {
        const input = document.getElementById('customAmount');
        const amount = parseFloat(input.value);
        if (!amount || amount < 1) {
            input.focus();
            input.style.outline = '2px solid #e74c3c';
            setTimeout(() => input.style.outline = '', 2000);
            alert('Por favor, introduce un importe de al menos 1€');
            return;
        }
        mostrarLoading(true);
        try {
            const response = await fetch('/api/create_onetime_payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    concept_code:  'APOYO_5',
                    custom_amount: amount,
                    success_url: window.location.origin + '/apoyar.php?payment=success',
                    cancel_url:  window.location.origin + '/apoyar.php?payment=canceled'
                })
            });
            const data = await response.json();
            if (data.success && data.data.checkout_url) {
                window.location.href = data.data.checkout_url;
            } else {
                mostrarLoading(false);
                alert('Error: ' + (data.error || 'No se pudo crear el pago'));
            }
        } catch (error) {
            mostrarLoading(false);
            alert('Error de conexión. Por favor, inténtalo de nuevo.');
        }
    }

    function mostrarLoading(show) {
        document.getElementById('loadingOverlay').classList.toggle('show', show);
    }

    function mostrarResultado(type, concept) {
        const overlay = document.getElementById('paymentResult');
        const icon    = document.getElementById('resultIcon');
        const title   = document.getElementById('resultTitle');
        const msg     = document.getElementById('resultMessage');
        const conceptMessages = {
            'CAFE_1':   '¡Gracias por el café! Nos da energía para seguir mejorando Rutas Rurales. ☕',
            'CAFE_2':   '¡Dos cafés! El equipo al completo te lo agradece. ☕☕',
            'APOYO_5':  '¡Gracias por tu contribución de 5€! El turismo rural te lo agradece. 🌱',
            'APOYO_10': '¡Gracias por tu contribución de 10€! Eres un gran apoyo. 🌳',
            'APOYO_20': '¡Increíble! Tu contribución de 20€ marca la diferencia. 🏔️',
            'APOYO_50': '¡Eres un héroe del turismo rural! Gracias por tu generosidad. 🦅',
        };
        if (type === 'success') {
            icon.textContent  = '🎉';
            title.textContent = '¡Muchas gracias!';
            msg.textContent   = conceptMessages[concept] || 'Tu pago ha sido procesado correctamente. ¡Gracias por apoyar el turismo rural!';
        } else {
            icon.textContent  = '😔';
            title.textContent = 'Pago cancelado';
            msg.textContent   = 'No se ha realizado ningún cargo. Puedes intentarlo de nuevo cuando quieras.';
        }
        overlay.classList.add('show');
    }

    function cerrarResultado() {
        document.getElementById('paymentResult').classList.remove('show');
    }

    document.getElementById('paymentResult').addEventListener('click', function(e) {
        if (e.target === this) cerrarResultado();
    });
</script>
