<?php
/**
 * View: loans/index.php
 */
use BnkApp\Helpers\FormatHelper;

ob_start(); ?>

<div class="toolbar">
    <form method="GET" action="/loans" class="search-form">
        <select name="status" class="form__select form__select--sm">
            <option value="">All Statuses</option>
            <?php foreach (['applied','in_review','approved','disbursed','active','defaulted','paid_off','rejected','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status'] ?? '') === $s ? 'selected' : '' ?>>
                    <?= FormatHelper::titleCase($s) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn--primary btn--sm">Filter</button>
    </form>
</div>

<div class="table-wrapper">
    <table class="table" aria-label="Loans">
        <thead>
            <tr>
                <th>ID</th><th>Type</th><th>Principal</th><th>Outstanding</th>
                <th>Rate</th><th>Term</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($loans)): ?>
            <tr><td colspan="8" class="table__empty">No loans found.</td></tr>
        <?php else: ?>
            <?php foreach ($loans as $l): ?>
            <tr>
                <td><a href="/loans/<?= $l['id'] ?>">#<?= $l['id'] ?></a></td>
                <td><?= FormatHelper::titleCase($l['loan_type']) ?></td>
                <td><?= FormatHelper::money((float)$l['principal_amount']) ?></td>
                <td><?= FormatHelper::money((float)$l['outstanding_balance']) ?></td>
                <td><?= number_format((float)$l['interest_rate'] * 100, 2) ?>%</td>
                <td><?= $l['term_months'] ?> months</td>
                <td>
                    <span class="badge badge--<?= FormatHelper::statusBadge($l['status']) ?>">
                        <?= FormatHelper::e($l['status']) ?>
                    </span>
                </td>
                <td><a href="/loans/<?= $l['id'] ?>" class="btn btn--secondary btn--xs">View</a></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
