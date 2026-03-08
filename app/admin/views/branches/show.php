<?php
/**
 * View: branches/show.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="/branches" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= FormatHelper::e($branch['name']) ?></h1>
    <?= $branch['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card table-card">
            <div class="card-header fw-semibold">Branch Details</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-4 text-muted">Sort Code</dt><dd class="col-8 text-mono"><?= FormatHelper::e($branch['sort_code']) ?></dd>
                    <dt class="col-4 text-muted">Address</dt><dd class="col-8"><?= FormatHelper::e($branch['address_line1']??'—') ?></dd>
                    <dt class="col-4 text-muted">City</dt><dd class="col-8"><?= FormatHelper::e($branch['city']??'—') ?></dd>
                    <dt class="col-4 text-muted">Postal Code</dt><dd class="col-8"><?= FormatHelper::e($branch['postal_code']??'—') ?></dd>
                    <dt class="col-4 text-muted">Country</dt><dd class="col-8"><?= FormatHelper::e($branch['country_name']??'—') ?></dd>
                    <dt class="col-4 text-muted">Phone</dt><dd class="col-8"><?= FormatHelper::e($branch['phone']??'—') ?></dd>
                    <dt class="col-4 text-muted">Email</dt><dd class="col-8"><?= FormatHelper::e($branch['email']??'—') ?></dd>
                    <dt class="col-4 text-muted">Accounts</dt><dd class="col-8"><?= count($accounts) ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <?php if (!empty($accounts)): ?>
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header fw-semibold">Accounts at this Branch</div>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead><tr><th>IBAN</th><th>Owner</th><th>Currency</th><th>Balance</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($accounts as $a): ?>
                    <tr>
                        <td class="text-mono small"><a href="/accounts/<?= $a['id'] ?>"><?= FormatHelper::e($a['iban']) ?></a></td>
                        <td><?= FormatHelper::e($a['owner_name']) ?></td>
                        <td><?= $a['currency_code'] ?></td>
                        <td class="fw-semibold"><?= FormatHelper::money((float)$a['balance'], $a['currency_code']) ?></td>
                        <td><span class="badge text-bg-<?= FormatHelper::statusBadge($a['status']) ?>"><?= $a['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
