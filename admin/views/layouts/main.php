<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'BnkApp Admin', ENT_QUOTES, 'UTF-8') ?> — BnkApp Admin</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-layout">

<!-- =====================================================================
     Sidebar Navigation
===================================================================== -->
<nav class="sidebar" aria-label="Main navigation">
    <div class="sidebar__brand">
        <span class="sidebar__logo" aria-hidden="true">🏦</span>
        <span class="sidebar__name">BnkApp Admin</span>
    </div>

    <?php if (!empty($authUser)): ?>
    <div class="sidebar__user">
        <span class="sidebar__user-name">
            <?= htmlspecialchars($authUser['first_name'] . ' ' . $authUser['last_name'], ENT_QUOTES, 'UTF-8') ?>
        </span>
        <span class="sidebar__user-role badge badge--info">
            <?= htmlspecialchars($authUser['role'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </span>
    </div>
    <?php endif; ?>

    <ul class="sidebar__nav" role="list">
        <li class="sidebar__nav-item">
            <a href="/dashboard" class="sidebar__nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/dashboard') || ($_SERVER['REQUEST_URI'] ?? '') === '/' ? 'is-active' : '' ?>">
                <span aria-hidden="true">📊</span> Dashboard
            </a>
        </li>
        <li class="sidebar__nav-item">
            <a href="/users" class="sidebar__nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/users') ? 'is-active' : '' ?>">
                <span aria-hidden="true">👥</span> Users
            </a>
        </li>
        <li class="sidebar__nav-item">
            <a href="/accounts" class="sidebar__nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/accounts') ? 'is-active' : '' ?>">
                <span aria-hidden="true">🏛️</span> Accounts
            </a>
        </li>
        <li class="sidebar__nav-item">
            <a href="/transactions" class="sidebar__nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/transactions') ? 'is-active' : '' ?>">
                <span aria-hidden="true">💸</span> Transactions
            </a>
        </li>
        <li class="sidebar__nav-item">
            <a href="/loans" class="sidebar__nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/loans') ? 'is-active' : '' ?>">
                <span aria-hidden="true">📋</span> Loans
            </a>
        </li>
        <li class="sidebar__nav-item">
            <a href="/cards" class="sidebar__nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/cards') ? 'is-active' : '' ?>">
                <span aria-hidden="true">💳</span> Cards
            </a>
        </li>
        <li class="sidebar__nav-item">
            <a href="/reports" class="sidebar__nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/reports') ? 'is-active' : '' ?>">
                <span aria-hidden="true">📈</span> Reports
            </a>
        </li>
        <li class="sidebar__nav-item">
            <a href="/support" class="sidebar__nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/support') ? 'is-active' : '' ?>">
                <span aria-hidden="true">🎫</span> Support
            </a>
        </li>
    </ul>

    <div class="sidebar__footer">
        <form method="POST" action="/auth/logout">
            <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
            <button type="submit" class="btn btn--ghost btn--sm btn--full-width">
                Sign Out
            </button>
        </form>
    </div>
</nav>

<!-- =====================================================================
     Main Content
===================================================================== -->
<main class="main-content" id="main-content">
    <header class="page-header">
        <h1 class="page-header__title"><?= htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
    </header>

    <!-- Flash Messages -->
    <?php foreach (['success', 'error', 'warning', 'info'] as $flashType): ?>
        <?php if (\BnkApp\Core\Session::hasFlash($flashType)): ?>
            <?php foreach (\BnkApp\Core\Session::getFlash($flashType) as $msg): ?>
                <div class="alert alert--<?= $flashType ?>" role="alert">
                    <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Page Body -->
    <?= $content ?? '' ?>
</main>

<script src="/assets/js/admin.js"></script>
</body>
</html>
