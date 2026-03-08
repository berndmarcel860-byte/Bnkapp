<?php
/**
 * View: fee-schedules/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Fee Schedules</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFeeModal">
        <i class="bi bi-plus-circle me-1"></i>Add Fee
    </button>
</div>

<div class="card table-card">
    <div class="card-header">
        <input type="search" class="form-control form-control-sm" style="max-width:280px"
               placeholder="Quick filter…" data-table-filter="fee-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="fee-table">
            <thead><tr><th>#</th><th>Name</th><th>Account Type</th><th>Fee Type</th><th>Currency</th><th>Fixed Fee</th><th>% Fee</th><th>Min Fee</th><th>Max Fee</th><th>Active</th></tr></thead>
            <tbody>
            <?php if (empty($fees)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No fee schedules configured.</td></tr>
            <?php else: ?>
                <?php foreach ($fees as $f): ?>
                <tr>
                    <td><?= $f['id'] ?></td>
                    <td><?= FormatHelper::e($f['name']??'') ?></td>
                    <td><?= FormatHelper::e($f['account_type_name']??'All') ?></td>
                    <td><?= FormatHelper::titleCase($f['fee_type']) ?></td>
                    <td><?= $f['currency_code']??'*' ?></td>
                    <td><?= FormatHelper::money((float)($f['fixed_amount']??0)) ?></td>
                    <td><?= number_format((float)($f['percentage']??0),4) ?>%</td>
                    <td><?= FormatHelper::money((float)($f['min_fee']??0)) ?></td>
                    <td><?= ($f['max_fee'] ?? null) ? FormatHelper::money((float)$f['max_fee']) : '—' ?></td>
                    <td><?= $f['is_active'] ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-secondary">No</span>' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Fee Modal -->
<div class="modal fade" id="addFeeModal" tabindex="-1" aria-labelledby="addFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/fee-schedules" data-ajax="true" data-reload="true">
                <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="addFeeModalLabel">Add Fee Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. SEPA Transfer Fee" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Fee Type</label>
                        <select name="fee_type" class="form-select" required>
                            <option value="">Select type…</option>
                            <?php foreach (['transaction','maintenance','overdraft','card_issuance','wire_transfer','atm_withdrawal','fx_conversion','late_payment'] as $ft): ?>
                            <option value="<?= $ft ?>"><?= FormatHelper::titleCase($ft) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Fixed Fee (EUR)</label>
                        <input type="number" name="fixed_amount" step="0.01" class="form-control" value="0" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Percentage Fee</label>
                        <input type="number" name="percentage" step="0.0001" class="form-control" value="0" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Min Fee (EUR)</label>
                        <input type="number" name="min_fee" step="0.01" class="form-control" value="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Max Fee (EUR)</label>
                        <input type="number" name="max_fee" step="0.01" class="form-control" placeholder="No limit">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Currency</label>
                        <input type="text" name="currency_code" class="form-control" value="EUR" maxlength="3" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Effective From</label>
                        <input type="date" name="effective_from" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Fee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
