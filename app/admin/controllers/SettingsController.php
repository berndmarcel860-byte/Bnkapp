<?php
/**
 * BnkApp Admin — Settings Controller
 *
 * Manage roles, permissions, account types, countries, and SMTP settings.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\EmailService;
use BnkApp\Core\Request;
use BnkApp\Middleware\CsrfMiddleware;

class SettingsController extends Controller
{
    // ------------------------------------------------------------------
    // GET /settings
    // ------------------------------------------------------------------

    public function index(Request $request, array $params = []): void
    {
        $db = Database::getInstance();

        $roles        = $db->query("SELECT * FROM roles ORDER BY name")->fetchAll();
        $permissions  = $db->query("SELECT * FROM permissions ORDER BY name")->fetchAll();
        $accountTypes = $db->query("SELECT * FROM account_types ORDER BY name")->fetchAll();
        $countries    = $db->query("SELECT * FROM countries WHERE is_sepa = 1 ORDER BY name")->fetchAll();
        $smtp         = $this->fetchSmtpSettings($db);

        $this->view('settings.index', [
            'title'        => 'Settings',
            'roles'        => $roles,
            'permissions'  => $permissions,
            'accountTypes' => $accountTypes,
            'countries'    => $countries,
            'smtp'         => $smtp,
        ]);
    }

    // ------------------------------------------------------------------
    // POST /settings/account-types
    // ------------------------------------------------------------------

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

    // ------------------------------------------------------------------
    // POST /settings/smtp   (save SMTP settings)
    // ------------------------------------------------------------------

    public function saveSmtp(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);

        $errors = $this->validate($request, [
            'host'         => 'max:255',
            'port'         => 'required',
            'from_address' => 'required|max:255',
            'from_name'    => 'required|max:100',
        ]);
        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $port       = max(1, min(65535, (int)$request->input('port', 587)));
        $encryption = in_array($request->input('encryption'), ['tls', 'ssl', 'none'], true)
            ? $request->input('encryption')
            : 'tls';

        $db = Database::getInstance();

        // Upsert: always keep id = 1
        $password = (string)$request->input('password', '');

        if ($password === '') {
            // Password field left blank → keep existing password
            $db->prepare(
                "UPDATE smtp_settings
                    SET host = ?, port = ?, encryption = ?,
                        username = ?, from_address = ?, from_name = ?
                  WHERE id = 1"
            )->execute([
                trim((string)$request->input('host', '')),
                $port,
                $encryption,
                trim((string)$request->input('username', '')),
                trim((string)$request->input('from_address')),
                trim((string)$request->input('from_name')),
            ]);
        } else {
            $db->prepare(
                "UPDATE smtp_settings
                    SET host = ?, port = ?, encryption = ?,
                        username = ?, password = ?,
                        from_address = ?, from_name = ?
                  WHERE id = 1"
            )->execute([
                trim((string)$request->input('host', '')),
                $port,
                $encryption,
                trim((string)$request->input('username', '')),
                $password,
                trim((string)$request->input('from_address')),
                trim((string)$request->input('from_name')),
            ]);
        }

        $this->success([], 'SMTP settings saved.');
    }

    // ------------------------------------------------------------------
    // POST /settings/smtp/test   (test SMTP connection)
    // ------------------------------------------------------------------

    public function testSmtp(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);

        try {
            $result = (new EmailService())->testSmtpConnection();
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'message' => $e->getMessage()];
        }

        if ($result['ok']) {
            $this->success([], $result['message']);
        } else {
            $this->error($result['message'], HTTP_BAD_GATEWAY);
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function fetchSmtpSettings(Database $db): array
    {
        try {
            $row = $db->prepare("SELECT * FROM smtp_settings WHERE id = 1 LIMIT 1");
            $row->execute();
            $result = $row->fetch();
            return is_array($result) ? $result : $this->defaultSmtp();
        } catch (\Throwable) {
            return $this->defaultSmtp();
        }
    }

    private function defaultSmtp(): array
    {
        return [
            'id'           => 1,
            'host'         => '',
            'port'         => 587,
            'encryption'   => 'tls',
            'username'     => '',
            'password'     => '',
            'from_address' => 'noreply@example.com',
            'from_name'    => 'BnkApp',
            'last_tested_at'  => null,
            'last_test_ok'    => null,
            'last_test_error' => null,
        ];
    }
}
