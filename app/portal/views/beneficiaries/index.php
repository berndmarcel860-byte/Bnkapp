<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">Beneficiaries</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBenModal">
        <i class="bi bi-plus-circle me-1"></i>Add
    </button>
</div>

<?php if (empty($beneficiaries)): ?>
<div class="card border-0 shadow-sm text-center py-5 text-muted">
    <i class="bi bi-person-lines-fill fs-1 d-block mb-2 opacity-40"></i>
    No beneficiaries saved yet.
</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($beneficiaries as $b): ?>
<div class="col-md-6">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-semibold"><?= $esc($b['account_holder_name']) ?></div>
                <div class="text-mono small text-muted"><?= $esc($b['iban']) ?>
                    <button class="btn btn-link p-0 ms-1 text-muted" data-copy="<?= $esc($b['iban']) ?>"><i class="bi bi-clipboard small"></i></button>
                </div>
                <?php if ($b['bic']): ?><div class="small text-muted">BIC: <?= $esc($b['bic']) ?></div><?php endif; ?>
                <?php if ($b['bank_name']): ?><div class="small text-muted"><?= $esc($b['bank_name']) ?></div><?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <a href="/transfer/sepa?creditor_iban=<?= urlencode($b['iban']) ?>&creditor_name=<?= urlencode($b['account_holder_name']) ?>"
                   class="btn btn-sm btn-outline-primary"><i class="bi bi-send"></i></a>
                <form method="POST" action="/beneficiaries/<?= $b['id'] ?>">
                    <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button class="btn btn-sm btn-outline-danger" data-confirm="Remove this beneficiary?"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add modal -->
<div class="modal fade" id="addBenModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/beneficiaries">
                <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header"><h5 class="modal-title">Add Beneficiary</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label fw-semibold">Name</label><input type="text" name="account_holder_name" class="form-control" required></div>
                    <div class="col-12"><label class="form-label fw-semibold">IBAN</label><input type="text" name="iban" class="form-control font-monospace" required></div>
                    <div class="col-6"><label class="form-label fw-semibold">BIC</label><input type="text" name="bic" class="form-control"></div>
                    <div class="col-6"><label class="form-label fw-semibold">Bank Name</label><input type="text" name="bank_name" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
