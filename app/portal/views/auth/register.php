<?php ob_start(); ?>

<h4 class="fw-bold mb-1">Create your account</h4>
<p class="text-muted small mb-4">Join BnkApp in minutes.</p>

<?php if (\BnkPortal\Core\Session::hasFlash('error')): ?>
<?php foreach (\BnkPortal\Core\Session::getFlash('error') as $msg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($msg, ENT_QUOTES,'UTF-8') ?></div>
<?php endforeach; endif; ?>

<form method="POST" action="/register" novalidate>
    <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
    <div class="row g-3 mb-3">
        <div class="col-6">
            <label class="form-label fw-semibold">First Name</label>
            <input type="text" name="first_name" class="form-control" required>
        </div>
        <div class="col-6">
            <label class="form-label fw-semibold">Last Name</label>
            <input type="text" name="last_name" class="form-control" required>
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" autocomplete="email" required>
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control" required>
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Password <small class="text-muted">(min. 12 characters)</small></label>
            <input type="password" name="password" class="form-control" minlength="12" autocomplete="new-password" required>
        </div>
    </div>
    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
    </div>
    <p class="text-center text-muted small mb-0">
        Already have an account? <a href="/login" class="text-primary">Sign in</a>
    </p>
</form>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/auth.php';
