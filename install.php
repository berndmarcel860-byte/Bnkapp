<?php
declare(strict_types=1);
/**
 * BnkApp — Browser-Based Installation Wizard
 *
 * Place this file at the web-accessible root.
 * After installation completes, this file locks itself (install.lock is created).
 *
 * Nginx quick-start (point your server root at the repo root for the installer):
 *   location / { try_files $uri $uri/ /install.php?$query_string; }
 *   location ~ \.php$ { fastcgi_pass unix:/run/php/php8.3-fpm.sock; ... }
 *
 * Once done, reconfigure Nginx to point to app/admin/public or app/portal/public.
 */

// ---------------------------------------------------------------------------
// 0. Guard: already installed?
// ---------------------------------------------------------------------------
define('BASE_DIR',    __DIR__);
define('LOCK_FILE',   BASE_DIR . '/install.lock');
define('DB_DIR',      BASE_DIR . '/database');
define('APP_DIR',     BASE_DIR . '/app');
define('ENV_FILE',    APP_DIR  . '/env.php');

if (file_exists(LOCK_FILE)) {
    http_response_code(403);
    die(renderLocked());
}

// ---------------------------------------------------------------------------
// 1. Session
// ---------------------------------------------------------------------------
session_name('BNKAPP_INSTALL');
session_start();

// ---------------------------------------------------------------------------
// 2. Routing: AJAX actions bypass the normal wizard
// ---------------------------------------------------------------------------
$action = $_GET['action'] ?? '';

if ($action === 'test_db' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode(ajaxTestDb());
    exit;
}

if ($action === 'migrate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode(ajaxRunMigrations());
    exit;
}

if ($action === 'create_admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode(ajaxCreateAdmin());
    exit;
}

if ($action === 'finalize' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode(ajaxFinalize());
    exit;
}

// ---------------------------------------------------------------------------
// 3. Step management
// ---------------------------------------------------------------------------
$step = (int)($_GET['step'] ?? $_SESSION['install_step'] ?? 1);
$step = max(1, min(5, $step));
$_SESSION['install_step'] = $step;

// ---------------------------------------------------------------------------
// 4. Render the full page
// ---------------------------------------------------------------------------
echo renderPage($step);
exit;

// ============================================================================
// AJAX HANDLERS
// ============================================================================

function ajaxTestDb(): array
{
    $host      = trim($_POST['db_host']    ?? '');
    $port      = (int)($_POST['db_port']   ?? 3306);
    $name      = trim($_POST['db_name']    ?? '');
    $user      = trim($_POST['db_user']    ?? '');
    $pass      = $_POST['db_pass']         ?? '';
    $adminUser = trim($_POST['admin_user'] ?? '');
    $adminPass = $_POST['admin_pass']      ?? '';

    if (!$host || !$name || !$user) {
        return ['ok' => false, 'message' => 'Host, database name, and username are required.'];
    }

    // Reject host values that contain DSN metacharacters to prevent injection.
    if (!preg_match('/^[a-zA-Z0-9\-\.\[\]:_]+$/', $host)) {
        return ['ok' => false, 'message' => 'Invalid characters in database host.'];
    }

    $grantMsg = '';

    // If privileged admin credentials were provided, use them to create the
    // application database user and grant it the required privileges.
    if ($adminUser !== '') {
        try {
            $adminPdo = new PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                $adminUser,
                $adminPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Create the database under the admin account
            $safeName = str_replace('`', '``', $name);
            $adminPdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Create the app user and grant ALL PRIVILEGES (database-scoped —
            // does NOT include global privileges such as CREATE USER or SHUTDOWN)
            // for both Unix-socket ('localhost') and TCP ('%') connections.
            // Note: IDENTIFIED BY transmits the password in plaintext inside the
            // SQL statement; enable MySQL's general_log only for debugging.
            $quotedUser = $adminPdo->quote($user);
            $quotedPass = $adminPdo->quote($pass);
            foreach (['localhost', '%'] as $grantHost) {
                $adminPdo->exec("CREATE USER IF NOT EXISTS {$quotedUser}@'{$grantHost}' IDENTIFIED BY {$quotedPass}");
                $adminPdo->exec("GRANT ALL PRIVILEGES ON `{$safeName}`.* TO {$quotedUser}@'{$grantHost}'");
            }
            $adminPdo->exec('FLUSH PRIVILEGES');

            $grantMsg = ' Privileges granted to <strong>' . htmlspecialchars($user) . '</strong>.';
        } catch (PDOException $e) {
            return ['ok' => false, 'message' => 'Admin connection failed: ' . htmlspecialchars($e->getMessage())];
        }
    }

    // Verify connection with the application-user credentials
    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Check whether the target database exists
        $stmt   = $pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($name));
        $exists = (bool)$stmt->fetchColumn();

        if (!$exists) {
            // No admin user was provided — attempt to create with the app user
            $safeName = str_replace('`', '``', $name);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $msg = "Connected successfully. Database <strong>" . htmlspecialchars($name) . "</strong> was created.{$grantMsg}";
        } else {
            $msg = "Connected successfully. Database <strong>" . htmlspecialchars($name) . "</strong> exists.{$grantMsg}";
        }

        // Save credentials to session
        $_SESSION['db'] = compact('host', 'port', 'name', 'user', 'pass');

        return ['ok' => true, 'message' => $msg];
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => 'Connection failed: ' . htmlspecialchars($e->getMessage())];
    }
}

