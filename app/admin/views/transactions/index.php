<?php
/**
 * View: transactions/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Transactions</h1>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2 align-items-center">
        <form method="GET" action="/transactions" class="d-flex flex-wrap gap-2">
            <select name="type" class="form-select form-select-sm" style="width:auto">
                <option value="">All Types</option>
                <?php foreach (['deposit','withdrawal','internal_transfer','sepa_credit_transfer','sepa_instant_transfer','sepa_direct_debit','fee','interest','loan_disbursement','loan_repayment','refund','reversal'] as $t): ?>
                <option value="<?= $t ?>" <?= ($filter['type']??'')===$t?'selected':'' ?>><?= FormatHelper::titleCase($t) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-select form-select-sm" style="width:auto">
                <option value="">All Statuses</option>
                <?php foreach (['pending','processing','completed','failed','cancelled','reversed'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="from" class="form-control form-control-sm" style="width:auto" value="<?= FormatHelper::e($filter['from']??'') ?>">
            <input type="date" name="to"   class="form-control form-control-sm" style="width:auto" value="<?= FormatHelper::e($filter['to']??'') ?>">
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="txn-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="txn-table">
            <thead><tr><th>#</th><th>Type</th><th>From</th><th>To</th><th>Amount</th><th>Fee</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php if (empty($transactions)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No transactions found.</td></tr>
            <?php else: ?>
                <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><a href="/transactions/<?= $t['id'] ?>" class="fw-semibold">#<?= $t['id'] ?></a></td>
                    <td><span class="badge text-bg-secondary"><?= FormatHelper::titleCase($t['transaction_type']) ?></span></td>
                    <td class="text-mono small"><?= FormatHelper::e($t['from_iban']??'—') ?></td>
                    <td class="text-mono small"><?= FormatHelper::e($t['to_iban']??'—') ?></td>
                    <td class="fw-semibold"><?= FormatHelper::money((float)$t['amount'], $t['currency_code']) ?></td>
                    <td class="text-muted small"><?= FormatHelper::money((float)$t['fee_amount'], $t['currency_code']) ?></td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($t['status']) ?>"><?= $t['status'] ?></span></td>
                    <td class="text-muted small"><?= FormatHelper::dateTime($t['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($pagination)): ?>
    <div class="card-footer d-flex justify-content-between">
        <small class="text-muted">Page <?= $pagination['page'] ?> / <?= $pagination['last_page'] ?></small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($p = max(1,$pagination['page']-2); $p<=min($pagination['last_page'],$pagination['page']+2); $p++): ?>
            <li class="page-item <?= $p===$pagination['page']?'active':''?>">
                <a class="page-link" href="/transactions?page=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
