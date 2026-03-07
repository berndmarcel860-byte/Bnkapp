<?php
/**
 * View: branches/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="page-title mb-0">Branches</h1>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBranchModal">
        <i class="bi bi-plus-circle me-1"></i>Add Branch
    </button>
</div>

<div class="row g-3">
    <?php if (empty($branches)): ?>
    <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">No branches configured yet.</div></div></div>
    <?php else: ?>
    <?php foreach ($branches as $b): ?>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-1"><a href="/branches/<?= $b['id'] ?>"><?= FormatHelper::e($b['name']) ?></a></h6>
                        <div class="text-mono small text-muted"><?= FormatHelper::e($b['sort_code']) ?></div>
                    </div>
                    <?= $b['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' ?>
                </div>
                <hr class="my-2">
                <div class="small text-muted">
                    <div><i class="bi bi-geo-alt me-1"></i><?= FormatHelper::e($b['city']??'') . ', ' . FormatHelper::e($b['country_name']??'') ?></div>
                    <div><i class="bi bi-credit-card me-1"></i><?= number_format((int)$b['account_count']) ?> accounts</div>
                    <?php if (!empty($b['phone'])): ?>
                    <div><i class="bi bi-telephone me-1"></i><?= FormatHelper::e($b['phone']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Branch Modal -->
<div class="modal fade" id="addBranchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/branches" data-ajax="true" data-reload="true">
                <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Add Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Sort Code</label>
                        <input type="text" name="sort_code" class="form-control" maxlength="20" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Address</label>
                        <input type="text" name="address_line1" class="form-control" placeholder="Street address">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">City</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Post Code</label>
                        <input type="text" name="postal_code" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Country ID</label>
                        <input type="number" name="country_id" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
