<?php
$lang = 'en';
$page_title = 'Support Rutas Rurales — Buy us a coffee or make a contribution';
$page_description = 'Support sustainable rural tourism. Buy us a coffee or make a voluntary contribution to Rutas Rurales.';
$page_canonical = 'https://www.rutasrurales.io/en/apoyar.php';
require_once __DIR__ . '/../header.php';
?>

<style>
    :root { --primary:#2f5233;--primary-light:#4a7c59;--accent:#ff8f00;--bg:#f8faf8;--white:#fff;--text:#333;--text-light:#666;--border:#e0e8e0;--shadow:0 4px 20px rgba(47,82,51,0.10);--radius:16px; }
    body { padding-top: 80px; }
    @media (max-width: 992px) { body { padding-top: 70px; } }

    .hero {
        background: linear-gradient(135deg, rgba(20,40,22,0.68) 0%, rgba(47,82,51,0.55) 60%, rgba(30,60,35,0.50) 100%),
                    url('/menu_images/laguna_negra.webp') center center / cover no-repeat;
        color: white; text-align: center; padding: 5rem 1.5rem 4rem; position: relative; overflow: hidden;
    }
    .hero-emoji { font-size: 4rem; margin-bottom: 1rem; display: block; position: relative; z-index: 1; }
    .hero h1 { font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 800; margin-bottom: 1rem; position: relative; z-index: 1; text-shadow: 0 2px 8px rgba(0,0,0,0.4); }
    .hero p { font-size: 1.1rem; opacity: 0.95; max-width: 600px; margin: 0 auto 2rem; position: relative; z-index: 1; text-shadow: 0 1px 4px rgba(0,0,0,0.3); }
    .hero-stats { display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem; position: relative; z-index: 1; }
    .hero-stat { text-align: center; }
    .hero-stat strong { display: block; font-size: 1.8rem; font-weight: 800; text-shadow: 0 2px 6px rgba(0,0,0,0.3); }
    .hero-stat span { font-size: 0.85rem; opacity: 0.9; }

    .apoyar-container { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; }
    .apoyar-section { padding: 4rem 0; }
    .section-title { text-align: center; margin-bottom: 2.5rem; }
    .section-title h2 { font-size: 1.8rem; color: var(--primary); margin-bottom: 0.5rem; }
    .section-title p { color: var(--text-light); font-size: 1rem; }

    .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; }
    .pay-card { background: var(--white); border-radius: var(--radius); padding: 2rem 1.5rem; text-align: center; box-shadow: var(--shadow); border: 2px solid transparent; transition: all 0.3s ease; cursor: pointer; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; }
    .pay-card:hover { transform: translateY(-6px); box-shadow: 0 12px 35px rgba(47,82,51,0.18); border-color: var(--primary); }
    .pay-card.popular { border-color: var(--accent); }
    .pay-card .badge { position: absolute; top: 12px; right: 12px; background: var(--accent); color: white; font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; }
    .pay-card .icon { font-size: 2.8rem; margin-bottom: 1rem; display: block; }
    .pay-card h3 { font-size: 1.1rem; color: var(--primary); margin-bottom: 0.5rem; font-weight: 700; }
    .pay-card p { font-size: 0.88rem; color: var(--text-light); margin-bottom: 1.2rem; min-height: 2.5rem; }
    .pay-card .price { font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 1.2rem; }

    .pay-card.custom-card { cursor: default; }
    .pay-card.custom-card:hover { transform: none; border-color: var(--primary); }
    .amount-input-wrap { display: flex; align-items: center; border: 2px solid var(--border); border-radius: 30px; overflow: hidden; margin-bottom: 1rem; }
    .amount-input-wrap span { padding: 0.7rem 1rem; background: var(--bg); font-size: 1.1rem; font-weight: 700; color: var(--primary); border-right: 2px solid var(--border); }
    .amount-input-wrap input { flex: 1; padding: 0.7rem 0.8rem; border: none; font-size: 1.1rem; font-weight: 700; color: var(--primary); outline: none; background: white; min-width: 0; }

    .btn-pay { display: inline-block; width: 100%; padding: 0.85rem 1.5rem; background: var(--primary); color: white; border: none; border-radius: 30px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background 0.2s, transform 0.1s; }
    .btn-pay:hover { background: var(--primary-light); }
    .btn-pay:active { transform: scale(0.98); }
    .btn-pay.accent { background: var(--accent); }
    .btn-pay.accent:hover { background: #e67e00; }

    .cafe-section { background: linear-gradient(135deg, #fff8e1, #fff3cd); }
    .cafe-section .section-title h2 { color: #8B6914; }
    .apoyo-section { background: var(--bg); }

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

    @media (max-width: 600px) { .hero { padding: 3rem 1rem 2.5rem; } .cards-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 400px) { .cards-grid { grid-template-columns: 1fr; } }
</style>

<section class="hero">
    <span class="hero-emoji">🌿</span>
    <h1>Support rural tourism</h1>
    <p>Rutas Rurales connects travellers with authentic rural Spain. Your support helps us keep growing and giving visibility to small local businesses.</p>
    <div class="hero-stats">
        <div class="hero-stat"><strong>+500</strong><span>Accommodations</span></div>
        <div class="hero-stat"><strong>+1,200</strong><span>Places of interest</span></div>
        <div class="hero-stat"><strong>+300</strong><span>Rural events</span></div>
    </div>
</section>

<section class="apoyar-section cafe-section">
    <div class="apoyar-container">
        <div class="section-title">
            <h2>☕ Buy us a coffee</h2>
            <p>The simplest way to support the project. Every coffee helps us keep the servers running and keep improving.</p>
        </div>
        <div class="cards-grid" style="max-width:600px;margin:0 auto;">
            <div class="pay-card" onclick="pagar('CAFE_1')">
                <span class="icon">☕</span>
                <h3>One coffee</h3>
                <p>The smallest gesture with the greatest meaning</p>
                <div class="price">€1.50</div>
                <button class="btn-pay accent">Buy a coffee</button>
            </div>
            <div class="pay-card popular" onclick="pagar('CAFE_2')">
                <span class="badge">⭐ Popular</span>
                <span class="icon">☕☕</span>
                <h3>Two coffees</h3>
                <p>For the whole team. Double the energy!</p>
                <div class="price">€3.00</div>
                <button class="btn-pay accent">Buy two coffees</button>
            </div>
        </div>
    </div>
</section>

<section class="apoyar-section apoyo-section">
    <div class="apoyar-container">
        <div class="section-title">
            <h2>🎯 Voluntary contribution</h2>
            <p>If Rutas Rurales has been useful to you, any contribution helps us keep growing and giving more visibility to rural tourism.</p>
        </div>
        <div class="cards-grid" style="max-width:1100px;margin:0 auto;">
            <div class="pay-card" onclick="pagar('APOYO_5')">
                <span class="icon">🌱</span>
                <h3>Seedling</h3>
                <p>A small contribution with a big impact</p>
                <div class="price">€5</div>
                <button class="btn-pay">Contribute €5</button>
            </div>
            <div class="pay-card popular" onclick="pagar('APOYO_10')">
                <span class="badge">💚 Thanks!</span>
                <span class="icon">🌳</span>
                <h3>Tree</h3>
                <p>Help the project keep growing</p>
                <div class="price">€10</div>
                <button class="btn-pay">Contribute €10</button>
            </div>
            <div class="pay-card" onclick="pagar('APOYO_20')">
                <span class="icon">🏔️</span>
                <h3>Mountain</h3>
                <p>A contribution that makes a real difference</p>
                <div class="price">€20</div>
                <button class="btn-pay">Contribute €20</button>
            </div>
            <div class="pay-card" onclick="pagar('APOYO_50')">
                <span class="icon">🦅</span>
                <h3>Rural Hero</h3>
                <p>You're amazing! Rural tourism thanks you</p>
                <div class="price">€50</div>
                <button class="btn-pay">Contribute €50</button>
            </div>
            <div class="pay-card custom-card">
                <span class="icon">❤️</span>
                <h3>Choose your amount</h3>
                <p>Any amount is welcome (minimum €1)</p>
                <div class="amount-input-wrap">
                    <span>€</span>
                    <input type="number" id="customAmount" min="1" step="0.50" placeholder="e.g. 7.50">
                </div>
                <button class="btn-pay" onclick="pagarImporteLibre()">
                    <i class="fas fa-heart"></i> Contribute
                </button>
            </div>
        </div>
    </div>
</section>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-box">
        <i class="fas fa-spinner fa-spin"></i>
        <p style="color:#666;">Redirecting to secure payment...</p>
        <small style="color:#aaa;">Powered by Stripe 🔒</small>
    </div>
</div>

<div class="payment-result" id="paymentResult">
    <div class="result-box">
        <span class="result-icon" id="resultIcon">🎉</span>
        <h2 id="resultTitle">Thank you!</h2>
        <p id="resultMessage">Your contribution has been processed successfully.</p>
        <button class="btn-pay" onclick="cerrarResultado()" style="max-width:200px;margin:0 auto;">Close</button>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const params = new URLSearchParams(window.location.search);
        if (params.get('payment') === 'success') { mostrarResultado('success', params.get('concept')); window.history.replaceState({}, '', location.pathname); }
        else if (params.get('payment') === 'canceled') { mostrarResultado('canceled'); window.history.replaceState({}, '', location.pathname); }
    });
    async function pagar(conceptCode) {
        mostrarLoading(true);
        try {
            const r = await fetch('/api/create_onetime_payment.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ concept_code: conceptCode, success_url: window.location.origin+'/en/apoyar.php?payment=success', cancel_url: window.location.origin+'/en/apoyar.php?payment=canceled' }) });
            const d = await r.json();
            if (d.success && d.data.checkout_url) { window.location.href = d.data.checkout_url; }
            else { mostrarLoading(false); alert('Error: '+(d.error||'Could not create payment')); }
        } catch(e) { mostrarLoading(false); alert('Connection error. Please try again.'); }
    }
    async function pagarImporteLibre() {
        const input = document.getElementById('customAmount');
        const amount = parseFloat(input.value);
        if (!amount || amount < 1) { input.focus(); input.style.outline='2px solid #e74c3c'; setTimeout(()=>input.style.outline='',2000); alert('Please enter an amount of at least €1'); return; }
        mostrarLoading(true);
        try {
            const r = await fetch('/api/create_onetime_payment.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ concept_code:'APOYO_5', custom_amount: amount, success_url: window.location.origin+'/en/apoyar.php?payment=success', cancel_url: window.location.origin+'/en/apoyar.php?payment=canceled' }) });
            const d = await r.json();
            if (d.success && d.data.checkout_url) { window.location.href = d.data.checkout_url; }
            else { mostrarLoading(false); alert('Error: '+(d.error||'Could not create payment')); }
        } catch(e) { mostrarLoading(false); alert('Connection error.'); }
    }
    function mostrarLoading(s) { document.getElementById('loadingOverlay').classList.toggle('show', s); }
    function cerrarResultado() { document.getElementById('paymentResult').classList.remove('show'); }
    function mostrarResultado(type, concept) {
        const msgs = { 'CAFE_1':'Thanks for the coffee! ☕','CAFE_2':'Two coffees! The whole team thanks you. ☕☕','APOYO_5':'Thank you for your €5 contribution! 🌱','APOYO_10':'Thank you for your €10 contribution! 🌳','APOYO_20':'Incredible! Your €20 contribution makes a real difference. 🏔️','APOYO_50':'You are a rural hero! Thank you for your generosity. 🦅' };
        document.getElementById('resultIcon').textContent = type==='success'?'🎉':'😔';
        document.getElementById('resultTitle').textContent = type==='success'?'Thank you so much!':'Payment cancelled';
        document.getElementById('resultMessage').textContent = type==='success'?(msgs[concept]||'Your payment has been processed. Thank you for supporting rural tourism!'):'No charge was made. You can try again whenever you like.';
        document.getElementById('paymentResult').classList.add('show');
    }
    document.getElementById('paymentResult').addEventListener('click', function(e) { if(e.target===this) cerrarResultado(); });
</script>
