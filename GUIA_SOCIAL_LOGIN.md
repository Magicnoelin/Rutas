# Guía de Configuración - Social Login (Google/Facebook)

## Descripción

Se ha implementado el sistema de **Social Login** que permite a los usuarios registrarse e iniciar sesión con sus cuentas de Google o Facebook con un solo clic.

## Archivos Modificados/Creados

1. **`api/social_login.php`** - Endpoint API que maneja la autenticación social
2. **`api/agregar_columnas_social_login.sql`** - Script SQL para agregar columnas necesarias a la tabla users
3. **`login.html`** - Añadidos botones de Google y Facebook para iniciar sesión
4. **`register.html`** - Añadidos botones de Google y Facebook para registrarse

---

## PASOS DE CONFIGURACIÓN NECESARIOS

### 1. Ejecutar el script SQL

Ejecuta el script `api/agregar_columnas_social_login.sql` en tu base de datos MySQL para agregar las columnas necesarias:

```sql
ALTER TABLE users 
ADD COLUMN google_id VARCHAR(255) NULL DEFAULT NULL,
ADD COLUMN facebook_id VARCHAR(255) NULL DEFAULT NULL,
ADD COLUMN avatar_url VARCHAR(500) NULL DEFAULT NULL,
ADD COLUMN auth_provider VARCHAR(20) NULL DEFAULT NULL,
ADD COLUMN created_social DATETIME NULL DEFAULT NULL,
ADD COLUMN updated_at DATETIME NULL DEFAULT NULL;

-- Índices
ALTER TABLE users 
ADD INDEX idx_google_id (google_id),
ADD INDEX idx_facebook_id (facebook_id),
ADD INDEX idx_auth_provider (auth_provider);
```

### 2. Configurar Firebase

El sistema utiliza **Firebase Authentication** para manejar Google y Facebook. Necesitas:

#### A) Crear un proyecto en Firebase
1. Ve a https://console.firebase.google.com/
2. Crea un nuevo proyecto o selecciona uno existente
3. Agrega tu aplicación web (icono </>)

#### B) HabilitarAuthentication Proveedores

En Firebase Console → Authentication → Sign-in method:

1. **Google**: Habilitar y configurar
2. **Facebook**: Habilitar y configurar (necesitas App ID y App Secret de Facebook Developers)

#### C) Obtener configuración

En Firebase Console → Configuración del proyecto → General → Tus apps → Configuración de la SDK:

Copia las credenciales y reemplázalas en `login.html` y `register.html`:

```javascript
const firebaseConfig = {
    apiKey: "TU_API_KEY",
    authDomain: "tu-proyecto.firebaseapp.com",
    projectId: "tu-proyecto",
    storageBucket: "tu-proyecto.appspot.com",
    messagingSenderId: "123456789",
    appId: "1:123456789:web:abcdef"
};
```

---

## Actualizar las URLs de callback

En Firebase Console → Authentication → Sign-in method → Configuración de OAuth:

- **URI de redirección de OAuth autorizado**: `https://tu-dominio.io/__/auth/handler`

---

## Configuración de Facebook Developers

1. Ve a https://developers.facebook.com/
2. Crea una aplicación o usa una existente
3. En "Facebook Login" → Configuración:
   - URI de OAuth válidos: `https://tu-proyecto.firebaseapp.com/__/auth/handler`
4. Obtén el **App ID** y **App Secret** y configúralos en Firebase

---

## Cómo funciona

1. El usuario hace clic en "Continuar con Google" o "Continuar con Facebook"
2. Se abre un popup de Firebase para autenticarse
3. Firebase devuelve un token de identificación
4. El token se envía al servidor (`api/social_login.php`)
5. El servidor:
   - Verifica el token con Google/Facebook
   - Busca si el usuario ya existe en la base de datos
   - Si no existe, crea una nueva cuenta automáticamente
   - Si existe (mismo email), vincula la cuenta social
6. El usuario es redirigido al dashboard

---

## Notas Importantes

- El sistema **no toca** el sistema de login tradicional (email/contraseña)
- Los usuarios existentes pueden vincular sus cuentas sociales
- El avatar de Google/Facebook se guarda automáticamente
- Si un usuario se registra con Google, puede luego iniciar sesión con email/contraseña (si vincularon la cuenta)

---

## Solución de problemas

### Error: "Popup closed by user"
- El usuario cerró el popup sin completar la autenticación
- No es un error crítico, el usuario puede intentar de nuevo

### Error: "Firebase no definido"
- Verifica que los scripts de Firebase se carguen correctamente
- Verifica tu conexión a internet

### Error: "Token de verificación fallido"
- Verifica que la configuración de Firebase sea correcta
- Verifica que los dominios estén autorizados en Firebase Console

### Error: "No se pudo verificar la identidad"
- Verifica que el App ID de Facebook coincida en Facebook Developers y Firebase
- Verifica que el Client ID de Google esté bien configurado
