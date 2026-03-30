# 📋 Instrucciones Paso a Paso - Asignar Slugs

## 🎯 ¿Qué es esto?

He creado una herramienta HTML que te permitirá asignar URLs amigables (slugs) a todos tus alojamientos de forma súper fácil.

## 📁 Archivo Creado

**Archivo:** `test-slugs.html`

Este archivo está listo para usar desde tu navegador.

## 🚀 Pasos a Seguir:

### PASO 1: Subir el Archivo
1. Sube el archivo `test-slugs.html` a tu servidor web
2. Debe estar en la misma carpeta que tus otros archivos HTML

### PASO 2: Abrir en el Navegador
1. Ve a tu navegador web
2. Abre la URL: `https://rutasrurales.io/test-slugs.html`
3. Verás una página con un botón verde

### PASO 3: Ejecutar la Asignación
1. Haz clic en el botón **"🚀 Asignar Slugs a Todos los Alojamientos"**
2. Espera a que se procese (puede tomar 10-30 segundos)
3. Verás el resultado en pantalla

### PASO 4: Verificar Resultados
Si todo sale bien, verás algo como:
```
✅ ¡Éxito!
Se asignaron slugs a 25 alojamientos.

Ejemplos de URLs generadas:
• /alojamientos/casa-rural-el-pinar
• /alojamientos/apartamento-centro-valladolid  
• /alojamientos/casa-enrique-soria
```

## 🔍 ¿Qué hace exactamente?

El script:
1. ✅ Busca todos los alojamientos sin slug en tu base de datos
2. ✅ Genera URLs amigables automáticamente
3. ✅ Verifica que sean únicas
4. ✅ Las guarda en la base de datos
5. ✅ Te muestra el resultado

## 🌐 URLs que Obtendrás

**Antes:**
```
alojamiento.html?id=123
alojamiento.html?id=124
```

**Después:**
```
/alojamientos/casa-rural-el-pinar-soria
/alojamientos/apartamento-centro-valladolid
/alojamientos/casa-rural-los-encinos
```

## 🛠️ Solución de Problemas

### ❌ Si ves "Error 404":
- Verifica que `test-slugs.html` esté subido al servidor
- Asegúrate de que la URL sea correcta

### ❌ Si ves "Error de Conexión":
- Verifica que `api/slug_generator.php` esté en la carpeta `api/`
- Confirma que tu servidor soporte PHP

### ❌ Si ves "Error" sin detalles:
- Revisa la consola del navegador (F12) para más información
- Verifica que la base de datos sea accesible

## 🎉 ¡Después de Ejecutar!

Una vez completado, podrás:
1. **Compartir URLs amigables** como: `rutasrurales.io/alojamientos/casa-enrique-soria`
2. **Mejorar tu SEO** con URLs descriptivas
3. **Tener páginas de detalle** profesionales para cada alojamiento

## 📱 Método Alternativo (Si Prefieres)

Si no quieres usar el archivo HTML, también puedes:

### En la Consola del Navegador:
1. Abre `test-slugs.html`
2. Presiona F12 (herramientas de desarrollador)
3. Ve a la pestaña "Console"
4. Copia y pega este código:

```javascript
fetch('/api/slug_generator.php', {
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    console.log('Resultado:', data);
    alert('¡Slugs asignados! ' + data.actualizados + ' alojamientos procesados.');
});
```

5. Presiona Enter

## ✅ Resultado Final

Una vez ejecutado correctamente, todos tus alojamientos tendrán URLs amigables como:
- `/alojamientos/casa-enrique-soria`
- `/alojamientos/apartamento-centro-valladolid`
- `/alojamientos/casa-rural-los-encinos`

¡Y tendrás páginas de detalle profesionales para cada uno!
