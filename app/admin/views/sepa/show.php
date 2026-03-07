<?php
/**
 * View: sepa/show.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="/sepa" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">SEPA Transfer #<?= $transfer['id'] ?></h1>
    <span class="badge text-bg-<?= FormatHelper::statusBadge($transfer['status']) ?>"><?= $transfer['status'] ?></span>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card table-card">
            <div class="card-header fw-semibold">Transfer Details</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Type</dt><dd class="col-7"><?= FormatHelper::titleCase($transfer['transfer_type']??'') ?></dd>
                    <dt class="col-5 text-muted">Amount</dt><dd class="col-7 fw-bold"><?= FormatHelper::money((float)$transfer['amount'], $transfer['currency_code']??'EUR') ?></dd>
                    <dt class="col-5 text-muted">Debtor IBAN</dt><dd class="col-7 text-mono"><?= FormatHelper::e($transfer['debtor_iban']??'—') ?></dd>
                    <dt class="col-5 text-muted">Debtor Name</dt><dd class="col-7"><?= FormatHelper::e($transfer['debtor_name']??'—') ?></dd>
                    <dt class="col-5 text-muted">Creditor IBAN</dt><dd class="col-7 text-mono"><?= FormatHelper::e($transfer['creditor_iban']??$transfer['creditor_iban_raw']??'—') ?></dd>
                    <dt class="col-5 text-muted">Creditor Name</dt><dd class="col-7"><?= FormatHelper::e($transfer['creditor_name']??'—') ?></dd>
                    <dt class="col-5 text-muted">Reference</dt><dd class="col-7 text-mono"><?= FormatHelper::e($transfer['end_to_end_id']??'—') ?></dd>
                    <dt class="col-5 text-muted">Remittance</dt><dd class="col-7"><?= FormatHelper::e($transfer['remittance_information']??'—') ?></dd>
                    <dt class="col-5 text-muted">Settlement Date</dt><dd class="col-7"><?= FormatHelper::date($transfer['settlement_date']??'') ?></dd>
                    <dt class="col-5 text-muted">Created</dt><dd class="col-7"><?= FormatHelper::dateTime($transfer['created_at']) ?></dd>
                    <?php if (!empty($transfer['rejection_reason'])): ?>
                    <dt class="col-5 text-muted">Rejection Reason</dt><dd class="col-7 text-danger"><?= FormatHelper::e($transfer['rejection_reason']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
