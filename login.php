<?php
require __DIR__ . '/src/bootstrap.php';

if (Auth::isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$state = bin2hex(random_bytes(16));
$nonce = bin2hex(random_bytes(16));
$_SESSION['oidc_state'] = $state;
$_SESSION['oidc_nonce'] = $nonce;

$oidc = new GoogleOidc($config['oidc']);
header('Location: ' . $oidc->buildAuthUrl($state, $nonce));
exit;