function ajaxRunMigrations(): array
{
    if (empty($_SESSION['db'])) {
        return ['ok' => false, 'results' => [], 'message' => 'No database credentials in session. Please go back to Step 2.'];
    }

    $db = $_SESSION['db'];
    $results = [];

    try {
        $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => true,   // required for multi-statement DDL
        ]);
        $pdo->exec("SET NAMES utf8mb4");
    } catch (PDOException $e) {
        return ['ok' => false, 'results' => [], 'message' => 'DB connect failed: ' . htmlspecialchars($e->getMessage())];
    }

    $files = [
        '01_schema.sql'    => 'Schema (tables)',
        '02_functions.sql' => 'Functions & stored procedures',
        '03_indexes.sql'   => 'Indexes',
        '04_seed.sql'      => 'Seed data (roles, permissions, countries)',
        '05_email_templates.sql' => 'Email templates',
        '06_smtp_settings.sql'   => 'SMTP settings table',
    ];

    $allOk = true;
    foreach ($files as $filename => $label) {
        $path = DB_DIR . '/' . $filename;
        if (!file_exists($path)) {
            $results[] = ['file' => $filename, 'label' => $label, 'ok' => false, 'errors' => ["File not found: {$path}"]];
            $allOk = false;
            continue;
        }
        $sql    = file_get_contents($path);
        $stmts  = parseSqlStatements($sql);
        $errors = [];

        foreach ($stmts as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') continue;
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // Skip "already exists" / duplicate-key errors for idempotency
                $code = (int)$e->getCode();
                $msg  = $e->getMessage();
                if (in_array($code, [1050, 1060, 1061, 1062, 1091, 1304, 1305, 1360])) {
                    // 1050 table exists, 1060 dup column, 1061/1091 dup index,
                    // 1062 dup entry, 1304/1305 function/procedure exists, 1360 trigger exists
                    continue;
                }
                $errors[] = htmlspecialchars($msg);
            }
        }

        $results[] = [
            'file'   => $filename,
            'label'  => $label,
            'ok'     => empty($errors),
            'errors' => $errors,
        ];
        if (!empty($errors)) $allOk = false;
    }

    if ($allOk) {
        $_SESSION['migrations_done'] = true;
    }

    return ['ok' => $allOk, 'results' => $results, 'message' => $allOk ? 'All migrations completed successfully.' : 'Some migrations had errors (see details).'];
}

function ajaxCreateAdmin(): array
{
    if (empty($_SESSION['db'])) {
        return ['ok' => false, 'message' => 'No database credentials. Please restart.'];
    }

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $password  = $_POST['password']        ?? '';
    $confirm   = $_POST['password_confirm']?? '';

    // Validation
    if (!$firstName || !$lastName || !$email || !$password) {
        return ['ok' => false, 'message' => 'All fields are required.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Invalid email address.'];
    }
    if (strlen($password) < 12) {
        return ['ok' => false, 'message' => 'Password must be at least 12 characters.'];
    }
    if ($password !== $confirm) {
        return ['ok' => false, 'message' => 'Passwords do not match.'];
    }

    $db  = $_SESSION['db'];
    try {
        $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => true,
        ]);
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => 'DB connect failed: ' . htmlspecialchars($e->getMessage())];
    }

    // Check roles table exists and has super_admin
    try {
        $stmt = $pdo->query("SELECT id FROM roles WHERE name = 'super_admin' LIMIT 1");
        $roleId = $stmt->fetchColumn();
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => 'Could not read roles table. Have you run migrations? ' . htmlspecialchars($e->getMessage())];
    }

    if (!$roleId) {
        return ['ok' => false, 'message' => "Role 'super_admin' not found. Please run migrations first."];
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn()) {
        return ['ok' => false, 'message' => "A user with this email already exists."];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Get Germany (DE) country_id — fallback to NULL if not seeded yet
    try {
        $stmtC = $pdo->query("SELECT id FROM countries WHERE iso_code = 'DE' LIMIT 1");
        $countryId = $stmtC->fetchColumn() ?: null;
    } catch (PDOException $e) {
        $countryId = null;
    }

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO users
                    (role_id, first_name, last_name, email,
                     password_hash, password_salt,
                     is_active, is_email_verified, kyc_status, kyc_approved_at,
                     country_id)
                VALUES
                    (:role_id, :first_name, :last_name, :email,
                     :hash, 'bcrypt',
                     1, 1, 'approved', NOW(),
                     :country_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':role_id'    => $roleId,
            ':first_name' => $firstName,
            ':last_name'  => $lastName,
            ':email'      => $email,
            ':hash'       => $hash,
            ':country_id' => $countryId,
        ]);
        $userId = (int)$pdo->lastInsertId();

        // Generate a unique employee ID
        $empId = 'EMP-' . date('Ymd') . '-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);

        $sqlAdmin = "INSERT INTO admins
                         (user_id, employee_id, department, access_level,
                          can_approve_transactions, can_manage_users,
                          can_view_reports, can_manage_loans, can_freeze_accounts)
                     VALUES
                         (:uid, :empid, 'IT & Operations', 'super_admin',
                          1, 1, 1, 1, 1)";
        $stmt2 = $pdo->prepare($sqlAdmin);
        $stmt2->execute([':uid' => $userId, ':empid' => $empId]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['ok' => false, 'message' => 'Failed to create admin: ' . htmlspecialchars($e->getMessage())];
    }

    $_SESSION['admin_email'] = $email;
    $_SESSION['admin_done']  = true;

    return ['ok' => true, 'message' => "Admin user <strong>{$email}</strong> created successfully."];
}

