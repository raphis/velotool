<?php
require __DIR__ . '/src/bootstrap.php';
Auth::requireLogin();

$pdo = Database::get();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$bikeId = (int) ($_GET['bike_id'] ?? $_POST['bike_id'] ?? 0);

$log = ['log_date' => date('Y-m-d'), 'category' => '', 'description' => '',
    'mileage_km' => '', 'cost' => '', 'performed_by' => '', 'chain_wear_percent' => '',
    'disc_thickness_front_mm' => '', 'disc_thickness_rear_mm' => '', 'pad_condition_front_percent' => '',
    'pad_condition_rear_percent' => '', 'other_measurements' => ''];
$selectedComponentIds = [];
$usedParts = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM maintenance_logs WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $log = $found;
        $bikeId = (int) $found['bike_id'];

        $stmt = $pdo->prepare('SELECT component_id FROM maintenance_log_components WHERE log_id = ?');
        $stmt->execute([$id]);
        $selectedComponentIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $stmt = $pdo->prepare('SELECT catalog_item_id, quantity FROM maintenance_log_parts WHERE log_id = ?');
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll() as $row) {
            $usedParts[(int) $row['catalog_item_id']] = (int) $row['quantity'];
        }
    }
}

if (!$bikeId) {
    http_response_code(400);
    exit('bike_id fehlt.');
}

$components = $pdo->prepare('SELECT id, name, category FROM components WHERE bike_id = ? ORDER BY category, name');
$components->execute([$bikeId]);
$components = $components->fetchAll();
$validComponentIds = array_column($components, 'id');

