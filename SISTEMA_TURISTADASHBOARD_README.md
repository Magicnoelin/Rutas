# 🎯 Sistema de Dashboard del Turista - Rutas

## 📋 Descripción General

Sistema completo de gestión para turistas logueados que incluye:
- **Panel personalizado** con estadísticas y resumen de actividad
- **Sistema de mensajería en tiempo real** con proveedores
- **Sistema de ofertas** bidireccional (turistas y proveedores pueden enviar/recibir)
- **Recomendaciones personalizadas** basadas en preferencias
- **Gestión de favoritos** y perfil de usuario
- **Notificaciones por email** automáticas

---

## 🗂️ Archivos Creados

### 1. **tourist-dashboard.html**
Landing page principal del turista logueado con:
- Sidebar de navegación con menú completo
- Tarjetas de estadísticas (mensajes, favoritos, recomendaciones, ofertas)
- Vista previa de mensajes recientes
- Grid de recomendaciones personalizadas
- Diseño responsive con sidebar fijo

**Características:**
- ✅ Diseño moderno con gradientes verdes (colores de la marca)
- ✅ Navegación por secciones (Resumen, Perfil, Mensajes, Preferencias, etc.)
- ✅ Datos simulados para testing
- ✅ Integración con sistema de mensajería

### 2. **tourist-messages.html**
Sistema de mensajería completo estilo WhatsApp/Telegram:
- Lista de conversaciones con búsqueda
- Chat en tiempo real con burbujas de mensajes
- Sistema de ofertas integrado en el chat
- Indicador de "escribiendo..."
- Estados de lectura (✓✓)
- Indicador de usuario en línea

**Características:**
- ✅ Interfaz de chat moderna y fluida
- ✅ Tarjetas de ofertas con botones Aceptar/Rechazar
- ✅ Filtros (Todos / No leídos)
- ✅ Avatares con iconos según tipo de entidad
- ✅ Timestamps y metadatos de mensajes
- ✅ Responsive para móvil

### 3. **api/crear_tablas_mensajeria.sql**
Script SQL completo con:
- 12 tablas principales
- Vistas optimizadas
- Procedimientos almacenados
- Triggers automáticos
- Índices para rendimiento
- Datos de ejemplo para testing

---

## 🗄️ Estructura de Base de Datos

### Tablas Principales:

#### **users**
Almacena turistas y proveedores
```sql
- id, email, password_hash
- user_type (tourist/provider)
- nickname, first_name, last_name, phone
- avatar_url, preferences_json
- created_at, updated_at, last_login
```

#### **conversations**
Conversaciones entre turistas y proveedores
```sql
- id, tourist_id, provider_id
- entity_type, entity_id
- status (active/archived/closed)
- last_message_at
```

#### **messages**
Mensajes individuales
```sql
- id, conversation_id
- sender_id, sender_type
- message_text, message_type
- is_read, read_at, created_at
```

#### **offers**
Ofertas enviadas/recibidas
```sql
- id, message_id, conversation_id
- offer_title, offer_description
- offer_price, original_price
- discount_percentage
- valid_from, valid_until
- status (pending/accepted/rejected)
```

#### **shared_contacts**
Datos compartidos al aceptar ofertas
```sql
- id, offer_id, conversation_id
- tourist_email, tourist_phone
- provider_email, provider_phone
- shared_at
```

#### **notifications**
Sistema de notificaciones
```sql
- id, user_id, notification_type
- title, message
- related_conversation_id
- is_read, read_at
```

#### **email_notifications**
Log de emails enviados
```sql
- id, user_id, email_to
- subject, body
- status (pending/sent/failed)
- sent_at
```

#### **favorites**
Favoritos del usuario
```sql
- id, user_id
- entity_type, entity_id
- notes, created_at
```

#### **user_sessions**
Sesiones para WebSockets
```sql
- id, user_id, session_token
- socket_id, is_online
- last_activity, expires_at
```

---

## 🔄 Flujo de Funcionamiento

