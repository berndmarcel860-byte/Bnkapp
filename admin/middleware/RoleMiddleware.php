<?php
/**
 * BnkApp Admin — Role Middleware
 *
 * Ensures the authenticated user is an admin-level staff member
 * (super_admin, admin, manager, or teller).
 * Customer-role users are forbidden from accessing the admin panel.
 */
declare(strict_types=1);

namespace BnkApp\Middleware;

use BnkApp\Core\Auth;
use BnkApp\Core\Request;
use BnkApp\Core\Response;

class RoleMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::isAdmin()) {
            if ($request->isAjax()) {
                Response::error('Forbidden. Insufficient permissions.', HTTP_FORBIDDEN);
            }
            Response::abort(HTTP_FORBIDDEN, 'You do not have permission to access this area.');
        }
    }
}
