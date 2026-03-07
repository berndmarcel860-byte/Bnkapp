<?php
/**
 * View: transactions/show.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/transactions" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Transaction #<?= $transaction['id'] ?></h1>
    <span class="badge text-bg-<?= FormatHelper::statusBadge($transaction['status']) ?>"><?= $transaction['status'] ?></span>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="bi bi-info-circle me-2 text-primary"></i>Transaction Details</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Reference</dt>    <dd class="col-7 text-mono"><?= FormatHelper::e($transaction['transaction_ref']??'') ?></dd>
                    <dt class="col-5 text-muted">Type</dt>         <dd class="col-7"><?= FormatHelper::titleCase($transaction['transaction_type']) ?></dd>
                    <dt class="col-5 text-muted">Amount</dt>       <dd class="col-7 fw-bold text-success fs-5"><?= FormatHelper::money((float)$transaction['amount'], $transaction['currency_code']) ?></dd>
                    <dt class="col-5 text-muted">Fee</dt>          <dd class="col-7"><?= FormatHelper::money((float)($transaction['fee_amount']??0), $transaction['currency_code']) ?></dd>
                    <dt class="col-5 text-muted">Net Amount</dt>   <dd class="col-7"><?= FormatHelper::money((float)($transaction['net_amount']??0), $transaction['currency_code']) ?></dd>
                    <dt class="col-5 text-muted">From IBAN</dt>    <dd class="col-7 text-mono"><?= FormatHelper::e($transaction['from_iban']??'—') ?></dd>
                    <dt class="col-5 text-muted">To IBAN</dt>      <dd class="col-7 text-mono"><?= FormatHelper::e($transaction['to_iban']??'—') ?></dd>
                    <dt class="col-5 text-muted">Description</dt>  <dd class="col-7"><?= FormatHelper::e($transaction['description']??'—') ?></dd>
                    <dt class="col-5 text-muted">End-to-End ID</dt><dd class="col-7 text-mono small"><?= FormatHelper::e($transaction['end_to_end_id']??'—') ?></dd>
                    <dt class="col-5 text-muted">Booking Date</dt> <dd class="col-7"><?= FormatHelper::date($transaction['booking_date']??'') ?></dd>
                    <dt class="col-5 text-muted">Initiated By</dt> <dd class="col-7"><?= FormatHelper::e($transaction['initiated_by_name']??'—') ?></dd>
                    <dt class="col-5 text-muted">Created</dt>      <dd class="col-7"><?= FormatHelper::dateTime($transaction['created_at']??'') ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="bi bi-gear me-2 text-primary"></i>Actions</div>
            <div class="card-body d-flex flex-column gap-2">
                <?php if ($transaction['status'] === 'completed'): ?>
                <form method="POST" action="/transactions/<?= $transaction['id'] ?>/reverse" data-ajax="true" data-reload="true">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                    <button class="btn btn-warning w-100" data-confirm="Reverse this transaction?">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reverse
                    </button>
                </form>
                <?php else: ?>
                <p class="text-muted small">No actions available for status: <strong><?= $transaction['status'] ?></strong></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
