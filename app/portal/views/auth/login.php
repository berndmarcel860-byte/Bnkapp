<?php ob_start(); ?>

<h4 class="fw-bold mb-1">Welcome back!</h4>
<p class="text-muted small mb-4">Sign in to your BnkApp account.</p>

<?php if (\BnkPortal\Core\Session::hasFlash('error')): ?>
<?php foreach (\BnkPortal\Core\Session::getFlash('error') as $msg): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($msg, ENT_QUOTES,'UTF-8') ?></div>
<?php endforeach; endif; ?>

<form method="POST" action="/login" novalidate>
    <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
    <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control" autocomplete="email" required autofocus>
        </div>
    </div>
    <div class="mb-4">
        <label class="form-label fw-semibold">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control" autocomplete="current-password" required>
            <button type="button" class="btn btn-outline-secondary"
                    onclick="var i=this.previousElementSibling;i.type=i.type==='password'?'text':'password'">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>
    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
    </div>
    <p class="text-center text-muted small mb-0">
        No account? <a href="/register" class="text-primary">Create one for free</a>
    </p>
</form>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/auth.php';
