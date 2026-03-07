<?php
/**
 * View: mandates/show.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="/mandates" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Mandate #<?= $mandate['id'] ?></h1>
    <span class="badge text-bg-<?= FormatHelper::statusBadge($mandate['status']) ?>"><?= $mandate['status'] ?></span>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card table-card">
            <div class="card-header fw-semibold">Mandate Details</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Mandate Ref</dt><dd class="col-7 text-mono"><?= FormatHelper::e($mandate['mandate_reference']) ?></dd>
                    <dt class="col-5 text-muted">Sequence Type</dt><dd class="col-7"><?= FormatHelper::titleCase($mandate['sequence_type']??'') ?></dd>
                    <dt class="col-5 text-muted">Debtor Name</dt><dd class="col-7"><?= FormatHelper::e($mandate['debtor_name']) ?></dd>
                    <dt class="col-5 text-muted">Debtor IBAN</dt><dd class="col-7 text-mono"><?= FormatHelper::e($mandate['debtor_iban']) ?></dd>
                    <dt class="col-5 text-muted">Creditor Name</dt><dd class="col-7"><?= FormatHelper::e($mandate['creditor_name']??'—') ?></dd>
                    <dt class="col-5 text-muted">Creditor ID</dt><dd class="col-7 text-mono"><?= FormatHelper::e($mandate['creditor_identifier']??'—') ?></dd>
                    <dt class="col-5 text-muted">Signing Date</dt><dd class="col-7"><?= FormatHelper::date($mandate['signing_date']??'') ?></dd>
                    <dt class="col-5 text-muted">Expiry</dt><dd class="col-7"><?= FormatHelper::date($mandate['expiry_date']??'') ?></dd>
                    <dt class="col-5 text-muted">Created</dt><dd class="col-7"><?= FormatHelper::dateTime($mandate['created_at']) ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <?php if ($mandate['status']==='active'): ?>
        <div class="card table-card">
            <div class="card-header fw-semibold">Actions</div>
            <div class="card-body">
                <form method="POST" action="/mandates/<?= $mandate['id'] ?>/revoke" data-ajax="true" data-redirect="/mandates">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="PATCH">
                    <div class="d-grid">
                        <button class="btn btn-danger" data-confirm="Revoke this mandate permanently?">
                            <i class="bi bi-x-circle me-1"></i>Revoke Mandate
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
