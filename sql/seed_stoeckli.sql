-- Seed: Stöckli e.t. 1 Urban
-- Hinweis: Stöckli hat die E-Bike-Sparte 2018 eingestellt; ein fixes Spec-Blatt
-- pro Variante/Groesse ist nicht mehr auffindbar. Bremsen/Schaltung sind daher
-- nicht erfasst -- bitte am Velo selbst nachschauen und in components ergaenzen.
INSERT INTO bikes (name, brand, model, color, notes)
VALUES (
    'Stöckli e.t. 1 Urban',
    'Stöckli',
    'e.t. 1 Urban',
    NULL,
    'Urban-Ausstattung mit Schutzblech, Gepäckträger, Zentralständer, Beleuchtung. Akku ca. 724Wh (Basismodell hatte serienmässig nur 396Wh/11Ah, 724Wh war eine der grösseren Optionen ~20.1Ah) -- bitte Wh-Angabe auf dem Akku selbst verifizieren. Stöckli hat die E-Bike-Sparte 2018 eingestellt, Service/Ersatzteile für den Go-SwissDrive-Motor laufen seither über youmo.ch (Nachfolgefirma von Go SwissDrive).'
);

SET @bike_id = LAST_INSERT_ID();

INSERT INTO components (bike_id, category, name, manufacturer, details) VALUES
(@bike_id, 'Motor',      'Nabenmotor Heck, 250W / 40Nm', 'Go SwissDrive', 'Integrierter Hinterrad-Nabenmotor (BikeBus-System). Unterstützung serienmässig bis 25 km/h, per Software-Freischaltung bis 40 km/h möglich.'),
(@bike_id, 'Akku',       'ca. 724 Wh (36V, ~20.1 Ah)',   'Go SwissDrive', 'Im Sattelrohr integriert (unauffällig als "Sattelstütze" getarnt). Genaue Wh-Zahl unbedingt am Akku pruefen -- Basismodell hatte 396Wh, optional 522Wh oder ~724Wh.'),
(@bike_id, 'Gabel',      'Starrgabel',                    NULL,            'Serienmässig, bei e.t. Cross-Variante gegen Federgabel (RockShox/SR Suntour) ersetzbar.'),
(@bike_id, 'Display',    'Go SwissDrive Standard-Display', 'Go SwissDrive', NULL),
(@bike_id, 'Sonstiges',  'Schutzblech, Gepäckträger, Zentralständer, Beleuchtung', NULL, 'Urban-Ausstattungspaket.');
