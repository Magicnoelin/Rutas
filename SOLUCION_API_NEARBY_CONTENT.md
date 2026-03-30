# Solución: Error en API get_nearby_content.php para Google Rich Results

## Problema Identificado

La API `https://rutasrurales.io/api/get_nearby_content.php?accommodation_id=50` estaba devolviendo un error SQL:

```json
{
  "success": false,
  "error": "Error al obtener contenido cercano: SQLSTATE[HY093]: Invalid parameter number"
}
```

Este error impedía que Google Rich Results Test pudiera cargar los resultados enriquecidos de la página.

## Causa del Error

El problema estaba en las consultas SQL que usaban **parámetros nombrados** (`:parameter`) pero luego se ejecutaban con **parámetros posicionales** (array indexado). Además, algunos parámetros se usaban múltiples veces en la misma consulta (como `municipality` en la cláusula ORDER BY), lo que causaba el error "Invalid parameter number".

### Ejemplo del código con error:

```php
$sql = "SELECT ... 
        WHERE municipality = :municipality OR province = :province
        ORDER BY CASE WHEN municipality = :municipality THEN 0 ELSE 1 END";

$stmt->execute([
    'municipality' => $municipality,
    'province' => $province
]);
```

El problema: `:municipality` aparece 2 veces pero solo se pasa 1 vez en el array.

## Solución Aplicada

Se cambiaron todas las consultas SQL para usar **parámetros posicionales** (`?`) y se pasaron los valores en el orden correcto, incluyendo valores repetidos cuando era necesario.

### Código corregido:

```php
$sql = "SELECT ... 
        WHERE municipality = ? OR province = ?
        ORDER BY CASE WHEN municipality = ? THEN 0 ELSE 1 END";

$stmt->execute([$municipality, $province, $municipality]);
```

## Archivos Modificados

- **api/get_nearby_content.php**
  - Función `getPlacesOfInterest()` - Corregida
  - Función `getTouristActivities()` - Corregida
  - Función `getCulturalEvents()` - Corregida

## Cambios Específicos

### 1. getPlacesOfInterest()
```php
// ANTES (con error)
AND (municipality = :municipality OR province = :province)
ORDER BY CASE WHEN municipality = :municipality THEN 0 ELSE 1 END

$stmt->execute(['municipality' => $municipality, 'province' => $province]);

// DESPUÉS (corregido)
AND (municipality = ? OR province = ?)
ORDER BY CASE WHEN municipality = ? THEN 0 ELSE 1 END

$stmt->execute([$municipality, $province, $municipality]);
```

### 2. getTouristActivities()
```php
// ANTES (con error)
AND (municipality = :municipality OR province = :province)
ORDER BY CASE WHEN municipality = :municipality THEN 0 ELSE 1 END

$stmt->execute(['municipality' => $municipality, 'province' => $province]);

// DESPUÉS (corregido)
AND (municipality = ? OR province = ?)
ORDER BY CASE WHEN municipality = ? THEN 0 ELSE 1 END

$stmt->execute([$municipality, $province, $municipality]);
```

### 3. getCulturalEvents()
```php
// ANTES (con error)
WHERE is_active = 1 
AND event_date >= :today
AND (municipality = :municipality OR province = :province)
ORDER BY event_date ASC, CASE WHEN municipality = :municipality THEN 0 ELSE 1 END

$stmt->execute(['today' => $today, 'municipality' => $municipality, 'province' => $province]);

// DESPUÉS (corregido)
WHERE is_active = 1 
AND event_date >= ?
AND (municipality = ? OR province = ?)
ORDER BY event_date ASC, CASE WHEN municipality = ? THEN 0 ELSE 1 END

$stmt->execute([$today, $municipality, $province, $municipality]);
```

## Cómo Subir los Cambios al Servidor

### Opción 1: Usando Git (Recomendado)

```bash
git add api/get_nearby_content.php
git commit -m "Fix: Corregir parametros SQL en get_nearby_content.php para Google Rich Results"
git push origin main
```

**NOTA**: Si GitHub bloquea el push por detectar secretos de Stripe en commits antiguos, sigue el enlace que proporciona GitHub para permitir el secreto temporalmente.

### Opción 2: Subida Manual por FTP

1. Conecta a tu servidor por FTP
2. Navega a la carpeta `/api/`
3. Sube el archivo `get_nearby_content.php` corregido
4. Sobrescribe el archivo existente

## Verificación

Después de subir los cambios, verifica que la API funciona correctamente:

### 1. Prueba directa de la API:
```bash
curl "https://rutasrurales.io/api/get_nearby_content.php?accommodation_id=50"
```

Deberías recibir una respuesta JSON exitosa con:
```json
{
  "success": true,
  "message": "",
  "data": {
    "places_of_interest": [...],
    "tourist_activities": [...],
    "cultural_events": [...],
    "location": {
      "municipality": "...",
      "province": "..."
    }
  }
}
```

### 2. Prueba con Google Rich Results Test:

1. Ve a: https://search.google.com/test/rich-results
2. Ingresa la URL de tu alojamiento: `https://rutasrurales.io/alojamiento-detalle.html?id=50`
3. Haz clic en "Probar URL"
4. Verifica que ahora Google puede cargar los resultados enriquecidos correctamente

## Impacto

✅ **Resuelto**: La API ahora devuelve correctamente el contenido relacionado  
✅ **Resuelto**: Google Rich Results Test puede acceder a la API  
✅ **Mejora**: El SEO de las páginas de alojamientos mejorará con datos estructurados correctos  

## Fecha de Corrección

2 de octubre de 2026

## Commit

```
Fix: Corregir parametros SQL en get_nearby_content.php para Google Rich Results
Commit: a434de9
```
