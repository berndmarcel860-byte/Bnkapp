<?php
/**
 * View: settings/index.php
 */
use BnkApp\Helpers\FormatHelper;
ob_start(); ?>

<div class="mb-4">
    <h1 class="page-title mb-0">Settings</h1>
    <p class="text-muted small">System configuration — roles, permissions, account types, countries, SMTP.</p>
</div>

<!-- Nav tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-smtp">SMTP / Email</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-account-types">Account Types</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-roles">Roles</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-permissions">Permissions</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-countries">SEPA Countries</a></li>
</ul>

<div class="tab-content">

    <!-- ── SMTP / Email ─────────────────────────────────────────────────── -->
    <div class="tab-pane fade show active" id="tab-smtp">
        <div class="card">
            <div class="card-header fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-envelope-at text-primary"></i> SMTP Mail Settings
            </div>
            <div class="card-body">

                <?php
                $testOk    = $smtp['last_test_ok'] ?? null;
                $testError = $smtp['last_test_error'] ?? null;
                $testedAt  = $smtp['last_tested_at']  ?? null;
                ?>

                <?php if ($testedAt !== null): ?>
                <div class="alert alert-<?= $testOk ? 'success' : 'danger' ?> d-flex align-items-center gap-2 py-2">
                    <i class="bi bi-<?= $testOk ? 'check-circle' : 'x-circle' ?>"></i>
                    <span>
                        Last test (<?= htmlspecialchars($testedAt, ENT_QUOTES, 'UTF-8') ?>):
                        <?= $testOk ? 'Connection OK' : htmlspecialchars((string)$testError, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <?php endif; ?>

                <form method="POST" action="/settings/smtp" data-ajax="true" data-reload="false" id="smtp-form">
                    <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Host</label>
                            <input type="text" name="host" class="form-control"
                                   value="<?= htmlspecialchars($smtp['host'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="smtp.example.com">
                            <div class="form-text">Leave blank to use PHP <code>mail()</code> instead of SMTP.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Port</label>
                            <input type="number" name="port" class="form-control" min="1" max="65535"
                                   value="<?= (int)($smtp['port'] ?? 587) ?>"
                                   required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Encryption</label>
                            <select name="encryption" class="form-select">
                                <?php foreach (['tls' => 'STARTTLS (TLS)', 'ssl' => 'SSL/TLS', 'none' => 'None'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($smtp['encryption'] ?? 'tls') === $val ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Username</label>
                            <input type="text" name="username" class="form-control" autocomplete="username"
                                   value="<?= htmlspecialchars($smtp['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="user@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Password</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control" autocomplete="new-password"
                                       placeholder="Leave blank to keep existing password">
                                <button type="button" class="btn btn-outline-secondary" data-toggle-pwd>
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">From Address</label>
                            <input type="email" name="from_address" class="form-control"
                                   value="<?= htmlspecialchars($smtp['from_address'] ?? 'noreply@example.com', ENT_QUOTES, 'UTF-8') ?>"
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">From Name</label>
                            <input type="text" name="from_name" class="form-control"
                                   value="<?= htmlspecialchars($smtp['from_name'] ?? 'BnkApp', ENT_QUOTES, 'UTF-8') ?>"
                                   required>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy me-1"></i>Save Settings
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="smtpTestBtn">
                            <i class="bi bi-send-check me-1"></i>Test Connection
                        </button>
                    </div>
                </form>

            </div><!-- /card-body -->
        </div><!-- /card -->

        <div class="alert alert-info mt-3 small">
            <i class="bi bi-info-circle me-1"></i>
            <strong>PHPMailer</strong> is used to deliver all transactional emails.
            SMTP credentials are stored in the <code>smtp_settings</code> table.
            Environment variables (<code>MAIL_HOST</code>, <code>MAIL_USER</code>, etc.) act as fallbacks
            when no host is configured here.
        </div>
    </div><!-- /#tab-smtp -->

    <!-- ── Account Types ───────────────────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-account-types">
        <div class="card table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Account Types</span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountTypeModal">
                    <i class="bi bi-plus-circle me-1"></i>Add
                </button>
            </div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>#</th><th>Name</th><th>Min Balance</th><th>Max Balance</th><th>Interest Rate</th><th>Active</th></tr></thead>
                    <tbody>
                    <?php foreach ($accountTypes as $at): ?>
                    <tr>
                        <td><?= $at['id'] ?></td>
                        <td class="fw-semibold"><?= FormatHelper::e($at['name']) ?></td>
                        <td><?= FormatHelper::money((float)$at['min_balance']) ?></td>
                        <td><?= $at['max_balance'] ? FormatHelper::money((float)$at['max_balance']) : '—' ?></td>
                        <td><?= number_format((float)$at['interest_rate'],4) ?>%</td>
                        <td><?= $at['is_active'] ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-secondary">No</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Roles -->
    <div class="tab-pane fade" id="tab-roles">
        <div class="card table-card">
            <div class="card-header fw-semibold">Roles</div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>#</th><th>Name</th><th>Description</th></tr></thead>
                    <tbody>
                    <?php foreach ($roles as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td class="fw-semibold"><?= FormatHelper::e($r['name']) ?></td>
                        <td class="text-muted small"><?= FormatHelper::e($r['description']??'') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Permissions -->
    <div class="tab-pane fade" id="tab-permissions">
        <div class="card table-card">
            <div class="card-header fw-semibold">Permissions</div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>#</th><th>Name</th><th>Description</th></tr></thead>
                    <tbody>
                    <?php foreach ($permissions as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td class="fw-semibold text-mono small"><?= FormatHelper::e($p['name']) ?></td>
                        <td class="text-muted small"><?= FormatHelper::e($p['description']??'') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SEPA Countries -->
    <div class="tab-pane fade" id="tab-countries">
        <div class="card table-card">
            <div class="card-header fw-semibold">SEPA Countries</div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>Code</th><th>Name</th><th>Currency</th><th>SEPA</th></tr></thead>
                    <tbody>
                    <?php foreach ($countries as $c): ?>
                    <tr>
                        <td class="fw-bold text-mono"><?= $c['iso_code'] ?></td>
                        <td><?= FormatHelper::e($c['name']) ?></td>
                        <td><?= $c['currency_code'] ?></td>
                        <td><i class="bi bi-check-circle-fill text-success"></i></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Account Type Modal -->
<div class="modal fade" id="addAccountTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/settings/account-types" data-ajax="true" data-reload="true">
                <?= \BnkApp\Middleware\CsrfMiddleware::field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Add Account Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Code</label>
                        <input type="text" name="code" class="form-control text-uppercase" maxlength="20" placeholder="e.g. CHK" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Min Balance</label>
                        <input type="number" name="min_balance" step="0.01" class="form-control" value="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Max Balance</label>
                        <input type="number" name="max_balance" step="0.01" class="form-control" placeholder="No limit">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Interest Rate (%)</label>
                        <input type="number" name="interest_rate" step="0.0001" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ── SMTP test connection button ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const testBtn = document.getElementById('smtpTestBtn');
    if (testBtn) {
        testBtn.addEventListener('click', function () {
            testBtn.disabled = true;
            testBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing…';

            const form  = document.getElementById('smtp-form');
            const token = form.querySelector('[name="_csrf_token"]');

            fetch('/settings/smtp/test', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({
                    _csrf_token: token ? token.value : ''
                }),
            })
            .then(r => r.json())
            .then(json => {
                const cls = json.success ? 'success' : 'danger';
                const msg = json.message || (json.success ? 'Connection OK' : 'Connection failed');
                showAlert(cls, msg);
            })
            .catch(() => showAlert('danger', 'Request failed.'))
            .finally(() => {
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="bi bi-send-check me-1"></i>Test Connection';
            });
        });
    }

    function showAlert(type, msg) {
        const existing = document.getElementById('smtp-test-alert');
        if (existing) existing.remove();
        const el = document.createElement('div');
        el.id = 'smtp-test-alert';
        el.className = `alert alert-${type} alert-dismissible fade show mt-3`;
        el.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'x-circle'} me-1"></i>${msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.getElementById('smtp-form').after(el);
    }

    // ── Password show/hide ──────────────────────────────────────────────
    document.querySelectorAll('[data-toggle-pwd]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const inp  = btn.closest('.input-group').querySelector('input');
            const icon = btn.querySelector('i');
            if (inp.type === 'password') {
                inp.type = 'text';
                if (icon) { icon.className = 'bi bi-eye-slash'; }
            } else {
                inp.type = 'password';
                if (icon) { icon.className = 'bi bi-eye'; }
            }
        });
    });
});
</script>

<?php $content = ob_get_clean(); require VIEWS_PATH . '/layouts/main.php';
