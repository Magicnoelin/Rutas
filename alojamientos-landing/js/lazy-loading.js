/**
 * ════════════════════════════════════════════════════════════════════════════
 * LAZY LOADING INTELIGENTE — Solo carga imágenes cuando son necesarias
 * ════════════════════════════════════════════════════════════════════════════
 */

document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar si hay soporte para IntersectionObserver
    if (!window.IntersectionObserver) {
        // Fallback: cargar todas las imágenes inmediatamente en navegadores antiguos
        loadAllImages();
        return;
    }

    let imageCount = 0;
    const maxConcurrentLoads = 2; // Limitar cargas simultáneas
    const loadingQueue = [];
    let isProcessingQueue = false;

    // Configurar el observer para detectar cuándo las imágenes entran en el viewport
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                
                // Desconectar el observer para esta imagen
                observer.unobserve(img);
                
                // Añadir a la cola de carga
                loadingQueue.push(img);
                processLoadingQueue();
            }
        });
    }, {
        // Cargar la imagen cuando esté 100px antes de entrar en el viewport
        rootMargin: '100px 0px 100px 0px',
        threshold: 0.01
    });

    // Procesar la cola de carga de forma controlada
    function processLoadingQueue() {
        if (isProcessingQueue || loadingQueue.length === 0) return;
        
        isProcessingQueue = true;
        
        // Procesar hasta 2 imágenes simultáneamente
        const batch = loadingQueue.splice(0, maxConcurrentLoads);
        let pendingLoads = batch.length;
        
        batch.forEach(img => {
            loadImageLazy(img).finally(() => {
                pendingLoads--;
                if (pendingLoads === 0) {
                    isProcessingQueue = false;
                    // Continuar procesando la cola si hay más elementos
                    setTimeout(() => processLoadingQueue(), 100);
                }
            });
        });
    }

    // Cargar una imagen específica
    function loadImageLazy(img) {
        return new Promise((resolve, reject) => {
            const realSrc = img.dataset.src;
            if (!realSrc) {
                resolve();
                return;
            }

            // Crear una nueva imagen para precargar
            const newImg = new Image();
            
            newImg.onload = () => {
                // Añadir clase de transición
                img.classList.add('lnd-card__img--loading');
                
                // Cambiar la fuente después de una pequeña pausa para la transición
                setTimeout(() => {
                    img.src = realSrc;
                    img.classList.remove('lnd-card__img--lazy', 'lnd-card__img--loading');
                    img.classList.add('lnd-card__img--loaded');
                    
                    // Limpiar el data-src
                    delete img.dataset.src;
                    
                    imageCount++;
                    console.log(`🖼️ Imagen cargada (#${imageCount}):`, realSrc.substring(realSrc.lastIndexOf('/') + 1));
                }, 150);
                
                resolve();
            };
            
            newImg.onerror = () => {
                console.warn('❌ Error cargando imagen:', realSrc);
                // Mantener el placeholder en caso de error
                img.classList.remove('lnd-card__img--lazy');
                reject();
            };
            
            // Iniciar la carga
            newImg.src = realSrc;
        });
    }

    // Fallback para navegadores sin IntersectionObserver
    function loadAllImages() {
        const lazyImages = document.querySelectorAll('.lnd-card__img--lazy');
        lazyImages.forEach(img => {
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.classList.remove('lnd-card__img--lazy');
                delete img.dataset.src;
            }
        });
    }

    // Observar todas las imágenes lazy
    const lazyImages = document.querySelectorAll('.lnd-card__img--lazy');
    lazyImages.forEach(img => imageObserver.observe(img));

    // Log inicial
    if (lazyImages.length > 0) {
        console.log(`🚀 Sistema de lazy loading iniciado. ${lazyImages.length} imágenes en cola.`);
    }

    // Cargar imágenes inmediatamente si el usuario hace scroll muy rápido
    let scrollTimeout;
    window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            // Verificar si hay imágenes muy cerca del viewport que deberíamos cargar
            const viewportHeight = window.innerHeight;
            const scrollY = window.scrollY;
            
            document.querySelectorAll('.lnd-card__img--lazy').forEach(img => {
                const rect = img.getBoundingClientRect();
                const distanceFromViewport = rect.top - viewportHeight;
                
                // Si está a menos de 300px del viewport, cargarla inmediatamente
                if (distanceFromViewport < 300) {
                    imageObserver.unobserve(img);
                    loadingQueue.push(img);
                    processLoadingQueue();
                }
            });
        }, 100);
    }, { passive: true });
});