<?php
// Copy this file to config.php and fill in real values.
// config.php is git-ignored and must NEVER be committed.

return [
    'db' => [
        'host' => 'localhost',
        'name' => '',
        'user' => '',
        'pass' => '',
    ],
    'oidc' => [
        'client_id'     => '',
        'client_secret' => '',
        'redirect_uri'  => 'https://velo.thoma.cx/callback.php',
    ],
    // Only these email addresses are allowed to log in.
    'allowed_emails' => [
        // 'someone@example.com',
    ],
    'app' => [
        'name' => 'Velotool',
        'session_name' => 'velotool_session',
    ],
];
