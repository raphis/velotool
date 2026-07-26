-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).

ALTER TABLE bikes
    ADD COLUMN purchase_price_currency ENUM('CHF','EUR') NOT NULL DEFAULT 'CHF' AFTER purchase_price;
