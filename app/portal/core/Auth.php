<?php
/**
 * BnkApp Portal — Auth helper
 */
declare(strict_types=1);

namespace BnkPortal\Core;

class Auth
{
    public static function check(): bool
    {
        return Session::has('portal_user_id');
    }

    public static function id(): ?int
    {
        return Session::get('portal_user_id');
    }

    public static function user(): ?array
    {
        return Session::get('portal_user');
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('portal_user_id', (int)$user['id']);
        Session::set('portal_user', $user);
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
