<?php
/**
 * =============================================================================
 * PASAPORTE RURAL — Página para Propietarios: "¿Cómo gestionar descuentos?"
 * =============================================================================
 * Archivo  : pasaporte_rural/como-gestionar-descuentos.php
 * Acceso   : Propietarios de alojamientos Premium
 * Función  : Explica cómo gestionar los descuentos del Pasaporte Rural
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
    <meta name="description" content="Aprende a gestionar los descuentos del Pasaporte Rural para tu alojamiento. Sella experiencias y atrae más turistas.">
    <title>Gestionar Descuentos — Pasaporte Rural</title>

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
        .como-gestionar-body {
            background: linear-gradient(135deg, #f8fdf6 0%, #e8f5e0 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .gestionar-header {
            text-align: center;
            padding: 2rem 0;
            margin-bottom: 1rem;
        }
        
        .gestionar-header h1 {
            color: var(--p-primary);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .gestionar-header .subtitle {
            color: #6c757d;
            font-size: 1rem;
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
            width: 36px;
            height: 36px;
            background: var(--p-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
        }
        
        .paso-titulo {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--p-primary);
            margin-bottom: 0.5rem;
        }
        
        .paso-texto {
            color: #495057;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .info-box {
            background: #e8f5e0;
            border-radius: 12px;
            padding: 1.25rem;
            margin: 1.5rem 0;
            border-left: 4px solid var(--p-primary);
        }
        
        .info-box h4 {
            color: var(--p-primary);
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }
        
        .info-box p {
            color: #495057;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .descuentos-tabla {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        
        .descuentos-tabla h4 {
            color: var(--p-primary);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .tabla-descuento {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tabla-descuento th,
        .tabla-descuento td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .tabla-descuento th {
            color: var(--p-primary);
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .tabla-descuento td {
            font-size: 0.9rem;
        }
        
        .tabla-descuento tr:last-child td {
            border-bottom: none;
        }
        
        .nivel-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            background: #f8fdf6;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .faq-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin: 1.5rem 0;
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
            font-size: 0.95rem;
        }
        
        .faq-respuesta {
            color: #495057;
            line-height: 1.5;
            font-size: 0.9rem;
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
        
        .requisitos-box {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        
        .requisitos-box h4 {
            color: var(--p-primary);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .requisito-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .requisito-item:last-child {
            margin-bottom: 0;
        }
        
        .requisito-icono {
            color: var(--p-primary);
            flex-shrink: 0;
        }
        
        .requisito-texto {
            color: #495057;
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="como-gestionar-body">

    <div class="container" style="max-width: 620px;">
        
        <!-- Volver -->
        <a href="/user-dashboard.html" class="volver-link">
            <i class="fa-solid fa-arrow-left"></i> Volver a mi panel
        </a>
        
        <!-- Cabecera -->
        <div class="gestionar-header">
            <span style="font-size: 2.5rem;">🏨</span>
            <h1>Gestionar Descuentos</h1>
            <p class="subtitle">Aprende a usar el Pasaporte Rural con tus huéspedes</p>
        </div>
        
        <!-- Introducción -->
        <div class="info-box">
            <h4><i class="fa-solid fa-circle-info me-2"></i>¿Qué es el Pasaporte Rural?</h4>
            <p>Es un sistema digital gratuito que permite a los turistas acumular sellos y puntos al alojarse en establecimientos Premium. A cambio, reciben descuentos que tú puedes aplicar en sus siguientes estancias.</p>
        </div>
        
        <!-- Cómo sellar -->
        <h2 style="font-size: 1.2rem; color: var(--p-primary); margin: 1.5rem 0 1rem;">
            <i class="fa-solid fa-qrcode me-2"></i>¿Cómo sellar a un huésped?
        </h2>
        
        <div class="paso-card">
            <div class="paso-numero">1</div>
            <div class="paso-titulo">Pide el código QR</div>
            <div class="paso-texto">
                Cuando llegue un huésped con Pasaporte Rural, pídele que muestre su código QR desde la app o su móvil.
            </div>
        </div>
        
        <div class="paso-card">
            <div class="paso-numero">2</div>
            <div class="paso-titulo">Escanea el QR</div>
            <div class="paso-texto">
                Usa la app de validación (o el sistema de check-in) para escanear el código QR del huésped.
            </div>
        </div>
        
        <div class="paso-card">
            <div class="paso-numero">3</div>
            <div class="paso-titulo">Califica al huésped</div>
            <div class="paso-texto">
                Al hacer el check-out, califica al huésped en <strong>limpieza</strong> y <strong>civismo</strong> (1-5 estrellas). El sistema automáticamente sellará su pasaporte.
            </div>
        </div>
        
        <!-- Tabla de descuentos -->
        <div class="descuentos-tabla">
            <h4><i class="fa-solid fa-percent me-2"></i>Descuentos disponibles</h4>
            <table class="tabla-descuento">
                <thead>
                    <tr>
                        <th>Puntos del huésped</th>
                        <th>Descuento</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>0-49 puntos</td>
                        <td><span class="nivel-badge">5% dto.</span></td>
                    </tr>
                    <tr>
                        <td>50-99 puntos</td>
                        <td><span class="nivel-badge">6% dto.</span></td>
                    </tr>
                    <tr>
                        <td>100-149 puntos</td>
                        <td><span class="nivel-badge">7% dto.</span></td>
                    </tr>
                    <tr>
                        <td>150-199 puntos</td>
                        <td><span class="nivel-badge">8% dto.</span></td>
                    </tr>
                    <tr>
                        <td>200+ puntos</td>
                        <td><span class="nivel-badge">9% dto.</span></td>
                    </tr>
                    <tr>
                        <td>300+ puntos</td>
                        <td><span class="nivel-badge">10% dto.</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Requisitos -->
        <div class="requisitos-box">
            <h4><i class="fa-solid fa-star me-2"></i>¿Quién puede dar sellos?</h4>
            
            <div class="requisito-item">
                <i class="fa-solid fa-check-circle requisito-icono"></i>
                <span class="requisito-texto">Ser alojamiento <strong>Premium</strong> de rutasrurales.io</span>
            </div>
            <div class="requisito-item">
                <i class="fa-solid fa-check-circle requisito-icono"></i>
                <span class="requisito-texto">Tener el sistema de check-in activo</span>
            </div>
            <div class="requisito-item">
                <i class="fa-solid fa-check-circle requisito-icono"></i>
                <span class="requisito-texto">El huésped debe tener Pasaporte Rural activo</span>
            </div>
        </div>
        
        <!-- FAQ -->
        <div class="faq-section">
            <h4 style="font-size: 1.1rem; color: var(--p-primary); margin-bottom: 1rem;">
                <i class="fa-solid fa-circle-question me-2"></i>Preguntas frecuentes
            </h4>
            
            <div class="faq-item">
                <div class="faq-pregunta">¿Cuántos puntos da cada sello?</div>
                <div class="faq-respuesta">
                    (Limpieza 1-5) + (Civismo 1-5) = puntos base. 
                    Si el huésped tiene 4+ estrellas en ambos, ¡gana +2 puntos extra!
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-pregunta">¿El descuento me beneficia?</div>
                <div class="faq-respuesta">
                    El descuento lo aplica el sistema automáticamente. Atrae más clientes fieels y recurrentes a tu alojamiento.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-pregunta">¿Puedo dar sellos sin ser Premium?</div>
                <div class="faq-respuesta">
                    No, solo los alojamientos Premium pueden dar sellos. 
                    <a href="/user-dashboard.html#membresia" style="color: var(--p-primary);">Consulta cómo ser Premium</a>
                </div>
            </div>
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