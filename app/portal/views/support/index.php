<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">Support</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newTicketModal">
        <i class="bi bi-plus-circle me-1"></i>New Ticket
    </button>
</div>

<?php if (empty($tickets)): ?>
<div class="card border-0 shadow-sm text-center py-5 text-muted">
    <i class="bi bi-headset fs-1 d-block mb-2 opacity-40"></i>No support tickets yet.
</div>
<?php else: ?>
<div class="list-group shadow-sm border-0">
<?php foreach ($tickets as $t): ?>
<a href="/support/<?= $t['id'] ?>" class="list-group-item list-group-item-action border-0 mb-2" style="border-radius:.5rem!important">
    <div class="d-flex justify-content-between align-items-start">
        <div class="fw-semibold"><?= $esc($t['subject']) ?></div>
        <span class="badge text-bg-<?= match($t['status']??'open'){
            'open'=>'danger','in_progress'=>'warning','resolved','closed'=>'success',default=>'secondary'} ?> ms-2"><?= $esc($t['status']??'') ?></span>
    </div>
    <div class="small text-muted mt-1"><?= $esc(ucfirst($t['category']??'general')) ?> · <?= $esc(substr($t['created_at']??'',0,10)) ?></div>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- New Ticket Modal -->
<div class="modal fade" id="newTicketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/support">
                <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header"><h5 class="modal-title">New Support Ticket</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label fw-semibold">Subject</label><input type="text" name="subject" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Category</label>
                        <select name="category" class="form-select">
                            <option value="general">General</option>
                            <option value="account">Account</option>
                            <option value="payment">Payment</option>
                            <option value="card">Card</option>
                            <option value="loan">Loan</option>
                            <option value="technical">Technical</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label fw-semibold">Message</label><textarea name="message" class="form-control" rows="5" required></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
