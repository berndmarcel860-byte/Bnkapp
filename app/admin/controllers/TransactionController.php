<?php
/**
 * BnkApp Admin — Transaction Controller
 *
 * View transactions, post deposits, execute transfers, and reverse payments.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Auth;
use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;
use BnkApp\Models\Transaction;

class TransactionController extends Controller
{
    private Transaction $txnModel;

    public function __construct()
    {
        $this->txnModel = new Transaction();
    }

    /**
     * GET /transactions
     */
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)($request->query('page', 1));
        $type   = $request->query('type', '');
        $status = $request->query('status', '');
        $from   = $request->query('from', '');
        $to     = $request->query('to', '');

        [$where, $bindings] = $this->txnModel->buildFilters($type, $status, $from, $to);
        $paginated = $this->txnModel->paginate($page, 25, $where, $bindings);

        $this->view('transactions.index', [
            'title'        => 'Transactions',
            'transactions' => $paginated['data'],
            'pagination'   => $paginated,
            'filter'       => compact('type', 'status', 'from', 'to'),
        ]);
    }

    /**
     * GET /transactions/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $txn = $this->txnModel->findWithDetails((int)$params['id']);
        if ($txn === null) {
            $this->abort(HTTP_NOT_FOUND, 'Transaction not found.');
        }

        $this->view('transactions.show', [
            'title'       => "Transaction #{$txn['id']}",
            'transaction' => $txn,
        ]);
    }

    /**
     * POST /transactions/deposit
     */
    public function deposit(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'account_id'    => 'required|numeric',
            'amount'        => 'required|numeric',
            'currency_code' => 'required|max:3',
            'description'   => 'required|max:500',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $db = Database::getInstance();

        try {
            $db->exec('SET @dep_ref = NULL; SET @dep_id = NULL;');
            $db->prepare('CALL sp_deposit(:account_id, :amount, :currency, :description, :user_id, @dep_ref, @dep_id)')
               ->execute([
                   ':account_id'  => $request->input('account_id'),
                   ':amount'      => $request->input('amount'),
                   ':currency'    => strtoupper((string)$request->input('currency_code')),
                   ':description' => $request->input('description'),
                   ':user_id'     => Auth::id(),
               ]);

            $result = $db->query('SELECT @dep_ref AS ref, @dep_id AS id')->fetch();

            $this->success(['transaction_ref' => $result['ref'], 'id' => $result['id']], 'Deposit posted.', HTTP_CREATED);
        } catch (\PDOException $e) {
            $this->error('Deposit failed: ' . $e->getMessage(), HTTP_BAD_REQUEST);
        }
    }

    /**
     * POST /transactions/transfer
     */
    public function transfer(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'from_account_id' => 'required|numeric',
            'to_account_id'   => 'required|numeric',
            'amount'          => 'required|numeric',
            'currency_code'   => 'required|max:3',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $db = Database::getInstance();

        try {
            $db->exec('SET @tr_ref = NULL; SET @tr_id = NULL;');
            $db->prepare('CALL sp_execute_transfer(:from_id, :to_id, :amount, :currency, :desc, :ref, :e2e, :user_id, @tr_ref, @tr_id)')
               ->execute([
                   ':from_id'  => $request->input('from_account_id'),
                   ':to_id'    => $request->input('to_account_id'),
                   ':amount'   => $request->input('amount'),
                   ':currency' => strtoupper((string)$request->input('currency_code')),
                   ':desc'     => $request->input('description', ''),
                   ':ref'      => $request->input('reference', ''),
                   ':e2e'      => $request->input('end_to_end_id'),
                   ':user_id'  => Auth::id(),
               ]);

            $result = $db->query('SELECT @tr_ref AS ref, @tr_id AS id')->fetch();

            $this->success(['transaction_ref' => $result['ref'], 'id' => $result['id']], 'Transfer completed.', HTTP_CREATED);
        } catch (\PDOException $e) {
            $this->error('Transfer failed: ' . $e->getMessage(), HTTP_BAD_REQUEST);
        }
    }

    /**
     * POST /transactions/{id}/reverse
     */
    public function reverse(Request $request, array $params = []): void
    {
        $txn = $this->txnModel->find((int)$params['id']);
        if ($txn === null) {
            $this->abort(HTTP_NOT_FOUND, 'Transaction not found.');
        }

        if ($txn['status'] !== TXN_COMPLETED) {
            $this->error('Only completed transactions can be reversed.');
        }

        $this->txnModel->update((int)$params['id'], ['status' => TXN_REVERSED]);
        $this->success(null, 'Transaction reversed.');
    }
}
