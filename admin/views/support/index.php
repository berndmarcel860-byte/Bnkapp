<?php
/**
 * View: support/index.php
 */
use BnkApp\Helpers\FormatHelper;

ob_start(); ?>

<div class="toolbar">
    <form method="GET" action="/support" class="search-form">
        <select name="status" class="form__select form__select--sm">
            <?php foreach (['open','in_progress','waiting_on_customer','resolved','closed'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status'] ?? 'open') === $s ? 'selected' : '' ?>>
                    <?= FormatHelper::titleCase($s) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn--primary btn--sm">Filter</button>
    </form>
</div>

<div class="table-wrapper">
    <table class="table" aria-label="Support Tickets">
        <thead>
            <tr>
                <th>ID</th><th>Subject</th><th>Customer</th>
                <th>Category</th><th>Priority</th><th>Status</th><th>Created</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($tickets)): ?>
            <tr><td colspan="7" class="table__empty">No tickets found.</td></tr>
        <?php else: ?>
            <?php foreach ($tickets as $t): ?>
            <tr>
                <td><a href="/support/<?= $t['id'] ?>">#<?= $t['id'] ?></a></td>
                <td><?= FormatHelper::e(FormatHelper::truncate($t['subject'], 60)) ?></td>
                <td><?= FormatHelper::e($t['customer_name']) ?></td>
                <td><?= FormatHelper::titleCase($t['category']) ?></td>
                <td>
                    <span class="badge badge--<?= match($t['priority']) { 'urgent' => 'danger', 'high' => 'warning', default => 'info' } ?>">
                        <?= FormatHelper::e($t['priority']) ?>
                    </span>
                </td>
                <td>
                    <span class="badge badge--<?= FormatHelper::statusBadge($t['status']) ?>">
                        <?= FormatHelper::e($t['status']) ?>
                    </span>
                </td>
                <td><?= FormatHelper::dateTime($t['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
