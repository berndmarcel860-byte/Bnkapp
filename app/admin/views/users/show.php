<?php
/**
 * View: users/show.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/users" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0"><?= FormatHelper::e($user['first_name'] . ' ' . $user['last_name']) ?></h1>
    <span class="badge text-bg-<?= FormatHelper::statusBadge($user['kyc_status']) ?>"><?= FormatHelper::e($user['kyc_status']) ?></span>
    <?= $user['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-danger">Inactive</span>' ?>
</div>

<div class="row g-4">
    <!-- Profile -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="bi bi-person me-2 text-primary"></i>Profile</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">ID</dt>          <dd class="col-7"><?= $user['id'] ?></dd>
                    <dt class="col-5 text-muted">Email</dt>       <dd class="col-7 text-break"><?= FormatHelper::e($user['email']) ?></dd>
                    <dt class="col-5 text-muted">Phone</dt>       <dd class="col-7"><?= FormatHelper::e($user['phone'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Date of Birth</dt><dd class="col-7"><?= FormatHelper::date($user['date_of_birth'] ?? '') ?></dd>
                    <dt class="col-5 text-muted">National ID</dt> <dd class="col-7"><?= FormatHelper::e($user['national_id'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Role</dt>        <dd class="col-7"><span class="badge text-bg-secondary"><?= FormatHelper::e($user['role_name'] ?? '') ?></span></dd>
                    <dt class="col-5 text-muted">Registered</dt>  <dd class="col-7"><?= FormatHelper::dateTime($user['created_at'] ?? '') ?></dd>
                    <dt class="col-5 text-muted">Last Login</dt>  <dd class="col-7"><?= FormatHelper::dateTime($user['last_login_at'] ?? '') ?></dd>
                    <dt class="col-5 text-muted">2FA</dt>         <dd class="col-7"><?= $user['two_factor_enabled'] ? '<span class="text-success">Enabled</span>' : '<span class="text-muted">Disabled</span>' ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Address -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="bi bi-geo-alt me-2 text-primary"></i>Address</div>
            <div class="card-body">
                <address class="small mb-0">
                    <?= FormatHelper::e($user['address_line1'] ?? '—') ?><br>
                    <?php if (!empty($user['address_line2'])): ?><?= FormatHelper::e($user['address_line2']) ?><br><?php endif; ?>
                    <?= FormatHelper::e(($user['city'] ?? '') . ' ' . ($user['postal_code'] ?? '')) ?>
                </address>
            </div>
        </div>
    </div>

    <!-- KYC Actions -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="bi bi-shield-check me-2 text-primary"></i>KYC Actions</div>
            <div class="card-body d-flex flex-column gap-2">
                <form method="POST" action="/users/<?= $user['id'] ?>/kyc" data-ajax="true" data-reload="true">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="kyc_status" value="approved">
                    <button class="btn btn-success w-100" <?= $user['kyc_status']==='approved'?'disabled':'' ?>>
                        <i class="bi bi-check-circle me-1"></i>Approve KYC
                    </button>
                </form>
                <form method="POST" action="/users/<?= $user['id'] ?>/kyc" data-ajax="true" data-reload="true">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="kyc_status" value="rejected">
                    <button class="btn btn-danger w-100" <?= $user['kyc_status']==='rejected'?'disabled':'' ?>
                            data-confirm="Reject this user's KYC?">
                        <i class="bi bi-x-circle me-1"></i>Reject KYC
                    </button>
                </form>
                <hr>
                <form method="POST" action="/users/<?= $user['id'] ?>" data-ajax="true" data-reload="true">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button class="btn btn-outline-danger w-100" data-confirm="Deactivate this user?">
                        <i class="bi bi-person-x me-1"></i>Deactivate User
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Accounts -->
    <div class="col-12">
        <div class="card table-card">
            <div class="card-header fw-semibold"><i class="bi bi-wallet2 me-2 text-primary"></i>Bank Accounts</div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>ID</th><th>IBAN</th><th>Type</th><th>Currency</th><th>Balance</th><th>Status</th><th>Opened</th></tr></thead>
                    <tbody>
                    <?php if (empty($accounts)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No accounts.</td></tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $a): ?>
                        <tr>
                            <td><a href="/accounts/<?= $a['id'] ?>">#<?= $a['id'] ?></a></td>
                            <td class="text-mono small"><?= FormatHelper::e($a['iban']) ?></td>
                            <td><?= FormatHelper::e($a['account_type']) ?></td>
                            <td><?= FormatHelper::e($a['currency_code']) ?></td>
                            <td class="fw-semibold"><?= FormatHelper::money((float)$a['balance'], $a['currency_code']) ?></td>
                            <td><span class="badge text-bg-<?= FormatHelper::statusBadge($a['status']) ?>"><?= $a['status'] ?></span></td>
                            <td class="text-muted small"><?= FormatHelper::dateTime($a['opened_at'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- KYC Documents -->
    <div class="col-12">
        <div class="card table-card">
            <div class="card-header fw-semibold"><i class="bi bi-files me-2 text-primary"></i>KYC Documents</div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>ID</th><th>Type</th><th>Status</th><th>Expiry</th><th>Submitted</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if (empty($kycDocs)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No documents.</td></tr>
                    <?php else: ?>
                        <?php foreach ($kycDocs as $d): ?>
                        <tr>
                            <td><?= $d['id'] ?></td>
                            <td><?= FormatHelper::titleCase($d['document_type']) ?></td>
                            <td><span class="badge text-bg-<?= FormatHelper::statusBadge($d['status']) ?>"><?= $d['status'] ?></span></td>
                            <td><?= FormatHelper::date($d['expiry_date'] ?? '') ?></td>
                            <td class="text-muted small"><?= FormatHelper::dateTime($d['created_at']) ?></td>
                            <td><a href="/kyc/<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary py-0">Review</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
