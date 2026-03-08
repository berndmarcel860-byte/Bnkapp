#!/usr/bin/env bash
# BnkApp — Pre-installer permission setup
# ============================================================================
# Run this script once as root (or with sudo) after cloning the repository and
# BEFORE visiting the web installer.  It transfers ownership of the directories
# that the web server must be able to write to during installation and at runtime.
#
# Usage:
#   sudo bash deploy/setup-permissions.sh [DEPLOY_DIR] [WEB_USER]
#
# Defaults:
#   DEPLOY_DIR = /var/www/bnkapp
#   WEB_USER   = www-data         (use "nginx" for nginx-only setups)
#
# Examples:
#   sudo bash deploy/setup-permissions.sh
#   sudo bash deploy/setup-permissions.sh /var/www/bnkapp www-data
# ============================================================================

set -euo pipefail

DEPLOY_DIR="${1:-/var/www/bnkapp}"
WEB_USER="${2:-www-data}"

# ── Validate inputs ────────────────────────────────────────────────────────────
if [[ ! -d "${DEPLOY_DIR}" ]]; then
    echo "ERROR: Deploy directory '${DEPLOY_DIR}' does not exist." >&2
    exit 1
fi

if ! id -u "${WEB_USER}" &>/dev/null; then
    echo "ERROR: Web server user '${WEB_USER}' does not exist on this system." >&2
    echo "       Common values: www-data (Debian/Ubuntu), nginx (RHEL/CentOS)." >&2
    exit 1
fi

echo "Setting web-server ownership on writable paths …"
echo "  Deploy dir : ${DEPLOY_DIR}"
echo "  Web user   : ${WEB_USER}"
echo ""

# ── Directories the installer writes to ───────────────────────────────────────
# These are checked by install.php Step 1 (System Requirements).
# The installer writes:
#   • env.php          → app/
#   • install.lock     → project root
#   • config.local.php → app/admin/config/  and  app/portal/config/
#   • log files        → app/admin/storage/logs/  and  app/portal/storage/logs/
WRITABLE_DIRS=(
    "${DEPLOY_DIR}"
    "${DEPLOY_DIR}/app"
    "${DEPLOY_DIR}/app/admin/config"
    "${DEPLOY_DIR}/app/portal/config"
    "${DEPLOY_DIR}/app/admin/storage/logs"
    "${DEPLOY_DIR}/app/portal/storage/logs"
)

for dir in "${WRITABLE_DIRS[@]}"; do
    # Create the directory if it does not exist yet
    if [[ ! -d "${dir}" ]]; then
        mkdir -p "${dir}"
        echo "  [created]  ${dir}"
    fi
    chown "${WEB_USER}:${WEB_USER}" "${dir}"
    chmod 755 "${dir}"
    echo "  [ok]       ${dir}"
done

# ── Read-only paths — keep owned by root for security ─────────────────────────
# Everything else (source code, views, SQL files) stays root-owned with 755/644
# so the web process cannot modify application code even if compromised.

echo ""
echo "Done.  The following directories are now writable by '${WEB_USER}':"
for dir in "${WRITABLE_DIRS[@]}"; do
    ls -ld "${dir}"
done
echo ""
echo "Next steps:"
echo "  1. Start / reload nginx:  sudo systemctl reload nginx"
echo "  2. Visit the installer:   http://<your-domain>/install.php"
echo "  3. After installation:    sudo bash deploy/post-install-permissions.sh ${DEPLOY_DIR} ${WEB_USER}"
