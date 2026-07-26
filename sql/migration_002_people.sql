-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).
-- Splits bike ownership away from the login-users table: only one Google account
-- can log in, but any family member should be selectable as a bike's owner.

CREATE TABLE IF NOT EXISTS people (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE bikes DROP FOREIGN KEY fk_bikes_owner;
ALTER TABLE bikes CHANGE owner_user_id owner_person_id INT UNSIGNED NULL;
ALTER TABLE bikes ADD CONSTRAINT fk_bikes_owner FOREIGN KEY (owner_person_id) REFERENCES people(id) ON DELETE SET NULL;
