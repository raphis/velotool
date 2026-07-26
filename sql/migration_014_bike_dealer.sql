-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).

ALTER TABLE bikes
    ADD COLUMN dealer VARCHAR(150) NULL COMMENT 'Haendler/Geschaeft, wo das Velo gekauft wurde' AFTER purchase_price_currency;
