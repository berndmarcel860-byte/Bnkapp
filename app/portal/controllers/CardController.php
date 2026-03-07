<?php
/**
 * BnkApp Portal — Card Controller
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;
use BnkPortal\Core\Session;
use BnkPortal\Middleware\CsrfMiddleware;

class CardController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT c.*, ba.iban AS account_iban
               FROM cards c
               JOIN bank_accounts ba ON ba.id = c.account_id
              WHERE ba.user_id = ?
              ORDER BY c.created_at DESC"
        );
        $stmt->execute([Auth::id()]);

        $this->view('cards.index', [
            'title' => 'My Cards',
            'cards' => $stmt->fetchAll(),
        ]);
    }

    public function show(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT c.*, ba.iban AS account_iban, ba.user_id
               FROM cards c
               JOIN bank_accounts ba ON ba.id = c.account_id
              WHERE c.id = ? LIMIT 1"
        );
        $stmt->execute([$params['id']]);
        $card = $stmt->fetch();

        if (!$card || $card['user_id'] !== Auth::id()) {
            $this->abort(HTTP_NOT_FOUND);
        }

        $this->view('cards.show', [
            'title' => "Card ****{$card['card_number_last4']}",
            'card'  => $card,
        ]);
    }

    public function freeze(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT c.id, c.status, ba.user_id
               FROM cards c JOIN bank_accounts ba ON ba.id = c.account_id
              WHERE c.id = ? LIMIT 1"
        );
        $stmt->execute([$params['id']]);
        $card = $stmt->fetch();

        if (!$card || $card['user_id'] !== Auth::id()) {
            $this->abort(HTTP_FORBIDDEN);
        }

        $newStatus = $card['status'] === 'active' ? 'blocked' : 'active';
        $db->prepare("UPDATE cards SET status = ? WHERE id = ?")->execute([$newStatus, $card['id']]);

        Session::flash('success', $newStatus === 'blocked' ? 'Card frozen.' : 'Card unfrozen.');
        $this->redirect('/cards/' . $card['id']);
    }
}
