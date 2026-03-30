-- =====================================================
-- CONFIGURAR USUARIO ADMIN: olgamarin@rutasrurales.io
-- =====================================================
-- Ejecutar este script en phpMyAdmin después de instalar el sistema de moderación

USE u412199647_Rutas;

-- Actualizar usuario a tipo admin
UPDATE users 
SET user_type = 'admin' 
WHERE email = 'olgamarin@rutasrurales.io';

-- Verificar que se actualizó correctamente
SELECT id, email, first_name, last_name, user_type, created_at 
FROM users 
WHERE email = 'olgamarin@rutasrurales.io';

-- Mensaje de confirmación
SELECT 'Usuario olgamarin@rutasrurales.io configurado como ADMIN correctamente' as resultado;
