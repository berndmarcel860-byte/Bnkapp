<?php
/**
 * View: cards/index.php
 */
use BnkApp\Helpers\FormatHelper;

ob_start(); ?>

<div class="toolbar">
    <form method="GET" action="/cards" class="search-form">
        <select name="status" class="form__select form__select--sm">
            <option value="">All Statuses</option>
            <?php foreach (['inactive','active','blocked','expired','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status'] ?? '') === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn--primary btn--sm">Filter</button>
    </form>
    <a href="/cards/create" class="btn btn--success btn--sm">+ Issue Card</a>
</div>

<div class="table-wrapper">
    <table class="table" aria-label="Cards">
        <thead>
            <tr>
                <th>ID</th><th>PAN (last 4)</th><th>Cardholder</th>
                <th>Type</th><th>Network</th><th>Account IBAN</th>
                <th>Expires</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($cards)): ?>
            <tr><td colspan="9" class="table__empty">No cards found.</td></tr>
        <?php else: ?>
            <?php foreach ($cards as $c): ?>
            <tr>
                <td><a href="/cards/<?= $c['id'] ?>">#<?= $c['id'] ?></a></td>
                <td class="mono">**** <?= FormatHelper::e($c['card_number_last4']) ?></td>
                <td><?= FormatHelper::e($c['cardholder_name']) ?></td>
                <td><?= ucfirst($c['card_type']) ?></td>
                <td><?= ucfirst($c['card_network']) ?></td>
                <td class="mono"><?= FormatHelper::e($c['account_iban']) ?></td>
                <td><?= FormatHelper::date($c['expires_at']) ?></td>
                <td>
                    <span class="badge badge--<?= FormatHelper::statusBadge($c['status']) ?>">
                        <?= FormatHelper::e($c['status']) ?>
                    </span>
                </td>
                <td><a href="/cards/<?= $c['id'] ?>" class="btn btn--secondary btn--xs">View</a></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
