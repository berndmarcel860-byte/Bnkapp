<?php
/**
 * BnkApp Admin — Exchange Rate Controller
 *
 * Manage FX rates (exchange_rates table).
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class ExchangeRateController extends Controller
{
    /**
     * GET /exchange-rates
     */
    public function index(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $rows = $db->query(
            "SELECT * FROM exchange_rates
              ORDER BY base_currency, target_currency, effective_at DESC"
        )->fetchAll();

        $this->view('exchange-rates.index', [
            'title' => 'Exchange Rates',
            'rates' => $rows,
        ]);
    }

    /**
     * POST /exchange-rates
     */
    public function store(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'base_currency'   => 'required|max:3',
            'target_currency' => 'required|max:3',
            'rate'            => 'required|numeric',
            'effective_at'    => 'required',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $db = Database::getInstance();
        $db->prepare(
            "INSERT INTO exchange_rates (base_currency, target_currency, rate, source, effective_at)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            strtoupper((string)$request->input('base_currency')),
            strtoupper((string)$request->input('target_currency')),
            $request->input('rate'),
            $request->input('source', 'manual'),
            $request->input('effective_at'),
        ]);

        $this->success(['id' => $db->lastInsertId()], 'Exchange rate added.', HTTP_CREATED);
    }

    /**
     * DELETE /exchange-rates/{id}
     */
    public function destroy(Request $request, array $params = []): void
    {
        Database::getInstance()->prepare('DELETE FROM exchange_rates WHERE id = ?')->execute([$params['id']]);
        $this->success(null, 'Rate removed.');
    }
}
