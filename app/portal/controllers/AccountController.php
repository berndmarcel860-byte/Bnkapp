<?php
/**
 * BnkApp Portal — Account Controller
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;

class AccountController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT ba.*, at.name AS account_type_name
               FROM bank_accounts ba
               LEFT JOIN account_types at ON at.id = ba.account_type_id
              WHERE ba.user_id = ?
              ORDER BY ba.opened_at DESC"
        );
        $stmt->execute([Auth::id()]);

        $this->view('accounts.index', [
            'title'    => 'My Accounts',
            'accounts' => $stmt->fetchAll(),
        ]);
    }

    public function show(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT ba.*, at.name AS account_type_name
               FROM bank_accounts ba
               LEFT JOIN account_types at ON at.id = ba.account_type_id
              WHERE ba.id = ? AND ba.user_id = ? LIMIT 1"
        );
        $stmt->execute([$params['id'], Auth::id()]);
        $account = $stmt->fetch();

        if (!$account) {
            $this->abort(HTTP_NOT_FOUND, 'Account not found.');
        }

        $txnStmt = $db->prepare(
            "SELECT t.*, fa.iban AS from_iban, ta.iban AS to_iban
               FROM transactions t
               LEFT JOIN bank_accounts fa ON fa.id = t.from_account_id
               LEFT JOIN bank_accounts ta ON ta.id = t.to_account_id
              WHERE t.from_account_id = ? OR t.to_account_id = ?
              ORDER BY t.created_at DESC LIMIT 50"
        );
        $txnStmt->execute([$account['id'], $account['id']]);

        $this->view('accounts.show', [
            'title'        => 'Account ' . $account['iban'],
            'account'      => $account,
            'transactions' => $txnStmt->fetchAll(),
        ]);
    }
}
