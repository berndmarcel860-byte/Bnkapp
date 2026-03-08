<?php
/**
 * View: beneficiaries/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Beneficiaries</h1>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2">
        <form method="GET" action="/beneficiaries" class="d-flex gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="max-width:280px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" name="search" class="form-control" placeholder="Name or IBAN…" value="<?= FormatHelper::e($search??'') ?>">
            </div>
            <button class="btn btn-sm btn-primary">Search</button>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="ben-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="ben-table">
            <thead><tr><th>#</th><th>Beneficiary Name</th><th>IBAN</th><th>BIC</th><th>Bank</th><th>Owner</th><th>Verified</th><th>Added</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($beneficiaries)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No beneficiaries found.</td></tr>
            <?php else: ?>
                <?php foreach ($beneficiaries as $b): ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td class="fw-semibold"><?= FormatHelper::e($b['account_holder_name']) ?></td>
                    <td class="text-mono small"><?= FormatHelper::e($b['iban']) ?></td>
                    <td class="text-mono small"><?= FormatHelper::e($b['bic']??'—') ?></td>
                    <td><?= FormatHelper::e($b['bank_name']??'—') ?></td>
                    <td><?= FormatHelper::e($b['owner_name']) ?></td>
                    <td><?= $b['is_verified'] ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-warning">No</span>' ?></td>
                    <td class="text-muted small"><?= FormatHelper::dateTime($b['created_at']) ?></td>
                    <td><a href="/beneficiaries/<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
