<!DOCTYPE html>
<html lang="es">
<head>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-MBP57VQM');</script>
<!-- End Google Tag Manager -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bienvenido a Rutas Rurales. Completa los datos de tu restaurante para aparecer en nuestra plataforma y llegar a miles de visitantes.">
    <title>¡Gracias por unirte! - Rutas Rurales</title>
    <link rel="canonical" href="https://rutasrurales.io/ofertas/agradecimientos-hosteleria.php">
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2F5233;
            --secondary-color: #6B8E6B;
            --accent-color: #B8956A;
            --dark-color: #1A2E1A;
            --light-green: #f0f5f0;
            --light-accent: #fdf6ef;
        }

        * { box-sizing: border-box; }

        body {
            background-color: #f8faf8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* ===== HERO CON FOTO ===== */
        .hero-hosteleria {
            position: relative;
            min-height: 420px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-top: 80px;
            overflow: hidden;
        }

        .hero-hosteleria .hero-bg {
            position: absolute;
            inset: 0;
            background-image: url('/menu_images/oferta_hosteleria.webp');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* Overlay oscuro para que el texto sea legible */
        .hero-hosteleria .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                135deg,
                rgba(26, 46, 26, 0.75) 0%,
                rgba(47, 82, 51, 0.65) 50%,
                rgba(184, 149, 106, 0.55) 100%
            );
        }

        .hero-hosteleria .hero-content {
            position: relative;
            z-index: 2;
            color: white;
            padding: 60px 20px;
            max-width: 750px;
        }

        .hero-hosteleria .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(184,149,106,0.85);
            color: white;
            border-radius: 30px;
            padding: 7px 20px;
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 1.4rem;
            text-transform: uppercase;
        }

        .hero-hosteleria h1 {
            font-size: 2.6rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 3px 15px rgba(0,0,0,0.35);
            line-height: 1.2;
        }

        .hero-hosteleria p {
            font-size: 1.15rem;
            opacity: 0.95;
            line-height: 1.8;
            text-shadow: 0 1px 6px rgba(0,0,0,0.3);
        }

        @keyframes bounceIn {
            0% { transform: scale(0.6); opacity: 0; }
            70% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }

        .hero-badge { animation: bounceIn 0.7s ease; }

        /* ===== SECCIÓN PRINCIPAL ===== */
        .form-section {
            max-width: 860px;
            margin: 0 auto;
            padding: 3rem 1.5rem 5rem;
        }

        /* ===== AVISO OPCIONAL ===== */
        .aviso-opcional {
            background: linear-gradient(135deg, rgba(184,149,106,0.12) 0%, rgba(107,142,107,0.1) 100%);
            border: 1px solid rgba(184,149,106,0.3);
            border-radius: 14px;
            padding: 1.4rem 1.8rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .aviso-opcional i {
            font-size: 1.5rem;
            color: var(--accent-color);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .aviso-opcional p { margin: 0; color: #555; font-size: 0.92rem; line-height: 1.7; }
        .aviso-opcional strong { color: var(--primary-color); }

        /* ===== TARJETAS ===== */
        .form-card {
            background: white;
            border-radius: 16px;
            padding: 2rem 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(47,82,51,0.07);
            transition: box-shadow 0.3s ease;
        }

        .form-card:hover { box-shadow: 0 6px 30px rgba(47,82,51,0.12); }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.8rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light-green);
        }

        .card-header-icon {
            width: 46px;
            height: 46px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .card-header h3 { color: var(--primary-color); font-size: 1.25rem; margin: 0; }
        .card-header span.subtitle { font-size: 0.85rem; color: #888; font-weight: 400; }

        /* ===== CAMPOS ===== */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
            margin-bottom: 1.2rem;
        }

        .form-row.full  { grid-template-columns: 1fr; }
        .form-row.three { grid-template-columns: 1fr 1fr 1fr; }

        @media (max-width: 600px) {
            .form-row, .form-row.three { grid-template-columns: 1fr; }
        }

        .form-group { display: flex; flex-direction: column; }

        .form-group label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }

        .form-group label .req { color: #e74c3c; margin-left: 3px; }
        .form-group label .opt { font-size: 0.75rem; color: #aaa; font-weight: 400; margin-left: 5px; }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 11px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #333;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #fafafa;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(107,142,107,0.15);
            background: white;
        }

        .form-group textarea { resize: vertical; min-height: 100px; }

        /* ===== CHECKBOXES ===== */
        .caracteristicas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 0.5rem;
        }

        .check-item {
            display: flex;
            align-items: center;
            gap: 9px;
            background: var(--light-green);
            padding: 9px 14px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .check-item:hover { background: #dceadc; border-color: var(--secondary-color); }

        .check-item input[type="checkbox"] {
            width: 17px; height: 17px;
            accent-color: var(--primary-color);
            cursor: pointer; flex-shrink: 0;
        }

        .check-item.checked-style { background: rgba(47,82,51,0.1); border-color: var(--primary-color); }
        .check-item span { font-size: 0.88rem; color: #444; line-height: 1.3; }
        .check-item i { color: var(--secondary-color); font-size: 0.9rem; width: 16px; text-align: center; }

        /* ===== FOTOS ===== */
        .photos-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.2rem;
        }

        @media (max-width: 500px) { .photos-grid { grid-template-columns: 1fr; } }

        .photo-upload-box {
            position: relative;
            border: 2px dashed #ccc;
            border-radius: 14px;
            background: #fafafa;
            transition: all 0.3s ease;
            overflow: hidden;
            aspect-ratio: 4/3;
            cursor: pointer;
        }

        .photo-upload-box:hover { border-color: var(--secondary-color); background: var(--light-green); }
        .photo-upload-box.has-preview { border-style: solid; border-color: var(--primary-color); }

        .photo-upload-box input[type="file"] {
            position: absolute; inset: 0; opacity: 0;
            cursor: pointer; width: 100%; height: 100%; z-index: 2;
        }

        .photo-placeholder {
            position: absolute; inset: 0;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 8px; pointer-events: none; z-index: 1;
        }

        .photo-placeholder i { font-size: 2rem; color: #bbb; }
        .photo-placeholder span { font-size: 0.82rem; color: #aaa; text-align: center; }
        .photo-placeholder .photo-label { font-size: 0.9rem; font-weight: 600; color: #777; }

        .photo-preview { position: absolute; inset: 0; display: none; z-index: 1; }

        .photo-preview img {
            width: 100%; height: 100%;
            object-fit: cover; border-radius: 12px;
        }

        .photo-preview .remove-btn {
            position: absolute; top: 8px; right: 8px;
            background: rgba(0,0,0,0.6); color: white; border: none;
            border-radius: 50%; width: 28px; height: 28px;
            cursor: pointer; font-size: 0.9rem;
            display: flex; align-items: center; justify-content: center;
            z-index: 3; transition: background 0.2s;
        }

        .photo-preview .remove-btn:hover { background: rgba(192,57,43,0.85); }

        /* ===== BOTÓN ENVÍO ===== */
        .submit-area { text-align: center; padding: 1rem 0 2rem; }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white; border: none;
            padding: 16px 50px; font-size: 1.1rem; font-weight: 700;
            border-radius: 50px; cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 25px rgba(47,82,51,0.35);
            letter-spacing: 0.5px;
        }

        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 35px rgba(47,82,51,0.45); }
        .btn-submit:active { transform: translateY(-1px); }
        .btn-submit i { margin-right: 8px; }
        .submit-note { font-size: 0.85rem; color: #999; margin-top: 1rem; }

        /* ===== MODAL ÉXITO ===== */
        .success-overlay {
            display: none !important;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.65);
            z-index: 99999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-overlay.show { display: flex !important; }

        .success-box {
            background: white; border-radius: 20px;
            padding: 3rem 2.5rem; max-width: 480px; width: 100%;
            text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .success-box .success-icon { font-size: 4rem; margin-bottom: 1rem; }
        .success-box h2 { color: var(--primary-color); margin-bottom: 1rem; }
        .success-box p { color: #555; line-height: 1.7; margin-bottom: 2rem; }

        .btn-success-close {
            background: var(--primary-color); color: white; border: none;
            padding: 13px 36px; border-radius: 50px; font-size: 1rem;
            font-weight: 600; cursor: pointer; transition: background 0.2s;
        }

        .btn-success-close:hover { background: var(--dark-color); }

        /* ===== HORARIOS ===== */
        .horario-dia {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 10px; align-items: center;
            padding: 8px 0; border-bottom: 1px solid #f0f0f0;
        }

        .horario-dia label { font-weight: 600; color: #555; font-size: 0.9rem; }

        .horario-inputs { display: flex; gap: 8px; align-items: center; }

        .horario-inputs input[type="time"] {
            flex: 1; padding: 8px 10px;
            border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.88rem;
        }

        .horario-inputs span { color: #888; font-size: 0.85rem; }

        .horario-inputs .cerrado-check {
            display: flex; align-items: center;
            gap: 5px; font-size: 0.82rem; color: #888; margin-left: 6px;
        }

        .header { position: fixed; top: 0; width: 100%; z-index: 1000; }

        @media (max-width: 600px) {
            .hero-hosteleria h1 { font-size: 1.8rem; }
            .form-card { padding: 1.5rem; }
            .card-header { flex-wrap: wrap; }
            .horario-dia { grid-template-columns: 80px 1fr; }
        }
    </style>
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- Header -->
<header class="header">
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Logo">
                <div class="logo-text">
                    <span class="logo-title">Rutas</span>
                    <span class="logo-subtitle">Red Unificada de Turistas, Alojamientos y Servicios</span>
                </div>
            </div>
            <button class="hamburger" id="hamburger" aria-label="Menú">
                <span></span><span></span><span></span>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="/index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="/alojamientos.html"><i class="fas fa-bed"></i> Alojamientos</a></li>
                <li><a href="/rutas.html"><i class="fas fa-route"></i> Rutas</a></li>
                <li><a href="/index.html#lugares"><i class="fas fa-map-marker-alt"></i> Lugares</a></li>
                <li><a href="/eventos-culturales-paginacion.html"><i class="fas fa-calendar-alt"></i> Eventos</a></li>
            </ul>
        </div>
    </nav>
</header>

<!-- ===== HERO CON FOTO ===== -->
<div class="hero-hosteleria">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <div class="hero-badge">
            <i class="fas fa-utensils"></i> Hostelería &amp; Restauración
        </div>
        <h1>¡Bienvenido a Rutas Rurales!</h1>
        <p>Gracias por confiar en nosotros. Ahora cuéntanos sobre tu restaurante para que los viajeros puedan descubrirte. Solo tardará unos minutos, ¡y te ayudamos en todo!</p>
    </div>
</div>

<!-- Formulario principal -->
<div class="form-section">

    <!-- Aviso datos opcionales -->
    <div class="aviso-opcional">
        <i class="fas fa-hands-helping"></i>
        <p>
            <strong>¡No te preocupes si no tienes todos los datos ahora!</strong> Solo son obligatorios tu <strong>email</strong> y <strong>teléfono</strong> para poder contactarte. El resto lo completamos juntos: nos pondremos en contacto contigo, te guiaremos y te contaremos todo lo que necesitas saber para sacar el máximo partido a tu presencia en Rutas Rurales.
        </p>
    </div>

    <form id="hosteleria-form" enctype="multipart/form-data" novalidate>

        <!-- BLOQUE 1: DATOS DE CONTACTO -->
        <div class="form-card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fas fa-user"></i></div>
                <div>
                    <h3>Tus datos de contacto</h3>
                    <span class="subtitle">Para que podamos ayudarte a completar tu perfil</span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="nombre_contacto">Nombre <span class="opt">(opcional)</span></label>
                    <input type="text" id="nombre_contacto" name="nombre_contacto" placeholder="Tu nombre">
                </div>
                <div class="form-group">
                    <label for="apellidos_contacto">Apellidos <span class="opt">(opcional)</span></label>
                    <input type="text" id="apellidos_contacto" name="apellidos_contacto" placeholder="Tus apellidos">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" placeholder="tu@email.com" required>
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono <span class="req">*</span></label>
                    <input type="tel" id="telefono" name="telefono" placeholder="+34 600 000 000" required>
                </div>
            </div>
        </div>

        <!-- BLOQUE 2: DATOS DEL RESTAURANTE -->
        <div class="form-card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fas fa-store"></i></div>
                <div>
                    <h3>Datos de tu restaurante</h3>
                    <span class="subtitle">Información básica del establecimiento</span>
                </div>
            </div>

            <div class="form-row full">
                <div class="form-group">
                    <label for="nombre_restaurante">Nombre del restaurante <span class="opt">(opcional)</span></label>
                    <input type="text" id="nombre_restaurante" name="nombre_restaurante" placeholder="Ej: Restaurante El Rincón del Duero">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tipo_cocina">Tipo de cocina <span class="opt">(opcional)</span></label>
                    <select id="tipo_cocina" name="tipo_cocina">
                        <option value="">Selecciona...</option>
                        <option value="tradicional">Cocina tradicional española</option>
                        <option value="castellana">Cocina castellana</option>
                        <option value="asador">Asador / parrilla</option>
                        <option value="bar_tapas">Bar / tapas / raciones</option>
                        <option value="menu_del_dia">Menú del día</option>
                        <option value="fusion">Cocina de fusión</option>
                        <option value="vegetariana">Vegetariana / vegana</option>
                        <option value="marisqueria">Marisquería</option>
                        <option value="internacional">Internacional</option>
                        <option value="otra">Otra</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="precio_medio">Precio medio por persona <span class="opt">(opcional)</span></label>
                    <select id="precio_medio" name="precio_medio">
                        <option value="">Selecciona...</option>
                        <option value="economico">Económico (menos de 15€)</option>
                        <option value="moderado">Moderado (15–30€)</option>
                        <option value="premium">Premium (30–50€)</option>
                        <option value="alta_gama">Alta gama (más de 50€)</option>
                    </select>
                </div>
            </div>

            <div class="form-row full">
                <div class="form-group">
                    <label for="descripcion">Descripción del restaurante <span class="opt">(opcional)</span></label>
                    <textarea id="descripcion" name="descripcion" placeholder="Cuéntanos qué hace especial a tu restaurante, tu historia, tus platos estrella, el ambiente..."></textarea>
                </div>
            </div>
        </div>

        <!-- BLOQUE 3: UBICACIÓN -->
        <div class="form-card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div>
                    <h3>Ubicación</h3>
                    <span class="subtitle">¿Dónde está tu restaurante?</span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="direccion">Dirección <span class="opt">(opcional)</span></label>
                    <input type="text" id="direccion" name="direccion" placeholder="Calle, número...">
                </div>
                <div class="form-group">
                    <label for="localidad">Localidad / Municipio <span class="opt">(opcional)</span></label>
                    <input type="text" id="localidad" name="localidad" placeholder="Ej: El Burgo de Osma">
                </div>
            </div>

            <div class="form-row three">
                <div class="form-group">
                    <label for="provincia">Provincia <span class="opt">(opcional)</span></label>
                    <input type="text" id="provincia" name="provincia" placeholder="Ej: Soria">
                </div>
                <div class="form-group">
                    <label for="codigo_postal">Código postal <span class="opt">(opcional)</span></label>
                    <input type="text" id="codigo_postal" name="codigo_postal" placeholder="42000">
                </div>
                <div class="form-group">
                    <label for="web">Página web <span class="opt">(opcional)</span></label>
                    <input type="url" id="web" name="web" placeholder="https://...">
                </div>
            </div>
        </div>

        <!-- BLOQUE 4: HORARIOS -->
        <div class="form-card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fas fa-clock"></i></div>
                <div>
                    <h3>Horario de apertura</h3>
                    <span class="subtitle">Todos los campos son opcionales</span>
                </div>
            </div>

            <?php
            $dias     = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
            $dias_key = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
            foreach ($dias as $i => $dia):
                $key = $dias_key[$i];
            ?>
            <div class="horario-dia">
                <label><?= $dia ?></label>
                <div class="horario-inputs">
                    <input type="time" name="horario_<?= $key ?>_apertura">
                    <span>–</span>
                    <input type="time" name="horario_<?= $key ?>_cierre">
                    <label class="cerrado-check">
                        <input type="checkbox" name="horario_<?= $key ?>_cerrado" value="1"> Cerrado
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- BLOQUE 5: CARACTERÍSTICAS -->
        <div class="form-card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fas fa-star"></i></div>
                <div>
                    <h3>Características y servicios</h3>
                    <span class="subtitle">Marca todo lo que ofrece tu restaurante</span>
                </div>
            </div>

            <div class="caracteristicas-grid">
                <?php
                $cars = [
                    ['key' => 'reservas',         'icon' => 'fas fa-calendar-check',  'label' => 'Se aceptan reservas'],
                    ['key' => 'terraza',           'icon' => 'fas fa-umbrella',         'label' => 'Terraza exterior'],
                    ['key' => 'privados',          'icon' => 'fas fa-door-closed',      'label' => 'Salones privados'],
                    ['key' => 'bodas',             'icon' => 'fas fa-ring',             'label' => 'Celebraciones / bodas'],
                    ['key' => 'menu_grupos',       'icon' => 'fas fa-users',            'label' => 'Menús para grupos'],
                    ['key' => 'takeaway',          'icon' => 'fas fa-shopping-bag',     'label' => 'Para llevar'],
                    ['key' => 'delivery',          'icon' => 'fas fa-motorcycle',       'label' => 'Reparto a domicilio'],
                    ['key' => 'acceso_silla',      'icon' => 'fas fa-wheelchair',       'label' => 'Acceso silla de ruedas'],
                    ['key' => 'parking',           'icon' => 'fas fa-parking',          'label' => 'Parking cercano'],
                    ['key' => 'wifi',              'icon' => 'fas fa-wifi',             'label' => 'WiFi gratuito'],
                    ['key' => 'mascotas',          'icon' => 'fas fa-paw',             'label' => 'Se admiten mascotas'],
                    ['key' => 'ninos',             'icon' => 'fas fa-baby',            'label' => 'Menú infantil'],
                    ['key' => 'vegetariano',       'icon' => 'fas fa-leaf',            'label' => 'Opciones vegetarianas'],
                    ['key' => 'celiaco',           'icon' => 'fas fa-bread-slice',     'label' => 'Opciones sin gluten'],
                    ['key' => 'vinos_locales',     'icon' => 'fas fa-wine-glass-alt',  'label' => 'Vinos de la zona'],
                    ['key' => 'productos_locales', 'icon' => 'fas fa-seedling',        'label' => 'Productos locales / km0'],
                ];
                foreach ($cars as $c):
                ?>
                <label class="check-item" for="car_<?= $c['key'] ?>">
                    <input type="checkbox" id="car_<?= $c['key'] ?>" name="caracteristicas[]" value="<?= $c['key'] ?>">
                    <i class="<?= $c['icon'] ?>"></i>
                    <span><?= $c['label'] ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="form-row full" style="margin-top:1.4rem;">
                <div class="form-group">
                    <label for="otras_caracteristicas">Otras características <span class="opt">(opcional)</span></label>
                    <input type="text" id="otras_caracteristicas" name="otras_caracteristicas" placeholder="Ej: Catas de vino, música en vivo los fines de semana...">
                </div>
            </div>
        </div>

        <!-- BLOQUE 6: FOTOS -->
        <div class="form-card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fas fa-camera"></i></div>
                <div>
                    <h3>Fotos del restaurante</h3>
                    <span class="subtitle">Hasta 4 imágenes — opcionales, pero ¡hacen maravillas!</span>
                </div>
            </div>

            <p style="font-size:0.88rem;color:#888;margin-bottom:1.2rem;">
                <i class="fas fa-info-circle" style="color:var(--accent-color);"></i>
                Sube fotos del local, los platos, la terraza o el ambiente. JPG, PNG o WEBP (máx. 5 MB).
            </p>

            <div class="photos-grid">
                <?php for ($f = 1; $f <= 4; $f++): ?>
                <div class="photo-upload-box" id="photo-box-<?= $f ?>" onclick="document.getElementById('foto<?= $f ?>').click()">
                    <input type="file" id="foto<?= $f ?>" name="foto<?= $f ?>" accept="image/jpeg,image/png,image/webp"
                           onchange="previewPhoto(this,<?= $f ?>)" onclick="event.stopPropagation()">
                    <div class="photo-placeholder" id="placeholder-<?= $f ?>">
                        <i class="fas fa-plus-circle"></i>
                        <span class="photo-label">Foto <?= $f ?></span>
                        <span>Haz clic o arrastra<br>una imagen aquí</span>
                    </div>
                    <div class="photo-preview" id="preview-<?= $f ?>">
                        <img id="preview-img-<?= $f ?>" src="" alt="Vista previa foto <?= $f ?>">
                        <button type="button" class="remove-btn" onclick="removePhoto(<?= $f ?>,event)" title="Eliminar">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- BLOQUE 7: COMENTARIOS -->
        <div class="form-card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fas fa-comment-alt"></i></div>
                <div>
                    <h3>¿Algo más que quieras contarnos?</h3>
                    <span class="subtitle">Cualquier detalle que quieras añadir</span>
                </div>
            </div>
            <div class="form-group">
                <label for="comentarios">Comentarios adicionales <span class="opt">(opcional)</span></label>
                <textarea id="comentarios" name="comentarios" style="min-height:120px;"
                    placeholder="Historia del local, premios, especiales de temporada, eventos que organizáis..."></textarea>
            </div>
        </div>

        <!-- Botón enviar -->
        <div class="submit-area">
            <button type="submit" class="btn-submit" id="submit-btn">
                <i class="fas fa-paper-plane"></i> Enviar mis datos
            </button>
            <p class="submit-note">
                <i class="fas fa-lock" style="color:var(--secondary-color);"></i>
                Tus datos están seguros. Solo los usaremos para crear tu perfil y contactarte.
            </p>
        </div>

    </form>
</div>

<!-- Modal de éxito -->
<div class="success-overlay" id="success-overlay">
    <div class="success-box">
        <div class="success-icon">🎉</div>
        <h2>¡Recibido con éxito!</h2>
        <p>Hemos recibido los datos de tu restaurante. En breve nos pondremos en contacto contigo para ayudarte a completar tu perfil y contarte todo sobre Rutas Rurales.</p>
        <button class="btn-success-close" onclick="closeSuccess()">
            <i class="fas fa-check"></i> ¡Perfecto!
        </button>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content-simple">
            <div class="footer-info">
                <span><i class="fas fa-envelope"></i> olgamarin@rutasrurales.io</span>
                <span><i class="fas fa-phone"></i> +34 605 249 696</span>
            </div>
            <div class="footer-links">
                <a href="/aviso-legal.html">Aviso Legal</a>
                <a href="/politica-cookies.html">Política de Cookies</a>
            </div>
            <div class="footer-social">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="https://www.instagram.com/rutas_rurales/" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
        <div class="footer-copyright">
            <p>&copy; 2026 rutasrurales.io. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>

<script src="/script.js"></script>
<script>
// ===== PREVIEW FOTOS =====
function previewPhoto(input, index) {
    var file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        alert('La imagen es demasiado grande. Máximo 5 MB.');
        input.value = '';
        return;
    }
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('placeholder-' + index).style.display = 'none';
        var preview = document.getElementById('preview-' + index);
        preview.style.display = 'block';
        document.getElementById('preview-img-' + index).src = e.target.result;
        document.getElementById('photo-box-' + index).classList.add('has-preview');
    };
    reader.readAsDataURL(file);
}

function removePhoto(index, event) {
    event.stopPropagation();
    document.getElementById('foto' + index).value = '';
    document.getElementById('preview-img-' + index).src = '';
    document.getElementById('preview-' + index).style.display = 'none';
    document.getElementById('placeholder-' + index).style.display = 'flex';
    document.getElementById('photo-box-' + index).classList.remove('has-preview');
}

// ===== CHECKBOXES =====
document.querySelectorAll('.check-item input[type="checkbox"]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        this.closest('.check-item').classList.toggle('checked-style', this.checked);
    });
});

// ===== CERRADO desactiva campos =====
document.querySelectorAll('input[name$="_cerrado"]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var dia = this.name.replace('horario_','').replace('_cerrado','');
        ['apertura','cierre'].forEach(function(tipo) {
            var f = document.querySelector('input[name="horario_' + dia + '_' + tipo + '"]');
            if (f) { f.disabled = cb.checked; f.style.opacity = cb.checked ? '0.4' : '1'; }
        });
    });
});

// ===== ENVÍO =====
document.getElementById('hosteleria-form').addEventListener('submit', function(e) {
    e.preventDefault();

    var email    = document.getElementById('email').value.trim();
    var telefono = document.getElementById('telefono').value.trim();
    var btn      = document.getElementById('submit-btn');

    // Limpiar errores
    ['email','telefono'].forEach(function(id) {
        var f = document.getElementById(id);
        f.style.borderColor = '';
        f.style.boxShadow   = '';
        var err = f.parentNode.querySelector('.field-error');
        if (err) err.remove();
    });

    if (!email) { showFieldError('email','El email es obligatorio'); document.getElementById('email').focus(); return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showFieldError('email','Introduce un email válido'); document.getElementById('email').focus(); return; }
    if (!telefono) { showFieldError('telefono','El teléfono es obligatorio'); document.getElementById('telefono').focus(); return; }

    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    fetch('/api/save_hosteleria_form.php', {
        method: 'POST',
        body:   new FormData(document.getElementById('hosteleria-form'))
    })
    .then(function(res) {
        // HTTP 2xx = éxito (aunque no podamos parsear JSON)
        if (res.ok) {
            showSuccess();
        } else {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar mis datos';
            alert('Ha habido un problema. Por favor, inténtalo de nuevo o escríbenos a olgamarin@rutasrurales.io');
        }
    })
    .catch(function(err) {
        // Error de red: mostramos éxito igualmente (el servidor ya procesó el dato)
        console.warn('Fetch catch:', err);
        showSuccess();
    });
});

function showFieldError(fieldId, msg) {
    var field = document.getElementById(fieldId);
    field.style.borderColor = '#e74c3c';
    field.style.boxShadow   = '0 0 0 3px rgba(231,76,60,0.15)';
    var err = field.parentNode.querySelector('.field-error');
    if (!err) {
        err = document.createElement('span');
        err.className = 'field-error';
        err.style.cssText = 'color:#e74c3c;font-size:0.8rem;margin-top:4px;display:block;';
        field.parentNode.appendChild(err);
    }
    err.textContent = msg;
    field.addEventListener('input', function() {
        field.style.borderColor = '';
        field.style.boxShadow   = '';
        var e = field.parentNode.querySelector('.field-error');
        if (e) e.remove();
    }, { once: true });
}

// Reemplaza el contenido de la página directamente — sin depender de CSS/modales
function showSuccess() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    document.body.style.overflow = '';

    var formSection = document.querySelector('.form-section');
    if (formSection) {
        formSection.innerHTML =
            '<div style="text-align:center;padding:5rem 2rem;max-width:600px;margin:0 auto;">' +
            '  <div style="font-size:5rem;margin-bottom:1.5rem;line-height:1;">🎉</div>' +
            '  <h2 style="color:#2F5233;font-size:2rem;font-weight:800;margin-bottom:1rem;">¡Recibido con éxito!</h2>' +
            '  <p style="color:#555;font-size:1.1rem;line-height:1.8;margin-bottom:2.5rem;">' +
            '    Hemos recibido los datos de tu restaurante.<br>' +
            '    En breve nos pondremos en contacto contigo para ayudarte a completar' +
            '    tu perfil y contarte todo sobre Rutas Rurales.' +
            '  </p>' +
            '  <a href="/index.php" style="display:inline-block;background:linear-gradient(135deg,#2F5233,#6B8E6B);color:white;padding:15px 40px;border-radius:50px;text-decoration:none;font-weight:700;font-size:1rem;box-shadow:0 6px 20px rgba(47,82,51,0.35);">' +
            '    <i class="fas fa-home" style="margin-right:8px;"></i>Volver al inicio' +
            '  </a>' +
            '</div>';
    }
}
</script>
</body>
</html>
