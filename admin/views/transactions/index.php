<?php
/**
 * View: transactions/index.php
 * Paginated transaction ledger with type, status, and date filters.
 */
use BnkApp\Helpers\FormatHelper;

ob_start(); ?>

<div class="toolbar">
    <form method="GET" action="/transactions" class="search-form">
        <select name="type" class="form__select form__select--sm">
            <option value="">All Types</option>
            <?php foreach (['deposit','withdrawal','internal_transfer','sepa_credit_transfer','sepa_instant_transfer','sepa_direct_debit','fee','interest','loan_disbursement','loan_repayment','refund','reversal'] as $t): ?>
                <option value="<?= $t ?>" <?= ($filter['type'] ?? '') === $t ? 'selected' : '' ?>>
                    <?= FormatHelper::titleCase($t) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="form__select form__select--sm">
            <option value="">All Statuses</option>
            <?php foreach (['pending','processing','completed','failed','cancelled','reversed'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filter['status'] ?? '') === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="from" class="form__input form__input--sm" value="<?= FormatHelper::e($filter['from'] ?? '') ?>">
        <input type="date" name="to"   class="form__input form__input--sm" value="<?= FormatHelper::e($filter['to']   ?? '') ?>">
        <button type="submit" class="btn btn--primary btn--sm">Filter</button>
    </form>
</div>

<div class="table-wrapper">
    <table class="table" aria-label="Transactions">
        <thead>
            <tr>
                <th>ID</th><th>Type</th><th>From</th><th>To</th>
                <th>Amount</th><th>Fee</th><th>Status</th><th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($transactions)): ?>
            <tr><td colspan="8" class="table__empty">No transactions found.</td></tr>
        <?php else: ?>
            <?php foreach ($transactions as $t): ?>
            <tr>
                <td><a href="/transactions/<?= $t['id'] ?>">#<?= $t['id'] ?></a></td>
                <td><?= FormatHelper::titleCase($t['transaction_type']) ?></td>
                <td class="mono"><?= FormatHelper::e($t['from_iban'] ?? '—') ?></td>
                <td class="mono"><?= FormatHelper::e($t['to_iban']   ?? '—') ?></td>
                <td><?= FormatHelper::money((float)$t['amount'], $t['currency_code']) ?></td>
                <td><?= FormatHelper::money((float)$t['fee_amount'], $t['currency_code']) ?></td>
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
