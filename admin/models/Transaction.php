<?php
/**
 * BnkApp Admin — Transaction Model
 *
 * Maps to the `transactions` table.
 */
declare(strict_types=1);

namespace BnkApp\Models;

use BnkApp\Core\Model;

class Transaction extends Model
{
    protected string $table      = 'transactions';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'transaction_ref', 'from_account_id', 'to_account_id',
        'transaction_type', 'amount', 'currency_code', 'exchange_rate',
        'fee_amount', 'net_amount', 'status',
        'category_id', 'description', 'reference', 'end_to_end_id',
        'value_date', 'booking_date', 'initiated_by', 'approved_by',
        'requires_approval', 'ip_address', 'metadata', 'failure_reason',
    ];

    // ------------------------------------------------------------------
    // Enriched query
    // ------------------------------------------------------------------

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->query(
            "SELECT t.*,
                    fa.iban AS from_iban, ta.iban AS to_iban,
                    CONCAT(iu.first_name, ' ', iu.last_name) AS initiated_by_name,
                    CONCAT(au.first_name, ' ', au.last_name) AS approved_by_name,
                    c.name AS category_name
               FROM transactions t
               LEFT JOIN bank_accounts fa ON fa.id = t.from_account_id
               LEFT JOIN bank_accounts ta ON ta.id = t.to_account_id
               LEFT JOIN users iu ON iu.id = t.initiated_by
               LEFT JOIN users au ON au.id = t.approved_by
               LEFT JOIN transaction_categories c ON c.id = t.category_id
              WHERE t.id = ? LIMIT 1",
            [$id]
        );
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    // ------------------------------------------------------------------
    // Filter builder (used by TransactionController)
    // ------------------------------------------------------------------

    /**
     * Build a WHERE clause and bindings array from optional filter values.
     * Returns [string $where, array $bindings].
     */
    public function buildFilters(string $type, string $status, string $from, string $to): array
    {
        $conditions = [];
        $bindings   = [];

        if ($type !== '') {
            $conditions[] = 't.transaction_type = ?';
            $bindings[]   = $type;
        }

        if ($status !== '') {
            $conditions[] = 't.status = ?';
            $bindings[]   = $status;
        }

        if ($from !== '') {
            $conditions[] = 'DATE(t.created_at) >= ?';
            $bindings[]   = $from;
        }

        if ($to !== '') {
            $conditions[] = 'DATE(t.created_at) <= ?';
            $bindings[]   = $to;
        }

        return [implode(' AND ', $conditions), $bindings];
    }

    // ------------------------------------------------------------------
    // Paginate with account IBANs joined
    // ------------------------------------------------------------------

    public function paginate(int $page = 1, int $perPage = 25, string $where = '', array $bindings = []): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $w      = $where ? "WHERE {$where}" : '';

        $total = (int)$this->query(
            "SELECT COUNT(*) FROM transactions t {$w}",
            $bindings
        )->fetchColumn();

        $rows = $this->query(
            "SELECT t.id, t.transaction_ref, t.transaction_type,
                    t.amount, t.currency_code, t.fee_amount, t.net_amount,
                    t.status, t.description, t.booking_date, t.created_at,
                    fa.iban AS from_iban, ta.iban AS to_iban
               FROM transactions t
               LEFT JOIN bank_accounts fa ON fa.id = t.from_account_id
               LEFT JOIN bank_accounts ta ON ta.id = t.to_account_id
               {$w}
              ORDER BY t.created_at DESC
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
