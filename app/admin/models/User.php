<?php
/**
 * BnkApp Admin — User Model
 *
 * Maps to the `users` table (joined with `roles` for role name).
 */
declare(strict_types=1);

namespace BnkApp\Models;

use BnkApp\Core\Model;

class User extends Model
{
    protected string $table      = 'users';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'role_id', 'first_name', 'last_name', 'email', 'phone',
        'date_of_birth', 'national_id',
        'address_line1', 'address_line2', 'city', 'postal_code', 'country_id',
        'password_hash', 'password_salt',
        'is_active', 'is_email_verified',
        'kyc_status', 'kyc_submitted_at', 'kyc_approved_at',
        'two_factor_enabled', 'locked_until', 'failed_login_attempts',
    ];

    protected array $hidden = [
        'password_hash', 'password_salt', 'two_factor_secret',
        'email_verification_token', 'password_reset_token',
    ];

    // ------------------------------------------------------------------
    // Lookups
    // ------------------------------------------------------------------

    /**
     * Find a user by email, including their role name.
     * Returns the password_hash for verification (hidden in normal queries).
     */
    public static function findByEmail(string $email): ?array
    {
        $self = new self();
        $stmt = $self->query(
            "SELECT u.*, r.name AS role_name
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.email = ? LIMIT 1",
            [strtolower(trim($email))]
        );
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    // ------------------------------------------------------------------
    // Paginate with role name
    // ------------------------------------------------------------------

    public function paginate(int $page = 1, int $perPage = 25, string $where = '', array $bindings = []): array
    {
        $page    = max(1, $page);
        $offset  = ($page - 1) * $perPage;
        $where   = $where ? "WHERE {$where}" : '';

        $total = (int)$this->query(
            "SELECT COUNT(*) FROM users {$where}",
            $bindings
        )->fetchColumn();

        $rows = $this->query(
            "SELECT u.id, u.first_name, u.last_name, u.email, u.phone,
                    u.kyc_status, u.is_active, u.created_at, r.name AS role_name
               FROM users u
               JOIN roles r ON r.id = u.role_id
               {$where}
              ORDER BY u.created_at DESC
              LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        )->fetchAll();

        return [
            'data'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }

    // ------------------------------------------------------------------
    // Related data
    // ------------------------------------------------------------------

    public function getAccounts(int $userId): array
    {
        return $this->query(
            "SELECT ba.id, ba.iban, ba.account_number, ba.currency_code,
                    ba.balance, ba.status, at.name AS account_type
               FROM bank_accounts ba
               JOIN account_types at ON at.id = ba.account_type_id
              WHERE ba.user_id = ?
              ORDER BY ba.opened_at DESC",
            [$userId]
        )->fetchAll();
    }

    public function getKycDocuments(int $userId): array
    {
        return $this->query(
            "SELECT id, document_type, status, rejection_reason, reviewed_at, expiry_date, created_at
               FROM kyc_documents
              WHERE user_id = ?
              ORDER BY created_at DESC",
            [$userId]
        )->fetchAll();
    }

    // ------------------------------------------------------------------
    // Login support
    // ------------------------------------------------------------------

    public static function incrementFailedAttempts(int $userId): void
    {
        $self = new self();
        $cfg  = require CONFIG_PATH . '/config.php';
        $max  = $cfg['security']['max_login_attempts'];
        $lock = $cfg['security']['lockout_duration_minutes'];

        $self->query(
            "UPDATE users
                SET failed_login_attempts = failed_login_attempts + 1,
                    locked_until = IF(failed_login_attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), locked_until)
              WHERE id = ?",
            [$max, $lock, $userId]
        );
    }

    public static function clearFailedAttempts(int $userId): void
    {
        (new self())->query(
            "UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?",
            [$userId]
        );
    }

    public static function updateLastLogin(int $userId): void
    {
        (new self())->query(
            "UPDATE users SET last_login_at = NOW() WHERE id = ?",
            [$userId]
        );
    }
}
