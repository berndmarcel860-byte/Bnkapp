<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'BnkApp Admin', ENT_QUOTES, 'UTF-8') ?> — BnkApp Admin</title>
    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom admin styles -->
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-layout">

<!-- =====================================================================
     Sidebar
===================================================================== -->
<nav id="sidebar" class="sidebar d-flex flex-column" aria-label="Main navigation">
    <div class="sidebar-brand d-flex align-items-center px-3 py-3">
        <i class="bi bi-bank2 me-2 fs-4 text-primary"></i>
        <span class="sidebar-brand-text fw-bold fs-5">BnkApp <span class="text-primary">Admin</span></span>
    </div>

    <?php if (!empty($authUser)): ?>
    <div class="sidebar-user d-flex align-items-center px-3 py-2 mb-2 border-bottom border-secondary border-opacity-25">
        <div class="sidebar-avatar me-2">
            <?= strtoupper(substr($authUser['first_name'], 0, 1) . substr($authUser['last_name'], 0, 1)) ?>
        </div>
        <div class="sidebar-user-info overflow-hidden">
            <div class="sidebar-user-name fw-semibold text-truncate small">
                <?= htmlspecialchars($authUser['first_name'] . ' ' . $authUser['last_name'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="sidebar-user-role">
                <span class="badge text-bg-primary"><?= htmlspecialchars($authUser['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="sidebar-nav-wrapper flex-grow-1 overflow-y-auto px-2">
        <?php
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $isActive = fn(string $prefix) => str_starts_with($uri, $prefix) ? 'active' : '';
        ?>

        <div class="sidebar-section-label">CORE</div>
        <ul class="nav flex-column mb-2" role="list">
            <li class="nav-item">
                <a href="/dashboard" class="nav-link sidebar-link <?= ($uri === '/' || str_starts_with($uri, '/dashboard')) ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label">CUSTOMERS</div>
        <ul class="nav flex-column mb-2" role="list">
            <li class="nav-item">
                <a href="/users" class="nav-link sidebar-link <?= $isActive('/users') ?>">
                    <i class="bi bi-people"></i><span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/accounts" class="nav-link sidebar-link <?= $isActive('/accounts') ?>">
                    <i class="bi bi-wallet2"></i><span>Accounts</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/kyc" class="nav-link sidebar-link <?= $isActive('/kyc') ?>">
                    <i class="bi bi-shield-check"></i><span>KYC Documents</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label">PAYMENTS</div>
        <ul class="nav flex-column mb-2" role="list">
            <li class="nav-item">
                <a href="/transactions" class="nav-link sidebar-link <?= $isActive('/transactions') ?>">
                    <i class="bi bi-arrow-left-right"></i><span>Transactions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/sepa" class="nav-link sidebar-link <?= $isActive('/sepa') ?>">
                    <i class="bi bi-send"></i><span>SEPA Transfers</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/mandates" class="nav-link sidebar-link <?= $isActive('/mandates') ?>">
                    <i class="bi bi-file-earmark-text"></i><span>SEPA Mandates</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/standing-orders" class="nav-link sidebar-link <?= $isActive('/standing-orders') ?>">
                    <i class="bi bi-repeat"></i><span>Standing Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/beneficiaries" class="nav-link sidebar-link <?= $isActive('/beneficiaries') ?>">
                    <i class="bi bi-person-lines-fill"></i><span>Beneficiaries</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label">PRODUCTS</div>
        <ul class="nav flex-column mb-2" role="list">
            <li class="nav-item">
                <a href="/loans" class="nav-link sidebar-link <?= $isActive('/loans') ?>">
                    <i class="bi bi-cash-coin"></i><span>Loans</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/cards" class="nav-link sidebar-link <?= $isActive('/cards') ?>">
                    <i class="bi bi-credit-card"></i><span>Cards</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label">OPERATIONS</div>
        <ul class="nav flex-column mb-2" role="list">
            <li class="nav-item">
                <a href="/branches" class="nav-link sidebar-link <?= $isActive('/branches') ?>">
                    <i class="bi bi-building"></i><span>Branches</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/fee-schedules" class="nav-link sidebar-link <?= $isActive('/fee-schedules') ?>">
                    <i class="bi bi-percent"></i><span>Fee Schedules</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/exchange-rates" class="nav-link sidebar-link <?= $isActive('/exchange-rates') ?>">
                    <i class="bi bi-currency-exchange"></i><span>Exchange Rates</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/notifications" class="nav-link sidebar-link <?= $isActive('/notifications') ?>">
                    <i class="bi bi-bell"></i><span>Notifications</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/support" class="nav-link sidebar-link <?= $isActive('/support') ?>">
                    <i class="bi bi-headset"></i><span>Support</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label">ANALYTICS</div>
        <ul class="nav flex-column mb-2" role="list">
            <li class="nav-item">
                <a href="/reports" class="nav-link sidebar-link <?= $isActive('/reports') ?>">
                    <i class="bi bi-bar-chart-line"></i><span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/reports/audit" class="nav-link sidebar-link <?= $isActive('/reports/audit') ?>">
                    <i class="bi bi-journal-text"></i><span>Audit Log</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label">SYSTEM</div>
        <ul class="nav flex-column mb-2" role="list">
            <li class="nav-item">
                <a href="/settings" class="nav-link sidebar-link <?= $isActive('/settings') ?>">
                    <i class="bi bi-gear"></i><span>Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/email-templates" class="nav-link sidebar-link <?= $isActive('/email-templates') ?>">
                    <i class="bi bi-envelope-paper"></i><span>Email Templates</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer px-3 py-3 border-top border-secondary border-opacity-25">
        <form method="POST" action="/auth/logout" class="d-grid">
            <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-box-arrow-left me-1"></i>Sign Out
            </button>
        </form>
    </div>
</nav>

<!-- =====================================================================
     Main Wrapper
===================================================================== -->
<div class="main-wrapper d-flex flex-column">

    <!-- Top Navbar -->
    <nav class="topbar navbar px-3 d-flex align-items-center justify-content-between shadow-sm">
        <button id="sidebarToggle" class="btn btn-link text-secondary p-0 me-3" aria-label="Toggle sidebar">
            <i class="bi bi-list fs-4"></i>
        </button>

        <div class="topbar-breadcrumb small text-muted d-none d-md-block">
            <?= htmlspecialchars($title ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div class="topbar-actions d-flex align-items-center gap-2 ms-auto">
            <a href="/notifications" class="btn btn-link text-secondary position-relative p-1" title="Notifications">
                <i class="bi bi-bell fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notif-count" style="display:none;font-size:.6rem"></span>
            </a>
            <div class="dropdown">
                <button class="btn btn-link text-secondary p-1 d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="topbar-avatar me-1"><?= !empty($authUser) ? strtoupper(substr($authUser['first_name'] ?? 'A', 0, 1)) : 'A' ?></div>
                    <i class="bi bi-chevron-down small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars(($authUser['first_name'] ?? '') . ' ' . ($authUser['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/settings"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li>
                        <form method="POST" action="/auth/logout">
                            <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-left me-2"></i>Sign Out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="page-content flex-grow-1 p-4" id="main-content">

        <!-- Flash Messages -->
        <div id="flash-container">
        <?php
        $flashMap = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
        foreach ($flashMap as $flashType => $bsClass): ?>
            <?php if (\BnkApp\Core\Session::hasFlash($flashType)): ?>
                <?php foreach (\BnkApp\Core\Session::getFlash($flashType) as $msg): ?>
                    <div class="alert alert-<?= $bsClass ?> alert-dismissible fade show d-flex align-items-center" role="alert">
                        <i class="bi bi-<?= $flashType === 'success' ? 'check-circle' : ($flashType === 'error' ? 'x-circle' : 'info-circle') ?> me-2"></i>
                        <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>

        <?= $content ?? '' ?>
    </main>

    <footer class="page-footer text-center text-muted small py-2 border-top">
        © <?= date('Y') ?> BnkApp Administration Panel
    </footer>
</div>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Custom admin JS -->
<script src="/assets/js/admin.js"></script>
</body>
</html>