function ajaxFinalize(): array
{
    if (empty($_SESSION['db'])) {
        return ['ok' => false, 'message' => 'No database credentials. Please restart.'];
    }

    $db     = $_SESSION['db'];
    $appUrl = rtrim(trim($_POST['app_url'] ?? ''), '/');
    $appEnv = ($_POST['app_env'] ?? 'production') === 'development' ? 'development' : 'production';
    $appDebug = $appEnv === 'development' ? 'true' : 'false';

    // ---- Write app/env.php ----
    $envContent = "<?php\n";
    $envContent .= "/**\n * BnkApp — Environment Configuration\n";
    $envContent .= " * Generated by install.php on " . date('Y-m-d H:i:s') . "\n";
    $envContent .= " * DO NOT commit this file to version control.\n */\n\n";
    $envContent .= "putenv('DB_HOST="     . addslashes($db['host'])    . "');\n";
    $envContent .= "putenv('DB_PORT="     . (int)$db['port']           . "');\n";
    $envContent .= "putenv('DB_NAME="     . addslashes($db['name'])    . "');\n";
    $envContent .= "putenv('DB_USER="     . addslashes($db['user'])    . "');\n";
    $envContent .= "putenv('DB_PASSWORD=" . addslashes($db['pass'])    . "');\n";
    $envContent .= "putenv('DB_PASS="     . addslashes($db['pass'])    . "');\n"; // alias for portal
    $envContent .= "putenv('APP_ENV="     . $appEnv                    . "');\n";
    $envContent .= "putenv('APP_DEBUG="   . $appDebug                  . "');\n";
    if ($appUrl) {
        $envContent .= "putenv('APP_URL="  . addslashes($appUrl)       . "');\n";
    }
    $envContent .= "putenv('LOG_LEVEL="   . ($appEnv === 'development' ? 'debug' : 'warning') . "');\n";

    $envWritten = (bool)file_put_contents(ENV_FILE, $envContent);
    if (!$envWritten) {
        return ['ok' => false, 'message' => 'Could not write ' . ENV_FILE . '. Check write permissions on app/.'];
    }
    @chmod(ENV_FILE, 0640);

    // ---- Create storage/logs directories ----
    foreach (['app/admin/storage/logs', 'app/portal/storage/logs'] as $dir) {
        $path = BASE_DIR . '/' . $dir;
        if (!is_dir($path)) {
            @mkdir($path, 0750, true);
        }
    }

    // ---- Write install.lock ----
    $lockContent = json_encode([
        'installed_at' => date('c'),
        'installed_by' => $_SESSION['admin_email'] ?? 'unknown',
        'php_version'  => PHP_VERSION,
    ], JSON_PRETTY_PRINT);
    file_put_contents(LOCK_FILE, $lockContent);

    // Clear session
    $_SESSION = [];
    session_destroy();

    return ['ok' => true, 'message' => 'Installation complete.'];
}

// ============================================================================
// SQL PARSER
// ============================================================================

/**
 * Parse a MySQL SQL dump into individual executable statements.
 * Handles DELIMITER directives used in stored routines.
 */
function parseSqlStatements(string $sql): array
{
    $statements = [];
    $delimiter  = ';';
    $buffer     = '';

    // Normalise line endings
    $sql   = str_replace("\r\n", "\n", $sql);
    $lines = explode("\n", $sql);

    foreach ($lines as $line) {
        // DELIMITER directive — must appear on its own line
        if (preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i', $line, $m)) {
            $delimiter = $m[1];
            continue;
        }

        $buffer .= $line . "\n";

        // Does the accumulated buffer end with the current delimiter?
        $stripped = rtrim($buffer);
        $dlen     = strlen($delimiter);

        if ($dlen > 0 && substr($stripped, -$dlen) === $delimiter) {
            $stmt = trim(substr($stripped, 0, strlen($stripped) - $dlen));

            // Add any non-empty statement (comments inside routines are fine)
            if ($stmt !== '') {
                $statements[] = $stmt;
            }

            $buffer = '';
        }
    }

    // Flush remaining buffer
    $remaining = trim($buffer);
    if ($remaining !== '') {
        $statements[] = $remaining;
    }

    return $statements;
}

// ============================================================================
// REQUIREMENTS CHECK
// ============================================================================

function checkRequirements(): array
{
    $results = [];

    // PHP version
    $phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
    $results['php_version'] = [
        'label' => 'PHP Version (≥ 8.0)',
        'value' => PHP_VERSION,
        'ok'    => $phpOk,
        'note'  => $phpOk ? '' : 'PHP 8.0 or higher is required.',
    ];

    // Required extensions
    $exts = [
        'pdo'       => 'PDO',
        'pdo_mysql' => 'PDO MySQL driver',
        'mbstring'  => 'Multibyte string (mbstring)',
        'openssl'   => 'OpenSSL',
        'json'      => 'JSON',
        'session'   => 'Session',
        'filter'    => 'Filter',
        'hash'      => 'Hash',
        'pcre'      => 'PCRE (regex)',
    ];
    foreach ($exts as $ext => $label) {
        $ok = extension_loaded($ext);
        $results['ext_' . $ext] = [
            'label' => $label . ' extension',
            'value' => $ok ? 'Loaded' : 'MISSING',
            'ok'    => $ok,
            'note'  => $ok ? '' : "Install / enable the {$ext} PHP extension.",
        ];
    }

    // File/directory write permissions
    $paths = [
        APP_DIR . '/admin/config'       => 'app/admin/config/  (for config.local.php)',
        APP_DIR . '/portal/config'      => 'app/portal/config/ (for config.local.php)',
        APP_DIR . '/admin/storage/logs' => 'app/admin/storage/logs/',
        APP_DIR . '/portal/storage/logs'=> 'app/portal/storage/logs/',
        APP_DIR                         => 'app/  (for env.php)',
        BASE_DIR                        => 'Root dir (for install.lock)',
    ];
    foreach ($paths as $path => $label) {
        // Ensure the directory exists before checking
        if (!is_dir($path)) {
            @mkdir($path, 0750, true);
        }
        $ok = is_writable($path);
        $results['perm_' . md5($path)] = [
            'label' => $label,
            'value' => $ok ? 'Writable' : 'NOT WRITABLE',
            'ok'    => $ok,
            'note'  => $ok ? '' : "Run: sudo chown www-data:www-data {$path}",
        ];
    }

    // MySQL connectivity (just checks if the driver is present)
    $mysqlOk = in_array('mysql', PDO::getAvailableDrivers(), true);
    $results['mysql_driver'] = [
        'label' => 'MySQL PDO driver',
        'value' => $mysqlOk ? 'Available' : 'MISSING',
        'ok'    => $mysqlOk,
        'note'  => $mysqlOk ? '' : 'Install php-mysql or php8.x-mysql package.',
    ];

    return $results;
}

function allRequirementsMet(array $checks): bool
{
    foreach ($checks as $c) {
        if (!$c['ok']) return false;
    }
    return true;
}

// ============================================================================
// NGINX CONFIG GENERATOR
// ============================================================================

