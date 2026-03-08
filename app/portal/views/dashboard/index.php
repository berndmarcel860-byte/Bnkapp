<?php
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB', \NumberFormatter::CURRENCY))->formatCurrency($v, $cur);
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$userId = \BnkPortal\Core\Auth::id();
$grads  = ['grad-0','grad-1','grad-2','grad-3','grad-4','grad-5'];
$hour   = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
ob_start();
?>

<div class="fade-in-up">

<!-- ── Header ─────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-bold mb-0" style="letter-spacing:-.3px">
      <?= $greeting ?>, <?= $esc($authUser['first_name']??'') ?>! 👋
    </h4>
    <p class="text-muted small mb-0"><?= date('l, d F Y') ?></p>
  </div>
  <a href="/transfer" class="btn btn-primary d-none d-md-flex align-items-center gap-2">
    <i class="bi bi-send"></i> Send Money
  </a>
</div>

<!-- ── Hero balance + stats row ─────────────────────────────────────── -->
<div class="row g-3 mb-4 fade-in-up">

  <!-- Total balance hero -->
  <div class="col-lg-6 col-xl-5">
    <div class="balance-hero h-100">
      <div class="hero-label">Total Balance</div>
      <div class="hero-amount" id="heroBalance">
        <?= $fmt((float)($totalBalance??0), 'EUR') ?>
      </div>
      <div class="hero-change">
        <span class="me-2">Across <?= count($accounts) ?> account<?= count($accounts)!==1?'s':'' ?></span>
        <?php if ($notifCount > 0): ?>
        <a href="/notifications" class="text-decoration-none" style="color:rgba(255,255,255,.75)">
          <i class="bi bi-bell-fill me-1"></i><?= $notifCount ?> new notification<?= $notifCount!==1?'s':'' ?>
        </a>
        <?php endif; ?>
      </div>
      <div class="d-flex gap-2 mt-3 flex-wrap">
        <a href="/transfer" class="btn btn-sm" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(4px);font-size:.8rem;font-weight:600;border-radius:8px">
          <i class="bi bi-send me-1"></i>Transfer
        </a>
        <a href="/transfer/sepa" class="btn btn-sm" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(4px);font-size:.8rem;font-weight:600;border-radius:8px">
          <i class="bi bi-globe me-1"></i>SEPA
        </a>
        <a href="/accounts" class="btn btn-sm" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(4px);font-size:.8rem;font-weight:600;border-radius:8px">
          <i class="bi bi-wallet2 me-1"></i>Accounts
        </a>
      </div>
    </div>
  </div>

  <!-- Stat cards -->
  <div class="col-lg-6 col-xl-7">
    <div class="row g-3 h-100">

      <div class="col-6">
        <div class="stat-card">
          <div class="stat-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-wallet2"></i></div>
          <div>
            <div class="stat-label">Active Accounts</div>
            <div class="stat-value"><?= count($accounts) ?></div>
            <div class="stat-sub">All currencies</div>
          </div>
        </div>
      </div>

      <div class="col-6">
        <div class="stat-card">
          <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-cash-coin"></i></div>
          <div>
            <div class="stat-label">Active Loans</div>
            <div class="stat-value"><?= $activeLoanCount ?? 0 ?></div>
            <div class="stat-sub"><a href="/loans" class="text-primary text-decoration-none" style="font-size:.72rem">View all →</a></div>
          </div>
        </div>
      </div>

      <div class="col-6">
        <div class="stat-card">
          <div class="stat-icon" style="background:#f0fdf4;color:#059669"><i class="bi bi-arrow-left-right"></i></div>
          <div>
            <div class="stat-label">Recent Transactions</div>
            <div class="stat-value"><?= count($transactions) ?></div>
            <div class="stat-sub">Last 10</div>
          </div>
        </div>
      </div>

      <div class="col-6">
        <div class="stat-card">
          <div class="stat-icon" style="background:#fdf4ff;color:#9333ea"><i class="bi bi-bell"></i></div>
          <div>
            <div class="stat-label">Notifications</div>
            <div class="stat-value"><?= $notifCount ?></div>
            <div class="stat-sub">
              <?php if ($notifCount > 0): ?>
              <a href="/notifications" class="text-danger text-decoration-none" style="font-size:.72rem">Read now →</a>
              <?php else: ?>
              All caught up
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>

