<?php
/**
 * BnkApp Portal — Dashboard Controller
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;

class DashboardController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $userId = Auth::id();
        $db     = Database::getInstance();

        // Fetch summary data
        $accounts = $db->prepare(
            "SELECT ba.*, at.name AS account_type_name
               FROM bank_accounts ba
               LEFT JOIN account_types at ON at.id = ba.account_type_id
              WHERE ba.user_id = ? AND ba.status = 'active'
              ORDER BY ba.opened_at"
        );
        $accounts->execute([$userId]);
        $accountList = $accounts->fetchAll();

        // Total balance
        $totalBalance = array_sum(array_column($accountList, 'balance'));

        // Recent transactions (last 10)
        $txnStmt = $db->prepare(
            "SELECT t.*, fa.iban AS from_iban, ta.iban AS to_iban,
                    fa.user_id AS from_user_id, ta.user_id AS to_user_id
               FROM transactions t
               LEFT JOIN bank_accounts fa ON fa.id = t.from_account_id
               LEFT JOIN bank_accounts ta ON ta.id = t.to_account_id
              WHERE fa.user_id = ? OR ta.user_id = ?
              ORDER BY t.created_at DESC
              LIMIT 10"
        );
        $txnStmt->execute([$userId, $userId]);

        // Pending notifications count
        $notifStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $notifStmt->execute([$userId]);
        $notifCount = (int)$notifStmt->fetchColumn();

        // Active loans
        $loanStmt = $db->prepare("SELECT COUNT(*) FROM loans WHERE user_id = ? AND status = 'active'");
        $loanStmt->execute([$userId]);
        $activeLoanCount = (int)$loanStmt->fetchColumn();

        $this->view('dashboard.index', [
            'title'          => 'My Dashboard',
            'accounts'       => $accountList,
            'totalBalance'   => $totalBalance,
            'transactions'   => $txnStmt->fetchAll(),
            'notifCount'     => $notifCount,
            'activeLoanCount'=> $activeLoanCount,
        ]);
    }
}
