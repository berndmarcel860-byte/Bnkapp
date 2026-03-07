<?php
/**
 * BnkApp Admin — Dashboard Controller
 *
 * Renders the admin dashboard with key summary statistics.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class DashboardController extends Controller
{
    /**
     * GET /  |  GET /dashboard
     */
    public function index(Request $request, array $params = []): void
    {
        $db = Database::getInstance();

        // Aggregate statistics for the summary cards
        $stats = [
            'total_users'            => (int)$db->query("SELECT COUNT(*) FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'customer')")->fetchColumn(),
            'active_accounts'        => (int)$db->query("SELECT COUNT(*) FROM bank_accounts WHERE status = 'active'")->fetchColumn(),
            'pending_kyc'            => (int)$db->query("SELECT COUNT(*) FROM users WHERE kyc_status = 'pending'")->fetchColumn(),
            'transactions_today'     => (int)$db->query("SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
            'volume_today'           => (float)($db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE DATE(created_at) = CURDATE() AND status = 'completed'")->fetchColumn()),
            'pending_loans'          => (int)$db->query("SELECT COUNT(*) FROM loans WHERE status IN ('applied','in_review')")->fetchColumn(),
            'open_support_tickets'   => (int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn(),
        ];

        // Recent transactions (last 10)
        $recentTransactions = $db->query(
            "SELECT t.id, t.transaction_type, t.amount, t.currency_code, t.status,
                    t.created_at,
                    fa.iban AS from_iban, ta.iban AS to_iban
               FROM transactions t
               LEFT JOIN bank_accounts fa ON fa.id = t.from_account_id
               LEFT JOIN bank_accounts ta ON ta.id = t.to_account_id
              ORDER BY t.created_at DESC
              LIMIT 10"
        )->fetchAll();

        $this->view('dashboard.index', [
            'title'              => 'Dashboard',
            'stats'              => $stats,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