$catalogStmt = $pdo->prepare(
    'SELECT id, name, manufacturer, stock_qty FROM parts_catalog c
     WHERE NOT EXISTS (SELECT 1 FROM parts_catalog_bikes pcb WHERE pcb.catalog_item_id = c.id)
        OR EXISTS (SELECT 1 FROM parts_catalog_bikes pcb WHERE pcb.catalog_item_id = c.id AND pcb.bike_id = ?)
     ORDER BY manufacturer, name'
);
$catalogStmt->execute([$bikeId]);
$catalogItems = $catalogStmt->fetchAll();
$validCatalogIds = array_column($catalogItems, 'id');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $postedComponentIds = array_map('intval', $_POST['component_ids'] ?? []);
    $selectedComponentIds = array_values(array_intersect($postedComponentIds, $validComponentIds));

    $newUsedParts = [];
    foreach ($_POST['parts_used'] ?? [] as $catalogId => $qty) {
        $catalogId = (int) $catalogId;
        $qty = (int) $qty;
        if ($qty > 0 && in_array($catalogId, $validCatalogIds, true)) {
            $newUsedParts[$catalogId] = $qty;
        }
    }

    $data = [
        'log_date' => $_POST['log_date'] ?: date('Y-m-d'),
        'category' => trim($_POST['category'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'mileage_km' => $_POST['mileage_km'] !== '' ? (int) $_POST['mileage_km'] : null,
        'cost' => $_POST['cost'] !== '' ? $_POST['cost'] : null,
        'performed_by' => trim($_POST['performed_by'] ?? '') ?: null,
        'chain_wear_percent' => $_POST['chain_wear_percent'] !== '' ? $_POST['chain_wear_percent'] : null,
        'disc_thickness_front_mm' => $_POST['disc_thickness_front_mm'] !== '' ? $_POST['disc_thickness_front_mm'] : null,
        'disc_thickness_rear_mm' => $_POST['disc_thickness_rear_mm'] !== '' ? $_POST['disc_thickness_rear_mm'] : null,
        'pad_condition_front_percent' => $_POST['pad_condition_front_percent'] !== '' ? (int) $_POST['pad_condition_front_percent'] : null,
        'pad_condition_rear_percent' => $_POST['pad_condition_rear_percent'] !== '' ? (int) $_POST['pad_condition_rear_percent'] : null,
        'other_measurements' => trim($_POST['other_measurements'] ?? '') ?: null,
    ];

    if ($data['category'] === '' || $data['description'] === '') {
        $errors[] = 'Kategorie und Beschreibung sind erforderlich.';
    }

    if (!$errors) {
        if ($id) {
            $pdo->prepare('UPDATE maintenance_logs SET log_date=?, category=?, description=?, mileage_km=?, cost=?, performed_by=?,
                    chain_wear_percent=?, disc_thickness_front_mm=?, disc_thickness_rear_mm=?, pad_condition_front_percent=?, pad_condition_rear_percent=?, other_measurements=?
                    WHERE id=?')
                ->execute([...array_values($data), $id]);
        } else {
            $pdo->prepare('INSERT INTO maintenance_logs (bike_id, log_date, category, description, mileage_km, cost, performed_by,
                    chain_wear_percent, disc_thickness_front_mm, disc_thickness_rear_mm, pad_condition_front_percent, pad_condition_rear_percent, other_measurements, created_by_user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$bikeId, ...array_values($data), Auth::userId()]);
            $id = (int) $pdo->lastInsertId();
        }

        $pdo->prepare('DELETE FROM maintenance_log_components WHERE log_id = ?')->execute([$id]);
        if ($selectedComponentIds) {
            $ins = $pdo->prepare('INSERT INTO maintenance_log_components (log_id, component_id) VALUES (?, ?)');
            foreach ($selectedComponentIds as $componentId) {
                $ins->execute([$id, $componentId]);
            }
        }

        // Lagerbestand anpassen: alte Verbrauchsmenge zurückbuchen, neue abziehen (nie unter 0).
        $oldUsedStmt = $pdo->prepare('SELECT catalog_item_id, quantity FROM maintenance_log_parts WHERE log_id = ?');
        $oldUsedStmt->execute([$id]);
        $oldUsedParts = [];
        foreach ($oldUsedStmt->fetchAll() as $row) {
            $oldUsedParts[(int) $row['catalog_item_id']] = (int) $row['quantity'];
        }

        $touchedCatalogIds = array_unique(array_merge(array_keys($oldUsedParts), array_keys($newUsedParts)));
        $stockUpd = $pdo->prepare('UPDATE parts_catalog SET stock_qty = GREATEST(0, stock_qty + ? - ?) WHERE id = ?');
        foreach ($touchedCatalogIds as $catalogId) {
            $oldQty = $oldUsedParts[$catalogId] ?? 0;
            $newQty = $newUsedParts[$catalogId] ?? 0;
            if ($oldQty !== $newQty) {
                $stockUpd->execute([$oldQty, $newQty, $catalogId]);
            }
        }

        $pdo->prepare('DELETE FROM maintenance_log_parts WHERE log_id = ?')->execute([$id]);
        if ($newUsedParts) {
            $insUsed = $pdo->prepare('INSERT INTO maintenance_log_parts (log_id, catalog_item_id, quantity) VALUES (?, ?, ?)');
            foreach ($newUsedParts as $catalogId => $qty) {
                $insUsed->execute([$id, $catalogId, $qty]);
            }
        }

        header('Location: /bike.php?id=' . $bikeId);
        exit;
    }
    $log = array_merge($log, $data);
}

$pageTitle = $id ? 'Wartungseintrag bearbeiten' : 'Neuer Wartungseintrag';
require __DIR__ . '/src/views/header.php';
?>
<h1><?= htmlspecialchars($pageTitle) ?></h1>
<?php foreach ($errors as $e): ?><p class="error"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>

<form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <input type="hidden" name="bike_id" value="<?= (int) $bikeId ?>">

    <label>Datum*<input type="date" name="log_date" value="<?= htmlspecialchars($log['log_date']) ?>" required></label>
    <label>Kategorie*<input type="text" name="category" value="<?= htmlspecialchars($log['category']) ?>" placeholder="z.B. Service, Reparatur, Inspektion" required></label>

    <fieldset class="full component-picker">
        <legend>Betroffene Komponente(n)</legend>
        <?php if ($components): ?>
            <?php foreach ($components as $c): ?>
            <label class="checkbox"><input type="checkbox" name="component_ids[]" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $selectedComponentIds, true) ? 'checked' : '' ?>> <?= htmlspecialchars($c['category'] . ' – ' . $c['name']) ?></label>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="muted">Noch keine Komponenten für dieses Velo erfasst.</p>
        <?php endif; ?>
    </fieldset>

    <fieldset class="full component-picker">
        <legend>Verwendete Ersatzteile (aus Lager)</legend>
        <?php if ($catalogItems): ?>
            <?php foreach ($catalogItems as $c): ?>
            <label class="parts-used-row">
                <input type="number" min="0" step="1" name="parts_used[<?= (int) $c['id'] ?>]" value="<?= (int) ($usedParts[$c['id']] ?? 0) ?>">
                <span><?= htmlspecialchars(($c['manufacturer'] ? $c['manufacturer'] . ' – ' : '') . $c['name']) ?> <span class="muted small">(Lager: <?= (int) $c['stock_qty'] ?>x)</span></span>
            </label>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="muted">Noch keine Katalog-Teile erfasst. <a href="/catalog.php">Katalog verwalten</a></p>
        <?php endif; ?>
    </fieldset>

    <label class="full">Beschreibung*<textarea name="description" rows="3" required><?= htmlspecialchars($log['description']) ?></textarea></label>
    <label>Kilometerstand<input type="number" name="mileage_km" value="<?= htmlspecialchars((string) ($log['mileage_km'] ?? '')) ?>"></label>
    <label>Kosten (CHF)<input type="number" step="0.01" name="cost" value="<?= htmlspecialchars((string) ($log['cost'] ?? '')) ?>"></label>
    <label>Ausgeführt von<input type="text" name="performed_by" value="<?= htmlspecialchars($log['performed_by'] ?? '') ?>" placeholder="z.B. Velowerkstatt Muster, selbst"></label>

    <label>Kettenverschleiss (%)<input type="number" step="0.01" min="0" name="chain_wear_percent" value="<?= htmlspecialchars((string) ($log['chain_wear_percent'] ?? '')) ?>" placeholder="z.B. 0.5 / 0.75 / 1.0"></label>
    <label>Bremsscheibe vorne (mm)<input type="number" step="0.01" min="0" name="disc_thickness_front_mm" value="<?= htmlspecialchars((string) ($log['disc_thickness_front_mm'] ?? '')) ?>"></label>
    <label>Bremsscheibe hinten (mm)<input type="number" step="0.01" min="0" name="disc_thickness_rear_mm" value="<?= htmlspecialchars((string) ($log['disc_thickness_rear_mm'] ?? '')) ?>"></label>
    <label>Bremsklötze vorne (% Restzustand)<input type="number" step="1" min="0" max="100" name="pad_condition_front_percent" value="<?= htmlspecialchars((string) ($log['pad_condition_front_percent'] ?? '')) ?>"></label>
    <label>Bremsklötze hinten (% Restzustand)<input type="number" step="1" min="0" max="100" name="pad_condition_rear_percent" value="<?= htmlspecialchars((string) ($log['pad_condition_rear_percent'] ?? '')) ?>"></label>
    <label class="full">Weitere Messwerte<input type="text" name="other_measurements" value="<?= htmlspecialchars($log['other_measurements'] ?? '') ?>" placeholder="z.B. Reifenprofil, Federgabel-SAG, ..."></label>

    <div class="form-actions">
        <button type="submit" class="button">Speichern</button>
        <a class="button secondary" href="/bike.php?id=<?= (int) $bikeId ?>">Abbrechen</a>
    </div>
</form>
<?php require __DIR__ . '/src/views/footer.php'; ?>
