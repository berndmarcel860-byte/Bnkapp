<?php
/**
 * View: cards/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Cards</h1>
    <a href="/cards/create" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Issue Card</a>
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

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
