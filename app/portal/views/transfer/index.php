<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
ob_start(); ?>

<h4 class="fw-bold mb-4">Transfer Money</h4>

<div class="row g-4">
    <!-- Internal transfer -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Internal Transfer</div>
            <div class="card-body">
                <form method="POST" action="/transfer">
                    <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">From Account</label>
                        <select name="from_account_id" class="form-select" required>
                            <?php foreach ($accounts as $a): ?>
                            <option value="<?= $a['id'] ?>"
                                    data-balance="<?= htmlspecialchars((string)$a['balance'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-currency="<?= htmlspecialchars($a['currency_code'], ENT_QUOTES, 'UTF-8') ?>"
                                    <?= (($_GET['from']??'')==$a['id'])?'selected':'' ?>>
                                <?= $esc($a['iban']) ?> (<?= $fmt((float)$a['balance'],$a['currency_code']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">To IBAN</label>
                        <input type="text" name="to_iban" class="form-control font-monospace" placeholder="DE89 3704 0044 0532 0130 00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount (EUR)</label>
                        <div class="input-group">
                            <span class="input-group-text">€</span>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="form-text text-muted" data-balance-hint></div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Rent payment">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send Transfer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SEPA link -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-globe me-2 text-info"></i>SEPA Credit Transfer</div>
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center">
                <i class="bi bi-globe fs-1 text-info mb-3"></i>
                <p class="text-muted">Send money to any bank account in the SEPA zone (36 countries).</p>
                <a href="/transfer/sepa" class="btn btn-info text-white"><i class="bi bi-send me-1"></i>SEPA Transfer</a>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
