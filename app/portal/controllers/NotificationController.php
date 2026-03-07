<?php
/**
 * BnkApp Portal — Notification Controller
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;
use BnkPortal\Core\Session;
use BnkPortal\Middleware\CsrfMiddleware;

class NotificationController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100"
        );
        $stmt->execute([Auth::id()]);

        // Mark all as read after viewing
        $db->prepare("UPDATE notifications SET is_read=1, read_at=NOW() WHERE user_id=? AND is_read=0")
           ->execute([Auth::id()]);

        $this->view('notifications.index', [
            'title'         => 'Notifications',
            'notifications' => $stmt->fetchAll(),
        ]);
    }

    public function markRead(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        Database::getInstance()->prepare(
            "UPDATE notifications SET is_read=1, read_at=NOW() WHERE id=? AND user_id=?"
        )->execute([$params['id'], Auth::id()]);
        $this->redirect('/notifications');
    }

    public function markAllRead(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        Database::getInstance()->prepare(
            "UPDATE notifications SET is_read=1, read_at=NOW() WHERE user_id=?"
        )->execute([Auth::id()]);
        Session::flash('success', 'All notifications marked as read.');
        $this->redirect('/notifications');
    }
}
