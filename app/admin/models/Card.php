<?php
/**
 * BnkApp Admin — Card Model
 */
declare(strict_types=1);

namespace BnkApp\Models;

use BnkApp\Core\Model;

class Card extends Model
{
    protected string $table      = 'cards';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'account_id', 'card_number_hash', 'card_number_last4',
        'card_type', 'card_network', 'cardholder_name',
        'expiry_month', 'expiry_year', 'cvv_hash', 'pin_hash',
        'credit_limit', 'available_credit',
        'status', 'is_contactless',
        'daily_atm_limit', 'daily_pos_limit', 'online_limit',
        'is_online_enabled', 'is_international_enabled',
        'expires_at', 'activated_at',
        'blocked_at', 'blocked_reason',
    ];

    /** Never expose these — hashed values are not useful to the UI */
    protected array $hidden = [
        'card_number_hash', 'cvv_hash', 'pin_hash',
    ];

    public function paginate(int $page = 1, int $perPage = 25, string $where = '', array $bindings = []): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $w      = $where ? "WHERE {$where}" : '';

        $total = (int)$this->query(
            "SELECT COUNT(*) FROM cards c {$w}",
            $bindings
        )->fetchColumn();

        $rows = $this->query(
            "SELECT c.id, c.card_number_last4, c.card_type, c.card_network,
                    c.cardholder_name, c.status, c.expires_at, c.issued_at,
                    ba.iban AS account_iban
               FROM cards c
               JOIN bank_accounts ba ON ba.id = c.account_id
               {$w}
              ORDER BY c.issued_at DESC
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
