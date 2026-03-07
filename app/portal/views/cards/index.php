<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
ob_start(); ?>

<h4 class="fw-bold mb-4">My Cards</h4>

<?php if (empty($cards)): ?>
<div class="card border-0 shadow-sm text-center py-5 text-muted">
    <i class="bi bi-credit-card fs-1 d-block mb-2 opacity-40"></i>No cards yet. Contact support to order a card.
</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($cards as $c): ?>
<div class="col-md-6 col-xl-4">
    <div class="card border-0 shadow-sm" style="border-radius:.75rem;overflow:hidden">
        <!-- Card visual -->
        <div class="p-4 text-white" style="background:linear-gradient(135deg,#1e40af,#6366f1);min-height:160px;position:relative">
            <div class="d-flex justify-content-between mb-4">
                <div class="fw-bold" style="letter-spacing:.15em">BNKAPP</div>
                <div class="fw-semibold text-uppercase small"><?= $esc($c['card_network']??'') ?></div>
            </div>
            <div class="fw-bold fs-5 mb-2" style="letter-spacing:.2em">**** **** **** <?= $esc($c['card_number_last4']) ?></div>
            <div class="d-flex justify-content-between small opacity-75">
                <span><?= $esc($c['cardholder_name']) ?></span>
                <span>Exp: <?= $esc(substr($c['expires_at']??'',0,7)) ?></span>
            </div>
            <div class="position-absolute" style="top:1rem;right:1rem;opacity:.3;font-size:4rem"><i class="bi bi-credit-card-2-front"></i></div>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <div class="small text-muted"><?= ucfirst($c['card_type']??'') ?> Card</div>
                    <span class="badge text-bg-<?= match($c['status']??''){
                        'active'=>'success','blocked'=>'danger','expired'=>'secondary',default=>'warning'} ?>"><?= $c['status'] ?></span>
                </div>
                <form method="POST" action="/cards/<?= $c['id'] ?>/freeze">
                    <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="PATCH">
                    <button class="btn btn-sm btn-<?= $c['status']==='active'?'outline-danger':'outline-success' ?>"
                            data-confirm="<?= $c['status']==='active'?'Freeze this card?':'Unfreeze this card?' ?>">
                        <i class="bi bi-<?= $c['status']==='active'?'snow':'play' ?> me-1"></i>
                        <?= $c['status']==='active'?'Freeze':'Unfreeze' ?>
                    </button>
                </form>
            </div>
            <div class="small text-muted">Account: <span class="font-monospace"><?= $esc($c['account_iban']) ?></span></div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
