<?php
/**
 * BnkApp Admin — Authentication Middleware
 *
 * Redirects unauthenticated requests to the login page.
 * Applied to every route that requires a logged-in user.
 */
declare(strict_types=1);

namespace BnkApp\Middleware;

use BnkApp\Core\Auth;
use BnkApp\Core\Request;
use BnkApp\Core\Response;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (Auth::guest()) {
            if ($request->isAjax()) {
                Response::error('Unauthenticated.', HTTP_UNAUTHORIZED);
            }
            Response::redirect('/auth/login');
        }
    }
}
