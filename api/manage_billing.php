<?php
/**
 * API: Gestionar Facturación - Stripe Customer Portal
 * GET /api/manage_billing.php
 *
 * Crea una sesión del Stripe Billing Portal para que el usuario pueda:
 *   - Ver y descargar sus facturas
 *   - Cancelar su suscripción anual/mensual
 *   - Cambiar el método de pago
 *   - Cambiar el ciclo de facturación
 *
 * Retorna la URL del portal de Stripe (válida ~15 min).
 * El usuario es redirigido a esa URL desde el frontend.
 *
 * REQUISITO EN STRIPE DASHBOARD:
 *   Asegúrate de que el Customer Portal esté activado en:
 *   https://dashboard.stripe.com/settings/billing/portal
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'stripe_config.php';

// ── Verificar autenticación ──────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión para gestionar tu facturación', 401);
}

// ── Solo GET o POST ──────────────────────────────────────────────────────────
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    jsonError('Método no permitido', 405);
}

try {
    $userId = (int) $_SESSION['user_id'];
    $pdo    = getDBConnection();

    // ── Obtener datos del usuario ────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, last_name,
               stripe_customer_id, stripe_subscription_id,
               membership_type, membership_status, membership_end_date
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonError('Usuario no encontrado', 404);
    }

    // ── Verificar que tiene suscripción activa ───────────────────────────────
    $membershipType   = $user['membership_type']   ?? 'free';
    $membershipStatus = $user['membership_status'] ?? '';

    if (empty($membershipType) || $membershipType === 'free') {
        jsonError(
            'No tienes una suscripción activa para gestionar. ' .
            'Si quieres contratar un plan Premium, ve a la sección "Mi Membresía".',
            400
        );
    }

    $stripeCustomerId = $user['stripe_customer_id'] ?? '';

    // ── Si no tenemos stripe_customer_id, buscarlo en Stripe por email ──────
    if (empty($stripeCustomerId)) {
        error_log("manage_billing.php: user=$userId no tiene stripe_customer_id, buscando por email...");

        $searchResult = stripeRequest('GET', 'customers/search', [
            'query' => 'email:"' . $user['email'] . '"',
            'limit' => 1,
        ]);

        // Fallback: buscar con list si la búsqueda avanzada falla
        if (!$searchResult || isset($searchResult['error'])) {
            $searchResult = stripeRequest('GET', 'customers', [
                'email' => $user['email'],
                'limit' => 1,
            ]);
        }

        if ($searchResult && !isset($searchResult['error'])
            && isset($searchResult['data']) && count($searchResult['data']) > 0
        ) {
            $stripeCustomerId = $searchResult['data'][0]['id'];

            // Guardar para futuras llamadas
            $pdo->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?")
                ->execute([$stripeCustomerId, $userId]);

            error_log("manage_billing.php: stripe_customer_id encontrado y guardado: $stripeCustomerId (user=$userId)");
        } else {
            // Último recurso: informar al usuario que contacte con soporte
            error_log("manage_billing.php: No se encontró cliente Stripe para user=$userId email={$user['email']}");
            jsonError(
                'No encontramos tu suscripción en el sistema de pagos. ' .
                'Por favor, contacta con soporte en olgamarin@rutasrurales.io indicando tu email y ' .
                'te ayudaremos a gestionar tu facturación manualmente.',
                404
            );
        }
    }

    // ── Construir URL de retorno ─────────────────────────────────────────────
    $returnUrl = 'https://rutasrurales.io/user-dashboard.html?billing=returned';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $proto     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $returnUrl = $proto . '://' . $_SERVER['HTTP_HOST'] . '/user-dashboard.html?billing=returned';
    }

    // ── Crear sesión del Customer Portal en Stripe ───────────────────────────
    $portalSession = stripeRequest('POST', 'billing_portal/sessions', [
        'customer'   => $stripeCustomerId,
        'return_url' => $returnUrl,
    ]);

    if (!$portalSession || isset($portalSession['error'])) {
        $errMsg = $portalSession['error']['message']
            ?? 'No se pudo crear la sesión del portal de Stripe';
        error_log("manage_billing.php: Stripe error para user=$userId: $errMsg");
        jsonError(
            'Error al abrir el portal de facturación: ' . $errMsg . '. ' .
            'Si el problema persiste, contacta con olgamarin@rutasrurales.io',
            500
        );
    }

    $portalUrl = $portalSession['url'] ?? '';
    if (empty($portalUrl)) {
        jsonError('No se obtuvo la URL del portal de Stripe', 500);
    }

    error_log("manage_billing.php: Portal session OK para user=$userId customer=$stripeCustomerId");

    // ── Respuesta exitosa ────────────────────────────────────────────────────
    jsonSuccess([
        'portal_url'      => $portalUrl,
        'expires_at'      => date('c', strtotime('+15 minutes')),
        'membership_type' => $membershipType,
        'customer_id'     => $stripeCustomerId,
    ], 'Portal de facturación listo. Redirigiendo...');

} catch (PDOException $e) {
    error_log('manage_billing.php PDO Error: ' . $e->getMessage());
    jsonError('Error de base de datos. Inténtalo de nuevo o contacta con soporte.', 500);
} catch (Exception $e) {
    error_log('manage_billing.php General Error: ' . $e->getMessage());
    jsonError('Error interno: ' . $e->getMessage(), 500);
}
?>
