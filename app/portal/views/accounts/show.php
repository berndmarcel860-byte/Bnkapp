<?php
$fmt   = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
$esc   = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$userId = \BnkPortal\Core\Auth::id();
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="/accounts" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
  <div>
    <h4 class="fw-bold mb-0">Account Statement</h4>
    <span class="text-muted small font-mono"><?= $esc($account['iban']) ?></span>
  </div>
</div>

<!-- Account Card Visual -->
<div class="row g-3 mb-4">
  <div class="col-md-5 col-lg-4">
    <div class="account-card grad-0" style="cursor:default">
      <div class="acct-type d-flex align-items-center justify-content-between">
        <span><?= $esc($account['account_type_name']??'Account') ?></span>
        <button class="acct-copy-btn" data-copy="<?= $esc($account['iban']) ?>" title="Copy IBAN">
          <i class="bi bi-clipboard"></i>
        </button>
      </div>
      <div class="acct-iban"><?= chunk_split($esc(str_replace(' ','',$account['iban'])),4,' ') ?></div>
      <div class="acct-balance"><?= $fmt((float)$account['balance'], $account['currency_code']) ?></div>
      <div style="font-size:.75rem;color:rgba(255,255,255,.6);margin-top:.25rem">
        <?= $esc($account['currency_code']) ?> · <?= ucfirst($account['status']) ?>
        <?php if (!empty($account['bic'])): ?> · <?= $esc($account['bic']) ?><?php endif; ?>
      </div>
      <div class="acct-actions">
        <a href="/transfer?from=<?= $account['id'] ?>" class="acct-btn"><i class="bi bi-send me-1"></i>Transfer</a>
        <a href="/transfer/sepa?from=<?= $account['id'] ?>" class="acct-btn"><i class="bi bi-globe me-1"></i>SEPA</a>
      </div>
    </div>
  </div>
  <div class="col-md-7 col-lg-8">
    <div class="row g-3 h-100">
      <div class="col-6">
        <div class="stat-card">
          <div class="stat-icon" style="background:#f0fdf4;color:#059669"><i class="bi bi-bank"></i></div>
          <div>
            <div class="stat-label">Available Balance</div>
            <div class="stat-value" style="font-size:1rem"><?= $fmt((float)($account['available_balance']??$account['balance']), $account['currency_code']) ?></div>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="stat-card">
          <div class="stat-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-calendar-check"></i></div>
          <div>
            <div class="stat-label">Opened</div>
            <div class="stat-value" style="font-size:.95rem"><?= date('d M Y', strtotime($account['opened_at']??'now')) ?></div>
          </div>
        </div>
      </div>
      <div class="col-12">
        <div class="info-card">
          <h6>Account Details</h6>
          <div class="row g-0">
            <div class="col-5 text-muted small py-1">IBAN</div>
            <div class="col-7 small py-1 font-mono"><?= $esc($account['iban']) ?></div>
            <?php if (!empty($account['bic'])): ?>
            <div class="col-5 text-muted small py-1">BIC / SWIFT</div>
            <div class="col-7 small py-1 font-mono"><?= $esc($account['bic']) ?></div>
            <?php endif; ?>
            <div class="col-5 text-muted small py-1">Currency</div>
            <div class="col-7 small py-1"><?= $esc($account['currency_code']) ?></div>
            <div class="col-5 text-muted small py-1">Status</div>
            <div class="col-7 small py-1"><span class="badge text-bg-success"><?= ucfirst($account['status']) ?></span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Transaction Statement -->
<div class="portal-card">
  <div class="portal-card-header">
    <span><i class="bi bi-list-ul me-2"></i>Transaction History</span>
    <a href="/transactions" class="btn btn-sm btn-outline-primary" style="font-size:.78rem">All Transactions</a>
  </div>

  <?php if (empty($transactions)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-30"></i>
    <p class="mb-0 small">No transactions on this account yet.</p>
  </div>
  <?php else: ?>
  <div class="txn-feed">
    <?php foreach ($transactions as $t):
      $isDebit = $t['from_account_id'] == $account['id'];
      $statusCls = match($t['status']??'') {
        'completed','settled' => 'completed',
        'pending','processing','under_review' => 'pending',
        'failed','cancelled' => 'failed',
        default => 'pending',
      };
    ?>
    <a href="/transactions/<?= $t['id'] ?>" class="txn-row">
      <div class="txn-icon-wrap <?= $isDebit ? 'debit' : 'credit' ?>">
        <i class="bi bi-<?= $isDebit ? 'arrow-up-right' : 'arrow-down-left' ?>"></i>
      </div>
      <div class="txn-body">
        <div class="txn-title"><?= $esc($t['description']??ucwords(str_replace('_',' ',$t['transaction_type']??''))) ?></div>
        <div class="txn-meta">
          <span class="txn-status-dot <?= $statusCls ?>"></span>
          <?= ucwords(str_replace('_',' ',$t['status']??'')) ?>
          · <?= $esc(substr($t['created_at']??'',0,16)) ?>
        </div>
      </div>
      <div class="txn-amount <?= $isDebit ? 'debit' : 'credit' ?>">
        <?= $isDebit ? '−' : '+' ?><?= $fmt((float)$t['amount'], $t['currency_code']) ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
