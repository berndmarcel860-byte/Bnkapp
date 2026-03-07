<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">My Loans</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#applyModal">
        <i class="bi bi-plus-circle me-1"></i>Apply for Loan
    </button>
</div>

<?php if (empty($loans)): ?>
<div class="card border-0 shadow-sm text-center py-5 text-muted">
    <i class="bi bi-cash-coin fs-1 d-block mb-2 opacity-40"></i>No loans yet.
</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($loans as $l): ?>
<div class="col-md-6">
    <a href="/loans/<?= $l['id'] ?>" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-semibold"><?= $esc(ucwords(str_replace('_',' ',$l['loan_type']))) ?></span>
                <span class="badge text-bg-<?= match($l['status']){
                    'active','disbursed'=>'success','applied','in_review','approved'=>'warning','paid_off'=>'info',default=>'danger'} ?>"><?= $esc($l['status']) ?></span>
            </div>
            <div class="mb-1">Principal: <strong><?= $fmt((float)$l['principal_amount']) ?></strong></div>
            <div class="mb-1">Outstanding: <strong class="text-danger"><?= $fmt((float)$l['outstanding_balance']) ?></strong></div>
            <div class="progress mb-2" style="height:6px">
                <?php $pct = $l['principal_amount']>0 ? (1-$l['outstanding_balance']/$l['principal_amount'])*100 : 0; ?>
                <div class="progress-bar bg-success" style="width:<?= round($pct) ?>%"></div>
            </div>
            <div class="small text-muted"><?= round($pct) ?>% repaid · <?= $l['term_months'] ?> months</div>
        </div>
    </a>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Apply Modal -->
<div class="modal fade" id="applyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/loans">
                <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header"><h5 class="modal-title">Loan Application</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label fw-semibold">Loan Type</label>
                        <select name="loan_type" class="form-select" required>
                            <option value="personal">Personal Loan</option>
                            <option value="auto">Auto Loan</option>
                            <option value="mortgage">Mortgage</option>
                            <option value="business">Business Loan</option>
                            <option value="student">Student Loan</option>
                        </select>
                    </div>
                    <div class="col-6"><label class="form-label fw-semibold">Amount (EUR)</label><input type="number" name="principal_amount" step="0.01" min="100" class="form-control" required></div>
                    <div class="col-6"><label class="form-label fw-semibold">Term (months)</label><input type="number" name="term_months" min="1" max="360" class="form-control" required></div>
                    <div class="col-12"><label class="form-label fw-semibold">Disbursement Account ID</label><input type="number" name="disbursement_account_id" class="form-control" required></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
