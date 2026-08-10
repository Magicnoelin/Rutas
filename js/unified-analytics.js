/**
 * Cliente JavaScript para Sistema Unificado de Analytics
 * Sincronizado con Google Search Console
 */

class RutasAnalytics {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || '/api/unified-analytics.php';
        this.debug = options.debug || false;
        this.autoTrack = options.autoTrack !== false; // Por defecto true
        this.tracked = new Set(); // Para evitar tracking duplicado
        
        if (this.autoTrack) {
            this.init();
        }
    }
    
    /**
     * Inicializar tracking automático
     */
    init() {
        // Detectar y trackear automáticamente la página actual
        this.detectAndTrackCurrentPage();
        
        // Trackear clics en enlaces internos
        this.setupLinkTracking();
        
        // Sincronizar con Google Analytics si está disponible
        this.syncWithGoogleAnalytics();
        
        this.log('RutasAnalytics inicializado correctamente');
    }
    
    /**
     * Detectar tipo de página y trackear automáticamente
     */
    detectAndTrackCurrentPage() {
        const url = window.location.pathname;
        let resourceType = null;
        let resourceId = null;
        
        // Detectar patrones de URL
        const patterns = [
            { regex: /\/alojamiento\/([^\/]+)/, type: 'accommodation' },
            { regex: /\/evento\/([^\/]+)/, type: 'event' },
            { regex: /\/lugar\/([^\/]+)/, type: 'place' },
            { regex: /\/actividad\/([^\/]+)/, type: 'activity' },
            { regex: /\/ruta\/([^\/]+)/, type: 'route' }
        ];
        
        for (const pattern of patterns) {
            const match = url.match(pattern.regex);
            if (match) {
                resourceType = pattern.type;
                // El ID puede estar en un atributo del DOM o derivarse del slug
                resourceId = this.getResourceIdFromPage(pattern.type, match[1]);
                break;
            }
        }
        
        // También verificar elementos con atributo data-resource
        if (!resourceType) {
            const resourceElement = document.querySelector('[data-resource-type][data-resource-id]');
            if (resourceElement) {
                resourceType = resourceElement.dataset.resourceType;
                resourceId = parseInt(resourceElement.dataset.resourceId);
            }
        }
        
        if (resourceType && resourceId) {
            this.trackView(resourceType, resourceId);
        }
    }
    
    /**
     * Obtener ID del recurso desde la página
     */
    getResourceIdFromPage(type, slug) {
        // Buscar en elementos del DOM
        const idElement = document.querySelector(`[data-${type}-id]`);
        if (idElement) {
            return parseInt(idElement.dataset[`${type}Id`]);
        }
        
        // Buscar en variables globales JavaScript comunes
        const globalVars = [`${type}Id`, 'resourceId', 'currentId'];
        for (const varName of globalVars) {
            if (window[varName]) {
                return parseInt(window[varName]);
            }
        }
        
        // Buscar en elementos con clase que contenga ID
        const classElement = document.querySelector(`[class*="${type}-"], [id*="${type}-"]`);
        if (classElement) {
            const classMatch = classElement.className.match(new RegExp(`${type}-(\\d+)`));
            if (classMatch) {
                return parseInt(classMatch[1]);
            }
        }
        
        this.log(`No se pudo determinar ID para ${type} con slug: ${slug}`);
        return null;
    }
    
    /**
     * Trackear vista de recurso
     */
    async trackView(resourceType, resourceId) {
        if (!resourceType || !resourceId) {
            this.log('Error: resourceType y resourceId son requeridos');
            return;
        }
        
        const trackingKey = `${resourceType}_${resourceId}`;
        if (this.tracked.has(trackingKey)) {
            this.log(`Vista ya trackeada para ${trackingKey}`);
            return;
        }
        
        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: 'track_view',
                    resource_type: resourceType,
                    resource_id: resourceId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.tracked.add(trackingKey);
                this.log(`Vista trackeada: ${resourceType} #${resourceId}`, result);
                
                // Actualizar contador en DOM si existe
                this.updateViewCounterInDOM(result);
                
                // Evento personalizado para otros scripts
                this.dispatchTrackingEvent('view', resourceType, resourceId, result);
            } else {
                this.log('Error al trackear vista:', result);
            }
            
        } catch (error) {
            this.log('Error de red al trackear vista:', error);
        }
    }
    
    /**
     * Obtener estadísticas de un recurso
     */
    async getStats(resourceType, resourceId) {
        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'get_stats',
                    resource_type: resourceType,
                    resource_id: resourceId
                })
            });
            
            return await response.json();
        } catch (error) {
            this.log('Error al obtener estadísticas:', error);
            return null;
        }
    }
    
    /**
     * Actualizar contador visual en el DOM
     */
    updateViewCounterInDOM(result) {
        const selectors = [
            '#view-count',
            '.view-count',
            '[data-view-count]',
            '.stat-number',
            '.views-counter'
        ];
        
        for (const selector of selectors) {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                if (result.stats && result.stats.views_count !== undefined) {
                    element.textContent = this.formatNumber(result.stats.views_count);
                }
            });
        }
    }
    
    /**
     * Configurar tracking de enlaces internos
     */
    setupLinkTracking() {
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!link || !link.href.includes(window.location.hostname)) return;
            
            const href = link.href;
            const patterns = [
                { regex: /\/alojamiento\/([^\/\?#]+)/, type: 'accommodation' },
                { regex: /\/evento\/([^\/\?#]+)/, type: 'event' },
                { regex: /\/lugar\/([^\/\?#]+)/, type: 'place' },
                { regex: /\/actividad\/([^\/\?#]+)/, type: 'activity' },
                { regex: /\/ruta\/([^\/\?#]+)/, type: 'route' }
            ];
            
            for (const pattern of patterns) {
                const match = href.match(pattern.regex);
                if (match) {
                    // Marcar para tracking cuando se cargue la página
                    sessionStorage.setItem('pending_track', JSON.stringify({
                        type: pattern.type,
                        slug: match[1],
                        timestamp: Date.now()
                    }));
                    break;
                }
            }
        });
    }
    
    /**
     * Sincronizar con Google Analytics
     */
    syncWithGoogleAnalytics() {
        if (typeof gtag !== 'undefined') {
            this.log('Google Analytics detectado, sincronización habilitada');
            
            // Escuchar eventos de tracking personalizados
            document.addEventListener('rutas-analytics', (event) => {
                const { eventType, resourceType, resourceId } = event.detail;
                
                gtag('event', eventType, {
                    'event_category': 'Resource Interaction',
                    'event_label': `${resourceType}_${resourceId}`,
                    'custom_map': {
                        'dimension1': resourceType,
                        'dimension2': resourceId
                    }
                });
            });
        }
    }
    
    /**
     * Disparar evento personalizado
     */
    dispatchTrackingEvent(eventType, resourceType, resourceId, data) {
        const event = new CustomEvent('rutas-analytics', {
            detail: { eventType, resourceType, resourceId, data }
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Formatear número para mostrar
     */
    formatNumber(num) {
        if (num < 1000) return num.toString();
        if (num < 1000000) return (num / 1000).toFixed(1) + 'K';
        return (num / 1000000).toFixed(1) + 'M';
    }
    
    /**
     * Logger con debug
     */
    log(...args) {
        if (this.debug) {
            console.log('[RutasAnalytics]', ...args);
        }
    }
    
    /**
     * Sincronizar contadores existentes (solo admin)
     */
    async syncCounters() {
        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'sync_counters'
                })
            });
            
            return await response.json();
        } catch (error) {
            this.log('Error al sincronizar contadores:', error);
            return null;
        }
    }
    
    /**
     * Obtener reporte diario (solo admin)
     */
    async getDailyReport(date) {
        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'daily_report',
                    date: date
                })
            });
            
            return await response.json();
        } catch (error) {
            this.log('Error al obtener reporte diario:', error);
            return null;
        }
    }
}

// Inicialización automática
window.addEventListener('DOMContentLoaded', () => {
    // Verificar si se debe auto-inicializar
    const shouldAutoInit = !document.querySelector('[data-no-auto-analytics]');
    
    if (shouldAutoInit) {
        window.rutasAnalytics = new RutasAnalytics({
            debug: window.location.hostname === 'localhost' || 
                   window.location.search.includes('debug=1')
        });
    }
});

// Exportar para uso manual
window.RutasAnalytics = RutasAnalytics;