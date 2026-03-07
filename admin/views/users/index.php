<?php
/**
 * View: users/index.php
 * Paginated list of users with search and KYC filter.
 */
use BnkApp\Helpers\FormatHelper;

ob_start(); ?>

<div class="toolbar">
    <form method="GET" action="/users" class="search-form" role="search">
        <input type="search" name="search" class="form__input form__input--sm"
               placeholder="Search name or email…"
               value="<?= FormatHelper::e($search ?? '') ?>">
        <button type="submit" class="btn btn--primary btn--sm">Search</button>
    </form>
    <a href="/users/create" class="btn btn--success btn--sm">+ New User</a>
</div>

<div class="table-wrapper">
    <table class="table" aria-label="Users">
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Role</th>
                <th>KYC</th><th>Active</th><th>Created</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="8" class="table__empty">No users found.</td></tr>
        <?php else: ?>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><a href="/users/<?= $u['id'] ?>">#<?= $u['id'] ?></a></td>
                <td><?= FormatHelper::e($u['first_name'] . ' ' . $u['last_name']) ?></td>
                <td><?= FormatHelper::e($u['email']) ?></td>
                <td><?= FormatHelper::e($u['role_name'] ?? '') ?></td>
                <td>
                    <span class="badge badge--<?= FormatHelper::statusBadge($u['kyc_status']) ?>">
                        <?= FormatHelper::e($u['kyc_status']) ?>
                    </span>
                </td>
                <td><?= $u['is_active'] ? '✅' : '❌' ?></td>
                <td><?= FormatHelper::dateTime($u['created_at']) ?></td>
                <td>
                    <a href="/users/<?= $u['id'] ?>" class="btn btn--secondary btn--xs">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($pagination) && $pagination['last_page'] > 1): ?>
<nav class="pagination" aria-label="User pagination">
    <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
        <a href="/users?page=<?= $p ?>&search=<?= urlencode($search ?? '') ?>"
           class="pagination__link <?= $p === $pagination['page'] ? 'is-active' : '' ?>">
            <?= $p ?>
        </a>
    <?php endfor; ?>
</nav>
<?php endif; ?>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
