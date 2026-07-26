<?php
/** @var string $pageTitle */
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle ?? 'Velotool') ?> – Velotool</title>
<link rel="icon" href="/assets/logo.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
    <a href="/index.php" class="brand">
        <img src="/assets/logo.svg" alt="" class="brand-logo">
        <span>Velotool</span>
    </a>
    <?php if (Auth::isLoggedIn()): ?>
    <nav class="topnav">
        <a href="/index.php">Velos</a>
        <span class="user">
            <?php if (Auth::userPicture()): ?><img src="<?= htmlspecialchars(Auth::userPicture()) ?>" class="avatar" alt=""><?php endif; ?>
            <?= htmlspecialchars(Auth::userName()) ?>
        </span>
        <a href="/logout.php">Abmelden</a>
    </nav>
    <?php endif; ?>
</header>
<main class="content">
