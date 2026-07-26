-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).

CREATE TABLE IF NOT EXISTS parts_catalog (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    manufacturer VARCHAR(150) NULL,
    price_chf DECIMAL(6,2) NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE parts_needed
    ADD COLUMN catalog_item_id INT UNSIGNED NULL AFTER component_id,
    ADD COLUMN price_chf DECIMAL(6,2) NULL AFTER priority,
    ADD CONSTRAINT fk_parts_catalog FOREIGN KEY (catalog_item_id) REFERENCES parts_catalog(id) ON DELETE SET NULL;
