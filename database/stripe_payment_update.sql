-- =====================================================================
-- database/stripe_payment_update.sql
--
-- Adds Stripe tracking columns to the payment table and registers
-- the Stripe payment gateway in payment_gateway table.
-- Preserves existing database structure and existing records.
-- =====================================================================

USE vspms_db;

-- 1. Add stripe_session_id and stripe_payment_intent_id to payment table
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'vspms_db' AND TABLE_NAME = 'payment' AND COLUMN_NAME = 'stripe_session_id');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE payment 
        ADD COLUMN stripe_session_id VARCHAR(255) NULL AFTER transactionID,
        ADD COLUMN stripe_payment_intent_id VARCHAR(255) NULL AFTER stripe_session_id,
        ADD INDEX idx_stripe_session (stripe_session_id),
        ADD INDEX idx_stripe_payment_intent (stripe_payment_intent_id)', 
    'SELECT "stripe columns already exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Register Stripe in payment_gateway table
INSERT INTO payment_gateway (gatewayName, apiEndpoint, isActive, transactionFeeRate)
SELECT 'Stripe', 'https://api.stripe.com', 1, 2.90
WHERE NOT EXISTS (SELECT 1 FROM payment_gateway WHERE gatewayName = 'Stripe');
