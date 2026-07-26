-- Seed: Stöckli e.t. 1 Urban
-- Basiert auf dem Original-Kassenzettel (Stöckli Swiss Sports AG, Bon 24048,
-- P-Bon Montage/Bike 132028950, 12.05.2015) -- Artikel-Nr./Serien-Nr. siehe
-- components.details. Bremsen/Schaltung sind auf dem Beleg nicht separat
-- aufgefuehrt (im Rahmenpreis enthalten) -- bitte am Velo pruefen und ergaenzen.
INSERT INTO bikes (name, brand, model, color, frame_size, purchase_date, purchase_price, purchase_price_currency, owner_person_id, notes)
VALUES (
    'Stöckli e.t. 1 Urban',
    'Stöckli',
    'e.t. 1 Urban',
    NULL,
    'L',
    '2015-05-12',
    5100.00,
    'CHF',
    (SELECT id FROM people WHERE name = 'Raphael Thoma'),
    'Urban-Ausstattung mit Schutzblech, Gepäckträger, Zentralständer, Beleuchtung. Kassenzettel Stöckli Swiss Sports AG vom 12.05.2015 (Bon 24048, P-Bon Montage/Bike 132028950) liegt vor, Details siehe Komponenten. Batterie-Entsorgungsgebühr CHF 20.00 war Teil der Rechnung (kein Bike-Teil). Stöckli hat die E-Bike-Sparte 2018 eingestellt, Service/Ersatzteile für den Go-SwissDrive-Motor laufen seither über youmo.ch.'
);

SET @bike_id = LAST_INSERT_ID();

INSERT INTO components (bike_id, category, name, manufacturer, details) VALUES
(@bike_id, 'Motor',        'Nabenmotor Heck, 500 Watt',                 'Go SwissDrive', 'Art.-Nr. 43100212, Serien-Nr. 100109841039 (Kassenzettel 12.05.2015).'),
(@bike_id, 'Akku',         '834 Wh, 415mm',                              'Go SwissDrive', 'Art.-Nr. 43100913, Serien-Nr. 01441271-01522 (Kassenzettel 12.05.2015). Im Sattelrohr integriert.'),
(@bike_id, 'Ladegerät',    'Ladegerät 4A',                               'Go SwissDrive', 'Art.-Nr. 43100712 (Kassenzettel 12.05.2015).'),
(@bike_id, 'Gabel',        'Starrgabel',                                 NULL,            'Nicht separat auf dem Kassenzettel aufgefuehrt (im Rahmenpreis enthalten), bei e.t. Cross-Variante gegen Federgabel ersetzbar.'),
(@bike_id, 'Display',      'Go SwissDrive Standard-Display',            'Go SwissDrive', 'Nicht separat auf dem Kassenzettel aufgefuehrt (im Rahmenpreis enthalten).'),
(@bike_id, 'Scheinwerfer', 'Lumotec IQ Cyo T',                           'Busch & Müller', 'Art.-Nr. 133086353 (Kassenzettel 12.05.2015).'),
(@bike_id, 'Rücklicht',    'Toplight+',                                  'Busch & Müller', 'Art.-Nr. 122074501 (Kassenzettel 12.05.2015).'),
(@bike_id, 'Schutzblech',  'Schutzblech-Set, schwarz',                  'SKS',           'Art.-Nr. 122074518 (Kassenzettel 12.05.2015).'),
(@bike_id, 'Ständer',      'Seitenstütze Optima 270',                   'Pletscher',     'Art.-Nr. 122073489 (Kassenzettel 12.05.2015).'),
(@bike_id, 'Gepäckträger', 'Nummernschild-Halterung',                   NULL,            'Art.-Nr. 43100812 (Kassenzettel 12.05.2015).'),
(@bike_id, 'Rückspiegel',  'Rückspiegel',                                NULL,            'Art.-Nr. 122076919 (Kassenzettel 12.05.2015).'),
(@bike_id, 'Schloss',      'Faltschloss FS 300/85, schwarz',            'Trelock',       'Art.-Nr. 122073520 (Kassenzettel 12.05.2015).'),
(@bike_id, 'Sonstiges',    'PLET-15-Geni, 26"/28" (Bezeichnung laut Kassenzettel, nicht eindeutig zuordenbar)', 'Pletscher (vermutet)', 'Art.-Nr. 144099012, CHF 65.00 (Kassenzettel 12.05.2015). Genaue Funktion unklar, evtl. Gepäckträger-Zubehör -- bitte am Velo pruefen.');
