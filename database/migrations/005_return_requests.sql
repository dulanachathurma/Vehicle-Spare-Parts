-- =====================================================================
-- database/migrations/005_return_requests.sql
--
-- Adds a customer-facing return/refund request flow for Delivered
-- orders. The homepage's "Why Shop With Us" section promises "Easy
-- Returns - hassle-free returns within 7 days", but until now nothing
-- backed that up: a customer could only cancel an order before it
-- shipped, and only an admin could issue a refund manually with no
-- customer-initiated trigger. One return request per order; approving
-- it reuses the existing refund mechanism (payment.status set to
-- 'Refunded' via adminProcessRefund(), same as a manual admin refund).
-- Per Section 3, Rule 3, schema.sql itself is never edited - this is a
-- new numbered migration instead.
-- =====================================================================

USE vspms_db;

CREATE TABLE IF NOT EXISTS return_request (
    returnRequestID INT AUTO_INCREMENT PRIMARY KEY,
    orderID         INT NOT NULL UNIQUE,
    userID          INT NOT NULL,
    adminID         INT NULL,
    reasonCategory  VARCHAR(50) NOT NULL,
    description     TEXT NULL,
    status          ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    requestedAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolvedAt      DATETIME NULL,
    adminNotes      TEXT NULL,
    CONSTRAINT fk_return_order FOREIGN KEY (orderID) REFERENCES orders(orderID) ON DELETE CASCADE,
    CONSTRAINT fk_return_user  FOREIGN KEY (userID)  REFERENCES registered_user(userID),
    CONSTRAINT fk_return_admin FOREIGN KEY (adminID) REFERENCES admin(adminID) ON DELETE SET NULL,
    INDEX idx_return_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
