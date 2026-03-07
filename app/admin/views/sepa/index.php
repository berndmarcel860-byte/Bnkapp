<?php
/**
 * View: sepa/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">SEPA Transfers</h1>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2">
        <form method="GET" action="/sepa" class="d-flex gap-2 flex-wrap">
            <select name="type" class="form-select form-select-sm" style="width:auto">
                <option value="">All Types</option>
                <?php foreach (['credit_transfer','instant_credit_transfer','direct_debit'] as $t): ?>
                <option value="<?= $t ?>" <?= ($filter['type']??'')===$t?'selected':'' ?>><?= FormatHelper::titleCase($t) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-select form-select-sm" style="width:auto">
                <option value="">All Statuses</option>
                <?php foreach (['pending','accepted','settled','rejected','returned','recalled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="sepa-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="sepa-table">
            <thead><tr><th>#</th><th>Type</th><th>Debtor IBAN</th><th>Creditor IBAN</th><th>Creditor Name</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($transfers)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No SEPA transfers found.</td></tr>
            <?php else: ?>
                <?php foreach ($transfers as $t): ?>
                <tr>
                    <td><a href="/sepa/<?= $t['id'] ?>">#<?= $t['id'] ?></a></td>
                    <td><span class="badge text-bg-secondary"><?= FormatHelper::titleCase($t['transfer_type']) ?></span></td>
                    <td class="text-mono small"><?= FormatHelper::e($t['debtor_iban']??'—') ?></td>
                    <td class="text-mono small"><?= FormatHelper::e($t['creditor_iban']??$t['creditor_iban_raw']??'—') ?></td>
                    <td><?= FormatHelper::e($t['creditor_name']??'—') ?></td>
                    <td class="fw-semibold"><?= FormatHelper::money((float)$t['amount'], $t['currency_code']??'EUR') ?></td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($t['status']) ?>"><?= $t['status'] ?></span></td>
                    <td class="text-muted small"><?= FormatHelper::dateTime($t['created_at']) ?></td>
                    <td><a href="/sepa/<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
