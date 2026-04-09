<?php 
    $page_title = "Únete a Rutas Rurales | Impulsa tu Bodega o Restaurante";
    $page_description = "Forma parte de la red de Rutas Rurales. Aumenta la visibilidad de tu bodega, bar o restaurante conectando directamente con turistas rurales y amantes de la gastronomía.";
    $page_canonical = "https://rutasurales.io/unete-restaurante-bodega.php";
    include 'header.php'; 
?>

<style>
    :root {
        --primary-color: #2c5f2d;
        --accent-color: #d4a574;
    }
    
    .hero-join {
        background: linear-gradient(rgba(44, 95, 45, 0.8), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        padding: 160px 20px 100px;
        color: white;
        text-align: center;
        position: relative;
        margin-top: 60px; /* Offset for header */
    }
    
    .hero-join h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 24px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    }
    
    .hero-join .lead {
        font-size: 1.5rem;
        max-width: 800px;
        margin: 0 auto 40px;
        font-weight: 300;
        line-height: 1.6;
    }
    
    .card-benefit {
        border: none;
        transition: all 0.4s ease;
        padding: 40px 30px;
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        height: 100%;
        position: relative;
        overflow: hidden;
        border-top: 4px solid var(--accent-color);
    }
    
    .card-benefit:hover { 
        transform: translateY(-10px); 
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }
    
    .card-benefit .icon-wrapper {
        width: 80px;
        height: 80px;
        background: rgba(212, 165, 116, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        font-size: 35px;
        color: var(--accent-color);
    }
    
    .card-benefit h3 {
        font-weight: 700;
        margin-bottom: 15px;
        color: #333;
    }
    
    .card-benefit p {
        color: #666;
        line-height: 1.7;
    }
    
    .btn-registro {
        background-color: var(--accent-color);
        border: none;
        padding: 18px 40px;
        font-weight: 700;
        font-size: 1.1rem;
        color: white;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 10px 20px rgba(212, 165, 116, 0.3);
        display: inline-block;
        text-decoration: none;
    }
    
    .btn-registro:hover {
        background-color: #b88a5b;
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }
    
    .section-title {
        text-align: center;
        margin-bottom: 60px;
    }
    
    .section-title h2 {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary-color);
        margin-bottom: 20px;
    }
    
    .section-title p {
        color: #666;
        font-size: 1.2rem;
        max-width: 700px;
        margin: 0 auto;
    }
    
    .stats-section {
        background: var(--primary-color);
        color: white;
        padding: 80px 0;
    }
    
    .stat-item {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .stat-number {
        font-size: 3.5rem;
        font-weight: 800;
        color: var(--accent-color);
        margin-bottom: 10px;
    }
    
    .stat-text {
        font-size: 1.1rem;
        font-weight: 500;
    }
    
    .form-section {
        background-color: #f8f9fa;
        padding: 100px 0;
    }
    
    .form-container {
        background: white;
        padding: 50px;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    }
    
    .form-control {
        padding: 15px 20px;
        border-radius: 10px;
        border: 1px solid #e0e0e0;
        background-color: #fcfcfc;
        transition: all 0.3s ease;
        height: auto;
    }
    
    .form-control:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 0 0.2rem rgba(212, 165, 116, 0.25);
        background-color: #fff;
    }
    
    .trust-badges {
        display: flex;
        justify-content: center;
        gap: 30px;
        flex-wrap: wrap;
        margin-top: 40px;
        opacity: 0.9;
    }
    
    .trust-badges div {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
    
    .trust-badges i {
        font-size: 40px;
        color: var(--accent-color);
    }
    
    .trust-badges span {
        font-size: 0.9rem;
        font-weight: 600;
    }

    /* SEO Content Area */
    .seo-content {
        padding: 80px 0;
        background: #fff;
        line-height: 1.8;
        color: #555;
    }
    
    .seo-content h2 {
        color: var(--primary-color);
        font-weight: 800;
        margin-bottom: 30px;
        font-size: 2.2rem;
    }
    
    .seo-content h3 {
        color: var(--primary-color);
        font-weight: 700;
        margin-top: 40px;
        margin-bottom: 20px;
        font-size: 1.8rem;
    }

    @media (max-width: 768px) {
        .hero-join h1 { font-size: 2.2rem; }
        .hero-join .lead { font-size: 1.2rem; }
        .form-container { padding: 30px 20px; }
        .hero-join { margin-top: 50px; padding: 100px 20px 60px; }
        .stat-number { font-size: 2.5rem; }
    }
</style>

<div class="container-fluid p-0">
    <!-- HERO SECTION -->
    <section class="hero-join">
        <div class="container">
            <h1>Rutas Rurales: El Aliado Estratégico para tu Negocio</h1>
            <p class="lead">Conectamos la excelencia de la gastronomía y la enología local con una comunidad exclusiva de viajeros y amantes del turismo rural.</p>
            <a href="#formulario" class="btn-registro"><i class="fas fa-handshake"></i> Solicitar Alianza Comercial</a>
            
            <div class="trust-badges mt-5">
                <div title="Plataforma Segura"><i class="fas fa-shield-alt"></i><span>Plataforma Segura</span></div>
                <div title="Soporte 24/7"><i class="fas fa-headset"></i><span>Soporte 24/7</span></div>
                <div title="Comunidad Premium"><i class="fas fa-star"></i><span>Turistas Premium</span></div>
                <div title="Crecimiento Demostrado"><i class="fas fa-chart-line"></i><span>Crecimiento B2B</span></div>
            </div>
        </div>
    </section>

    <!-- BENEFICIOS B2B -->
    <section class="container my-5 py-5">
        <div class="section-title">
            <h2>¿Por qué unirte a nuestra red?</h2>
            <p>Diseñado específicamente para impulsar la rentabilidad y visibilidad de bodegas, restaurantes y espacios enoturísticos de calidad.</p>
        </div>
        
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="card-benefit">
                    <div class="icon-wrapper">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h3>Visibilidad Premium</h3>
                    <p>Posicionamiento destacado en nuestros mapas interactivos de rutas turísticas, consultados a diario por viajeros con alto poder adquisitivo que buscan experiencias auténticas.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card-benefit">
                    <div class="icon-wrapper">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <h3>Sinergia B2B Directa</h3>
                    <p>Fomentamos el ecosistema local: conectamos a los productores y bodegueros con los restauradores de la zona, creando colaboraciones rentables y sostenibles sin intermediarios.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card-benefit">
                    <div class="icon-wrapper">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Incremento de Ingresos</h3>
                    <p>Atrae a nuevos clientes fuera de temporada, aumenta el ticket medio de tu establecimiento y gestiona reservas de grupos interesados en turismo gastronómico y enológico.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ESTADÍSTICAS -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6 stat-item">
                    <div class="stat-number">+50k</div>
                    <div class="stat-text">Turistas Activos</div>
                </div>
                <div class="col-md-3 col-6 stat-item">
                    <div class="stat-number">+500</div>
                    <div class="stat-text">Negocios Asociados</div>
                </div>
                <div class="col-md-3 col-6 stat-item">
                    <div class="stat-number">40%</div>
                    <div class="stat-text">Aumento de Reservas</div>
                </div>
                <div class="col-md-3 col-6 stat-item">
                    <div class="stat-number">100%</div>
                    <div class="stat-text">Sin Comisiones Ocultas</div>
                </div>
            </div>
        </div>
    </section>

    <!-- FORMULARIO DE ALTA -->
    <section id="formulario" class="form-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="form-container">
                        <div class="text-center mb-5">
                            <h2 style="color: var(--primary-color); font-weight: 800;">Impulsa tu Negocio Hoy</h2>
                            <p class="text-muted" style="font-size: 1.1rem;">Déjanos tus datos y un asesor especializado en desarrollo de negocio se pondrá en contacto contigo en menos de 24 horas.</p>
                        </div>
                        
                        <form action="enviar.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="font-weight-bold text-dark mb-2">Nombre del Establecimiento *</label>
                                    <input type="text" name="nombre" class="form-control" placeholder="Ej. Bodega Los Álamos" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="font-weight-bold text-dark mb-2">Tipo de Negocio *</label>
                                    <select name="tipo" class="form-control" required style="appearance: auto;">
                                        <option value="" disabled selected>Selecciona una opción...</option>
                                        <option value="restaurante">Restaurante / Bar / Gastronomía</option>
                                        <option value="bodega">Bodega / Productor Local</option>
                                        <option value="enoturismo">Espacio Enoturístico Mixto</option>
                                        <option value="otro">Otro tipo de negocio local</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="font-weight-bold text-dark mb-2">Persona de Contacto *</label>
                                    <input type="text" name="contacto" class="form-control" placeholder="Tu nombre completo" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="font-weight-bold text-dark mb-2">Cargo</label>
                                    <input type="text" name="cargo" class="form-control" placeholder="Ej. Gerente, Propietario...">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="font-weight-bold text-dark mb-2">Correo Electrónico Corporativo *</label>
                                    <input type="email" name="email" class="form-control" placeholder="email@tunegocio.com" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="font-weight-bold text-dark mb-2">Teléfono Directo *</label>
                                    <input type="tel" name="telefono" class="form-control" placeholder="+34 XXX XXX XXX" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="font-weight-bold text-dark mb-2">¿Cómo podemos ayudarte? (Opcional)</label>
                                <textarea name="mensaje" class="form-control" rows="4" placeholder="Cuéntanos brevemente sobre tu negocio o tus objetivos de crecimiento..."></textarea>
                            </div>
                            
                            <div class="mb-4 custom-control custom-checkbox d-flex align-items-center gap-2" style="gap: 10px;">
                                <input type="checkbox" class="custom-control-input" id="privacidad" name="privacidad" required style="width: 20px; height: 20px; margin-top: 3px;">
                                <label class="custom-control-label text-muted m-0" for="privacidad" style="font-size: 0.95rem; cursor: pointer;">
                                    He leído y acepto la <a href="/aviso-legal.html" style="color: var(--accent-color); font-weight: 600;">política de privacidad</a> y el tratamiento de mis datos para gestión comercial.
                                </label>
                            </div>

                            <button type="submit" class="btn-registro w-100 mt-3" style="font-size: 1.2rem; padding: 20px;">
                                <i class="fas fa-paper-plane"></i> Enviar Solicitud de Alianza
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SEO & CONTENT SECTION -->
    <section class="seo-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h2>Impulsa el enoturismo y turismo gastronómico en tu región</h2>
                    <p style="font-size: 1.15rem;">En <strong>Rutas Rurales</strong> entendemos que la riqueza de una región no solo se mide por sus paisajes, sino por los sabores de su tierra y la hospitalidad de su gente. Nuestro portal está diseñado para ser el puente perfecto entre turistas en busca de experiencias culinarias auténticas y los mejores <strong>restaurantes, bodegas y bares</strong> locales.</p>
                    
                    <h3>¿Eres una Bodega buscando nuevos canales de venta y visitas?</h3>
                    <p>El enoturismo está en auge. Al dar de alta tu bodega en nuestra plataforma, no solo consigues reservas para catas y visitas guiadas, sino que te posicionas frente a un público altamente segmentado. Además, nuestra red B2B te permite conectar con restaurantes cercanos que buscan ampliar su carta de vinos con productos locales y de calidad (KM0), fortaleciendo la economía circular de la región.</p>

                    <h3>La oportunidad perfecta para Restaurantes y Bares de calidad</h3>
                    <p>Destaca la identidad gastronómica de tu restaurante. Los viajeros de Rutas Rurales buscan lugares excepcionales donde comer bien tras una jornada de turismo y exploración. Publicita tus menús degustación, jornadas gastronómicas o maridajes especiales. Ser parte de nuestra red te acredita como un punto de interés culinario recomendado, mejorando tu reputación online y atrayendo comensales de alto valor que aprecian la buena mesa.</p>

                    <h3>Un Ecosistema B2B de Crecimiento Mutuo</h3>
                    <p>Creemos en la fuerza de la colaboración. Nuestro ecosistema no solo conecta negocios con turistas, sino negocios con negocios. Facilita encuentros, acuerdos comerciales y patrocinios cruzados entre alojamientos, restaurantes, productores locales y bodegas, creando un tejido empresarial robusto y resiliente en el entorno rural.</p>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'footer.php'; ?>