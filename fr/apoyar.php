<?php
$lang = 'fr';
$page_title = 'Soutenez Rutas Rurales — Offrez-nous un café ou faites une contribution';
$page_description = 'Soutenez le tourisme rural durable. Offrez-nous un café ou faites une contribution volontaire à Rutas Rurales.';
$page_canonical = 'https://rutasrurales.io/fr/apoyar.php';
require_once __DIR__ . '/../header.php';
?>

<style>
    :root { --primary:#2f5233;--primary-light:#4a7c59;--accent:#ff8f00;--bg:#f8faf8;--white:#fff;--text:#333;--text-light:#666;--border:#e0e8e0;--shadow:0 4px 20px rgba(47,82,51,.10);--radius:16px; }
    body { padding-top: 80px; }
    @media (max-width: 992px) { body { padding-top: 70px; } }

    .hero {
        background: linear-gradient(135deg, rgba(20,40,22,0.68) 0%, rgba(47,82,51,0.55) 60%, rgba(30,60,35,0.50) 100%),
                    url('/menu_images/laguna_negra.webp') center center / cover no-repeat;
        color: #fff; text-align: center; padding: 5rem 1.5rem 4rem; position: relative; overflow: hidden;
    }
    .hero-emoji{font-size:4rem;margin-bottom:1rem;display:block;position:relative;z-index:1}
    .hero h1{font-size:clamp(1.8rem,4vw,2.8rem);font-weight:800;margin-bottom:1rem;position:relative;z-index:1;text-shadow:0 2px 8px rgba(0,0,0,.4)}
    .hero p{font-size:1.1rem;opacity:.95;max-width:600px;margin:0 auto 2rem;position:relative;z-index:1;text-shadow:0 1px 4px rgba(0,0,0,.3)}
    .hero-stats{display:flex;gap:2rem;justify-content:center;flex-wrap:wrap;margin-top:2rem;position:relative;z-index:1}
    .hero-stat{text-align:center}
    .hero-stat strong{display:block;font-size:1.8rem;font-weight:800;text-shadow:0 2px 6px rgba(0,0,0,.3)}
    .hero-stat span{font-size:.85rem;opacity:.9}

    .apoyar-container{max-width:1100px;margin:0 auto;padding:0 1.5rem}
    .apoyar-section{padding:4rem 0}
    .section-title{text-align:center;margin-bottom:2.5rem}
    .section-title h2{font-size:1.8rem;color:var(--primary);margin-bottom:.5rem}
    .section-title p{color:var(--text-light);font-size:1rem}

    .cards-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem}
    .pay-card{background:var(--white);border-radius:var(--radius);padding:2rem 1.5rem;text-align:center;box-shadow:var(--shadow);border:2px solid transparent;transition:all .3s ease;cursor:pointer;position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:space-between}
    .pay-card:hover{transform:translateY(-6px);box-shadow:0 12px 35px rgba(47,82,51,.18);border-color:var(--primary)}
    .pay-card.popular{border-color:var(--accent)}
    .pay-card .badge{position:absolute;top:12px;right:12px;background:var(--accent);color:#fff;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.05em}
    .pay-card .icon{font-size:2.8rem;margin-bottom:1rem;display:block}
    .pay-card h3{font-size:1.1rem;color:var(--primary);margin-bottom:.5rem;font-weight:700}
    .pay-card p{font-size:.88rem;color:var(--text-light);margin-bottom:1.2rem;min-height:2.5rem}
    .pay-card .price{font-size:2rem;font-weight:800;color:var(--primary);margin-bottom:1.2rem}

    .pay-card.custom-card{cursor:default}
    .pay-card.custom-card:hover{transform:none;border-color:var(--primary)}
    .amount-input-wrap{display:flex;align-items:center;border:2px solid var(--border);border-radius:30px;overflow:hidden;margin-bottom:1rem}
    .amount-input-wrap span{padding:.7rem 1rem;background:var(--bg);font-size:1.1rem;font-weight:700;color:var(--primary);border-right:2px solid var(--border)}
    .amount-input-wrap input{flex:1;padding:.7rem .8rem;border:none;font-size:1.1rem;font-weight:700;color:var(--primary);outline:none;background:#fff;min-width:0}

    .btn-pay{display:inline-block;width:100%;padding:.85rem 1.5rem;background:var(--primary);color:#fff;border:none;border-radius:30px;font-size:1rem;font-weight:700;cursor:pointer;transition:background .2s,transform .1s}
    .btn-pay:hover{background:var(--primary-light)}
    .btn-pay:active{transform:scale(.98)}
    .btn-pay.accent{background:var(--accent)}
    .btn-pay.accent:hover{background:#e67e00}

    .cafe-section{background:linear-gradient(135deg,#fff8e1,#fff3cd)}
    .cafe-section .section-title h2{color:#8B6914}
    .apoyo-section{background:var(--bg)}

    .payment-result{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center}
    .payment-result.show{display:flex}
    .result-box{background:#fff;border-radius:var(--radius);padding:3rem 2rem;text-align:center;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2)}
    .result-box .result-icon{font-size:4rem;margin-bottom:1rem;display:block}
    .result-box h2{color:var(--primary);margin-bottom:.8rem}
    .result-box p{color:var(--text-light);margin-bottom:1.5rem}
    .loading-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;align-items:center;justify-content:center}
    .loading-overlay.show{display:flex}
    .loading-box{background:#fff;border-radius:var(--radius);padding:2rem;text-align:center}
    .loading-box i{font-size:2.5rem;color:var(--primary);margin-bottom:1rem;display:block}

    @media(max-width:600px){.hero{padding:3rem 1rem 2.5rem}.cards-grid{grid-template-columns:1fr 1fr}}
    @media(max-width:400px){.cards-grid{grid-template-columns:1fr}}
</style>

<section class="hero">
    <span class="hero-emoji">🌿</span>
    <h1>Soutenez le tourisme rural</h1>
    <p>Rutas Rurales connecte les voyageurs avec l'Espagne rurale authentique. Votre soutien nous aide à continuer à grandir et à donner de la visibilité aux petites entreprises locales.</p>
    <div class="hero-stats">
        <div class="hero-stat"><strong>+500</strong><span>Hébergements</span></div>
        <div class="hero-stat"><strong>+1 200</strong><span>Lieux d'intérêt</span></div>
        <div class="hero-stat"><strong>+300</strong><span>Événements ruraux</span></div>
    </div>
</section>

<section class="apoyar-section cafe-section">
    <div class="apoyar-container">
        <div class="section-title">
            <h2>☕ Offrez-nous un café</h2>
            <p>La façon la plus simple de soutenir le projet. Chaque café nous aide à maintenir les serveurs et à continuer à nous améliorer.</p>
        </div>
        <div class="cards-grid" style="max-width:600px;margin:0 auto;">
            <div class="pay-card" onclick="pagar('CAFE_1')">
                <span class="icon">☕</span>
                <h3>Un café</h3>
                <p>Le plus petit geste avec la plus grande signification</p>
                <div class="price">1,50€</div>
                <button class="btn-pay accent">Offrir un café</button>
            </div>
            <div class="pay-card popular" onclick="pagar('CAFE_2')">
                <span class="badge">⭐ Populaire</span>
                <span class="icon">☕☕</span>
                <h3>Deux cafés</h3>
                <p>Pour toute l'équipe. Double énergie !</p>
                <div class="price">3,00€</div>
                <button class="btn-pay accent">Offrir deux cafés</button>
            </div>
        </div>
    </div>
</section>

<section class="apoyar-section apoyo-section">
    <div class="apoyar-container">
        <div class="section-title">
            <h2>🎯 Contribution volontaire</h2>
            <p>Si Rutas Rurales vous a été utile, toute contribution nous aide à continuer à grandir et à donner plus de visibilité au tourisme rural.</p>
        </div>
        <div class="cards-grid" style="max-width:1100px;margin:0 auto;">
            <div class="pay-card" onclick="pagar('APOYO_5')">
                <span class="icon">🌱</span><h3>Graine</h3>
                <p>Une petite contribution avec un grand impact</p>
                <div class="price">5€</div>
                <button class="btn-pay">Contribuer 5€</button>
            </div>
            <div class="pay-card popular" onclick="pagar('APOYO_10')">
                <span class="badge">💚 Merci !</span>
                <span class="icon">🌳</span><h3>Arbre</h3>
                <p>Aidez le projet à continuer à grandir</p>
                <div class="price">10€</div>
                <button class="btn-pay">Contribuer 10€</button>
            </div>
            <div class="pay-card" onclick="pagar('APOYO_20')">
                <span class="icon">🏔️</span><h3>Montagne</h3>
                <p>Une contribution qui fait vraiment la différence</p>
                <div class="price">20€</div>
                <button class="btn-pay">Contribuer 20€</button>
            </div>
            <div class="pay-card" onclick="pagar('APOYO_50')">
                <span class="icon">🦅</span><h3>Héros Rural</h3>
                <p>Vous êtes incroyable ! Le tourisme rural vous remercie</p>
                <div class="price">50€</div>
                <button class="btn-pay">Contribuer 50€</button>
            </div>
            <div class="pay-card custom-card">
                <span class="icon">❤️</span>
                <h3>Choisissez votre montant</h3>
                <p>Tout montant est le bienvenu (minimum 1€)</p>
                <div class="amount-input-wrap">
                    <span>€</span>
                    <input type="number" id="customAmount" min="1" step="0.50" placeholder="Ex : 7,50">
                </div>
                <button class="btn-pay" onclick="pagarImporteLibre()">
                    <i class="fas fa-heart"></i> Contribuer
                </button>
            </div>
        </div>
    </div>
</section>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-box"><i class="fas fa-spinner fa-spin"></i><p style="color:#666;">Redirection vers le paiement sécurisé...</p><small style="color:#aaa;">Powered by Stripe 🔒</small></div>
</div>
<div class="payment-result" id="paymentResult">
    <div class="result-box">
        <span class="result-icon" id="resultIcon">🎉</span>
        <h2 id="resultTitle">Merci !</h2>
        <p id="resultMessage">Votre contribution a été traitée avec succès.</p>
        <button class="btn-pay" onclick="cerrarResultado()" style="max-width:200px;margin:0 auto;">Fermer</button>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded',function(){const p=new URLSearchParams(window.location.search);if(p.get('payment')==='success'){mostrarResultado('success',p.get('concept'));window.history.replaceState({},'',location.pathname);}else if(p.get('payment')==='canceled'){mostrarResultado('canceled');window.history.replaceState({},'',location.pathname);}});
    async function pagar(code){mostrarLoading(true);try{const r=await fetch('/api/create_onetime_payment.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({concept_code:code,success_url:window.location.origin+'/fr/apoyar.php?payment=success',cancel_url:window.location.origin+'/fr/apoyar.php?payment=canceled'})});const d=await r.json();if(d.success&&d.data.checkout_url){window.location.href=d.data.checkout_url;}else{mostrarLoading(false);alert('Erreur : '+(d.error||''));}}catch(e){mostrarLoading(false);alert('Erreur de connexion.');}}
    async function pagarImporteLibre(){const input=document.getElementById('customAmount');const a=parseFloat(input.value);if(!a||a<1){input.focus();input.style.outline='2px solid #e74c3c';setTimeout(()=>input.style.outline='',2000);alert('Veuillez entrer un montant d\'au moins 1€');return;}mostrarLoading(true);try{const r=await fetch('/api/create_onetime_payment.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({concept_code:'APOYO_5',custom_amount:a,success_url:window.location.origin+'/fr/apoyar.php?payment=success',cancel_url:window.location.origin+'/fr/apoyar.php?payment=canceled'})});const d=await r.json();if(d.success&&d.data.checkout_url){window.location.href=d.data.checkout_url;}else{mostrarLoading(false);alert('Erreur : '+(d.error||''));}}catch(e){mostrarLoading(false);alert('Erreur de connexion.');}}
    function mostrarLoading(s){document.getElementById('loadingOverlay').classList.toggle('show',s);}
    function cerrarResultado(){document.getElementById('paymentResult').classList.remove('show');}
    function mostrarResultado(type,concept){const msgs={'CAFE_1':'Merci pour le café ! ☕','CAFE_2':'Deux cafés ! Toute l\'équipe vous remercie. ☕☕','APOYO_5':'Merci pour votre contribution de 5€ ! 🌱','APOYO_10':'Merci pour votre contribution de 10€ ! 🌳','APOYO_20':'Incroyable ! Votre contribution de 20€ fait la différence. 🏔️','APOYO_50':'Vous êtes un héros rural ! Merci pour votre générosité. 🦅'};document.getElementById('resultIcon').textContent=type==='success'?'🎉':'😔';document.getElementById('resultTitle').textContent=type==='success'?'Merci beaucoup !':'Paiement annulé';document.getElementById('resultMessage').textContent=type==='success'?(msgs[concept]||'Votre paiement a été traité. Merci de soutenir le tourisme rural !'):'Aucun débit n\'a été effectué.';document.getElementById('paymentResult').classList.add('show');}
    document.getElementById('paymentResult').addEventListener('click',function(e){if(e.target===this)cerrarResultado();});
</script>
