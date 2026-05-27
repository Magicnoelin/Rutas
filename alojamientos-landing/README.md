# 🏡 Sistema de Landings SEO Long-Tail — rutasrurales.io

> **Estado:** ✅ Implementado · **Versión:** 1.0 · **Fecha:** Mayo 2026

Sistema modular para generar dinámicamente páginas de aterrizaje ultra-específicas de alojamientos rurales, diseñadas para capturar tráfico long-tail y **superar a Booking, Airbnb y Tripadvisor** en búsquedas de nicho.

---

## 📁 Estructura de Archivos

```
alojamientos-landing/
├── index.php                    ← Orquestador principal (entry point)
├── README.md                    ← Esta documentación
│
├── config/
│   └── filters.php              ← Catálogo de filtros + provincias + parseLandingSlug()
│
├── i18n/
│   └── translations.php         ← Textos en 5 idiomas + funciones t() y getLandingTranslations()
│
├── api/
│   └── landing-data.php         ← Capa de datos: queries PDO a accommodations, places, routes, events
│
├── modules/
│   ├── schema.php               ← JSON-LD: CollectionPage + BreadcrumbList + ItemList/LodgingBusiness
│   ├── hero.php                 ← Hero: breadcrumb, H1, stats en tiempo real, badges
│   ├── intro.php                ← Bloque SEO introductorio con texto dinámico por provincia
│   ├── listing.php              ← Grid de tarjetas + paginación accesible
│   └── cruce-semantico.php      ← 🚀 Lugares de interés + Rutas + Eventos de la provincia
│
└── css/
    └── landing.css              ← CSS completo (carga asíncrona, no bloqueante)
```

**Reglas de enrutamiento:** añadidas en `/.htaccess` (sección 4c)

---

## 🌐 URLs Generadas

### Español (sin prefijo)
```
/alojamientos/casas-rurales-con-chimenea-soria
/alojamientos/turismo-rural-mascotas-zamora
/alojamientos/alojamiento-rural-con-piscina-burgos
/alojamientos/turismo-rural-ninos-segovia
/alojamientos/casas-rurales-con-jacuzzi-leon
/alojamientos/casas-rurales-wifi-avila
```

### Multiidioma
```
/en/alojamientos/casas-rurales-con-chimenea-soria
/fr/alojamientos/casas-rurales-con-chimenea-soria
/de/alojamientos/casas-rurales-con-chimenea-soria
/zh/alojamientos/casas-rurales-con-chimenea-soria
```

### Regla htaccess (sección 4c)
```apache
# Landings: slugs con ≥ 4 tokens separados por guión
RewriteRule ^alojamientos/([a-z][a-z0-9]*(?:-[a-z0-9]+){3,})/?$
    alojamientos-landing/index.php?lang=es&slug=$1 [L,QSA]
```
> **Por qué 4 tokens mínimo:** Los slugs de alojamientos individuales suelen tener 2-3 tokens (`viriato-1-zamora`). Las landings son siempre compuestas (`turismo-rural-mascotas-zamora` = 4 tokens).

---

## ⚙️ Cómo Añadir Nuevos Filtros

Edita `config/filters.php`, sección `LANDING_FILTROS`:

```php
'con-terraza' => [
    'icon'   => '🌅',
    'sql'    => "JSON_CONTAINS(a.amenities, '\"terraza\"')",
    'labels' => [
        'es' => 'Casas rurales con terraza',
        'en' => 'Rural houses with terrace',
        'fr' => 'Maisons rurales avec terrasse',
        'de' => 'Landhäuser mit Terrasse',
        'zh' => '带露台乡村民宿',
    ],
],
```

Luego añade el slug a los arrays `FILTER_SLUGS_ES` y `FILTER_SLUGS_LANG`.

**URL resultante automática:** `/alojamientos/casas-rurales-con-terraza-{provincia}`

---

## ⚙️ Cómo Añadir Nuevas Provincias

Edita `config/filters.php`, sección `LANDING_PROVINCIAS`:

```php
'salamanca' => [
    'db'    => 'Salamanca',
    'label' => 'Salamanca',
    'vibe'  => [
        'es' => 'Salamanca combina patrimonio universitario medieval con una sierra llena de naturaleza',
        'en' => 'Salamanca blends medieval university heritage with a nature-filled mountain range',
        'fr' => 'Salamanque allie patrimoine universitaire médiéval et nature en montagne',
        'de' => 'Salamanca verbindet mittelalterliches Universitätserbe mit naturreicher Berglandschaft',
        'zh' => '萨拉曼卡将中世纪大学遗产与自然山脉融为一体',
    ],
    'attractions' => [
        'Plaza Mayor de Salamanca',
        'Sierra de Francia',
        'Las Batuecas',
        'Catedral de Salamanca',
    ],
],
```

