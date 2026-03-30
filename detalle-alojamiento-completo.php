<?php
/**
 * COMPLETAR LA PÁGINA DE DETALLE DE ALOJAMIENTO
 * Este archivo contiene el resto del HTML que falta en detalle-alojamiento.php
 */

?>
                <img src="<?php echo $fotos[0]; ?>" alt="Foto principal de <?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?>" class="foto-principal">
                
                <?php if (count($fotos) > 1): ?>
                <div class="fotos-secundarias">
                    <?php foreach (array_slice($fotos, 1, 4) as $index => $foto): ?>
                    <img src="<?php echo $foto; ?>" alt="Foto <?php echo $index + 2; ?> de <?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?>" class="foto-secundaria" onclick="cambiarFotoPrincipal('<?php echo $foto; ?>', '<?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?>')">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Información del alojamiento -->
            <div class="detalle-info">
                <!-- Características -->
                <div class="info-card">
                    <h3><i class="fas fa-info-circle"></i> Características</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><i class="fas fa-home" style="color: var(--primary-color); width: 20px;"></i> <strong>Tipo:</strong> <?php echo htmlspecialchars($alojamiento['accommodation_type'], ENT_QUOTES, 'UTF-8'); ?></li>
                        <li style="margin-bottom: 0.5rem;"><i class="fas fa-users" style="color: var(--primary-color); width: 20px;"></i> <strong>Capacidad:</strong> <?php echo $alojamiento['capacity']; ?> personas</li>
                        <li style="margin-bottom: 0.5rem;"><i class="fas fa-map-marker-alt" style="color: var(--primary-color); width: 20px;"></i> <strong>Dirección:</strong> <?php echo htmlspecialchars($alojamiento['address'], ENT_QUOTES, 'UTF-8'); ?></li>
                        <li style="margin-bottom: 0.5rem;"><i class="fas fa-star" style="color: var(--primary-color); width: 20px;"></i> <strong>Estado:</strong> <?php echo $alojamiento['is_active'] ? 'Activo' : 'Inactivo'; ?></li>
                    </ul>
                </div>

                <!-- Contacto -->
                <div class="info-card">
                    <h3><i class="fas fa-phone"></i> Contacto</h3>
                    <ul style="list-style: none; padding: 0;">
                        <?php if (!empty($alojamiento['phone'])): ?>
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-phone" style="color: var(--primary-color); width: 20px;"></i> 
                            <a href="tel:<?php echo htmlspecialchars($alojamiento['phone'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($alojamiento['phone'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($alojamiento['email'])): ?>
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-envelope" style="color: var(--primary-color); width: 20px;"></i> 
                            <a href="mailto:<?php echo htmlspecialchars($alojamiento['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($alojamiento['email'], ENT_QUOTES, 'UTF-8'); ?></a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($alojamiento['website'])): ?>
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-globe" style="color: var(--primary-color); width: 20px;"></i> 
                            <a href="<?php echo htmlspecialchars($alojamiento['website'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Sitio web</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Descripción -->
            <div class="detalle-descripcion">
                <h3><i class="fas fa-file-text"></i> Descripción</h3>
                <p style="font-size: 1.1rem; line-height: 1.6; color: #333;">
                    <?php echo nl2br(htmlspecialchars($alojamiento['description'] ?? 'Descripción no disponible.', ENT_QUOTES, 'UTF-8')); ?>
                </p>
            </div>

            <!-- Botones de contacto -->
            <div class="info-card">
                <h3><i class="fas fa-comments"></i> Contactar</h3>
                <div class="contacto-botones">
                    <?php if (!empty($alojamiento['phone'])): ?>
                    <a href="tel:<?php echo htmlspecialchars($alojamiento['phone'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-contacto btn-telefono">
                        <i class="fas fa-phone"></i> Llamar
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($alojamiento['phone'])): ?>
                    <a href="https://wa.me/34<?php echo preg_replace('/[^0-9]/', '', $alojamiento['phone']); ?>" target="_blank" class="btn-contacto btn-whatsapp">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($alojamiento['email'])): ?>
                    <a href="mailto:<?php echo htmlspecialchars($alojamiento['email'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-contacto btn-email">
                        <i class="fas fa-envelope"></i> Email
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($alojamiento['website'])): ?>
                    <a href="<?php echo htmlspecialchars($alojamiento['website'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn-contacto btn-web">
                        <i class="fas fa-globe"></i> Sitio Web
                    </a>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Alojamiento no encontrado -->
            <div class="detalle-header" style="text-align: center;">
                <h1 class="detalle-titulo">🏠 Alojamiento no encontrado</h1>
                <p class="detalle-ubicacion">El alojamiento que buscas no existe o ha sido eliminado.</p>
                <div style="margin-top: 2rem;">
                    <a href="alojamientos-turisticos-paginacion.html" class="btn-primary" style="padding: 1rem 2rem; text-decoration: none; display: inline-block; border-radius: 8px;">
                        <i class="fas fa-arrow-left"></i> Volver a Alojamientos
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Botón para volver a la lista -->
        <div class="volver-lista">
            <a href="alojamientos-turisticos-paginacion.html" class="btn-secondary" style="padding: 1rem 2rem; text-decoration: none; border-radius: 8px; display: inline-block;">
                <i class="fas fa-list"></i> Ver todos los alojamientos
            </a>
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
                    <a href="aviso-legal.html">Aviso Legal</a>
                    <a href="politica-cookies.html">Política de Cookies</a>
                    <a href="agradecimientos.html">Agradecimientos</a>
                </div>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/rutas_rurales/" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div class="footer-copyright">
                <p>&copy; 2025 rutasrurales.io. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Función para cambiar la foto principal
        function cambiarFotoPrincipal(nuevaFoto, nombreAlojamiento) {
            const fotoPrincipal = document.querySelector('.foto-principal');
            if (fotoPrincipal) {
                fotoPrincipal.src = nuevaFoto;
                fotoPrincipal.alt = 'Foto principal de ' + nombreAlojamiento;
            }
        }

        // Función para compartir en redes sociales
        function compartirEnRedSocial(red) {
            const url = encodeURIComponent(window.location.href);
            const titulo = encodeURIComponent(document.title);
            const descripcion = encodeURIComponent(document.querySelector('meta[name="description"]')?.content || '');
            
            let urlCompartir = '';
            
            switch(red) {
                case 'facebook':
                    urlCompartir = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'twitter':
                    urlCompartir = `https://twitter.com/intent/tweet?url=${url}&text=${titulo}`;
                    break;
                case 'whatsapp':
                    urlCompartir = `https://wa.me/?text=${titulo}%20${url}`;
                    break;
                case 'linkedin':
                    urlCompartir = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
                    break;
            }
            
            if (urlCompartir) {
                window.open(urlCompartir, '_blank', 'width=600,height=400');
            }
        }

        // Agregar botones de compartir si hay alojamiento
        <?php if ($alojamiento): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const infoCard = document.querySelector('.info-card h3');
            if (infoCard && infoCard.textContent.includes('Contactar')) {
                const compartirDiv = document.createElement('div');
                compartirDiv.className = 'contacto-botones';
                compartirDiv.style.marginTop = '1rem';
                compartirDiv.innerHTML = `
                    <h4 style="margin-bottom: 1rem; color: var(--primary-color);"><i class="fas fa-share-alt"></i> Compartir</h4>
                    <button onclick="compartirEnRedSocial('facebook')" class="btn-contacto" style="background: #3b5998; color: white;">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </button>
                    <button onclick="compartirEnRedSocial('twitter')" class="btn-contacto" style="background: #1da1f2; color: white;">
                        <i class="fab fa-twitter"></i> Twitter
                    </button>
                    <button onclick="compartirEnRedSocial('whatsapp')" class="btn-contacto" style="background: #25d366; color: white;">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </button>
                    <button onclick="compartirEnRedSocial('linkedin')" class="btn-contacto" style="background: #0077b5; color: white;">
                        <i class="fab fa-linkedin-in"></i> LinkedIn
                    </button>
                `;
                infoCard.parentNode.appendChild(compartirDiv);
            }
        });
        <?php endif; ?>

        // Analytics tracking
        <?php if ($alojamiento): ?>
        gtag('event', 'view_item', {
            'currency': 'EUR',
            'value': <?php echo $alojamiento['price_per_night'] ?? 0; ?>,
            'items': [{
                'item_id': '<?php echo $alojamiento['id']; ?>',
                'item_name': '<?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?>',
                'category': 'Alojamiento Turístico',
                'quantity': 1,
                'price': <?php echo $alojamiento['price_per_night'] ?? 0; ?>
            }]
        });
        <?php endif; ?>
    </script>
</body>
</html>
