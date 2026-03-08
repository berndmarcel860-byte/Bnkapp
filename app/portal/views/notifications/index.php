<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">Notifications</h4>
    <?php if (!empty($notifications)): ?>
    <form method="POST" action="/notifications/read-all">
        <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
        <button class="btn btn-sm btn-outline-secondary">Mark All Read</button>
    </form>
    <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
<div class="card border-0 shadow-sm text-center py-5 text-muted">
    <i class="bi bi-bell fs-1 d-block mb-2 opacity-40"></i>No notifications yet.
</div>
<?php else: ?>
<div class="list-group shadow-sm border-0">
<?php foreach ($notifications as $n): ?>
<div class="list-group-item border-0 mb-1 <?= $n['is_read']?'':'bg-info bg-opacity-10' ?>" style="border-radius:.5rem!important">
    <div class="d-flex align-items-start gap-2">
        <div class="mt-1">
            <?php $iconMap = ['email'=>'envelope','sms'=>'chat-text','push'=>'phone','in_app'=>'bell']; ?>
            <i class="bi bi-<?= $iconMap[$n['channel']??'in_app']??'bell' ?> text-primary"></i>
        </div>
        <div class="flex-grow-1">
            <div class="fw-semibold small"><?= $esc($n['title']??'') ?></div>
            <div class="small text-muted"><?= $esc($n['message']??'') ?></div>
            <div class="small text-muted mt-1"><?= $esc(substr($n['created_at']??'',0,16)) ?></div>
        </div>
        <?php if (!$n['is_read']): ?>
        <span class="badge text-bg-primary">New</span>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
