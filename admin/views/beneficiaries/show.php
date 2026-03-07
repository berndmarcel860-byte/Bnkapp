<?php
/**
 * View: beneficiaries/show.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="/beneficiaries" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Beneficiary #<?= $beneficiary['id'] ?></h1>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card table-card">
            <div class="card-header fw-semibold">Beneficiary Details</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Name</dt><dd class="col-7 fw-semibold"><?= FormatHelper::e($beneficiary['beneficiary_name']) ?></dd>
                    <dt class="col-5 text-muted">IBAN</dt><dd class="col-7 text-mono"><?= FormatHelper::e($beneficiary['iban']) ?></dd>
                    <dt class="col-5 text-muted">BIC</dt><dd class="col-7 text-mono"><?= FormatHelper::e($beneficiary['bic']??'—') ?></dd>
                    <dt class="col-5 text-muted">Bank</dt><dd class="col-7"><?= FormatHelper::e($beneficiary['bank_name']??'—') ?></dd>
                    <dt class="col-5 text-muted">Owner</dt><dd class="col-7"><a href="/users/<?= $beneficiary['user_id'] ?>"><?= FormatHelper::e($beneficiary['owner_name']) ?></a></dd>
                    <dt class="col-5 text-muted">Verified</dt><dd class="col-7"><?= $beneficiary['is_verified'] ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-warning">No</span>' ?></dd>
                    <dt class="col-5 text-muted">Added</dt><dd class="col-7"><?= FormatHelper::dateTime($beneficiary['created_at']) ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
