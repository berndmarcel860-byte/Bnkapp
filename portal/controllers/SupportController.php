<?php
/**
 * BnkApp Portal — Support Controller
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;
use BnkPortal\Core\Session;
use BnkPortal\Middleware\CsrfMiddleware;

class SupportController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([Auth::id()]);

        $this->view('support.index', [
            'title'   => 'Support',
            'tickets' => $stmt->fetchAll(),
        ]);
    }

    public function create(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $errors = $this->validate($request, [
            'subject' => 'required|max:255',
            'message' => 'required',
        ]);

        if (!empty($errors)) {
            Session::flash('error', 'Please fill in a subject and message.');
            $this->redirect('/support');
        }

        $db = Database::getInstance();
        $db->prepare(
            "INSERT INTO support_tickets (user_id, subject, category, priority, status, first_message)
             VALUES (?, ?, ?, ?, 'open', ?)"
        )->execute([
            Auth::id(),
            $request->input('subject'),
            $request->input('category', 'general'),
            $request->input('priority', 'normal'),
            $request->input('message'),
        ]);
        $ticketId = $db->lastInsertId();

        $db->prepare(
            "INSERT INTO support_ticket_messages (ticket_id, sender_user_id, message) VALUES (?,?,?)"
        )->execute([$ticketId, Auth::id(), $request->input('message')]);

        Session::flash('success', 'Support ticket created.');
        $this->redirect('/support/' . $ticketId);
    }

    public function show(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM support_tickets WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$params['id'], Auth::id()]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            $this->abort(HTTP_NOT_FOUND);
        }

        $msgStmt = $db->prepare(
            "SELECT m.*, CONCAT(u.first_name,' ',u.last_name) AS sender_name, u.role_id
               FROM support_ticket_messages m
               LEFT JOIN users u ON u.id = m.sender_user_id
              WHERE m.ticket_id = ? ORDER BY m.created_at"
        );
        $msgStmt->execute([$ticket['id']]);

        $this->view('support.show', [
            'title'    => "Ticket #{$ticket['id']}: {$ticket['subject']}",
            'ticket'   => $ticket,
            'messages' => $msgStmt->fetchAll(),
        ]);
    }

    public function reply(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $errors = $this->validate($request, ['message' => 'required']);

        if (!empty($errors)) {
            Session::flash('error', 'Message cannot be empty.');
            $this->redirect('/support/' . $params['id']);
        }

        $db     = Database::getInstance();
        $ticket = $db->prepare("SELECT * FROM support_tickets WHERE id = ? AND user_id = ? LIMIT 1");
        $ticket->execute([$params['id'], Auth::id()]);
        if (!$ticket->fetch()) {
            $this->abort(HTTP_FORBIDDEN);
        }

        $db->prepare(
            "INSERT INTO support_ticket_messages (ticket_id, sender_user_id, message) VALUES (?,?,?)"
        )->execute([$params['id'], Auth::id(), $request->input('message')]);

        $db->prepare("UPDATE support_tickets SET status='in_progress', updated_at=NOW() WHERE id=?")
           ->execute([$params['id']]);

        Session::flash('success', 'Reply sent.');
        $this->redirect('/support/' . $params['id']);
    }
}
