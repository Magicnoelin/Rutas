# Cómo marcar alojamientos como no disponibles

## Método actual

Para marcar un alojamiento como "no disponible" y mostrar el mensaje amigable, sigue estos pasos:

### 1. Editar el archivo `redirect_manager.php`

Abre el archivo `redirect_manager.php` y busca el array `$gone` (aproximadamente línea 37):

```php
// Array para slugs que deben devolver 410 Gone
$gone = [
    'alojamiento' => [
        'entrepinos',
        'casa-olvido',
    ]
];
```

### 2. Agregar el slug del alojamiento

Simplemente agrega el slug del alojamiento que quieres marcar como no disponible dentro del array `'alojamiento' => [...]`.

**Ejemplo:**
```php
'alojamiento' => [
    'entrepinos',
    'casa-olvido',
    'nuevo-alojamiento-no-disponible',  // ← Agregar aquí
]
```

### 3. Guardar el archivo

Una vez guardado, el sistema automáticamente mostrará el mensaje:

> **"Este alojamiento ya no está disponible"**
> 
> Lo sentimos, esta propiedad ha sido retirada de nuestra plataforma de forma permanente. Pero tu próximo viaje no tiene por qué detenerse aquí; tenemos un lugar esperándote que te gustará aún más.
> 
> [Ver otros alojamientos]

## Qué hace el sistema

1. **Código HTTP 410 Gone**: El sistema devuelve el código HTTP 410 (Gone), que es SEO-friendly para indicar que el contenido ha sido eliminado permanentemente.

2. **Página amigable**: Muestra una página HTML con diseño profesional que incluye:
   - Título claro
   - Mensaje explicativo
   - Botón para ver otros alojamientos
   - Estilos CSS integrados

3. **SEO optimizado**: 
   - No genera enlaces rotos (404)
   - Indica claramente a los motores de búsqueda que el contenido ya no existe
   - Ofrece una alternativa al usuario

## Alojamientos actualmente marcados como no disponibles

- `entrepinos` - https://rutasrurales.io/alojamiento/entrepinos
- `casa-olvido` - https://rutasrurales.io/alojamiento/casa-olvido

## Notas importantes

- **No elimines** el alojamiento de la base de datos, solo agrégalo a este array
- El slug debe coincidir exactamente con el que aparece en la URL
- Este método es solo para alojamientos (no funciona para lugares o actividades)
- Para lugares o actividades, usarías el array `$redirects` en lugar de `$gone`

## Para futuras referencias

Cuando necesites marcar otro alojamiento como no disponible, simplemente:
1. Copia el slug de la URL (ej: `https://rutasrurales.io/alojamiento/MI-SLUG`)
2. Ábre `redirect_manager.php`
3. Agrega el slug al array `$gone['alojamiento']`
4. Guarda el archivo

¡Listo! El alojamiento ahora mostrará el mensaje de "no disponible".