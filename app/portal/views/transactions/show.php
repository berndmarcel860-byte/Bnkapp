<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/transactions" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0">Transaction #<?= $transaction['id'] ?></h4>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="text-center mb-4">
            <div class="fs-2 fw-bold text-primary"><?= $fmt((float)$transaction['amount'], $transaction['currency_code']) ?></div>
            <span class="badge text-bg-<?= match($transaction['status']??''){
                'completed','settled'=>'success','pending','processing'=>'warning','failed','cancelled'=>'danger',default=>'secondary'} ?> fs-6">
                <?= $esc($transaction['status']??'') ?>
            </span>
        </div>
        <dl class="row">
            <dt class="col-5 text-muted">Type</dt><dd class="col-7"><?= $esc(ucwords(str_replace('_',' ',$transaction['transaction_type']))) ?></dd>
            <dt class="col-5 text-muted">From IBAN</dt><dd class="col-7 font-monospace small"><?= $esc($transaction['from_iban']??'—') ?></dd>
            <dt class="col-5 text-muted">To IBAN</dt><dd class="col-7 font-monospace small"><?= $esc($transaction['to_iban']??'—') ?></dd>
            <dt class="col-5 text-muted">Description</dt><dd class="col-7"><?= $esc($transaction['description']??'—') ?></dd>
            <dt class="col-5 text-muted">Fee</dt><dd class="col-7"><?= $fmt((float)($transaction['fee_amount']??0),$transaction['currency_code']) ?></dd>
            <dt class="col-5 text-muted">Booking Date</dt><dd class="col-7"><?= $esc(substr($transaction['booking_date']??'—',0,10)) ?></dd>
            <dt class="col-5 text-muted">Reference</dt><dd class="col-7 font-monospace small"><?= $esc($transaction['transaction_ref']??'—') ?></dd>
            <dt class="col-5 text-muted">Date</dt><dd class="col-7 text-muted"><?= $esc(substr($transaction['created_at']??'',0,16)) ?></dd>
        </dl>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
