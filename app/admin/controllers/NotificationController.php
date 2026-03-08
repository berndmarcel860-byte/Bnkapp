<?php
/**
 * BnkApp Admin — Notification Controller
 *
 * View and manage system notifications (notifications table).
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class NotificationController extends Controller
{
    /**
     * GET /notifications
     */
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)$request->query('page', 1);
        $type   = (string)$request->query('type', '');
        $offset = ($page - 1) * 25;

        $conditions = [];
        $bindings   = [];

        if ($type !== '') {
            $conditions[] = 'n.type = ?';
            $bindings[]   = $type;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $db    = Database::getInstance();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM notifications n {$where}");
        $countStmt->execute($bindings);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT n.*, CONCAT(u.first_name,' ',u.last_name) AS user_name
               FROM notifications n
               JOIN users u ON u.id = n.user_id
               {$where}
              ORDER BY n.created_at DESC
              LIMIT 25 OFFSET {$offset}"
        );
        $stmt->execute($bindings);

        $this->view('notifications.index', [
            'title'         => 'Notifications',
            'notifications' => $stmt->fetchAll(),
            'total'         => $total,
            'page'          => $page,
            'filter'        => ['type' => $type],
        ]);
    }

    /**
     * GET /notifications/count  (AJAX — used by topbar badge)
     */
    public function count(Request $request, array $params = []): void
    {
        $db  = Database::getInstance();
        $n   = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
        $this->json(['count' => $n]);
    }

    /**
     * POST /notifications/send
     * Broadcast a notification to a single user or all users.
     */
    public function send(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'title'   => 'required|max:255',
            'message' => 'required|max:1000',
            'type'    => 'required|in:transaction,security,account,loan,card,system,marketing,kyc',
            'channel' => 'required|in:in_app,email,sms,push',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $db     = Database::getInstance();
        $userId = $request->input('user_id'); // null = broadcast

        if ($userId) {
            $db->prepare(
                "INSERT INTO notifications (user_id, type, channel, title, message) VALUES (?,?,?,?,?)"
            )->execute([$userId, $request->input('type'), $request->input('channel'), $request->input('title'), $request->input('message')]);
        } else {
            // Broadcast to all active users
            $users = $db->query("SELECT id FROM users WHERE is_active = 1")->fetchAll();
            $stmt  = $db->prepare(
                "INSERT INTO notifications (user_id, type, channel, title, message) VALUES (?,?,?,?,?)"
            );
            foreach ($users as $u) {
                $stmt->execute([$u['id'], $request->input('type'), $request->input('channel'), $request->input('title'), $request->input('message')]);
            }
        }

        $this->success(null, 'Notification(s) sent.');
    }
}
