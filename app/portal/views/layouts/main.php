<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'BnkApp', ENT_QUOTES, 'UTF-8') ?> — BnkApp</title>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <!-- Bootstrap 5.3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Portal CSS -->
  <link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body class="portal-layout">

<!-- Mobile sidebar overlay -->
<div class="sb-overlay" id="sbOverlay"></div>

<!-- =====================================================================
     Sidebar
===================================================================== -->
<nav id="portal-sidebar" aria-label="Customer navigation">

  <!-- Brand -->
  <a href="/dashboard" class="sb-brand">
    <div class="sb-brand-icon"><i class="bi bi-bank2"></i></div>
    <span class="sb-brand-text">Bnk<span>App</span></span>
  </a>

  <!-- User block -->
  <?php if (!empty($authUser)): ?>
  <div class="sb-user">
    <div class="sb-avatar">
      <?= strtoupper(substr($authUser['first_name']??'U',0,1).substr($authUser['last_name']??'',0,1)) ?>
    </div>
    <div class="overflow-hidden">
      <div class="sb-user-name text-truncate">
        <?= htmlspecialchars($authUser['first_name'].' '.$authUser['last_name'], ENT_QUOTES,'UTF-8') ?>
      </div>
      <div class="sb-user-email text-truncate">
        <?= htmlspecialchars($authUser['email']??'', ENT_QUOTES,'UTF-8') ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Navigation -->
  <?php
  $uri = parse_url($_SERVER['REQUEST_URI']??'/', PHP_URL_PATH);
  $al  = fn(string $p) => str_starts_with($uri, $p) ? 'active' : '';
  $isActive = fn(string $p) => ($uri==='/'||str_starts_with($uri,$p)) ? 'active' : '';

  /* Notification count */
  $unread = 0;
  try {
    if (!empty($authUser)) {
      $nb = \BnkPortal\Core\Database::getInstance()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
      $nb->execute([$authUser['id']]);
      $unread = (int)$nb->fetchColumn();
    }
  } catch (\Throwable) {}
  ?>
  <div class="sb-scroll">

    <p class="sb-section-label">Overview</p>
    <ul class="sb-nav">
      <li>
        <a href="/dashboard" class="sb-link <?= ($uri==='/'||str_starts_with($uri,'/dashboard'))?'active':'' ?>">
          <i class="bi bi-speedometer2"></i><span class="sb-label">Dashboard</span>
        </a>
      </li>
      <li>
        <a href="/accounts" class="sb-link <?= $al('/accounts') ?>">
          <i class="bi bi-wallet2"></i><span class="sb-label">My Accounts</span>
        </a>
      </li>
      <li>
        <a href="/transactions" class="sb-link <?= $al('/transactions') ?>">
          <i class="bi bi-list-ul"></i><span class="sb-label">Transactions</span>
        </a>
      </li>
    </ul>

    <p class="sb-section-label">Payments</p>
    <ul class="sb-nav">
      <li>
        <a href="/transfer" class="sb-link <?= $al('/transfer') ?>">
          <i class="bi bi-send"></i><span class="sb-label">Transfer Money</span>
        </a>
      </li>
      <li>
        <a href="/beneficiaries" class="sb-link <?= $al('/beneficiaries') ?>">
          <i class="bi bi-person-lines-fill"></i><span class="sb-label">Beneficiaries</span>
        </a>
      </li>
      <li>
        <a href="/standing-orders" class="sb-link <?= $al('/standing-orders') ?>">
          <i class="bi bi-repeat"></i><span class="sb-label">Standing Orders</span>
        </a>
      </li>
    </ul>

    <p class="sb-section-label">Products</p>
    <ul class="sb-nav">
      <li>
        <a href="/loans" class="sb-link <?= $al('/loans') ?>">
          <i class="bi bi-cash-coin"></i><span class="sb-label">Loans</span>
        </a>
      </li>
      <li>
        <a href="/cards" class="sb-link <?= $al('/cards') ?>">
          <i class="bi bi-credit-card"></i><span class="sb-label">Cards</span>
        </a>
      </li>
    </ul>

    <p class="sb-section-label">Account</p>
    <ul class="sb-nav">
      <li>
        <a href="/notifications" class="sb-link <?= $al('/notifications') ?>">
          <i class="bi bi-bell"></i><span class="sb-label">Notifications</span>
          <?php if ($unread > 0): ?>
          <span class="sb-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li>
        <a href="/support" class="sb-link <?= $al('/support') ?>">
          <i class="bi bi-headset"></i><span class="sb-label">Support</span>
        </a>
      </li>
      <li>
        <a href="/kyc" class="sb-link <?= $al('/kyc') ?>">
          <i class="bi bi-shield-check"></i><span class="sb-label">Verification (KYC)</span>
        </a>
      </li>
      <li>
        <a href="/profile" class="sb-link <?= $al('/profile') ?>">
          <i class="bi bi-person-circle"></i><span class="sb-label">My Profile</span>
        </a>
      </li>
    </ul>

  </div><!-- /sb-scroll -->

  <!-- Sign out -->
  <div class="sb-footer">
    <form method="POST" action="/logout">
      <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>
      <button type="submit" class="sb-signout">
        <i class="bi bi-box-arrow-left"></i>
        <span class="sb-label">Sign Out</span>
      </button>
    </form>
  </div>

