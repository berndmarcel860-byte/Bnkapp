<?php
/**
 * View: support/show.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="/support" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Ticket #<?= $ticket['id'] ?></h1>
    <span class="badge text-bg-<?= FormatHelper::statusBadge($ticket['status']) ?> ms-1"><?= $ticket['status'] ?></span>
    <?php if (!in_array($ticket['status'], ['closed','resolved'])): ?>
    <form method="POST" action="/support/<?= $ticket['id'] ?>/close" class="ms-auto" data-ajax="true" data-reload="true">
        <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
        <button class="btn btn-sm btn-outline-danger" data-confirm="Close this ticket?">
            <i class="bi bi-x-circle me-1"></i>Close Ticket
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="row g-3">
    <!-- Messages -->
    <div class="col-lg-8">
        <div class="card table-card mb-3">
            <div class="card-header fw-semibold">
                <?= FormatHelper::e($ticket['subject']) ?>
                <span class="text-muted small ms-2">— <?= FormatHelper::e($ticket['customer_name']) ?></span>
            </div>
            <div class="card-body p-0" style="max-height:480px;overflow-y:auto">
                <?php foreach ($messages as $m): ?>
                <div class="p-3 border-bottom d-flex gap-3">
                    <div class="sidebar-avatar flex-shrink-0" style="width:34px;height:34px;background:linear-gradient(135deg,<?= $m['sender_id']==$ticket['user_id']?'#0d6efd':'#6366f1' ?>,#818cf8)">
                        <?= strtoupper(substr($m['sender_name']??'?',0,1)) ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="small fw-semibold mb-1">
                            <?= FormatHelper::e($m['sender_name']??'Unknown') ?>
                            <span class="text-muted fw-normal ms-1"><?= FormatHelper::dateTime($m['created_at']) ?></span>
                        </div>
                        <div><?= nl2br(FormatHelper::e($m['message']??'')) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($messages)): ?>
                <div class="p-4 text-center text-muted">No messages yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reply form -->
        <?php if (!in_array($ticket['status'], ['closed','resolved'])): ?>
        <div class="card table-card">
            <div class="card-header fw-semibold">Reply to Customer</div>
            <div class="card-body">
                <form method="POST" action="/support/<?= $ticket['id'] ?>/reply" data-ajax="true" data-reload="true">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                    <textarea name="message" class="form-control mb-3" rows="4" placeholder="Type your reply…" required></textarea>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send Reply</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Ticket info -->
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header fw-semibold">Ticket Details</div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Customer</dt>
                    <dd class="col-7"><a href="/users/<?= $ticket['user_id'] ?>"><?= FormatHelper::e($ticket['customer_name']) ?></a></dd>
                    <dt class="col-5 text-muted">Category</dt>
                    <dd class="col-7"><?= FormatHelper::titleCase($ticket['category']??'') ?></dd>
                    <dt class="col-5 text-muted">Priority</dt>
                    <dd class="col-7"><span class="badge text-bg-<?= match($ticket['priority']??'normal'){
                        'urgent'=>'danger','high'=>'warning',default=>'secondary'} ?>"><?= $ticket['priority']??'normal' ?></span></dd>
                    <dt class="col-5 text-muted">Created</dt>
                    <dd class="col-7"><?= FormatHelper::dateTime($ticket['created_at']) ?></dd>
                    <dt class="col-5 text-muted">Updated</dt>
                    <dd class="col-7"><?= FormatHelper::dateTime($ticket['updated_at']??'') ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
