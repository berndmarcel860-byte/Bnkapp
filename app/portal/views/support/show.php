<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/support" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0"><?= $esc($ticket['subject']) ?></h4>
    <span class="badge text-bg-<?= match($ticket['status']??'open'){
        'open'=>'danger','in_progress'=>'warning','resolved','closed'=>'success',default=>'secondary'} ?>"><?= $esc($ticket['status']??'') ?></span>
</div>

<!-- Messages -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body" style="max-height:480px;overflow-y:auto">
        <?php foreach ($messages as $m):
            $isMe = ($m['sender_user_id'] == \BnkPortal\Core\Auth::id());
        ?>
        <div class="d-flex mb-3 <?= $isMe?'justify-content-end':'' ?>">
            <div class="rounded-3 px-3 py-2 shadow-sm" style="max-width:70%;background:<?= $isMe?'#dbeafe':'#f1f5f9' ?>">
                <div class="small fw-semibold mb-1 <?= $isMe?'text-primary':'text-secondary' ?>">
                    <?= $isMe ? 'You' : $esc($m['sender_name']??'Support') ?>
                </div>
                <div><?= nl2br($esc($m['message']??'')) ?></div>
                <div class="small text-muted mt-1"><?= $esc(substr($m['created_at']??'',0,16)) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!in_array($ticket['status']??'open', ['resolved','closed'])): ?>
<form method="POST" action="/support/<?= $ticket['id'] ?>/reply" class="card border-0 shadow-sm">
    <div class="card-body">
        <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
        <textarea name="message" class="form-control mb-3" rows="3" placeholder="Write a reply…" required></textarea>
        <div class="text-end">
            <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send Reply</button>
        </div>
    </div>
</form>
<?php else: ?>
<div class="alert alert-secondary">This ticket is <?= $esc($ticket['status']??'closed') ?>. No further replies can be sent.</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
