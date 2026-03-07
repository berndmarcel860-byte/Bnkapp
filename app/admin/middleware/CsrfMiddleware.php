<?php
/**
 * BnkApp Admin — CSRF Protection Middleware
 *
 * Validates the CSRF token on all mutating requests (POST, PUT, PATCH, DELETE).
 * GET requests are exempt.
 *
 * Token generation: call CsrfMiddleware::generateToken() inside your view
 * and embed the result in a hidden form field named `_csrf_token`.
 */
declare(strict_types=1);

namespace BnkApp\Middleware;

use BnkApp\Core\Request;
use BnkApp\Core\Response;
use BnkApp\Core\Session;

class CsrfMiddleware
{
    private const TOKEN_KEY = '_csrf_token';

    public function handle(Request $request): void
    {
        if ($request->isGet()) {
            return; // Safe method — no validation needed
        }

        $sessionToken = Session::get(self::TOKEN_KEY);
        $requestToken = $request->input(self::TOKEN_KEY)
                     ?? $request->header('X-CSRF-TOKEN');

        if (empty($sessionToken) || !hash_equals($sessionToken, (string)$requestToken)) {
            if ($request->isAjax()) {
                Response::error('CSRF token mismatch.', HTTP_FORBIDDEN);
            }
            Response::abort(HTTP_FORBIDDEN, 'CSRF token validation failed. Please refresh and try again.');
        }
    }

    // ------------------------------------------------------------------
    // Token management (called from views / controllers)
    // ------------------------------------------------------------------

    /**
     * Generate a new CSRF token, store it in the session, and return it.
     * Should be called once per page render.
     */
    public static function generateToken(): string
    {
        $cfg   = require CONFIG_PATH . '/config.php';
        $token = bin2hex(random_bytes($cfg['security']['csrf_token_length']));
        Session::set(self::TOKEN_KEY, $token);
        return $token;
    }

    /**
     * Return the current session CSRF token (or generate one if missing).
     */
    public static function token(): string
    {
        if (!Session::has(self::TOKEN_KEY)) {
            return self::generateToken();
        }
        return Session::get(self::TOKEN_KEY);
    }

    /**
     * Render a hidden HTML input field containing the CSRF token.
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return "<input type=\"hidden\" name=\"_csrf_token\" value=\"{$token}\">";
    }
}
