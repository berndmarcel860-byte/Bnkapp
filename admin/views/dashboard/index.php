<?php
/**
 * View: dashboard/index.php
 */
use BnkApp\Helpers\FormatHelper;

ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="page-title mb-0">Dashboard</h1>
    <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= date('l, j F Y') ?></span>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['label'=>'Total Customers',       'value'=> number_format($stats['total_users']),       'icon'=>'people-fill',         'color'=>'primary'],
        ['label'=>'Active Accounts',       'value'=> number_format($stats['active_accounts']),   'icon'=>'wallet2',             'color'=>'success'],
        ['label'=>'Pending KYC',           'value'=> number_format($stats['pending_kyc']),       'icon'=>'shield-exclamation',  'color'=>'warning'],
        ['label'=>'Transactions Today',    'value'=> number_format($stats['transactions_today']), 'icon'=>'arrow-left-right',   'color'=>'info'],
        ['label'=>"Today's Volume",        'value'=> FormatHelper::money($stats['volume_today']), 'icon'=>'graph-up-arrow',     'color'=>'success'],
        ['label'=>'Pending Loans',         'value'=> number_format($stats['pending_loans']),     'icon'=>'cash-coin',           'color'=>'warning'],
        ['label'=>'Open Support Tickets',  'value'=> number_format($stats['open_support_tickets']),'icon'=>'headset',           'color'=>'danger'],
    ];
    foreach ($kpis as $kpi): ?>
    <div class="col-6 col-md-4 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-<?= $kpi['color'] ?> bg-opacity-10 text-<?= $kpi['color'] ?>">
                    <i class="bi bi-<?= $kpi['icon'] ?>"></i>
                </div>
                <div>
                    <div class="stat-label"><?= $kpi['label'] ?></div>
                    <div class="stat-value text-<?= $kpi['color'] ?>"><?= $kpi['value'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Recent Transactions -->
<div class="card table-card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Recent Transactions</h6>
        <a href="/transactions" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="recent-txns-table" aria-label="Recent transactions">
            <thead>
                <tr>
                    <th>#ID</th><th>Type</th><th>From</th><th>To</th>
                    <th>Amount</th><th>Status</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recentTransactions)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No transactions yet.</td></tr>
            <?php else: ?>
                <?php foreach ($recentTransactions as $txn): ?>
                <tr>
                    <td><a href="/transactions/<?= $txn['id'] ?>" class="fw-semibold">#<?= $txn['id'] ?></a></td>
                    <td><span class="badge text-bg-secondary"><?= FormatHelper::titleCase($txn['transaction_type']) ?></span></td>
                    <td class="text-mono small"><?= FormatHelper::e($txn['from_iban'] ?? '—') ?></td>
                    <td class="text-mono small"><?= FormatHelper::e($txn['to_iban']   ?? '—') ?></td>
                    <td class="fw-semibold"><?= FormatHelper::money((float)$txn['amount'], $txn['currency_code']) ?></td>
                    <td><span class="badge text-bg-<?= FormatHelper::statusBadge($txn['status']) ?>"><?= FormatHelper::e($txn['status']) ?></span></td>
                    <td class="text-muted small"><?= FormatHelper::dateTime($txn['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
