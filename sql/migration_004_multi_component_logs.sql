-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).
-- A maintenance entry can affect several components at once (e.g. a service
-- touching chain + front disc + rear disc in one go) -- move from a single
-- nullable FK to a many-to-many join table.

CREATE TABLE IF NOT EXISTS maintenance_log_components (
    log_id INT UNSIGNED NOT NULL,
    component_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (log_id, component_id),
    CONSTRAINT fk_mlc_log FOREIGN KEY (log_id) REFERENCES maintenance_logs(id) ON DELETE CASCADE,
    CONSTRAINT fk_mlc_component FOREIGN KEY (component_id) REFERENCES components(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO maintenance_log_components (log_id, component_id)
SELECT id, component_id FROM maintenance_logs WHERE component_id IS NOT NULL;

ALTER TABLE maintenance_logs DROP FOREIGN KEY fk_logs_component;
ALTER TABLE maintenance_logs DROP COLUMN component_id;