### 1. **Login del Turista**
```
login.html → Autenticación → tourist-dashboard.html
```

### 2. **Navegación en el Dashboard**
```
Dashboard → Sidebar Menu → Secciones:
- Resumen (overview)
- Mi Perfil (editar nickname, preferencias)
- Mensajes (tourist-messages.html)
- Preferencias
- Recomendaciones
- Favoritos
- Alojamientos/Lugares/Actividades/Eventos filtrados
```

### 3. **Sistema de Mensajería**
```
Turista → Click en "Contactar" en alojamiento/lugar/actividad
       → Se crea conversación
       → Envía mensaje
       → Proveedor recibe notificación (email + in-app)
       → Proveedor responde
       → Turista recibe notificación
```

### 4. **Sistema de Ofertas**

#### **Proveedor envía oferta:**
```
Proveedor → Envía oferta a turista
          → Turista recibe notificación
          → Turista ve oferta en chat
          → Turista acepta/rechaza
          → Si acepta: Se comparten datos de contacto
```

#### **Turista solicita oferta:**
```
Turista → "Solicitar oferta especial"
        → Proveedor recibe solicitud
        → Proveedor crea y envía oferta
        → Flujo de aceptación
```

### 5. **Compartir Datos de Contacto**
```
Oferta aceptada → Trigger automático
                → Inserta en shared_contacts
                → Ambas partes reciben:
                  - Email del otro
                  - Teléfono del otro
                  - Nombre completo
                  - Dirección (proveedor)
```

---

## 🎨 Diseño y Estilos

### Colores de la Marca:
```css
--primary-color: #2F5233 (Verde oscuro)
--secondary-color: #6B8E6B (Verde medio)
--accent-color: #B8956A (Dorado)
--dark-color: #1A2E1A (Verde muy oscuro)
```

### Componentes Visuales:
- **Sidebar fijo** con gradiente verde
- **Tarjetas de estadísticas** con gradientes personalizados
- **Burbujas de chat** estilo moderno
- **Tarjetas de ofertas** con fondo naranja
- **Badges de notificación** rojos
- **Indicadores de estado** (online, leído, etc.)

---

## 📱 Responsive Design

### Desktop (> 768px):
- Sidebar fijo de 280px
- Contenido principal con margen izquierdo
- Grid de 3-4 columnas para recomendaciones

### Tablet (768px):
- Sidebar se mantiene pero ajustado
- Grid de 2 columnas

### Móvil (< 768px):
- Sidebar se convierte en menú completo arriba
- Contenido a ancho completo
- Grid de 1 columna
- Chat optimizado para pantalla pequeña

---

## 🔧 APIs Backend Necesarias (Próximas a implementar)

### 1. **api/tourist-auth.php**
```php
POST /api/tourist-auth.php
- login(email, password)
- logout(session_token)
- register(email, password, nickname)
- verify_session(session_token)
```

### 2. **api/tourist-profile.php**
```php
GET /api/tourist-profile.php?user_id=X
POST /api/tourist-profile.php
- update_profile(nickname, first_name, last_name, phone, avatar)
- update_preferences(interests, budget, etc.)
```

### 3. **api/tourist-messages.php**
```php
GET /api/tourist-messages.php?user_id=X
- get_conversations(user_id)
- get_messages(conversation_id)
POST /api/tourist-messages.php
- send_message(conversation_id, text)
- mark_as_read(message_id)
- create_conversation(tourist_id, entity_type, entity_id)
```

### 4. **api/tourist-offers.php**
```php
GET /api/tourist-offers.php?user_id=X
POST /api/tourist-offers.php
- create_offer(conversation_id, offer_data)
- accept_offer(offer_id)
- reject_offer(offer_id)
- get_shared_contacts(offer_id)
```

### 5. **api/tourist-recommendations.php**
```php
GET /api/tourist-recommendations.php?user_id=X
- Filtra alojamientos/lugares/actividades/eventos
- Basado en preferencias del usuario
- Retorna JSON con recomendaciones personalizadas
```

