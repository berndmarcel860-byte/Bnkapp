<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/loans" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0">Loan #<?= $loan['id'] ?></h4>
    <span class="badge text-bg-<?= match($loan['status']){
        'active','disbursed'=>'success','applied','in_review','approved'=>'warning','paid_off'=>'info',default=>'danger'} ?>"><?= $loan['status'] ?></span>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Loan Details</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-6 text-muted">Type</dt><dd class="col-6"><?= $esc(ucwords(str_replace('_',' ',$loan['loan_type']))) ?></dd>
                    <dt class="col-6 text-muted">Principal</dt><dd class="col-6 fw-bold"><?= $fmt((float)$loan['principal_amount']) ?></dd>
                    <dt class="col-6 text-muted">Outstanding</dt><dd class="col-6 text-danger fw-bold"><?= $fmt((float)$loan['outstanding_balance']) ?></dd>
                    <dt class="col-6 text-muted">Interest Rate</dt><dd class="col-6"><?= number_format((float)$loan['interest_rate']*100,2) ?>% p.a.</dd>
                    <dt class="col-6 text-muted">Monthly Payment</dt><dd class="col-6"><?= $fmt((float)($loan['monthly_payment']??0)) ?></dd>
                    <dt class="col-6 text-muted">Term</dt><dd class="col-6"><?= $loan['term_months'] ?> months</dd>
                    <dt class="col-6 text-muted">Start Date</dt><dd class="col-6"><?= $esc(substr($loan['start_date']??'—',0,10)) ?></dd>
                    <dt class="col-6 text-muted">Maturity Date</dt><dd class="col-6"><?= $esc(substr($loan['maturity_date']??'—',0,10)) ?></dd>
                </dl>
                <div class="progress mt-3" style="height:8px">
                    <?php $pct = $loan['principal_amount']>0 ? (1-$loan['outstanding_balance']/$loan['principal_amount'])*100 : 0; ?>
                    <div class="progress-bar bg-success" style="width:<?= round($pct) ?>%" role="progressbar"><?= round($pct) ?>%</div>
                </div>
                <div class="small text-muted mt-1 text-end"><?= round($pct) ?>% repaid</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Payment Schedule</div>
            <div class="table-responsive" style="max-height:320px;overflow-y:auto">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Due Date</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No payment schedule yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $i => $p): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= $esc(substr($p['due_date'],0,10)) ?></td>
                            <td><?= $fmt((float)$p['amount']) ?></td>
                            <td><span class="badge text-bg-<?= match($p['status']??'pending'){
                                'paid'=>'success','overdue'=>'danger',default=>'warning'} ?>"><?= $p['status']??'pending' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
