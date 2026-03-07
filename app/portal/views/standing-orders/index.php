<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
$fmt = fn(float $v, string $cur='EUR') => (new \NumberFormatter('en_GB',\NumberFormatter::CURRENCY))->formatCurrency($v,$cur);
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">Standing Orders</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSOModal">
        <i class="bi bi-plus-circle me-1"></i>New Order
    </button>
</div>

<?php if (empty($orders)): ?>
<div class="card border-0 shadow-sm text-center py-5 text-muted">
    <i class="bi bi-repeat fs-1 d-block mb-2 opacity-40"></i>No standing orders set up yet.
</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($orders as $o): ?>
<div class="col-md-6">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="fw-bold"><?= $fmt((float)$o['amount'], $o['currency_code']) ?>
                    <span class="badge text-bg-secondary ms-1"><?= ucfirst($o['frequency']) ?></span>
                </div>
                <span class="badge text-bg-<?= match($o['status']){
                    'active'=>'success','paused'=>'warning','cancelled'=>'secondary',default=>'info'} ?>"><?= $o['status'] ?></span>
            </div>
            <div class="small text-muted">To: <span class="font-monospace"><?= $esc($o['to_iban']) ?></span></div>
            <div class="small text-muted">From: <span class="font-monospace"><?= $esc($o['from_iban']) ?></span></div>
            <div class="small text-muted">Next run: <?= $esc($o['next_execution_date']??'—') ?></div>
        </div>
        <?php if ($o['status']==='active'): ?>
        <div class="card-footer d-flex gap-2" style="background:#f8fafc">
            <form method="POST" action="/standing-orders/<?= $o['id'] ?>/pause" class="flex-fill">
                <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
                <input type="hidden" name="_method" value="PATCH">
                <button class="btn btn-warning btn-sm w-100">Pause</button>
            </form>
            <form method="POST" action="/standing-orders/<?= $o['id'] ?>/cancel" class="flex-fill">
                <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
                <input type="hidden" name="_method" value="PATCH">
                <button class="btn btn-danger btn-sm w-100" data-confirm="Cancel this standing order?">Cancel</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add modal -->
<div class="modal fade" id="addSOModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/standing-orders">
                <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header"><h5 class="modal-title">New Standing Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label fw-semibold">From Account</label>
                        <select name="from_account_id" class="form-select" required></select>
                    </div>
                    <div class="col-12"><label class="form-label fw-semibold">To IBAN</label><input type="text" name="to_iban" class="form-control font-monospace" required></div>
                    <div class="col-12"><label class="form-label fw-semibold">Beneficiary Name</label><input type="text" name="to_name" class="form-control"></div>
                    <div class="col-6"><label class="form-label fw-semibold">Amount (EUR)</label><input type="number" name="amount" step="0.01" class="form-control" required></div>
                    <div class="col-6"><label class="form-label fw-semibold">Frequency</label>
                        <select name="frequency" class="form-select" required>
                            <option value="weekly">Weekly</option>
                            <option value="bi_weekly">Bi-weekly</option>
                            <option value="monthly" selected>Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annually">Annually</option>
                        </select>
                    </div>
                    <div class="col-6"><label class="form-label fw-semibold">Start Date</label><input type="date" name="start_date" class="form-control" required></div>
                    <div class="col-6"><label class="form-label fw-semibold">End Date <small class="text-muted">(optional)</small></label><input type="date" name="end_date" class="form-control"></div>
                    <div class="col-12"><label class="form-label fw-semibold">Description</label><input type="text" name="description" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
