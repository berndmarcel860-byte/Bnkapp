<?php
/**
 * BnkApp Admin — Session Manager
 *
 * Wraps PHP native sessions with security hardening:
 *   - HTTP-only, Secure, SameSite cookies
 *   - Session ID regeneration after login
 *   - Flash message support
 */
declare(strict_types=1);

namespace BnkApp\Core;

class Session
{
    private static bool $started = false;

    // ------------------------------------------------------------------
    // Lifecycle
    // ------------------------------------------------------------------

    /**
     * Start the session with secure settings from config.
     * Safe to call multiple times — only starts once.
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $cfg = require CONFIG_PATH . '/config.php';
        $s   = $cfg['session'];

        session_name($s['name']);

        session_set_cookie_params([
            'lifetime' => $s['lifetime'],
            'path'     => '/',
            'secure'   => $s['secure'],
            'httponly' => $s['http_only'],
            'samesite' => $s['same_site'],
        ]);

        session_start();
        self::$started = true;
    }

    /**
     * Regenerate the session ID (call on login to prevent fixation).
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Destroy the session completely (logout).
     */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                $p['secure'],
                $p['httponly']
            );
        }
        session_destroy();
        self::$started = false;
    }

    // ------------------------------------------------------------------
    // Get / Set / Delete
    // ------------------------------------------------------------------

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    // ------------------------------------------------------------------
    // Flash messages (shown once, then removed)
    // ------------------------------------------------------------------

    /**
     * Store a flash message.
     * @param string $type  e.g. 'success' | 'error' | 'warning' | 'info'
     */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    /**
     * Retrieve and clear all flash messages of a given type.
     * Returns an empty array if none exist.
     */
    public static function getFlash(string $type): array
    {
        $messages = $_SESSION['_flash'][$type] ?? [];
        unset($_SESSION['_flash'][$type]);
        return $messages;
    }

    /**
     * Returns true if there are any flash messages of the given type.
     */
    public static function hasFlash(string $type): bool
    {
        return !empty($_SESSION['_flash'][$type]);
    }
}
