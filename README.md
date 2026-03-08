# BnkApp — Professional Banking System

A professional, SEPA-compliant banking platform supporting both customer
self-service and administrator back-office workflows.

## Features

- **User management** — registration, KYC/AML verification, 2FA, session management
- **Admin & staff roles** — super_admin, admin, manager, teller with granular permissions
- **SEPA IBAN generation** — ISO 7064 MOD-97-10 check-digit algorithm (all 34 SEPA countries)
- **Full account lifecycle** — open, deposit, withdraw, transfer, freeze, close
- **SEPA payments** — SCT, SCT Instant, SDD Core, SDD B2B (ISO 20022 aligned)
- **Cards** — debit, credit, prepaid (PAN/CVV stored as bcrypt hashes)
- **Loans** — application, approval, disbursement, amortisation schedule
- **Standing orders** — recurring SEPA Credit Transfers
- **Direct Debit mandates** — SDD CORE / SDD B2B mandate management
- **Beneficiary management** — saved/trusted payees
- **Fee schedules** — configurable flat + percentage fees with min/max caps
- **FX / exchange rates** — multi-currency support
- **Notifications** — email, SMS, push, in-app
- **Support tickets** — case management with message threads
- **Audit logging** — immutable ledger of all sensitive operations

## Database

The database layer is built for **MySQL 8.0+** (InnoDB, utf8mb4).

```
database/
├── 01_schema.sql   — 27 tables
├── 02_functions.sql — IBAN functions + stored procedures
├── 03_indexes.sql  — Performance indexes
├── 04_seed.sql     — Roles, permissions, SEPA countries, account types, branches
└── README.md       — Full database documentation
```

See **[database/README.md](database/README.md)** for:
- Quick-start setup instructions
- Complete table reference
- IBAN generation algorithm and usage
- Stored procedure API
- Security checklist
- SEPA compliance notes

## Repository Layout

```
/                      ← Marketing / frontend (public-facing website)
├── app/               ← Back-end applications (NOT directly web-accessible)
│   ├── admin/         ← Admin back-office panel
│   │   └── public/   ← Web root for admin  → point document root here
│   └── portal/        ← Customer self-service portal
│       └── public/   ← Web root for portal → point document root here
├── database/          ← SQL migrations & seed data
└── README.md
```

> **Deployment rule:** your web server must have **two virtual hosts** (or subdomains),
> each pointing to a different `public/` directory:
>
> | Application   | Document root                |
> |---------------|------------------------------|
> | Admin panel   | `app/admin/public/`          |
> | Customer portal | `app/portal/public/`       |

## Admin Backend (`app/admin/`)

The admin panel lives in `app/admin/`. Point your web server document root at `app/admin/public/`.

```
app/admin/
├── public/               ← Web root (only this directory is web-accessible)
│   ├── index.php         ← Single entry point
│   ├── .htaccess         ← mod_rewrite rules + security headers
│   └── assets/
│       ├── css/admin.css ← Professional Bootstrap 5 custom styles
│       ├── js/admin.js   ← Sidebar toggle, toasts, AJAX helpers
│       └── img/
├── config/
│   ├── config.php        ← DB, session, security settings (use env vars)
│   └── constants.php     ← Named constants (roles, statuses, paths)
├── core/                 ← MVC framework classes
│   ├── App.php           ← Bootstrap: autoloader, session, routes, dispatch
│   ├── Router.php        ← URI → Controller@method dispatcher
│   ├── Controller.php    ← Base controller (view, redirect, validate, flash)
│   ├── Model.php         ← Base model (CRUD, paginate, raw query)
│   ├── Database.php      ← PDO singleton
│   ├── Request.php       ← HTTP request abstraction
│   ├── Response.php      ← JSON / view / redirect / abort helpers
│   ├── Session.php       ← Secure session + flash messages
│   └── Auth.php          ← Login, logout, role checks
├── controllers/          ← One controller per domain area (20 controllers)
├── models/               ← Active-record style models (7 models)
├── middleware/
│   ├── AuthMiddleware.php   ← Redirect unauthenticated users
│   ├── RoleMiddleware.php   ← Restrict to admin-level roles
│   └── CsrfMiddleware.php  ← CSRF token validation on mutating requests
├── helpers/
│   ├── IbanHelper.php       ← PHP IBAN generation & validation (ISO 7064)
│   ├── FormatHelper.php     ← Money, date, status badge, HTML escape
│   └── ValidationHelper.php ← IBAN, BIC, amount, password strength checks
├── views/                ← Bootstrap 5 views (31 views across 16 sections)
│   ├── layouts/
│   │   ├── main.php         ← Sidebar layout (authenticated pages)
│   │   └── auth.php         ← Centred card layout (login page)
│   ├── auth/, dashboard/, users/, accounts/, transactions/
│   ├── loans/, cards/, kyc/, sepa/, mandates/, standing-orders/
│   ├── beneficiaries/, branches/, fee-schedules/, exchange-rates/
│   ├── notifications/, support/, reports/, settings/
└── storage/
    └── logs/               ← Application log files (git-ignored)
```