function nginxConfig(): string
{
    $base = '/var/www/bnkapp';
    return <<<NGINX
# ── HTTP → HTTPS redirect ──────────────────────────────────────────────────────
server {
    listen 80;
    listen [::]:80;
    server_name admin.yourdomain.com portal.yourdomain.com;

    # Allow Let's Encrypt ACME challenges before redirecting
    # The directory /var/www/letsencrypt must exist and be readable by Nginx.
    # Create it with: sudo mkdir -p /var/www/letsencrypt
    # Certbot webroot plugin uses it with: --webroot -w /var/www/letsencrypt
    location /.well-known/acme-challenge/ {
        root /var/www/letsencrypt;
        allow all;
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}

# ── Admin Panel ────────────────────────────────────────────────────────────────
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name admin.yourdomain.com;

    root {$base}/app/admin/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/admin.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/admin.yourdomain.com/privkey.pem;
    include             /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam         /etc/letsencrypt/ssl-dhparams.pem;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php\$ {
        fastcgi_pass        unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index       index.php;
        fastcgi_param       SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include             fastcgi_params;
    }

    location ~ /\\. { deny all; }
}

# ── Customer Portal ─────────────────────────────────────────────────────────────
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name portal.yourdomain.com;

    root {$base}/app/portal/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/portal.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/portal.yourdomain.com/privkey.pem;
    include             /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam         /etc/letsencrypt/ssl-dhparams.pem;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php\$ {
        fastcgi_pass        unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index       index.php;
        fastcgi_param       SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include             fastcgi_params;
    }

    location ~ /\\. { deny all; }
}
NGINX;
}

// ============================================================================
// HTML RENDERING
// ============================================================================

function renderLocked(): string
{
    return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>BnkApp — Already Installed</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-dark text-white d-flex align-items-center justify-content-center vh-100">
  <div class="text-center p-5">
    <div class="fs-1 mb-3">🔒</div>
    <h1 class="h3 mb-3">BnkApp is already installed</h1>
    <p class="text-secondary">
      The installer is locked. To re-run it, delete <code>install.lock</code>
      from the project root and remove <code>app/env.php</code>.
    </p>
    <p class="text-danger fw-bold">Warning: re-installing will overwrite your configuration.</p>
  </div>
</body>
</html>
HTML;
}

