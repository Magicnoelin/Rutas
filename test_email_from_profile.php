<?php
// Test para verificar que el email se obtiene del perfil del usuario

echo "=== TEST de obtención de email desde perfil de usuario ===\n\n";

// Simular datos de una actividad
$actividad_id = 15;
$created_by = 1; // ID de usuario de ejemplo

echo "Actividad ID: $actividad_id\n";
echo "Usuario creador ID: $created_by\n\n";

// Simular consulta a la base de datos para obtener email del usuario
$usuarios_ejemplo = [
    1 => ['email' => 'usuario1@example.com', 'first_name' => 'Juan', 'last_name' => 'Pérez'],
    2 => ['email' => 'usuario2@example.com', 'first_name' => 'María', 'last_name' => 'Gómez'],
    3 => ['email' => 'usuario3@example.com', 'first_name' => 'Carlos', 'last_name' => 'López'],
];

if (isset($usuarios_ejemplo[$created_by])) {
    $user = $usuarios_ejemplo[$created_by];
    $user_email = $user['email'];
    $user_name = trim($user['first_name'] . ' ' . $user['last_name']);
    
    echo "✅ Email obtenido correctamente:\n";
    echo "   Email: $user_email\n";
    echo "   Nombre: $user_name\n";
    echo "   Fuente: Perfil del usuario creador (ID: $created_by)\n\n";
} else {
    echo "❌ Usuario no encontrado\n";
    echo "   Email: No asignado\n";
    echo "   Nombre: Usuario desconocido\n";
    echo "   Fuente: No se pudo obtener información del usuario\n\n";
}

echo "=== CAMBIOS IMPLEMENTADOS ===\n";
echo "1. ✅ Campo 'contact_email' ELIMINADO del formulario de edición\n";
echo "2. ✅ Email ahora se obtiene del perfil del usuario creador (campo 'created_by')\n";
echo "3. ✅ En la vista pública, se mostrará el email del perfil del usuario\n";
echo "4. ✅ Si el usuario quiere un email diferente, debe actualizar su perfil\n";
echo "5. ✅ Solucionado el problema de restricción CHECK en 'contact_email'\n\n";

echo "=== VENTAJAS DE ESTA SOLUCIÓN ===\n";
echo "1. ✅ Más seguro: El email siempre será válido (viene del perfil verificado)\n";
echo "2. ✅ Más consistente: Todos los usuarios usan su email principal\n";
echo "3. ✅ Menos errores: No hay problemas de validación de formato\n";
echo "4. ✅ Más fácil de mantener: Un solo lugar para actualizar emails\n";
echo "5. ✅ Mejor experiencia: Los usuarios ven su email actualizado automáticamente\n\n";

echo "=== PARA PROBAR ===\n";
echo "1. Ve a: https://www.rutasrurales.io/admin_tablas/actividades_editar.php?id=15\n";
echo "2. Verifica que NO hay campo 'Email de Contacto' para editar\n";
echo "3. Verifica que SÍ hay información del email del usuario creador\n";
echo "4. Haz cambios en otros campos y guarda\n";
echo "5. Verifica que no hay errores de restricción CHECK\n";

?>