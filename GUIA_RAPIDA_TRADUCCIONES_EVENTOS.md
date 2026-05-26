# GUÍA RÁPIDA: Traducciones de Eventos Culturales

## 🎯 Objetivo
Completar las traducciones que faltan en la tabla `cultural_events_trads` para eventos activos con fecha posterior al 1 abril 2026.

## 📊 Situación Actual
- **109 eventos** activos en español
- **~53-54 traducciones** por idioma (deberían ser 109)
- **Faltan ~224 traducciones** (inglés, francés, alemán, chino)

## 🚀 Cómo Ejecutar (2 opciones)

### Opción 1: Script automático (recomendada)
Ejecuta el archivo **`completar_traducciones_eventos_final.sql`** en phpMyAdmin.
- Genera traducciones genéricas para todos los eventos que falten
- Slugs SEO: `slug-traditional-festival-spain`, `slug-fete-traditionnelle-espagne`, etc.
- No sobrescribe traducciones existentes

### Opción 2: Traducciones personalizadas (como evento 2425)
Si quieres contenido SEO curado para eventos específicos:
1. Identifica los IDs de eventos que te faltan con esta query:
   ```sql
   SELECT ce.id, ce.name, GROUP_CONCAT(cet.language_code) as idiomas
   FROM cultural_events ce
   LEFT JOIN cultural_events_trads cet ON ce.id = cet.event_id
   WHERE ce.is_active = 1 AND (ce.start_date >= '2026-04-01' OR ce.end_date >= '2026-04-01')
   GROUP BY ce.id
   ORDER BY ce.start_date;
   ```
2. Crea un INSERT como el de `insertar_traducciones_evento_2425.sql` para cada evento

## 📁 Archivos Relacionados
| Archivo | Qué hace |
|---------|----------|
| `completar_traducciones_eventos_final.sql` | Script principal: inserta y actualiza traducciones masivamente |
| `insertar_traducciones_evento_2425.sql` | Ejemplo de traducción personalizada para un evento |
| `actualizar_traducciones_eventos.sql` | Versión corregida del script automático |

## ✅ Verificación post-ejecución
```sql
SELECT cet.event_id, ce.name, cet.language_code, cet.name as nombre_traducido
FROM cultural_events_trads cet
JOIN cultural_events ce ON cet.event_id = ce.id
WHERE ce.is_active = 1
ORDER BY cet.event_id, cet.language_code;
```

## ⚠️ Notas
- La tabla se llama `cultural_events_trads` (sin guión bajo entre "events" y "trads")
- No tiene columnas `created_at` ni `updated_at`
- Idiomas: `en` (inglés), `fr` (francés), `de` (alemán), `zh` (chino)
