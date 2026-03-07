<?php
use BnkApp\Helpers\FormatHelper;
// Use the admin FormatHelper since portal shares same DB and money format
ob_start();
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB', \NumberFormatter::CURRENCY))->formatCurrency($v, $cur);
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
?>

<div class="mb-4">
    <h4 class="fw-bold mb-0">Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>, <?= $esc($authUser['first_name']??'') ?>!</h4>
    <p class="text-muted small">Here's an overview of your finances.</p>
</div>

<!-- Account balance cards -->
<div class="row g-3 mb-4">
    <?php
    $bgColors = ['bg-primary','bg-success','bg-info','bg-warning','bg-secondary'];
    foreach ($accounts as $i => $a):
        $bg = $bgColors[$i % count($bgColors)];
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="account-card card text-white <?= $bg ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="small opacity-75"><?= $esc($a['account_type_name']??'Account') ?></div>
                        <div class="account-iban opacity-75"><?= $esc($a['iban']) ?></div>
                    </div>
                    <button class="btn btn-sm btn-link text-white opacity-75 p-0" data-copy="<?= $esc($a['iban']) ?>" title="Copy IBAN">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <div class="account-balance"><?= $fmt((float)$a['balance'], $a['currency_code']) ?></div>
                <div class="small opacity-75 mt-1"><?= $a['currency_code'] ?> · <?= ucfirst($a['status']) ?></div>
            </div>
            <div class="card-footer d-flex gap-2" style="background:rgba(0,0,0,.15);border:none">
                <a href="/accounts/<?= $a['id'] ?>" class="btn btn-sm btn-light btn-sm flex-fill">Details</a>
                <a href="/transfer?account=<?= $a['id'] ?>" class="btn btn-sm btn-light btn-sm flex-fill">Transfer</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($accounts)): ?>
    <div class="col-12">
        <div class="card border-dashed text-center py-5">
            <div class="text-muted">
                <i class="bi bi-wallet2 fs-1 d-block mb-2 opacity-50"></i>
                No active accounts yet. Contact support to open your first account.
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Quick actions -->
<div class="row g-3 mb-4">
    <?php
    $actions = [
        ['/transfer','bi-send','primary','Transfer'],
        ['/transfer/sepa','bi-globe','info','SEPA'],
        ['/standing-orders','bi-repeat','success','Standing Order'],
        ['/loans','bi-cash-coin','warning','Apply for Loan'],
        ['/support','bi-headset','secondary','Support'],
        ['/kyc','bi-shield-check','danger','Verify KYC'],
    ];
    foreach ($actions as [$url,$icon,$color,$label]): ?>
    <div class="col-4 col-md-2">
        <a href="<?= $url ?>" class="card text-center text-decoration-none p-3 h-100 border-0 shadow-sm">
            <i class="bi <?= $icon ?> fs-3 text-<?= $color ?> mb-1"></i>
            <div class="small text-muted fw-semibold"><?= $label ?></div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Recent transactions -->
<div class="card shadow-sm border-0">
    <div class="card-header d-flex align-items-center justify-content-between bg-white">
        <span class="fw-semibold"><i class="bi bi-list-ul me-2 text-primary"></i>Recent Transactions</span>
        <a href="/transactions" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <?php if (empty($transactions)): ?>
    <div class="card-body text-center text-muted py-4">No transactions yet.</div>
    <?php else: ?>
    <?php foreach ($transactions as $t):
        $isDebit = isset($t['from_user_id']) && $t['from_user_id'] === \BnkPortal\Core\Auth::id();
        $amount  = (float)$t['amount'];
    ?>
    <div class="txn-item">
        <div class="txn-icon <?= $isDebit?'debit':'credit' ?>">
            <i class="bi bi-<?= $isDebit?'arrow-up-right':'arrow-down-left' ?>"></i>
        </div>
        <div class="flex-grow-1">
            <div class="fw-semibold small"><?= $esc($t['description']??ucfirst(str_replace('_',' ',$t['transaction_type']??''))) ?></div>
            <div class="text-muted" style="font-size:.75rem"><?= $esc(substr($t['created_at']??'',0,16)) ?></div>
        </div>
        <div class="text-end">
            <div class="fw-bold small <?= $isDebit?'text-danger':'text-success' ?>">
                <?= $isDebit?'−':'+' ?><?= $fmt($amount, $t['currency_code']??'EUR') ?>
            </div>
            <span class="badge text-bg-<?= match($t['status']??''){
                'completed','settled'=>'success','pending'=>'warning','failed'=>'danger',default=>'secondary'} ?>" style="font-size:.65rem">
                <?= $esc($t['status']??'') ?>
            </span>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
