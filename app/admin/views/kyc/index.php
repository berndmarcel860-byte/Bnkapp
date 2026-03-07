<?php
/**
 * View: kyc/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">KYC Documents</h1>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2">
        <form method="GET" action="/kyc" class="d-flex gap-2 flex-wrap">
            <select name="status" class="form-select form-select-sm" style="width:auto">
                <option value="">All Statuses</option>
                <?php foreach (['pending','in_review','approved','rejected','expired'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status']??'')===$s?'selected':'' ?>><?= FormatHelper::titleCase($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="kyc-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="kyc-table">
            <thead><tr><th>#</th><th>Owner</th><th>Email</th><th>Type</th><th>User KYC</th><th>Expiry</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($docs)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No documents found.</td></tr>
            <?php else: ?>
                <?php foreach ($docs as $d): ?>
                <tr>
                    <td><?= $d['id'] ?></td>
                    <td class="fw-semibold"><?= FormatHelper::e($d['owner_name']) ?></td>
                    <td class="small"><?= FormatHelper::e($d['owner_email']) ?></td>
                    <td><?= FormatHelper::titleCase($d['document_type']) ?></td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($d['user_kyc_status']??'') ?>"><?= $d['user_kyc_status'] ?></span></td>
                    <td><?= FormatHelper::date($d['expiry_date']??'') ?></td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($d['status']) ?>"><?= $d['status'] ?></span></td>
                    <td class="text-muted small"><?= FormatHelper::dateTime($d['created_at']) ?></td>
                    <td><a href="/kyc/<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
