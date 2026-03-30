<?php
// -------------------------------------------------
// Seguridad: solo ejecución por CLI
// -------------------------------------------------
if (php_sapi_name() !== 'cli') {
    exit;
}

// -------------------------------------------------
// Configuración y conexión BD
// -------------------------------------------------
require_once __DIR__ . '/../config/database.php';

// -------------------------------------------------
// Funciones auxiliares (PASO 4)
// -------------------------------------------------

function recalcInvoiceTotals(PDO $db, int $invoiceId): void
{
    $stmt = $db->prepare("
        SELECT SUM(line_total)
        FROM invoice_items
        WHERE invoice_id = ?
    ");
    $stmt->execute([$invoiceId]);
    $subtotal = (float) $stmt->fetchColumn();

    $taxRate = 21.00;
    $taxAmount = round($subtotal * ($taxRate / 100), 2);
    $total = $subtotal + $taxAmount;

    $db->prepare("
        UPDATE invoices
        SET subtotal = ?, tax_rate = ?, tax_amount = ?, total = ?
        WHERE id = ?
    ")->execute([
        $subtotal,
        $taxRate,
        $taxAmount,
        $total,
        $invoiceId
    ]);
}

// -------------------------------------------------
// Lógica principal (PASO 3)
// -------------------------------------------------

$sql = "
    SELECT *
    FROM subscriptions
    WHERE active = 1
    AND next_billing_date <= CURDATE()
";

$subscriptions = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($subscriptions as $sub) {

    // 1. Crear factura
    $invoiceNumber = generateInvoiceNumber(); // tu función existente

    $stmt = $db->prepare("
        INSERT INTO invoices
        (invoice_number, customer_name, customer_tax_id, customer_email, invoice_date, status)
        VALUES (?, ?, ?, ?, CURDATE(), 'issued')
    ");
    $stmt->execute([
        $invoiceNumber,
        $sub['customer_name'],
        $sub['customer_tax_id'],
        $sub['customer_email']
    ]);

    $invoiceId = (int) $db->lastInsertId();

    // 2. Insertar línea de factura
    $lineTotal = $sub['unit_price'] * $sub['quantity'];

    $stmt = $db->prepare("
        INSERT INTO invoice_items
        (invoice_id, subscription_id, concept_code, concept_name, description,
         quantity, unit_price, line_total, billing_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'monthly')
    ");
    $stmt->execute([
        $invoiceId,
        $sub['id'],
        $sub['concept_code'],
        $sub['concept_name'],
        $sub['description'],
        $sub['quantity'],
        $sub['unit_price'],
        $lineTotal
    ]);

    // 3. Calcular totales
    recalcInvoiceTotals($db, $invoiceId);

    // 4. Actualizar próxima fecha de facturación
    $db->prepare("
        UPDATE subscriptions
        SET next_billing_date = DATE_ADD(next_billing_date, INTERVAL 1 MONTH)
        WHERE id = ?
    ")->execute([$sub['id']]);
}
