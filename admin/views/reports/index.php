<?php
/**
 * View: reports/index.php
 * Landing page with links to each report type.
 */
ob_start(); ?>

<div class="report-cards">
    <a href="/reports/transactions" class="report-card">
        <div class="report-card__icon" aria-hidden="true">💸</div>
        <div class="report-card__title">Transaction Report</div>
        <div class="report-card__desc">Volume, count and fees by type and currency for any date range.</div>
    </a>

    <a href="/reports/loans" class="report-card">
        <div class="report-card__icon" aria-hidden="true">📋</div>
        <div class="report-card__title">Loan Portfolio</div>
        <div class="report-card__desc">Outstanding balances, average rates, and status breakdown.</div>
    </a>

    <a href="/reports/audit" class="report-card">
        <div class="report-card__icon" aria-hidden="true">🔍</div>
        <div class="report-card__title">Audit Log</div>
        <div class="report-card__desc">Immutable record of all sensitive admin and system actions.</div>
    </a>
</div>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/main.php';
