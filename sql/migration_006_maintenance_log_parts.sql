-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).

CREATE TABLE IF NOT EXISTS maintenance_log_parts (
    log_id INT UNSIGNED NOT NULL,
    catalog_item_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (log_id, catalog_item_id),
    CONSTRAINT fk_mlp_log FOREIGN KEY (log_id) REFERENCES maintenance_logs(id) ON DELETE CASCADE,
    CONSTRAINT fk_mlp_catalog FOREIGN KEY (catalog_item_id) REFERENCES parts_catalog(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
