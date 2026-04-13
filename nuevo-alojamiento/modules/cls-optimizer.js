// cls-optimizer.js - Optimize Cumulative Layout Shift (CLS)
export function initCLSOptimizer() {
    // Mark when content is loaded to remove skeletons smoothly
    function markContentLoaded() {
        document.body.classList.add('content-loaded');
        
        // Remove min-height constraints after content loads
        setTimeout(() => {
            const elements = document.querySelectorAll('.content-main, .card-booking, .text-description');
            elements.forEach(el => {
                el.style.minHeight = 'auto';
                el.style.transition = 'none'; // Remove transition after initial load
            });
        }, 500); // Short delay to ensure content is visible
    }
    
    // Handle image loading to prevent layout shifts
    function optimizeImages() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            if (img.complete) {
                // Image already loaded
                img.style.opacity = '1';
            } else {
                // Image still loading
                img.style.opacity = '0';
                img.style.transition = 'opacity 0.3s ease';
                img.addEventListener('load', function() {
                    this.style.opacity = '1';
                });
                img.addEventListener('error', function() {
                    this.style.opacity = '1'; // Show even if error
                });
            }
        });
    }
    
    // Reserve space for dynamically loaded content
    function reserveSpaceForDynamicContent() {
        // Gallery
        const gallery = document.getElementById('galeria');
        if (gallery) {
            const originalHeight = gallery.offsetHeight;
            gallery.dataset.originalHeight = originalHeight;
            gallery.style.minHeight = originalHeight + 'px';
        }
        
        // Description
        const description = document.getElementById('descripcion');
        if (description) {
            const originalHeight = description.offsetHeight;
            description.dataset.originalHeight = originalHeight;
            description.style.minHeight = originalHeight + 'px';
        }
    }
    
    // Release reserved space when content is loaded
    function releaseReservedSpace() {
        setTimeout(() => {
            const elements = document.querySelectorAll('[data-original-height]');
            elements.forEach(el => {
                el.style.minHeight = 'auto';
                delete el.dataset.originalHeight;
            });
        }, 1000); // Release after content should be loaded
    }
    
    // Initialize
    function init() {
        // Reserve space early
        reserveSpaceForDynamicContent();
        
        // Optimize images
        optimizeImages();
        
        // Mark content as loaded when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                markContentLoaded();
                releaseReservedSpace();
            });
        } else {
            markContentLoaded();
            releaseReservedSpace();
        }
        
        // Listen for dynamic content updates
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // New content added, re-optimize
                    optimizeImages();
                }
            });
        });
        
        // Observe main content areas
        const mainContent = document.querySelector('.content-main');
        const sidebar = document.querySelector('.sidebar');
        if (mainContent) observer.observe(mainContent, { childList: true, subtree: true });
        if (sidebar) observer.observe(sidebar, { childList: true, subtree: true });
        
        console.log('CLS Optimizer initialized');
    }
    
    // Start initialization
    init();
}

// Auto-initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCLSOptimizer);
} else {
    initCLSOptimizer();
}