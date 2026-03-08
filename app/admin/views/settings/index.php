<?php
/**
 * View: settings/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="mb-4">
    <h1 class="page-title mb-0">Settings</h1>
    <p class="text-muted small">System configuration — roles, permissions, account types, countries.</p>
</div>

<!-- Nav tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-account-types">Account Types</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-roles">Roles</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-permissions">Permissions</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-countries">SEPA Countries</a></li>
</ul>

<div class="tab-content">
    <!-- Account Types -->
    <div class="tab-pane fade show active" id="tab-account-types">
        <div class="card table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Account Types</span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountTypeModal">
                    <i class="bi bi-plus-circle me-1"></i>Add
                </button>
            </div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>#</th><th>Name</th><th>Min Balance</th><th>Max Balance</th><th>Interest Rate</th><th>Active</th></tr></thead>
                    <tbody>
                    <?php foreach ($accountTypes as $at): ?>
                    <tr>
                        <td><?= $at['id'] ?></td>
                        <td class="fw-semibold"><?= FormatHelper::e($at['name']) ?></td>
                        <td><?= FormatHelper::money((float)$at['min_balance']) ?></td>
                        <td><?= $at['max_balance'] ? FormatHelper::money((float)$at['max_balance']) : '—' ?></td>
                        <td><?= number_format((float)$at['interest_rate'],4) ?>%</td>
                        <td><?= $at['is_active'] ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-secondary">No</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Roles -->
    <div class="tab-pane fade" id="tab-roles">
        <div class="card table-card">
            <div class="card-header fw-semibold">Roles</div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>#</th><th>Name</th><th>Description</th></tr></thead>
                    <tbody>
                    <?php foreach ($roles as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td class="fw-semibold"><?= FormatHelper::e($r['name']) ?></td>
                        <td class="text-muted small"><?= FormatHelper::e($r['description']??'') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Permissions -->
    <div class="tab-pane fade" id="tab-permissions">
        <div class="card table-card">
            <div class="card-header fw-semibold">Permissions</div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>#</th><th>Name</th><th>Description</th></tr></thead>
                    <tbody>
                    <?php foreach ($permissions as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td class="fw-semibold text-mono small"><?= FormatHelper::e($p['name']) ?></td>
                        <td class="text-muted small"><?= FormatHelper::e($p['description']??'') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SEPA Countries -->
    <div class="tab-pane fade" id="tab-countries">
        <div class="card table-card">
            <div class="card-header fw-semibold">SEPA Countries</div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>Code</th><th>Name</th><th>Currency</th><th>SEPA</th></tr></thead>
                    <tbody>
                    <?php foreach ($countries as $c): ?>
                    <tr>
                        <td class="fw-bold text-mono"><?= $c['iso_code'] ?></td>
                        <td><?= FormatHelper::e($c['name']) ?></td>
                        <td><?= $c['currency_code'] ?></td>
                        <td><i class="bi bi-check-circle-fill text-success"></i></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Account Type Modal -->
<div class="modal fade" id="addAccountTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/settings/account-types" data-ajax="true" data-reload="true">
                <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Add Account Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Code</label>
                        <input type="text" name="code" class="form-control text-uppercase" maxlength="20" placeholder="e.g. CHK" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Min Balance</label>
                        <input type="number" name="min_balance" step="0.01" class="form-control" value="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Max Balance</label>
                        <input type="number" name="max_balance" step="0.01" class="form-control" placeholder="No limit">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Interest Rate (%)</label>
                        <input type="number" name="interest_rate" step="0.0001" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
