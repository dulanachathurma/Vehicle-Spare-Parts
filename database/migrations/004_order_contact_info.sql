-- =====================================================================
-- database/migrations/004_order_contact_info.sql
--
-- Checkout previously only asked for a shipping address. Adds a
-- recipient name and phone number to each order, captured fresh at
-- checkout (not just pulled from the account, since an order can be
-- placed for someone else) - so admin order views and delivery can
-- reach the right person. Per Section 3, Rule 3, schema.sql itself is
-- never edited - this is a new numbered migration instead.
--
-- Run once against an existing database. A fresh schema.sql import
-- does not need this file re-run once its ADD COLUMNs are folded in,
-- but schema.sql stays frozen, so for now this always runs after it.
-- =====================================================================

USE vspms_db;

ALTER TABLE orders
    ADD COLUMN recipientName  VARCHAR(150) NOT NULL DEFAULT '' AFTER userID,
    ADD COLUMN recipientPhone VARCHAR(20)  NOT NULL DEFAULT '' AFTER recipientName;
