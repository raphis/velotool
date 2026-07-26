<?php
require __DIR__ . '/src/bootstrap.php';
Auth::requireLogin();

$pdo = Database::get();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$bike = ['name' => '', 'brand' => '', 'model' => '', 'model_year' => '', 'frame_size' => '', 'color' => '',
    'frame_number' => '', 'registration_number' => '', 'purchase_date' => '', 'purchase_price' => '', 'purchase_price_currency' => 'CHF', 'dealer' => '', 'weight_kg' => '', 'notes' => '',
    'owner_person_id' => '', 'is_active' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM bikes WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $bike = $found;
    }
}

$people = $pdo->query('SELECT id, name FROM people ORDER BY name')->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'brand' => trim($_POST['brand'] ?? '') ?: null,
        'model' => trim($_POST['model'] ?? '') ?: null,
        'model_year' => $_POST['model_year'] !== '' ? (int) $_POST['model_year'] : null,
        'frame_size' => trim($_POST['frame_size'] ?? '') ?: null,
        'color' => trim($_POST['color'] ?? '') ?: null,
        'frame_number' => trim($_POST['frame_number'] ?? '') ?: null,
        'registration_number' => trim($_POST['registration_number'] ?? '') ?: null,
        'purchase_date' => $_POST['purchase_date'] ?: null,
        'purchase_price' => $_POST['purchase_price'] !== '' ? $_POST['purchase_price'] : null,
        'purchase_price_currency' => in_array($_POST['purchase_price_currency'] ?? '', ['CHF', 'EUR'], true) ? $_POST['purchase_price_currency'] : 'CHF',
        'dealer' => trim($_POST['dealer'] ?? '') ?: null,
        'weight_kg' => $_POST['weight_kg'] !== '' ? $_POST['weight_kg'] : null,
        'notes' => trim($_POST['notes'] ?? '') ?: null,
        'owner_person_id' => $_POST['owner_person_id'] !== '' ? (int) $_POST['owner_person_id'] : null,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($data['name'] === '') {
        $errors[] = 'Name ist erforderlich.';
    }

    if (!$errors) {
        if ($id) {
            $sql = 'UPDATE bikes SET name=?, brand=?, model=?, model_year=?, frame_size=?, color=?, frame_number=?, registration_number=?,
                    purchase_date=?, purchase_price=?, purchase_price_currency=?, dealer=?, weight_kg=?, notes=?, owner_person_id=?, is_active=? WHERE id=?';
            $pdo->prepare($sql)->execute([...array_values($data), $id]);
        } else {
            $cols = implode(',', array_keys($data));
            $placeholders = implode(',', array_fill(0, count($data), '?'));
            $pdo->prepare("INSERT INTO bikes ($cols) VALUES ($placeholders)")->execute(array_values($data));
            $id = (int) $pdo->lastInsertId();
        }
        header('Location: /bike.php?id=' . $id);
        exit;
    }
    $bike = array_merge($bike, $data);
}

$pageTitle = $id ? 'Velo bearbeiten' : 'Neues Velo';
require __DIR__ . '/src/views/header.php';
?>
<h1><?= htmlspecialchars($pageTitle) ?></h1>

<?php foreach ($errors as $e): ?><p class="error"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>

<form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <label>Name*<input type="text" name="name" value="<?= htmlspecialchars($bike['name']) ?>" required></label>
    <label>Marke<input type="text" name="brand" value="<?= htmlspecialchars($bike['brand'] ?? '') ?>"></label>
    <label>Modell<input type="text" name="model" value="<?= htmlspecialchars($bike['model'] ?? '') ?>"></label>
    <label>Modelljahr<input type="number" name="model_year" value="<?= htmlspecialchars((string) ($bike['model_year'] ?? '')) ?>"></label>
    <label>Rahmengrösse<input type="text" name="frame_size" value="<?= htmlspecialchars($bike['frame_size'] ?? '') ?>"></label>
    <label>Farbe<input type="text" name="color" value="<?= htmlspecialchars($bike['color'] ?? '') ?>"></label>
    <label>Rahmennummer<input type="text" name="frame_number" value="<?= htmlspecialchars($bike['frame_number'] ?? '') ?>"></label>
    <label>Kontrollschild-Nr. (Strassenverkehrsamt)<input type="text" name="registration_number" value="<?= htmlspecialchars($bike['registration_number'] ?? '') ?>" placeholder="bei S-Pedelec"></label>
    <label>Gehört<select name="owner_person_id">
        <option value="">–</option>
        <?php foreach ($people as $u): ?>
        <option value="<?= (int) $u['id'] ?>" <?= (int) ($bike['owner_person_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
        <?php endforeach; ?>
    </select></label>
    <label>Kaufdatum<input type="date" name="purchase_date" value="<?= htmlspecialchars($bike['purchase_date'] ?? '') ?>"></label>
    <label>Kaufpreis<input type="number" step="0.01" name="purchase_price" value="<?= htmlspecialchars((string) ($bike['purchase_price'] ?? '')) ?>"></label>
    <label>Währung<select name="purchase_price_currency">
        <?php foreach (['CHF', 'EUR'] as $cur): ?>
        <option value="<?= $cur ?>" <?= $bike['purchase_price_currency'] === $cur ? 'selected' : '' ?>><?= $cur ?></option>
        <?php endforeach; ?>
    </select></label>
    <label>Händler<input type="text" name="dealer" value="<?= htmlspecialchars($bike['dealer'] ?? '') ?>" placeholder="z.B. Stöckli Swiss Sports AG, Wädenswil"></label>
    <label>Gewicht (kg)<input type="number" step="0.01" name="weight_kg" value="<?= htmlspecialchars((string) ($bike['weight_kg'] ?? '')) ?>"></label>
    <label class="full">Notizen<textarea name="notes" rows="4"><?= htmlspecialchars($bike['notes'] ?? '') ?></textarea></label>
    <label class="checkbox"><input type="checkbox" name="is_active" <?= $bike['is_active'] ? 'checked' : '' ?>> Aktiv im Einsatz</label>

    <div class="form-actions">
        <button type="submit" class="button">Speichern</button>
        <?php if ($id): ?><a class="button secondary" href="/bike.php?id=<?= (int) $id ?>">Abbrechen</a><?php endif; ?>
    </div>
</form>
<?php require __DIR__ . '/src/views/footer.php'; ?>