function renderPage(int $step): string
{
    $checks   = checkRequirements();
    $allOk    = allRequirementsMet($checks);
    $dbSaved  = !empty($_SESSION['db']);
    $migDone  = !empty($_SESSION['migrations_done']);
    $admDone  = !empty($_SESSION['admin_done']);

    $stepLabels = [
        1 => 'Requirements',
        2 => 'Database',
        3 => 'Migrations',
        4 => 'Admin User',
        5 => 'Finish',
    ];

    // Build step progress indicators
    $stepsHtml = '';
    foreach ($stepLabels as $n => $label) {
        $cls = 'wizard-step';
        if ($n < $step)  $cls .= ' done';
        if ($n === $step) $cls .= ' active';
        $stepsHtml .= "<div class=\"{$cls}\"><span class=\"step-num\">{$n}</span><span class=\"step-label\">{$label}</span></div>";
        if ($n < count($stepLabels)) {
            $stepsHtml .= '<div class="wizard-connector' . ($n < $step ? ' done' : '') . '"></div>';
        }
    }

    $bodyHtml = match ($step) {
        1 => renderStep1($checks, $allOk),
        2 => renderStep2($dbSaved),
        3 => renderStep3($dbSaved, $migDone),
        4 => renderStep4($migDone, $admDone),
        5 => renderStep5($admDone),
        default => renderStep1($checks, $allOk),
    };

    return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>BnkApp — Installation Wizard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    :root {
      --brand: #0d6efd;
      --brand-dark: #0a58ca;
    }
    body { background: #0f1117; color: #e2e8f0; font-family: system-ui, sans-serif; }
    .installer-wrap { max-width: 860px; margin: 0 auto; padding: 2rem 1rem; }
    .installer-header { text-align: center; margin-bottom: 2.5rem; }
    .installer-header .logo { font-size: 2.5rem; font-weight: 800; color: var(--brand); letter-spacing: -1px; }
    .installer-header p  { color: #94a3b8; margin: 0; }

    /* Step progress bar */
    .wizard-progress { display: flex; align-items: center; justify-content: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 4px; }
    .wizard-step { display: flex; flex-direction: column; align-items: center; min-width: 90px; }
    .step-num {
      width: 36px; height: 36px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: .85rem;
      background: #1e2430; border: 2px solid #334155; color: #64748b;
      transition: all .25s;
    }
    .wizard-step.done  .step-num { background: #166534; border-color: #22c55e; color: #86efac; }
    .wizard-step.active .step-num { background: var(--brand); border-color: var(--brand-dark); color: #fff; box-shadow: 0 0 0 4px rgba(13,110,253,.25); }
    .step-label { font-size: .7rem; color: #64748b; margin-top: 4px; text-transform: uppercase; letter-spacing: .5px; }
    .wizard-step.active .step-label { color: #93c5fd; }
    .wizard-connector { flex: 1; min-width: 20px; max-width: 60px; height: 2px; background: #334155; }
    .wizard-connector.done { background: #22c55e; }

    /* Card */
    .installer-card { background: #1a1f2e; border: 1px solid #2d3748; border-radius: 12px; padding: 2rem; }
    .installer-card h2 { font-size: 1.3rem; font-weight: 700; color: #f1f5f9; margin-bottom: 1.5rem; display: flex; align-items: center; gap: .5rem; }

    /* Requirement rows */
    .req-row { display: flex; align-items: center; gap: .5rem; padding: .45rem 0; border-bottom: 1px solid #1e2430; font-size: .9rem; }
    .req-row:last-child { border-bottom: none; }
    .req-label { flex: 1; color: #cbd5e1; }
    .req-value { color: #94a3b8; font-family: monospace; font-size: .8rem; margin-right: .5rem; }
    .badge-ok  { background: #166534; color: #bbf7d0; }
    .badge-err { background: #7f1d1d; color: #fca5a5; }

    /* Forms */
    .form-label { color: #94a3b8; font-size: .85rem; margin-bottom: .3rem; }
    .form-control, .form-select {
      background: #0f1117; border-color: #2d3748; color: #e2e8f0;
    }
    .form-control:focus, .form-select:focus {
      background: #0f1117; border-color: var(--brand); color: #e2e8f0;
      box-shadow: 0 0 0 3px rgba(13,110,253,.2);
    }
    .form-control::placeholder { color: #475569; }
    .input-group-text { background: #1e2430; border-color: #2d3748; color: #64748b; }

    /* Buttons */
    .btn-primary { background: var(--brand); border-color: var(--brand-dark); }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline-secondary { border-color: #334155; color: #94a3b8; }
    .btn-outline-secondary:hover { background: #1e2430; color: #e2e8f0; border-color: #475569; }

    /* Alert boxes */
    .alert-dark-success { background: #14532d; border-color: #166534; color: #bbf7d0; }
    .alert-dark-danger  { background: #450a0a; border-color: #7f1d1d; color: #fca5a5; }
    .alert-dark-info    { background: #0c1a2e; border-color: #1e3a5f; color: #93c5fd; }
    .alert-dark-warn    { background: #431407; border-color: #7c2d12; color: #fdba74; }

    /* Migration results */
    .mig-file { background: #0f1117; border: 1px solid #2d3748; border-radius: 8px; padding: 1rem; margin-bottom: .75rem; }
    .mig-file-header { display: flex; align-items: center; gap: .5rem; font-weight: 600; margin-bottom: .3rem; }
    .mig-errors { font-size: .78rem; font-family: monospace; color: #f87171; margin-top: .4rem; }

    /* Code block */
    pre.nginx-conf { background: #020617; border: 1px solid #1e3a5f; border-radius: 8px; padding: 1.25rem; color: #7dd3fc; font-size: .78rem; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }

    /* Password strength */
    #pwd-strength-bar { height: 4px; border-radius: 2px; transition: all .3s; background: #334155; }

    /* Spinner */
    .spinner-sm { width: 1rem; height: 1rem; border-width: 2px; }
  </style>
</head>
<body>
<div class="installer-wrap">
  <div class="installer-header">
    <div class="logo">🏦 BnkApp</div>
    <p>Professional Banking System — Installation Wizard</p>
  </div>

  <div class="wizard-progress">
    {$stepsHtml}
  </div>

  <div class="installer-card">
    {$bodyHtml}
  </div>

  <p class="text-center text-muted mt-3" style="font-size:.75rem;">
    BnkApp v1.0.0 &mdash; PHP {PHP_VERSION} &mdash; 
    <a href="https://github.com/berndmarcel860-byte/Bnkapp" class="text-secondary">GitHub</a>
  </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Utility ──────────────────────────────────────────────────────────────────
function setLoading(btn, loading) {
  if (loading) {
    btn.dataset.origText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-sm me-2" role="status"></span>Working…';
    btn.disabled  = true;
  } else {
    btn.innerHTML = btn.dataset.origText || btn.innerHTML;
    btn.disabled  = false;
  }
}

function showAlert(containerId, ok, html) {
  const el = document.getElementById(containerId);
  if (!el) return;
  const cls = ok ? 'alert-dark-success' : 'alert-dark-danger';
  el.innerHTML = `<div class="alert \${cls} rounded-3 py-2 px-3 mt-3">\${html}</div>`;
}

// ── Step 2: Test DB connection ────────────────────────────────────────────────
const testBtn = document.getElementById('btn-test-db');
if (testBtn) {
  testBtn.addEventListener('click', async () => {
    setLoading(testBtn, true);
    const form = document.getElementById('form-db');
    const data = new FormData(form);
    try {
      const res  = await fetch('?action=test_db', { method: 'POST', body: data });
      const json = await res.json();
      showAlert('db-result', json.ok, json.message);
      if (json.ok) {
        document.getElementById('btn-next-db').disabled = false;
      }
    } catch(e) {
      showAlert('db-result', false, 'Network error: ' + e.message);
    } finally {
      setLoading(testBtn, false);
    }
  });
}

// ── Step 3: Run migrations ────────────────────────────────────────────────────
const migrateBtn = document.getElementById('btn-migrate');
if (migrateBtn) {
  migrateBtn.addEventListener('click', async () => {
    setLoading(migrateBtn, true);
    document.getElementById('mig-results').innerHTML = '';
    try {
      const res  = await fetch('?action=migrate', { method: 'POST' });
      const json = await res.json();

      let html = '';
      if (json.results && json.results.length) {
        json.results.forEach(r => {
          const icon = r.ok ? '✅' : '❌';
          let errs = '';
          if (r.errors && r.errors.length) {
            errs = '<div class="mig-errors">' + r.errors.map(e => '⚠ ' + e).join('<br>') + '</div>';
          }
          html += `<div class="mig-file">
            <div class="mig-file-header">\${icon} <code>\${r.file}</code> — \${r.label}</div>
            \${errs}
          </div>`;
        });
      }
      document.getElementById('mig-results').innerHTML = html;

      showAlert('mig-alert', json.ok, json.message);
      if (json.ok) {
        document.getElementById('btn-next-mig').disabled = false;
      }
    } catch(e) {
      showAlert('mig-alert', false, 'Network error: ' + e.message);
    } finally {
      setLoading(migrateBtn, false);
    }
  });
}

// ── Step 4: Create admin ──────────────────────────────────────────────────────
const adminForm = document.getElementById('form-admin');
const adminBtn  = document.getElementById('btn-create-admin');
if (adminForm && adminBtn) {
  adminBtn.addEventListener('click', async () => {
    setLoading(adminBtn, true);
    const data = new FormData(adminForm);
    try {
      const res  = await fetch('?action=create_admin', { method: 'POST', body: data });
      const json = await res.json();
      showAlert('admin-result', json.ok, json.message);
      if (json.ok) {
        document.getElementById('btn-next-admin').disabled = false;
      }
    } catch(e) {
      showAlert('admin-result', false, 'Network error: ' + e.message);
    } finally {
      setLoading(adminBtn, false);
    }
  });
}

// ── Password strength meter ───────────────────────────────────────────────────
const pwdInput = document.getElementById('password');
if (pwdInput) {
  pwdInput.addEventListener('input', () => {
    const v = pwdInput.value;
    let score = 0;
    if (v.length >= 12)  score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const colours = ['#334155','#ef4444','#f97316','#eab308','#22c55e'];
    const bar = document.getElementById('pwd-strength-bar');
    bar.style.width  = (score * 25) + '%';
    bar.style.background = colours[score];
  });
}

// ── Step 5: Finalize ──────────────────────────────────────────────────────────
const finalBtn  = document.getElementById('btn-finalize');
if (finalBtn) {
  finalBtn.addEventListener('click', async () => {
    setLoading(finalBtn, true);
    const form = document.getElementById('form-finalize');
    const data = new FormData(form);
    try {
      const res  = await fetch('?action=finalize', { method: 'POST', body: data });
      const json = await res.json();
      if (json.ok) {
        document.getElementById('finalize-form-section').style.display = 'none';
        document.getElementById('finalize-success').style.display       = 'block';
      } else {
        showAlert('finalize-alert', false, json.message);
      }
    } catch(e) {
      showAlert('finalize-alert', false, 'Network error: ' + e.message);
    } finally {
      setLoading(finalBtn, false);
    }
  });
}

// ── Nginx copy button ─────────────────────────────────────────────────────────
const copyBtn = document.getElementById('btn-copy-nginx');
if (copyBtn) {
  copyBtn.addEventListener('click', () => {
    const text = document.getElementById('nginx-conf-block').innerText;
    navigator.clipboard.writeText(text).then(() => {
      copyBtn.textContent = '✅ Copied!';
      setTimeout(() => copyBtn.textContent = 'Copy', 2000);
    });
  });
}
</script>
</body>
</html>
HTML;
}

// ============================================================================
// STEP RENDERERS
// ============================================================================

function renderStep1(array $checks, bool $allOk): string
{
    $rows = '';
    foreach ($checks as $c) {
        $badge = $c['ok']
            ? '<span class="badge badge-ok rounded-pill px-2">OK</span>'
            : '<span class="badge badge-err rounded-pill px-2">FAIL</span>';
        $note  = $c['note'] ? '<small class="d-block text-warning mt-1">' . htmlspecialchars($c['note']) . '</small>' : '';
        $rows .= <<<ROW
<div class="req-row">
  <span class="req-label">{$c['label']}{$note}</span>
  <span class="req-value">{$c['value']}</span>
  {$badge}
</div>
ROW;
    }

    $statusAlert = $allOk
        ? '<div class="alert alert-dark-success rounded-3 py-2 px-3 mt-3"><i class="bi bi-check-circle-fill me-2"></i>All requirements met. You can proceed.</div>'
        : '<div class="alert alert-dark-danger rounded-3 py-2 px-3 mt-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>Some requirements are not met. Fix the issues above before continuing.</div>';

    $disabled = $allOk ? '' : 'disabled="disabled"';
    $nextUrl  = '?step=2';

    return <<<HTML
<h2><i class="bi bi-clipboard2-check"></i> Step 1 — System Requirements</h2>

<p class="text-secondary mb-3" style="font-size:.9rem;">
  BnkApp requires <strong>PHP 8.0+</strong>, MySQL 8.0+, and a few PHP extensions.
  All items below must show <span class="badge badge-ok px-2">OK</span> before you continue.
</p>

{$rows}

{$statusAlert}

<div class="mt-4 d-flex justify-content-end">
  <a href="{$nextUrl}" class="btn btn-primary px-4 {$disabled}">
    Next: Database &rarr;
  </a>
</div>
HTML;
}

function renderStep2(bool $dbSaved): string
{
    $savedNotice = $dbSaved
        ? '<div class="alert alert-dark-info rounded-3 py-2 px-3 mb-3"><i class="bi bi-info-circle me-2"></i>Database credentials were previously saved. You can update them below.</div>'
        : '';

    $db   = $_SESSION['db'] ?? [];
    $host = htmlspecialchars($db['host'] ?? '127.0.0.1');
    $port = htmlspecialchars((string)($db['port'] ?? '3306'));
    $name = htmlspecialchars($db['name'] ?? 'bnkapp');
    $user = htmlspecialchars($db['user'] ?? '');
    $nextDisabled = $dbSaved ? '' : 'disabled';

    return <<<HTML
<h2><i class="bi bi-database"></i> Step 2 — Database Configuration</h2>

<p class="text-secondary mb-3" style="font-size:.9rem;">
  Enter your MySQL connection details. The installer will test the connection and
  create the database if it does not exist. The application user must have
  <code>CREATE, DROP, ALTER, INSERT, UPDATE, SELECT, EXECUTE</code> privileges.
  If the user does not exist yet or receives <em>Access denied</em>, expand
  <strong>Grant privileges</strong> below to let the installer create the user and
  grant the required permissions using a privileged account (e.g.&nbsp;<code>root</code>).
</p>

{$savedNotice}

<form id="form-db" autocomplete="off">
  <div class="row g-3 mb-3">
    <div class="col-md-8">
      <label class="form-label" for="db_host">Database Host</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-hdd-network"></i></span>
        <input type="text" class="form-control" id="db_host" name="db_host"
               value="{$host}" placeholder="127.0.0.1 or localhost" required>
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="db_port">Port</label>
      <input type="number" class="form-control" id="db_port" name="db_port"
             value="{$port}" min="1" max="65535" required>
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label" for="db_name">Database Name</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-table"></i></span>
      <input type="text" class="form-control" id="db_name" name="db_name"
             value="{$name}" placeholder="bnkapp" required>
    </div>
    <small class="text-muted">Will be created automatically if it does not exist.</small>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label" for="db_user">Database Username</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-person"></i></span>
        <input type="text" class="form-control" id="db_user" name="db_user"
               value="{$user}" placeholder="bnkapp" required>
      </div>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="db_pass">Database Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-key"></i></span>
        <input type="password" class="form-control" id="db_pass" name="db_pass"
               placeholder="Leave blank if no password">
        <button class="btn btn-outline-secondary" type="button"
                onclick="const i=this.previousElementSibling;i.type=i.type==='password'?'text':'password'">
          <i class="bi bi-eye"></i>
        </button>
      </div>
    </div>
  </div>

  <hr class="my-3">

  <div class="mb-2">
    <button class="btn btn-sm btn-outline-secondary" type="button"
            data-bs-toggle="collapse" data-bs-target="#admin-creds-section"
            aria-expanded="false" aria-controls="admin-creds-section">
      <i class="bi bi-shield-lock me-1"></i> Optional: Grant privileges with a MySQL admin account
    </button>
  </div>

  <div class="collapse" id="admin-creds-section">
    <div class="card card-body bg-dark border-secondary mb-3">
      <p class="text-muted small mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Provide a <strong>privileged</strong> MySQL account (e.g.&nbsp;<code>root</code>).
        The installer will run <code>CREATE USER IF NOT EXISTS</code> and
        <code>GRANT ALL PRIVILEGES ON <em>database</em>.* TO <em>user</em></code>
        so the application user can connect.
        These credentials are used only during this test and are never stored.
      </p>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="admin_user">Admin Username</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person-fill-gear"></i></span>
            <input type="text" class="form-control" id="admin_user" name="admin_user"
                   placeholder="root" autocomplete="off">
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="admin_pass">Admin Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
            <input type="password" class="form-control" id="admin_pass" name="admin_pass"
                   placeholder="Admin password" autocomplete="new-password">
            <button class="btn btn-outline-secondary" type="button"
                    onclick="const i=this.previousElementSibling;i.type=i.type==='password'?'text':'password'">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<div id="db-result"></div>

<div class="mt-4 d-flex justify-content-between">
  <a href="?step=1" class="btn btn-outline-secondary">&larr; Back</a>
  <div class="d-flex gap-2">
    <button id="btn-test-db" class="btn btn-outline-primary">
      <i class="bi bi-plug me-1"></i> Test Connection
    </button>
    <a id="btn-next-db" href="?step=3" class="btn btn-primary px-4" {$nextDisabled}>
      Next: Migrations &rarr;
    </a>
  </div>
</div>
HTML;
}

function renderStep3(bool $dbSaved, bool $migDone): string
{
    if (!$dbSaved) {
        return '<div class="alert alert-dark-danger">Please complete Step 2 first.</div>'
             . '<a href="?step=2" class="btn btn-outline-secondary mt-3">&larr; Back</a>';
    }

    $db      = $_SESSION['db'];
    $dbLabel = htmlspecialchars("{$db['user']}@{$db['host']}:{$db['port']}/{$db['name']}");

    $doneNotice = $migDone
        ? '<div class="alert alert-dark-success rounded-3 py-2 px-3 mb-3"><i class="bi bi-check-circle-fill me-2"></i>Migrations already completed. Click Next to continue or re-run to refresh.</div>'
        : '';

    $nextDisabled = $migDone ? '' : 'disabled';

    $files = [
        ['01_schema.sql',          'Schema — 27 tables'],
        ['02_functions.sql',       'Functions & stored procedures'],
        ['03_indexes.sql',         'Performance indexes'],
        ['04_seed.sql',            'Seed data (roles, permissions, SEPA countries)'],
        ['05_email_templates.sql', 'Email templates'],
        ['06_smtp_settings.sql',   'SMTP settings table'],
    ];
    $fileList = '';
    foreach ($files as [$f, $d]) {
        $exists = file_exists(DB_DIR . '/' . $f);
        $icon   = $exists ? '📄' : '❌';
        $fileList .= "<li class=\"mb-1\">{$icon} <code>{$f}</code> — {$d}</li>";
    }

    return <<<HTML
<h2><i class="bi bi-lightning-charge"></i> Step 3 — Database Migrations</h2>

<p class="text-secondary mb-3" style="font-size:.9rem;">
  The following SQL files will be executed against <strong>{$dbLabel}</strong>
  in order. This is safe to run multiple times — <code>CREATE TABLE IF NOT EXISTS</code>
  and similar idempotent statements are used throughout.
</p>

{$doneNotice}

<ul class="list-unstyled ps-2 mb-4">
  {$fileList}
</ul>

<button id="btn-migrate" class="btn btn-primary">
  <i class="bi bi-play-circle me-1"></i> Run Migrations
</button>

<div id="mig-alert"></div>
<div id="mig-results" class="mt-3"></div>

<div class="mt-4 d-flex justify-content-between">
  <a href="?step=2" class="btn btn-outline-secondary">&larr; Back</a>
  <a id="btn-next-mig" href="?step=4" class="btn btn-primary px-4" {$nextDisabled}>
    Next: Admin User &rarr;
  </a>
</div>
HTML;
}

function renderStep4(bool $migDone, bool $admDone): string
{
    if (!$migDone) {
        return '<div class="alert alert-dark-danger">Please complete Step 3 (migrations) first.</div>'
             . '<a href="?step=3" class="btn btn-outline-secondary mt-3">&larr; Back</a>';
    }

    $doneNotice = $admDone
        ? '<div class="alert alert-dark-success rounded-3 py-2 px-3 mb-3"><i class="bi bi-check-circle-fill me-2"></i>Admin user already created. You can proceed to Step 5.</div>'
        : '';

    $nextDisabled = $admDone ? '' : 'disabled';

    return <<<HTML
<h2><i class="bi bi-person-badge"></i> Step 4 — Create Administrator</h2>

<p class="text-secondary mb-3" style="font-size:.9rem;">
  Create the first <strong>super_admin</strong> account. You will use these credentials
  to log in to the admin panel after installation.
</p>

{$doneNotice}

<form id="form-admin" autocomplete="off">
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label" for="first_name">First Name</label>
      <input type="text" class="form-control" id="first_name" name="first_name"
             placeholder="Jane" required>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="last_name">Last Name</label>
      <input type="text" class="form-control" id="last_name" name="last_name"
             placeholder="Smith" required>
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label" for="email">Email Address</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-envelope"></i></span>
      <input type="email" class="form-control" id="email" name="email"
             placeholder="admin@yourdomain.com" required>
    </div>
  </div>

  <div class="mb-1">
    <label class="form-label" for="password">Password <small class="text-muted">(min 12 characters)</small></label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-lock"></i></span>
      <input type="password" class="form-control" id="password" name="password"
             placeholder="Strong password" minlength="12" required>
      <button class="btn btn-outline-secondary" type="button"
              onclick="const i=this.previousElementSibling;i.type=i.type==='password'?'text':'password'">
        <i class="bi bi-eye"></i>
      </button>
    </div>
    <div class="mt-1" style="background:#1e2430;border-radius:2px;height:4px;">
      <div id="pwd-strength-bar" style="width:0%"></div>
    </div>
    <small class="text-muted">Use uppercase, numbers, and symbols for a strong password.</small>
  </div>

  <div class="mb-3 mt-3">
    <label class="form-label" for="password_confirm">Confirm Password</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
      <input type="password" class="form-control" id="password_confirm" name="password_confirm"
             placeholder="Repeat password" minlength="12" required>
    </div>
  </div>
</form>

<div id="admin-result"></div>

<div class="mt-4 d-flex justify-content-between">
  <a href="?step=3" class="btn btn-outline-secondary">&larr; Back</a>
  <div class="d-flex gap-2">
    <button id="btn-create-admin" class="btn btn-primary">
      <i class="bi bi-person-plus me-1"></i> Create Admin User
    </button>
    <a id="btn-next-admin" href="?step=5" class="btn btn-success px-4" {$nextDisabled}>
      Next: Finish &rarr;
    </a>
  </div>
</div>
HTML;
}

function renderStep5(bool $admDone): string
{
    if (!$admDone) {
        return '<div class="alert alert-dark-danger">Please complete Step 4 (admin user) first.</div>'
             . '<a href="?step=4" class="btn btn-outline-secondary mt-3">&larr; Back</a>';
    }

    $nginx = htmlspecialchars(nginxConfig(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return <<<HTML
<h2><i class="bi bi-flag-fill text-success"></i> Step 5 — Finalize Installation</h2>

<p class="text-secondary mb-3" style="font-size:.9rem;">
  Almost done! Configure a few last settings, then click <strong>Complete Installation</strong>.
  BnkApp will write <code>app/env.php</code> (environment variables) and
  lock the installer so it cannot be run again.
</p>

<div id="finalize-form-section">
  <form id="form-finalize" autocomplete="off">
    <div class="mb-3">
      <label class="form-label" for="app_url">Application Base URL <small class="text-muted">(optional)</small></label>
      <input type="url" class="form-control" id="app_url" name="app_url"
             placeholder="https://admin.yourdomain.com">
      <small class="text-muted">Used in links / emails. Leave blank if using environment variables.</small>
    </div>
    <div class="mb-4">
      <label class="form-label" for="app_env">Environment</label>
      <select class="form-select" id="app_env" name="app_env">
        <option value="production" selected>Production (recommended)</option>
        <option value="development">Development (enables debug output — NOT for production)</option>
      </select>
    </div>
  </form>

  <div class="alert alert-dark-warn rounded-3 py-2 px-3 mb-3">
    <i class="bi bi-shield-exclamation me-2"></i>
    <strong>Security reminder:</strong> After installation, delete or restrict access to
    <code>install.php</code> at the web-server level. The installer will create
    <code>install.lock</code> which prevents re-runs, but removing <code>install.php</code>
    is safer.
  </div>

  <div id="finalize-alert"></div>

  <div class="mt-3 d-flex justify-content-between">
    <a href="?step=4" class="btn btn-outline-secondary">&larr; Back</a>
    <button id="btn-finalize" class="btn btn-success px-5">
      <i class="bi bi-check2-circle me-2"></i> Complete Installation
    </button>
  </div>
</div>

<!-- Success panel (hidden until finalize completes) -->
<div id="finalize-success" style="display:none;">
  <div class="alert alert-dark-success rounded-3 py-3 px-4 mb-4">
    <h5 class="mb-2"><i class="bi bi-check-circle-fill me-2"></i>Installation Complete!</h5>
    <p class="mb-1">✅ <code>app/env.php</code> written with your database credentials.</p>
    <p class="mb-1">✅ <code>install.lock</code> created — the wizard is now locked.</p>
    <p class="mb-0">✅ You can now log in to the admin panel.</p>
  </div>

  <h5 class="mb-3"><i class="bi bi-server me-2 text-info"></i>Nginx Configuration</h5>
  <p class="text-secondary" style="font-size:.85rem;">
    Configure two virtual hosts in Nginx — one for the admin panel, one for the customer portal.
    Replace <code>/var/www/bnkapp</code> with the actual path to your BnkApp directory and
    <code>yourdomain.com</code> with your real domain names.
    Adjust the PHP-FPM socket path if you use a different PHP version
    (e.g. <code>php8.3-fpm.sock</code> is used below for PHP 8.3).
  </p>
  <div class="d-flex justify-content-end mb-2">
    <button id="btn-copy-nginx" class="btn btn-outline-secondary btn-sm">Copy</button>
  </div>
  <pre id="nginx-conf-block" class="nginx-conf">{$nginx}</pre>

  <div class="mt-4 p-4" style="background:#0c1a2e;border-radius:8px;border:1px solid #1e3a5f;">
    <h6 class="text-info mb-3"><i class="bi bi-list-check me-2"></i>Next Steps</h6>
    <ol class="text-secondary" style="font-size:.9rem;line-height:2;">
      <li>Copy the Nginx config above into <code>/etc/nginx/sites-available/bnkapp.conf</code></li>
      <li>Replace <code>admin.yourdomain.com</code> and <code>portal.yourdomain.com</code> with your real domains</li>
      <li>Enable it: <code>sudo ln -s /etc/nginx/sites-available/bnkapp.conf /etc/nginx/sites-enabled/</code></li>
      <li>Create the Let's Encrypt webroot directory: <code>sudo mkdir -p /var/www/letsencrypt</code></li>
      <li>Test: <code>sudo nginx -t</code> then reload: <code>sudo systemctl reload nginx</code></li>
      <li>Update DNS to point your domains to this server</li>
      <li>Obtain TLS certificates via Certbot:
        <code>sudo certbot --nginx -d admin.yourdomain.com -d portal.yourdomain.com</code>
      </li>
      <li>Log in to the admin panel at <strong>https://admin.yourdomain.com/auth/login</strong></li>
      <li><strong class="text-warning">Delete or restrict access to <code>install.php</code></strong></li>
    </ol>
  </div>
</div>
HTML;
}

// Inline the disabled attribute properly for step 2
// (PHP closure used inside heredoc workaround)
function buildDisabled(bool $condition): string
{
    return $condition ? 'disabled' : '';
}
