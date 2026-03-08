<?php
/**
 * View: accounts/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Bank Accounts</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#openAccountModal">
        <i class="bi bi-plus-circle me-1"></i>Open Account
    </button>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2 align-items-center">
        <form method="GET" action="/accounts" class="d-flex gap-2 flex-wrap">
            <select name="status" class="form-select form-select-sm" style="width:auto">
                <option value="">All Statuses</option>
                <?php foreach (['pending','active','inactive','frozen','closed'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="accounts-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="accounts-table">
            <thead><tr>
                <th>#</th><th>IBAN</th><th>Owner</th><th>Type</th><th>Currency</th><th>Balance</th><th>Status</th><th>Opened</th><th></th>
            </tr></thead>
            <tbody>
            <?php if (empty($accounts)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No accounts found.</td></tr>
            <?php else: ?>
                <?php foreach ($accounts as $a): ?>
                <tr>
                    <td><?= $a['id'] ?></td>
                    <td class="text-mono small"><?= FormatHelper::e($a['iban']) ?></td>
                    <td><?= FormatHelper::e($a['owner_name']) ?></td>
                    <td><?= FormatHelper::e($a['account_type_name']) ?></td>
                    <td><?= $a['currency_code'] ?></td>
                    <td class="fw-semibold"><?= FormatHelper::money((float)$a['balance'], $a['currency_code']) ?></td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($a['status']) ?>"><?= $a['status'] ?></span></td>
                    <td class="text-muted small"><?= FormatHelper::dateTime($a['opened_at']) ?></td>
                    <td><a href="/accounts/<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Open Account Modal -->
<div class="modal fade" id="openAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/accounts" data-ajax="true" data-reload="true">
                <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Open New Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">User ID</label>
                        <input type="number" name="user_id" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Account Type ID</label>
                        <input type="number" name="account_type_id" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Branch ID</label>
                        <input type="number" name="branch_id" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Currency</label>
                        <input type="text" name="currency_code" class="form-control text-uppercase" maxlength="3" value="EUR" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Country Code</label>
                        <input type="text" name="country_code" class="form-control text-uppercase" maxlength="2" placeholder="DE" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Bank Code</label>
                        <input type="text" name="bank_code" class="form-control" maxlength="20" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Open Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
