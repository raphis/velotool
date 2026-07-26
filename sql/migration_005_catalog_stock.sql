-- Run once against an already-deployed DB (fresh installs get this via schema.sql directly).

ALTER TABLE parts_catalog
    ADD COLUMN stock_qty INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Wieviele Stueck aktuell zuhause auf Lager sind' AFTER note,
    ADD COLUMN stock_note VARCHAR(255) NULL COMMENT 'z.B. "1x reserviert fuers Radon"' AFTER stock_qty;

-- Bereits vorhandener Bestand: eine Kette fuers Radon liegt bereits zuhause.
UPDATE parts_catalog SET stock_qty = 1, stock_note = 'Reserviert fürs Radon'
WHERE name LIKE 'Deore Kette CN-M6100%';
