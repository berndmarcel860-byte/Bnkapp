<?php
/**
 * View: dashboard/index.php
 * Admin dashboard with summary statistics and recent transactions.
 */
use BnkApp\Helpers\FormatHelper;

ob_start(); ?>

<!-- Summary stat cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card__label">Total Customers</div>
        <div class="stat-card__value"><?= number_format($stats['total_users']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Active Accounts</div>
        <div class="stat-card__value"><?= number_format($stats['active_accounts']) ?></div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__label">Pending KYC</div>
        <div class="stat-card__value"><?= number_format($stats['pending_kyc']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Transactions Today</div>
        <div class="stat-card__value"><?= number_format($stats['transactions_today']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Volume Today</div>
        <div class="stat-card__value"><?= FormatHelper::money($stats['volume_today']) ?></div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__label">Pending Loans</div>
        <div class="stat-card__value"><?= number_format($stats['pending_loans']) ?></div>
    </div>
    <div class="stat-card stat-card--info">
        <div class="stat-card__label">Open Support Tickets</div>
        <div class="stat-card__value"><?= number_format($stats['open_support_tickets']) ?></div>
    </div>
</div>

<!-- Recent transactions -->
<section class="section">
    <h2 class="section__title">Recent Transactions</h2>

    <div class="table-wrapper">
        <table class="table" aria-label="Recent transactions">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Type</th>
                    <th scope="col">From IBAN</th>
                    <th scope="col">To IBAN</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Status</th>
                    <th scope="col">Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recentTransactions)): ?>
                <tr><td colspan="7" class="table__empty">No transactions yet.</td></tr>
            <?php else: ?>
                <?php foreach ($recentTransactions as $txn): ?>
                <tr>
                    <td>
                        <a href="/transactions/<?= $txn['id'] ?>">#<?= $txn['id'] ?></a>
                    </td>
                    <td><?= FormatHelper::titleCase($txn['transaction_type']) ?></td>
                    <td><?= FormatHelper::e($txn['from_iban'] ?? '—') ?></td>
                    <td><?= FormatHelper::e($txn['to_iban']   ?? '—') ?></td>
                    <td><?= FormatHelper::money((float)$txn['amount'], $txn['currency_code']) ?></td>
                    <td>
                        <span class="badge badge--<?= FormatHelper::statusBadge($txn['status']) ?>">
                            <?= FormatHelper::e($txn['status']) ?>
                        </span>
                    </td>
                    <td><?= FormatHelper::dateTime($txn['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="/transactions" class="btn btn--secondary btn--sm">View all transactions →</a>
</section>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
