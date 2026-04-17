# 🗺️ Sistema de Rutas Temáticas — rutasrurales.io

Plantilla modular y reutilizable para páginas de rutas turísticas.
Diseñada para el puente del 1 de mayo en Soria, pero funciona para **cualquier ruta**.

## 📁 Estructura

```
rutas-tematicas/
├── index.php              ← Plantilla universal (recibe ?slug=)
├── .htaccess              ← URL limpia: /rutas-tematicas/{slug}
├── README.md              ← Este archivo
├── api/
│   └── ruta-slug.php      ← API REST: devuelve ruta completa con JOINs
├── modules/
│   ├── hero.php           ← Hero con imagen, cuenta atrás, badges, CTAs
│   ├── itinerario.php     ← Timeline día a día con items agrupados
│   ├── alojamientos.php   ← Cards de alojamientos con precio y reserva
│   ├── lugares.php        ← Cards de lugares de interés
│   ├── actividades.php    ← Cards de actividades turísticas
│   ├── eventos.php        ← Cards de eventos culturales del período
│   ├── faq.php            ← FAQ dinámica + bloque SEO de texto
│   └── schema.php         ← JSON-LD: TouristTrip, ItemList, FAQ, Breadcrumb
├── css/
│   └── ruta.css           ← Estilos con prefijo rt- (sin colisiones)
└── sql/
    └── setup.sql          ← ALTER TABLE + INSERT ruta puente mayo
```

## 🚀 URL de acceso

```
https://rutasrurales.io/rutas/puente-1-mayo-soria
```

La regla en `.htaccess` raíz mapea `/rutas/{slug}` → `rutas-tematicas/index.php?slug={slug}`

## 🗄️ Base de datos

### Tablas usadas
- `routes` — Datos de la ruta (título, SEO, itinerario JSON, hero image...)
- `route_items` — Items de la ruta (tipo + ID + metadatos editoriales)
- `accommodations` — Alojamientos (JOIN por item_id)
- `places_of_interest` — Lugares de interés (JOIN por item_id)
- `tourist_activities` — Actividades (JOIN por item_id)
- `cultural_events` — Eventos (carga dinámica por fechas del itinerario)

### Ejecutar SQL
```sql
-- En phpMyAdmin o cliente MySQL:
SOURCE rutas-tematicas/sql/setup.sql;
```

⚠️ **Ajusta los `item_id`** en el INSERT de `route_items` a los IDs reales de tu BD.

## ✅ SEO implementado

- **Meta title/description** personalizados por ruta
- **Canonical URL** dinámica
- **Open Graph** + **Twitter Card**
- **Schema.org JSON-LD**:
  - `TouristTrip` con itinerario
  - `BreadcrumbList`
  - `FAQPage` (7 preguntas dinámicas)
  - `ItemList` de alojamientos
  - `ItemList` de eventos
- **CSS crítico inline** (evita render-blocking)
- **CSS principal no bloqueante** (media="print" trick)
- **GTM diferido** (carga tras interacción del usuario)
- **Imágenes lazy** con `loading="lazy"` + `decoding="async"`
- **Cuenta atrás** en el hero (urgencia)
- **Texto SEO enriquecido** en sección FAQ

## 🔄 Crear una nueva ruta

1. Insertar en `routes` con un nuevo `slug`
2. Insertar en `route_items` los items vinculados
3. La URL `/rutas/{slug}` funciona automáticamente

## 📝 Tipos de ruta soportados

| `route_type` | Descripción |
|---|---|
| `temporal` | Puentes, festivos, eventos puntuales |
| `tematica` | Castillos, románico, vinos, setas... |
| `provincial` | Rutas por provincia completa |
| `gastronomica` | Rutas gastronómicas |
