<?php
$esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES,'UTF-8');
ob_start(); ?>

<h4 class="fw-bold mb-4">My Profile</h4>

<div class="row g-4">
    <!-- Profile form -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person me-2 text-primary"></i>Personal Information</div>
            <div class="card-body">
                <form method="POST" action="/profile">
                    <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= $esc($user['first_name']??'') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= $esc($user['last_name']??'') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Email <small class="text-muted">(read-only)</small></label>
                            <input type="email" class="form-control bg-light" value="<?= $esc($user['email']??'') ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?= $esc($user['phone']??'') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <input type="text" name="address_line1" class="form-control" value="<?= $esc($user['address_line1']??'') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">City</label>
                            <input type="text" name="city" class="form-control" value="<?= $esc($user['city']??'') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control" value="<?= $esc($user['postal_code']??'') ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-floppy me-1"></i>Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Password change -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-lock me-2 text-warning"></i>Change Password</div>
            <div class="card-body">
                <form method="POST" action="/profile/password">
                    <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="new_password" class="form-control" minlength="12" required>
                        <div class="form-text">Minimum 12 characters.</div>
                    </div>
                    <button type="submit" class="btn btn-warning w-100"><i class="bi bi-shield-lock me-1"></i>Update Password</button>
                </form>
            </div>
        </div>

        <!-- Account info -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-info-circle me-2 text-info"></i>Account Info</div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-6 text-muted">KYC Status</dt>
                    <dd class="col-6"><span class="badge text-bg-<?= match($user['kyc_status']??'pending'){
                        'approved'=>'success','rejected'=>'danger','in_review'=>'info',default=>'warning'} ?>"><?= $esc($user['kyc_status']??'pending') ?></span></dd>
                    <dt class="col-6 text-muted">2FA</dt>
                    <dd class="col-6"><?= $user['two_factor_enabled'] ? '<span class="text-success">Enabled</span>' : '<span class="text-muted">Disabled</span>' ?></dd>
                    <dt class="col-6 text-muted">Member Since</dt>
                    <dd class="col-6"><?= $esc(substr($user['created_at']??'',0,10)) ?></dd>
                </dl>
                <?php if (($user['kyc_status']??'') !== 'approved'): ?>
                <div class="mt-3">
                    <a href="/kyc" class="btn btn-sm btn-outline-warning w-100"><i class="bi bi-shield-check me-1"></i>Complete KYC Verification</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
