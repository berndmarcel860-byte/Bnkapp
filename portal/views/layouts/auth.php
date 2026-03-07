<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'BnkApp', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body class="portal-auth">

<div class="portal-auth-card mx-auto px-3">
    <!-- Brand -->
    <div class="text-center mb-4">
        <div class="portal-auth-brand mb-2"><i class="bi bi-bank2"></i></div>
        <h1 class="portal-auth-brand-name h4 fw-bold mb-0">BnkApp</h1>
        <p class="text-white-50 small mt-1">Digital Banking for Everyone</p>
    </div>

    <!-- Flash messages -->
    <?php $flashMap=['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info']; ?>
    <?php foreach ($flashMap as $type => $bsCls): ?>
    <?php if (\BnkPortal\Core\Session::hasFlash($type)): ?>
        <?php foreach (\BnkPortal\Core\Session::getFlash($type) as $msg): ?>
        <div class="alert alert-<?= $bsCls ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php endforeach; ?>

    <!-- Card -->
    <div class="card shadow border-0">
        <div class="card-body p-4">
            <?= $content ?? '' ?>
        </div>
    </div>

    <div class="text-center mt-3 text-white-50 small">
        © <?= date('Y') ?> BnkApp. All rights reserved.
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmQ==" crossorigin="anonymous"></script>
<script src="/assets/js/portal.js"></script>
</body>
</html>
