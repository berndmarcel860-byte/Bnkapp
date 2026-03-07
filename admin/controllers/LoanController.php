<?php
/**
 * BnkApp Admin — Loan Controller
 *
 * List, view, approve, and reject loan applications.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Auth;
use BnkApp\Core\Controller;
use BnkApp\Core\Request;
use BnkApp\Models\Loan;

class LoanController extends Controller
{
    private Loan $loanModel;

    public function __construct()
    {
        $this->loanModel = new Loan();
    }

    /**
     * GET /loans
     */
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)($request->query('page', 1));
        $status = $request->query('status', '');

        $where    = '';
        $bindings = [];

        if ($status !== '') {
            $where    = 'status = ?';
            $bindings = [$status];
        }

        $paginated = $this->loanModel->paginate($page, 25, $where, $bindings);

        $this->view('loans.index', [
            'title'      => 'Loans',
            'loans'      => $paginated['data'],
            'pagination' => $paginated,
            'filter'     => ['status' => $status],
        ]);
    }

    /**
     * GET /loans/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $loan = $this->loanModel->findWithSchedule((int)$params['id']);
        if ($loan === null) {
            $this->abort(HTTP_NOT_FOUND, 'Loan not found.');
        }

        $this->view('loans.show', [
            'title' => "Loan #{$loan['id']}",
            'loan'  => $loan,
        ]);
    }

    /**
     * POST /loans
     * Staff-initiated loan application on behalf of a customer.
     */
    public function store(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'user_id'          => 'required|numeric',
            'account_id'       => 'required|numeric',
            'loan_type'        => 'required|in:personal,mortgage,auto,business,student,overdraft',
            'principal_amount' => 'required|numeric',
            'interest_rate'    => 'required|numeric',
            'term_months'      => 'required|numeric',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $principal    = (float)$request->input('principal_amount');
        $rate         = (float)$request->input('interest_rate');
        $termMonths   = (int)$request->input('term_months');
        $monthlyRate  = $rate / 12;

        // Standard amortisation formula: M = P * [r(1+r)^n] / [(1+r)^n - 1]
        $monthlyPayment = $monthlyRate > 0
            ? $principal * ($monthlyRate * (1 + $monthlyRate) ** $termMonths)
                        / ((1 + $monthlyRate) ** $termMonths - 1)
            : $principal / $termMonths;

        $id = $this->loanModel->create([
            'user_id'           => $request->input('user_id'),
            'account_id'        => $request->input('account_id'),
            'loan_type'         => $request->input('loan_type'),
            'purpose'           => $request->input('purpose'),
            'principal_amount'  => $principal,
            'outstanding_balance' => $principal,
            'interest_rate'     => $rate,
            'term_months'       => $termMonths,
            'monthly_payment'   => round($monthlyPayment, 2),
            'status'            => LOAN_APPLIED,
        ]);

        $this->success(['id' => $id], 'Loan application created.', HTTP_CREATED);
    }

    /**
     * PATCH /loans/{id}/approve
     */
    public function approve(Request $request, array $params = []): void
    {
        $loan = $this->loanModel->find((int)$params['id']);
        if ($loan === null) {
            $this->abort(HTTP_NOT_FOUND, 'Loan not found.');
        }

        if (!in_array($loan['status'], [LOAN_APPLIED, LOAN_IN_REVIEW], true)) {
            $this->error('This loan cannot be approved in its current status.');
        }

        $this->loanModel->update((int)$params['id'], [
            'status'      => LOAN_APPROVED,
            'approved_by' => Auth::id(),
        ]);

        $this->success(null, 'Loan approved.');
    }

    /**
     * PATCH /loans/{id}/reject
     */
    public function reject(Request $request, array $params = []): void
    {
        $loan = $this->loanModel->find((int)$params['id']);
        if ($loan === null) {
            $this->abort(HTTP_NOT_FOUND, 'Loan not found.');
        }

        $this->loanModel->update((int)$params['id'], ['status' => LOAN_REJECTED]);
        $this->success(null, 'Loan rejected.');
    }
}