### 6. **api/tourist-favorites.php**
```php
GET /api/tourist-favorites.php?user_id=X
POST /api/tourist-favorites.php
- add_favorite(user_id, entity_type, entity_id)
- remove_favorite(favorite_id)
- get_favorites(user_id)
```

### 7. **api/tourist-notifications.php**
```php
GET /api/tourist-notifications.php?user_id=X
POST /api/tourist-notifications.php
- get_notifications(user_id)
- mark_as_read(notification_id)
- get_unread_count(user_id)
```

### 8. **api/send-email-notification.php**
```php
POST /api/send-email-notification.php
- send_new_message_email(user_id, message)
- send_new_offer_email(user_id, offer)
- send_offer_accepted_email(user_id, offer)
```

---

## 🚀 WebSockets para Tiempo Real

### Implementación con Socket.IO (Node.js):

```javascript
// server.js
const io = require('socket.io')(3000);

io.on('connection', (socket) => {
    // Usuario se conecta
    socket.on('user_online', (userId) => {
        // Actualizar user_sessions
        // Notificar a contactos
    });
    
    // Nuevo mensaje
    socket.on('send_message', (data) => {
        // Guardar en BD
        // Emitir a destinatario
        io.to(recipientSocketId).emit('new_message', data);
    });
    
    // Usuario escribiendo
    socket.on('typing', (conversationId) => {
        socket.broadcast.emit('user_typing', conversationId);
    });
});
```

### Cliente (en tourist-messages.html):
```javascript
const socket = io('http://localhost:3000');

socket.emit('user_online', userId);

socket.on('new_message', (message) => {
    // Actualizar UI con nuevo mensaje
    renderNewMessage(message);
});

socket.on('user_typing', (conversationId) => {
    // Mostrar indicador de escritura
    showTypingIndicator(conversationId);
});
```

---

## 📧 Sistema de Notificaciones por Email

### Configuración PHP Mailer:

```php
// api/email-config.php
use PHPMailer\PHPMailer\PHPMailer;

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'noreply@rutasrurales.io';
    $mail->Password = 'your_password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    $mail->setFrom('noreply@rutasrurales.io', 'Rutas Rurales');
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->isHTML(true);
    
    return $mail->send();
}
```

### Templates de Email:

#### **Nuevo Mensaje:**
```html
<h2>Nuevo mensaje en Rutas</h2>
<p>Hola {nickname},</p>
<p>Has recibido un nuevo mensaje de <strong>{sender_name}</strong>:</p>
<blockquote>{message_preview}</blockquote>
<a href="https://rutasrurales.io/tourist-messages.html">Ver mensaje completo</a>
```

#### **Nueva Oferta:**
```html
<h2>¡Nueva oferta especial!</h2>
<p>Hola {nickname},</p>
<p><strong>{provider_name}</strong> te ha enviado una oferta:</p>
<h3>{offer_title}</h3>
<p>{offer_description}</p>
<p><strong>Precio: {offer_price}€</strong> (antes {original_price}€)</p>
<p>Válida hasta: {valid_until}</p>
<a href="https://rutasrurales.io/tourist-messages.html">Ver oferta</a>
```

---

## 🔐 Seguridad

### Medidas Implementadas:

1. **Autenticación:**
   - Passwords hasheados con `password_hash()` (bcrypt)
   - Tokens de sesión únicos
   - Expiración de sesiones

2. **Autorización:**
   - Verificar que el usuario solo accede a sus propios datos
   - Validar permisos en cada endpoint

3. **Protección SQL Injection:**
   - Prepared statements en todas las queries
   - Validación de inputs

4. **XSS Protection:**
   - Escapar outputs con `htmlspecialchars()`
   - Content Security Policy headers

5. **CSRF Protection:**
   - Tokens CSRF en formularios
   - Verificación de origen

---

## 📊 Procedimientos Almacenados

### **sp_create_conversation**
Crea una nueva conversación o retorna la existente
```sql
CALL sp_create_conversation(tourist_id, entity_type, entity_id, provider_id);
```

