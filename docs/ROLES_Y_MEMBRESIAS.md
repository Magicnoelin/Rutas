# Lógica de Roles y Membresías - Rutas Rurales

## 1. Reglas de Asignación
* **Límite Gratuito:** Un usuario puede tener un máximo de 2 roles vinculados a planes con `price_monthly = 0`.
* **Protección de Datos:** No se puede eliminar el rol 'Alojamiento' si existen registros vinculados en la tabla `alojamientos`.

## 2. Estados de Membresía
* `active`: Acceso total a las funciones del rol.
* `expired`: El sistema bloquea el acceso al menú del Dashboard.
* `pending`: Esperando confirmación de pago (Stripe).

## 3. Política de Downgrade (De Pago a Gratuito)
Si un usuario con 3 alojamientos pasa a un plan que solo permite 1:
1. Todos los alojamientos pasan a estado `invisible`.
2. Se requiere que el usuario marque el ID que desea mantener activo.
3. Los excedentes permanecen en la base de datos (estado `archived`) por 30 días antes de ofrecer la eliminación definitiva.
 
## 4. Validación de Integridad Multitabla
Para evitar registros "huérfanos" (registros sin un dueño con el rol activo), se aplican las siguientes restricciones de borrado:

* **Rol Alojamiento:** Bloqueado si existen registros en `accommodations`.
* **Rol Actividades:** Bloqueado si existen registros en `activities`.
* **Rol Lugares:** Bloqueado si existen registros en `places_of_interest`.
* **Rol Eventos:** Bloqueado si existen registros en `cultural_events`.

*Nota: Antes de desactivar un rol, el usuario debe vaciar su contenido correspondiente.*