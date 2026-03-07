<?php
/**
 * BnkApp Admin — Loan Model
 */
declare(strict_types=1);

namespace BnkApp\Models;

use BnkApp\Core\Model;

class Loan extends Model
{
    protected string $table      = 'loans';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id', 'account_id', 'loan_type', 'purpose',
        'principal_amount', 'outstanding_balance',
        'interest_rate', 'term_months', 'monthly_payment',
        'origination_fee', 'late_fee',
        'status', 'approved_by', 'disbursed_at',
        'start_date', 'end_date', 'next_payment_date',
    ];

    /**
     * Find a loan with its owner's details and payment schedule.
     */
    public function findWithSchedule(int $id): ?array
    {
        $stmt = $this->query(
            "SELECT l.*,
                    CONCAT(u.first_name, ' ', u.last_name) AS borrower_name,
                    u.email AS borrower_email,
                    ba.iban AS account_iban
               FROM loans l
               JOIN users u        ON u.id  = l.user_id
               JOIN bank_accounts ba ON ba.id = l.account_id
              WHERE l.id = ? LIMIT 1",
            [$id]
        );
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $row['schedule'] = $this->query(
            "SELECT * FROM loan_payments WHERE loan_id = ? ORDER BY payment_number ASC",
            [$id]
        )->fetchAll();

        return $row;
    }
}
