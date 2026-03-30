<?php
/**
 * Diagnóstico del Sistema de Facturación
 * Verifica la estructura de tablas y datos de billing_concepts
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Diagnóstico del Sistema de Facturación</h1>";

try {
    $pdo = getDBConnection();
    
    // 1. Verificar si existen las tablas del sistema de facturación
    echo "<h2>1. Verificación de Tablas</h2>";
    
    $tables = [
        'billing_concepts',
        'billing_profiles',
        'subscriptions',
        'invoices',
        'invoice_items',
        'payments',
        'membership_plans',
        'user_subscriptions',
        'membership_upgrade_intents'
    ];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Tabla <strong>$table</strong> existe</p>";
            
            // Mostrar estructura
            $structure = $pdo->query("DESCRIBE $table")->fetchAll();
            echo "<details><summary>Ver estructura</summary><pre>";
            print_r($structure);
            echo "</pre></details>";
        } else {
            echo "<p style='color: red;'>✗ Tabla <strong>$table</strong> NO existe</p>";
        }
    }
    
    // 2. Verificar billing_concepts 12 y 15
    echo "<h2>2. Billing Concepts 12 y 15</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'billing_concepts'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT * FROM billing_concepts WHERE id IN (12, 15)");
        $concepts = $stmt->fetchAll();
        
        if (count($concepts) > 0) {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Monto</th><th>Tipo</th></tr>";
            foreach ($concepts as $concept) {
                echo "<tr>";
                echo "<td>{$concept['id']}</td>";
                echo "<td>{$concept['concept_name']}</td>";
                echo "<td>{$concept['description']}</td>";
                echo "<td>{$concept['amount']} €</td>";
                echo "<td>{$concept['billing_type']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠ No se encontraron billing_concepts con ID 12 o 15</p>";
            
            // Mostrar todos los billing_concepts disponibles
            $stmt = $pdo->query("SELECT * FROM billing_concepts ORDER BY id");
            $all_concepts = $stmt->fetchAll();
            
            echo "<h3>Billing Concepts Disponibles:</h3>";
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Monto</th><th>Tipo</th></tr>";
            foreach ($all_concepts as $concept) {
                echo "<tr>";
                echo "<td>{$concept['id']}</td>";
                echo "<td>{$concept['concept_name']}</td>";
                echo "<td>{$concept['description']}</td>";
                echo "<td>{$concept['amount']} €</td>";
                echo "<td>{$concept['billing_type']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // 3. Verificar membership_plans
    echo "<h2>3. Planes de Membresía</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'membership_plans'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT * FROM membership_plans");
        $plans = $stmt->fetchAll();
        
        if (count($plans) > 0) {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Precio Mensual</th><th>Precio Anual</th><th>Popular</th><th>Activo</th></tr>";
            foreach ($plans as $plan) {
                echo "<tr>";
                echo "<td>{$plan['id']}</td>";
                echo "<td>{$plan['name']}</td>";
                echo "<td>{$plan['price_monthly']} €</td>";
                echo "<td>{$plan['price_yearly']} €</td>";
                echo "<td>" . ($plan['is_popular'] ? 'Sí' : 'No') . "</td>";
                echo "<td>" . ($plan['is_active'] ? 'Sí' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠ No hay planes de membresía configurados</p>";
        }
    }
    
    // 4. Verificar usuarios con membresía Premium
    echo "<h2>4. Usuarios con Membresía</h2>";
    
    $stmt = $pdo->query("
        SELECT id, email, first_name, last_name, membership_type, membership_status, created_at
        FROM users
        WHERE membership_type IS NOT NULL
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Nombre</th><th>Membresía</th><th>Estado</th><th>Fecha</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>{$user['membership_type']}</td>";
            echo "<td>{$user['membership_status']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay usuarios con membresía configurada</p>";
    }
    
    echo "<h2>5. Resumen</h2>";
    echo "<p><strong>Estado del sistema:</strong></p>";
    echo "<ul>";
    echo "<li>Base de datos: <strong>" . DB_NAME . "</strong></li>";
    echo "<li>Host: <strong>" . DB_HOST . "</strong></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
