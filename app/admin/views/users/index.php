<?php
/**
 * View: users/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Users</h1>
    <a href="/users/create" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>New User</a>
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

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
