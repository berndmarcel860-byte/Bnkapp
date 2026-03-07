<?php
/**
 * BnkApp Admin — Support Ticket Controller
 *
 * View, reply to, and close customer support tickets.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Auth;
use BnkApp\Core\Controller;
use BnkApp\Core\Database;
use BnkApp\Core\Request;

class SupportController extends Controller
{
    /**
     * GET /support
     */
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)($request->query('page', 1));
        $status = $request->query('status', 'open');

        $db     = Database::getInstance();
        $offset = ($page - 1) * 25;

        $stmt = $db->prepare(
            "SELECT st.*, CONCAT(u.first_name, ' ', u.last_name) AS customer_name
               FROM support_tickets st
               JOIN users u ON u.id = st.user_id
              WHERE st.status = ?
              ORDER BY st.created_at DESC
              LIMIT 25 OFFSET {$offset}"
        );
        $stmt->execute([$status]);

        $this->view('support.index', [
            'title'   => 'Support Tickets',
            'tickets' => $stmt->fetchAll(),
            'filter'  => ['status' => $status],
            'page'    => $page,
        ]);
    }

    /**
     * GET /support/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $db = Database::getInstance();

        $ticket = $db->prepare(
            "SELECT st.*, CONCAT(u.first_name, ' ', u.last_name) AS customer_name
               FROM support_tickets st
               JOIN users u ON u.id = st.user_id
              WHERE st.id = ? LIMIT 1"
        );
        $ticket->execute([$params['id']]);
        $ticketData = $ticket->fetch();

        if (!$ticketData) {
            $this->abort(HTTP_NOT_FOUND, 'Ticket not found.');
        }

        $messages = $db->prepare(
            "SELECT m.*, CONCAT(u.first_name, ' ', u.last_name) AS sender_name
               FROM support_ticket_messages m
               JOIN users u ON u.id = m.sender_id
              WHERE m.ticket_id = ?
              ORDER BY m.created_at ASC"
        );
        $messages->execute([$params['id']]);

        $this->view('support.show', [
            'title'    => "Ticket #{$ticketData['id']} — {$ticketData['subject']}",
            'ticket'   => $ticketData,
            'messages' => $messages->fetchAll(),
        ]);
    }

    /**
     * POST /support/{id}/reply
     */
    public function reply(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'message' => 'required|max:5000',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $db = Database::getInstance();
        $db->prepare(
            'INSERT INTO support_ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)'
        )->execute([$params['id'], Auth::id(), $request->input('message')]);

        // Move ticket to in_progress if it was open
        $db->prepare(
            "UPDATE support_tickets SET status = 'in_progress', assigned_to = ?
              WHERE id = ? AND status = 'open'"
        )->execute([Auth::id(), $params['id']]);

        $this->success(null, 'Reply sent.');
    }

    /**
     * PATCH /support/{id}/close
     */
    public function close(Request $request, array $params = []): void
    {
        $db = Database::getInstance();
        $db->prepare(
            "UPDATE support_tickets SET status = 'closed', resolved_at = NOW() WHERE id = ?"
        )->execute([$params['id']]);

        $this->success(null, 'Ticket closed.');
    }
}
