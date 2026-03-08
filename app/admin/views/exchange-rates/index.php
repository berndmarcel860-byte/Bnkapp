<?php
/**
 * View: exchange-rates/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Exchange Rates</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRateModal">
        <i class="bi bi-plus-circle me-1"></i>Add Rate
    </button>
</div>

<div class="card table-card">
    <div class="card-header">
        <input type="search" class="form-control form-control-sm" style="max-width:280px"
               placeholder="Quick filter…" data-table-filter="rate-table">
    </div>
    <div class="table-responsive">
        <table class="table admin-table" id="rate-table">
            <thead><tr><th>#</th><th>Base</th><th>Target</th><th>Rate</th><th>Source</th><th>Effective At</th></tr></thead>
            <tbody>
            <?php if (empty($rates)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No exchange rates configured.</td></tr>
            <?php else: ?>
                <?php foreach ($rates as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td class="fw-bold text-primary"><?= $r['base_currency'] ?></td>
                    <td class="fw-bold"><?= $r['target_currency'] ?></td>
                    <td><?= number_format((float)$r['rate'], 6) ?></td>
                    <td><?= FormatHelper::e($r['source']??'manual') ?></td>
                    <td><?= FormatHelper::dateTime($r['effective_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Rate Modal -->
<div class="modal fade" id="addRateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/exchange-rates" data-ajax="true" data-reload="true">
                <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Add Exchange Rate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-4">
                        <label class="form-label fw-semibold">Base</label>
                        <input type="text" name="base_currency" class="form-control text-uppercase" maxlength="3" value="EUR" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Target</label>
                        <input type="text" name="target_currency" class="form-control text-uppercase" maxlength="3" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Rate</label>
                        <input type="number" name="rate" step="0.000001" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Effective At</label>
                        <input type="datetime-local" name="effective_at" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Source</label>
                        <input type="text" name="source" class="form-control" value="manual">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Rate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
