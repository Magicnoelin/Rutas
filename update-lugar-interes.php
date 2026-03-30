<?php
// Script para actualizar lugar-interes.html en el servidor
// Sube este archivo al servidor y ejecútalo desde el navegador

$contenido = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Lugar de Interés | Rutas Rurales</title>
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">
    <link rel="canonical" id="canonical-url" href="">
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .detail-container { background-color: white; padding: 2.5rem; border-radius: 16px; box-shadow: 0 4px 30px rgba(44, 95, 45, 0.1); margin-bottom: 2rem; }
        .detail-header { margin-bottom: 2rem; border-bottom: 3px solid var(--primary-color); padding-bottom: 1rem; }
        .detail-header h1 { color: var(--primary-color); font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .detail-header p { color: #666; font-size: 1.1rem; }
        .detail-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 3rem; }
        @media (max-width: 900px) { .detail-grid { grid-template-columns: 1fr; } }
        .gallery-container { position: relative; height: 450px; border-radius: 12px; overflow: hidden; margin-bottom: 1.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .gallery-img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .gallery-img.active { display: block; }
        .gallery-nav { position: absolute; top: 50%; width: 100%; display: flex; justify-content: space-between; transform: translateY(-50%); padding: 0 1rem; }
        .nav-btn { background: rgba(255,255,255,0.9); color: var(--primary-color); border: none; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.2); transition: all 0.3s ease; }
        .nav-btn:hover { background: var(--primary-color); color: white; transform: scale(1.1); }
        .info-card { background: linear-gradient(135deg, #fafffc 0%, #f0f9f0 100%); padding: 2rem; border-radius: 16px; border: 2px solid rgba(44, 95, 45, 0.1); }
        .info-card h3 { color: var(--primary-color); font-size: 1.4rem; font-weight: 700; margin-bottom: 1.5rem; padding-bottom: 0.8rem; border-bottom: 2px solid var(--primary-color); }
        .info-item { display: flex; align-items: center; margin-bottom: 1.2rem; color: #555; font-size: 1rem; padding: 0.8rem; background: white; border-radius: 10px; }
        .info-item i { width: 40px; height: 40px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; }
        .info-item a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Logo" style="height: 50px; margin-right: 10px;">
                </div>
                <ul class="nav-menu">
                    <li><a href="/index.html">Inicio</a></li>
                    <li><a href="/alojamientos-turisticos.html">Alojamientos</a></li>
                    <li><a href="/lugares-interes-paginacion.html">Lugares</a></li>
                    <li><a href="/rutas-turisticas.html">Rutas</a></li>
                </ul>
            </div>
        </nav>
    </header>
    <section class="section" style="margin-top: 100px;">
        <div class="container">
            <div id="loading" style="text-align: center; padding: 3rem;">
                <i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Cargando...
            </div>
            <div id="error-msg" style="display: none; text-align: center; padding: 3rem;">
                <i class="fas fa-exclamation-triangle fa-3x" style="color: #e74c3c;"></i>
                <h2 style="margin-top: 1rem;">No encontrado</h2>
                <a href="/lugares-interes-paginacion.html" class="btn-primary" style="margin-top: 1rem;">Volver</a>
            </div>
            <div id="detail-content" class="detail-container" style="display: none;"></div>
        </div>
    </section>
    <footer class="footer">
        <div class="container">
            <div class="footer-content-simple">
                <div class="footer-info">
                    <span><i class="fas fa-envelope"></i> olgamarin@rutasrurales.io</span>
                    <span><i class="fas fa-phone"></i> +34 605 249 696</span>
                </div>
                <div class="footer-copyright"><p>&copy; 2025 rutasrurales.io</p></div>
            </div>
        </div>
    </footer>
    <script>
        let currentImageIndex = 0, currentPhotos = [];
        window.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const slug = urlParams.get("slug");
            if (!slug) { mostrarError(); return; }
            document.getElementById("canonical-url").setAttribute("href", "https://www.rutasrurales.io/lugar-interes.html?slug=" + slug);
            document.querySelector("link[rel=\"canonical\"]").setAttribute("href", "https://www.rutasrurales.io/lugar-interes.html?slug=" + slug);
            cargarLugar(slug);
        });

        async function cargarLugar(slug) {
            try {
                const response = await fetch("/api/lugar-interes.php?slug=" + slug);
                const data = await response.json();
                if (data.success && data.data) {
                    renderizarDetalle(data.data);
                } else {
                    mostrarError();
                }
            } catch (error) {
                console.error("Error:", error);
                mostrarError();
            }
        }

        function mostrarError() {
            document.getElementById("loading").style.display = "none";
            document.getElementById("error-msg").style.display = "block";
        }

        function renderizarDetalle(lugar) {
            document.getElementById("loading").style.display = "none";
            const c = document.getElementById("detail-content");
            c.style.display = "block";
            const nom = lugar.nombre || lugar.name || "Lugar";
            document.title = nom + " | Rutas Rurales";
            
            currentPhotos = lugar.fotos || [];
            if (!currentPhotos.length) {
                ["photo1","photo2","photo3","photo4"].forEach(p => {
                    if (lugar[p]) currentPhotos.push(lugar[p]);
                });
            }
            if (!currentPhotos.length) currentPhotos = ["https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800"];
            
            const gh = `<div class="gallery-container">
                ${currentPhotos.map((f,i) => `<img src="${f}" class="gallery-img ${i===0?"active":""}" alt="${nom}" onerror="this.src=\\"https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800\\"">`).join("")}
                ${currentPhotos.length>1?`<div class="gallery-nav"><button class="nav-btn" onclick="cambiarFoto(-1)"><i class="fas fa-chevron-left"></i></button><button class="nav-btn" onclick="cambiarFoto(1)"><i class="fas fa-chevron-right"></i></button></div>`:""}
            </div>`;
            
            const des = lugar.descripcion || lugar.description || "Sin descripción";
            const tel = lugar.telefono || lugar.phone || "";
            const em = lugar.email || "";
            const web = lugar.web || lugar.website || "";
            let bh = "";
            if (tel) bh += `<div class="info-item"><i class="fas fa-phone-alt"></i> <a href="tel:${tel}">${tel}</a></div>`;
            if (em) bh += `<div class="info-item"><i class="fas fa-envelope"></i> <a href="mailto:${em}">${em}</a></div>`;
            if (web) bh += `<div class="info-item"><i class="fas fa-globe"></i> <a href="${web}" target="_blank">Web</a></div>`;
            
            c.innerHTML = `<div class="detail-header"><h1>${nom}</h1><p><i class="fas fa-map-marker-alt"></i> ${lugar.direccion||""} ${lugar.localidad||""} ${lugar.provincia||""}</p></div>
                ${gh}<div class="detail-grid"><div><h3>Descripción</h3><p>${des}</p></div><div><div class="info-card"><h3>Información</h3>${bh}</div></div></div>`;
        }

        window.cambiarFoto = function(d) {
            if (currentPhotos.length <= 1) return;
            const imgs = document.querySelectorAll(".gallery-img");
            imgs[currentImageIndex].classList.remove("active");
            currentImageIndex += d;
            if (currentImageIndex >= imgs.length) currentImageIndex = 0;
            if (currentImageIndex < 0) currentImageIndex = imgs.length - 1;
            imgs[currentImageIndex].classList.add("active");
        }
    </script>
</body>
</html>';

$archivo = __DIR__ . '/lugar-interes.html';
if (file_put_contents($archivo, $contenido)) {
    echo "✅ Archivo lugar-interes.html actualizado correctamente";
} else {
    echo "❌ Error al guardar el archivo. Verifica permisos.";
}
