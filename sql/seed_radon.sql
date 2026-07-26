-- Seed: Radon Relate 8.0 Lady 625
INSERT INTO bikes (name, brand, model, model_year, frame_size, color, serial_number, notes)
VALUES (
    'Radon Relate 8.0 Lady',
    'Radon',
    'Relate 8.0 Lady 625',
    NULL,
    NULL,
    'coolgrey / nearlyblack',
    NULL,
    'E-MTB, Aluminium-Rahmen RELATE 625 (Powertube Akku 625Wh horizontal), Schaltauge #8651. Zul. Gesamtgewicht 150kg (130kg Fahrer+Rad+Ausruestung + 20kg Zuladung Geptr.). Gewicht Komplettrad ab 24.45kg.'
);

SET @bike_id = LAST_INSERT_ID();

INSERT INTO components (bike_id, category, name, manufacturer, details) VALUES
(@bike_id, 'Rahmen',        'RELATE 625',                          'Radon',    'Aluminium, Powertube Akku 625Wh horizontal, Schaltauge #8651, 28"'),
(@bike_id, 'Gabel',         'RockShox Recon Silver RL',             'RockShox', 'Air, Boost, Nachlauf 42mm'),
(@bike_id, 'Motor',         'Performance Line CX Gen4',             'Bosch',    NULL),
(@bike_id, 'Akku',          'PowerTube 625Wh',                      'Bosch',    NULL),
(@bike_id, 'Ladegeraet',    'Compact Charger 2A',                   'Bosch',    NULL),
(@bike_id, 'Display',       'Intuvia',                              'Bosch',    NULL),
(@bike_id, 'Bremsen',       'BR-MT420 / BR-MT410',                  'Shimano',  NULL),
(@bike_id, 'Bremsscheiben', 'SM-RT30, 180/180mm, center-lock',      'Shimano',  NULL),
(@bike_id, 'Steuersatz',    'Block-Lock, ZS44/ZS56',                'Acros',    NULL),
(@bike_id, 'Vorbau',        'Swell-R Eco adjust, 31.8mm',           'Humpert',  '100mm (Lady 46/50/54cm)'),
(@bike_id, 'Lenker',        'Comfort Trail Bar, 31.8 x 680mm',      'Radon',    NULL),
(@bike_id, 'Griffe',        'GP10-S',                               'Ergon',    NULL),
(@bike_id, 'Sattel',        'ST10',                                 'Ergon',    NULL),
(@bike_id, 'Sattelstuetze', 'Suspension Seatpost, 30.9 x 350mm',    'Radon',    NULL),
(@bike_id, 'Kurbel',        'E-Crank, 175mm, 38T (KMC Kettenblatt)','Acid',     NULL),
(@bike_id, 'Schaltwerk',    'DEORE RD-M6100-SGS, 12-speed',         'Shimano',  NULL),
(@bike_id, 'Schalthebel',   'DEORE SL-M6100-IR, I-Spec-EV',         'Shimano',  NULL),
(@bike_id, 'Kassette',      'Deore CS-M6100, 10-51, 12-speed',      'Shimano',  NULL),
(@bike_id, 'Kette',         'Deore CN-M6100',                       'Shimano',  NULL),
(@bike_id, 'Reifen',        'Big Ben, PerfLine, wired, Reflex, 28" x 55mm', 'Schwalbe', 'Vorne + hinten'),
(@bike_id, 'Nabe VR',       'HB-TC500-15-B, 36h, 110mm',            'Shimano',  NULL),
(@bike_id, 'Nabe HR',       'FH-TC500-MS-B, 36h, 148mm',            'Shimano',  NULL),
(@bike_id, 'Felgen',        'MD23, 19mm Felgenhoehe, 23mm Maulweite, tubeless ready', 'Alexrims', NULL),
(@bike_id, 'Speichen',      'C14 ED black',                         'Pillar',   NULL),
(@bike_id, 'Scheinwerfer',  'Lumotec IQ-XS Light E',                'Busch & Mueller', NULL),
(@bike_id, 'Ruecklicht',    'H-Trace E-Bike',                       'Herrmans', NULL),
(@bike_id, 'Gepaecktraeger','IC 2.0',                               'Racktime', NULL),
(@bike_id, 'Sonstiges',     'Schutzblech, Staender, Glocke',        NULL,       NULL);
