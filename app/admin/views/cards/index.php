<?php
/**
 * View: cards/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Cards</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#issueCardModal">
        <i class="bi bi-plus-circle me-1"></i>Issue Card
    </button>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2">
        <form method="GET" action="/cards" class="d-flex gap-2 flex-wrap">
            <select name="status" class="form-select form-select-sm" style="width:auto">
                <option value="">All Statuses</option>
                <?php foreach (['inactive','active','blocked','expired','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="cards-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="cards-table">
            <thead><tr><th>#</th><th>PAN (last 4)</th><th>Cardholder</th><th>Type</th><th>Network</th><th>Account IBAN</th><th>Expires</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($cards)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No cards found.</td></tr>
            <?php else: ?>
                <?php foreach ($cards as $c): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td class="text-mono fw-bold">**** <?= FormatHelper::e($c['card_number_last4']) ?></td>
                    <td><?= FormatHelper::e($c['cardholder_name']) ?></td>
                    <td><?= ucfirst($c['card_type']) ?></td>
                    <td><?= ucfirst($c['card_network']) ?></td>
                    <td class="text-mono small"><?= FormatHelper::e($c['account_iban']) ?></td>
                    <td><?= FormatHelper::date($c['expires_at']) ?></td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($c['status']) ?>"><?= $c['status'] ?></span></td>
                    <td><a href="/cards/<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Issue Card Modal -->
<div class="modal fade" id="issueCardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/cards" data-ajax="true" data-reload="true">
                <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Issue New Card</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Account ID</label>
                        <input type="number" name="account_id" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Cardholder Name</label>
                        <input type="text" name="cardholder_name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Card Type</label>
                        <select name="card_type" class="form-select" required>
                            <option value="debit">Debit</option>
                            <option value="credit">Credit</option>
                            <option value="prepaid">Prepaid</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Network</label>
                        <select name="card_network" class="form-select" required>
                            <option value="visa">Visa</option>
                            <option value="mastercard">Mastercard</option>
                            <option value="maestro">Maestro</option>
                            <option value="amex">Amex</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Expiry Month</label>
                        <input type="number" name="expiry_month" class="form-control" min="1" max="12" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Expiry Year</label>
                        <input type="number" name="expiry_year" class="form-control" min="<?= date('Y') ?>" max="<?= date('Y')+10 ?>" value="<?= date('Y')+3 ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">PIN (4 digits)</label>
                        <input type="password" name="pin" class="form-control" minlength="4" maxlength="4" pattern="\d{4}" required autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-credit-card me-1"></i>Issue Card</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
