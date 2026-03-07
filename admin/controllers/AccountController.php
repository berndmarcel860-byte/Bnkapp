<?php
/**
 * BnkApp Admin — Account Controller
 *
 * Open, view, freeze, and close bank accounts.
 * Delegates IBAN generation to the sp_open_account stored procedure.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Auth;
use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;
use BnkApp\Models\BankAccount;

class AccountController extends Controller
{
    private BankAccount $accountModel;

    public function __construct()
    {
        $this->accountModel = new BankAccount();
    }

    /**
     * GET /accounts
     */
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)($request->query('page', 1));
        $status = $request->query('status', '');

        $where    = '';
        $bindings = [];

        if ($status !== '') {
            $where    = 'ba.status = ?';
            $bindings = [$status];
        }

        $paginated = $this->accountModel->paginate($page, 25, $where, $bindings);

        $this->view('accounts.index', [
            'title'      => 'Bank Accounts',
            'accounts'   => $paginated['data'],
            'pagination' => $paginated,
            'filter'     => ['status' => $status],
        ]);
    }

    /**
     * GET /accounts/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $account = $this->accountModel->findWithDetails((int)$params['id']);
        if ($account === null) {
            $this->abort(HTTP_NOT_FOUND, 'Account not found.');
        }

        $transactions = $this->accountModel->getRecentTransactions((int)$params['id'], 20);

        $this->view('accounts.show', [
            'title'        => "Account — {$account['iban']}",
            'account'      => $account,
            'transactions' => $transactions,
        ]);
    }

    /**
     * POST /accounts
     * Open a new account via sp_open_account stored procedure.
     */
    public function store(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'user_id'         => 'required|numeric',
            'account_type_id' => 'required|numeric',
            'branch_id'       => 'required|numeric',
            'country_code'    => 'required|max:2',
            'bank_code'       => 'required|max:20',
            'currency_code'   => 'required|max:3',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $db = Database::getInstance();

        try {
            // Call the stored procedure defined in 02_functions.sql
            $db->exec('SET @iban = NULL; SET @account_id = NULL;');

            $stmt = $db->prepare(
                'CALL sp_open_account(:user_id, :type_id, :branch_id, :country, :bank_code, :currency, :admin_id, @iban, @account_id)'
            );
            $stmt->execute([
                ':user_id'    => $request->input('user_id'),
                ':type_id'    => $request->input('account_type_id'),
                ':branch_id'  => $request->input('branch_id'),
                ':country'    => strtoupper((string)$request->input('country_code')),
                ':bank_code'  => $request->input('bank_code'),
                ':currency'   => strtoupper((string)$request->input('currency_code')),
                ':admin_id'   => Auth::id(),
            ]);

            $result = $db->query('SELECT @iban AS iban, @account_id AS account_id')->fetch();

            $this->success([
                'account_id' => (int)$result['account_id'],
                'iban'       => $result['iban'],
            ], 'Account opened successfully.', HTTP_CREATED);

        } catch (\PDOException $e) {
            $this->error('Could not open account: ' . $e->getMessage(), HTTP_BAD_REQUEST);
        }
    }

    /**
     * PATCH /accounts/{id}/freeze
     */
    public function freeze(Request $request, array $params = []): void
    {
        $account = $this->accountModel->find((int)$params['id']);
        if ($account === null) {
            $this->abort(HTTP_NOT_FOUND, 'Account not found.');
        }

        $newStatus = ($account['status'] === ACCOUNT_FROZEN) ? ACCOUNT_ACTIVE : ACCOUNT_FROZEN;
        $this->accountModel->update((int)$params['id'], ['status' => $newStatus]);

        $this->success(['status' => $newStatus], "Account status set to '{$newStatus}'.");
    }

    /**
     * PATCH /accounts/{id}/close
     */
    public function close(Request $request, array $params = []): void
    {
        $account = $this->accountModel->find((int)$params['id']);
        if ($account === null) {
            $this->abort(HTTP_NOT_FOUND, 'Account not found.');
        }

        $db = Database::getInstance();

        try {
            $db->prepare('CALL sp_close_account(:account_id, :closed_by)')
               ->execute([':account_id' => $params['id'], ':closed_by' => Auth::id()]);

            $this->success(null, 'Account closed.');
        } catch (\PDOException $e) {
            $this->error('Could not close account: ' . $e->getMessage(), HTTP_BAD_REQUEST);
        }
    }
}
