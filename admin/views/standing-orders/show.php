<?php
/**
 * View: standing-orders/show.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="/standing-orders" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Standing Order #<?= $order['id'] ?></h1>
    <span class="badge text-bg-<?= FormatHelper::statusBadge($order['status']) ?>"><?= $order['status'] ?></span>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card table-card">
            <div class="card-header fw-semibold">Order Details</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Owner</dt><dd class="col-7"><a href="/users/<?= $order['user_id'] ?>"><?= FormatHelper::e($order['owner_name']) ?></a></dd>
                    <dt class="col-5 text-muted">From IBAN</dt><dd class="col-7 text-mono"><?= FormatHelper::e($order['from_iban']) ?></dd>
                    <dt class="col-5 text-muted">To IBAN</dt><dd class="col-7 text-mono"><?= FormatHelper::e($order['to_iban']) ?></dd>
                    <dt class="col-5 text-muted">To Name</dt><dd class="col-7"><?= FormatHelper::e($order['to_name']??'—') ?></dd>
                    <dt class="col-5 text-muted">Amount</dt><dd class="col-7 fw-bold"><?= FormatHelper::money((float)$order['amount'], $order['currency_code']) ?></dd>
                    <dt class="col-5 text-muted">Frequency</dt><dd class="col-7"><?= ucfirst($order['frequency']) ?></dd>
                    <dt class="col-5 text-muted">Start Date</dt><dd class="col-7"><?= FormatHelper::date($order['start_date']??'') ?></dd>
                    <dt class="col-5 text-muted">End Date</dt><dd class="col-7"><?= FormatHelper::date($order['end_date']??'') ?></dd>
                    <dt class="col-5 text-muted">Next Run</dt><dd class="col-7"><?= FormatHelper::date($order['next_execution_date']??'') ?></dd>
                    <dt class="col-5 text-muted">Description</dt><dd class="col-7"><?= FormatHelper::e($order['description']??'—') ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <?php if ($order['status']==='active'): ?>
        <div class="card table-card">
            <div class="card-header fw-semibold">Actions</div>
            <div class="card-body">
                <form method="POST" action="/standing-orders/<?= $order['id'] ?>/cancel" data-ajax="true" data-redirect="/standing-orders">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="PATCH">
                    <div class="d-grid">
                        <button class="btn btn-danger" data-confirm="Cancel this standing order?">
                            <i class="bi bi-stop-circle me-1"></i>Cancel Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
