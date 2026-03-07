<?php
/**
 * View: standing-orders/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Standing Orders</h1>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2">
        <form method="GET" action="/standing-orders" class="d-flex gap-2 flex-wrap">
            <select name="status" class="form-select form-select-sm" style="width:auto">
                <option value="">All Statuses</option>
                <?php foreach (['active','paused','cancelled','completed'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="so-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="so-table">
            <thead><tr><th>#</th><th>From IBAN</th><th>Owner</th><th>To IBAN</th><th>Amount</th><th>Frequency</th><th>Next Run</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No standing orders found.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><a href="/standing-orders/<?= $o['id'] ?>">#<?= $o['id'] ?></a></td>
                    <td class="text-mono small"><?= FormatHelper::e($o['from_iban']) ?></td>
                    <td><?= FormatHelper::e($o['owner_name']) ?></td>
                    <td class="text-mono small"><?= FormatHelper::e($o['to_iban']) ?></td>
                    <td class="fw-semibold"><?= FormatHelper::money((float)$o['amount'], $o['currency_code']) ?></td>
                    <td><?= ucfirst($o['frequency']) ?></td>
                    <td><?= FormatHelper::date($o['next_execution_date']??'') ?></td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($o['status']) ?>"><?= $o['status'] ?></span></td>
                    <td>
                        <?php if ($o['status']==='active'): ?>
                        <form method="POST" action="/standing-orders/<?= $o['id'] ?>/cancel" data-ajax="true" data-reload="true">
                            <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                            <input type="hidden" name="_method" value="PATCH">
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" data-confirm="Cancel this standing order?">
                                <i class="bi bi-stop-circle"></i>
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
