# 📤 Guía Paso a Paso: Subir Archivos al Servidor

## 🎯 Objetivo
Subir los archivos actualizados desde tu computadora al servidor web para que el formulario funcione.

---

## 📋 Opción 1: Usando cPanel (Recomendado - Más Fácil)

### **Paso 1: Acceder a cPanel**
1. Abre tu navegador
2. Ve a la URL de tu cPanel (normalmente es algo como):
   - `https://rutasrurales.io/cpanel`
   - O `https://cpanel.tuhosting.com`
3. Ingresa tu usuario y contraseña de cPanel

### **Paso 2: Abrir el Administrador de Archivos**
1. Una vez dentro de cPanel, busca el icono **"Administrador de archivos"** o **"File Manager"**
2. Haz clic en él
3. Se abrirá una nueva ventana con tus archivos

### **Paso 3: Navegar a la Carpeta Correcta**
1. En el panel izquierdo, busca y haz clic en:
   - `public_html` (la carpeta principal de tu sitio web)
2. Deberías ver archivos como `index.html`, `styles.css`, etc.

### **Paso 4: Subir el Archivo HTML Actualizado**
1. En la barra superior, haz clic en el botón **"Subir"** o **"Upload"**
2. Se abrirá una nueva ventana
3. Haz clic en **"Seleccionar archivo"**
4. En tu computadora, navega a:
   ```
   [RUTA_DE_TU_PROYECTO]\Rutas
   ```
5. Selecciona el archivo: **`agregar-alojamiento.html`**
6. Haz clic en **"Abrir"**
7. El archivo comenzará a subirse
8. **IMPORTANTE**: Si te pregunta si quieres sobrescribir el archivo existente, haz clic en **"Sí"** o **"Sobrescribir"**
9. Espera a que termine (verás una barra de progreso)
10. Cierra la ventana de subida

### **Paso 5: Subir los Archivos de la API**
1. Vuelve al Administrador de Archivos
2. Busca la carpeta **`api`** en `public_html`
3. Haz doble clic en la carpeta `api` para abrirla
4. Ahora vas a subir 3 archivos, uno por uno:

#### **Subir config.php:**
1. Haz clic en **"Subir"**
2. Selecciona archivo de tu computadora:
   ```
   [RUTA_DE_TU_PROYECTO]\Rutas\api\config.php
   ```
3. Si pregunta si sobrescribir, di **"Sí"**
4. Espera a que termine
5. Cierra la ventana de subida

#### **Subir crear.php:**
1. Haz clic en **"Subir"**
2. Selecciona archivo:
   ```
   [RUTA_DE_TU_PROYECTO]\Rutas\api\crear.php
   ```
3. Si pregunta si sobrescribir, di **"Sí"**
4. Espera a que termine
5. Cierra la ventana de subida

#### **Subir .htaccess:**
1. Haz clic en **"Subir"**
2. Selecciona archivo:
   ```
   [RUTA_DE_TU_PROYECTO]\Rutas\api\.htaccess
   ```
3. **NOTA**: El archivo `.htaccess` puede estar oculto en Windows
4. Para verlo, en el explorador de archivos:
   - Haz clic en la pestaña **"Vista"**
   - Marca la casilla **"Elementos ocultos"**
5. Si pregunta si sobrescribir, di **"Sí"**
6. Espera a que termine

### **Paso 6: Verificar que los Archivos se Subieron**
1. En el Administrador de Archivos, dentro de la carpeta `api`, deberías ver:
   - `config.php` (con fecha y hora reciente)
   - `crear.php` (con fecha y hora reciente)
   - `.htaccess` (con fecha y hora reciente)
   - Otros archivos que ya estaban

### **Paso 7: Verificar Permisos (Importante)**
1. En la carpeta `api`, haz clic derecho en el archivo **`config.php`**
2. Selecciona **"Permisos"** o **"Change Permissions"**
3. Asegúrate de que los permisos sean **644** (normalmente es el predeterminado)
4. Haz clic en **"Cambiar permisos"** o **"Change Permissions"**
5. Repite lo mismo para `crear.php` y `.htaccess`

---

## 📋 Opción 2: Usando FileZilla (FTP)

### **Paso 1: Descargar e Instalar FileZilla (si no lo tienes)**
1. Ve a: https://filezilla-project.org/download.php?type=client
2. Descarga FileZilla Client
3. Instálalo (siguiente, siguiente, instalar)

### **Paso 2: Conectar al Servidor**
1. Abre FileZilla
2. En la parte superior verás 4 campos:
   - **Servidor**: Ingresa `ftp.rutasrurales.io` (o la dirección FTP que te dio tu hosting)
   - **Nombre de usuario**: Tu usuario FTP
   - **Contraseña**: Tu contraseña FTP
   - **Puerto**: Deja en blanco o pon `21`
