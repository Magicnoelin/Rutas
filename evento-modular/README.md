# Evento Modular - Página de Detalle de Eventos

Versión optimizada y modular de la página de detalle de eventos.

## 📁 Estructura

```
evento-modular/
├── .htaccess              → Reglas de reescritura de URLs
├── index.php              → Página principal (SSR + CSS crítico inline)
├── api/
│   └── evento-data.php    → API optimizada (minimal/full/nearby)
└── js/
    └── evento-modular.js  → Módulos JS: Lightbox, Mapa, Nearby, Engagement
```

## 🚀 URLs de Prueba

- **Español:** `https://rutasrurales.io/evento-modular/{slug}`
- **Inglés:** `https://rutasrurales.io/evento-modular/en/{slug}`
- **Ejemplo:** `https://rutasrurales.io/evento-modular/pedrea-pan-quesillo-palencia-2026-santo-toribio`

## ⚡ Optimizaciones de Velocidad

1. **CSS crítico inline** - Todo el CSS en el `<head>`, sin archivos externos bloqueantes
2. **JS diferido** - `defer` en el script principal, carga después del HTML
3. **Mapa Leaflet** - Carga solo al hacer clic o al hacer scroll cerca del mapa
4. **Contenido cercano** - Carga diferida via API (IntersectionObserver)
5. **Imágenes lazy** - `loading="lazy"` en todas excepto la primera
6. **GTM diferido** - Solo carga después de interacción del usuario (8s máx)
7. **Fuentes locales** - Montserrat desde servidor, sin Google Fonts
8. **Font Awesome diferido** - `media="print"` trick para carga no bloqueante

## 🗺️ Mapa Interactivo (Leaflet)

- **Carga diferida**: Solo cuando el usuario hace clic o hace scroll al mapa
- **Marcador del evento**: Icono personalizado con emoji 🎭
- **Toggles**: Activar/desactivar capas de alojamientos 🏠 y lugares 🏛️
- **Puntos iniciales**: Solo el evento visible al cargar
- **Puntos cercanos**: Se añaden al activar el toggle correspondiente

## 📍 Contenido Cercano

- **3 alojamientos** visibles por defecto + botón "Ver más"
- **3 lugares de interés** visibles por defecto + botón "Ver más"
- **6 eventos similares** (misma categoría o provincia)
- Carga via API cuando el usuario hace scroll al 60% de la página

## 🎯 Elementos de Enganche

1. **CTA Registro** (sidebar) - Solo visible para usuarios no logueados
2. **Guardar evento** - Persiste en localStorage, toggle on/off
3. **Añadir a ruta** - Requiere login, guarda en localStorage
4. **Compartir** - WhatsApp, Twitter, Facebook, copiar enlace
5. **Suscripción** - Email para alertas de eventos similares

## 🔄 Migración a Producción

Cuando la nueva versión esté validada:

### Opción A: Reemplazar archivo (recomendada)
```bash
# 1. Hacer backup del archivo actual
cp evento-detalle.php evento-detalle.php.backup-modular

# 2. Copiar nueva versión
cp evento-modular/index.php evento-detalle-new.php

# 3. Actualizar require_once en evento-detalle-new.php:
#    Cambiar: require_once '../api/config.php';
#    Por:     require_once 'api/config.php';

# 4. Renombrar
mv evento-detalle.php evento-detalle-legacy.php
mv evento-detalle-new.php evento-detalle.php
```

### Opción B: Actualizar .htaccess raíz
Cambiar la regla de reescritura para que `/evento/{slug}` apunte a `evento-modular/index.php`.

## 📊 API Endpoints

### Datos del evento
```
GET /evento-modular/api/evento-data.php?slug={slug}&lang={lang}&mode={minimal|full|nearby}
```

**Modos:**
- `minimal` → Solo datos críticos (título, fechas, ubicación) - para carga inicial rápida
- `full` → Datos completos del evento (por defecto)
- `nearby` → Alojamientos, lugares y eventos similares cercanos

**Parámetros adicionales para `nearby`:**
- `lat` → Latitud del evento
- `lng` → Longitud del evento
- `prov` → Provincia (fallback si no hay coordenadas)

## 🐛 Notas Técnicas

- Los errores de Pylance en `.htaccess` son **falsos positivos** (Pylance no reconoce sintaxis Apache)
- El `header.php` existente se incluye automáticamente si existe en la ruta padre
- Si `header.php` no está disponible, se usa un header ligero de fallback
- La API de suscripción (`/api/subscribe-events.php`) es opcional; si no existe, guarda en localStorage
