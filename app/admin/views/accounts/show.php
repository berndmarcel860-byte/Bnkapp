<?php
/**
 * View: accounts/show.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/accounts" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h1 class="page-title mb-0">Account</h1>
    <span class="text-mono fw-bold fs-6"><?= FormatHelper::e($account['iban']) ?></span>
    <span class="badge text-bg-<?= FormatHelper::statusBadge($account['status']) ?>"><?= $account['status'] ?></span>
</div>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="bi bi-info-circle me-2 text-primary"></i>Account Details</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-6 text-muted">IBAN</dt>        <dd class="col-6 text-mono"><?= FormatHelper::e($account['iban']) ?></dd>
                    <dt class="col-6 text-muted">BIC</dt>         <dd class="col-6 text-mono"><?= FormatHelper::e($account['bic'] ?? '—') ?></dd>
                    <dt class="col-6 text-muted">Account No.</dt> <dd class="col-6 text-mono"><?= FormatHelper::e($account['account_number'] ?? '') ?></dd>
                    <dt class="col-6 text-muted">Type</dt>        <dd class="col-6"><?= FormatHelper::e($account['account_type_name']) ?></dd>
                    <dt class="col-6 text-muted">Currency</dt>    <dd class="col-6"><?= $account['currency_code'] ?></dd>
                    <dt class="col-6 text-muted">Balance</dt>     <dd class="col-6 fw-bold text-success"><?= FormatHelper::money((float)$account['balance'], $account['currency_code']) ?></dd>
                    <dt class="col-6 text-muted">Available</dt>   <dd class="col-6"><?= FormatHelper::money((float)($account['available_balance']??0), $account['currency_code']) ?></dd>
                    <dt class="col-6 text-muted">Branch</dt>      <dd class="col-6"><?= FormatHelper::e($account['branch_name'] ?? '—') ?></dd>
                    <dt class="col-6 text-muted">Owner</dt>       <dd class="col-6"><a href="/users/<?= $account['user_id'] ?>"><?= FormatHelper::e($account['owner_name']) ?></a></dd>
                    <dt class="col-6 text-muted">Email</dt>       <dd class="col-6 text-break"><?= FormatHelper::e($account['owner_email']) ?></dd>
                    <dt class="col-6 text-muted">Opened</dt>      <dd class="col-6"><?= FormatHelper::dateTime($account['opened_at'] ?? '') ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="bi bi-gear me-2 text-primary"></i>Actions</div>
            <div class="card-body d-flex flex-column gap-2">
                <form method="POST" action="/accounts/<?= $account['id'] ?>/freeze" data-ajax="true" data-reload="true">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="PATCH">
                    <button class="btn btn-warning w-100">
                        <i class="bi bi-snow me-1"></i><?= $account['status']==='frozen'?'Unfreeze':'Freeze' ?>
                    </button>
                </form>
                <form method="POST" action="/accounts/<?= $account['id'] ?>/close" data-ajax="true" data-redirect="/accounts">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="PATCH">
                    <button class="btn btn-danger w-100" data-confirm="Close this account permanently?"
                            <?= $account['status']==='closed'?'disabled':'' ?>>
                        <i class="bi bi-x-circle me-1"></i>Close Account
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card table-card">
            <div class="card-header fw-semibold"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Recent Transactions</div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>#</th><th>Type</th><th>From</th><th>To</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No transactions.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><a href="/transactions/<?= $t['id'] ?>">#<?= $t['id'] ?></a></td>
                            <td><span class="badge text-bg-secondary"><?= FormatHelper::titleCase($t['transaction_type']) ?></span></td>
                            <td class="text-mono small"><?= FormatHelper::e($t['from_iban']??'—') ?></td>
                            <td class="text-mono small"><?= FormatHelper::e($t['to_iban']??'—') ?></td>
                            <td class="fw-semibold"><?= FormatHelper::money((float)$t['amount'], $t['currency_code']) ?></td>
                            <td><span class="badge text-bg-<?= FormatHelper::statusBadge($t['status']) ?>"><?= $t['status'] ?></span></td>
                            <td class="text-muted small"><?= FormatHelper::dateTime($t['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
