<?php
/**
 * BnkApp Portal — Auth Middleware
 */
declare(strict_types=1);

namespace BnkPortal\Middleware;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Request;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            http_response_code(302);
            header('Location: /login?next=' . urlencode($request->uri()));
            exit;
        }
    }
}
