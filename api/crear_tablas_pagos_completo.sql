-- ============================================================
-- CREAR TABLAS COMPLETAS DEL SISTEMA DE PAGOS
-- Ejecutar en el servidor de producción si las tablas no existen
-- ============================================================

-- 1. TABLA: payment_intents (intenciones de pago)
CREATE TABLE IF NOT EXISTS `payment_intents` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT UNSIGNED NOT NULL,
    `plan_id`           INT UNSIGNED NOT NULL,
    `stripe_session_id` VARCHAR(255) NOT NULL,
    `stripe_price_id`   VARCHAR(255) DEFAULT NULL,
    `amount`            DECIMAL(10,2) NOT NULL COMMENT 'Precio sin IVA',
    `vat_amount`        DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Importe del IVA',
    `total_amount`      DECIMAL(10,2) NOT NULL COMMENT 'Total con IVA',
    `billing_cycle`     ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    `status`            ENUM('pending','completed','failed','expired','refunded') NOT NULL DEFAULT 'pending',
    `metadata`          JSON DEFAULT NULL,
    `completed_at`      DATETIME DEFAULT NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id`           (`user_id`),
    INDEX `idx_stripe_session_id` (`stripe_session_id`),
    INDEX `idx_status`            (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Intenciones de pago de Stripe';

-- 2. TABLA: user_subscriptions (suscripciones activas)
CREATE TABLE IF NOT EXISTS `user_subscriptions` (
    `id`                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`                 INT UNSIGNED NOT NULL,
    `plan_id`                 INT UNSIGNED NOT NULL,
    `plan_name`               VARCHAR(100) NOT NULL,
    `billing_cycle`           ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    `price`                   DECIMAL(10,2) NOT NULL COMMENT 'Precio sin IVA',
    `vat_amount`              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount`            DECIMAL(10,2) NOT NULL COMMENT 'Total con IVA',
    `currency`                CHAR(3) NOT NULL DEFAULT 'EUR',
    `stripe_subscription_id`  VARCHAR(255) DEFAULT NULL,
    `stripe_customer_id`      VARCHAR(255) DEFAULT NULL,
    `stripe_invoice_id`       VARCHAR(255) DEFAULT NULL,
    `start_date`              DATE NOT NULL,
    `end_date`                DATE DEFAULT NULL,
    `next_billing_date`       DATE DEFAULT NULL,
    `status`                  ENUM('active','pending','canceled','expired','past_due') NOT NULL DEFAULT 'pending',
    `canceled_at`             DATETIME DEFAULT NULL,
    `metadata`                JSON DEFAULT NULL,
    `created_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id`                  (`user_id`),
    INDEX `idx_stripe_subscription_id`   (`stripe_subscription_id`),
    INDEX `idx_status`                   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Suscripciones activas de usuarios';

-- 3. TABLA: invoices (facturas)
CREATE TABLE IF NOT EXISTS `invoices` (
    `id`                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subscription_id`           INT UNSIGNED DEFAULT NULL,
    `user_id`                   INT UNSIGNED NOT NULL,
    `invoice_number`            VARCHAR(50) NOT NULL UNIQUE,
    `invoice_date`              DATE NOT NULL,
    `due_date`                  DATE DEFAULT NULL,
    `subtotal`                  DECIMAL(10,2) NOT NULL,
    `vat_rate`                  DECIMAL(5,2) NOT NULL DEFAULT 21.00,
    `vat_amount`                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount`              DECIMAL(10,2) NOT NULL,
    `stripe_invoice_id`         VARCHAR(255) DEFAULT NULL,
    `stripe_payment_intent_id`  VARCHAR(255) DEFAULT NULL,
    `stripe_receipt_url`        VARCHAR(500) DEFAULT NULL,
    `payment_status`            ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    `paid_at`                   DATETIME DEFAULT NULL,
    `billing_name`              VARCHAR(200) DEFAULT NULL,
    `billing_nif`               VARCHAR(20) DEFAULT NULL,
    `billing_address`           VARCHAR(300) DEFAULT NULL,
    `billing_email`             VARCHAR(200) DEFAULT NULL,
    `description`               TEXT DEFAULT NULL,
    `metadata`                  JSON DEFAULT NULL,
    `created_at`                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id`          (`user_id`),
    INDEX `idx_invoice_number`   (`invoice_number`),
    INDEX `idx_payment_status`   (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Facturas generadas automáticamente';

-- 4. TABLA: payment_failures (fallos de pago)
CREATE TABLE IF NOT EXISTS `payment_failures` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT UNSIGNED NOT NULL,
    `subscription_id`   INT UNSIGNED DEFAULT NULL,
    `stripe_invoice_id` VARCHAR(255) DEFAULT NULL,
    `amount`            DECIMAL(10,2) DEFAULT NULL,
    `failure_reason`    TEXT DEFAULT NULL,
    `metadata`          JSON DEFAULT NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Registro de fallos de pago';

-- 5. COLUMNAS EN users (si no existen)
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `stripe_customer_id`      VARCHAR(255) DEFAULT NULL AFTER `email`,
    ADD COLUMN IF NOT EXISTS `stripe_subscription_id`  VARCHAR(255) DEFAULT NULL AFTER `stripe_customer_id`,
    ADD COLUMN IF NOT EXISTS `membership_type`         VARCHAR(50)  DEFAULT 'free' AFTER `stripe_subscription_id`,
    ADD COLUMN IF NOT EXISTS `membership_status`       ENUM('active','expired','canceled','pending') DEFAULT 'pending' AFTER `membership_type`,
    ADD COLUMN IF NOT EXISTS `membership_start_date`   DATE DEFAULT NULL AFTER `membership_status`,
    ADD COLUMN IF NOT EXISTS `membership_end_date`     DATE DEFAULT NULL AFTER `membership_start_date`;

-- 6. COLUMNAS EN membership_plans (si no existen)
ALTER TABLE `membership_plans`
    ADD COLUMN IF NOT EXISTS `stripe_product_id`        VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `stripe_monthly_price_id`  VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `stripe_yearly_price_id`   VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `plan_type`                VARCHAR(50)  DEFAULT 'alojamiento',
    ADD COLUMN IF NOT EXISTS `status`                   ENUM('active','inactive') DEFAULT 'active';

-- 7. VERIFICAR QUE EL PLAN DE TEST TIENE PRECIO MÍNIMO (Stripe requiere >= 0.50€)
-- Si tienes un plan con precio 0.01€, actualízalo a mínimo 0.50€
-- UPDATE membership_plans SET price_monthly = 0.50, price_yearly = 0.50 WHERE price_monthly < 0.50 AND price_monthly > 0;

SELECT '✅ Tablas del sistema de pagos creadas/verificadas correctamente' AS resultado;
SELECT id, name, price_monthly, price_yearly, plan_type, status FROM membership_plans ORDER BY id;
