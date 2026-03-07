<?php
/**
 * BnkApp Admin — KYC Document Controller
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Auth;
use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class KycController extends Controller
{
    /**
     * GET /kyc
     */
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)$request->query('page', 1);
        $status = (string)$request->query('status', 'pending');
        $offset = ($page - 1) * 25;

        $where    = $status !== '' ? 'WHERE kd.status = ?' : '';
        $bindings = $status !== '' ? [$status] : [];

        $db = Database::getInstance();
        $countStmt = $db->prepare("SELECT COUNT(*) FROM kyc_documents kd {$where}");
        $countStmt->execute($bindings);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT kd.*,
                    CONCAT(u.first_name,' ',u.last_name) AS owner_name,
                    u.email AS owner_email,
                    u.kyc_status AS user_kyc_status
               FROM kyc_documents kd
               JOIN users u ON u.id = kd.user_id
               {$where}
              ORDER BY kd.created_at DESC
              LIMIT 25 OFFSET {$offset}"
        );
        $stmt->execute($bindings);

        $this->view('kyc.index', [
            'title'  => 'KYC Documents',
            'docs'   => $stmt->fetchAll(),
            'total'  => $total,
            'page'   => $page,
            'filter' => ['status' => $status],
        ]);
    }

    /**
     * GET /kyc/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT kd.*,
                    CONCAT(u.first_name,' ',u.last_name) AS owner_name,
                    u.email AS owner_email
               FROM kyc_documents kd
               JOIN users u ON u.id = kd.user_id
              WHERE kd.id = ? LIMIT 1"
        );
        $stmt->execute([$params['id']]);
        $doc = $stmt->fetch();

        if (!$doc) {
            $this->abort(HTTP_NOT_FOUND, 'Document not found.');
        }

        $this->view('kyc.show', [
            'title' => "KYC Document #{$doc['id']}",
            'doc'   => $doc,
        ]);
    }

    /**
     * PATCH /kyc/{id}/review
     * Approve or reject a KYC document.
     */
    public function review(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'status' => 'required|in:approved,rejected',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $db     = Database::getInstance();
        $status = (string)$request->input('status');

        $db->prepare(
            "UPDATE kyc_documents SET status=?, rejection_reason=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?"
        )->execute([
            $status,
            $request->input('rejection_reason'),
            Auth::id(),
            $params['id'],
        ]);

        // If all docs approved, update user KYC status
        if ($status === 'approved') {
            $doc = $db->prepare("SELECT user_id FROM kyc_documents WHERE id=?")->execute([$params['id']]);
        }

        $this->success(null, "Document {$status}.");
    }
}
