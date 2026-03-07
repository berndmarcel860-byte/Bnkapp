<?php
/**
 * View: notifications/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Notifications</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sendNotifModal">
        <i class="bi bi-send me-1"></i>Send Notification
    </button>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2">
        <form method="GET" action="/notifications" class="d-flex gap-2 flex-wrap">
            <select name="type" class="form-select form-select-sm" style="width:auto">
                <option value="">All Types</option>
                <?php foreach (['email','sms','push','in_app'] as $t): ?>
                <option value="<?= $t ?>" <?= ($filter['type']??'')===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="notif-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="notif-table">
            <thead><tr><th>#</th><th>User</th><th>Type</th><th>Title</th><th>Read</th><th>Sent</th></tr></thead>
            <tbody>
            <?php if (empty($notifications)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No notifications found.</td></tr>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                <tr>
                    <td><?= $n['id'] ?></td>
                    <td><?= FormatHelper::e($n['user_name']) ?></td>
                    <td><span class="badge text-bg-info"><?= $n['notification_type'] ?></span></td>
                    <td><?= FormatHelper::e(FormatHelper::truncate($n['title'],60)) ?></td>
                    <td><?= $n['is_read'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-circle text-muted"></i>' ?></td>
                    <td class="text-muted small"><?= FormatHelper::dateTime($n['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Send Notification Modal -->
<div class="modal fade" id="sendNotifModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/notifications/send" data-ajax="true" data-reload="true">
                <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Send Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">User ID <small class="text-muted">(leave blank to broadcast)</small></label>
                        <input type="number" name="user_id" class="form-control" placeholder="Leave blank = all users">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Type</label>
                        <select name="notification_type" class="form-select" required>
                            <?php foreach (['in_app','email','sms','push'] as $t): ?>
                            <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Title</label>
                        <input type="text" name="title" class="form-control" maxlength="255" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Message</label>
                        <textarea name="message" class="form-control" rows="4" maxlength="1000" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
