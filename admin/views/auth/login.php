<?php
/**
 * View: auth/login.php
 * Displayed inside the auth layout by AuthController::showLogin().
 */
ob_start(); ?>

<?php if (!empty($errors['auth'])): ?>
    <div class="alert alert--error" role="alert">
        <?= htmlspecialchars($errors['auth'][0], ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form method="POST" action="/auth/login" class="form" novalidate>
    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>

    <div class="form__group">
        <label class="form__label" for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            class="form__input <?= !empty($errors['email']) ? 'form__input--error' : '' ?>"
            value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="email"
            required
            autofocus
        >
        <?php if (!empty($errors['email'])): ?>
            <span class="form__error"><?= htmlspecialchars($errors['email'][0], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>

    <div class="form__group">
        <label class="form__label" for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            class="form__input <?= !empty($errors['password']) ? 'form__input--error' : '' ?>"
            autocomplete="current-password"
            required
        >
        <?php if (!empty($errors['password'])): ?>
            <span class="form__error"><?= htmlspecialchars($errors['password'][0], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn--primary btn--full-width">
        Sign In
    </button>
</form>

<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/auth.php';
