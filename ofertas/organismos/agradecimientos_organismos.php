
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Alta Institucional - Rutas Rurales</title>
    <style>
        :root {
            --primary: #1b3d22; /* Verde pino institucional con más contraste para legibilidad */
            --primary-light: #f1f6f2;
            --accent: #b45309; /* Ámbar oscuro/tierra con alto contraste, cumple accesibilidad */
            --slate-dark: #0f172a; /* Casi negro para máxima legibilidad del texto */
            --slate-light: #475569;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --border: #cbd5e1; /* Bordes ligeramente más oscuros para dar estructura */
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: var(--bg-light);
            color: var(--slate-dark);
            line-height: 1.5;
            padding: 40px 20px;
        }

        .form-container {
            max-width: 650px;
            margin: 0 auto;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* Cabecera del Formulario */
        .header-section {
            text-align: center;
            margin-bottom: 35px;
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 25px;
        }

        .logo {
            font-size: 22px;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .logo span {
            color: var(--accent);
        }

        h1 {
            font-size: 24px;
            color: var(--slate-dark);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .subtitle {
            font-size: 14.5px;
            color: var(--slate-light);
        }

        /* Estructura del Formulario */
        .form-group {
            margin-bottom: 22px;
        }

        .form-row {
            display: block;
            margin-bottom: 0;
        }

        /* Layout clásico de dos columnas usando inline-block con ancho controlado */
        .col-6 {
            display: inline-block;
            vertical-align: top;
            width: 48%;
            margin-right: 3%;
        }
        
        .col-6:last-child {
            margin-right: 0;
        }

        label {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--slate-dark);
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            padding: 11px 14px;
            font-size: 14px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background-color: var(--white);
            color: var(--slate-dark);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(27, 61, 34, 0.15);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .help-text {
            display: block;
            font-size: 12px;
            color: var(--slate-light);
            margin-top: 4px;
        }

        /* Botón de Envíos */
        .submit-btn {
            display: block;
            width: 100%;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 14px 20px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 30px;
        }

        .submit-btn:hover {
            background-color: #112615;
        }

        /* Sección de Cierre Directo (Solo Teléfono / Soporte) */
        .footer-support {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px dashed var(--border);
            text-align: center;
        }

        .footer-support p {
            font-size: 13.5px;
            color: var(--slate-light);
            margin-bottom: 8px;
        }

        .phone-link {
            font-size: 18px;
            font-weight: 700;
            color: var(--accent);
            text-decoration: none;
            display: inline-block;
        }

        .phone-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 580px) {
            .col-6 {
                width: 100%;
                margin-right: 0;
                margin-bottom: 22px;
            }
            .form-container {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>

    <div class="form-container">
        
        <div class="header-section">
            <div class="logo">Rutas<span>Rurales</span></div>
            <h1>Datos de Alta Institucional</h1>
            <p class="subtitle">Completa los detalles de tu organismo para configurar el espacio y procesar la orden de visibilidad.</p>
        </div>

        <!-- El action apuntará a tu backend o webhook una vez procesado el pago o pre-registro -->
        <form action="procesar_organismo.php" method="POST">
            
            <div class="form-group">
                <label for="nombre_organismo">Nombre Oficial del Organismo / Ayuntamiento *</label>
                <input type="text" id="nombre_organismo" name="nombre_organismo" required placeholder="Ej. Excmo. Ayuntamiento de Villar del Campo">
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="persona_contacto">Persona de Contacto *</label>
                    <input type="text" id="persona_contacto" name="persona_contacto" required placeholder="Nombre y Apellidos">
                </div>
                <div class="form-group col-6">
                    <label for="cargo_contacto">Cargo / Puesto *</label>
                    <input type="text" id="cargo_contacto" name="cargo_contacto" required placeholder="Ej. Concejal de Turismo, Agente de Desarrollo">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-6">
                    <label for="email_contacto">Correo Electrónico Oficial *</label>
                    <input type="email" id="email_contacto" name="email_contacto" required placeholder="ejemplo@ayto.es">
                </div>
                <div class="form-group col-6">
                    <label for="telefono_contacto">Teléfono de Contacto Directo *</label>
                    <input type="tel" id="telefono_contacto" name="telefono_contacto" required placeholder="Ej. 975 00 00 00">
                </div>
            </div>

            <div class="form-group">
                <label for="web_oficial">Sitio Web Oficial (URL para enlace DoFollow) *</label>
                <input type="text" id="web_oficial" name="web_oficial" required placeholder="https://www.tuayuntamiento.es">
                <span class="help-text">Utilizada para configurar el enlace directo de alta autoridad SEO.</span>
            </div>

            <div class="form-group">
                <label for="observaciones">Notas sobre Facturación o Contenido Especial</label>
                <textarea id="observaciones" name="observaciones" placeholder="Indica aquí si requieres tramitación mediante Face/REGE, si dispones de un archivo GPX de rutas, o cualquier detalle adicional..."></textarea>
            </div>

            <button type="submit" class="submit-btn">Guardar Datos y Activar Espacio</button>

        </form>

        <!-- Sección de cierre limpio: Cuidado meticuloso, solo el teléfono visible de soporte -->
        <div class="footer-support">
            <p>¿Prefieres tramitar el alta directamente por teléfono con un asesor?</p>
            <a href="tel:+34605249696" class="phone-link">📞 Soporte Institucional:+34 605 24 96 96</a>
        </div>

    </div>

</body>
</html>