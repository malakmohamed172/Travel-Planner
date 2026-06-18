-- Run once on travel_planner (XAMPP MySQL)
-- Extends payment for "successful" status and traveler/card metadata (demo-safe: last 4 only, no CVV stored)

ALTER TABLE payment
  MODIFY COLUMN status ENUM('pending','paid','failed','successful') NOT NULL DEFAULT 'pending';

-- If any ADD COLUMN fails with "Duplicate column", skip that line (already applied).

ALTER TABLE payment
  ADD COLUMN traveler_full_name VARCHAR(255) NULL AFTER booking_id;

ALTER TABLE payment
  ADD COLUMN cardholder_name VARCHAR(255) NULL AFTER traveler_full_name;

ALTER TABLE payment
  ADD COLUMN card_last4 CHAR(4) NULL AFTER cardholder_name;

ALTER TABLE payment
  ADD COLUMN card_expiry CHAR(5) NULL COMMENT 'MM/YY' AFTER card_last4;