---

## 🔑 Cómo Funciona `parseLandingSlug()`

El slug `casas-rurales-con-chimenea-soria` se analiza así:

1. **Busca provincia al final**: compara el último token (`soria`) contra `LANDING_PROVINCIAS`
2. **Si encuentra provincia**: toma el resto como filtros (`casas-rurales-con-chimenea`)
3. **Busca filtros**: escanea los tokens restantes contra `LANDING_FILTROS` (busca slugs de filtro como `con-chimenea`, `casas-rurales`)
4. **Devuelve** `['valid' => true, 'province' => 'soria', 'filters' => ['casas-rurales', 'con-chimenea']]`
5. **Si no encuentra**: `valid = false` → el `index.php` lanza 301 a `/alojamiento/{slug}` (compatibilidad)

---

## 🗄️ Base de Datos — Tablas Utilizadas

| Tabla | Uso |
|-------|-----|
| `accommodations` | Listado principal: `is_active`, `province`, `amenities`, `pet_friendly`, `wifi`, `price_per_night` |
| `categories_accommodations` | Tipo de alojamiento para badge y Schema |
| `places_of_interest` | Cruce semántico: lugares de la provincia |
| `routes` | Cruce semántico: rutas temáticas |
| `cultural_events` | Cruce semántico: próximos eventos |

### Query principal (simplificada)
```sql
SELECT a.*, c.name AS category_name
FROM accommodations a
LEFT JOIN categories_accommodations c ON a.category_id = c.id
WHERE a.is_active = 1
  AND a.province = :province
  AND {condiciones_de_filtros}   -- hardcoded desde LANDING_FILTROS['sql']
ORDER BY
  CASE WHEN a.price_per_night > 0 THEN 0 ELSE 1 END ASC,
  a.price_per_night ASC,
  a.name ASC
LIMIT 12 OFFSET {offset}
```

> **Seguridad:** Las condiciones SQL de filtros son constantes PHP (no user input). Solo `:province` lleva parámetro preparado.

---

## 🌍 Sistema Multiidioma — hreflang

### Implementación

```html
<!-- En el <head> de cada landing -->
<link rel="alternate" hreflang="es"        href="https://rutasrurales.io/alojamientos/{slug}">
<link rel="alternate" hreflang="en"        href="https://rutasrurales.io/en/alojamientos/{slug}">
<link rel="alternate" hreflang="fr"        href="https://rutasrurales.io/fr/alojamientos/{slug}">
<link rel="alternate" hreflang="de"        href="https://rutasrurales.io/de/alojamientos/{slug}">
<link rel="alternate" hreflang="zh"        href="https://rutasrurales.io/zh/alojamientos/{slug}">
<link rel="alternate" hreflang="x-default" href="https://rutasrurales.io/alojamientos/{slug}">
```

### Estrategia de slug
El **mismo slug en todos los idiomas** (basado en el nombre español de filtros/provincia). Esto es intencional:
- ✅ Evita duplicar la lógica de mapeo de slugs
- ✅ Los filtros/provincias son nombres propios en español (Soria, chimenea)
- ✅ El contenido se traduce completamente (H1, meta, textos, schema)
- ⚠️ Para mercados de habla alemana/francesa, considera crear slugs nativos como ampliación futura

### Selector de idioma en footer
El footer incluye flags + enlaces hreflang para el usuario, consolidando señales de idioma.

---

## ⚡ Core Web Vitals — Decisiones de Rendimiento

### LCP (Largest Contentful Paint)
- **H1** en el hero es texto → LCP candidato sin dependencia de imagen
- **Primera imagen** de tarjeta: `loading="eager"` + `<link rel="preload">` en `<head>`
- **CSS crítico inline** (above the fold): navbar + hero + grid placeholder → pintura visible sin esperar CSS externo
- **CSS no crítico** cargado asíncronamente con el truco `media="print" → onload="this.media='all'"`

### CLS (Cumulative Layout Shift)
- Todas las imágenes tienen `width` y `height` explícitos (600×400 tarjetas, 320×200 semántico)
- Uso de `aspect-ratio: 3/2` y `aspect-ratio: 16/10` en CSS para reservar espacio
- Fuentes con `font-display: swap` y declaradas antes del CSS no crítico
- `contain: layout style` en secciones → aisla reflows

### INP (Interaction to Next Paint)
- **Cero JavaScript** en el renderizado principal (SSR puro PHP)
- GTM cargado mediante técnica de lazy loading basada en interacción del usuario
- Transiciones CSS con `contain: layout style` para reducir scope de repintado

---

## 🧪 Cómo Probar Localmente

