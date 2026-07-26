-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).
-- Converts parts_catalog.for_bike_id (single, nullable FK) into a proper
-- many-to-many, since a part (e.g. a saddle) can fit more than one bike.

CREATE TABLE IF NOT EXISTS parts_catalog_bikes (
    catalog_item_id INT UNSIGNED NOT NULL,
    bike_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (catalog_item_id, bike_id),
    CONSTRAINT fk_pcb_catalog FOREIGN KEY (catalog_item_id) REFERENCES parts_catalog(id) ON DELETE CASCADE,
    CONSTRAINT fk_pcb_bike FOREIGN KEY (bike_id) REFERENCES bikes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO parts_catalog_bikes (catalog_item_id, bike_id)
SELECT id, for_bike_id FROM parts_catalog WHERE for_bike_id IS NOT NULL;

ALTER TABLE parts_catalog DROP FOREIGN KEY fk_catalog_bike;
ALTER TABLE parts_catalog DROP COLUMN for_bike_id;
