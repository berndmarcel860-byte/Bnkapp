<?php
/**
 * BnkApp Admin — Fee Schedule Controller
 *
 * CRUD for fee_schedules table.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class FeeScheduleController extends Controller
{
    /**
     * GET /fee-schedules
     */
    public function index(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $rows = $db->query(
            "SELECT fs.*, at.name AS account_type_name
               FROM fee_schedules fs
               LEFT JOIN account_types at ON at.id = fs.account_type_id
              WHERE fs.is_active = 1
              ORDER BY fs.fee_type, fs.currency_code"
        )->fetchAll();

        $this->view('fee-schedules.index', [
            'title' => 'Fee Schedules',
            'fees'  => $rows,
        ]);
    }

    /**
     * POST /fee-schedules
     */
    public function store(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'name'           => 'required|max:100',
            'fee_type'       => 'required|max:50',
            'fixed_amount'   => 'required|numeric',
            'percentage'     => 'required|numeric',
            'currency_code'  => 'required|max:3',
            'effective_from' => 'required',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $db = Database::getInstance();
        $db->prepare(
            "INSERT INTO fee_schedules
                (name, account_type_id, fee_type, currency_code, fixed_amount, percentage, min_fee, max_fee, effective_from, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
        )->execute([
            $request->input('name'),
            $request->input('account_type_id'),
            $request->input('fee_type'),
            strtoupper((string)$request->input('currency_code')),
            $request->input('fixed_amount'),
            $request->input('percentage'),
            $request->input('min_fee') ?? 0,
            $request->input('max_fee'),
            $request->input('effective_from'),
        ]);

        $this->success(['id' => $db->lastInsertId()], 'Fee schedule created.', HTTP_CREATED);
    }

    /**
     * PATCH /fee-schedules/{id}
     */
    public function update(Request $request, array $params = []): void
    {
        $db = Database::getInstance();
        $db->prepare(
            "UPDATE fee_schedules SET fixed_amount=?, percentage=?, min_fee=?, max_fee=?, is_active=? WHERE id=?"
        )->execute([
            $request->input('fixed_amount'),
            $request->input('percentage'),
            $request->input('min_fee') ?? 0,
            $request->input('max_fee'),
            $request->input('is_active', 1),
            $params['id'],
        ]);
        $this->success(null, 'Fee schedule updated.');
    }

    /**
     * DELETE /fee-schedules/{id}
     */
    public function destroy(Request $request, array $params = []): void
    {
        $db = Database::getInstance();
        $db->prepare('UPDATE fee_schedules SET is_active = 0 WHERE id = ?')->execute([$params['id']]);
        $this->success(null, 'Fee schedule deactivated.');
    }
}
