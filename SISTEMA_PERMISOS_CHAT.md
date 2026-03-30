# 📱 Sistema de Permisos de Mensajería - Rutas Rurales

## 🎯 Objetivo

Sistema configurable de permisos de chat basado en **tipo de usuario** y **nivel de membresía**, que controla quién puede iniciar conversaciones, enviar mensajes y ofertas, con límites diarios para prevenir spam.

---

## 📊 Arquitectura del Sistema

### Componentes Principales:

1. **Tabla `chat_permissions`**: Define reglas de permisos
2. **Tabla `chat_daily_limits`**: Control de límites diarios
3. **API `check_chat_permission.php`**: Validación de permisos
4. **API `chat.php`**: Sistema de mensajería con validación integrada

---

## 🔐 Reglas de Permisos Actuales

### 📋 Matriz de Permisos

| Iniciador | Membresía | Destinatario | Puede Iniciar | Puede Enviar | Puede Ofertar | Límite Diario |
|-----------|-----------|--------------|---------------|--------------|---------------|---------------|
| **Turista** | Free | Gestor | ✅ SÍ | ✅ SÍ | ❌ NO | 50 mensajes |
| **Turista** | Free | Turista | ❌ NO | ❌ NO | ❌ NO | - |
| **Turista** | Premium | Gestor | ✅ SÍ | ✅ SÍ | ❌ NO | Ilimitado |
| **Turista** | Premium | Turista | ❌ NO | ❌ NO | ❌ NO | - |
| **Gestor** | Free | Turista | ❌ NO* | ✅ SÍ* | ❌ NO | 20 mensajes |
| **Gestor** | Free | Gestor | ❌ NO | ❌ NO | ❌ NO | - |
| **Gestor** | Premium | Turista | ❌ NO* | ✅ SÍ* | ✅ SÍ | Ilimitado |
| **Gestor** | Premium | Gestor Premium | ✅ SÍ | ✅ SÍ | ✅ SÍ | Ilimitado |
| **Gestor** | Premium | Gestor Free | ✅ SÍ | ✅ SÍ | ❌ NO | Ilimitado |

**\* Los gestores NO pueden iniciar conversaciones con turistas, solo responder cuando el turista les contacta primero.**

---

## 🚀 Instalación

### 1. Ejecutar Script SQL

```bash
mysql -u tu_usuario -p tu_base_datos < api/crear_tabla_chat_permissions.sql
```

Este script crea:
- ✅ Tabla `chat_permissions`
- ✅ Tabla `chat_daily_limits`
- ✅ Reglas iniciales de permisos
- ✅ Funciones y procedimientos auxiliares
- ✅ Vistas útiles

### 2. Verificar Instalación

```sql
-- Ver todas las reglas activas
SELECT * FROM v_chat_permissions_summary;

-- Verificar tablas creadas
SHOW TABLES LIKE 'chat%';
```

---

## 📖 Uso de las APIs

### 1. Verificar Permisos (Antes de Mostrar Botones)

**Endpoint:** `POST /api/check_chat_permission.php`

```javascript
// Verificar si puede iniciar conversación
const response = await fetch('/api/check_chat_permission.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'initiate',
        recipient_id: 123
    })
});

const result = await response.json();

if (result.success && result.data.allowed) {
    // Mostrar botón "Contactar"
    showContactButton();
} else {
    // Mostrar mensaje de upgrade
    showUpgradeMessage(result.data.message);
}
```

