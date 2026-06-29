<?php
/**
 * =============================================================================
 * PASAPORTE RURAL — Página explicativa: "¿Cómo funciona?"
 * =============================================================================
 * Archivo  : pasaporte_rural/como-funciona.php
 * Acceso   : Público (turistas y público general)
 * Función  : Explica de forma clara y sencilla cómo funciona el Pasaporte Rural
 * =============================================================================
 */

declare(strict_types=1);

// Cargar configuración del módulo (sin iniciar sesión necesaria)
define('API_NO_HEADERS', true);
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Descubre cómo funciona el Pasaporte Rural de rutasrurales.io. Sella experiencias en alojamientos rurales y consiga descuentos exclusivos.">
    <title>¿Cómo funciona el Pasaporte Rural? — rutasrurales.io</title>

    <!-- Bootstrap 5 CDN -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Estilos propios del módulo -->
    <link rel="stylesheet" href="css/pasaporte.css">

    <style>
        /* Estilos específicos para esta página */
        .como-funciona-body {
            background: linear-gradient(135deg, #f8fdf6 0%, #e8f5e0 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .como-funciona-header {
            text-align: center;
            padding: 2rem 0;
            margin-bottom: 1rem;
        }
        
        .como-funciona-header h1 {
            color: var(--p-primary);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .como-funciona-header .subtitle {
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .paso-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border-left: 4px solid var(--p-primary);
        }
        
        .paso-numero {
            width: 40px;
            height: 40px;
            background: var(--p-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .paso-titulo {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--p-primary);
            margin-bottom: 0.5rem;
        }
        
        .paso-texto {
            color: #495057;
            line-height: 1.6;
        }
        
        .beneficios-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        
        .beneficio-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .beneficio-icono {
            width: 48px;
            height: 48px;
            background: #e8f5e0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .beneficio-titulo {
            font-weight: 600;
            color: #1a2e1a;
            margin-bottom: 0.25rem;
        }
        
        .beneficio-texto {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .niveles-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        
        .nivel-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f8fdf6;
            border-radius: 50px;
            font-weight: 600;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .faq-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        
        .faq-item {
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 0;
        }
        
        .faq-item:last-child {
            border-bottom: none;
        }
        
        .faq-pregunta {
            font-weight: 600;
            color: var(--p-primary);
            margin-bottom: 0.5rem;
        }
        
        .faq-respuesta {
            color: #495057;
            line-height: 1.6;
        }
        
        .cta-section {
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: var(--p-primary);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .cta-btn:hover {
            background: #2d4a2d;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .volver-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--p-primary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }
        
        .volver-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="como-funciona-body">

    <div class="container" style="max-width: 680px;">
        
        <!-- Volver -->
        <a href="/pasaporte_rural/mi-pasaporte.php" class="volver-link">
            <i class="fa-solid fa-arrow-left"></i> Volver a mi pasaporte
        </a>
        
        <!-- Cabecera -->
        <div class="como-funciona-header">
            <span style="font-size: 3rem;">🌿</span>
            <h1>¿Cómo funciona el Pasaporte Rural?</h1>
            <p class="subtitle">Sella experiencias en alojamientos rurales y consigues descuentos exclusivos</p>
        </div>
        
        <!-- Introducción -->
        <div class="paso-card" style="border-left-color: #4CAF50;">
            <p style="font-size: 1.1rem; line-height: 1.7; margin: 0;">
                El <strong>Pasaporte Rural</strong> es tu tarjeta digital gratuita que te permite 
                <strong>acumular sellos y puntos</strong> cada vez que te alojas en un 
                <strong>alojamiento Premium</strong> de nuestra red. ¡Es很简单: cuanto más viajas, más descuento obtienes!
            </p>
        </div>
        
        <!-- Pasos -->
        <h2 style="font-size: 1.3rem; color: var(--p-primary); margin: 2rem 0 1rem;">
            <i class="fa-solid fa-list-ol me-2"></i>¿Cómo funciona?
        </h2>
        
        <div class="paso-card">
            <div class="paso-numero">1</div>
            <div class="paso-titulo">Obtén tu Pasaporte Rural</div>
            <div class="paso-texto">
                Regístrate gratis en rutasrurales.io y automáticamente se creará tu Pasaporte Rural 
                con un <strong>5% de descuento</strong> incluido. ¡Sin coste ni compromiso!
            </div>
        </div>
        
        <div class="paso-card">
            <div class="paso-numero">2</div>
            <div class="paso-titulo">Visita un alojamiento Premium</div>
            <div class="paso-texto">
                Cuando reserves en un alojamiento marcado como 
                <strong><i class="fa-solid fa-star" style="color: #FF9800;"></i> Premium</strong>, 
                muestra tu código QR al llegar. El propietario lo escaneará para verificar tu pasaporte.
            </div>
        </div>
        
        <div class="paso-card">
            <div class="paso-numero">3</div>
            <div class="paso-titulo">Recibe tu Sello Rural</div>
            <div class="paso-texto">
                Al finalizar tu estancia, el propietario sellará tu pasaporte. 
                Obtendrás puntos según tu <strong>limpieza</strong> y <strong>civismo</strong> (1-5 estrellas cada uno).
            </div>
        </div>
        
        <div class="paso-card">
            <div class="paso-numero">4</div>
            <div class="paso-titulo">Sube tu nivel y descuento</div>
            <div class="paso-texto">
                Con <strong>50 puntos</strong> subes un 1% de descuento. 
                El máximo es <strong>10%</strong>. ¡También vas subiendo de nivel: 
                Viajero → Explorador → Embajador!
            </div>
        </div>
        
        <!-- Beneficios -->
        <div class="beneficios-section">
            <h2 style="font-size: 1.3rem; color: var(--p-primary); margin-bottom: 1.5rem;">
                <i class="fa-solid fa-gift me-2"></i>¿Qué ganas?
            </h2>
            
            <div class="beneficio-item">
                <div class="beneficio-icono">🏨</div>
                <div>
                    <div class="beneficio-titulo">Descuentos en alojamientos</div>
                    <div class="beneficio-texto">Desde el 5% hasta el 10% de descuento en tu próxima reserva</div>
                </div>
            </div>
            
            <div class="beneficio-item">
                <div class="beneficio-icono">🏆</div>
                <div>
                    <div class="beneficio-titulo">Sube de nivel</div>
                    <div class="beneficio-texto">Viajero (🌱) → Explorador (🗺️) → Embajador (🏅)</div>
                </div>
            </div>
            
            <div class="beneficio-item">
                <div class="beneficio-icono">⭐</div>
                <div>
                    <div class="beneficio-titulo">Bonus por excelencia</div>
                    <div class="beneficio-texto">+2 puntos extra si calificas con 4+ estrellas en limpieza y civismo</div>
                </div>
            </div>
            
            <div class="beneficio-item">
                <div class="beneficio-icono">🔒</div>
                <div>
                    <div class="beneficio-titulo">Código QR seguro</div>
                    <div class="beneficio-texto">Tu código cambia cada 45 segundos para mayor seguridad</div>
                </div>
            </div>
        </div>
        
        <!-- Niveles -->
        <div class="niveles-section">
            <h2 style="font-size: 1.3rem; color: var(--p-primary); margin-bottom: 1rem;">
                <i class="fa-solid fa-layer-group me-2"></i>Niveles del Pasaporte
            </h2>
            <p style="color: #6c757d; margin-bottom: 1.5rem;">
                Cada nivel tiene requisitos de puntos diferentes:
            </p>
            
            <div class="nivel-badge">
                <span>🌱</span> <strong>Viajero</strong> <span style="color: #6c757d;">(0-100 pts)</span>
            </div>
            <div class="nivel-badge">
                <span>🗺️</span> <strong>Explorador</strong> <span style="color: #6c757d;">(101-300 pts)</span>
            </div>
            <div class="nivel-badge">
                <span>🏅</span> <strong>Embajador</strong> <span style="color: #6c757d;">(301+ pts)</span>
            </div>
        </div>
        
        <!-- FAQ -->
        <div class="faq-section">
            <h2 style="font-size: 1.3rem; color: var(--p-primary); margin-bottom: 1.5rem;">
                <i class="fa-solid fa-circle-question me-2"></i>Preguntas frecuentes
            </h2>
            
            <div class="faq-item">
                <div class="faq-pregunta">¿Cuánto cuesta el Pasaporte Rural?</div>
                <div class="faq-respuesta">¡Es completamente <strong>gratis</strong>! No tiene coste alguno.</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-pregunta">¿Cómo consigo mi código QR?</div>
                <div class="faq-respuesta">Accede a <strong>/pasaporte_rural/mi-pasaporte.php</strong> desde tu cuenta de rutasrurales.io y automáticamente se generará tu código QR.</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-pregunta">¿Qué alojamientos dan sellos?</div>
                <div class="faq-respuesta">Solo los alojamientos marcados con el icono de 
                <strong><i class="fa-solid fa-star" style="color: #FF9800;"></i> Premium</strong> 
                dan sellos. ¡Busca el icono dorado!</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-pregunta">¿Cuántos puntos da cada sello?</div>
                <div class="faq-respuesta">Los puntos dependen de tu puntuación: 
                (limpieza 1-5) + (civismo 1-5) = puntos base. 
                ¡Si calificas con 4+ en ambos, ganas +2 puntos extra!</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-pregunta">¿Puedo usar el descuento en cualquier alojamiento?</div>
                <div class="faq-respuesta">Sí, el descuento se aplica en cualquier 
                <strong>alojamiento Premium</strong> de la red. El propietario 
                verá tu descuento automáticamente al hacer el check-in.</div>
            </div>
            
            <div class="faq-item">
                <div class="faq-preunta">¿El código QR caduca?</div>
                <div class="faq-respuesta">Cada código QR es válido solo 
                <strong>60 segundos</strong>. El código se renueva automáticamente 
                cada 45 segundos para máxima seguridad.</div>
            </div>
        </div>
        
        <!-- CTA -->
        <div class="cta-section">
            <a href="/pasaporte_rural/mi-pasaporte.php" class="cta-btn">
                <i class="fa-solid fa-qrcode"></i> Ver mi Pasaporte Rural
            </a>
            <p style="margin-top: 1rem; color: #6c757d; font-size: 0.9rem;">
                Si aún no tienes cuenta, <a href="/login.html?redirect=/pasaporte_rural/mi-pasaporte.php" style="color: var(--p-primary);">regístrate gratis aquí</a>
            </p>
        </div>
        
        <!-- Pie -->
        <div class="pasaporte-footer">
            <p><strong>Pasaporte Rural by rutasrurales.io</strong></p>
            <p>Sella experiencias · Consigue ventajas · Descubre la España rural</p>
        </div>
        
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>