# Instrucciones: Sitemap de Eventos Corregido

## ✅ Problemas Solucionados

1. **Lógica de fechas corregida**: El sitemap ahora solo incluye eventos futuros/actuales, no eventos pasados.
2. **Traducciones incluidas**: Se incluyen correctamente todas las traducciones de la tabla `cultural_events_trads`.

## 🔧 Archivos Modificados

- `sitemap-eventos.php` - Sitemap dinámico principal (ya corregido)
- `sitemap-eventos-idiomas.php` - Sitemap dinámico de traducciones (ya corregido)
- `generar-sitemap.php` - Generador de sitemaps (actualizado con lógica corregida)
- `admin_tablas/cron/regenerar_sitemap_i18n.php` - Script de cron (actualizado)

## 🚀 Ejecutar Regeneración Manual (PARA GOOGLE)

Para regenerar el sitemap inmediatamente y pasar la versión corregida a Google:

### Opción 1: Acceder desde navegador
1. Visitar: `https://rutasrurales.io/ejecutar_regeneracion_sitemap.php`
2. El script regenerará automáticamente `sitemap-eventos-i18n.xml`
3. Actualizará la fecha en `sitemap.xml`

### Opción 2: Si no funciona la Opción 1
1. Visitar: `https://rutasrurales.io/sitemap-eventos.php` (ya está corregido)
2. Visitar: `https://rutasrurales.io/sitemap-eventos-idiomas.php` (ya está corregido)

## 📋 Pasos para Google Search Console

1. **Acceder a Google Search Console**: https://search.google.com/search-console
2. **Seleccionar propiedad**: "rutasrurales.io"
3. **Ir a "Sitemaps"** en el menú lateral
4. **Eliminar sitemaps con errores** (si los hay)
5. **Agregar nuevos sitemaps**:
   - `https://rutasrurales.io/sitemap-eventos.php` (PRINCIPAL)
   - `https://rutasrurales.io/sitemap-eventos-i18n.xml` (TRADUCCIONES)

## 🔄 Cron Automático

El sistema ya tiene configurado un cron que se ejecuta automáticamente:
- Archivo: `admin_tablas/cron/regenerar_sitemap_i18n.php`
- Genera: `sitemap-eventos-i18n.xml` (estático)
- Actualiza: `sitemap.xml` (incluye `sitemap-eventos-i18n.xml` si no existe y actualiza fechas)

## 📊 Verificación

Para verificar que todo funciona:

1. **Ver sitemap dinámico**: https://rutasrurales.io/sitemap-eventos.php
2. **Ver sitemap de traducciones**: https://rutasrurales.io/sitemap-eventos-i18n.xml
3. **Ver índice principal**: https://rutasrurales.io/sitemap.xml

## 🎯 Resumen de Cambios Técnicos

### Lógica ANTIGUA (incorrecta):
```sql
AND COALESCE(e.end_date, DATE_ADD(e.start_date, INTERVAL 1 DAY)) >= CURDATE()
```

### Lógica NUEVA (corregida):
```sql
AND (
  (end_date IS NULL AND start_date >= CURDATE()) OR
  (end_date IS NOT NULL AND end_date >= CURDATE())
)
```

**Explicación**:
- Eventos **sin fecha de fin**: se incluyen si empiezan hoy o después
- Eventos **con fecha de fin**: se incluyen si terminan hoy o después

## 📞 Soporte

Si hay problemas:
1. Verificar que los archivos PHP tengan permisos de ejecución
2. Verificar conexión a base de datos
3. Ejecutar manualmente el script de regeneración

---

**Fecha de corrección**: 4 de abril de 2026  
**Estado**: ✅ COMPLETADO - Sitemap corregido y listo para Google