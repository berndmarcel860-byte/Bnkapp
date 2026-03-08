<?php
/**
 * View: users/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Users</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus me-1"></i>New User
    </button>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2 align-items-center">
        <form method="GET" action="/users" class="d-flex gap-2 flex-wrap" role="search">
            <div class="input-group input-group-sm" style="max-width:300px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" name="search" class="form-control" placeholder="Name or email…"
                       value="<?= FormatHelper::e($search ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <?php if (!empty($search)): ?>
                <a href="/users" class="btn btn-sm btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
        <input type="search" class="form-control form-control-sm ms-auto" style="max-width:220px"
               placeholder="Quick filter…" data-table-filter="users-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="users-table">
            <thead><tr>
                <th>#</th><th>Name</th><th>Email</th><th>Role</th>
                <th>KYC</th><th>Active</th><th>Registered</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td class="fw-semibold"><?= FormatHelper::e($u['first_name'] . ' ' . $u['last_name']) ?></td>
                    <td><?= FormatHelper::e($u['email']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= FormatHelper::e($u['role_name'] ?? '') ?></span></td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($u['kyc_status']) ?>"><?= FormatHelper::e($u['kyc_status']) ?></span></td>
                    <td><?= $u['is_active'] ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-danger">No</span>' ?></td>
                    <td class="text-muted small"><?= FormatHelper::dateTime($u['created_at']) ?></td>
                    <td>
                        <a href="/users/<?= $u['id'] ?>" class="btn btn-xs btn-outline-primary btn-sm py-0 px-2">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($pagination) && $pagination['last_page'] > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing page <?= $pagination['page'] ?> of <?= $pagination['last_page'] ?> (<?= number_format($pagination['total']) ?> total)</small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($p = max(1,$pagination['page']-2); $p <= min($pagination['last_page'],$pagination['page']+2); $p++): ?>
            <li class="page-item <?= $p===$pagination['page']?'active':'' ?>">
                <a class="page-link" href="/users?page=<?= $p ?>&search=<?= urlencode($search??'') ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<!-- New User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/users" data-ajax="true" data-reload="true">
                <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">First Name</label>
                        <input type="text" name="first_name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Last Name</label>
                        <input type="text" name="last_name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" maxlength="255" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="tel" name="phone" class="form-control" maxlength="30">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Password <small class="text-muted">(min 12 chars)</small></label>
                        <input type="password" name="password" class="form-control" minlength="12" required autocomplete="new-password">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Role ID</label>
                        <input type="number" name="role_id" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">National ID</label>
                        <input type="text" name="national_id" class="form-control" maxlength="50">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