**Acciones disponibles:**
- `initiate`: Verificar si puede iniciar conversación
- `send_message`: Verificar si puede enviar mensaje
- `send_offer`: Verificar si puede enviar oferta

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "allowed": true,
        "message": "",
        "upgrade_required": false,
        "permission_details": {
            "can_initiate": true,
            "can_send_messages": true,
            "can_send_offers": false,
            "daily_limit": 50,
            "description": "Turistas gratuitos pueden contactar gestores..."
        },
        "initiator": {
            "type": "turista",
            "membership": "free"
        },
        "recipient": {
            "type": "gestor",
            "membership": "premium",
            "name": "Juan Pérez"
        }
    }
}
```

### 2. Iniciar Conversación

**Endpoint:** `POST /api/chat.php?action=start_conversation`

```javascript
const response = await fetch('/api/chat.php?action=start_conversation', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        recipient_id: 123
    })
});
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "data": {
        "conversation_id": 45,
        "is_new": true
    }
}
```

**Respuesta con error de permisos:**
```json
{
    "success": false,
    "error": "Tu membresía gratuita no permite iniciar conversaciones. Los gestores solo pueden responder cuando un turista les contacta. Actualiza a Premium para esta funcionalidad."
}
```

### 3. Enviar Mensaje

**Endpoint:** `POST /api/chat.php?action=send_message`

```javascript
const response = await fetch('/api/chat.php?action=send_message', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        conversation_id: 45,
        content: "Hola, me interesa tu alojamiento"
    })
});
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "data": {
        "status": "sent",
        "timestamp": "2026-02-03 05:45:00",
        "messages_remaining": 49,
        "daily_limit": 50
    }
}
```

**Respuesta con límite alcanzado:**
```json
{
    "success": false,
    "error": "Has alcanzado tu límite diario de 50 mensajes. Actualiza a Premium para enviar mensajes ilimitados."
}
```

---

## 🛠️ Modificar Reglas de Permisos

### Desde SQL (Recomendado para Administradores)

```sql
-- Cambiar límite diario de turistas gratuitos
UPDATE chat_permissions
SET max_messages_per_day = 100
WHERE initiator_type = 'turista'
AND initiator_membership = 'free'
AND recipient_type = 'gestor';

-- Permitir que gestores premium inicien conversaciones con turistas
UPDATE chat_permissions
SET can_initiate_conversation = TRUE
WHERE initiator_type = 'gestor'
AND initiator_membership = 'premium'
AND recipient_type = 'turista';

-- Desactivar una regla temporalmente
UPDATE chat_permissions
SET is_active = FALSE
WHERE id = 5;

-- Añadir nueva regla personalizada
INSERT INTO chat_permissions (
    initiator_type, initiator_membership,
    recipient_type, recipient_membership,
    can_initiate_conversation, can_send_messages, can_send_offers,
    max_messages_per_day, description
) VALUES (
    'gestor', 'enterprise',
    'turista', 'any',
    TRUE, TRUE, TRUE,
    NULL, 'Gestores enterprise pueden iniciar conversaciones con turistas'
);
```

### Ver Reglas Activas

```sql
-- Vista resumida
SELECT * FROM v_chat_permissions_summary;

-- Detalle completo
SELECT 
    id,
    CONCAT(initiator_type, ' (', initiator_membership, ')') as from_user,
    CONCAT(recipient_type, ' (', recipient_membership, ')') as to_user,
    can_initiate_conversation,
    can_send_messages,
    can_send_offers,
    max_messages_per_day,
    description,
    is_active
FROM chat_permissions
ORDER BY initiator_type, initiator_membership;
```

---

## 📊 Monitoreo y Estadísticas

### Ver Uso Diario de Mensajes

```sql
-- Usuarios que más mensajes envían hoy
SELECT 
    u.id,
    CONCAT(u.first_name, ' ', u.last_name) as nombre,
    u.user_type,
    u.membership_type,
    cdl.messages_sent,
    cp.max_messages_per_day as limite
FROM chat_daily_limits cdl
JOIN users u ON cdl.user_id = u.id
LEFT JOIN chat_permissions cp ON 
    (CASE WHEN u.user_type = 'turista' THEN 'turista' ELSE 'gestor' END) = cp.initiator_type
    AND u.membership_type = cp.initiator_membership
WHERE cdl.date = CURDATE()
ORDER BY cdl.messages_sent DESC
LIMIT 20;
```

### Usuarios Cerca del Límite

```sql
-- Usuarios que han usado más del 80% de su límite diario
SELECT 
    u.id,
    CONCAT(u.first_name, ' ', u.last_name) as nombre,
    u.email,
    cdl.messages_sent,
    cp.max_messages_per_day as limite,
    ROUND((cdl.messages_sent / cp.max_messages_per_day) * 100, 1) as porcentaje_usado