## Customer Portal (`app/portal/`)

The customer self-service portal lives in `app/portal/`. Point your web server document root at `app/portal/public/`.

```
app/portal/
├── public/               ← Web root
│   ├── index.php
│   ├── .htaccess
│   └── assets/
│       ├── css/portal.css ← Professional Bootstrap 5 portal styles
│       └── js/portal.js   ← CSRF AJAX helper, toasts, card UI
├── config/config.php
├── core/                  ← App, Router, Database, Session, Request, Auth, Controller
├── controllers/           ← 14 controllers covering all customer journeys
├── middleware/            ← AuthMiddleware, CsrfMiddleware
└── views/                 ← 22+ Bootstrap 5 views
    ├── layouts/main.php   ← Sidebar layout with notification badge
    ├── layouts/auth.php   ← Centred auth card
    ├── auth/              ← login, register
    ├── dashboard/         ← balance cards, quick actions, recent transactions
    ├── accounts/          ← index + statement view
    ├── transactions/      ← paginated list + detail
    ├── transfer/          ← internal transfer + SEPA transfer
    ├── beneficiaries/     ← cards + add modal
    ├── standing-orders/   ← list + create modal + pause/cancel
    ├── loans/             ← index (progress bar) + show (amortisation) + apply
    ├── cards/             ← visual card UI + freeze/unfreeze
    ├── support/           ← ticket list + chat-style thread + reply
    ├── notifications/     ← unread badge + mark-all-read
    ├── profile/           ← edit info + change password + KYC status
    └── kyc/               ← status banner + document upload
```

### Quick Start

```bash
# 1. Clone and enter the repository
git clone https://github.com/berndmarcel860-byte/Bnkapp /var/www/bnkapp
cd /var/www/bnkapp

# 2. Grant the web server write access to installer-required directories.
#    The installer writes env.php, install.lock, and log files on your behalf.
#    Without this step the System Requirements check shows "NOT WRITABLE".
sudo bash deploy/setup-permissions.sh /var/www/bnkapp www-data
#    (replace "www-data" with "nginx" on RHEL/CentOS systems)

# 3. Configure Nginx — use the HTTP-only bootstrap config first (no cert needed yet).
#    The installer will display the post-install Nginx config, or use the templates:
sudo cp deploy/nginx.conf /etc/nginx/sites-available/bnkapp.conf
sudo ln -s /etc/nginx/sites-available/bnkapp.conf /etc/nginx/sites-enabled/
#    Edit the file: replace "yourdomain.com" placeholders with real domains.
sudo nginx -t && sudo systemctl reload nginx

# 4. Run the web installer — visit http://<your-domain>/install.php
#    The wizard will:
#      • Check system requirements (PHP extensions, writable directories)
#      • Test and configure the MySQL connection
#      • Run database migrations (schema, functions, indexes, seed data)
#      • Create the first admin account
#      • Write app/env.php and create install.lock

# 5. After the installer completes, obtain TLS certificates:
sudo certbot --nginx -d admin.yourdomain.com -d portal.yourdomain.com

# 6. Harden permissions now that the installer has finished:
sudo bash deploy/post-install-permissions.sh /var/www/bnkapp www-data

# 7. Navigate to:
#    Admin:  https://admin.yourdomain.com/auth/login
#    Portal: https://portal.yourdomain.com/login
```