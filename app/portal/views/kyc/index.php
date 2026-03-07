<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
ob_start(); ?>

<h4 class="fw-bold mb-2">Identity Verification (KYC)</h4>

<?php
$statusColors = ['pending'=>'warning','in_review'=>'info','approved'=>'success','rejected'=>'danger'];
$sc = $statusColors[$userStatus['kyc_status']??'pending'] ?? 'secondary';
?>

<div class="alert alert-<?= $sc ?> d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-shield-<?= $userStatus['kyc_status']==='approved'?'check':'exclamation' ?> fs-4"></i>
    <div>
        <strong>KYC Status: <?= $esc(ucfirst($userStatus['kyc_status']??'pending')) ?></strong>
        <?php if ($userStatus['kyc_status']==='approved'): ?>
        <div class="small">Verified on <?= $esc(substr($userStatus['kyc_approved_at']??'',0,10)) ?></div>
        <?php elseif ($userStatus['kyc_status']==='pending'): ?>
        <div class="small">Please upload your identity documents to verify your account.</div>
        <?php elseif ($userStatus['kyc_status']==='in_review'): ?>
        <div class="small">Your documents are under review. This usually takes 1-3 business days.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Upload form -->
<?php if (($userStatus['kyc_status']??'') !== 'approved'): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-cloud-upload me-2 text-primary"></i>Upload Document</div>
    <div class="card-body">
        <form method="POST" action="/kyc/upload" enctype="multipart/form-data">
            <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Document Type</label>
                    <select name="document_type" class="form-select" required>
                        <option value="national_id">National ID</option>
                        <option value="passport">Passport</option>
                        <option value="driving_licence">Driving Licence</option>
                        <option value="residence_permit">Residence Permit</option>
                        <option value="utility_bill">Utility Bill (Proof of Address)</option>
                        <option value="bank_statement">Bank Statement</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Document Number <small class="text-muted">(optional)</small></label>
                    <input type="text" name="document_number" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Expiry Date <small class="text-muted">(optional)</small></label>
                    <input type="date" name="expiry_date" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">File <small class="text-muted">(JPG, PNG or PDF, max 10MB)</small></label>
                    <input type="file" name="document_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload me-1"></i>Upload Document</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Submitted documents -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-files me-2 text-primary"></i>Submitted Documents</div>
    <?php if (empty($docs)): ?>
    <div class="card-body text-center text-muted py-4">No documents uploaded yet.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Type</th><th>Expiry</th><th>Status</th><th>Submitted</th></tr></thead>
            <tbody>
            <?php foreach ($docs as $d): ?>
            <tr>
                <td><?= $esc(ucwords(str_replace('_',' ',$d['document_type']))) ?></td>
                <td><?= $esc(substr($d['expiry_date']??'—',0,10)) ?></td>
                <td><span class="badge text-bg-<?= $statusColors[$d['status']??'pending']??'secondary' ?>"><?= $esc($d['status']??'pending') ?></span>
                    <?php if (!empty($d['rejection_reason'])): ?>
                    <div class="small text-danger mt-1"><?= $esc($d['rejection_reason']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-muted small"><?= $esc(substr($d['created_at']??'',0,10)) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
