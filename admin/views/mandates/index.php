<?php
/**
 * View: mandates/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">SEPA Mandates</h1>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2">
        <form method="GET" action="/mandates" class="d-flex gap-2 flex-wrap">
            <select name="status" class="form-select form-select-sm" style="width:auto">
                <option value="">All Statuses</option>
                <?php foreach (['active','expired','revoked','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="mandate-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="mandate-table">
            <thead><tr><th>#</th><th>Mandate Ref</th><th>Debtor IBAN</th><th>Debtor Name</th><th>Creditor Name</th><th>Type</th><th>Status</th><th>Signed</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($mandates)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No mandates found.</td></tr>
            <?php else: ?>
                <?php foreach ($mandates as $m): ?>
                <tr>
                    <td><?= $m['id'] ?></td>
                    <td class="text-mono small"><?= FormatHelper::e($m['mandate_reference']) ?></td>
                    <td class="text-mono small"><?= FormatHelper::e($m['debtor_iban']) ?></td>
                    <td><?= FormatHelper::e($m['debtor_name']) ?></td>
                    <td><?= FormatHelper::e($m['creditor_name']??'—') ?></td>
                    <td><?= FormatHelper::titleCase($m['sequence_type']??'') ?></td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($m['status']) ?>"><?= $m['status'] ?></span></td>
                    <td class="text-muted small"><?= FormatHelper::date($m['signing_date']??'') ?></td>
                    <td>
                        <?php if ($m['status']==='active'): ?>
                        <form method="POST" action="/mandates/<?= $m['id'] ?>/revoke" data-ajax="true" data-reload="true">
                            <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                            <input type="hidden" name="_method" value="PATCH">
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" data-confirm="Revoke this mandate?">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
