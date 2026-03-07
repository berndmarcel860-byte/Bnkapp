<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/accounts" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0">Account</h4>
    <span class="font-monospace small text-muted"><?= $esc($account['iban']) ?></span>
</div>

<!-- Balance card -->
<div class="card text-white bg-primary mb-4">
    <div class="card-body">
        <div class="small opacity-75 mb-1"><?= $esc($account['account_type_name']??'Account') ?></div>
        <div class="account-balance"><?= $fmt((float)$account['balance'], $account['currency_code']) ?></div>
        <div class="account-iban"><?= $esc($account['iban']) ?>
            <button class="btn btn-link text-white p-0 ms-2 opacity-75" data-copy="<?= $esc($account['iban']) ?>"><i class="bi bi-clipboard"></i></button>
        </div>
        <div class="mt-2 small d-flex gap-3 opacity-75">
            <span>Status: <?= ucfirst($account['status']) ?></span>
            <span>BIC: <?= $esc($account['bic']??'—') ?></span>
        </div>
    </div>
    <div class="card-footer d-flex gap-2" style="background:rgba(0,0,0,.15);border:none">
        <a href="/transfer?from=<?= $account['id'] ?>" class="btn btn-light btn-sm"><i class="bi bi-send me-1"></i>Transfer</a>
        <a href="/transfer/sepa?from=<?= $account['id'] ?>" class="btn btn-light btn-sm"><i class="bi bi-globe me-1"></i>SEPA</a>
    </div>
</div>

<!-- Transaction history -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-list-ul me-2 text-primary"></i>Statement</div>
    <?php if (empty($transactions)): ?>
    <div class="card-body text-center text-muted py-4">No transactions on this account yet.</div>
    <?php else: ?>
    <?php foreach ($transactions as $t):
        $isDebit = $t['from_account_id'] == $account['id'];
    ?>
    <div class="txn-item">
        <div class="txn-icon <?= $isDebit?'debit':'credit' ?>">
            <i class="bi bi-<?= $isDebit?'arrow-up-right':'arrow-down-left' ?>"></i>
        </div>
        <div class="flex-grow-1">
            <div class="fw-semibold small"><?= $esc($t['description']??ucfirst(str_replace('_',' ',$t['transaction_type']))) ?></div>
            <div class="text-muted" style="font-size:.75rem"><?= substr($t['created_at']??'',0,16) ?></div>
        </div>
        <div class="text-end">
            <div class="fw-bold <?= $isDebit?'text-danger':'text-success' ?>">
                <?= $isDebit?'−':'+' ?><?= $fmt((float)$t['amount'], $t['currency_code']) ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
