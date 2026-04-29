<?php
$lang = 'zh';
$page_title = '支持 Rutas Rurales — 请我们喝杯咖啡';
$page_description = '支持可持续乡村旅游。请我们喝杯咖啡或向 Rutas Rurales 自愿捐款。';
$page_canonical = 'https://www.rutasrurales.io/zh/apoyar.php';
require_once __DIR__ . '/../header.php';
?>

<style>
    :root{--primary:#2f5233;--primary-light:#4a7c59;--accent:#ff8f00;--bg:#f8faf8;--white:#fff;--text:#333;--text-light:#666;--border:#e0e8e0;--shadow:0 4px 20px rgba(47,82,51,.10);--radius:16px}
    body { padding-top: 80px; font-family:'Microsoft YaHei','PingFang SC',system-ui,sans-serif; line-height:1.7; }
    @media (max-width: 992px) { body { padding-top: 70px; } }

    .hero{background:linear-gradient(135deg,var(--primary) 0%,var(--primary-light) 60%,#5a9e6f 100%);color:#fff;text-align:center;padding:4rem 1.5rem 3.5rem;position:relative;overflow:hidden}
    .hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .hero-emoji{font-size:4rem;margin-bottom:1rem;display:block}
    .hero h1{font-size:clamp(1.6rem,4vw,2.5rem);font-weight:800;margin-bottom:1rem}
    .hero p{font-size:1.05rem;opacity:.9;max-width:600px;margin:0 auto 2rem}
    .hero-stats{display:flex;gap:2rem;justify-content:center;flex-wrap:wrap;margin-top:2rem}
    .hero-stat{text-align:center}
    .hero-stat strong{display:block;font-size:1.8rem;font-weight:800}
    .hero-stat span{font-size:.85rem;opacity:.8}

    .apoyar-container{max-width:1100px;margin:0 auto;padding:0 1.5rem}
    .apoyar-section{padding:4rem 0}
    .section-title{text-align:center;margin-bottom:2.5rem}
    .section-title h2{font-size:1.7rem;color:var(--primary);margin-bottom:.5rem}
    .section-title p{color:var(--text-light);font-size:1rem}

    .cards-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem}
    .pay-card{background:var(--white);border-radius:var(--radius);padding:2rem 1.5rem;text-align:center;box-shadow:var(--shadow);border:2px solid transparent;transition:all .3s ease;cursor:pointer;position:relative;overflow:hidden}
    .pay-card:hover{transform:translateY(-6px);box-shadow:0 12px 35px rgba(47,82,51,.18);border-color:var(--primary)}
    .pay-card.popular{border-color:var(--accent)}
    .pay-card .badge{position:absolute;top:12px;right:12px;background:var(--accent);color:#fff;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px}
    .pay-card .icon{font-size:2.8rem;margin-bottom:1rem;display:block}
    .pay-card h3{font-size:1.1rem;color:var(--primary);margin-bottom:.5rem;font-weight:700}
    .pay-card p{font-size:.88rem;color:var(--text-light);margin-bottom:1.2rem;min-height:2.5rem}
    .pay-card .price{font-size:2rem;font-weight:800;color:var(--primary);margin-bottom:1.2rem}

    .btn-pay{display:inline-block;width:100%;padding:.85rem 1.5rem;background:var(--primary);color:#fff;border:none;border-radius:30px;font-size:1rem;font-weight:700;cursor:pointer;transition:background .2s,transform .1s}
    .btn-pay:hover{background:var(--primary-light)}
    .btn-pay:active{transform:scale(.98)}
    .btn-pay.accent{background:var(--accent)}
    .btn-pay.accent:hover{background:#e67e00}

    .cafe-section{background:linear-gradient(135deg,#fff8e1,#fff3cd)}
    .cafe-section .section-title h2{color:#8B6914}
    .apoyo-section{background:var(--bg)}

    .custom-amount-box{background:#fff;border-radius:var(--radius);padding:2rem;box-shadow:var(--shadow);max-width:500px;margin:2rem auto 0;text-align:center}
    .custom-amount-box h3{color:var(--primary);margin-bottom:1rem}
    .amount-input-wrap{display:flex;align-items:center;border:2px solid var(--border);border-radius:30px;overflow:hidden;margin-bottom:1rem}
    .amount-input-wrap span{padding:.8rem 1.2rem;background:var(--bg);font-size:1.2rem;font-weight:700;color:var(--primary);border-right:2px solid var(--border)}
    .amount-input-wrap input{flex:1;padding:.8rem 1rem;border:none;font-size:1.2rem;font-weight:700;color:var(--primary);outline:none;background:#fff}

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
    <h1>支持乡村旅游</h1>
    <p>Rutas Rurales 将旅行者与真实的西班牙乡村连接起来。您的支持帮助我们不断成长，为当地小企业提供更多曝光机会。</p>
    <div class="hero-stats">
        <div class="hero-stat"><strong>+500</strong><span>住宿</span></div>
        <div class="hero-stat"><strong>+1,200</strong><span>景点</span></div>
        <div class="hero-stat"><strong>+300</strong><span>乡村活动</span></div>
    </div>
</section>

<!-- ☕ 请我们喝咖啡 -->
<section class="apoyar-section cafe-section">
    <div class="apoyar-container">
        <div class="section-title">
            <h2>☕ 请我们喝杯咖啡</h2>
            <p>支持项目最简单的方式。每杯咖啡都帮助我们维持服务器运行并持续改进。</p>
        </div>
        <div class="cards-grid" style="max-width:600px;margin:0 auto;">
            <div class="pay-card" onclick="pagar('CAFE_1')">
                <span class="icon">☕</span>
                <h3>一杯咖啡</h3>
                <p>最小的举动，最大的意义</p>
                <div class="price">€1.50</div>
                <button class="btn-pay accent">请喝一杯咖啡</button>
            </div>
            <div class="pay-card popular" onclick="pagar('CAFE_2')">
                <span class="badge">⭐ 热门</span>
                <span class="icon">☕☕</span>
                <h3>两杯咖啡</h3>
                <p>为整个团队。双倍能量！</p>
                <div class="price">€3.00</div>
                <button class="btn-pay accent">请喝两杯咖啡</button>
            </div>
        </div>
    </div>
</section>

<!-- 🎯 自愿捐款 -->
<section class="apoyar-section apoyo-section">
    <div class="apoyar-container">
        <div class="section-title">
            <h2>🎯 自愿捐款</h2>
            <p>如果 Rutas Rurales 对您有帮助，任何捐款都能帮助我们继续成长，为乡村旅游提供更多曝光。</p>
        </div>
        <div class="cards-grid" style="max-width:800px;margin:0 auto;">
            <div class="pay-card" onclick="pagar('APOYO_5')">
                <span class="icon">🌱</span>
                <h3>种子</h3>
                <p>小小贡献，大大影响</p>
                <div class="price">€5</div>
                <button class="btn-pay">捐款 €5</button>
            </div>
            <div class="pay-card popular" onclick="pagar('APOYO_10')">
                <span class="badge">💚 谢谢！</span>
                <span class="icon">🌳</span>
                <h3>大树</h3>
                <p>帮助项目继续成长</p>
                <div class="price">€10</div>
                <button class="btn-pay">捐款 €10</button>
            </div>
            <div class="pay-card" onclick="pagar('APOYO_20')">
                <span class="icon">🏔️</span>
                <h3>山峰</h3>
                <p>真正改变现状的贡献</p>
                <div class="price">€20</div>
                <button class="btn-pay">捐款 €20</button>
            </div>
            <div class="pay-card" onclick="pagar('APOYO_50')">
                <span class="icon">🦅</span>
                <h3>乡村英雄</h3>
                <p>您太棒了！乡村旅游感谢您</p>
                <div class="price">€50</div>
                <button class="btn-pay">捐款 €50</button>
            </div>
        </div>
        <div class="custom-amount-box">
            <h3><i class="fas fa-heart" style="color:#e74c3c;"></i> 选择您的金额</h3>
            <p style="color:var(--text-light);font-size:.9rem;margin-bottom:1rem;">任何金额都受欢迎（最低 €1）</p>
            <div class="amount-input-wrap">
                <span>€</span>
                <input type="number" id="customAmount" min="1" step="0.50" placeholder="例如：7.50">
            </div>
            <button class="btn-pay" onclick="pagarImporteLibre()" style="max-width:300px;">
                <i class="fas fa-heart"></i> 按我的金额捐款
            </button>
        </div>
    </div>
</section>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-box">
        <i class="fas fa-spinner fa-spin"></i>
        <p style="color:#666;">正在跳转到安全支付页面...</p>
        <small style="color:#aaa;">由 Stripe 提供安全支付 🔒</small>
    </div>
</div>

<div class="payment-result" id="paymentResult">
    <div class="result-box">
        <span class="result-icon" id="resultIcon">🎉</span>
        <h2 id="resultTitle">非常感谢！</h2>
        <p id="resultMessage">您的捐款已成功处理。</p>
        <button class="btn-pay" onclick="cerrarResultado()" style="max-width:200px;margin:0 auto;">关闭</button>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded',function(){
        const p=new URLSearchParams(window.location.search);
        if(p.get('payment')==='success'){mostrarResultado('success',p.get('concept'));window.history.replaceState({},'',location.pathname);}
        else if(p.get('payment')==='canceled'){mostrarResultado('canceled');window.history.replaceState({},'',location.pathname);}
    });
    async function pagar(code){
        mostrarLoading(true);
        try{const r=await fetch('/api/create_onetime_payment.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({concept_code:code,success_url:window.location.origin+'/zh/apoyar.php?payment=success',cancel_url:window.location.origin+'/zh/apoyar.php?payment=canceled'})});
        const d=await r.json();if(d.success&&d.data.checkout_url){window.location.href=d.data.checkout_url;}else{mostrarLoading(false);alert('错误：'+(d.error||'无法创建支付'));}}
        catch(e){mostrarLoading(false);alert('连接错误，请重试。');}
    }
    async function pagarImporteLibre(){
        const a=parseFloat(document.getElementById('customAmount').value);
        if(!a||a<1){alert('请输入至少 €1 的金额');return;}
        mostrarLoading(true);
        try{const r=await fetch('/api/create_onetime_payment.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({concept_code:'APOYO_5',custom_amount:a,success_url:window.location.origin+'/zh/apoyar.php?payment=success',cancel_url:window.location.origin+'/zh/apoyar.php?payment=canceled'})});
        const d=await r.json();if(d.success&&d.data.checkout_url){window.location.href=d.data.checkout_url;}else{mostrarLoading(false);alert('错误：'+(d.error||''));}}
        catch(e){mostrarLoading(false);alert('连接错误。');}
    }
    function mostrarLoading(s){document.getElementById('loadingOverlay').classList.toggle('show',s);}
    function cerrarResultado(){document.getElementById('paymentResult').classList.remove('show');}
    function mostrarResultado(type,concept){
        const msgs={'CAFE_1':'感谢您的咖啡！这给了我们继续改进的动力。☕','CAFE_2':'两杯咖啡！整个团队感谢您。☕☕','APOYO_5':'感谢您的 €5 捐款！乡村旅游感谢您。🌱','APOYO_10':'感谢您的 €10 捐款！您是我们的重要支持。🌳','APOYO_20':'太棒了！您的 €20 捐款真的很有意义。🏔️','APOYO_50':'您是乡村英雄！感谢您的慷慨。🦅'};
        document.getElementById('resultIcon').textContent=type==='success'?'🎉':'😔';
        document.getElementById('resultTitle').textContent=type==='success'?'非常感谢！':'支付已取消';
        document.getElementById('resultMessage').textContent=type==='success'?(msgs[concept]||'您的支付已处理。感谢支持乡村旅游！'):'未产生任何费用。您可以随时重试。';
        document.getElementById('paymentResult').classList.add('show');
    }
    document.getElementById('paymentResult').addEventListener('click',function(e){if(e.target===this)cerrarResultado();});
</script>
