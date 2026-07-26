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
