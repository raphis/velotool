-- Seed: Croozer Cargo Pakko (Fahrradanhänger) -- kein "Velo", aber dieselbe
-- Struktur (Komponenten/Wartung/Ersatzteile) passt genauso fuer Anhänger.
INSERT INTO bikes (name, brand, model, purchase_date, purchase_price, purchase_price_currency, dealer, owner_person_id, notes)
VALUES (
    'Croozer Cargo Pakko',
    'Croozer',
    'Cargo Pakko',
    '2021-08-31',
    334.80,
    'CHF',
    'HAWK Electronics GmbH',
    (SELECT id FROM people WHERE name = 'Raphael Thoma'),
    'Cargo-Fahrradanhänger. Rechnung HAWK Electronics GmbH Nr. 99756 vom 31.08.2021, Auftragsnr. 3200168135, Art.-Nr. 121000518.'
);
