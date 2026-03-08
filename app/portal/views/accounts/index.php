<?php
$fmt   = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
$esc   = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$grads = ['grad-0','grad-1','grad-2','grad-3','grad-4','grad-5'];
ob_start(); ?>

<div class="page-header d-flex align-items-center justify-content-between">
  <div>
    <h4 class="fw-bold mb-0">My Accounts</h4>
    <p class="text-muted small">All your bank accounts in one place</p>
  </div>
  <a href="/transfer" class="btn btn-primary d-none d-md-flex align-items-center gap-2">
    <i class="bi bi-send"></i> Transfer
  </a>
</div>

<?php if (empty($accounts)): ?>
<div class="portal-card text-center py-5">
  <i class="bi bi-wallet2 fs-1 d-block mb-3 opacity-30" style="color:#64748b"></i>
  <h5 class="fw-bold mb-1">No accounts yet</h5>
  <p class="text-muted small mb-3">Contact our support team to open your first account.</p>
  <a href="/support" class="btn btn-primary btn-sm">Contact Support</a>
</div>
<?php else: ?>

<!-- Total balance -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="balance-hero">
      <div class="hero-label">Total Net Worth</div>
      <div class="hero-amount">
        <?= $fmt(array_sum(array_column($accounts,'balance')), 'EUR') ?>
      </div>
      <div class="hero-change"><?= count($accounts) ?> account<?= count($accounts)!==1?'s':'' ?></div>
    </div>
  </div>
</div>

<!-- Account cards -->
<div class="row g-3 fade-in-up">
  <?php foreach ($accounts as $i => $a): ?>
  <div class="col-md-6 col-xl-4">
    <div class="account-card <?= $grads[$i % count($grads)] ?>">
      <div class="d-flex align-items-start justify-content-between">
        <div class="acct-type"><?= $esc($a['account_type_name']??'Account') ?></div>
        <button class="acct-copy-btn" data-copy="<?= $esc($a['iban']) ?>" title="Copy IBAN">
          <i class="bi bi-clipboard"></i>
        </button>
      </div>
      <div class="acct-iban"><?= chunk_split($esc(str_replace(' ','',$a['iban'])),4,' ') ?></div>
      <div class="acct-balance"><?= $fmt((float)$a['balance'], $a['currency_code']) ?></div>
      <div class="d-flex justify-content-between mt-1" style="opacity:.65;font-size:.75rem">
        <span><?= $esc($a['currency_code']) ?></span>
        <span><?= ucfirst($a['status']) ?></span>
        <?php if (!empty($a['bic'])): ?><span>BIC: <?= $esc($a['bic']) ?></span><?php endif; ?>
      </div>
      <div class="acct-actions">
        <a href="/accounts/<?= $a['id'] ?>" class="acct-btn"><i class="bi bi-eye me-1"></i>Statement</a>
        <a href="/transfer?from=<?= $a['id'] ?>" class="acct-btn"><i class="bi bi-send me-1"></i>Transfer</a>
        <a href="/transfer/sepa?from=<?= $a['id'] ?>" class="acct-btn"><i class="bi bi-globe me-1"></i>SEPA</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
