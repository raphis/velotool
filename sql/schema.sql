-- Velotool DB schema
-- Charset/collation chosen for umlaut support

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    google_sub VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    picture_url VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS people (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bikes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_person_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(100) NULL,
    model VARCHAR(150) NULL,
    model_year SMALLINT NULL,
    frame_size VARCHAR(50) NULL,
    color VARCHAR(100) NULL,
    serial_number VARCHAR(100) NULL,
    purchase_date DATE NULL,
    purchase_price DECIMAL(10,2) NULL,
    weight_kg DECIMAL(5,2) NULL,
    notes TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bikes_owner FOREIGN KEY (owner_person_id) REFERENCES people(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS components (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bike_id INT UNSIGNED NOT NULL,
    category VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    manufacturer VARCHAR(150) NULL,
    details TEXT NULL,
    installed_date DATE NULL,
    removed_date DATE NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_components_bike FOREIGN KEY (bike_id) REFERENCES bikes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bike_id INT UNSIGNED NOT NULL,
    log_date DATE NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    mileage_km INT UNSIGNED NULL,
    cost DECIMAL(10,2) NULL,
    performed_by VARCHAR(150) NULL,
    chain_wear_percent DECIMAL(3,2) UNSIGNED NULL COMMENT 'Kettenverschleiss-Messung, z.B. 0.5 / 0.75 / 1.0 (%)',
    disc_thickness_front_mm DECIMAL(4,2) UNSIGNED NULL,
    disc_thickness_rear_mm DECIMAL(4,2) UNSIGNED NULL,
    pad_condition_front_percent TINYINT UNSIGNED NULL COMMENT 'Bremsbelag vorne, geschaetzter Restzustand in %',
    pad_condition_rear_percent TINYINT UNSIGNED NULL COMMENT 'Bremsbelag hinten, geschaetzter Restzustand in %',
    other_measurements VARCHAR(255) NULL COMMENT 'Freitext fuer weitere Kennzahlen',
    created_by_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_bike FOREIGN KEY (bike_id) REFERENCES bikes(id) ON DELETE CASCADE,
    CONSTRAINT fk_logs_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ein Wartungseintrag kann mehrere Komponenten betreffen (z.B. Service:
-- Kette + Bremsscheibe vorne + Bremsscheibe hinten in einem Eintrag).
CREATE TABLE IF NOT EXISTS maintenance_log_components (
    log_id INT UNSIGNED NOT NULL,
    component_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (log_id, component_id),
    CONSTRAINT fk_mlc_log FOREIGN KEY (log_id) REFERENCES maintenance_logs(id) ON DELETE CASCADE,
    CONSTRAINT fk_mlc_component FOREIGN KEY (component_id) REFERENCES components(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parts_catalog (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    manufacturer VARCHAR(150) NULL,
    price_chf DECIMAL(6,2) NULL,
    note VARCHAR(255) NULL,
    stock_qty INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Wieviele Stueck aktuell zuhause auf Lager sind',
    stock_note VARCHAR(255) NULL COMMENT 'z.B. "1x reserviert fuers Radon"',
    for_bike_id INT UNSIGNED NULL COMMENT 'Passt nur zu einem bestimmten Velo (z.B. Bremsbelag-Modell), sonst NULL = universell',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_catalog_bike FOREIGN KEY (for_bike_id) REFERENCES bikes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Welche Katalog-Teile (aus dem Lager) bei einem Wartungseintrag verbraucht wurden.
CREATE TABLE IF NOT EXISTS maintenance_log_parts (
    log_id INT UNSIGNED NOT NULL,
    catalog_item_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (log_id, catalog_item_id),
    CONSTRAINT fk_mlp_log FOREIGN KEY (log_id) REFERENCES maintenance_logs(id) ON DELETE CASCADE,
    CONSTRAINT fk_mlp_catalog FOREIGN KEY (catalog_item_id) REFERENCES parts_catalog(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parts_needed (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bike_id INT UNSIGNED NOT NULL,
    component_id INT UNSIGNED NULL,
    catalog_item_id INT UNSIGNED NULL,
    part_name VARCHAR(255) NOT NULL,
    reason TEXT NULL,
    status ENUM('needed','ordered','installed') NOT NULL DEFAULT 'needed',
    priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
    price_chf DECIMAL(6,2) NULL,
    ordered_date DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_parts_bike FOREIGN KEY (bike_id) REFERENCES bikes(id) ON DELETE CASCADE,
    CONSTRAINT fk_parts_component FOREIGN KEY (component_id) REFERENCES components(id) ON DELETE SET NULL,
    CONSTRAINT fk_parts_catalog FOREIGN KEY (catalog_item_id) REFERENCES parts_catalog(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
