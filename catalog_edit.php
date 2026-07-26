<?php
require __DIR__ . '/src/bootstrap.php';
Auth::requireLogin();

$pdo = Database::get();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$item = ['name' => '', 'manufacturer' => '', 'supplier' => '', 'price_chf' => '', 'note' => '', 'stock_qty' => 0,
    'stock_note' => ''];
$selectedBikeIds = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM parts_catalog WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $item = $found;

        $stmt = $pdo->prepare('SELECT bike_id FROM parts_catalog_bikes WHERE catalog_item_id = ?');
        $stmt->execute([$id]);
        $selectedBikeIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}

$bikes = $pdo->query('SELECT id, name FROM bikes ORDER BY name')->fetchAll();
$validBikeIds = array_column($bikes, 'id');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $postedBikeIds = array_map('intval', $_POST['bike_ids'] ?? []);
    $selectedBikeIds = array_values(array_intersect($postedBikeIds, $validBikeIds));

    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'manufacturer' => trim($_POST['manufacturer'] ?? '') ?: null,
        'supplier' => trim($_POST['supplier'] ?? '') ?: null,
        'price_chf' => $_POST['price_chf'] !== '' ? $_POST['price_chf'] : null,
        'note' => trim($_POST['note'] ?? '') ?: null,
        'stock_qty' => $_POST['stock_qty'] !== '' ? (int) $_POST['stock_qty'] : 0,
        'stock_note' => trim($_POST['stock_note'] ?? '') ?: null,
    ];

    if ($data['name'] === '') {
        $errors[] = 'Name ist erforderlich.';
    }

    if (!$errors) {
        if ($id) {
            $pdo->prepare('UPDATE parts_catalog SET name=?, manufacturer=?, supplier=?, price_chf=?, note=?, stock_qty=?, stock_note=? WHERE id=?')
                ->execute([...array_values($data), $id]);
        } else {
            $pdo->prepare('INSERT INTO parts_catalog (name, manufacturer, supplier, price_chf, note, stock_qty, stock_note) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute(array_values($data));
            $id = (int) $pdo->lastInsertId();
        }

        $pdo->prepare('DELETE FROM parts_catalog_bikes WHERE catalog_item_id = ?')->execute([$id]);
        if ($selectedBikeIds) {
            $ins = $pdo->prepare('INSERT INTO parts_catalog_bikes (catalog_item_id, bike_id) VALUES (?, ?)');
            foreach ($selectedBikeIds as $bikeId) {
                $ins->execute([$id, $bikeId]);
            }
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
    <label>Lieferant<input type="text" name="supplier" value="<?= htmlspecialchars($item['supplier'] ?? '') ?>" placeholder="z.B. velofactory.ch"></label>
    <label>Preis (CHF)<input type="number" step="0.01" name="price_chf" value="<?= htmlspecialchars((string) ($item['price_chf'] ?? '')) ?>"></label>

    <fieldset class="full component-picker">
        <legend>Passt zu diesen Velos (keine Auswahl = universell/alle)</legend>
        <?php if ($bikes): ?>
            <?php foreach ($bikes as $b): ?>
            <label class="checkbox"><input type="checkbox" name="bike_ids[]" value="<?= (int) $b['id'] ?>" <?= in_array((int) $b['id'], $selectedBikeIds, true) ? 'checked' : '' ?>> <?= htmlspecialchars($b['name']) ?></label>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="muted">Noch keine Velos erfasst.</p>
        <?php endif; ?>
    </fieldset>

    <label class="full">Notiz<input type="text" name="note" value="<?= htmlspecialchars($item['note'] ?? '') ?>"></label>
    <label>Auf Lager (Stück)<input type="number" step="1" min="0" name="stock_qty" value="<?= htmlspecialchars((string) $item['stock_qty']) ?>"></label>
    <label>Lager-Notiz<input type="text" name="stock_note" value="<?= htmlspecialchars($item['stock_note'] ?? '') ?>" placeholder="z.B. reserviert fürs Radon"></label>

    <div class="form-actions">
        <button type="submit" class="button">Speichern</button>
        <a class="button secondary" href="/catalog.php">Abbrechen</a>
    </div>
</form>
<?php require __DIR__ . '/src/views/footer.php'; ?>
