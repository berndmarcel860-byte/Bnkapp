<?php
/**
 * BnkApp Admin — Standing Order Controller
 *
 * Manage recurring SEPA Credit Transfers (standing_orders table).
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Auth;
use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class StandingOrderController extends Controller
{
    /**
     * GET /standing-orders
     */
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)$request->query('page', 1);
        $status = (string)$request->query('status', '');

        $db     = Database::getInstance();
        $offset = ($page - 1) * 25;

        $where    = $status !== '' ? 'WHERE so.status = ?' : '';
        $bindings = $status !== '' ? [$status] : [];

        $countStmt = $db->prepare("SELECT COUNT(*) FROM standing_orders so {$where}");
        $countStmt->execute($bindings);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT so.*,
                    fa.iban AS from_iban,
                    CONCAT(u.first_name, ' ', u.last_name) AS owner_name
               FROM standing_orders so
               JOIN bank_accounts fa ON fa.id = so.from_account_id
               JOIN users u          ON u.id  = fa.user_id
               {$where}
              ORDER BY so.created_at DESC
              LIMIT 25 OFFSET {$offset}"
        );
        $stmt->execute($bindings);

        $this->view('standing-orders.index', [
            'title'          => 'Standing Orders',
            'orders'         => $stmt->fetchAll(),
            'total'          => $total,
            'page'           => $page,
            'filter'         => ['status' => $status],
        ]);
    }

    /**
     * GET /standing-orders/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT so.*,
                    fa.iban AS from_iban,
                    CONCAT(u.first_name, ' ', u.last_name) AS owner_name,
                    u.email AS owner_email
               FROM standing_orders so
               JOIN bank_accounts fa ON fa.id = so.from_account_id
               JOIN users u          ON u.id  = fa.user_id
              WHERE so.id = ? LIMIT 1"
        );
        $stmt->execute([$params['id']]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->abort(HTTP_NOT_FOUND, 'Standing order not found.');
        }

        $this->view('standing-orders.show', [
            'title' => "Standing Order #{$order['id']}",
            'order' => $order,
        ]);
    }

    /**
     * PATCH /standing-orders/{id}/cancel
     */
    public function cancel(Request $request, array $params = []): void
    {
        $db = Database::getInstance();
        $db->prepare("UPDATE standing_orders SET status = 'cancelled' WHERE id = ?")
           ->execute([$params['id']]);
        $this->success(null, 'Standing order cancelled.');
    }
}
