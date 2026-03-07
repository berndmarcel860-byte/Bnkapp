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

## Admin Backend (PHP)

The PHP backend lives in `admin/`. Point your web server document root at `admin/public/`.

```
admin/
├── public/               ← Web root (only this directory is web-accessible)
│   ├── index.php         ← Single entry point
│   ├── .htaccess         ← mod_rewrite rules + security headers
│   └── assets/
│       ├── css/admin.css
│       ├── js/admin.js
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
├── controllers/          ← One controller per domain area
│   ├── AuthController.php
│   ├── DashboardController.php
│   ├── UserController.php
│   ├── AccountController.php
│   ├── TransactionController.php
│   ├── LoanController.php
│   ├── CardController.php
│   ├── ReportController.php
│   └── SupportController.php
├── models/
│   ├── User.php
│   ├── BankAccount.php
│   ├── Transaction.php
│   ├── Loan.php
│   ├── Card.php
│   ├── KycDocument.php
│   └── AuditLog.php
├── middleware/
│   ├── AuthMiddleware.php   ← Redirect unauthenticated users
│   ├── RoleMiddleware.php   ← Restrict to admin-level roles
│   └── CsrfMiddleware.php  ← CSRF token validation on mutating requests
├── helpers/
│   ├── IbanHelper.php       ← PHP IBAN generation & validation (ISO 7064)
│   ├── FormatHelper.php     ← Money, date, status badge, HTML escape
│   └── ValidationHelper.php ← IBAN, BIC, amount, password strength checks
├── views/
│   ├── layouts/
│   │   ├── main.php         ← Sidebar layout (authenticated pages)
│   │   └── auth.php         ← Centred card layout (login page)
│   ├── auth/login.php
│   ├── dashboard/index.php
│   ├── users/index.php
│   ├── accounts/index.php
│   ├── transactions/index.php
│   ├── loans/index.php
│   ├── cards/index.php
│   ├── reports/index.php
│   └── support/index.php
└── storage/
    └── logs/               ← Application log files (git-ignored)
```

### Quick Start

```bash
# 1. Configure environment (copy and edit config)
cp admin/config/config.php admin/config/config.local.php  # edit DB credentials

# 2. Set web server document root to admin/public/

# 3. Apply database migrations (see Database section above)
mysql -u root -p bnkapp < database/01_schema.sql
mysql -u root -p bnkapp < database/02_functions.sql
mysql -u root -p bnkapp < database/03_indexes.sql
mysql -u root -p bnkapp < database/04_seed.sql

# 4. Navigate to http://localhost/auth/login
#    Default admin: admin@bnkapp.example (change password before use!)
```