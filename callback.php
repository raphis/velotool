<?php
require __DIR__ . '/src/bootstrap.php';

$error = null;

$state = $_GET['state'] ?? '';
$code = $_GET['code'] ?? '';
$expectedState = $_SESSION['oidc_state'] ?? '';
$expectedNonce = $_SESSION['oidc_nonce'] ?? '';
unset($_SESSION['oidc_state'], $_SESSION['oidc_nonce']);

if (isset($_GET['error'])) {
    $error = 'Google-Login abgebrochen oder fehlgeschlagen.';
} elseif ($code === '' || $state === '' || !hash_equals($expectedState, $state)) {
    $error = 'Ungueltiger Login-Versuch (state mismatch). Bitte erneut versuchen.';
} else {
    try {
        $oidc = new GoogleOidc($config['oidc']);
        $claims = $oidc->handleCallback($code, $expectedNonce);

        if (!Auth::isEmailAllowed($claims['email'] ?? '')) {
            $error = 'Diese Google-Adresse (' . htmlspecialchars($claims['email'] ?? '?') . ') ist nicht freigeschaltet.';
        } else {
            Auth::login($claims);
            header('Location: /index.php');
            exit;
        }
    } catch (Throwable $e) {
        $error = 'Login fehlgeschlagen: ' . htmlspecialchars($e->getMessage());
    }
}
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Login fehlgeschlagen – Velotool</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="auth-page">
    <img src="/assets/logo.svg" alt="Velotool" class="auth-logo">
    <h1>Login fehlgeschlagen</h1>
    <p class="error"><?= $error ?></p>
    <a class="button" href="/login.php">Erneut versuchen</a>
</div>
</body>
</html>
