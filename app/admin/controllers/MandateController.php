<?php
/**
 * BnkApp Admin — SEPA Mandate Controller
 *
 * View and manage Direct Debit mandates (sepa_mandates table).
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class MandateController extends Controller
{
    /**
     * GET /mandates
     */
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)$request->query('page', 1);
        $status = (string)$request->query('status', '');
        $offset = ($page - 1) * 25;

        $where    = $status !== '' ? 'WHERE sm.status = ?' : '';
        $bindings = $status !== '' ? [$status] : [];

        $db = Database::getInstance();
        $countStmt = $db->prepare("SELECT COUNT(*) FROM sepa_mandates sm {$where}");
        $countStmt->execute($bindings);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT sm.*,
                    ba.iban AS debtor_iban,
                    CONCAT(u.first_name,' ',u.last_name) AS debtor_name
               FROM sepa_mandates sm
               JOIN bank_accounts ba ON ba.id = sm.account_id
               JOIN users u          ON u.id  = ba.user_id
               {$where}
              ORDER BY sm.created_at DESC
              LIMIT 25 OFFSET {$offset}"
        );
        $stmt->execute($bindings);

        $this->view('mandates.index', [
            'title'    => 'SEPA Mandates',
            'mandates' => $stmt->fetchAll(),
            'total'    => $total,
            'page'     => $page,
            'filter'   => ['status' => $status],
        ]);
    }

    /**
     * GET /mandates/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT sm.*,
                    ba.iban AS debtor_iban,
                    CONCAT(u.first_name,' ',u.last_name) AS debtor_name,
                    u.email AS debtor_email
               FROM sepa_mandates sm
               JOIN bank_accounts ba ON ba.id = sm.account_id
               JOIN users u          ON u.id  = ba.user_id
              WHERE sm.id = ? LIMIT 1"
        );
        $stmt->execute([$params['id']]);
        $mandate = $stmt->fetch();

        if (!$mandate) {
            $this->abort(HTTP_NOT_FOUND, 'Mandate not found.');
        }

        $this->view('mandates.show', [
            'title'   => "Mandate #{$mandate['id']}",
            'mandate' => $mandate,
        ]);
    }

    /**
     * PATCH /mandates/{id}/revoke
     */
    public function revoke(Request $request, array $params = []): void
    {
        $db = Database::getInstance();
        $db->prepare("UPDATE sepa_mandates SET status = 'revoked', revoked_at = NOW() WHERE id = ?")
           ->execute([$params['id']]);
        $this->success(null, 'Mandate revoked.');
    }
}
