<?php
require __DIR__ . '/src/bootstrap.php';
Auth::requireLogin();

$pdo = Database::get();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$bikeId = (int) ($_GET['bike_id'] ?? $_POST['bike_id'] ?? 0);

$component = ['category' => '', 'name' => '', 'manufacturer' => '', 'details' => '',
    'installed_date' => '', 'removed_date' => '', 'is_current' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM components WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $component = $found;
        $bikeId = (int) $found['bike_id'];
    }
}

if (!$bikeId) {
    http_response_code(400);
    exit('bike_id fehlt.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data = [
        'category' => trim($_POST['category'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'manufacturer' => trim($_POST['manufacturer'] ?? '') ?: null,
        'details' => trim($_POST['details'] ?? '') ?: null,
        'installed_date' => $_POST['installed_date'] ?: null,
        'removed_date' => $_POST['removed_date'] ?: null,
        'is_current' => isset($_POST['is_current']) ? 1 : 0,
    ];

    if ($data['category'] === '' || $data['name'] === '') {
        $errors[] = 'Kategorie und Bezeichnung sind erforderlich.';
    }

    if (!$errors) {
        if ($id) {
            $pdo->prepare('UPDATE components SET category=?, name=?, manufacturer=?, details=?, installed_date=?, removed_date=?, is_current=? WHERE id=?')
                ->execute([...array_values($data), $id]);
        } else {
            $pdo->prepare('INSERT INTO components (bike_id, category, name, manufacturer, details, installed_date, removed_date, is_current) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$bikeId, ...array_values($data)]);
        }
        header('Location: /bike.php?id=' . $bikeId);
        exit;
    }
    $component = array_merge($component, $data);
}

$pageTitle = $id ? 'Komponente bearbeiten' : 'Neue Komponente';
require __DIR__ . '/src/views/header.php';
?>
<h1><?= htmlspecialchars($pageTitle) ?></h1>
<?php foreach ($errors as $e): ?><p class="error"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>

<form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <input type="hidden" name="bike_id" value="<?= (int) $bikeId ?>">

    <label>Kategorie*<input type="text" name="category" value="<?= htmlspecialchars($component['category']) ?>" placeholder="z.B. Bremsen, Reifen, Antrieb" required></label>
    <label>Bezeichnung*<input type="text" name="name" value="<?= htmlspecialchars($component['name']) ?>" required></label>
    <label>Hersteller<input type="text" name="manufacturer" value="<?= htmlspecialchars($component['manufacturer'] ?? '') ?>"></label>
    <label class="full">Details<textarea name="details" rows="3"><?= htmlspecialchars($component['details'] ?? '') ?></textarea></label>
    <label>Eingebaut am<input type="date" name="installed_date" value="<?= htmlspecialchars($component['installed_date'] ?? '') ?>"></label>
    <label>Ausgebaut am<input type="date" name="removed_date" value="<?= htmlspecialchars($component['removed_date'] ?? '') ?>"></label>
    <label class="checkbox"><input type="checkbox" name="is_current" <?= $component['is_current'] ? 'checked' : '' ?>> Aktuell verbaut</label>

    <div class="form-actions">
        <button type="submit" class="button">Speichern</button>
        <a class="button secondary" href="/bike.php?id=<?= (int) $bikeId ?>">Abbrechen</a>
    </div>
</form>
<?php require __DIR__ . '/src/views/footer.php'; ?>
