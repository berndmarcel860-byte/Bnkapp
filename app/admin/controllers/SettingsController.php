<?php
/**
 * BnkApp Admin — Settings Controller
 *
 * Manage roles, permissions, account types, and countries.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class SettingsController extends Controller
{
    /**
     * GET /settings
     */
    public function index(Request $request, array $params = []): void
    {
        $db = Database::getInstance();

        $roles        = $db->query("SELECT * FROM roles ORDER BY name")->fetchAll();
        $permissions  = $db->query("SELECT * FROM permissions ORDER BY name")->fetchAll();
        $accountTypes = $db->query("SELECT * FROM account_types ORDER BY name")->fetchAll();
        $countries    = $db->query("SELECT * FROM countries WHERE is_sepa = 1 ORDER BY name")->fetchAll();

        $this->view('settings.index', [
            'title'        => 'Settings',
            'roles'        => $roles,
            'permissions'  => $permissions,
            'accountTypes' => $accountTypes,
            'countries'    => $countries,
        ]);
    }

    /**
     * POST /settings/account-types
     */
    public function storeAccountType(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'name' => 'required|max:100',
            'code' => 'required|max:20',
        ]);
        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $db = Database::getInstance();
        $db->prepare(
            "INSERT INTO account_types (name, code, description, min_balance, max_balance, interest_rate, is_active)
             VALUES (?,?,?,?,?,?,1)"
        )->execute([
            $request->input('name'),
            strtoupper(trim((string)$request->input('code'))),
            $request->input('description'),
            $request->input('min_balance', 0),
            $request->input('max_balance'),
            $request->input('interest_rate', 0),
        ]);
        $this->success(['id' => $db->lastInsertId()], 'Account type created.', HTTP_CREATED);
    }
}
