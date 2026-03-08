<?php ob_start(); ?>

<div class="mb-4">
  <h2 class="fw-bold mb-1" style="letter-spacing:-.4px;font-size:1.7rem">Welcome back</h2>
  <p class="text-muted" style="font-size:.9rem">Sign in to your BnkApp account</p>
</div>

<form method="POST" action="/login" novalidate>
  <?= \BnkPortal\Middleware\CsrfMiddleware::field() ?>

  <div class="mb-3">
    <label class="form-label">Email address</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
      <input type="email" name="email" class="form-control" placeholder="you@example.com"
             autocomplete="email" required autofocus>
    </div>
  </div>

  <div class="mb-4">
    <div class="d-flex justify-content-between">
      <label class="form-label">Password</label>
    </div>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
      <input type="password" name="password" class="form-control" placeholder="••••••••"
             autocomplete="current-password" required>
      <button type="button" class="btn btn-outline-secondary" data-toggle-pwd style="border-radius:0 var(--radius-sm) var(--radius-sm) 0">
        <i class="bi bi-eye"></i>
      </button>
    </div>
  </div>

  <div class="d-grid mb-4">
    <button type="submit" class="btn btn-primary btn-lg">
      Sign In <i class="bi bi-arrow-right ms-1"></i>
    </button>
  </div>

  <p class="text-center text-muted mb-0" style="font-size:.88rem">
    Don't have an account?
    <a href="/register" class="text-primary fw-semibold text-decoration-none">Create one for free</a>
  </p>
</form>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/auth.php';
