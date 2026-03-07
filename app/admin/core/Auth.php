<?php
/**
 * BnkApp Admin — Authentication Helper
 *
 * Provides login, logout, and identity checks backed by the users
 * and admins tables defined in 01_schema.sql.
 */
declare(strict_types=1);

namespace BnkApp\Core;

use BnkApp\Models\User;

class Auth
{
    private const SESSION_KEY = 'auth_user';

    // ------------------------------------------------------------------
    // Login / Logout
    // ------------------------------------------------------------------

    /**
     * Attempt to authenticate a user by email and plain-text password.
     * Enforces account lockout defined in config.security.
     *
     * @return bool True on success, false on failure.
     */
    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if ($user === null) {
            return false;
        }

        // Check account lockout
        if ($user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
            return false;
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            User::incrementFailedAttempts((int)$user['id']);
            return false;
        }

        // Successful login — clear failed attempts, store in session
        User::clearFailedAttempts((int)$user['id']);
        User::updateLastLogin((int)$user['id']);

        Session::regenerate();
        Session::set(self::SESSION_KEY, [
            'id'         => (int)$user['id'],
            'email'      => $user['email'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
            'role'       => $user['role_name'],
        ]);

        return true;
    }

    /**
     * Log the current user out.
     */
    public static function logout(): void
    {
        Session::remove(self::SESSION_KEY);
        Session::regenerate();
    }

    // ------------------------------------------------------------------
    // Identity
    // ------------------------------------------------------------------

    /**
     * Returns true if a user is currently logged in.
     */
    public static function check(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    /**
     * Returns true if no user is logged in.
     */
    public static function guest(): bool
    {
        return !self::check();
    }

    /**
     * Returns the authenticated user's session data array,
     * or null if not logged in.
     */
    public static function user(): ?array
    {
        return Session::get(self::SESSION_KEY);
    }

    /**
     * Returns a single field from the authenticated user's session data.
     */
    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    // ------------------------------------------------------------------
    // Role / Permission checks
    // ------------------------------------------------------------------

    /**
     * Returns true if the authenticated user has at least one of the
     * given roles.
     */
    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    /**
     * Returns true if the authenticated user is a super admin.
     */
    public static function isSuperAdmin(): bool
    {
        return self::role() === ROLE_SUPER_ADMIN;
    }

    /**
     * Returns true if the user is admin-level or above.
     */
    public static function isAdmin(): bool
    {
        return self::hasRole(ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_MANAGER, ROLE_TELLER);
    }
}
