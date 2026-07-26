-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).

ALTER TABLE parts_catalog
    ADD COLUMN supplier VARCHAR(150) NULL COMMENT 'z.B. velofactory.ch' AFTER manufacturer;

UPDATE parts_catalog SET supplier = 'velofactory.ch' WHERE supplier IS NULL;
