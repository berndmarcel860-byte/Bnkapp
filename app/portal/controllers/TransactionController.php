<?php
/**
 * BnkApp Portal — Transaction Controller
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;

class TransactionController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)$request->query('page', 1);
        $offset = ($page - 1) * 25;
        $userId = Auth::id();
        $db     = Database::getInstance();

        $countStmt = $db->prepare(
            "SELECT COUNT(*) FROM transactions t
               LEFT JOIN bank_accounts fa ON fa.id = t.from_account_id
               LEFT JOIN bank_accounts ta ON ta.id = t.to_account_id
              WHERE fa.user_id = ? OR ta.user_id = ?"
        );
        $countStmt->execute([$userId, $userId]);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT t.*, fa.iban AS from_iban, ta.iban AS to_iban
               FROM transactions t
               LEFT JOIN bank_accounts fa ON fa.id = t.from_account_id
               LEFT JOIN bank_accounts ta ON ta.id = t.to_account_id
              WHERE fa.user_id = ? OR ta.user_id = ?
              ORDER BY t.created_at DESC
              LIMIT 25 OFFSET {$offset}"
        );
        $stmt->execute([$userId, $userId]);

        $this->view('transactions.index', [
            'title'        => 'Transactions',
            'transactions' => $stmt->fetchAll(),
            'total'        => $total,
            'page'         => $page,
            'lastPage'     => (int)ceil($total / 25),
        ]);
    }

    public function show(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT t.*, fa.iban AS from_iban, ta.iban AS to_iban,
                    fa.user_id AS from_user_id, ta.user_id AS to_user_id
               FROM transactions t
               LEFT JOIN bank_accounts fa ON fa.id = t.from_account_id
               LEFT JOIN bank_accounts ta ON ta.id = t.to_account_id
              WHERE t.id = ? LIMIT 1"
        );
        $stmt->execute([$params['id']]);
        $txn = $stmt->fetch();

        // Security: only show transactions belonging to this user
        if (!$txn || ($txn['from_user_id'] !== Auth::id() && $txn['to_user_id'] !== Auth::id())) {
            $this->abort(HTTP_NOT_FOUND);
        }

        $this->view('transactions.show', [
            'title'       => "Transaction #{$txn['id']}",
            'transaction' => $txn,
        ]);
    }
}
