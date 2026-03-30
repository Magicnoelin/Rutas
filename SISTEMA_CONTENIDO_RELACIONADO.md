Contenido Relacionado en Alojamientos

## 📋 Descripción

Se ha implementado un sistema que muestra contenido relacionado en la página de detalle de cada alojamiento, similar al que existe en la página de eventos culturales. Esto mejora la experiencia del usuario mostrando lugares de interés, actividades turísticas y eventos culturales de la zona.

## ✨ Características

### 1. **API Endpoint: `get_nearby_content.php`**
- **Ubicación**: `/api/get_nearby_content.php`
- **Función**: Obtiene contenido relacionado basándose en la ubicación del alojamiento
- **Parámetros**:
  - `accommodation_id`: ID del alojamiento
  - `municipality`: Localidad (alternativo)
  - `province`: Provincia (alternativo)

### 2. **Contenido Mostrado**

#### 🗺️ Lugares de Interés
- Muestra hasta 6 lugares de interés de la misma localidad/provincia
- Prioriza lugares de la misma localidad
- Incluye: nombre, descripción, foto, horarios, teléfono

#### 🥾 Actividades Turísticas
- Muestra hasta 6 actividades turísticas cercanas
- Incluye: nombre, descripción, duración, dificultad, precio
- Prioriza actividades de la misma localidad

#### 🎭 Eventos Culturales
- Muestra hasta 6 eventos culturales próximos
- Solo muestra eventos futuros (fecha >= hoy)
- Incluye: título, fecha, hora, lugar, precio
- Ordenados por fecha (más próximos primero)

## 🎨 Diseño

### Características Visuales
- **Cards responsivas**: Se adaptan a móvil, tablet y desktop
- **Hover effects**: Animación al pasar el ratón
- **Imágenes lazy loading**: Optimización de carga
- **Iconos descriptivos**: Font Awesome para mejor UX
- **Colores consistentes**: Usa la paleta de colores del sitio

### Layout
```
┌─────────────────────────────────────┐
│   Detalle del Alojamiento           │
│   (Información principal)           │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ 🗺️ Lugares de Interés en la Zona   │
│ ┌────┐ ┌────┐ ┌────┐               │
│ │Card│ │Card│ │Card│               │
│ └────┘ └────┘ └────┘               │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ 🥾 Actividades Turísticas           │
│ ┌────┐ ┌────┐ ┌────┐               │
│ │Card│ │Card│ │Card│               │
│ └────┘ └────┘ └────┘               │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ 🎭 Eventos Culturales Próximos      │
│ ┌────┐ ┌────┐ ┌────┐               │
│ │Card│ │Card│ │Card│               │
│ └────┘ └────┘ └────┘               │
└─────────────────────────────────────┘
```

## 🔧 Implementación Técnica

### Archivos Modificados/Creados

1. **`api/get_nearby_content.php`** (NUEVO)
   - Endpoint API para obtener contenido relacionado
   - Consulta las tablas: `places_of_interest`, `tourist_activities`, `cultural_events`
   - Filtra por localidad y provincia

2. **`alojamiento-detalle.html`** (MODIFICADO)
   - Añadidas secciones HTML para contenido relacionado
   - Estilos CSS para las cards
   - JavaScript para cargar y renderizar contenido

### Flujo de Datos

```
1. Usuario visita /alojamiento/[slug]
   ↓
2. Se carga información del alojamiento
   ↓
3. Se llama a get_nearby_content.php con el ID del alojamiento
   ↓
4. API consulta las 3 tablas filtrando por ubicación
   ↓
5. Se renderizan las cards de contenido relacionado
   ↓
6. Solo se muestran secciones con contenido disponible
```

## 📊 Consultas SQL

### Lugares de Interés
```sql
SELECT * FROM places_of_interest 
WHERE is_active = 1 
AND (municipality = :municipality OR province = :province)
ORDER BY CASE WHEN municipality = :municipality THEN 0 ELSE 1 END, name
LIMIT 6
```

### Actividades Turísticas
```sql
SELECT * FROM tourist_activities 
WHERE is_active = 1 
AND (municipality = :municipality OR province = :province)
ORDER BY CASE WHEN municipality = :municipality THEN 0 ELSE 1 END, name
LIMIT 6
```

### Eventos Culturales
```sql
SELECT * FROM cultural_events 
WHERE is_active = 1 
AND event_date >= :today
AND (municipality = :municipality OR province = :province)
ORDER BY event_date ASC, 
         CASE WHEN municipality = :municipality THEN 0 ELSE 1 END, 
         title
LIMIT 6
```

## 🎯 Comportamiento

### Si hay contenido disponible:
- ✅ Se muestra la sección correspondiente
- ✅ Cards clickeables que redirigen al detalle
- ✅ Información resumida y atractiva

### Si NO hay contenido:
- ❌ La sección NO se muestra (no aparece vacía)
- ✅ Experiencia limpia sin mensajes de "no hay contenido"

## 📱 Responsive Design

### Desktop (> 768px)
- Grid de 3 columnas
- Cards más grandes
- Hover effects completos

### Tablet (768px - 480px)
- Grid de 2 columnas
- Cards medianas

### Mobile (< 480px)
- Grid de 1 columna
- Cards apiladas verticalmente
- Padding reducido

## 🚀 Ejemplo de Uso

### URL de ejemplo:
```
https://rutasrurales.io/alojamiento/abuela-nines
```

### Respuesta API:
```json
{
  "success": true,
  "data": {
    "places_of_interest": [...],
    "tourist_activities": [...],
    "cultural_events": [...],
    "location": {
      "municipality": "Almazán",
      "province": "Soria"
    }
  }
}
```

## 🔍 Testing

Para probar la funcionalidad:

1. Visita cualquier página de detalle de alojamiento
2. Desplázate hacia abajo después de la información principal
3. Verás las secciones de contenido relacionado (si hay datos disponibles)
4. Haz clic en cualquier card para ir al detalle

## 📝 Notas Importantes

- ⚠️ Las secciones solo aparecen si hay contenido disponible
- ⚠️ Los eventos solo muestran fechas futuras
- ⚠️ Se priorizan resultados de la misma localidad sobre la provincia
- ⚠️ Límite de 6 items por sección para no saturar la página
- ✅ Imágenes con lazy loading para mejor rendimiento
- ✅ Manejo de errores silencioso (no afecta la carga del alojamiento)

## 🎨 Personalización

### Cambiar número de items mostrados:
Edita el `LIMIT` en las consultas SQL en `api/get_nearby_content.php`

### Cambiar estilos:
Modifica los estilos CSS en `alojamiento-detalle.html` dentro de la sección `<style>`

### Cambiar orden de prioridad:
Ajusta el `ORDER BY` en las consultas SQL

## ✅ Checklist de Implementación

- [x] Crear API endpoint `get_nearby_content.php`
- [x] Modificar `alojamiento-detalle.html`
- [x] Añadir estilos CSS responsivos
- [x] Implementar JavaScript para carga dinámica
- [x] Añadir manejo de errores
- [x] Optimizar consultas SQL
- [x] Implementar lazy loading de imágenes
- [x] Probar en diferentes dispositivos
- [x] Documentar funcionalidad

## 🎉 Resultado

Los usuarios ahora pueden descubrir fácilmente qué hacer y qué ver cerca de su alojamiento, mejorando significativamente la experiencia de planificación de viaje.
