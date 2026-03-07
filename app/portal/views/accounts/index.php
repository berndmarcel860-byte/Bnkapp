<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
ob_start(); ?>

<h4 class="fw-bold mb-4">My Accounts</h4>

<div class="row g-3">
    <?php if (empty($accounts)): ?>
    <div class="col-12 text-center text-muted py-5">
        <i class="bi bi-wallet2 fs-1 d-block mb-2 opacity-40"></i>
        No accounts yet. Contact support to open your first account.
    </div>
    <?php else: ?>
    <?php $bgs=['bg-primary','bg-success','bg-info','bg-warning','bg-secondary'];
    foreach ($accounts as $i => $a): ?>
    <div class="col-md-6">
        <div class="account-card card text-white <?= $bgs[$i%5] ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <div class="small opacity-75"><?= $esc($a['account_type_name']??'Account') ?></div>
                        <div class="account-iban"><?= $esc($a['iban']) ?></div>
                    </div>
                    <button class="btn btn-link text-white p-0 opacity-75" data-copy="<?= $esc($a['iban']) ?>" title="Copy IBAN">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <div class="account-balance"><?= $fmt((float)$a['balance'], $a['currency_code']) ?></div>
                <div class="d-flex justify-content-between mt-2 small opacity-75">
                    <span><?= $a['currency_code'] ?></span>
                    <span><?= ucfirst($a['status']) ?></span>
                </div>
            </div>
            <div class="card-footer d-flex gap-2" style="background:rgba(0,0,0,.15);border:none">
                <a href="/accounts/<?= $a['id'] ?>" class="btn btn-light btn-sm flex-fill">Statement</a>
                <a href="/transfer?from=<?= $a['id'] ?>" class="btn btn-light btn-sm flex-fill">Transfer</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
