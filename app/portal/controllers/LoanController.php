<?php
/**
 * BnkApp Portal — Loan Controller
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;
use BnkPortal\Core\Session;
use BnkPortal\Middleware\CsrfMiddleware;

class LoanController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT l.*, ba.iban AS disbursement_iban
               FROM loans l
               LEFT JOIN bank_accounts ba ON ba.id = l.account_id
              WHERE l.user_id = ?
              ORDER BY l.created_at DESC"
        );
        $stmt->execute([Auth::id()]);

        $this->view('loans.index', [
            'title' => 'My Loans',
            'loans' => $stmt->fetchAll(),
        ]);
    }

    public function show(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM loans WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$params['id'], Auth::id()]);
        $loan = $stmt->fetch();

        if (!$loan) {
            $this->abort(HTTP_NOT_FOUND);
        }

        $payments = $db->prepare(
            "SELECT * FROM loan_payments WHERE loan_id = ? ORDER BY due_date"
        );
        $payments->execute([$loan['id']]);

        $this->view('loans.show', [
            'title'    => "Loan #{$loan['id']}",
            'loan'     => $loan,
            'payments' => $payments->fetchAll(),
        ]);
    }

    public function apply(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $errors = $this->validate($request, [
            'loan_type'        => 'required',
            'principal_amount' => 'required|numeric',
            'term_months'      => 'required|numeric',
            'account_id'       => 'required|numeric',
        ]);

        if (!empty($errors)) {
            Session::flash('error', 'Please check the loan application form.');
            $this->redirect('/loans');
        }

        Database::getInstance()->prepare(
            "INSERT INTO loans
                (user_id, account_id, loan_type, principal_amount, outstanding_balance,
                 interest_rate, term_months, monthly_payment, status)
             VALUES (?, ?, ?, ?, ?, 0, ?, 0, 'applied')"
            // interest_rate=0 and monthly_payment=0 are placeholders; admin sets actual values on approval
        )->execute([
            Auth::id(),
            $request->input('account_id'),
            $request->input('loan_type'),
            $request->input('principal_amount'),
            $request->input('principal_amount'),
            $request->input('term_months'),
        ]);

        Session::flash('success', 'Loan application submitted. We will review it shortly.');
        $this->redirect('/loans');
    }
}
