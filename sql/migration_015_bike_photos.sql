-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).

CREATE TABLE IF NOT EXISTS bike_photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bike_id INT UNSIGNED NOT NULL,
    filename VARCHAR(64) NOT NULL COMMENT 'Dateiname in uploads/bikes/, serverseitig generiert',
    original_name VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bike_photos_bike FOREIGN KEY (bike_id) REFERENCES bikes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
