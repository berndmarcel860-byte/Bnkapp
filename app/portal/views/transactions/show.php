<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="/transactions" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
  <div>
    <h4 class="fw-bold mb-0">Transaction Detail</h4>
    <span class="text-muted small font-mono"><?= $esc($transaction['transaction_ref']??'') ?></span>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <!-- Amount card -->
    <div class="portal-card mb-3">
      <div class="portal-card-body text-center py-4">
        <?php
        $isDebit = !empty($transaction['from_user_id']) && $transaction['from_user_id'] === \BnkPortal\Core\Auth::id();
        $statusClass = match($transaction['status']??'') {
          'completed','settled' => 'success',
          'pending','processing','under_review' => 'warning',
          'failed','cancelled' => 'danger',
          'reversed' => 'secondary',
          default => 'secondary',
        };
        ?>
        <div class="fs-1 mb-2" style="color:<?= $isDebit?'#dc2626':'#059669' ?>">
          <?= $isDebit ? '−' : '+' ?><?= $fmt((float)$transaction['amount'], $transaction['currency_code']) ?>
        </div>
        <span class="badge text-bg-<?= $statusClass ?> fs-6 mb-2">
          <?= ucwords(str_replace('_',' ',$transaction['status']??'')) ?>
        </span>
        <div class="text-muted small mt-1"><?= ucwords(str_replace('_',' ',$transaction['transaction_type']??'')) ?></div>
      </div>
    </div>

    <!-- Details -->
    <div class="portal-card">
      <div class="portal-card-header"><span><i class="bi bi-info-circle me-2"></i>Details</span></div>
      <div class="portal-card-body p-0">
        <table class="table table-sm mb-0 portal-table">
          <tbody>
            <?php
            $rows = [
              ['Reference',    $transaction['transaction_ref']??'—', true],
              ['From IBAN',    $transaction['from_iban']??'—', true],
              ['To IBAN',      $transaction['to_iban']??'—', true],
            ];
            if (!empty($transaction['creditor_name']))
              $rows[] = ['Creditor', $transaction['creditor_name'], false];
            $rows = array_merge($rows, [
              ['Fee',          $fmt((float)($transaction['fee_amount']??0), $transaction['currency_code']), false],
              ['Description',  $transaction['description']??'—', false],
              ['Booking Date', $transaction['booking_date']??'—', false],
              ['Date',         substr($transaction['created_at']??'',0,16), false],
            ]);
            foreach ($rows as [$label, $value, $mono]):
            ?>
            <tr>
              <td class="text-muted fw-semibold" style="width:38%"><?= $esc($label) ?></td>
              <td class="<?= $mono ? 'font-mono small' : 'small' ?>"><?= $esc((string)$value) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
