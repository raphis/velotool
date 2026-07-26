-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).

ALTER TABLE bikes
    ADD COLUMN registration_number VARCHAR(50) NULL COMMENT 'Kontrollschild-Nr. Strassenverkehrsamt (bei S-Pedelec)' AFTER frame_number;