### **sp_send_message**
Envía un mensaje y crea notificación automática
```sql
CALL sp_send_message(conversation_id, sender_id, sender_type, message_text, message_type);
```

### **sp_accept_offer**
Acepta oferta y comparte datos de contacto
```sql
CALL sp_accept_offer(offer_id, user_id);
```

---

## 🎯 Próximos Pasos de Implementación

### Fase 1: Backend APIs ✅ (Prioridad Alta)
- [ ] Crear `api/tourist-auth.php`
- [ ] Crear `api/tourist-profile.php`
- [ ] Crear `api/tourist-messages.php`
- [ ] Crear `api/tourist-offers.php`
- [ ] Crear `api/tourist-recommendations.php`
- [ ] Crear `api/tourist-favorites.php`
- [ ] Crear `api/tourist-notifications.php`

### Fase 2: WebSockets ⚡ (Prioridad Media)
- [ ] Configurar servidor Socket.IO
- [ ] Integrar en tourist-messages.html
- [ ] Implementar eventos en tiempo real
- [ ] Gestionar sesiones online/offline

### Fase 3: Emails 📧 (Prioridad Media)
- [ ] Configurar PHPMailer
- [ ] Crear templates HTML
- [ ] Implementar `api/send-email-notification.php`
- [ ] Configurar cron job para emails pendientes

### Fase 4: Panel de Proveedores 🏢 (Prioridad Alta)
- [ ] Crear `provider-dashboard.html`
- [ ] Crear `provider-messages.html`
- [ ] Sistema para enviar ofertas proactivas
- [ ] Gestión de entidades (alojamientos/actividades)

### Fase 5: Funcionalidades Adicionales ⭐
- [ ] Sistema de valoraciones
- [ ] Historial de viajes
- [ ] Calendario de disponibilidad
- [ ] Integración con pasarelas de pago
- [ ] Chat grupal
- [ ] Videollamadas

---

## 🧪 Testing

### Datos de Prueba Incluidos:
```sql
-- Usuarios
maria@email.com (turista)
juan@email.com (turista)
casarural@email.com (proveedor)
rutaslobos@email.com (proveedor)

-- Conversaciones activas
-- Mensajes de ejemplo
-- Ofertas pendientes
-- Notificaciones
```

### Para Probar:
1. Ejecutar `crear_tablas_mensajeria.sql` en MySQL
2. Abrir `tourist-dashboard.html` en navegador
3. Navegar por las secciones
4. Abrir `tourist-messages.html`
5. Probar envío de mensajes
6. Probar aceptar/rechazar ofertas

---

## 📝 Notas Importantes

### Configuración Requerida:
- **PHP 7.4+** con extensiones: mysqli, json, mbstring
- **MySQL 5.7+** o MariaDB 10.3+
- **Node.js 14+** para WebSockets (opcional pero recomendado)
- **Servidor SMTP** para emails

### Variables de Entorno:
```env
DB_HOST=localhost
DB_NAME=rutas_db
DB_USER=root
DB_PASS=password

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=noreply@rutasrurales.io
SMTP_PASS=your_password

SOCKET_PORT=3000
```

---

## 🤝 Integración con Sistema Existente

### Archivos a Modificar:

1. **login.html**
   - Redirigir a `tourist-dashboard.html` tras login exitoso
   - Guardar session_token en localStorage

2. **index.html**
   - Añadir enlace "Mi Panel" si usuario logueado
   - Mostrar notificaciones en navbar

3. **alojamientos-turisticos.html, actividades-turisticas.html, eventos-culturales.html**
   - Añadir botón "Contactar" en cada tarjeta
   - Al click: crear conversación y redirigir a messages

---

## 📞 Soporte

Para dudas o problemas:
- Email: olgamarin@rutasrurales.io
- Teléfono: +34 605 249 696

---

## 📄 Licencia

© 2026 Rutas Rurales. Todos los derechos reservados.

---

**¡Sistema listo para implementación! 🚀**
