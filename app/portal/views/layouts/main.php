<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'BnkApp', ENT_QUOTES, 'UTF-8') ?> — BnkApp</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body class="portal-layout">

<!-- =====================================================================
     Sidebar
===================================================================== -->
<nav id="portal-sidebar" aria-label="Customer navigation">
    <!-- Brand -->
    <div class="portal-sidebar-brand d-flex align-items-center px-3 py-3">
        <i class="bi bi-bank2 text-white me-2 fs-4"></i>
        <span class="portal-sidebar-brand-name fw-bold fs-5">Bnk<span style="color:#60a5fa">App</span></span>
    </div>

    <!-- User info -->
    <?php if (!empty($authUser)): ?>
    <div class="d-flex align-items-center px-3 py-2 border-bottom border-white border-opacity-10">
        <div class="portal-avatar me-2">
            <?= strtoupper(substr($authUser['first_name']??'U',0,1).substr($authUser['last_name']??'',0,1)) ?>
        </div>
        <div class="overflow-hidden">
            <div class="fw-semibold text-white small text-truncate">
                <?= htmlspecialchars($authUser['first_name'].' '.$authUser['last_name'], ENT_QUOTES,'UTF-8') ?>
            </div>
            <div class="small" style="color:rgba(255,255,255,.5)">
                <?= htmlspecialchars($authUser['email']??'', ENT_QUOTES,'UTF-8') ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <?php
    $uri = parse_url($_SERVER['REQUEST_URI']??'/', PHP_URL_PATH);
    $al  = fn(string $p) => str_starts_with($uri, $p) ? 'active' : '';
    ?>
    <div class="flex-grow-1 overflow-y-auto px-2 py-2">
        <p class="portal-sidebar-section">Menu</p>
        <ul class="nav flex-column gap-1" role="list">
            <li><a href="/dashboard" class="portal-sidebar-link <?= ($uri==='/'||str_starts_with($uri,'/dashboard'))?'active':'' ?>">
                <i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
            <li><a href="/accounts" class="portal-sidebar-link <?= $al('/accounts') ?>">
                <i class="bi bi-wallet2"></i><span>My Accounts</span></a></li>
            <li><a href="/transactions" class="portal-sidebar-link <?= $al('/transactions') ?>">
                <i class="bi bi-list-ul"></i><span>Transactions</span></a></li>
        </ul>

        <p class="portal-sidebar-section mt-2">Payments</p>
        <ul class="nav flex-column gap-1" role="list">
            <li><a href="/transfer" class="portal-sidebar-link <?= $al('/transfer') ?>">
                <i class="bi bi-send"></i><span>Transfer Money</span></a></li>
            <li><a href="/beneficiaries" class="portal-sidebar-link <?= $al('/beneficiaries') ?>">
                <i class="bi bi-person-lines-fill"></i><span>Beneficiaries</span></a></li>
            <li><a href="/standing-orders" class="portal-sidebar-link <?= $al('/standing-orders') ?>">
                <i class="bi bi-repeat"></i><span>Standing Orders</span></a></li>
        </ul>

        <p class="portal-sidebar-section mt-2">Products</p>
        <ul class="nav flex-column gap-1" role="list">
            <li><a href="/loans" class="portal-sidebar-link <?= $al('/loans') ?>">
                <i class="bi bi-cash-coin"></i><span>Loans</span></a></li>
            <li><a href="/cards" class="portal-sidebar-link <?= $al('/cards') ?>">
                <i class="bi bi-credit-card"></i><span>Cards</span></a></li>
        </ul>

        <p class="portal-sidebar-section mt-2">Account</p>
        <ul class="nav flex-column gap-1" role="list">
            <li><a href="/notifications" class="portal-sidebar-link <?= $al('/notifications') ?>">
                <i class="bi bi-bell"></i><span>Notifications</span>
                <?php
                $unread = 0;
                try {
                    if ($authUser) {
                        $nb = \BnkPortal\Core\Database::getInstance()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
                        $nb->execute([$authUser['id']]);
                        $unread = (int)$nb->fetchColumn();
                    }
                } catch (\Throwable) {}
                ?>
                <?php if ($unread > 0): ?>
                <span class="badge text-bg-danger ms-auto"><?= $unread ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="/support" class="portal-sidebar-link <?= $al('/support') ?>">
                <i class="bi bi-headset"></i><span>Support</span></a></li>
            <li><a href="/kyc" class="portal-sidebar-link <?= $al('/kyc') ?>">
                <i class="bi bi-shield-check"></i><span>Verification (KYC)</span></a></li>
            <li><a href="/profile" class="portal-sidebar-link <?= $al('/profile') ?>">
                <i class="bi bi-person-circle"></i><span>My Profile</span></a></li>
        </ul>
    </div>

    <!-- Logout -->
    <div class="px-3 py-3 border-top border-white border-opacity-10">
        <form method="POST" action="/logout" class="d-grid">
            <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
            <button type="submit" class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-left me-1"></i>Sign Out
            </button>
        </form>
    </div>
</nav>

<!-- =====================================================================
     Main Content
===================================================================== -->
<div class="portal-main">
    <!-- Topbar -->
    <div class="portal-topbar">
        <button id="portalSidebarToggle" class="btn btn-link text-secondary p-0 me-3">
            <i class="bi bi-list fs-4"></i>
        </button>
        <span class="fw-semibold text-secondary small d-none d-md-block"><?= htmlspecialchars($title??'', ENT_QUOTES,'UTF-8') ?></span>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="/notifications" class="btn btn-link text-secondary p-1">
                <i class="bi bi-bell fs-5"></i>
            </a>
            <a href="/profile" class="btn btn-link text-secondary p-1 d-flex align-items-center gap-1">
                <div class="portal-avatar" style="width:28px;height:28px;font-size:.7rem">
                    <?= !empty($authUser) ? strtoupper(substr($authUser['first_name']??'U',0,1)) : 'U' ?>
                </div>
            </a>
        </div>
    </div>

    <!-- Flash messages -->
    <div id="portal-flash" class="px-4 pt-3">
        <?php $flashMap=['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info']; ?>
        <?php foreach ($flashMap as $type => $bsCls): ?>
        <?php if (\BnkPortal\Core\Session::hasFlash($type)): ?>
            <?php foreach (\BnkPortal\Core\Session::getFlash($type) as $msg): ?>
            <div class="alert alert-<?= $bsCls ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($msg, ENT_QUOTES,'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Page content -->
    <div class="portal-content">
        <?= $content ?? '' ?>
    </div>

    <footer class="text-center text-muted small py-3 border-top">
        © <?= date('Y') ?> BnkApp. Your money, simplified.
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmQ==" crossorigin="anonymous"></script>
<script src="/assets/js/portal.js"></script>
</body>
</html>
