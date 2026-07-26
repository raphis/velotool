-- Seed: Thule Chariot Cab 2 (Fahrradanhänger) -- kein "Velo", aber dieselbe
-- Struktur (Komponenten/Wartung/Ersatzteile) passt genauso fuer Anhänger.
INSERT INTO bikes (name, brand, model, owner_person_id, notes)
VALUES (
    'Thule Cab2',
    'Thule',
    'Chariot Cab 2',
    (SELECT id FROM people WHERE name = 'Raphael Thoma'),
    'Fahrradanhänger. Kompatibel via Steckachsen-Adapter (siehe Radon-Komponente "Zubehör: Steckachse für Fahrradanhänger").'
);

SET @bike_id = LAST_INSERT_ID();

INSERT INTO components (bike_id, category, name, manufacturer, details) VALUES
(@bike_id, 'Reifen', 'Road Cruiser, 20x1.75, Starr, schwarz', 'Schwalbe', 'Art.-Nr. 56714 (velofactory.ch).');

INSERT INTO parts_catalog (name, manufacturer, supplier, price_chf, stock_qty) VALUES
('Road Cruiser, 20x1.75, Starr, schwarz', 'Schwalbe', 'velofactory.ch', 8.90, 2);

INSERT INTO parts_catalog_bikes (catalog_item_id, bike_id)
VALUES (LAST_INSERT_ID(), @bike_id);
