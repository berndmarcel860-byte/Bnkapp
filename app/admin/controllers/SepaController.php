<?php
/**
 * BnkApp Admin — SEPA Transfer Controller
 *
 * View SEPA Credit Transfers (sepa_transfers table).
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class SepaController extends Controller
{
    /**
     * GET /sepa
     */
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)$request->query('page', 1);
        $type   = (string)$request->query('type', '');
        $status = (string)$request->query('status', '');
        $offset = ($page - 1) * 25;

        $conditions = [];
        $bindings   = [];

        if ($type !== '') {
            $conditions[] = 'st.transfer_type = ?';
            $bindings[]   = $type;
        }
        if ($status !== '') {
            $conditions[] = 'st.status = ?';
            $bindings[]   = $status;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countStmt = Database::getInstance()->prepare("SELECT COUNT(*) FROM sepa_transfers st {$where}");
        $countStmt->execute($bindings);
        $total = (int)$countStmt->fetchColumn();

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT st.*,
                    fa.iban AS debtor_iban,
                    ta.iban AS creditor_iban,
                    CONCAT(u.first_name,' ',u.last_name) AS debtor_name
               FROM sepa_transfers st
               JOIN transactions t  ON t.id  = st.transaction_id
               JOIN bank_accounts fa ON fa.id = t.from_account_id
               LEFT JOIN bank_accounts ta ON ta.id = t.to_account_id
               JOIN users u         ON u.id  = fa.user_id
               {$where}
              ORDER BY st.created_at DESC
              LIMIT 25 OFFSET {$offset}"
        );
        $stmt->execute($bindings);

        $this->view('sepa.index', [
            'title'    => 'SEPA Transfers',
            'transfers'=> $stmt->fetchAll(),
            'total'    => $total,
            'page'     => $page,
            'filter'   => ['type' => $type, 'status' => $status],
        ]);
    }

    /**
     * GET /sepa/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT st.*, t.amount, t.currency_code, t.status AS txn_status,
                    fa.iban AS debtor_iban, ta.iban AS creditor_iban,
                    CONCAT(u.first_name,' ',u.last_name) AS debtor_name
               FROM sepa_transfers st
               JOIN transactions t   ON t.id  = st.transaction_id
               JOIN bank_accounts fa ON fa.id = t.from_account_id
               LEFT JOIN bank_accounts ta ON ta.id = t.to_account_id
               JOIN users u          ON u.id  = fa.user_id
              WHERE st.id = ? LIMIT 1"
        );
        $stmt->execute([$params['id']]);
        $transfer = $stmt->fetch();

        if (!$transfer) {
            $this->abort(HTTP_NOT_FOUND, 'SEPA transfer not found.');
        }

        $this->view('sepa.show', [
            'title'    => "SEPA Transfer #{$transfer['id']}",
            'transfer' => $transfer,
        ]);
    }
}
