<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Sign In', ENT_QUOTES, 'UTF-8') ?> — BnkApp</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body class="portal-auth">

  <!-- Left decorative panel -->
  <div class="auth-split-left">
    <div>
      <div class="auth-brand-icon"><i class="bi bi-bank2"></i></div>
      <h2 class="text-white fw-800 mb-1" style="font-size:1.8rem;font-weight:800;letter-spacing:-.4px">BnkApp</h2>
      <p class="mb-5" style="color:rgba(255,255,255,.55);font-size:.9rem">Digital Banking for Everyone</p>

      <div class="auth-feature">
        <div class="auth-feature-icon"><i class="bi bi-shield-lock"></i></div>
        <div class="auth-feature-text">
          <h6>Bank-Grade Security</h6>
          <p>Your money and data protected with the latest encryption standards.</p>
        </div>
      </div>
      <div class="auth-feature">
        <div class="auth-feature-icon"><i class="bi bi-globe2"></i></div>
        <div class="auth-feature-text">
          <h6>SEPA Transfers</h6>
          <p>Send money across 36 European countries instantly and securely.</p>
        </div>
      </div>
      <div class="auth-feature">
        <div class="auth-feature-icon"><i class="bi bi-phone"></i></div>
        <div class="auth-feature-text">
          <h6>Anytime, Anywhere</h6>
          <p>Access your accounts on any device, 24 hours a day.</p>
        </div>
      </div>
    </div>

    <p style="color:rgba(255,255,255,.3);font-size:.75rem">
      © <?= date('Y') ?> BnkApp · All rights reserved
    </p>
  </div>

  <!-- Right form panel -->
  <div class="auth-split-right">
    <div class="auth-card">
      <!-- Flash messages -->
      <?php $flashMap=['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info']; ?>
      <?php foreach ($flashMap as $type => $bsCls): ?>
      <?php if (\BnkPortal\Core\Session::hasFlash($type)): ?>
        <?php foreach (\BnkPortal\Core\Session::getFlash($type) as $msg): ?>
        <div class="alert alert-<?= $bsCls ?> alert-dismissible fade show mb-4" role="alert">
          <i class="bi bi-<?= $type==='success'?'check-circle':'exclamation-circle' ?> me-2"></i>
          <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php endforeach; ?>

      <?= $content ?? '' ?>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="/assets/js/portal.js"></script>
</body>
</html>
