-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).
-- "Seriennummer" was really the frame number all along -- renamed for clarity.

ALTER TABLE bikes CHANGE serial_number frame_number VARCHAR(100) NULL COMMENT 'Rahmennummer';
