// Configuración de la aplicación Rutas
// Cambia estas constantes según tu entorno

// API Configuration
const API_BASE_URL = window.location.hostname === 'localhost'
    ? 'http://localhost/rutas/api/'
    : 'https://rutasrurales.io/api/';

const API_URL = API_BASE_URL + 'crear.php';
const TEST_API_URL = API_BASE_URL + 'test.php';

// Otras configuraciones
const APP_CONFIG = {
    siteName: 'Rutas',
    version: '1.0.0',
    environment: window.location.hostname === 'localhost' ? 'development' : 'production'
};

// Exportar configuración (para módulos ES6 si es necesario)
// export { API_URL, TEST_API_URL, APP_CONFIG };
