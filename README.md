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