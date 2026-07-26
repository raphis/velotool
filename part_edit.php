<?php
require __DIR__ . '/src/bootstrap.php';
Auth::requireLogin();

$pdo = Database::get();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$bikeId = (int) ($_GET['bike_id'] ?? $_POST['bike_id'] ?? 0);

$part = ['part_name' => '', 'reason' => '', 'component_id' => '', 'status' => 'needed',
    'priority' => 'normal', 'ordered_date' => ''];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM parts_needed WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $part = $found;
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
        'part_name' => trim($_POST['part_name'] ?? ''),
        'reason' => trim($_POST['reason'] ?? '') ?: null,
        'component_id' => $_POST['component_id'] !== '' ? (int) $_POST['component_id'] : null,
        'status' => in_array($_POST['status'] ?? '', ['needed', 'ordered', 'installed'], true) ? $_POST['status'] : 'needed',
        'priority' => in_array($_POST['priority'] ?? '', ['low', 'normal', 'high'], true) ? $_POST['priority'] : 'normal',
        'ordered_date' => $_POST['ordered_date'] ?: null,
    ];

    if ($data['part_name'] === '') {
        $errors[] = 'Teil-Bezeichnung ist erforderlich.';
    }

    if (!$errors) {
        if ($id) {
            $pdo->prepare('UPDATE parts_needed SET part_name=?, reason=?, component_id=?, status=?, priority=?, ordered_date=? WHERE id=?')
                ->execute([...array_values($data), $id]);
        } else {
            $pdo->prepare('INSERT INTO parts_needed (bike_id, part_name, reason, component_id, status, priority, ordered_date) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$bikeId, ...array_values($data)]);
        }
        header('Location: /bike.php?id=' . $bikeId);
        exit;
    }
    $part = array_merge($part, $data);
}

$pageTitle = $id ? 'Ersatzteil bearbeiten' : 'Neues Ersatzteil';
require __DIR__ . '/src/views/header.php';
?>
<h1><?= htmlspecialchars($pageTitle) ?></h1>
<?php foreach ($errors as $e): ?><p class="error"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>

<form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <input type="hidden" name="bike_id" value="<?= (int) $bikeId ?>">

    <label>Teil*<input type="text" name="part_name" value="<?= htmlspecialchars($part['part_name']) ?>" required></label>
    <label>Komponente<select name="component_id">
        <option value="">–</option>
        <?php foreach ($components as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= (int) ($part['component_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category'] . ' – ' . $c['name']) ?></option>
        <?php endforeach; ?>
    </select></label>
    <label class="full">Grund<textarea name="reason" rows="3"><?= htmlspecialchars($part['reason'] ?? '') ?></textarea></label>
    <label>Status<select name="status">
        <?php foreach (['needed' => 'benötigt', 'ordered' => 'bestellt', 'installed' => 'eingebaut'] as $val => $label): ?>
        <option value="<?= $val ?>" <?= $part['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select></label>
    <label>Priorität<select name="priority">
        <?php foreach (['low' => 'niedrig', 'normal' => 'normal', 'high' => 'hoch'] as $val => $label): ?>
        <option value="<?= $val ?>" <?= $part['priority'] === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select></label>
    <label>Bestellt am<input type="date" name="ordered_date" value="<?= htmlspecialchars($part['ordered_date'] ?? '') ?>"></label>

    <div class="form-actions">
        <button type="submit" class="button">Speichern</button>
        <a class="button secondary" href="/bike.php?id=<?= (int) $bikeId ?>">Abbrechen</a>
    </div>
</form>
<?php require __DIR__ . '/src/views/footer.php'; ?>