<!-- ── Account Cards ─────────────────────────────────────────────────── -->
<?php if (!empty($accounts)): ?>
<div class="mb-4 fade-in-up">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="fw-700 mb-0" style="font-weight:700;font-size:.88rem;color:#374151;letter-spacing:.02em">MY ACCOUNTS</h6>
    <a href="/accounts" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem">View all</a>
  </div>
  <div class="row g-3">
    <?php foreach ($accounts as $i => $a): ?>
    <div class="col-md-6 col-xl-4">
      <div class="account-card <?= $grads[$i % count($grads)] ?>">
        <div class="d-flex align-items-start justify-content-between">
          <div class="acct-type"><?= $esc($a['account_type_name']??'Account') ?></div>
          <button class="acct-copy-btn" data-copy="<?= $esc($a['iban']) ?>" title="Copy IBAN">
            <i class="bi bi-clipboard"></i>
          </button>
        </div>
        <div class="acct-iban"><?= chunk_split($esc(str_replace(' ','',$a['iban'])), 4, ' ') ?></div>
        <div class="acct-balance"><?= $fmt((float)$a['balance'], $a['currency_code']) ?></div>
        <div class="acct-currency"><?= $esc($a['currency_code']) ?> · <?= ucfirst($a['status']) ?></div>
        <div class="acct-actions">
          <a href="/accounts/<?= $a['id'] ?>" class="acct-btn"><i class="bi bi-eye me-1"></i>Details</a>
          <a href="/transfer?from=<?= $a['id'] ?>" class="acct-btn"><i class="bi bi-send me-1"></i>Transfer</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ── Quick Actions ─────────────────────────────────────────────────── -->
<div class="portal-card mb-4 fade-in-up">
  <div class="portal-card-body">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h6 class="fw-bold mb-0" style="font-size:.88rem">Quick Actions</h6>
    </div>
    <div class="quick-actions pb-1">
      <?php
      $qas = [
        ['/transfer',        'bi-send',            'qa-blue',   'Transfer'],
        ['/transfer/sepa',   'bi-globe2',           'qa-violet', 'SEPA'],
        ['/standing-orders', 'bi-repeat',           'qa-green',  'Standing Order'],
        ['/loans',           'bi-cash-coin',        'qa-amber',  'Apply Loan'],
        ['/cards',           'bi-credit-card',      'qa-teal',   'Cards'],
        ['/beneficiaries',   'bi-person-plus',      'qa-indigo', 'Beneficiaries'],
        ['/support',         'bi-headset',          'qa-rose',   'Support'],
        ['/kyc',             'bi-shield-check',     'qa-green',  'KYC'],
      ];
      foreach ($qas as [$url, $icon, $color, $label]): ?>
      <a href="<?= $url ?>" class="quick-action">
        <div class="quick-action-icon <?= $color ?>">
          <i class="bi <?= $icon ?>"></i>
        </div>
        <div class="quick-action-label"><?= $label ?></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ── Recent Transactions ─────────────────────────────────────────── -->
<div class="portal-card fade-in-up">
  <div class="portal-card-header">
    <span><i class="bi bi-list-ul me-2"></i>Recent Transactions</span>
    <a href="/transactions" class="btn btn-sm btn-outline-primary" style="font-size:.78rem">View all</a>
  </div>

  <?php if (empty($transactions)): ?>
  <div class="text-center text-muted py-5">
    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-30"></i>
    <p class="mb-0 small">No transactions yet. Make your first transfer!</p>
    <a href="/transfer" class="btn btn-primary btn-sm mt-3"><i class="bi bi-send me-1"></i>Send Money</a>
  </div>
  <?php else: ?>
  <div class="txn-feed">
    <?php
    $prevDate = null;
    foreach ($transactions as $t):
      $txnDate   = substr($t['created_at']??'',0,10);
      $isDebit   = !empty($t['from_user_id']) && $t['from_user_id'] === $userId;
      $amount    = (float)$t['amount'];
      $statusCls = match($t['status']??'') {
        'completed','settled' => 'completed',
        'pending','processing','under_review' => 'pending',
        'failed','cancelled' => 'failed',
        'reversed' => 'reversed',
        default => 'pending',
      };
      $typeLabel = $t['description']??ucwords(str_replace('_',' ',$t['transaction_type']??''));
      if ($txnDate !== $prevDate):
        $prevDate = $txnDate;
        $dateLabel = $txnDate === date('Y-m-d') ? 'Today'
          : ($txnDate === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday'
          : date('d M Y', strtotime($txnDate)));
    ?>
    <div class="txn-date-header px-4"><?= htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <a href="/transactions/<?= $t['id'] ?>" class="txn-row">
      <div class="txn-icon-wrap <?= $isDebit ? 'debit' : 'credit' ?>">
        <i class="bi bi-<?= $isDebit ? 'arrow-up-right' : 'arrow-down-left' ?>"></i>
      </div>
      <div class="txn-body">
        <div class="txn-title"><?= $esc($typeLabel) ?></div>
        <div class="txn-meta">
          <span class="txn-status-dot <?= $statusCls ?>"></span>
          <?= ucwords(str_replace('_',' ',$t['status']??'')) ?>
          · <?= $esc(substr($t['created_at']??'',11,5)) ?>
          <?php if (!empty($t['to_iban']) && !$isDebit): ?>
          · <span class="font-mono"><?= '····'.$esc(substr($t['to_iban'],-4)) ?></span>
          <?php elseif (!empty($t['from_iban']) && $isDebit): ?>
           · <span class="font-mono"><?= !empty($t['to_iban']) ? '→ ····'.$esc(substr($t['to_iban'],-4)) : '' ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="text-end">
        <div class="txn-amount <?= $isDebit ? 'debit' : 'credit' ?>">
          <?= $isDebit ? '−' : '+' ?><?= $fmt($amount, $t['currency_code']??'EUR') ?>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

</div><!-- /.fade-in-up -->

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
