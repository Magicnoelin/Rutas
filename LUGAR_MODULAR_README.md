# 🌿 LUGAR-MODULAR — Sistema de páginas de lugares de interés

## Estado: ✅ EN PARALELO (producción intacta)

---

## ¿Qué hace este sistema?

Implementa las páginas de tipo `/lugar/almazan-pueblo` de forma **monolítica pero ultrarrápida**:

- **SSR (Server-Side Rendering)**: PHP renderiza el Hero, título, descripción, info práctica y contacto en el HTML inicial → Google lo ve todo sin JS
- **Skeleton Screens**: mientras cargan el mapa y el contenido cercano, se muestran placeholders animados
- **SEO máximo**: JSON-LD Schema.org (TouristAttraction + BreadcrumbList + WebPage), Open Graph, Twitter Cards, hreflang, canonical, meta robots optimizados
- **Velocidad**: CSS crítico inline, imagen hero con `<link rel="preload">`, Leaflet diferido, GTM diferido, contenido cercano via fetch asíncrono a los 1.5s
- **Inbound linking**: enlaza a alojamientos, actividades, eventos y otros lugares cercanos (red de links internos que Google rastrea)

---

## Arquitectura de ficheros

```
lugar-modular/
├── index.php           ← Página principal con SSR
├── .htaccess           ← RewriteEngine Off (sin interferencias)
├── api/
│   └── lugar-data.php  ← API con 3 modos: minimal, full, nearby
└── js/
    └── lugar.js        ← Galería, lightbox, mapa, contenido cercano
```

---

## URL de prueba (sin afectar a producción)

```
https://rutasrurales.io/lugar-preview/almazan-pueblo
https://rutasrurales.io/lugar-preview/ermita-de-san-saturio-soria
https://rutasrurales.io/lugar-preview/pico-urbion-duruelo-de-la-sierra
```

La URL canónica generada es `/lugar/{slug}` (correcta para SEO aunque se acceda via `/lugar-preview/`).

---

## Checklist de verificación antes del switch

- [ ] Probar 5-10 páginas vía `/lugar-preview/{slug}`
- [ ] Verificar con Google Rich Results Test el JSON-LD
- [ ] Comprobar Lighthouse Performance > 90 en móvil
- [ ] Verificar que el mapa carga al hacer scroll
- [ ] Verificar que el contenido cercano aparece con skeleton y luego cards reales
- [ ] Probar redirecciones 301 (ej: `/lugar-preview/ermita-de-san-saturio` → `/lugar/ermita-de-san-saturio-soria`)
- [ ] Verificar la galería y lightbox con swipe táctil
- [ ] Probar botón favoritos y compartir

---

## ✅ SWITCH A PRODUCCIÓN

Cuando todo esté verificado, **UN SOLO CAMBIO en `.htaccess`** (línea ~131):

**Cambiar:**
```apache
RewriteRule ^lugar/([^/]+)/?$ redirect_manager.php?type=lugar&slug=$1 [L,QSA]
```

**Por:**
```apache
RewriteRule ^lugar/([^/]+)/?$ lugar-modular/index.php?slug=$1 [L,QSA]
```

Y opcionalmente eliminar la ruta de preview:
```apache
# RewriteRule ^lugar-preview/([^/]+)/?$ lugar-modular/index.php?slug=$1 [L,QSA]
```

---

## Secciones de la página (para SEO turístico)

| Sección | Carga | SEO relevante |
|---------|-------|---------------|
| **Hero** (foto + título + badge + meta) | SSR inmediato | H1, breadcrumb |
| **Galería** con lightbox | SSR + JS | ImageObject Schema |
| **Descripción** expandible | SSR | Texto para Google |
| **Información práctica** (horario, precio, duración, temporada, accesibilidad) | SSR | TouristAttraction properties |
| **Contacto** (tel, WhatsApp, email, web, dirección) | SSR | telephone, email Schema |
| **Mapa Leaflet** interactivo | Diferida (IntersectionObserver) | GeoCoordinates Schema |
| **🏠 Dónde dormir cerca** (alojamientos) | Skeleton → fetch 1.5s | Inbound linking |
| **🎯 Actividades cercanas** | Skeleton → fetch 1.5s | Inbound linking |
| **🎭 Eventos próximos** | Skeleton → fetch 1.5s | Inbound linking |
| **🏛️ Otros lugares cerca** | Skeleton → fetch 1.5s | Inbound linking |
| **Sidebar**: info rápida + CTA registro + compartir | SSR | UX + conversión |

---

## API endpoints disponibles

```
GET /lugar-modular/api/lugar-data.php?slug={slug}&mode=full
GET /lugar-modular/api/lugar-data.php?slug={slug}&mode=minimal
GET /lugar-modular/api/lugar-data.php?mode=nearby&slug={slug}&lat={lat}&lng={lng}&radius=50&prov={prov}
```

---

## Inbound linking — red de enlaces internos

```
/lugar/{slug}
    → /alojamiento/{slug}   (alojamientos cercanos en BBDD)
    → /actividad/{slug}     (actividades cercanas en BBDD)
    → /evento/{slug}        (eventos futuros cercanos en BBDD)
    → /lugar/{slug}         (otros lugares cercanos en BBDD)

/alojamiento/{slug}         (alojamiento-modular, ya existente)
    → /lugar/{slug}         (ya enlazaba a lugares cercanos)
```

---

_Generado: Mayo 2026_
