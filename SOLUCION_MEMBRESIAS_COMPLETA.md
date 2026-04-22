# SOLUCIÓN COMPLETA PARA SISTEMA DE MEMBRESÍAS

## Problemas Identificados

1. **Base de datos con datos incorrectos**: La tabla `membership_plans` tiene:
   - Plan ID 2: Nombre "Premium" en lugar de "Básico Alojamiento"
   - Plan ID 2: Precio 9.99€ en lugar de 10.00€
   - Nombres en inglés ("Free", "Premium", "Business") en lugar de español

2. **Tabla `user_memberships` no existe**: Mencionada en el diagnóstico pero no creada

3. **API devuelve datos incorrectos**: `get_membership_options.php` devuelve datos erróneos desde la BD

4. **Checkout genera URLs incorrectas**: `create_checkout_session.php` genera URLs con nombres de planes incorrectos

## Soluciones Implementadas

### 1. Script SQL para corregir datos (`fix_membership_plans.sql`)

```sql
-- Corrige todos los planes de membresía
UPDATE membership_plans SET 
    name = CASE id
        WHEN 1 THEN 'Gratuito Alojamiento'
        WHEN 2 THEN 'Básico Alojamiento'
        WHEN 3 THEN 'Premium Alojamiento'
    END,
    price_monthly = CASE id
        WHEN 1 THEN 0.00
        WHEN 2 THEN 10.00
        WHEN 3 THEN 10.00
    END,
    price_yearly = CASE id
        WHEN 1 THEN 0.00
        WHEN 2 THEN 50.00
        WHEN 3 THEN 100.00
    END,
    -- ... (descripciones, características, límites)
WHERE id IN (1, 2, 3);
```

**Ejecutar en producción:**
```bash
mysql -u usuario -p nombre_base_datos < fix_membership_plans.sql
```

### 2. Crear tabla `user_memberships` (si no existe)

El script SQL también crea la tabla `user_memberships` que falta.

### 3. Verificación de archivos API

Los archivos API están correctos:
- `api/get_membership_options.php`: Tiene planes por defecto correctos
- `api/create_checkout_session.php`: Genera URLs correctamente
- `api/stripe_config.php`: En modo simulado (placeholder keys)

### 4. Archivo `simulated-checkout.html`

El archivo existe y funciona correctamente:
- Parsea parámetros de URL con `URLSearchParams`
- Muestra información del plan correctamente
- Simula proceso de pago

## Pasos para Implementar en Producción

### Paso 1: Ejecutar script SQL en producción
1. Acceder a phpMyAdmin o línea de comandos MySQL
2. Ejecutar el script `fix_membership_plans.sql`
3. Verificar que los datos sean correctos:
   ```sql
   SELECT id, name, price_monthly, price_yearly FROM membership_plans ORDER BY id;
   ```

### Paso 2: Subir archivos actualizados al servidor
1. Asegurarse de que `simulated-checkout.html` esté en la raíz del sitio
2. Verificar que los archivos API estén actualizados:
   - `api/get_membership_options.php`
   - `api/create_checkout_session.php`
   - `api/stripe_config.php`

### Paso 3: Limpiar caché del navegador
Instruir a los usuarios a presionar `Ctrl+F5` para limpiar caché.

### Paso 4: Probar el sistema
1. Acceder a `https://rutasrurales.io/diagnostico_membresias.php`
2. Verificar que todos los checks sean ✅
3. Probar el flujo completo de checkout

## Verificación Final

Después de implementar las soluciones, verificar:

1. ✅ Base de datos con datos correctos
2. ✅ API devuelve planes correctos
3. ✅ Checkout genera URLs correctas
4. ✅ Página simulada de checkout funciona
5. ✅ Tabla `user_memberships` existe

## Archivos Creados/Modificados

1. `fix_membership_plans.sql` - Script SQL para corregir datos
2. `test_membership_api.php` - Script de prueba (opcional)
3. `SOLUCION_MEMBRESIAS_COMPLETA.md` - Esta documentación

## Notas Importantes

- **Stripe en modo simulado**: Las claves de Stripe son placeholders (`sk_live_...`). Para activar pagos reales, reemplazar con claves reales en `api/stripe_config.php`.
- **Cache**: Los usuarios deben limpiar caché del navegador (`Ctrl+F5`).
- **Monitoreo**: Revisar logs de error después de implementar cambios.

## Resultado Esperado

Después de aplicar todas las soluciones:
- Plan ID 2 se mostrará como "Básico Alojamiento" (no "Premium")
- Precio será 10.00€ (no 9.99€)
- URLs de checkout tendrán parámetros correctos
- Sistema de membresías funcionará correctamente en modo simulado