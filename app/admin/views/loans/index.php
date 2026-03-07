<?php
/**
 * View: loans/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Loans</h1>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2">
        <form method="GET" action="/loans" class="d-flex gap-2 flex-wrap">
            <select name="status" class="form-select form-select-sm" style="width:auto">
                <option value="">All Statuses</option>
                <?php foreach (['applied','in_review','approved','disbursed','active','defaulted','paid_off','rejected','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status']??'')===$s?'selected':'' ?>><?= FormatHelper::titleCase($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="loans-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="loans-table">
            <thead><tr><th>#</th><th>Type</th><th>Principal</th><th>Outstanding</th><th>Rate</th><th>Term</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($loans)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No loans found.</td></tr>
            <?php else: ?>
                <?php foreach ($loans as $l): ?>
                <tr>
                    <td><a href="/loans/<?= $l['id'] ?>">#<?= $l['id'] ?></a></td>
                    <td><?= FormatHelper::titleCase($l['loan_type']) ?></td>
                    <td class="fw-semibold"><?= FormatHelper::money((float)$l['principal_amount']) ?></td>
                    <td><?= FormatHelper::money((float)$l['outstanding_balance']) ?></td>
                    <td><?= number_format((float)$l['interest_rate']*100,2) ?>%</td>
                    <td><?= $l['term_months'] ?> mo</td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($l['status']) ?>"><?= $l['status'] ?></span></td>
                    <td><a href="/loans/<?= $l['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
