# 🌄 Rutas - Plataforma Web con Asistente de IA

**Red Unificada de Turistas, Alojamientos y Servicios**

Plataforma web interactiva para promover el turismo rural en Soria, España, con un asistente de inteligencia artificial integrado que recomienda rutas personalizadas incluyendo alojamientos, actividades turísticas y lugares de interés.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black)

## 📋 Índice

- [Características](#-características)
- [Tecnologías](#-tecnologías)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Instalación](#-instalación)
- [Uso](#-uso)
- [Agente de IA](#-agente-de-ia)
- [Funcionalidades](#-funcionalidades)
- [Secciones de la Web](#-secciones-de-la-web)
- [Personalización](#-personalización)
- [Responsive Design](#-responsive-design)
- [Navegadores Compatibles](#-navegadores-compatibles)
- [Capturas de Pantalla](#-capturas-de-pantalla)
- [Contribución](#-contribución)
- [Licencia](#-licencia)
- [Contacto](#-contacto)

## ✨ Características

- **Asistente Virtual con IA**: Sistema inteligente que analiza las preferencias del usuario y genera rutas turísticas personalizadas
- **Interfaz Moderna**: Diseño atractivo y profesional con animaciones suaves
- **100% Responsive**: Optimizado para todos los dispositivos (móvil, tablet, desktop)
- **Chat Interactivo**: Sistema de conversación en tiempo real con el asistente
- **Opciones Rápidas**: Botones de acceso rápido para facilitar la interacción
- **Recomendaciones Inteligentes**: Sugerencias basadas en días de estancia, intereses y temporada
- **Información Completa**: Alojamientos, actividades y lugares de interés detallados

## 🚀 Tecnologías

Este proyecto está desarrollado utilizando tecnologías web modernas:

- **HTML5**: Estructura semántica y accesible
- **CSS3**: Estilos modernos con variables CSS, Flexbox y Grid
- **JavaScript (Vanilla)**: Lógica del asistente de IA sin dependencias externas
- **Font Awesome 6.4.0**: Iconografía profesional
- **Unsplash**: Imágenes de alta calidad

### Ventajas Técnicas

- ✅ **Sin dependencias**: No requiere frameworks ni librerías externas
- ✅ **Ligero**: Carga rápida y rendimiento óptimo
- ✅ **Modular**: Código organizado y fácil de mantener
- ✅ **Escalable**: Fácil de expandir con nuevas funcionalidades
- ✅ **SEO Friendly**: Estructura HTML semántica y optimizada

## 📁 Estructura del Proyecto

```
turismo-soria/
│
├── index.html          # Página principal (estructura HTML)
├── styles.css          # Estilos y diseño visual
├── script.js           # Lógica JavaScript + Agente de IA
├── Logo.png            # Logo de Rutas
└── README.md           # Documentación del proyecto
```

### Descripción de Archivos

#### **index.html**
- Estructura completa de la página web
- Secciones: Header, Hero, Alojamientos, Actividades, Lugares, Asistente IA, Footer
- Navegación responsive con menú fijo
- Sistema de chat integrado con interfaz de usuario

#### **styles.css**
- Diseño moderno con variables CSS para fácil personalización
- Sistema de grid responsive para las tarjetas
- Animaciones y transiciones suaves
- Estilos específicos para el chat del asistente
- Media queries para adaptabilidad móvil

#### **script.js**
- Motor del asistente de inteligencia artificial
- Sistema de análisis de lenguaje natural (NLP básico)
- Generador de rutas personalizadas
- Gestión del estado de la conversación
- Animaciones y efectos interactivos

## 🔧 Instalación

### Opción 1: Descarga Directa

1. Descarga todos los archivos del proyecto
2. Mantén los archivos en la misma carpeta
3. Abre `index.html` en tu navegador web favorito

### Opción 2: Clonar con Git

```bash
# Clonar el repositorio (si está en Git)
git clone [url-del-repositorio]

# Navegar a la carpeta
cd turismo-soria

# Abrir en el navegador
start index.html  # Windows
open index.html   # macOS
xdg-open index.html  # Linux
```

### Opción 3: Servidor Local

Para mejor experiencia, usa un servidor local:

```bash
# Con Python 3
python -m http.server 8000

# Con Node.js (http-server)
npx http-server

# Con PHP
php -S localhost:8000
```

Luego abre tu navegador en: `http://localhost:8000`

## 💻 Uso

### Navegación Básica

1. **Explorar Secciones**: Usa el menú de navegación para acceder a diferentes secciones
2. **Ver Alojamientos**: Revisa las opciones de casas rurales y hoteles
3. **Descubrir Actividades**: Explora las actividades turísticas disponibles
4. **Lugares de Interés**: Conoce los sitios históricos y naturales

### Uso del Asistente de IA

1. **Iniciar Conversación**: 
   - Haz clic en "Planifica tu Viaje con IA" en el hero
   - O navega a la sección "Asistente IA"

2. **Opciones Rápidas**:
   - Selecciona una opción predefinida (1 día, 2-3 días, fin de semana, 1 semana)
   - O escribe tu consulta personalizada

3. **Interacción Natural**:
   - Escribe en lenguaje natural (español)
   - Menciona tus intereses (naturaleza, cultura, gastronomía, etc.)
   - Indica la duración de tu viaje

4. **Recibir Recomendaciones**:
   - El asistente generará una ruta personalizada
   - Incluirá itinerario día por día
   - Recomendará alojamientos específicos
   - Proporcionará consejos útiles

## 🤖 Agente de IA

### Funcionamiento del Sistema Inteligente

El asistente de IA utiliza un sistema de procesamiento de lenguaje natural (NLP) básico que:

1. **Analiza** el mensaje del usuario mediante expresiones regulares
2. **Detecta** patrones clave:
   - Duración del viaje (días)
   - Intereses turísticos (naturaleza, cultura, etc.)
   - Presupuesto (económico, medio, alto)
   - Tipo de alojamiento preferido
   - Temporada del año

3. **Mantiene** el contexto de la conversación
4. **Genera** respuestas personalizadas basadas en:
   - Información detectada
   - Estado de la conversación
   - Base de datos de lugares y actividades

### Capacidades del Asistente

#### Categorías de Intereses Reconocidas:
- 🥾 **Naturaleza**: Senderismo, montaña, rutas naturales
- 🏛️ **Cultura**: Historia, monumentos, patrimonio
- 🧘 **Relax**: Descanso, spa, tranquilidad
- 🍷 **Gastronomía**: Comida, vinos, restaurantes
- 🚴 **Aventura**: Deportes, actividades activas
- 📸 **Fotografía**: Paisajes, lugares fotogénicos
- 👨‍👩‍👧‍👦 **Familia**: Actividades con niños
- ✨ **Astronomía**: Observación de estrellas

#### Duraciones Soportadas:
- 1 día (ruta exprés)
- 2-3 días (fin de semana)
- 4-5 días (estancia media)
- 7 días (semana completa)
- Personalizadas

### Ejemplos de Consultas

```
"Quiero ir 3 días y me gusta el senderismo"
→ Genera ruta enfocada en naturaleza

"Fin de semana cultural e histórico"
→ Ruta por monumentos y patrimonio

"Una semana con la familia en verano"
→ Itinerario completo familiar

"2 días de relax y gastronomía"
→ Experiencia tranquila con buena comida
```

## 🎯 Funcionalidades

### Funcionalidades Principales

1. **Sistema de Chat en Tiempo Real**
   - Mensajes animados con efecto fade-in
   - Indicador de escritura (typing indicator)
   - Scroll automático a nuevos mensajes
   - Historial de conversación persistente

2. **Generación de Rutas Personalizadas**
   - Itinerarios día por día
   - Recomendaciones de alojamiento
   - Sugerencias de actividades
   - Consejos prácticos

3. **Navegación Suave**
   - Scroll suave entre secciones
   - Menú fijo al hacer scroll
   - Enlaces internos funcionales

4. **Diseño Responsive**
   - Adaptación automática a móviles
   - Grid flexible para las tarjetas
   - Menú optimizado para touch

5. **Animaciones y Efectos**
   - Hover effects en tarjetas
   - Transiciones suaves
   - Animaciones de entrada
   - Efectos de scroll

## 📱 Secciones de la Web

### 1. Hero Section
- Imagen de fondo impactante de Soria
- Título y eslogan principal
- Botón CTA al asistente de IA

### 2. Alojamientos Rurales
**Incluye 3 opciones destacadas:**
- Casa Rural El Roble (Valle de Hoyocasero)
- Posada La Laguna Negra (Vinuesa)
- Hotel Rural Numantino (Garray)

Cada alojamiento muestra:
- Imagen representativa
- Nombre y ubicación
- Descripción breve
- Características (WiFi, parking, spa, etc.)
- Precio orientativo

### 3. Actividades Turísticas
**4 actividades principales:**
- Senderismo en la Laguna Negra
- Cañón del Río Lobos
- Ruta Micológica
- Observación Astronómica

Información incluida:
- Duración estimada
- Nivel de dificultad
- Época recomendada
- Características especiales

### 4. Lugares de Interés
**Sitios históricos y culturales:**
- Yacimiento de Numancia
- Monasterio de San Juan de Duero
- Villa de Medinaceli
- El Burgo de Osma

Detalles proporcionados:
- Importancia histórica
- Ubicación
- Precio de entrada
- Características únicas

### 5. Asistente Inteligente
- Chat interactivo completo
- Opciones rápidas de respuesta
- Área de mensajes con scroll
- Input para escritura libre
- Avatares diferenciados (usuario/bot)

### 6. Footer
- Información de contacto
- Enlaces útiles
- Redes sociales
- Copyright

## 🎨 Personalización

### Cambiar Colores

Edita las variables CSS en `styles.css`:

```css
:root {
    --primary-color: #2c5f2d;      /* Verde principal */
    --secondary-color: #87a96b;    /* Verde secundario */
    --accent-color: #d4a574;       /* Color de acento */
    --dark-color: #1a1a1a;         /* Texto oscuro */
    --light-color: #f5f5f5;        /* Fondo claro */
}
```

### Añadir Nuevos Destinos

En `script.js`, modifica las funciones de generación:

```javascript
function generarDia1(intereses) {
    // Añade tu contenido personalizado aquí
}
```

### Modificar Respuestas del Asistente

Edita las funciones en `script.js`:

```javascript
function generarRespuesta(analisis) {
    // Personaliza las respuestas del bot
}
```

## 📱 Responsive Design

### Breakpoints

- **Desktop**: > 768px (diseño completo)
- **Tablet**: 768px (adaptaciones menores)
- **Mobile**: < 480px (diseño optimizado)

### Adaptaciones Móviles

- Menú de navegación adaptativo
- Grid de una columna en tarjetas
- Tipografía escalada
- Botones de tamaño touch-friendly
- Chat optimizado para pantallas pequeñas

## 🌐 Navegadores Compatibles

- ✅ Google Chrome (90+)
- ✅ Mozilla Firefox (88+)
- ✅ Microsoft Edge (90+)
- ✅ Safari (14+)
- ✅ Opera (76+)

## 📸 Capturas de Pantalla

### Vista Desktop
- Hero con imagen de fondo completa
- Grid de 3 columnas en tarjetas
- Chat lateral amplio

### Vista Mobile
- Navegación compacta
- Tarjetas en columna única
- Chat a pantalla completa

## 🛠️ Desarrollo Futuro

### Mejoras Planificadas

- [ ] Integración con API real de alojamientos
- [ ] Sistema de reservas online
- [ ] Mapa interactivo de Soria
- [ ] Galería de fotos ampliada
- [ ] Blog de viajes y experiencias
- [ ] Sistema de valoraciones de usuarios
- [ ] Integración con Google Maps
- [ ] Modo oscuro / claro
- [ ] Multiidioma (inglés, francés)
- [ ] IA más avanzada con Machine Learning

### Integraciones Posibles

- **Backend**: Node.js + Express o Python + Flask
- **Base de Datos**: MongoDB o PostgreSQL
- **APIs**: 
  - Google Maps API
  - OpenWeather API
  - Booking.com API
- **IA Avanzada**: OpenAI GPT, Dialogflow

## 🤝 Contribución

Las contribuciones son bienvenidas. Para contribuir:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/NuevaCaracteristica`)
3. Commit tus cambios (`git commit -m 'Añadir nueva característica'`)
4. Push a la rama (`git push origin feature/NuevaCaracteristica`)
5. Abre un Pull Request

### Guías de Contribución

- Mantén el código limpio y comentado
- Sigue las convenciones de nomenclatura existentes
- Actualiza la documentación si es necesario
- Prueba en múltiples navegadores

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Consulta el archivo `LICENSE` para más detalles.

### Permisos

✅ Uso comercial
✅ Modificación
✅ Distribución
✅ Uso privado

## 👨‍💻 Autor

**Proyecto Rutas**

Red Unificada de Turistas, Alojamientos y Servicios - Plataforma desarrollada para promover el turismo en Soria con tecnologías web modernas e inteligencia artificial.

## 📞 Contacto

Para preguntas, sugerencias o colaboraciones:

- 📧 Email: olgamarin@rutasrurales.io
- 📱 Teléfono: +34 605 249 696
- 🌐 Web: rutasrurales.io

---

## 🙏 Agradecimientos

- **Imágenes**: Unsplash contributors
- **Iconos**: Font Awesome
- **Inspiración**: La belleza natural e histórica de Soria

---

## 📚 Documentación Adicional

### Recursos sobre Soria

- [Turismo Castilla y León](https://www.turismocastillayleon.com)
- [Soria Ni Te La Imaginas](https://www.soriaymas.com)
- [Patronato Provincial de Turismo](https://www.dipsoria.es/turismo)

### Referencias Técnicas

- [MDN Web Docs](https://developer.mozilla.org)
- [CSS Tricks](https://css-tricks.com)
- [JavaScript.info](https://javascript.info)

---

<div align="center">

### ⭐ Si te gusta este proyecto, ¡dale una estrella! ⭐

**Hecho con ❤️ por Rutas - Red Unificada de Turistas, Alojamientos y Servicios** 🌄

</div>

---

**Versión**: 1.0.0  
**Última actualización**: Noviembre 2025  
**Estado**: ✅ Funcional y listo para usar
