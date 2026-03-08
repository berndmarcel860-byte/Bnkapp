<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/transfer" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0">SEPA Credit Transfer</h4>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="/transfer/sepa">
            <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">From Account</label>
                    <select name="from_account_id" class="form-select" required>
                        <?php foreach ($accounts as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= $esc($a['iban']) ?> (<?= $fmt((float)$a['balance'],$a['currency_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($beneficiaries)): ?>
                <div class="col-12">
                    <label class="form-label fw-semibold">Quick Select Beneficiary</label>
                    <select class="form-select" id="benSelect">
                        <option value="">-- Select a saved beneficiary --</option>
                        <?php foreach ($beneficiaries as $b): ?>
                        <option value="<?= $esc($b['iban']) ?>" data-name="<?= $esc($b['account_holder_name']) ?>">
                            <?= $esc($b['account_holder_name']) ?> — <?= $esc($b['iban']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Creditor IBAN</label>
                    <input type="text" name="creditor_iban" id="creditorIban" class="form-control font-monospace" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Currency</label>
                    <select name="currency_code" class="form-select">
                        <option value="EUR">EUR</option>
                        <option value="GBP">GBP</option>
                        <option value="USD">USD</option>
                        <option value="CHF">CHF</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Creditor Name</label>
                    <input type="text" name="creditor_name" id="creditorName" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">€</span>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Remittance Information</label>
                    <input type="text" name="remittance_information" class="form-control" placeholder="e.g. Invoice #1234" maxlength="140">
                </div>
                <div class="col-12 d-grid">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-send me-1"></i>Submit SEPA Transfer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
