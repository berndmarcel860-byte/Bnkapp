<?php
/**
 * View: transactions/show.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/transactions" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Transaction #<?= $transaction['id'] ?></h1>
    <span class="badge text-bg-<?= FormatHelper::statusBadge($transaction['status']) ?> fs-6">
        <?= FormatHelper::titleCase($transaction['status']) ?>
    </span>
</div>

<div class="row g-4">
    <!-- Transaction Details -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="bi bi-info-circle me-2 text-primary"></i>Transaction Details</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Reference</dt>    <dd class="col-7 text-mono small"><?= FormatHelper::e($transaction['transaction_ref']??'') ?></dd>
                    <dt class="col-5 text-muted">Type</dt>         <dd class="col-7"><?= FormatHelper::titleCase($transaction['transaction_type']) ?></dd>
                    <dt class="col-5 text-muted">Amount</dt>       <dd class="col-7 fw-bold text-success fs-5"><?= FormatHelper::money((float)$transaction['amount'], $transaction['currency_code']) ?></dd>
                    <dt class="col-5 text-muted">Fee</dt>          <dd class="col-7"><?= FormatHelper::money((float)($transaction['fee_amount']??0), $transaction['currency_code']) ?></dd>
                    <dt class="col-5 text-muted">Net Amount</dt>   <dd class="col-7"><?= FormatHelper::money((float)($transaction['net_amount']??0), $transaction['currency_code']) ?></dd>
                    <dt class="col-5 text-muted">From IBAN</dt>    <dd class="col-7 text-mono small"><?= FormatHelper::e($transaction['from_iban']??'—') ?></dd>
                    <dt class="col-5 text-muted">To IBAN</dt>      <dd class="col-7 text-mono small"><?= FormatHelper::e($transaction['to_iban']??'—') ?></dd>
                    <?php if (!empty($transaction['creditor_name'])): ?>
                    <dt class="col-5 text-muted">Creditor Name</dt><dd class="col-7"><?= FormatHelper::e($transaction['creditor_name']) ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($transaction['remittance_info'])): ?>
                    <dt class="col-5 text-muted">Remittance</dt>   <dd class="col-7"><?= FormatHelper::e($transaction['remittance_info']) ?></dd>
                    <?php endif; ?>
                    <dt class="col-5 text-muted">Description</dt>  <dd class="col-7"><?= FormatHelper::e($transaction['description']??'—') ?></dd>
                    <dt class="col-5 text-muted">End-to-End ID</dt><dd class="col-7 text-mono small"><?= FormatHelper::e($transaction['end_to_end_id']??'—') ?></dd>
                    <dt class="col-5 text-muted">Booking Date</dt> <dd class="col-7"><?= FormatHelper::date($transaction['booking_date']??'') ?></dd>
                    <dt class="col-5 text-muted">Initiated By</dt> <dd class="col-7"><?= FormatHelper::e($transaction['initiated_by_name']??'—') ?></dd>
                    <dt class="col-5 text-muted">Created</dt>      <dd class="col-7"><?= FormatHelper::dateTime($transaction['created_at']??'') ?></dd>
                    <?php if (!empty($transaction['failure_reason'])): ?>
                    <dt class="col-5 text-muted text-danger">Failure Reason</dt>
                    <dd class="col-7 text-danger"><?= FormatHelper::e($transaction['failure_reason']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- Actions column -->
    <div class="col-md-7">

        <!-- Status Update -->
        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-pencil-square me-2 text-warning"></i>Update Status</div>
            <div class="card-body">
                <form method="POST" action="/transactions/<?= $transaction['id'] ?>/status">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="PATCH">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">New Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <?php foreach (['pending','processing','under_review','completed','failed','cancelled','reversed'] as $s): ?>
                                <option value="<?= $s ?>" <?= $transaction['status']===$s?'selected':'' ?>>
                                    <?= FormatHelper::titleCase($s) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="send_email" id="sendEmail" value="1">
                                <label class="form-check-label" for="sendEmail">Send email notification</label>
                            </div>
                        </div>
                        <div class="col-12" id="adminNoteField">
                            <label class="form-label fw-semibold">Note / Reason <small class="text-muted">(included in email)</small></label>
                            <textarea name="admin_note" class="form-control" rows="3"
                                      placeholder="e.g. Transfer placed under review pending AML check…"></textarea>
                        </div>
                        <div class="col-12" id="failureReasonField" style="display:none">
                            <label class="form-label fw-semibold">Failure Reason</label>
                            <input type="text" name="failure_reason" class="form-control"
                                   value="<?= FormatHelper::e($transaction['failure_reason']??'') ?>"
                                   placeholder="e.g. Insufficient funds, compliance hold…">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-arrow-repeat me-1"></i>Update Status
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Other Actions -->
        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-gear me-2 text-primary"></i>Actions</div>
            <div class="card-body d-flex flex-column gap-2">
                <?php if ($transaction['status'] === 'completed'): ?>
                <form method="POST" action="/transactions/<?= $transaction['id'] ?>/reverse" data-ajax="true" data-reload="true">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                    <button class="btn btn-outline-warning w-100" data-confirm="Reverse this transaction?">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reverse Transaction
                    </button>
                </form>
                <?php else: ?>
                <p class="text-muted small mb-0">Use the «Update Status» panel above to change this transaction's state.</p>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /col-md-7 -->
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';

