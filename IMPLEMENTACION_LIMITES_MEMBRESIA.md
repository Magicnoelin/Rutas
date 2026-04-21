# Implementación de Límites de Membresía para Alojamientos

## Resumen de Cambios Realizados

Se han implementado las siguientes funcionalidades para gestionar los límites de membresía básica en la creación de alojamientos:

### 1. Validación de Límites en el Backend (`api/crear.php`)
- **Límites implementados:**
  - Membresía Básica: Máximo 2 alojamientos o 15 plazas totales
  - Membresía Gratuita: Máximo 1 alojamiento o 8 plazas totales
  - Membresía Premium: Máximo 10 alojamientos o 100 plazas totales
  - Membresía Empresa: Máximo 50 alojamientos o 500 plazas totales

- **Validación automática:** Antes de crear un nuevo alojamiento, el sistema verifica:
  - Número de alojamientos existentes del usuario
  - Total de plazas actuales + nuevas plazas
  - Muestra mensajes de error específicos si se superan los límites

### 2. Interfaz de Usuario Mejorada (`agregar-alojamiento.html`)
- **Sección informativa de límites:** Muestra en tiempo real:
  - Alojamientos actuales vs límite
  - Plazas totales vs límite
  - Tipo de membresía actual

- **Validación en tiempo real:**
  - El campo de "plazas" cambia de color cuando se acerca al límite
  - Mensajes informativos sobre los límites disponibles
  - Botón deshabilitado si se alcanza el límite de alojamientos

- **Promoción de upgrade:**
  - Se muestra automáticamente cuando el usuario está cerca de alcanzar sus límites
  - Enlace directo a la sección de membresía para actualizar a Premium

### 3. Nueva API para Gestión de Límites (`api/get_membership_limits.php`)
- **Endpoint:** `GET /api/get_membership_limits.php`
- **Funcionalidad:** Devuelve información completa sobre los límites del usuario actual
- **Datos incluidos:**
  - Tipo de membresía y estado
  - Alojamientos y plazas actuales
  - Límites máximos según el plan
  - Planes disponibles para upgrade
  - Mensajes personalizados según la situación

### 4. Integración con el Sistema de Pagos Existente
- **Compatibilidad con `api/upgrade_membership.php`**
- **Flujo de upgrade:** Los usuarios pueden actualizar su membresía directamente desde la interfaz
- **Redirección automática** a la sección de membresía cuando se recomienda upgrade

## Cómo Funciona el Sistema

### Para Usuarios Nuevos:
1. Al acceder a "Agregar Alojamiento", se muestra su información de membresía
2. Si tienen membresía básica/gratuita, ven sus límites claramente
3. Al intentar crear un alojamiento que supere los límites, reciben un error específico
4. Se les ofrece la opción de actualizar a Premium para aumentar sus límites

### Para Usuarios Existentes:
1. El sistema calcula automáticamente sus alojamientos y plazas totales
2. Se actualiza la interfaz en tiempo real según los datos ingresados
3. Reciben advertencias visuales antes de alcanzar los límites

## Mensajes de Error Implementados

### Cuando se supera el límite de alojamientos:
```
"Límite alcanzado: La membresía básica permite máximo 2 alojamientos. Actualiza a Premium para añadir más."
```

### Cuando se supera el límite de plazas:
```
"Límite alcanzado: La membresía básica permite máximo 15 plazas totales. Actualiza a Premium para añadir más plazas."
```

## Beneficios de la Implementación

1. **Transparencia:** Los usuarios conocen sus límites desde el principio
2. **Prevención de errores:** Validación antes de enviar el formulario
3. **Upselling inteligente:** Promoción de upgrades en el momento adecuado
4. **Experiencia mejorada:** Interfaz intuitiva con feedback visual
5. **Escalabilidad:** Fácil de extender para nuevos tipos de membresía

## Próximos Pasos Recomendados

1. **Integración con Stripe:** Completar el flujo de pago para upgrades
2. **Panel de control:** Añadir sección de membresía en el dashboard del usuario
3. **Notificaciones:** Enviar emails cuando el usuario esté cerca de sus límites
4. **Estadísticas:** Mostrar uso de recursos vs límites en tiempo real
5. **Pruebas:** Validar el sistema con diferentes tipos de usuarios y escenarios

## Archivos Modificados/Creados

1. `api/crear.php` - Validación de límites en creación de alojamientos
2. `agregar-alojamiento.html` - Interfaz de usuario con límites
3. `api/get_membership_limits.php` - Nueva API para gestión de límites
4. `IMPLEMENTACION_LIMITES_MEMBRESIA.md` - Esta documentación

## Notas Técnicas

- El sistema utiliza sesiones PHP para identificar al usuario
- Las consultas SQL optimizadas para contar alojamientos y plazas
- JavaScript asíncrono para actualización en tiempo real
- Diseño responsive que funciona en dispositivos móviles
- Compatible con el sistema de moderación existente

## Testing

Para probar el sistema:

1. Iniciar sesión con un usuario de membresía básica
2. Intentar crear más de 2 alojamientos
3. Verificar que se muestren los mensajes de error apropiados
4. Probar con diferentes números de plazas
5. Verificar que la interfaz se actualice correctamente