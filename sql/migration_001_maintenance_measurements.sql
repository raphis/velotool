-- Run this once if maintenance_logs already exists without the measurement columns.
-- (Fresh installs get these columns directly via schema.sql.)

ALTER TABLE maintenance_logs
    ADD COLUMN chain_wear_percent DECIMAL(3,2) UNSIGNED NULL COMMENT 'Kettenverschleiss-Messung, z.B. 0.5 / 0.75 / 1.0 (%)' AFTER performed_by,
    ADD COLUMN disc_thickness_front_mm DECIMAL(4,2) UNSIGNED NULL AFTER chain_wear_percent,
    ADD COLUMN disc_thickness_rear_mm DECIMAL(4,2) UNSIGNED NULL AFTER disc_thickness_front_mm,
    ADD COLUMN pad_condition_front_percent TINYINT UNSIGNED NULL COMMENT 'Bremsbelag vorne, geschaetzter Restzustand in %' AFTER disc_thickness_rear_mm,
    ADD COLUMN pad_condition_rear_percent TINYINT UNSIGNED NULL COMMENT 'Bremsbelag hinten, geschaetzter Restzustand in %' AFTER pad_condition_front_percent,
    ADD COLUMN other_measurements VARCHAR(255) NULL COMMENT 'Freitext fuer weitere Kennzahlen' AFTER pad_condition_rear_percent;
