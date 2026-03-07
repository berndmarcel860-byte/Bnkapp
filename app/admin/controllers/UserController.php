<?php
/**
 * BnkApp Admin — User Controller
 *
 * CRUD for customers and staff; KYC status management.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Request;
use BnkApp\Models\User;

class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * GET /users
     */
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)($request->query('page', 1));
        $search = trim((string)$request->query('search', ''));

        $where    = '';
        $bindings = [];

        if ($search !== '') {
            $where    = "CONCAT(first_name, ' ', last_name, ' ', email) LIKE ?";
            $bindings = ["%{$search}%"];
        }

        $paginated = $this->userModel->paginate($page, 25, $where, $bindings);

        $this->view('users.index', [
            'title'     => 'Users',
            'users'     => $paginated['data'],
            'pagination'=> $paginated,
            'search'    => $search,
        ]);
    }

    /**
     * GET /users/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $user = $this->userModel->find((int)$params['id']);
        if ($user === null) {
            $this->abort(HTTP_NOT_FOUND, 'User not found.');
        }

        $accounts = $this->userModel->getAccounts((int)$params['id']);
        $kycDocs  = $this->userModel->getKycDocuments((int)$params['id']);

        $this->view('users.show', [
            'title'    => "User — {$user['first_name']} {$user['last_name']}",
            'user'     => $user,
            'accounts' => $accounts,
            'kycDocs'  => $kycDocs,
        ]);
    }

    /**
     * POST /users
     * Create a new user (staff-initiated, e.g. teller-opened account).
     */
    public function store(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'first_name'    => 'required|max:100',
            'last_name'     => 'required|max:100',
            'email'         => 'required|email|max:255',
            'password'      => 'required|min:12',
            'date_of_birth' => 'required',
            'role_id'       => 'required|numeric',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $cfg      = require CONFIG_PATH . '/config.php';
        $hash     = password_hash((string)$request->input('password'), PASSWORD_BCRYPT, ['cost' => $cfg['security']['bcrypt_cost']]);
        $salt     = bin2hex(random_bytes(16));

        $id = $this->userModel->create([
            'role_id'       => $request->input('role_id'),
            'first_name'    => $request->input('first_name'),
            'last_name'     => $request->input('last_name'),
            'email'         => strtolower(trim((string)$request->input('email'))),
            'phone'         => $request->input('phone'),
            'date_of_birth' => $request->input('date_of_birth'),
            'national_id'   => $request->input('national_id'),
            'password_hash' => $hash,
            'password_salt' => $salt,
        ]);

        $this->success(['id' => $id], 'User created.', HTTP_CREATED);
    }

    /**
     * PATCH /users/{id}
     */
    public function update(Request $request, array $params = []): void
    {
        $user = $this->userModel->find((int)$params['id']);
        if ($user === null) {
            $this->abort(HTTP_NOT_FOUND, 'User not found.');
        }

        $allowed = $request->only('first_name', 'last_name', 'phone', 'address_line1',
                                  'address_line2', 'city', 'postal_code', 'country_id', 'is_active');
        $this->userModel->update((int)$params['id'], $allowed);

        $this->success(null, 'User updated.');
    }

    /**
     * DELETE /users/{id}
     * Soft-deactivates a user (sets is_active = 0).
     */
    public function destroy(Request $request, array $params = []): void
    {
        $user = $this->userModel->find((int)$params['id']);
        if ($user === null) {
            $this->abort(HTTP_NOT_FOUND, 'User not found.');
        }

        $this->userModel->update((int)$params['id'], ['is_active' => 0]);
        $this->success(null, 'User deactivated.');
    }

    /**
     * PATCH /users/{id}/kyc
     * Approve or reject a user's KYC status.
     */
    public function updateKyc(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'kyc_status' => 'required|in:pending,in_review,approved,rejected',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $user = $this->userModel->find((int)$params['id']);
        if ($user === null) {
            $this->abort(HTTP_NOT_FOUND, 'User not found.');
        }

        $status = (string)$request->input('kyc_status');
        $data   = ['kyc_status' => $status];

        if ($status === 'approved') {
            $data['kyc_approved_at'] = date('Y-m-d H:i:s');
        }

        $this->userModel->update((int)$params['id'], $data);
        $this->success(null, "KYC status updated to '{$status}'.");
    }
}
