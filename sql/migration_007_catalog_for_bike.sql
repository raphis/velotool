-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).

ALTER TABLE parts_catalog
    ADD COLUMN for_bike_id INT UNSIGNED NULL COMMENT 'Passt nur zu einem bestimmten Velo (z.B. Bremsbelag-Modell), sonst NULL = universell' AFTER stock_note,
    ADD CONSTRAINT fk_catalog_bike FOREIGN KEY (for_bike_id) REFERENCES bikes(id) ON DELETE SET NULL;

-- G05S-Bremsbeläge sind fürs Stöckli e.t. 1 Urban vorgesehen.
UPDATE parts_catalog
SET for_bike_id = (SELECT id FROM bikes WHERE model = 'e.t. 1 Urban')
WHERE name LIKE '%G05S%';
