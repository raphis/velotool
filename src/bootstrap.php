<?php

$config = require __DIR__ . '/../config/config.php';

require __DIR__ . '/Database.php';
require __DIR__ . '/Auth.php';
require __DIR__ . '/GoogleOidc.php';

Auth::init($config);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        exit('Ungueltiges Formular (CSRF-Token). Bitte Seite neu laden.');
    }
}
