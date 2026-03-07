<?php
/**
 * View: reports/index.php
 */
ob_start(); ?>

<div class="mb-4">
    <h1 class="page-title mb-0">Reports</h1>
    <p class="text-muted small">Select a report category to view detailed analytics.</p>
</div>

<div class="row g-3">
    <?php
    $cards = [
        ['/reports/transactions','bi-graph-up-arrow','text-primary','bg-primary','Transaction Report','Volume, count and fees by type and currency for any date range.'],
        ['/reports/loans','bi-cash-coin','text-success','bg-success','Loan Portfolio','Outstanding balances, average rates, and status breakdown.'],
        ['/reports/audit','bi-journal-text','text-warning','bg-warning','Audit Log','Immutable record of all sensitive admin and system actions.'],
    ];
    foreach ($cards as [$url,$icon,$textColor,$bgColor,$title,$desc]): ?>
    <div class="col-md-4">
        <a href="<?= $url ?>" class="report-card text-decoration-none d-block">
            <div class="report-card-icon <?= $textColor ?>"><i class="bi <?= $icon ?> fs-2"></i></div>
            <div class="report-card-title fw-bold mb-1"><?= $title ?></div>
            <div class="report-card-desc text-muted small"><?= $desc ?></div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
