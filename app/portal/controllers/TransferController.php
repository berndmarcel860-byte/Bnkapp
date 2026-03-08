<?php
/**
 * BnkApp Portal — Transfer Controller (internal + SEPA)
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;
use BnkPortal\Core\Session;
use BnkPortal\Middleware\CsrfMiddleware;

class TransferController extends Controller
{
    public function showForm(Request $request, array $params = []): void
    {
        $accounts = $this->getMyAccounts();
        $this->view('transfer.index', [
            'title'    => 'New Transfer',
            'accounts' => $accounts,
        ]);
    }

    public function execute(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $errors = $this->validate($request, [
            'from_account_id' => 'required|numeric',
            'to_iban'         => 'required|max:34',
            'amount'          => 'required|numeric',
        ]);

        if (!empty($errors)) {
            Session::flash('error', 'Please check the form fields.');
            $this->redirect('/transfer');
        }

        // Delegate to stored procedure
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("CALL sp_execute_transfer(?, ?, ?, ?, ?, @txn_id, @error)");
            $stmt->execute([
                $request->input('from_account_id'),
                $request->input('to_iban'),
                $request->input('amount'),
                $request->input('currency_code', 'EUR'),
                $request->input('description'),
            ]);
            $result = $db->query("SELECT @txn_id AS txn_id, @error AS error")->fetch();

            if ($result['error']) {
                Session::flash('error', $result['error']);
                $this->redirect('/transfer');
            }

            Session::flash('success', 'Transfer submitted successfully.');
            $this->redirect('/transactions/' . $result['txn_id']);
        } catch (\PDOException $e) {
            Session::flash('error', 'Transfer failed. Please try again.');
            $this->redirect('/transfer');
        }
    }

    public function sepaForm(Request $request, array $params = []): void
    {
        $accounts     = $this->getMyAccounts();
        $beneficiaries = Database::getInstance()->prepare(
            "SELECT * FROM beneficiaries WHERE user_id = ? ORDER BY account_holder_name"
        );
        $beneficiaries->execute([Auth::id()]);

        $this->view('transfer.sepa', [
            'title'         => 'SEPA Credit Transfer',
            'accounts'      => $accounts,
            'beneficiaries' => $beneficiaries->fetchAll(),
        ]);
    }

    public function sepaExecute(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $errors = $this->validate($request, [
            'from_account_id' => 'required|numeric',
            'creditor_iban'   => 'required|max:34',
            'creditor_name'   => 'required|max:255',
            'amount'          => 'required|numeric',
        ]);

        if (!empty($errors)) {
            Session::flash('error', 'Please check the form fields.');
            $this->redirect('/transfer/sepa');
        }

        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("CALL sp_execute_transfer(?, ?, ?, ?, ?, @txn_id, @error)");
            $stmt->execute([
                $request->input('from_account_id'),
                $request->input('creditor_iban'),
                $request->input('amount'),
                $request->input('currency_code', 'EUR'),
                $request->input('remittance_information'),
            ]);
            $result = $db->query("SELECT @txn_id AS txn_id, @error AS error")->fetch();

            if ($result['error']) {
                Session::flash('error', $result['error']);
                $this->redirect('/transfer/sepa');
            }

            Session::flash('success', 'SEPA transfer submitted.');
            $this->redirect('/transactions/' . $result['txn_id']);
        } catch (\PDOException $e) {
            Session::flash('error', 'SEPA transfer failed. Please try again.');
            $this->redirect('/transfer/sepa');
        }
    }

    private function getMyAccounts(): array
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT ba.id, ba.iban, ba.currency_code, ba.balance
               FROM bank_accounts ba
              WHERE ba.user_id = ? AND ba.status = 'active'
              ORDER BY ba.iban"
        );
        $stmt->execute([Auth::id()]);
        return $stmt->fetchAll();
    }
}
