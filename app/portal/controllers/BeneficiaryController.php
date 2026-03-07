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
            "SELECT * FROM beneficiaries WHERE user_id = ? ORDER BY beneficiary_name"
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
            'beneficiary_name' => 'required|max:255',
            'iban'             => 'required|max:34',
        ]);

        if (!empty($errors)) {
            Session::flash('error', 'Please provide a name and IBAN.');
            $this->redirect('/beneficiaries');
        }

        Database::getInstance()->prepare(
            "INSERT INTO beneficiaries (user_id, beneficiary_name, iban, bic, bank_name)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            Auth::id(),
            $request->input('beneficiary_name'),
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
