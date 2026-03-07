<?php
/**
 * View: kyc/show.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="/kyc" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">KYC Document #<?= $doc['id'] ?></h1>
    <span class="badge text-bg-<?= FormatHelper::statusBadge($doc['status']) ?>"><?= $doc['status'] ?></span>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card table-card mb-3">
            <div class="card-header fw-semibold">Document Details</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-4 text-muted">Owner</dt>
                    <dd class="col-8"><a href="/users/<?= $doc['user_id'] ?>"><?= FormatHelper::e($doc['owner_name']) ?></a> — <?= FormatHelper::e($doc['owner_email']) ?></dd>
                    <dt class="col-4 text-muted">Type</dt>
                    <dd class="col-8"><?= FormatHelper::titleCase($doc['document_type']) ?></dd>
                    <dt class="col-4 text-muted">Document #</dt>
                    <dd class="col-8 text-mono"><?= FormatHelper::e($doc['document_number']??'—') ?></dd>
                    <dt class="col-4 text-muted">Expiry</dt>
                    <dd class="col-8"><?= FormatHelper::date($doc['expiry_date']??'') ?></dd>
                    <dt class="col-4 text-muted">Submitted</dt>
                    <dd class="col-8"><?= FormatHelper::dateTime($doc['created_at']) ?></dd>
                    <dt class="col-4 text-muted">File</dt>
                    <dd class="col-8 text-mono small"><?= FormatHelper::e($doc['file_path']??'—') ?></dd>
                    <?php if ($doc['rejection_reason']): ?>
                    <dt class="col-4 text-muted">Rejection Reason</dt>
                    <dd class="col-8 text-danger"><?= FormatHelper::e($doc['rejection_reason']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <?php if (!in_array($doc['status'], ['approved','rejected'])): ?>
        <div class="card table-card">
            <div class="card-header fw-semibold">Review Decision</div>
            <div class="card-body">
                <form method="POST" action="/kyc/<?= $doc['id'] ?>/review" data-ajax="true" data-reload="true">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="PATCH">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Decision</label>
                        <select name="status" class="form-select" required>
                            <option value="approved">Approve</option>
                            <option value="rejected">Reject</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rejection Reason <small class="text-muted">(if rejecting)</small></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Explain the reason…"></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Submit Decision</button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-<?= FormatHelper::statusBadge($doc['status']) ?>">
            Document has been <strong><?= $doc['status'] ?></strong>.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
