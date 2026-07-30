-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).

ALTER TABLE bikes
    ADD COLUMN is_sold TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active,
    ADD COLUMN sold_date DATE NULL AFTER is_sold;
