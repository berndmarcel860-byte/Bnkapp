<?php
/**
 * BnkApp Portal — Standing Order Controller
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;
use BnkPortal\Core\Session;
use BnkPortal\Middleware\CsrfMiddleware;

class StandingOrderController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT so.*, ba.iban AS from_iban
               FROM standing_orders so
               JOIN bank_accounts ba ON ba.id = so.from_account_id
              WHERE ba.user_id = ?
              ORDER BY so.created_at DESC"
        );
        $stmt->execute([Auth::id()]);

        $this->view('standing-orders.index', [
            'title'  => 'Standing Orders',
            'orders' => $stmt->fetchAll(),
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $errors = $this->validate($request, [
            'from_account_id'     => 'required|numeric',
            'to_iban'             => 'required|max:34',
            'amount'              => 'required|numeric',
            'frequency'           => 'required',
            'start_date'          => 'required',
        ]);

        if (!empty($errors)) {
            Session::flash('error', 'Please fill in all required fields.');
            $this->redirect('/standing-orders');
        }

        // Verify account belongs to user
        $db   = Database::getInstance();
        $acct = $db->prepare("SELECT id FROM bank_accounts WHERE id = ? AND user_id = ? LIMIT 1");
        $acct->execute([$request->input('from_account_id'), Auth::id()]);
        if (!$acct->fetch()) {
            $this->abort(HTTP_FORBIDDEN);
        }

        $db->prepare(
            "INSERT INTO standing_orders
                (from_account_id, to_iban, to_name, amount, currency_code, frequency,
                 start_date, end_date, description, status, next_execution_date)
             VALUES (?,?,?,?,?,?,?,?,?,'active',?)"
        )->execute([
            $request->input('from_account_id'),
            strtoupper(str_replace(' ', '', (string)$request->input('to_iban', ''))),
            $request->input('to_name'),
            $request->input('amount'),
            $request->input('currency_code', 'EUR'),
            $request->input('frequency'),
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('description'),
            $request->input('start_date'),
        ]);

        Session::flash('success', 'Standing order created.');
        $this->redirect('/standing-orders');
    }

    public function pause(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $this->updateStatus($params['id'], 'paused');
        Session::flash('success', 'Standing order paused.');
        $this->redirect('/standing-orders');
    }

    public function cancel(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $this->updateStatus($params['id'], 'cancelled');
        Session::flash('success', 'Standing order cancelled.');
        $this->redirect('/standing-orders');
    }

    private function updateStatus(string $id, string $status): void
    {
        $db   = Database::getInstance();
        // Verify ownership
        $stmt = $db->prepare(
            "UPDATE standing_orders so
                JOIN bank_accounts ba ON ba.id = so.from_account_id
               SET so.status = ?
             WHERE so.id = ? AND ba.user_id = ?"
        );
        $stmt->execute([$status, $id, Auth::id()]);
    }
}
