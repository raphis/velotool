-- Bremsscheiben als Katalog-Teile, je dem passenden Velo zugeordnet.
INSERT INTO parts_catalog (name, manufacturer, stock_qty) VALUES
('SM-RT30-M, 180mm, min. 1.5mm', 'Shimano', 0),
('SM-RT70-M, 180mm, min. 1.5mm', 'Shimano', 0);

INSERT INTO parts_catalog_bikes (catalog_item_id, bike_id)
SELECT c.id, (SELECT id FROM bikes WHERE model LIKE 'Relate%')
FROM parts_catalog c WHERE c.name = 'SM-RT30-M, 180mm, min. 1.5mm';

INSERT INTO parts_catalog_bikes (catalog_item_id, bike_id)
SELECT c.id, (SELECT id FROM bikes WHERE model = 'e.t. 1 Urban')
FROM parts_catalog c WHERE c.name = 'SM-RT70-M, 180mm, min. 1.5mm';
