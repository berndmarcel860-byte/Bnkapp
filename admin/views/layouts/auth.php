<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'BnkApp Admin', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="auth-layout">

<div class="auth-card">
    <div class="auth-card__header">
        <span class="auth-card__logo" aria-hidden="true">🏦</span>
        <h1 class="auth-card__title">BnkApp Admin</h1>
        <?php if (!empty($subtitle)): ?>
            <p class="auth-card__subtitle"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <div class="auth-card__body">
        <?= $content ?? '' ?>
    </div>

    <div class="auth-card__footer">
        <small>© <?= date('Y') ?> BnkApp. All rights reserved.</small>
    </div>
</div>

<script src="/assets/js/admin.js"></script>
</body>
</html>
