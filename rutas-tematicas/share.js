/**
 * Módulo de Compartir para Rutas Temáticas
 *
 * Detecta el botón de compartir en el hero y abre un diálogo modal
 * con opciones para compartir en redes sociales o copiar el enlace.
 */
document.addEventListener('DOMContentLoaded', () => {
    const shareButton = document.getElementById('rt-hero-share-btn');
    if (!shareButton) {
        return;
    }

    const routeName = shareButton.dataset.routeName || 'esta increíble ruta';
    const routeUrl = shareButton.dataset.routeUrl || window.location.href;

    shareButton.addEventListener('click', () => {
        // Si el navegador soporta la API nativa de compartir, la usamos
        if (navigator.share) {
            navigator.share({
                title: `Descubre ${routeName} en Rutas Rurales`,
                text: `¡Echa un vistazo a ${routeName}! Un plan perfecto para una escapada.`,
                url: routeUrl,
            }).catch((error) => console.log('Error al compartir:', error));
        } else {
            // Fallback para navegadores de escritorio o sin soporte
            showShareModal();
        }
    });

    function showShareModal() {
        // Evitar duplicados
        if (document.getElementById('rt-share-modal')) {
            return;
        }

        const modalHTML = `
            <div class="rt-share-modal" id="rt-share-modal">
                <div class="rt-share-modal__overlay"></div>
                <div class="rt-share-modal__content">
                    <button class="rt-share-modal__close" aria-label="Cerrar">&times;</button>
                    <h3 class="rt-share-modal__title">Compartir ruta</h3>
                    <p class="rt-share-modal__subtitle">¡Comparte ${routeName} con tus amigos!</p>
                    <div class="rt-share-modal__buttons">
                        <a href="https://api.whatsapp.com/send?text=¡Echa un vistazo a esta ruta! ${encodeURIComponent(routeUrl)}" target="_blank" rel="noopener noreferrer" class="rt-share-btn rt-share-btn--whatsapp">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.61 15.35 3.48 16.84L2 22L7.32 20.55C8.77 21.34 10.38 21.81 12.04 21.81C17.5 21.81 21.95 17.36 21.95 11.9C21.95 6.45 17.5 2 12.04 2M12.04 20.13C10.56 20.13 9.12 19.68 7.89 18.85L7.54 18.64L4.5 19.5L5.41 16.59L5.18 16.23C4.29 14.94 3.82 13.45 3.82 11.91C3.82 7.39 7.52 3.69 12.04 3.69C16.56 3.69 20.26 7.39 20.26 11.91C20.26 16.43 16.56 20.13 12.04 20.13M17.46 14.47C17.21 14.35 16.05 13.78 15.83 13.7C15.61 13.62 15.46 13.58 15.31 13.82C15.16 14.06 14.66 14.64 14.51 14.82C14.36 15 14.22 15.02 13.97 14.9C13.72 14.78 12.82 14.45 11.75 13.5C10.91 12.76 10.33 11.85 10.18 11.61C10.03 11.37 10.16 11.25 10.28 11.13C10.38 11.03 10.51 10.85 10.63 10.71C10.75 10.57 10.8 10.45 10.92 10.23C11.04 10.01 10.99 9.84 10.92 9.72C10.85 9.6 10.3 8.45 10.1 7.9C9.91 7.35 9.71 7.42 9.56 7.41H9.21C9.06 7.41 8.78 7.49 8.53 7.73C8.28 7.97 7.63 8.55 7.63 9.7C7.63 10.85 8.56 11.95 8.68 12.1C8.8 12.25 10.25 14.53 12.52 15.48C14.8 16.43 14.8 16.09 15.15 16.05C15.5 16.01 16.49 15.45 16.69 14.88C16.89 14.31 16.89 13.84 16.81 13.7C16.74 13.56 16.62 13.5 16.49 13.42C16.37 13.34 17.71 14.59 17.46 14.47Z"></path></svg>
                            <span>WhatsApp</span>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=¡Echa un vistazo a ${encodeURIComponent(routeName)}!&url=${encodeURIComponent(routeUrl)}&via=rutasrurales_io" target="_blank" rel="noopener noreferrer" class="rt-share-btn rt-share-btn--twitter">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M22.46,6C21.69,6.35 20.86,6.58 20,6.69C20.88,6.16 21.56,5.32 21.88,4.31C21.05,4.81 20.13,5.16 19.16,5.36C18.37,4.5 17.26,4 16,4C13.65,4 11.73,5.92 11.73,8.29C11.73,8.63 11.77,8.96 11.84,9.27C8.28,9.09 5.11,7.38 3,4.79C2.63,5.42 2.42,6.16 2.42,6.94C2.42,8.43 3.17,9.75 4.33,10.5C3.62,10.5 2.96,10.3 2.38,10C2.38,10 2.38,10 2.38,10.03C2.38,12.11 3.86,13.85 5.82,14.24C5.46,14.34 5.08,14.39 4.69,14.39C4.42,14.39 4.15,14.36 3.89,14.31C4.43,16 6,17.26 7.89,17.29C6.43,18.45 4.58,19.13 2.56,19.13C2.22,19.13 1.88,19.11 1.54,19.07C3.44,20.29 5.7,21 8.12,21C16,21 20.33,14.46 20.33,8.79C20.33,8.6 20.33,8.42 20.32,8.23C21.16,7.63 21.88,6.87 22.46,6Z"></path></svg>
                            <span>Twitter</span>
                        </a>
                    </div>
                    <div class="rt-share-modal__copy-link">
                        <input type="text" readonly value="${routeUrl}">
                        <button id="rt-copy-btn">Copiar</button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = document.getElementById('rt-share-modal');
        const closeButton = modal.querySelector('.rt-share-modal__close');
        const overlay = modal.querySelector('.rt-share-modal__overlay');
        const copyButton = modal.querySelector('#rt-copy-btn');

        const closeModal = () => {
            modal.remove();
        };

        closeButton.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);

        copyButton.addEventListener('click', () => {
            const input = modal.querySelector('.rt-share-modal__copy-link input');
            input.select();
            input.setSelectionRange(0, 99999); // Para móviles
            try {
                document.execCommand('copy');
                copyButton.textContent = '¡Copiado!';
                setTimeout(() => {
                    copyButton.textContent = 'Copiar';
                }, 2000);
            } catch (err) {
                console.error('No se pudo copiar el enlace:', err);
            }
        });
    }
});