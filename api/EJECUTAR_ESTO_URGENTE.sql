-- =====================================================
-- ACTUALIZAR USUARIO 97 (Olga Marin) A ADMIN
-- =====================================================
-- Tu user_id es 97 y actualmente eres tipo "alojamiento"
-- Este script te cambia a "admin" para acceder al panel de moderación

USE u412199647_Rutas;

-- Actualizar tu usuario específico (ID 97)
UPDATE users 
SET user_type = 'admin' 
WHERE id = 97;

-- Verificar el cambio
SELECT id, email, first_name, last_name, user_type, created_at 
FROM users 
WHERE id = 97;

-- Mensaje de confirmación
SELECT 'Usuario ID 97 (Olga Marin) actualizado a ADMIN correctamente' as resultado;

-- IMPORTANTE: Después de ejecutar esto:
-- 1. Cierra sesión: https://rutasrurales.io/api/logout.php
-- 2. Vuelve a iniciar sesión
-- 3. Verifica: https://rutasrurales.io/api/test_moderation_session.php
-- 4. Accede al panel: https://rutasrurales.io/admin_tablas/moderacion_alojamientos.php
