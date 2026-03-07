<?php
/**
 * BnkApp Portal — CSRF Middleware
 */
declare(strict_types=1);

namespace BnkPortal\Middleware;

use BnkPortal\Core\Request;
use BnkPortal\Core\Session;

class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        if (in_array($request->method(), ['POST', 'PATCH', 'DELETE'], true)) {
            $token   = $request->input('_csrf_token');
            $session = Session::get('_csrf_token');

            if (!$token || !$session || !hash_equals((string)$session, (string)$token)) {
                http_response_code(419);
                echo 'CSRF token mismatch.';
                exit;
            }
        }
    }

    public static function token(): string
    {
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return (string)Session::get('_csrf_token');
    }

    public static function field(): string
    {
        $token = self::token();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
