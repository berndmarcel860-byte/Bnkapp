<?php
/**
 * View: accounts/index.php
 * Paginated list of bank accounts with status filter.
 */
use BnkApp\Helpers\FormatHelper;

ob_start(); ?>

<div class="toolbar">
    <form method="GET" action="/accounts" class="search-form">
        <select name="status" class="form__select form__select--sm">
            <option value="">All Statuses</option>
            <?php foreach (['pending','active','inactive','frozen','closed'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status'] ?? '') === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn--primary btn--sm">Filter</button>
    </form>
    <a href="/accounts/create" class="btn btn--success btn--sm">+ Open Account</a>
</div>

<div class="table-wrapper">
    <table class="table" aria-label="Bank Accounts">
        <thead>
            <tr>
                <th>ID</th><th>IBAN</th><th>Owner</th><th>Type</th>
                <th>Currency</th><th>Balance</th><th>Status</th><th>Opened</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($accounts)): ?>
            <tr><td colspan="9" class="table__empty">No accounts found.</td></tr>
        <?php else: ?>
            <?php foreach ($accounts as $a): ?>
            <tr>
                <td><a href="/accounts/<?= $a['id'] ?>">#<?= $a['id'] ?></a></td>
                <td class="mono"><?= FormatHelper::e($a['iban']) ?></td>
                <td><?= FormatHelper::e($a['owner_name']) ?></td>
                <td><?= FormatHelper::e($a['account_type_name']) ?></td>
                <td><?= FormatHelper::e($a['currency_code']) ?></td>
                <td><?= FormatHelper::money((float)$a['balance'], $a['currency_code']) ?></td>
                <td>
                    <span class="badge badge--<?= FormatHelper::statusBadge($a['status']) ?>">
                        <?= FormatHelper::e($a['status']) ?>
                    </span>
                </td>
                <td><?= FormatHelper::dateTime($a['opened_at']) ?></td>
                <td><a href="/accounts/<?= $a['id'] ?>" class="btn btn--secondary btn--xs">View</a></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
