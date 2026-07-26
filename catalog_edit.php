<?php
require __DIR__ . '/src/bootstrap.php';
Auth::requireLogin();

$pdo = Database::get();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$item = ['name' => '', 'manufacturer' => '', 'price_chf' => '', 'note' => '', 'stock_qty' => 0,
    'stock_note' => '', 'for_bike_id' => ''];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM parts_catalog WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $item = $found;
    }
}

$bikes = $pdo->query('SELECT id, name FROM bikes ORDER BY name')->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'manufacturer' => trim($_POST['manufacturer'] ?? '') ?: null,
        'price_chf' => $_POST['price_chf'] !== '' ? $_POST['price_chf'] : null,
        'note' => trim($_POST['note'] ?? '') ?: null,
        'stock_qty' => $_POST['stock_qty'] !== '' ? (int) $_POST['stock_qty'] : 0,
        'stock_note' => trim($_POST['stock_note'] ?? '') ?: null,
        'for_bike_id' => $_POST['for_bike_id'] !== '' ? (int) $_POST['for_bike_id'] : null,
    ];

    if ($data['name'] === '') {
        $errors[] = 'Name ist erforderlich.';
    }

    if (!$errors) {
        if ($id) {
            $pdo->prepare('UPDATE parts_catalog SET name=?, manufacturer=?, price_chf=?, note=?, stock_qty=?, stock_note=?, for_bike_id=? WHERE id=?')
                ->execute([...array_values($data), $id]);
        } else {
            $pdo->prepare('INSERT INTO parts_catalog (name, manufacturer, price_chf, note, stock_qty, stock_note, for_bike_id) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute(array_values($data));
        }
        header('Location: /catalog.php');
        exit;
    }
    $item = array_merge($item, $data);
}

$pageTitle = $id ? 'Katalog-Teil bearbeiten' : 'Neues Katalog-Teil';
require __DIR__ . '/src/views/header.php';
?>
<h1><?= htmlspecialchars($pageTitle) ?></h1>
<?php foreach ($errors as $e): ?><p class="error"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>

<form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <label>Name*<input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required></label>
    <label>Hersteller<input type="text" name="manufacturer" value="<?= htmlspecialchars($item['manufacturer'] ?? '') ?>"></label>
    <label>Preis (CHF)<input type="number" step="0.01" name="price_chf" value="<?= htmlspecialchars((string) ($item['price_chf'] ?? '')) ?>"></label>
    <label>Für Velo<select name="for_bike_id">
        <option value="">– universell / alle –</option>
        <?php foreach ($bikes as $b): ?>
        <option value="<?= (int) $b['id'] ?>" <?= (int) ($item['for_bike_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
        <?php endforeach; ?>
    </select></label>
    <label class="full">Notiz<input type="text" name="note" value="<?= htmlspecialchars($item['note'] ?? '') ?>"></label>
    <label>Auf Lager (Stück)<input type="number" step="1" min="0" name="stock_qty" value="<?= htmlspecialchars((string) $item['stock_qty']) ?>"></label>
    <label>Lager-Notiz<input type="text" name="stock_note" value="<?= htmlspecialchars($item['stock_note'] ?? '') ?>" placeholder="z.B. reserviert fürs Radon"></label>

    <div class="form-actions">
        <button type="submit" class="button">Speichern</button>
        <a class="button secondary" href="/catalog.php">Abbrechen</a>
    </div>
</form>
<?php require __DIR__ . '/src/views/footer.php'; ?>
