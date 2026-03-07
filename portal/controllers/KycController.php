<?php
/**
 * BnkApp Portal — KYC Controller
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;
use BnkPortal\Core\Session;
use BnkPortal\Middleware\CsrfMiddleware;

class KycController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $user = $db->prepare("SELECT kyc_status, kyc_approved_at FROM users WHERE id = ? LIMIT 1");
        $user->execute([Auth::id()]);
        $userStatus = $user->fetch();

        $docs = $db->prepare(
            "SELECT * FROM kyc_documents WHERE user_id = ? ORDER BY created_at DESC"
        );
        $docs->execute([Auth::id()]);

        $this->view('kyc.index', [
            'title'      => 'Identity Verification',
            'userStatus' => $userStatus,
            'docs'       => $docs->fetchAll(),
        ]);
    }

    public function upload(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);

        $errors = $this->validate($request, [
            'document_type' => 'required',
        ]);

        if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Please select a file to upload.');
            $this->redirect('/kyc');
        }

        $file     = $_FILES['document_file'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg','jpeg','png','pdf'];

        if (!in_array($ext, $allowed, true)) {
            Session::flash('error', 'Only JPG, PNG and PDF files are accepted.');
            $this->redirect('/kyc');
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            Session::flash('error', 'File size must be under 10 MB.');
            $this->redirect('/kyc');
        }

        // Store file reference (actual file storage implementation depends on hosting)
        $fileName = 'kyc_' . Auth::id() . '_' . time() . '.' . $ext;
        $uploadPath = PORTAL_ROOT . '/storage/kyc/' . $fileName;

        if (!is_dir(dirname($uploadPath))) {
            mkdir(dirname($uploadPath), 0750, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            Session::flash('error', 'File upload failed. Please try again.');
            $this->redirect('/kyc');
        }

        Database::getInstance()->prepare(
            "INSERT INTO kyc_documents (user_id, document_type, document_number, file_path, expiry_date, status)
             VALUES (?, ?, ?, ?, ?, 'pending')"
        )->execute([
            Auth::id(),
            $request->input('document_type'),
            $request->input('document_number'),
            $fileName,
            $request->input('expiry_date') ?: null,
        ]);

        // Update user KYC status to in_review
        Database::getInstance()->prepare(
            "UPDATE users SET kyc_status='in_review' WHERE id=? AND kyc_status='pending'"
        )->execute([Auth::id()]);

        Session::flash('success', 'Document uploaded. Our team will review it shortly.');
        $this->redirect('/kyc');
    }
}
