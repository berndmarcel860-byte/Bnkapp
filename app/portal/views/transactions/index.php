<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
ob_start(); ?>

<h4 class="fw-bold mb-4">Transactions</h4>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between">
        <span class="fw-semibold">All Transactions</span>
        <small class="text-muted"><?= number_format($total) ?> total</small>
    </div>
    <?php if (empty($transactions)): ?>
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-list-ul fs-1 d-block mb-2 opacity-40"></i>No transactions yet.
    </div>
    <?php else: ?>
    <?php foreach ($transactions as $t):
        $isDebit = isset($t['from_user_id']) && $t['from_user_id'] === \BnkPortal\Core\Auth::id();
    ?>
    <a href="/transactions/<?= $t['id'] ?>" class="txn-item text-decoration-none text-dark">
        <div class="txn-icon <?= $isDebit?'debit':'credit' ?>">
            <i class="bi bi-<?= $isDebit?'arrow-up-right':'arrow-down-left' ?>"></i>
        </div>
        <div class="flex-grow-1">
            <div class="fw-semibold small"><?= $esc($t['description']??ucfirst(str_replace('_',' ',$t['transaction_type']))) ?></div>
            <div class="text-muted" style="font-size:.75rem"><?= substr($t['created_at']??'',0,16) ?>
                · <span class="badge text-bg-<?= match($t['status']??''){
                    'completed','settled'=>'success','pending','processing'=>'warning','failed','cancelled'=>'danger',default=>'secondary'} ?>" style="font-size:.65rem"><?= $esc($t['status']??'') ?></span>
            </div>
        </div>
        <div class="text-end fw-bold <?= $isDebit?'text-danger':'text-success' ?>">
            <?= $isDebit?'−':'+' ?><?= $fmt((float)$t['amount'], $t['currency_code']) ?>
        </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($lastPage > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-center">
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($p=max(1,$page-2);$p<=min($lastPage,$page+2);$p++): ?>
            <li class="page-item <?= $p===$page?'active':'' ?>">
                <a class="page-link" href="/transactions?page=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
