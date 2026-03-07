<?php
/**
 * BnkApp Admin — BankAccount Model
 *
 * Maps to the `bank_accounts` table.
 */
declare(strict_types=1);

namespace BnkApp\Models;

use BnkApp\Core\Model;

class BankAccount extends Model
{
    protected string $table      = 'bank_accounts';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id', 'account_type_id', 'branch_id',
        'account_number', 'iban', 'bic', 'currency_code',
        'balance', 'available_balance', 'hold_amount',
        'status', 'is_primary', 'overdraft_enabled', 'overdraft_limit',
        'opened_at', 'closed_at', 'last_transaction_at',
    ];

    // ------------------------------------------------------------------
    // Enriched query
    // ------------------------------------------------------------------

    /**
     * Find an account with owner and type details joined in.
     */
    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->query(
            "SELECT ba.*,
                    CONCAT(u.first_name, ' ', u.last_name) AS owner_name,
                    u.email AS owner_email,
                    at.name AS account_type_name,
                    b.name  AS branch_name
               FROM bank_accounts ba
               JOIN users         u  ON u.id  = ba.user_id
               JOIN account_types at ON at.id = ba.account_type_id
               LEFT JOIN branches b  ON b.id  = ba.branch_id
              WHERE ba.id = ? LIMIT 1",
            [$id]
        );
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Recent transactions for an account (both sides of the ledger).
     */
    public function getRecentTransactions(int $accountId, int $limit = 20): array
    {
        return $this->query(
            "SELECT t.id, t.transaction_ref, t.transaction_type,
                    t.amount, t.currency_code, t.fee_amount, t.net_amount,
                    t.status, t.description, t.booking_date, t.created_at,
                    fa.iban AS from_iban, ta.iban AS to_iban
               FROM transactions t
               LEFT JOIN bank_accounts fa ON fa.id = t.from_account_id
               LEFT JOIN bank_accounts ta ON ta.id = t.to_account_id
              WHERE t.from_account_id = ? OR t.to_account_id = ?
              ORDER BY t.created_at DESC
              LIMIT ?",
            [$accountId, $accountId, $limit]
        )->fetchAll();
    }

    // ------------------------------------------------------------------
    // Paginate with owner details
    // ------------------------------------------------------------------

    public function paginate(int $page = 1, int $perPage = 25, string $where = '', array $bindings = []): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $w      = $where ? "WHERE {$where}" : '';

        $total = (int)$this->query(
            "SELECT COUNT(*) FROM bank_accounts ba {$w}",
            $bindings
        )->fetchColumn();

        $rows = $this->query(
            "SELECT ba.id, ba.iban, ba.account_number, ba.currency_code,
                    ba.balance, ba.status, ba.opened_at,
                    CONCAT(u.first_name, ' ', u.last_name) AS owner_name,
                    at.name AS account_type_name
               FROM bank_accounts ba
               JOIN users u         ON u.id  = ba.user_id
               JOIN account_types at ON at.id = ba.account_type_id
               {$w}
              ORDER BY ba.opened_at DESC
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
}
