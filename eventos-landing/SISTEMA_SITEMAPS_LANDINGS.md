# Sistema de Sitemaps de Landings Long-Tail

## 📋 ¿Qué son los sitemaps de landings?

Son archivos XML que listan todas las URLs de las páginas de aterrizaje SEO (long-tail) de:
- **Alojamientos**: `/alojamientos/{filtro}-{provincia}` (ej: `/alojamientos/casas-rurales-soria`)
- **Eventos**: `/eventos/{filtro}-{provincia}` (ej: `/eventos/ferias-soria`)

Cada URL se genera en **5 idiomas** (es, en, fr, de, zh) con hreflang completo.

## 🏗️ Arquitectura (NO hay tabla `seo_landings`)

**No existe una tabla `seo_landings` en la base de datos.** Los sitemaps de landings se generan **dinámicamente** consultando las tablas de contenido real:

```
┌─────────────────────────────────────────────────────────────┐
│                    actualizar-sitemap.php                    │
│                   (ejecutado por el CRON)                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. Genera sitemap-estatico.xml                             │
│  2. Genera sitemap-alojamientos.xml  ← desde accommodations │
│  3. Genera sitemap-lugares.xml       ← desde places_of_int. │
│  4. Genera sitemap-actividades.xml   ← desde tourist_act.   │
│  5. Genera sitemap-eventos.xml       ← desde cultural_events│
│  6. Genera sitemap-rutas.xml         ← desde routes         │
│  7. Genera sitemap-eventos-i18n.xml  ← desde cultural_event.│
│                                                             │
│  ─── NUEVO ───                                              │
│  8. Ejecuta regenerar_sitemap_landings.php                  │
│     → Consulta accommodations + LANDING_FILTROS             │
│     → Genera sitemap-landings.xml                           │
│                                                             │
│  9. Ejecuta regenerar_sitemap_eventos_landing.php           │
│     → Consulta cultural_events + EVENTOS_FILTROS            │
│     → Genera sitemap-eventos-landing.xml                    │
│                                                             │
│  10. Construye sitemap.xml (índice maestro)                 │
│      CON TODOS los archivos, incluyendo landings            │
└─────────────────────────────────────────────────────────────┘
```

## 🔍 ¿Cómo se genera cada landing URL?

### Alojamientos (`regenerar_sitemap_landings.php`)

1. Toma todas las combinaciones de `LANDING_FILTROS × LANDING_PROVINCIAS` definidas en `alojamientos-landing/config/filters.php`
2. Para cada combinación, ejecuta una consulta SQL:
   ```sql
   SELECT 1 FROM accommodations a
   WHERE a.is_active = 1
     AND a.province = :provincia
     AND ({condición_del_filtro})
   LIMIT 1
   ```
3. Si hay **al menos 1 resultado**, genera la URL en 5 idiomas con hreflang
4. Si no hay resultados, **omite** esa combinación (no genera URL vacía)

### Eventos (`regenerar_sitemap_eventos_landing.php`)

1. Toma todas las combinaciones de `EVENTOS_FILTROS × EVENTOS_PROVINCIAS` definidas en `eventos-landing/config/filters.php`
2. Para cada combinación, ejecuta:
   ```sql
   SELECT 1 FROM cultural_events e
   WHERE e.is_active = 1
     AND e.moderation_status = 'approved'
     AND COALESCE(e.end_date, e.start_date) >= CURDATE()
     AND e.province = :provincia
     AND ({condición_del_filtro})
   LIMIT 1
   ```
3. Si hay **al menos 1 evento activo y futuro**, genera la URL en 5 idiomas

## 📁 Archivos del sistema

| Archivo | Función |
|---------|---------|
| `actualizar-sitemap.php` | **Script del CRON de Hostinger**. Genera todo |
| `regenerar_sitemap_landings.php` | Genera `sitemap-landings.xml` |
| `regenerar_sitemap_eventos_landing.php` | Genera `sitemap-eventos-landing.xml` |
| `alojamientos-landing/config/filters.php` | Define provincias y filtros de alojamientos |
| `eventos-landing/config/filters.php` | Define provincias y filtros de eventos |
| `sitemap-landings.php` | Sirve `sitemap-landings.xml` dinámicamente (legacy) |
| `sitemap-eventos-landing.php` | Sirve `sitemap-eventos-landing.xml` dinámicamente (legacy) |

## ⚙️ Configuración del CRON en Hostinger

El cron ejecuta:
```
php /ruta/a/actualizar-sitemap.php
```

**Frecuencia recomendada:** diaria (una vez al día).

## 🆕 Novedades (fix 2026-07-08)

Antes, `actualizar-sitemap.php` **no ejecutaba** los regeneradores de landings, por lo que `sitemap.xml` (índice maestro) se regeneraba **sin** las entradas de landings. Cada vez que el cron se ejecutaba, las landings desaparecían del sitemap.

**Solución:** Ahora `actualizar-sitemap.php`:
1. Ejecuta `regenerar_sitemap_landings.php` → genera `sitemap-landings.xml`
2. Ejecuta `regenerar_sitemap_eventos_landing.php` → genera `sitemap-eventos-landing.xml`
3. Incluye ambos archivos en el índice maestro `sitemap.xml`

Además, los regeneradores detectan cuando son llamados desde `actualizar-sitemap.php` (mediante la constante `SKIP_SITEMAP_INDEX_UPDATE`) y **no modifican** `sitemap.xml` para evitar conflictos de escritura.

## ✅ Verificar que funciona

1. Ejecuta manualmente: `https://rutasrurales.io/actualizar-sitemap.php`
2. Comprueba que `sitemap.xml` incluya:
   ```xml
   <sitemap>
     <loc>https://rutasrurales.io/sitemap-landings.xml</loc>
     <lastmod>2026-07-08</lastmod>
   </sitemap>
   <sitemap>
     <loc>https://rutasrurales.io/sitemap-eventos-landing.xml</loc>
     <lastmod>2026-07-08</lastmod>
   </sitemap>
   ```
3. Comprueba que `sitemap-landings.xml` tenga URLs (abrir en navegador)
4. Comprueba que `sitemap-eventos-landing.xml` tenga URLs
