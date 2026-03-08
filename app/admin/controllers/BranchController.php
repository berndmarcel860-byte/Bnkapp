<?php
/**
 * BnkApp Admin — Branch Controller
 *
 * CRUD for branches table.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class BranchController extends Controller
{
    /**
     * GET /branches
     */
    public function index(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $rows = $db->query(
            "SELECT b.*, c.name AS country_name, COUNT(ba.id) AS account_count
               FROM branches b
               LEFT JOIN countries c    ON c.id  = b.country_id
               LEFT JOIN bank_accounts ba ON ba.branch_id = b.id
              GROUP BY b.id
              ORDER BY b.name"
        )->fetchAll();

        $this->view('branches.index', [
            'title'    => 'Branches',
            'branches' => $rows,
        ]);
    }

    /**
     * GET /branches/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT b.*, c.name AS country_name FROM branches b
               LEFT JOIN countries c ON c.id = b.country_id
              WHERE b.id = ? LIMIT 1"
        );
        $stmt->execute([$params['id']]);
        $branch = $stmt->fetch();

        if (!$branch) {
            $this->abort(HTTP_NOT_FOUND, 'Branch not found.');
        }

        $accounts = $db->prepare(
            "SELECT ba.id, ba.iban, ba.currency_code, ba.balance, ba.status,
                    CONCAT(u.first_name,' ',u.last_name) AS owner_name
               FROM bank_accounts ba
               JOIN users u ON u.id = ba.user_id
              WHERE ba.branch_id = ? ORDER BY ba.opened_at DESC LIMIT 50"
        );
        $accounts->execute([$params['id']]);

        $this->view('branches.show', [
            'title'    => "Branch — {$branch['name']}",
            'branch'   => $branch,
            'accounts' => $accounts->fetchAll(),
        ]);
    }

    /**
     * POST /branches
     */
    public function store(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'name'       => 'required|max:255',
            'bank_code'  => 'required|max:20',
            'bic'        => 'required|max:11',
            'country_id' => 'required|numeric',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $db = Database::getInstance();
        $db->prepare(
            "INSERT INTO branches (name, bank_code, bic, country_id, address, city, postal_code, is_active)
             VALUES (?,?,?,?,?,?,?,1)"
        )->execute([
            $request->input('name'),
            $request->input('bank_code'),
            $request->input('bic'),
            $request->input('country_id'),
            $request->input('address'),
            $request->input('city'),
            $request->input('postal_code'),
        ]);

        $this->success(['id' => $db->lastInsertId()], 'Branch created.', HTTP_CREATED);
    }

    /**
     * PATCH /branches/{id}
     */
    public function update(Request $request, array $params = []): void
    {
        $db = Database::getInstance();
        $db->prepare(
            "UPDATE branches SET name=?, address=?, city=?, postal_code=?, is_active=? WHERE id=?"
        )->execute([
            $request->input('name'),
            $request->input('address'),
            $request->input('city'),
            $request->input('postal_code'),
            $request->input('is_active', 1),
            $params['id'],
        ]);
        $this->success(null, 'Branch updated.');
    }
}
