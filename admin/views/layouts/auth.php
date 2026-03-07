<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'BnkApp Admin', ENT_QUOTES, 'UTF-8') ?></title>
    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom admin styles -->
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="auth-page min-vh-100 d-flex align-items-center justify-content-center">

<div class="auth-wrapper">
    <!-- Brand -->
    <div class="auth-brand text-center mb-4">
        <div class="auth-brand-icon mb-2">
            <i class="bi bi-bank2 text-primary"></i>
        </div>
        <h1 class="auth-brand-name h4 fw-bold mb-0">BnkApp <span class="text-primary">Admin</span></h1>
        <?php if (!empty($subtitle)): ?>
            <p class="text-muted small mt-1"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <!-- Card -->
    <div class="card shadow-sm border-0 auth-card">
        <div class="card-body p-4">
            <?= $content ?? '' ?>
        </div>
    </div>

    <div class="text-center mt-3 text-muted small">
        © <?= date('Y') ?> BnkApp. All rights reserved.
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmQ==" crossorigin="anonymous"></script>
<script src="/assets/js/admin.js"></script>
</body>
</html>
