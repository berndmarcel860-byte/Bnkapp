<?php
$esc    = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$fmt    = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
$userId = \BnkPortal\Core\Auth::id();
ob_start(); ?>

<div class="page-header d-flex align-items-center justify-content-between">
  <div>
    <h4 class="fw-bold mb-0">Transactions</h4>
    <p class="text-muted small"><?= number_format($total) ?> transaction<?= $total!==1?'s':'' ?> in total</p>
  </div>
  <a href="/transfer" class="btn btn-primary d-none d-md-flex align-items-center gap-2">
    <i class="bi bi-send"></i> New Transfer
  </a>
</div>

<div class="portal-card">
  <?php if (empty($transactions)): ?>
  <div class="text-center py-5 text-muted">
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
      $statusCls = match($t['status']??'') {
        'completed','settled' => 'completed',
        'pending','processing','under_review' => 'pending',
        'failed','cancelled' => 'failed',
        'reversed' => 'reversed',
        default => 'pending',
      };
      if ($txnDate !== $prevDate):
        $prevDate = $txnDate;
        $dateLabel = $txnDate === date('Y-m-d') ? 'Today'
          : ($txnDate === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday'
          : date('d F Y', strtotime($txnDate)));
    ?>
    <div class="txn-date-header px-4"><?= $esc($dateLabel) ?></div>
    <?php endif; ?>
    <a href="/transactions/<?= $t['id'] ?>" class="txn-row">
      <div class="txn-icon-wrap <?= $isDebit ? 'debit' : 'credit' ?>">
        <i class="bi bi-<?= $isDebit ? 'arrow-up-right' : 'arrow-down-left' ?>"></i>
      </div>
      <div class="txn-body">
        <div class="txn-title">
          <?= $esc($t['description']??ucwords(str_replace('_',' ',$t['transaction_type']??''))) ?>
        </div>
        <div class="txn-meta">
          <span class="txn-status-dot <?= $statusCls ?>"></span>
          <?= ucwords(str_replace('_',' ',$t['status']??'')) ?>
          · <?= $esc(substr($t['created_at']??'',11,5)) ?>
          <?php if (!$isDebit && !empty($t['from_iban'])): ?>
          · <span class="font-mono small">from ····<?= $esc(substr($t['from_iban'],-4)) ?></span>
          <?php elseif ($isDebit && !empty($t['to_iban'])): ?>
          · <span class="font-mono small">to ····<?= $esc(substr($t['to_iban'],-4)) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="text-end">
        <div class="txn-amount <?= $isDebit ? 'debit' : 'credit' ?>">
          <?= $isDebit ? '−' : '+' ?><?= $fmt((float)$t['amount'], $t['currency_code']??'EUR') ?>
        </div>
        <?php if (!empty($t['fee_amount']) && (float)$t['fee_amount'] > 0): ?>
        <div style="font-size:.68rem;color:#94a3b8">fee <?= $fmt((float)$t['fee_amount'], $t['currency_code']??'EUR') ?></div>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if ($lastPage > 1): ?>
  <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center">
    <span class="text-muted small">Page <?= $page ?> of <?= $lastPage ?></span>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="/transactions?page=<?= $page-1 ?>"><i class="bi bi-chevron-left"></i></a></li>
        <?php endif; ?>
        <?php for ($p=max(1,$page-2);$p<=min($lastPage,$page+2);$p++): ?>
        <li class="page-item <?= $p===$page?'active':'' ?>">
          <a class="page-link" href="/transactions?page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($page < $lastPage): ?>
        <li class="page-item"><a class="page-link" href="/transactions?page=<?= $page+1 ?>"><i class="bi bi-chevron-right"></i></a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
