<?php
/**
 * BnkApp Admin — Card Controller
 *
 * List, view, issue, and block payment cards.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Auth;
use BnkApp\Core\Controller;
use BnkApp\Core\Request;
use BnkApp\Models\Card;

class CardController extends Controller
{
    private Card $cardModel;

    public function __construct()
    {
        $this->cardModel = new Card();
    }

    /**
     * GET /cards
     */
    public function index(Request $request, array $params = []): void
    {
        $page   = (int)($request->query('page', 1));
        $status = $request->query('status', '');

        $where    = '';
        $bindings = [];

        if ($status !== '') {
            $where    = 'c.status = ?';
            $bindings = [$status];
        }

        $paginated = $this->cardModel->paginate($page, 25, $where, $bindings);

        $this->view('cards.index', [
            'title'      => 'Cards',
            'cards'      => $paginated['data'],
            'pagination' => $paginated,
            'filter'     => ['status' => $status],
        ]);
    }

    /**
     * GET /cards/{id}
     */
    public function show(Request $request, array $params = []): void
    {
        $card = $this->cardModel->find((int)$params['id']);
        if ($card === null) {
            $this->abort(HTTP_NOT_FOUND, 'Card not found.');
        }

        $this->view('cards.show', [
            'title' => "Card — ****{$card['card_number_last4']}",
            'card'  => $card,
        ]);
    }

    /**
     * POST /cards
     * Issue a new card for an account.
     * Sensitive values (full PAN, CVV) are hashed before storage.
     */
    public function store(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'account_id'      => 'required|numeric',
            'cardholder_name' => 'required|max:100',
            'card_type'       => 'required|in:debit,credit,prepaid',
            'card_network'    => 'required|in:visa,mastercard,maestro,amex',
            'expiry_month'    => 'required|numeric',
            'expiry_year'     => 'required|numeric',
            'pin'             => 'required|min:4|max:4',
        ]);

        if (!empty($errors)) {
            $this->error('Validation failed.', HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        // Generate a random 16-digit PAN for demo purposes
        // In production: integrate with card personalisation bureau
        $pan      = (string)random_int(4000_0000_0000_0000, 4999_9999_9999_9999);
        $cvv      = str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);
        $pin      = (string)$request->input('pin');

        $id = $this->cardModel->create([
            'account_id'          => $request->input('account_id'),
            'card_number_hash'    => password_hash($pan, PASSWORD_BCRYPT, ['cost' => 12]),
            'card_number_last4'   => substr($pan, -4),
            'card_type'           => $request->input('card_type'),
            'card_network'        => $request->input('card_network'),
            'cardholder_name'     => $request->input('cardholder_name'),
            'expiry_month'        => $request->input('expiry_month'),
            'expiry_year'         => $request->input('expiry_year'),
            'cvv_hash'            => password_hash($cvv, PASSWORD_BCRYPT, ['cost' => 12]),
            'pin_hash'            => password_hash($pin, PASSWORD_BCRYPT, ['cost' => 12]),
            'expires_at'          => sprintf('%04d-%02d-28', $request->input('expiry_year'), $request->input('expiry_month')),
            'status'              => 'inactive',
        ]);

        $this->success(['id' => $id, 'last4' => substr($pan, -4)], 'Card issued.', HTTP_CREATED);
    }

    /**
     * PATCH /cards/{id}/block
     * Toggle card block / unblock.
     */
    public function block(Request $request, array $params = []): void
    {
        $card = $this->cardModel->find((int)$params['id']);
        if ($card === null) {
            $this->abort(HTTP_NOT_FOUND, 'Card not found.');
        }

        if ($card['status'] === 'blocked') {
            $this->cardModel->update((int)$params['id'], [
                'status'         => 'active',
                'blocked_reason' => null,
                'blocked_at'     => null,
            ]);
            $this->success(['status' => 'active'], 'Card unblocked.');
        }

        $reason = $request->input('reason', 'Blocked by admin');
        $this->cardModel->update((int)$params['id'], [
            'status'         => 'blocked',
            'blocked_reason' => $reason,
            'blocked_at'     => date('Y-m-d H:i:s'),
        ]);
        $this->success(['status' => 'blocked'], 'Card blocked.');
    }
}