```bash
# Simular URL de landing pasando parámetros directamente
php -S localhost:8080

# Probar desde el navegador:
http://localhost:8080/alojamientos-landing/index.php?slug=casas-rurales-con-chimenea-soria&lang=es

# Con idioma inglés:
http://localhost:8080/alojamientos-landing/index.php?slug=casas-rurales-con-chimenea-soria&lang=en
```

### Verificar Schema.org
1. Copia el JSON-LD generado en el `<head>`
2. Pégalo en [schema.org/validator](https://validator.schema.org/)
3. Verifica: `CollectionPage` + `BreadcrumbList` + `ItemList` con `LodgingBusiness`

### Verificar hreflang
1. Usa [hreflang.org/checker](https://www.hreflang.org/checker/)
2. Introduce la URL de una landing
3. Verifica que los 5 idiomas + x-default aparecen y se enlazan entre sí

---

## 🔌 Ampliar el Sistema — Casos de Uso Futuros

### 1. Sitemap dinámico de landings
```php
// Genera URLs de sitemap a partir del producto cartesiano filtros × provincias
foreach (LANDING_FILTROS as $filter => $data) {
    foreach (LANDING_PROVINCIAS as $prov => $pdata) {
        echo "https://rutasrurales.io/alojamientos/{$filter}-{$prov}\n";
    }
}
// ~400 URLs automáticas desde 8 filtros × 50 provincias
```

### 2. Caché de páginas (OPcache + APCu)
```php
// Al inicio de index.php, antes de las queries
$cache_key = 'landing_' . $slug . '_' . $lang . '_p' . $page;
if ($cached = apcu_fetch($cache_key)) {
    echo $cached; exit;
}
ob_start();
// ... renderizado normal ...
$html = ob_get_clean();
apcu_store($cache_key, $html, 3600); // TTL 1h
echo $html;
```

### 3. Landings combinadas (múltiples filtros)
El parser `parseLandingSlug()` ya soporta múltiples filtros:
```
/alojamientos/casas-rurales-con-chimenea-mascotas-soria
→ filters: ['casas-rurales', 'con-chimenea', 'con-mascotas'], province: 'soria'
```

### 4. A/B Testing de H1
```php
// En index.php, variar el H1 para GSC click-through experiments
$h1_variants = [
    "Casas rurales con chimenea en Soria — Tu refugio en la naturaleza",
    "Casas rurales con chimenea en Soria — $stats[total] opciones verificadas",
];
$h1 = $h1_variants[crc32($slug) % count($h1_variants)];
```

---

## ✅ Checklist de Despliegue

- [ ] Subir `alojamientos-landing/` al servidor
- [ ] Verificar que `api/config.php` está accesible desde `alojamientos-landing/index.php`
- [ ] Confirmar que las tablas `places_of_interest` y `routes` existen en producción
- [ ] Subir `.htaccess` actualizado con sección 4c
- [ ] Testar URL: `https://rutasrurales.io/alojamientos/casas-rurales-con-chimenea-soria`
- [ ] Validar Schema.org en Rich Results Test de Google
- [ ] Verificar hreflang en Search Console → Internacionalización
- [ ] Añadir landings al sitemap XML
- [ ] Revisar en PageSpeed Insights (objetivo: LCP < 2.5s, CLS < 0.1)

---

## 📊 Diagrama de Flujo

```
URL: /alojamientos/casas-rurales-con-chimenea-soria
        │
        ▼
  .htaccess: sección 4c
  Regex: ≥4 tokens con guión ✓
        │
        ▼
  alojamientos-landing/index.php
  ├── $slug = "casas-rurales-con-chimenea-soria"
  ├── parseLandingSlug() →  { province: 'soria', filters: ['casas-rurales', 'con-chimenea'] }
  ├── getLandingTranslations('es')
  ├── getLandingAccommodations($pdo, 'Soria', ["a.amenities LIKE '%chimenea%'"], page=1)
  ├── getLandingStats()
  ├── getSemanticCrossing($pdo, 'Soria')  ← diferenciador vs Booking
  └── getUpcomingEvents($pdo, 'Soria')
        │
        ▼
  HTML renderizado SSR:
  ├── <head> CSS crítico inline + JSON-LD + hreflang + preload LCP
  ├── <header> Navbar sticky
  ├── <main>
  │   ├── renderLandingHero()      → H1, stats, breadcrumb, badges
  │   ├── renderLandingIntro()     → texto SEO dinámico, vibe de Soria, atractivos
  │   ├── renderLandingListing()   → grid 12 tarjetas + paginación
  │   └── renderCruceSemantico()   → lugares interés + rutas + eventos SORIA
  └── <footer> links + selector idioma
```

---

*Desarrollado para [rutasrurales.io](https://rutasrurales.io) — Turismo rural auténtico de Castilla y León*
