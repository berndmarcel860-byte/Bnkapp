<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
ob_start(); ?>

<div class="page-header">
  <h4 class="fw-bold mb-0">Transfer Money</h4>
  <p class="text-muted small">Choose your transfer type</p>
</div>

<div class="row g-4">

  <!-- Internal Transfer -->
  <div class="col-lg-7">
    <div class="portal-card">
      <div class="portal-card-header">
        <span><i class="bi bi-arrow-left-right me-2"></i>Internal Transfer</span>
        <span class="badge" style="background:#eff6ff;color:#2563eb;font-size:.75rem">Instant</span>
      </div>
      <div class="portal-card-body">
        <form method="POST" action="/transfer">
          <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>

          <div class="mb-3">
            <label class="form-label">From Account</label>
            <select name="from_account_id" class="form-select" required>
              <?php foreach ($accounts as $a): ?>
              <option value="<?= $a['id'] ?>"
                      data-balance="<?= $esc((string)($a['available_balance']??$a['balance'])) ?>"
                      data-currency="<?= $esc($a['currency_code']) ?>"
                      <?= (($_GET['from']??'')==(string)$a['id'])?'selected':'' ?>>
                <?= $esc($a['iban']) ?> &nbsp;·&nbsp; <?= $fmt((float)($a['available_balance']??$a['balance']),$a['currency_code']) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text text-info" data-balance-hint style="display:none"></div>
          </div>

          <div class="mb-3">
            <label class="form-label">Recipient IBAN</label>
            <input type="text" name="to_iban" class="form-control font-mono"
                   placeholder="DE89 3704 0044 0532 0130 00"
                   maxlength="34" required>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-7">
              <label class="form-label">Amount</label>
              <div class="input-group">
                <span class="input-group-text fw-semibold">€</span>
                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
              </div>
            </div>
            <div class="col-5">
              <label class="form-label">Currency</label>
              <select name="currency_code" class="form-select">
                <option value="EUR" selected>EUR</option>
                <option value="USD">USD</option>
                <option value="GBP">GBP</option>
                <option value="CHF">CHF</option>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
            <input type="text" name="description" class="form-control" placeholder="e.g. Rent for March" maxlength="140">
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="bi bi-send me-2"></i>Send Transfer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Right column: SEPA + quick tips -->
  <div class="col-lg-5">

    <!-- SEPA card -->
    <div class="portal-card mb-3">
      <div class="portal-card-body text-center py-4">
        <div class="quick-action-icon qa-violet mx-auto mb-3" style="width:64px;height:64px;border-radius:18px;font-size:1.75rem">
          <i class="bi bi-globe2"></i>
        </div>
        <h6 class="fw-bold mb-1">SEPA Credit Transfer</h6>
        <p class="text-muted small mb-3">Send money to any bank in 36 European countries. Same-day processing.</p>
        <a href="/transfer/sepa" class="btn btn-primary w-100">
          <i class="bi bi-globe me-2"></i>Start SEPA Transfer
        </a>
      </div>
    </div>

    <!-- Tips -->
    <div class="portal-card">
      <div class="portal-card-header"><span><i class="bi bi-lightbulb me-2 text-warning"></i>Tips</span></div>
      <div class="portal-card-body">
        <ul class="list-unstyled mb-0" style="font-size:.82rem;color:#475569">
          <li class="d-flex gap-2 mb-2">
            <i class="bi bi-shield-check text-success mt-1"></i>
            Always double-check the recipient IBAN before confirming.
          </li>
          <li class="d-flex gap-2 mb-2">
            <i class="bi bi-clock text-primary mt-1"></i>
            Internal transfers are processed instantly.
          </li>
          <li class="d-flex gap-2">
            <i class="bi bi-info-circle text-info mt-1"></i>
            SEPA transfers may take 1 business day for external banks.
          </li>
        </ul>
      </div>
    </div>

  </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
