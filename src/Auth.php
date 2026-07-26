<?php

final class Auth
{
    private static array $cfg;

    public static function init(array $appConfig): void
    {
        self::$cfg = $appConfig;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name($appConfig['app']['session_name']);
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function login(array $claims): void
    {
        $pdo = Database::get();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE google_sub = ?');
        $stmt->execute([$claims['sub']]);
        $userId = $stmt->fetchColumn();

        if ($userId) {
            $pdo->prepare('UPDATE users SET email = ?, name = ?, picture_url = ?, last_login_at = NOW() WHERE id = ?')
                ->execute([$claims['email'], $claims['name'] ?? $claims['email'], $claims['picture'] ?? null, $userId]);
        } else {
            $pdo->prepare('INSERT INTO users (google_sub, email, name, picture_url, last_login_at) VALUES (?, ?, ?, ?, NOW())')
                ->execute([$claims['sub'], $claims['email'], $claims['name'] ?? $claims['email'], $claims['picture'] ?? null]);
            $userId = $pdo->lastInsertId();
        }

        $_SESSION['user_id'] = (int) $userId;
        $_SESSION['user_email'] = $claims['email'];
        $_SESSION['user_name'] = $claims['name'] ?? $claims['email'];
        $_SESSION['user_picture'] = $claims['picture'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    public static function userName(): string
    {
        return (string) ($_SESSION['user_name'] ?? '');
    }

    public static function userPicture(): ?string
    {
        return $_SESSION['user_picture'] ?? null;
    }

    public static function isEmailAllowed(string $email): bool
    {
        $allowed = self::$cfg['allowed_emails'] ?? [];
        if (empty($allowed)) {
            // No whitelist configured yet: allow nobody, fail closed.
            return false;
        }
        foreach ($allowed as $allowedEmail) {
            if (strcasecmp($allowedEmail, $email) === 0) {
                return true;
            }
        }
        return false;
    }
}
