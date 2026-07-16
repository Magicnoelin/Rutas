<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tu pasaporte digital gratuito para descubrir la España rural. Acumula sellos en alojamientos Premium y viaja con descuentos exclusivos.">
    <title>Pasaporte Rural — Tu Pasaporte a la Aventura | rutasrurales.io</title>
    <!-- Bootstrap 5 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --p-primary: #2d4a2d;
            --p-accent: #4CAF50;
            --p-light-green: #e8f5e0;
            --p-gold: #ffb300;
        }

        .como-funciona-body {
            background: linear-gradient(135deg, #f9fbf7 0%, #f1f8ed 100%);
            min-height: 100vh;
            padding: 2rem 0;
            font-family: system-ui, -apple-system, sans-serif;
        }

        /* Hero Image & Hook */
        .hero-card {
            background: linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.65)), url('https://images.unsplash.com/photo-1543731068-7e0f5beff43a?auto=format&fit=crop&w=800&q=80') center/cover no-repeat;
            border-radius: 20px;
            padding: 3.5rem 2rem;
            color: white;
            text-align: center;
            box-shadow: 0 10px 30px rgba(45,74,45,0.15);
            margin-bottom: 2.5rem;
        }

        .hero-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 1rem;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .como-funciona-header h1 {
            font-size: 2.6rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .como-funciona-header .subtitle {
            font-size: 1.25rem;
            font-weight: 400;
            opacity: 0.95;
            max-width: 480px;
            margin: 0 auto;
        }

        /* Pasos Visuales con Conectores */
        .paso-card {
            background: white;
            border-radius: 18px;
            padding: 1.8rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            transition: all 0.3s ease;
        }

        .paso-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(76, 175, 80, 0.1);
        }

        .paso-numero {
            width: 40px;
            height: 40px;
            background: var(--p-primary);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 10px rgba(45,74,45,0.2);
        }

        .paso-titulo {
            font-weight: 800;
            font-size: 1.3rem;
            color: #1a2e1a;
            margin-bottom: 0.5rem;
        }

        .paso-texto {
            color: #555;
            font-size: 0.98rem;
            line-height: 1.6;
        }

        /* Secciones Generales */
        .beneficios-section, .faq-section {
            background: white;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            margin: 2.5rem 0;
            box-shadow: 0 6px 20px rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.04);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--p-primary);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--p-accent);
        }

        /* Beneficios */
        .beneficio-item {
            display: flex;
            align-items: flex-start;
            gap: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .beneficio-item:last-child { margin-bottom: 0; }

        .beneficio-icono {
            width: 48px;
            height: 48px;
            background: var(--p-light-green);
            color: var(--p-primary);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .beneficio-titulo {
            font-weight: 700;
            color: #1a2e1a;
            font-size: 1.1rem;
            margin-bottom: 0.2rem;
        }

        .beneficio-texto {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Rangos/Niveles Visuales */
        .niveles-container {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .nivel-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.2rem;
            background: white;
            border-radius: 14px;
            border: 1px solid rgba(0,0,0,0.06);
            transition: all 0.2s;
        }

        .nivel-row:hover {
            border-color: var(--p-accent);
            background: #fafdf8;
        }

        .nivel-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 700;
            color: #1a2e1a;
        }

        .nivel-badge-puntos {
            background: #f0f0f0;
            color: #555;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* FAQs */
        .faq-item {
            border-bottom: 1px solid #edf2f7;
            padding: 1.2rem 0;
        }
        .faq-item:last-child { border-bottom: none; }
        .faq-pregunta {
            font-weight: 700;
            color: #1a2e1a;
            margin-bottom: 0.5rem;
            font-size: 1.05rem;
        }
        .faq-respuesta {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* CTA */
        .cta-section {
            text-align: center;
            padding: 3rem 1.5rem;
            background: linear-gradient(135deg, var(--p-primary) 0%, #152515 100%);
            border-radius: 24px;
            color: white;
            margin-top: 3rem;
            box-shadow: 0 15px 35px rgba(45,74,45,0.25);
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=600&q=50') center/cover no-repeat;
            opacity: 0.08;
        }

        .cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1.2rem 3rem;
            background: var(--p-accent);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.2rem;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 5px 20px rgba(76, 175, 80, 0.4);
            position: relative;
            z-index: 2;
        }

        .cta-btn:hover {
            background: #43a047;
            color: white;
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.6);
        }

        .volver-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--p-primary);
            text-decoration: none;
            font-weight: 700;
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease;
        }
        .volver-link:hover {
            transform: translateX(-3px);
            color: var(--p-accent);
        }

        .pasaporte-footer {
            text-align: center;
            margin-top: 4rem;
            color: #888;
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="como-funciona-body">
    <div class="container" style="max-width: 600px;">
        
        <!-- Volver -->
        <a href="/pasaporte_rural/mi-pasaporte.php" class="volver-link">
            <i class="fa-solid fa-chevron-left"></i> Volver a mi pasaporte
        </a>
        
        <!-- Cabecera / Hero con GANCHO -->
        <div class="hero-card">
            <span class="hero-badge"><i class="fa-solid fa-sparkles me-1"></i> Club Exclusivo</span>
            <div class="como-funciona-header">
                <h1>Tu Pasaporte Rural</h1>
                <p class="subtitle">Explora la España auténtica, colecciona sellos digitales y desbloquea descuentos de por vida.</p>
            </div>
        </div>

        <!-- Imagen de Apoyo QR / Experiencia -->
        <div class="text-center mb-4">
            <img src="https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=600&h=280&q=80" class="img-fluid rounded-4 shadow-sm" alt="Viajero rural con mapa y móvil">
        </div>
        
        <!-- Pasos Simplificados -->
        <h2 class="section-title"><i class="fa-solid fa-route"></i>Tu ruta al ahorro en 4 pasos</h2>
        
        <div class="paso-card">
            <div class="paso-numero">1</div>
            <div class="paso-titulo">Consigue tu pase gratis</div>
            <div class="paso-texto">
                Te registras en 30 segundos y te regalamos tu primer <strong>5% de descuento de bienvenida</strong> directo en tu panel.
            </div>
        </div>
        
        <div class="paso-card">
            <div class="paso-numero">2</div>
            <div class="paso-titulo">Elige alojamientos Premium</div>
            <div class="paso-texto">
                Reserva en las escapadas rurales etiquetadas con la estrella <i class="fa-solid fa-star" style="color: var(--p-gold);"></i> <strong>Premium</strong>. Al llegar, solo enseña tu móvil.
            </div>
        </div>
        
        <div class="paso-card">
            <div class="paso-numero">3</div>
            <div class="paso-titulo">Sella digitalmente</div>
            <div class="paso-texto">
                Al hacer el checkout, el anfitrión escaneará tu código para sellar tu visita. ¡Cuidar la casa como si fuera tuya te da el doble de puntos!
            </div>
        </div>
        
        <div class="paso-card">
            <div class="paso-numero">4</div>
            <div class="paso-titulo">Sube de nivel y acumula</div>
            <div class="paso-texto">
                Más sellos significan más rango. Evoluciona tu estatus y asegura hasta un <strong>10% de descuento fijo</strong> para siempre.
            </div>
        </div>
        
        <!-- Beneficios Flash -->
        <div class="beneficios-section">
            <h2 class="section-title"><i class="fa-solid fa-gift"></i>¿Por qué unirse al club?</h2>
            
            <div class="beneficio-item">
                <div class="beneficio-icono"><i class="fa-solid fa-wallet"></i></div>
                <div>
                    <div class="beneficio-titulo">Viajar más por menos</div>
                    <div class="beneficio-texto">Ahorro real y directo del 5% al 10% aplicado en tus reservas en la plataforma.</div>
                </div>
            </div>
            
            <div class="beneficio-item">
                <div class="beneficio-icono"><i class="fa-solid fa-crown"></i></div>
                <div>
                    <div class="beneficio-titulo">Ventajas VIP exclusivas</div>
                    <div class="beneficio-texto">Acceso prioritario a nuevas rutas y alojamientos boutique antes que nadie.</div>
                </div>
            </div>
            
            <div class="beneficio-item">
                <div class="beneficio-icono"><i class="fa-solid fa-heart-circle-check"></i></div>
                <div>
                    <div class="beneficio-titulo">Comunidad con valores</div>
                    <div class="beneficio-texto">Premiamos a los viajeros ejemplares que cuidan el entorno rural y sus casas.</div>
                </div>
            </div>
        </div>
        
        <!-- Niveles de Estatus Modificados -->
        <div class="faq-section">
            <h2 class="section-title"><i class="fa-solid fa-ranking-star"></i>Rangos de Explorador</h2>
            <p style="color: #666; font-size: 0.95rem; margin-bottom: 1.2rem;">Suma puntos en cada escapada y evoluciona tu perfil:</p>
            
            <div class="niveles-container">
                <div class="nivel-row">
                    <div class="nivel-info"><span>🌱</span> Viajero Inicial</div>
                    <span class="nivel-badge-puntos">0 - 100 pts</span>
                </div>
                <div class="nivel-row" style="border-left: 4px solid var(--p-accent);">
                    <div class="nivel-info"><span>🗺️</span> Explorador Frecuente</div>
                    <span class="nivel-badge-puntos" style="background: var(--p-light-green); color: var(--p-primary);">101 - 300 pts</span>
                </div>
                <div class="nivel-row" style="border-left: 4px solid var(--p-gold);">
                    <div class="nivel-info"><span>🏅</span> Embajador Rural</div>
                    <span class="nivel-badge-puntos" style="background: #fff8e1; color: #b78103;">301+ pts</span>
                </div>
            </div>
        </div>
        
        <!-- FAQ Directas -->
        <div class="faq-section">
            <h2 class="section-title"><i class="fa-solid fa-circle-question"></i>Resolvemos tus dudas en 1 minuto</h2>
            
            <div class="faq-item">
                <div class="faq-pregunta">¿Realmente es gratis?</div>
                <div class="faq-respuesta">Sí, al 100%. No hay trampa ni suscripciones ocultas. Es nuestro regalo por descubrir el entorno rural con nosotros.</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-pregunta">¿Dónde está mi código QR?</div>
                <div class="faq-respuesta">Lo tienes siempre disponible en tu panel de usuario de <strong>rutasrurales.io</strong>. Llévalo en el móvil y enséñalo al llegar al alojamiento.</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-pregunta">¿Cómo se consiguen los puntos extra?</div>
                <div class="faq-respuesta">Al salir del alojamiento, los propietarios valoran el cuidado de la casa. Si la convivencia ha sido excelente, ¡recibes un extra de puntos directo!</div>
            </div>
        </div>
        
        <!-- CTA Principal Impactante -->
        <div class="cta-section">
            <h3 class="mb-3" style="font-weight: 800; position: relative; z-index: 2;">¿Listo para tu próxima escapada?</h3>
            <p class="mb-4" style="font-size: 1rem; opacity: 0.9; position: relative; z-index: 2;">Activa tu pasaporte ahora mismo y empieza a ahorrar desde hoy.</p>
            
            <a href="/pasaporte_rural/mi-pasaporte.php" class="cta-btn">
                <i class="fa-solid fa-qrcode"></i> Activar Mi Pasaporte Gratis
            </a>
            
            <p style="margin-top: 1.5rem; font-size: 0.95rem; opacity: 0.8; position: relative; z-index: 2;">
                ¿Aún no tienes cuenta? <a href="/login.html?redirect=/pasaporte_rural/mi-pasaporte.php" style="color: #81c784; font-weight: 700; text-decoration: underline;">Regístrate aquí</a>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="pasaporte-footer">
            <p class="mb-1"><strong>Pasaporte Rural by rutasrurales.io</strong></p>
            <p>Sella experiencias · Conecta con lo auténtico · Explora la España rural</p>
        </div>
        
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>