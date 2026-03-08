<?php
/**
 * BnkApp Admin — Beneficiary Controller
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class BeneficiaryController extends Controller
{
    /**
     * GET /beneficiaries
     */
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)$request->query('page', 1);
        $search = trim((string)$request->query('search', ''));
        $offset = ($page - 1) * 25;

        $conditions = [];
        $bindings   = [];

        if ($search !== '') {
            $conditions[] = "(b.account_holder_name LIKE ? OR b.iban LIKE ?)";
            $bindings[]   = "%{$search}%";
            $bindings[]   = "%{$search}%";
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $db    = Database::getInstance();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM beneficiaries b {$where}");
        $countStmt->execute($bindings);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT b.*,
                    CONCAT(u.first_name,' ',u.last_name) AS owner_name
               FROM beneficiaries b
               JOIN users u ON u.id = b.user_id
               {$where}
              ORDER BY b.created_at DESC
              LIMIT 25 OFFSET {$offset}"
        );
        $stmt->execute($bindings);

        $this->view('beneficiaries.index', [
            'title'         => 'Beneficiaries',
            'beneficiaries' => $stmt->fetchAll(),
            'total'         => $total,
            'page'          => $page,
            'search'        => $search,
        ]);
    }

    /**
     * GET /beneficiaries/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT b.*, CONCAT(u.first_name,' ',u.last_name) AS owner_name, u.email AS owner_email
               FROM beneficiaries b
               JOIN users u ON u.id = b.user_id
              WHERE b.id = ? LIMIT 1"
        );
        $stmt->execute([$params['id']]);
        $ben = $stmt->fetch();

        if (!$ben) {
            $this->abort(HTTP_NOT_FOUND, 'Beneficiary not found.');
        }

        $this->view('beneficiaries.show', [
            'title'       => "Beneficiary — {$ben['account_holder_name']}",
            'beneficiary' => $ben,
        ]);
    }

    /**
     * PATCH /beneficiaries/{id}/verify
     */
    public function verify(Request $request, array $params = []): void
    {
        $verified = (int)$request->input('verified', 1);
        Database::getInstance()
            ->prepare('UPDATE beneficiaries SET is_verified = ? WHERE id = ?')
            ->execute([$verified, $params['id']]);

        $label = $verified ? 'verified' : 'unverified';
        $this->success(null, "Beneficiary {$label} successfully.");
    }

    /**
     * DELETE /beneficiaries/{id}
     */
    public function destroy(Request $request, array $params = []): void
    {
        $db = Database::getInstance();
        $db->prepare('DELETE FROM beneficiaries WHERE id = ?')->execute([$params['id']]);
        $this->success(null, 'Beneficiary removed.');
    }
}
