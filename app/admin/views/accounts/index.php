<?php
/**
 * View: accounts/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Bank Accounts</h1>
    <a href="/accounts/create" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Open Account</a>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2 align-items-center">
        <form method="GET" action="/accounts" class="d-flex gap-2 flex-wrap">
            <select name="status" class="form-select form-select-sm" style="width:auto">
                <option value="">All Statuses</option>
                <?php foreach (['pending','active','inactive','frozen','closed'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="accounts-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="accounts-table">
            <thead><tr>
                <th>#</th><th>IBAN</th><th>Owner</th><th>Type</th><th>Currency</th><th>Balance</th><th>Status</th><th>Opened</th><th></th>
            </tr></thead>
            <tbody>
            <?php if (empty($accounts)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No accounts found.</td></tr>
            <?php else: ?>
                <?php foreach ($accounts as $a): ?>
                <tr>
                    <td><?= $a['id'] ?></td>
                    <td class="text-mono small"><?= FormatHelper::e($a['iban']) ?></td>
                    <td><?= FormatHelper::e($a['owner_name']) ?></td>
                    <td><?= FormatHelper::e($a['account_type_name']) ?></td>
                    <td><?= $a['currency_code'] ?></td>
                    <td class="fw-semibold"><?= FormatHelper::money((float)$a['balance'], $a['currency_code']) ?></td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($a['status']) ?>"><?= $a['status'] ?></span></td>
                    <td class="text-muted small"><?= FormatHelper::dateTime($a['opened_at']) ?></td>
                    <td><a href="/accounts/<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
