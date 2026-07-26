<?php
require __DIR__ . '/src/bootstrap.php';
Auth::requireLogin();

$pdo = Database::get();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$bikeId = (int) ($_GET['bike_id'] ?? $_POST['bike_id'] ?? 0);

$log = ['log_date' => date('Y-m-d'), 'category' => '', 'description' => '', 'component_id' => '',
    'mileage_km' => '', 'cost' => '', 'performed_by' => '', 'chain_wear_percent' => '',
    'disc_thickness_front_mm' => '', 'disc_thickness_rear_mm' => '', 'pad_condition_front_percent' => '',
    'pad_condition_rear_percent' => '', 'other_measurements' => ''];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM maintenance_logs WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $log = $found;
        $bikeId = (int) $found['bike_id'];
    }
}

if (!$bikeId) {
    http_response_code(400);
    exit('bike_id fehlt.');
}

$components = $pdo->prepare('SELECT id, name, category FROM components WHERE bike_id = ? ORDER BY category, name');
$components->execute([$bikeId]);
$components = $components->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data = [
        'log_date' => $_POST['log_date'] ?: date('Y-m-d'),
        'category' => trim($_POST['category'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'component_id' => $_POST['component_id'] !== '' ? (int) $_POST['component_id'] : null,
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
            $pdo->prepare('UPDATE maintenance_logs SET log_date=?, category=?, description=?, component_id=?, mileage_km=?, cost=?, performed_by=?,
                    chain_wear_percent=?, disc_thickness_front_mm=?, disc_thickness_rear_mm=?, pad_condition_front_percent=?, pad_condition_rear_percent=?, other_measurements=?
                    WHERE id=?')
                ->execute([...array_values($data), $id]);
        } else {
            $pdo->prepare('INSERT INTO maintenance_logs (bike_id, log_date, category, description, component_id, mileage_km, cost, performed_by,
                    chain_wear_percent, disc_thickness_front_mm, disc_thickness_rear_mm, pad_condition_front_percent, pad_condition_rear_percent, other_measurements, created_by_user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$bikeId, ...array_values($data), Auth::userId()]);
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
    <label>Komponente<select name="component_id">
        <option value="">–</option>
        <?php foreach ($components as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= (int) ($log['component_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category'] . ' – ' . $c['name']) ?></option>
        <?php endforeach; ?>
    </select></label>
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
