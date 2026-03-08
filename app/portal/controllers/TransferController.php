<?php
/**
 * BnkApp Portal — Transfer Controller (internal + SEPA)
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\EmailService;
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
        $fmt = new \NumberFormatter('en_GB', \NumberFormatter::CURRENCY);
        try {
            // Fetch and lock sender account
            $accStmt = $db->prepare(
                "SELECT ba.iban, ba.available_balance, ba.status,
                        ba.account_type_id,
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

            // Look up applicable SEPA/wire transfer fee from fee_schedules
            $feeAmount = $this->lookupFee($db, (int)($sender['account_type_id'] ?? 0), $amount);

            $totalDebit = $amount + $feeAmount;

            if ((float)$sender['available_balance'] < $totalDebit) {
                if ($feeAmount > 0) {
                    throw new \RuntimeException(
                        'Insufficient available balance. A transfer fee of ' .
                        $fmt->formatCurrency($feeAmount, $currency) . ' applies.'
                    );
                }
                throw new \RuntimeException('Insufficient available balance.');
            }

            $txnRef   = sprintf(
                'SEPA-%s-%s',
                date('Ymd'),
                strtoupper(substr(bin2hex(random_bytes(8)), 0, 12))
            );
            // Fee is charged on top of the transfer amount.
            // net_amount = what the creditor receives (full transfer amount).
            $netAmount = $amount;

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

            // Debit sender (amount + fee)
            $db->prepare(
                "UPDATE bank_accounts
                    SET balance           = balance           - ?,
                        available_balance = available_balance - ?,
                        last_transaction_at = NOW()
                  WHERE id = ?"
            )->execute([$totalDebit, $totalDebit, $fromId]);

            $db->commit();

            // Send confirmation email to customer
            $userStmt = $db->prepare("SELECT email, CONCAT(first_name,' ',last_name) AS full_name FROM users WHERE id = ? LIMIT 1");
            $userStmt->execute([Auth::id()]);
            $user = $userStmt->fetch();
            if ($user) {
                try {
                    (new EmailService())->sendTemplate('transfer_submitted', $user['email'], $user['full_name'], [
                        'customer_name' => $user['full_name'],
                        'amount'        => $fmt->formatCurrency($amount, $currency),
                        'fee'           => $fmt->formatCurrency($feeAmount, $currency),
                        'from_iban'     => $sender['iban'],
                        'to_iban'       => $creditorIban,
                        'creditor_name' => (string)($request->input('creditor_name') ?? ''),
                        'reference'     => $txnRef,
                        'status'        => 'Pending',
                    ]);
                } catch (\Throwable) {
                    // Non-fatal — do not prevent redirect on email failure
                }
            }

            if ($feeAmount > 0) {
                $feeStr = $fmt->formatCurrency($feeAmount, $currency);
                Session::flash('success', "SEPA transfer submitted successfully (fee applied: {$feeStr}).");
            } else {
                Session::flash('success', 'SEPA transfer submitted successfully.');
            }
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

    /**
     * Look up the applicable fee from fee_schedules for a SEPA/wire transfer.
     * Returns the fee amount (0.00 if none found).
     */
    private function lookupFee(\PDO $db, int $accountTypeId, float $amount): float
    {
        // Try account-type-specific fee first, then fall back to universal fee
        $stmt = $db->prepare(
            "SELECT fixed_amount, percentage, min_fee, max_fee
               FROM fee_schedules
              WHERE fee_type IN ('wire_transfer','transaction')
                AND is_active = 1
                AND (account_type_id = ? OR account_type_id IS NULL)
                AND (effective_to IS NULL OR effective_to >= CURDATE())
                AND effective_from <= CURDATE()
              ORDER BY (account_type_id IS NULL) ASC  -- prefer specific over universal
              LIMIT 1"
        );
        $stmt->execute([$accountTypeId]);
        $fee = $stmt->fetch();

        if (!$fee) {
            return 0.00;
        }

        $calculated = 0.00;
        if ($fee['fixed_amount'] !== null) {
            $calculated += (float)$fee['fixed_amount'];
        }
        if ($fee['percentage'] !== null) {
            $calculated += $amount * (float)$fee['percentage'];
        }

        if ($fee['min_fee'] !== null) {
            $calculated = max($calculated, (float)$fee['min_fee']);
        }
        if ($fee['max_fee'] !== null) {
            $calculated = min($calculated, (float)$fee['max_fee']);
        }

        return round($calculated, 2);
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
