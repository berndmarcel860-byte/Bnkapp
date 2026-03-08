#!/usr/bin/env bash
# BnkApp — Post-installer permission hardening
# ============================================================================
# Run this script once as root AFTER the web installer completes successfully.
# It restores stricter ownership/permissions now that env.php and install.lock
# have been written, so the web server no longer needs write access to most paths.
#
# Usage:
#   sudo bash deploy/post-install-permissions.sh [DEPLOY_DIR] [WEB_USER]
#
# Defaults:
#   DEPLOY_DIR = /var/www/bnkapp
#   WEB_USER   = www-data
#
# What it does:
#   • Returns ownership of the project root and app/ to root (source code
#     should not be modifiable by the web process).
#   • Keeps app/admin/storage/logs and app/portal/storage/logs writable so
#     the application can write log files at runtime.
#   • Sets env.php to 640 (root:www-data) so only root and the web server can
#     read credentials — other OS users cannot.
# ============================================================================

set -euo pipefail

DEPLOY_DIR="${1:-/var/www/bnkapp}"
WEB_USER="${2:-www-data}"

if [[ ! -d "${DEPLOY_DIR}" ]]; then
    echo "ERROR: Deploy directory '${DEPLOY_DIR}' does not exist." >&2
    exit 1
fi

if ! id -u "${WEB_USER}" &>/dev/null; then
    echo "ERROR: Web server user '${WEB_USER}' does not exist." >&2
    exit 1
fi

echo "Hardening permissions after installation …"
echo "  Deploy dir : ${DEPLOY_DIR}"
echo "  Web user   : ${WEB_USER}"
echo ""

# ── Return root-owned paths to root ───────────────────────────────────────────
for dir in \
    "${DEPLOY_DIR}" \
    "${DEPLOY_DIR}/app" \
    "${DEPLOY_DIR}/app/admin/config" \
    "${DEPLOY_DIR}/app/portal/config"
do
    chown root:root "${dir}"
    chmod 755 "${dir}"
    echo "  [locked]   ${dir}"
done

# ── Keep log directories writable by web server ────────────────────────────────
for dir in \
    "${DEPLOY_DIR}/app/admin/storage/logs" \
    "${DEPLOY_DIR}/app/portal/storage/logs"
do
    chown "${WEB_USER}:${WEB_USER}" "${dir}"
    chmod 755 "${dir}"
    echo "  [writable] ${dir}"
done

# ── Protect env.php (credentials) ─────────────────────────────────────────────
ENV_FILE="${DEPLOY_DIR}/app/env.php"
if [[ -f "${ENV_FILE}" ]]; then
    chown "root:${WEB_USER}" "${ENV_FILE}"
    chmod 640 "${ENV_FILE}"
    echo "  [640]      ${ENV_FILE}"
fi

# ── Protect install.lock ───────────────────────────────────────────────────────
LOCK_FILE="${DEPLOY_DIR}/install.lock"
if [[ -f "${LOCK_FILE}" ]]; then
    chown root:root "${LOCK_FILE}"
    chmod 644 "${LOCK_FILE}"
    echo "  [644]      ${LOCK_FILE}"
fi

echo ""
echo "Done.  The application is now running with hardened permissions."
echo "Log directories remain writable by '${WEB_USER}' for runtime logging."