</nav><!-- /#portal-sidebar -->

<!-- =====================================================================
     Main Content Area
===================================================================== -->
<div class="portal-main">

  <!-- Topbar -->
  <div class="portal-topbar">
    <button class="topbar-toggle" id="portalSidebarToggle" aria-label="Toggle navigation">
      <i class="bi bi-list fs-5"></i>
    </button>
    <span class="topbar-breadcrumb d-none d-md-block">
      <?= htmlspecialchars($title??'', ENT_QUOTES,'UTF-8') ?>
    </span>
    <div class="topbar-actions">
      <a href="/notifications" class="topbar-icon-btn" title="Notifications" aria-label="Notifications">
        <i class="bi bi-bell"></i>
        <?php if ($unread > 0): ?>
        <span class="topbar-notif-dot"></span>
        <?php endif; ?>
      </a>
      <a href="/profile" class="topbar-avatar" title="My Profile" aria-label="Profile">
        <?= !empty($authUser) ? strtoupper(substr($authUser['first_name']??'U',0,1)) : 'U' ?>
      </a>
    </div>
  </div>

  <!-- Flash messages -->
  <div id="portal-flash">
    <?php $flashMap=['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info']; ?>
    <?php foreach ($flashMap as $type => $bsCls): ?>
    <?php if (\BnkPortal\Core\Session::hasFlash($type)): ?>
      <?php foreach (\BnkPortal\Core\Session::getFlash($type) as $msg): ?>
      <div class="alert alert-<?= $bsCls ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $type==='success'?'check-circle':'exclamation-circle' ?> me-2"></i>
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

  <!-- Footer -->
  <footer class="portal-footer">
    © <?= date('Y') ?> BnkApp · Secure Digital Banking
  </footer>

</div><!-- /.portal-main -->

<!-- Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav" aria-label="Mobile navigation">
  <a href="/dashboard"    class="mbn-item <?= ($uri==='/'||str_starts_with($uri,'/dashboard'))?'active':'' ?>"><i class="bi bi-house"></i>Home</a>
  <a href="/accounts"     class="mbn-item <?= $al('/accounts') ?>"><i class="bi bi-wallet2"></i>Accounts</a>
  <a href="/transfer"     class="mbn-item <?= $al('/transfer') ?>"><i class="bi bi-send"></i>Transfer</a>
  <a href="/transactions" class="mbn-item <?= $al('/transactions') ?>"><i class="bi bi-list-ul"></i>History</a>
  <a href="/profile"      class="mbn-item <?= $al('/profile') ?>"><i class="bi bi-person"></i>Profile</a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="/assets/js/portal.js"></script>
</body>
</html>
