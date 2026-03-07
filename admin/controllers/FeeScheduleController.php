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
              ORDER BY fs.transaction_type, fs.currency_code"
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
            'transaction_type' => 'required|max:50',
            'flat_fee'         => 'required|numeric',
            'percentage_fee'   => 'required|numeric',
            'currency_code'    => 'required|max:3',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $db = Database::getInstance();
        $db->prepare(
            "INSERT INTO fee_schedules
                (account_type_id, transaction_type, currency_code, flat_fee, percentage_fee, min_fee, max_fee, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
        )->execute([
            $request->input('account_type_id'),
            $request->input('transaction_type'),
            strtoupper((string)$request->input('currency_code')),
            $request->input('flat_fee'),
            $request->input('percentage_fee'),
            $request->input('min_fee') ?? 0,
            $request->input('max_fee'),
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
            "UPDATE fee_schedules SET flat_fee=?, percentage_fee=?, min_fee=?, max_fee=?, is_active=? WHERE id=?"
        )->execute([
            $request->input('flat_fee'),
            $request->input('percentage_fee'),
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
