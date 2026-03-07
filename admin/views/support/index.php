<?php
/**
 * View: support/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Support Tickets</h1>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2">
        <form method="GET" action="/support" class="d-flex gap-2 flex-wrap">
            <select name="status" class="form-select form-select-sm" style="width:auto">
                <?php foreach (['open','in_progress','waiting_on_customer','resolved','closed'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status']??'open')===$s?'selected':'' ?>><?= FormatHelper::titleCase($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="tickets-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="tickets-table">
            <thead><tr><th>#</th><th>Subject</th><th>Customer</th><th>Category</th><th>Priority</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
            <?php if (empty($tickets)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No tickets found.</td></tr>
            <?php else: ?>
                <?php foreach ($tickets as $t): ?>
                <tr>
                    <td><a href="/support/<?= $t['id'] ?>" class="fw-semibold">#<?= $t['id'] ?></a></td>
                    <td><?= FormatHelper::e(FormatHelper::truncate($t['subject'],60)) ?></td>
                    <td><?= FormatHelper::e($t['customer_name']) ?></td>
                    <td><?= FormatHelper::titleCase($t['category']??'') ?></td>
                    <td><span class="badge text-bg-<?= match($t['priority']??''){
                        'urgent'=>'danger','high'=>'warning',default=>'secondary'} ?>"><?= FormatHelper::e($t['priority']??'') ?></span></td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($t['status']) ?>"><?= $t['status'] ?></span></td>
                    <td class="text-muted small"><?= FormatHelper::dateTime($t['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