FROM chat_daily_limits cdl
JOIN users u ON cdl.user_id = u.id
JOIN chat_permissions cp ON 
    (CASE WHEN u.user_type = 'turista' THEN 'turista' ELSE 'gestor' END) = cp.initiator_type
    AND u.membership_type = cp.initiator_membership
WHERE cdl.date = CURDATE()
AND cp.max_messages_per_day IS NOT NULL
AND (cdl.messages_sent / cp.max_messages_per_day) >= 0.8
ORDER BY porcentaje_usado DESC;
```

---

## 🔄 Mantenimiento

### Limpieza Automática de Límites Antiguos

```sql
-- Ejecutar mensualmente (crear como evento programado)
DELETE FROM chat_daily_limits 
WHERE date < DATE_SUB(CURDATE(), INTERVAL 30 DAY);
```

### Crear Evento Automático

```sql
CREATE EVENT IF NOT EXISTS cleanup_old_chat_limits
ON SCHEDULE EVERY 1 WEEK
DO
DELETE FROM chat_daily_limits 
WHERE date < DATE_SUB(CURDATE(), INTERVAL 30 DAY);
```

---

## 🎨 Integración en Frontend

### Ejemplo: Botón "Contactar" en Ficha de Alojamiento

```javascript
// En alojamiento-detalle.html
async function initContactButton() {
    const providerId = document.getElementById('provider-id').value;
    
    // Verificar permisos
    const permCheck = await fetch('/api/check_chat_permission.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'initiate',
            recipient_id: providerId
        })
    });
    
    const result = await permCheck.json();
    
    if (result.success && result.data.allowed) {
        // Mostrar botón normal
        document.getElementById('contact-btn').onclick = async () => {
            const conv = await startConversation(providerId);
            window.location.href = `/user-dashboard.html?conversation=${conv.conversation_id}`;
        };
    } else {
        // Mostrar mensaje de upgrade
        document.getElementById('contact-btn').onclick = () => {
            showUpgradeModal(result.data.message);
        };
        document.getElementById('contact-btn').innerHTML = '🔒 ' + result.data.message;
    }
}
```

### Ejemplo: Mostrar Límite de Mensajes en Dashboard

```javascript
// En user-dashboard.html
async function showMessageLimitWarning() {
    // Después de enviar un mensaje
    const response = await sendMessage(conversationId, messageText);
    
    if (response.success) {
        const { messages_remaining, daily_limit } = response.data;
        
        if (messages_remaining !== null && messages_remaining < 10) {
            showWarning(`Te quedan ${messages_remaining} de ${daily_limit} mensajes hoy. 
                        Actualiza a Premium para mensajes ilimitados.`);
        }
    }
}
```

---

## 🚨 Mensajes de Error Personalizados

El sistema devuelve mensajes claros según el caso:

| Situación | Mensaje |
|-----------|---------|
| Gestor free intenta iniciar | "Tu membresía gratuita no permite iniciar conversaciones. Los gestores solo pueden responder cuando un turista les contacta. Actualiza a Premium para esta funcionalidad." |
| Turista intenta hablar con turista | "No puedes iniciar conversaciones con otros turistas." |
| Límite diario alcanzado (free) | "Has alcanzado tu límite diario de X mensajes. Actualiza a Premium para enviar mensajes ilimitados." |
| Sin permisos genérico | "No tienes permisos para realizar esta acción." |

---

## 📈 Futuras Mejoras

- [ ] Panel de administración web para modificar permisos
- [ ] Notificaciones cuando un usuario alcanza el 80% del límite
- [ ] Estadísticas de uso por tipo de membresía
- [ ] Sistema de reportes de spam
- [ ] Límites por hora además de diarios
- [ ] Whitelist/blacklist de usuarios

---

## 🔗 Archivos Relacionados

- `api/crear_tabla_chat_permissions.sql` - Script de instalación
- `api/check_chat_permission.php` - API de validación
- `api/chat.php` - Sistema de mensajería
- `user-dashboard.html` - Interfaz de usuario

---

## 📞 Soporte

Para modificar reglas o resolver problemas, contacta al administrador del sistema.

**Última actualización:** 3 de febrero de 2026
