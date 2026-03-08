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

        $db = Database::getInstance();

        // Resolve destination IBAN → internal account ID
        $toIban  = strtoupper(str_replace(' ', '', (string)$request->input('to_iban', '')));
        $toAcct  = $db->prepare("SELECT id FROM bank_accounts WHERE iban = ? LIMIT 1");
        $toAcct->execute([$toIban]);
        $toRow   = $toAcct->fetch();

        if (!$toRow) {
            Session::flash('error', 'No account found with that IBAN. Please check the destination IBAN.');
            $this->redirect('/transfer');
        }

        try {
            // sp_execute_transfer(from_id, to_id, amount, currency, description,
            //                     reference, end_to_end_id, initiated_by,
            //                     OUT txn_ref, OUT txn_id)
            $stmt = $db->prepare(
                "CALL sp_execute_transfer(?, ?, ?, ?, ?, ?, ?, ?, @p_txn_ref, @p_txn_id)"
            );
            $stmt->execute([
                $request->input('from_account_id'),
                $toRow['id'],
                $request->input('amount'),
                $request->input('currency_code', 'EUR'),
                $request->input('description'),
                null,            // p_reference
                null,            // p_end_to_end_id
                Auth::id(),      // p_initiated_by
            ]);
            $result = $db->query("SELECT @p_txn_ref AS txn_ref, @p_txn_id AS txn_id")->fetch();

            Session::flash('success', 'Transfer submitted successfully.');
            $this->redirect('/transactions/' . $result['txn_id']);
        } catch (\PDOException $e) {
            $msg = $e->getCode() === '45000' ? $e->getMessage() : 'Transfer failed. Please try again.';
            Session::flash('error', $msg);
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

        $db           = Database::getInstance();
        $creditorIban = strtoupper(str_replace(' ', '', (string)$request->input('creditor_iban', '')));
        $amount       = (float)$request->input('amount');
        $currency     = $request->input('currency_code', 'EUR');
        $remittance   = $request->input('remittance_information');
        $fromId       = (int)$request->input('from_account_id');

        // Check whether the destination IBAN belongs to an account in this bank
        $internalStmt = $db->prepare("SELECT id FROM bank_accounts WHERE iban = ? LIMIT 1");
        $internalStmt->execute([$creditorIban]);
        $internalAcct = $internalStmt->fetch();

        if ($internalAcct) {
            // On-us: route as internal transfer via stored procedure
            try {
                $stmt = $db->prepare(
                    "CALL sp_execute_transfer(?, ?, ?, ?, ?, ?, ?, ?, @p_txn_ref, @p_txn_id)"
                );
                $stmt->execute([
                    $fromId,
                    $internalAcct['id'],
                    $amount,
                    $currency,
                    $remittance,
                    null,       // p_reference
                    null,       // p_end_to_end_id
                    Auth::id(), // p_initiated_by
                ]);
                $result = $db->query("SELECT @p_txn_ref AS txn_ref, @p_txn_id AS txn_id")->fetch();

                Session::flash('success', 'SEPA transfer submitted.');
                $this->redirect('/transactions/' . $result['txn_id']);
            } catch (\PDOException $e) {
                $msg = $e->getCode() === '45000' ? $e->getMessage() : 'SEPA transfer failed. Please try again.';
                Session::flash('error', $msg);
                $this->redirect('/transfer/sepa');
            }
        }

        // External SEPA: debit sender and record as sepa_credit_transfer
        $db->beginTransaction();
        try {
            // Fetch and lock sender account
            $accStmt = $db->prepare(
                "SELECT ba.iban, ba.available_balance, ba.status,
                        CONCAT(u.first_name, ' ', u.last_name) AS owner_name
                   FROM bank_accounts ba
                   JOIN users u ON u.id = ba.user_id
                  WHERE ba.id = ? AND ba.user_id = ?
                  LIMIT 1 FOR UPDATE"
            );
            $accStmt->execute([$fromId, Auth::id()]);
            $sender = $accStmt->fetch();

            if (!$sender) {
                throw new \RuntimeException('Source account not found.');
            }

            if ($sender['status'] !== 'active') {
                throw new \RuntimeException('Source account is not active.');
            }

            if ((float)$sender['available_balance'] < $amount) {
                throw new \RuntimeException('Insufficient available balance.');
            }

            $txnRef   = sprintf(
                'SEPA-%s-%s',
                date('Ymd'),
                strtoupper(substr(bin2hex(random_bytes(8)), 0, 12))
            );
            $feeAmount = 0.00;
            $netAmount = $amount - $feeAmount;

            // Insert transaction
            $txnStmt = $db->prepare(
                "INSERT INTO transactions
                    (transaction_ref, from_account_id, to_account_id,
                     transaction_type, amount, currency_code,
                     fee_amount, net_amount, status,
                     description, reference,
                     booking_date, value_date, initiated_by)
                 VALUES (?, ?, NULL, 'sepa_credit_transfer', ?, ?, ?, ?, 'pending', ?, ?,
                         CURDATE(), CURDATE(), ?)"
            );
            $txnStmt->execute([
                $txnRef,
                $fromId,
                $amount,
                $currency,
                $feeAmount,
                $netAmount,
                $remittance,
                $txnRef,
                Auth::id(),
            ]);
            $txnId = (int)$db->lastInsertId();

            // Insert SEPA-specific details
            $sepaStmt = $db->prepare(
                "INSERT INTO sepa_transfers
                    (transaction_id, sepa_type,
                     debtor_name, debtor_iban,
                     creditor_name, creditor_iban,
                     remittance_info,
                     requested_execution_date)
                 VALUES (?, 'SCT', ?, ?, ?, ?, ?, CURDATE())"
            );
            $sepaStmt->execute([
                $txnId,
                $sender['owner_name'],
                $sender['iban'],
                $request->input('creditor_name'),
                $creditorIban,
                $remittance,
            ]);

            // Debit sender
            $db->prepare(
                "UPDATE bank_accounts
                    SET balance           = balance           - ?,
                        available_balance = available_balance - ?,
                        last_transaction_at = NOW()
                  WHERE id = ?"
            )->execute([$amount, $amount, $fromId]);

            $db->commit();

            Session::flash('success', 'SEPA transfer submitted successfully.');
            $this->redirect('/transactions/' . $txnId);
        } catch (\RuntimeException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::flash('error', $e->getMessage());
            $this->redirect('/transfer/sepa');
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
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
