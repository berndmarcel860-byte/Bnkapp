<?php
/**
 * View: auth/login.php
 */
ob_start(); ?>

<?php if (!empty($errors['auth'])): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= htmlspecialchars($errors['auth'][0], ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form method="POST" action="/auth/login" novalidate>
    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>

    <div class="mb-3">
        <label class="form-label fw-semibold" for="email">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" id="email" name="email"
                class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="email" required autofocus>
            <?php if (!empty($errors['email'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['email'][0], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label fw-semibold" for="password">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" id="password" name="password"
                class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                autocomplete="current-password" required>
            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd(this)" tabindex="-1">
                <i class="bi bi-eye"></i>
            </button>
            <?php if (!empty($errors['password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['password'][0], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
        </button>
    </div>
</form>

<script>
function togglePwd(btn) {
    const inp = btn.closest('.input-group').querySelector('input[type="password"], input[type="text"]');
    const icon = btn.querySelector('i');
    if (inp.type === 'password') { inp.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else { inp.type = 'password'; icon.className = 'bi bi-eye'; }
}
</script>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/auth.php';