3. Haz clic en **"Conexión rápida"**
4. Si aparece un mensaje sobre certificado, haz clic en **"Aceptar"**

### **Paso 3: Navegar en FileZilla**
FileZilla tiene 2 paneles:
- **Izquierda**: Tu computadora
- **Derecha**: El servidor

#### **En el panel IZQUIERDO (tu computadora):**
1. Navega a:
   ```
   [RUTA_DE_TU_PROYECTO]\Rutas
   ```

#### **En el panel DERECHO (el servidor):**
1. Navega a la carpeta `public_html` (o `www` o `httpdocs`, depende del hosting)
2. Deberías ver tus archivos del sitio web

### **Paso 4: Subir Archivos**
1. En el panel IZQUIERDO, selecciona el archivo **`agregar-alojamiento.html`**
2. Haz clic derecho sobre él
3. Selecciona **"Subir"**
4. El archivo se copiará al servidor (panel derecho)
5. Si pregunta si sobrescribir, di **"Sí"**

### **Paso 5: Subir Archivos de la API**
1. En el panel DERECHO, entra a la carpeta `api`
2. En el panel IZQUIERDO, entra a la carpeta `api`
3. Selecciona estos 3 archivos (mantén presionada la tecla Ctrl mientras haces clic):
   - `config.php`
   - `crear.php`
   - `.htaccess`
4. Haz clic derecho sobre la selección
5. Selecciona **"Subir"**
6. Si pregunta si sobrescribir, di **"Sí a todo"**

### **Paso 6: Verificar**
En el panel DERECHO (servidor), dentro de la carpeta `api`, deberías ver los archivos con fecha y hora reciente.

---

## ✅ Verificación Final

### **Paso 1: Probar que los Archivos Están en el Servidor**
1. Abre tu navegador
2. Ve a: `https://rutasrurales.io/agregar-alojamiento.html`
3. **Resultado esperado**: Debería cargar el formulario (no error 404)

### **Paso 2: Probar la API**
1. En el navegador, ve a: `https://rutasrurales.io/api/crear.php`
2. **Resultado esperado**: Debería mostrar un mensaje JSON como:
   ```json
   {
     "success": false,
     "error": "Método no permitido"
   }
   ```
3. Esto es CORRECTO (significa que la API funciona pero rechaza peticiones GET)

### **Paso 3: Probar el Formulario Completo**
1. Ve a: `https://rutasrurales.io/agregar-alojamiento.html`
2. Llena el formulario con datos de prueba:
   - **Nombre**: Casa de Prueba
   - **Tipo**: Casa
   - **Dirección**: Calle Test 123
   - **Localidad**: Vinuesa
   - **Provincia**: Soria
   - **Plazas**: 4
   - **Teléfono**: 975000000
   - **Descripción**: Alojamiento de prueba
3. Haz clic en **"Guardar Alojamiento"**
4. **Resultado esperado**: Debería aparecer un mensaje de éxito con el ID del alojamiento

---

## 🆘 Solución de Problemas

### **No puedo ver el archivo .htaccess en Windows**
1. Abre el Explorador de Archivos
2. Haz clic en la pestaña **"Vista"**
3. Marca la casilla **"Elementos ocultos"**
4. Ahora deberías ver archivos que empiezan con punto (.)

### **FileZilla no se conecta**
1. Verifica que tienes los datos FTP correctos (pregunta a tu hosting)
2. Intenta cambiar el puerto a `21` o `22`
3. Si usa SFTP, el puerto es `22`

### **cPanel dice "Archivo demasiado grande"**
1. Los archivos PHP son pequeños, esto no debería pasar
2. Si pasa, usa FileZilla en su lugar

### **Después de subir, sigue sin funcionar**
1. Espera 2-3 minutos (el servidor puede tardar en actualizar)
2. Limpia la caché del navegador: Ctrl + Shift + R
3. Abre el navegador en modo incógnito y prueba de nuevo

---

## 📞 ¿Necesitas Ayuda?

Si tienes problemas:
1. Toma una captura de pantalla del error
2. Dime en qué paso te quedaste
3. Te ayudaré a resolverlo

---

## ✅ Checklist de Archivos a Subir

- [ ] `agregar-alojamiento.html` → en `public_html/`
- [ ] `api/config.php` → en `public_html/api/`
- [ ] `api/crear.php` → en `public_html/api/`
- [ ] `api/.htaccess` → en `public_html/api/`

Una vez subidos todos, ¡el formulario debería funcionar! 🎉
