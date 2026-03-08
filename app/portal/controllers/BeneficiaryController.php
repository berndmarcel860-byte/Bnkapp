<?php
/**
 * BnkApp Portal — Beneficiary Controller
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;
use BnkPortal\Core\Session;
use BnkPortal\Middleware\CsrfMiddleware;

class BeneficiaryController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT * FROM beneficiaries WHERE user_id = ? ORDER BY account_holder_name"
        );
        $stmt->execute([Auth::id()]);

        $this->view('beneficiaries.index', [
            'title'         => 'Beneficiaries',
            'beneficiaries' => $stmt->fetchAll(),
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $errors = $this->validate($request, [
            'account_holder_name' => 'required|max:140',
            'iban'                => 'required|max:34',
        ]);

        if (!empty($errors)) {
            Session::flash('error', 'Please provide a name and IBAN.');
            $this->redirect('/beneficiaries');
        }

        Database::getInstance()->prepare(
            "INSERT INTO beneficiaries (user_id, account_holder_name, iban, bic, bank_name)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            Auth::id(),
            $request->input('account_holder_name'),
            strtoupper(str_replace(' ', '', (string)$request->input('iban', ''))),
            $request->input('bic'),
            $request->input('bank_name'),
        ]);

        Session::flash('success', 'Beneficiary added.');
        $this->redirect('/beneficiaries');
    }

    public function destroy(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        Database::getInstance()->prepare(
            "DELETE FROM beneficiaries WHERE id = ? AND user_id = ?"
        )->execute([$params['id'], Auth::id()]);

        Session::flash('success', 'Beneficiary removed.');
        $this->redirect('/beneficiaries');
    }
}
