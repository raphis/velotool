-- Zusaetzliche Katalog-Teile, nur fuers Stöckli (velofactory.ch).
INSERT INTO parts_catalog (name, manufacturer, supplier, price_chf, note, stock_qty) VALUES
('Contact Plus, 26x1.75, mit Reflexstreifen, schwarz', 'Continental', 'velofactory.ch', 26.80, NULL, 0),
('Kette CN-E6090-10, 10-Gang, 138 Glieder, Box', 'Shimano', 'velofactory.ch', 34.90, 'Unsicher ob noch 1 Stk. an Lager -- bitte verifizieren.', 1);

INSERT INTO parts_catalog_bikes (catalog_item_id, bike_id)
SELECT c.id, (SELECT id FROM bikes WHERE model = 'e.t. 1 Urban')
FROM parts_catalog c
WHERE c.name IN (
    'Contact Plus, 26x1.75, mit Reflexstreifen, schwarz',
    'Kette CN-E6090-10, 10-Gang, 138 Glieder, Box'
);
