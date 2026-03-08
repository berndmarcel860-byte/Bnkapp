# BnkApp — Database Documentation

A professional SEPA-compliant banking system database for **MySQL 8.0+**.

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [File Structure](#file-structure)
3. [Entity-Relationship Overview](#entity-relationship-overview)
4. [Table Reference](#table-reference)
5. [IBAN Generation](#iban-generation)
6. [Stored Procedures](#stored-procedures)
7. [Security Notes](#security-notes)
8. [SEPA Compliance](#sepa-compliance)

---

## Quick Start

```bash
# 1. Create the database
mysql -u root -p -e "CREATE DATABASE bnkapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Apply all scripts in order
mysql -u root -p bnkapp < database/01_schema.sql
mysql -u root -p bnkapp < database/02_functions.sql
mysql -u root -p bnkapp < database/03_indexes.sql
mysql -u root -p bnkapp < database/04_seed.sql
```

---

## File Structure

| File | Purpose |
|------|---------|
| `database/01_schema.sql` | All table definitions (27 tables) |
| `database/02_functions.sql` | IBAN generation functions and stored procedures |
| `database/03_indexes.sql` | Performance indexes |
| `database/04_seed.sql` | Initial data: roles, permissions, SEPA countries, account types, branches, fee schedules, exchange rates |

---

## Entity-Relationship Overview

```
countries ──────────────── users ──────────────── admins
                             │
                    ┌────────┼────────┐
                    │        │        │
               bank_accounts loans  kyc_documents
                    │
          ┌─────────┼──────────────┐
          │         │              │
     transactions  cards    standing_orders
          │
    ┌─────┴──────┐
    │            │
sepa_transfers  loan_payments
```

---

## Table Reference

### Identity & Access

| Table | Description |
|-------|-------------|
| `roles` | User roles: `super_admin`, `admin`, `manager`, `teller`, `customer` |
| `permissions` | Granular permissions (31 predefined) |
| `role_permissions` | Many-to-many role ↔ permission mapping |
| `users` | All system users — customers and staff |
| `admins` | Extended profile for staff members |
| `user_sessions` | JWT/session token management |

### Banking Core

| Table | Description |
|-------|-------------|
| `countries` | 34 SEPA-participating countries with IBAN length metadata |
| `branches` | Bank branches with BIC/SWIFT codes |
| `account_types` | Product catalogue (8 types: CHK, SAV, PCHK, BCHK, BSAV, STU, YOUTH, FTD) |
| `bank_accounts` | Customer accounts, each with a unique SEPA IBAN |
| `iban_registry` | Immutable audit trail of every IBAN issued |

### Transactions & Payments

| Table | Description |
|-------|-------------|
| `transaction_categories` | 21 categories for statement labelling |
| `transactions` | Central ledger — every money movement |
| `sepa_transfers` | ISO 20022 / EPC rulebook data for SCT / SCT Inst / SDD |
| `standing_orders` | Recurring SEPA Credit Transfers |
| `sepa_mandates` | SEPA Direct Debit mandates (SDD CORE / SDD B2B) |
| `beneficiaries` | Saved/trusted payees per user |

### Cards & Lending

| Table | Description |
|-------|-------------|
| `cards` | Debit, credit and prepaid cards (PAN/CVV stored as bcrypt hashes) |
| `loans` | Loan applications and lifecycle management |
| `loan_payments` | Amortisation schedule and actual payment records |

### Operations

| Table | Description |
|-------|-------------|
| `fee_schedules` | Configurable fee matrix (flat fee + percentage + min/max caps) |
| `exchange_rates` | Historical FX rates used at transaction time |
| `kyc_documents` | Document uploads for KYC/AML identity verification |
| `notifications` | Multi-channel alerts (email, SMS, push, in-app) |
| `support_tickets` | Customer service case management |
| `support_ticket_messages` | Conversation threads within tickets |
| `audit_logs` | Immutable log of every sensitive operation |

---

## IBAN Generation

The SEPA IBAN is computed using the **ISO 7064 MOD-97-10** algorithm.

### Algorithm Steps

```
1. Take BBAN = bank_code + zero_padded_account_number
2. Rearrange: BBAN + country_code + "00"
3. Convert every letter to digits (A=10, B=11, … Z=35)
4. Compute the resulting number MOD 97 (processed in 9-digit chunks)
5. check_digits = 98 − remainder   (zero-padded to 2 digits)
6. IBAN = country_code + check_digits + BBAN
```

### MySQL Functions

```sql
-- Generate a new IBAN
SELECT fn_generate_iban('DE', '50010517', '0000123456');
-- → 'DE89500105170000123456'

-- Validate an existing IBAN
SELECT fn_validate_iban('DE89370400440532013000');  -- → 1 (valid)
SELECT fn_validate_iban('DE00370400440532013000');  -- → 0 (invalid)

-- Format for display (paper format)
SELECT fn_format_iban_paper('DE89370400440532013000');
-- → 'DE89 3704 0044 0532 0130 00'
```

### Country IBAN Lengths (SEPA)

| Country | Code | Length | Example (structure) |
|---------|------|--------|---------------------|
| Germany | DE | 22 | DE + 2 + 8n + 10n |
| France | FR | 27 | FR + 2 + 5n + 5n + 11c + 2n |
| Netherlands | NL | 18 | NL + 2 + 4a + 10n |
| Spain | ES | 24 | ES + 2 + 4n + 4n + 2n + 10n |
| Italy | IT | 27 | IT + 2 + 1a + 5n + 5n + 12c |
| Belgium | BE | 16 | BE + 2 + 3n + 7n + 2n |
| United Kingdom | GB | 22 | GB + 2 + 4a + 6n + 8n |
| Austria | AT | 20 | AT + 2 + 5n + 11n |

*(See `04_seed.sql` for all 34 supported SEPA countries)*

---

## Stored Procedures

### `sp_open_account`

Opens a bank account, generates a SEPA IBAN, and records it in `iban_registry`.

```sql
CALL sp_open_account(
    p_user_id         := 2,        -- customer user ID
    p_account_type_id := 1,        -- CHK = Personal Checking
    p_branch_id       := 1,        -- Frankfurt HQ
    p_country_code    := 'DE',
    p_bank_code       := '50010517',
    p_currency_code   := 'EUR',
    p_initiated_by    := 1,        -- admin user ID
    @iban,                         -- OUT: generated IBAN
    @account_id                    -- OUT: new bank_accounts.id
);
SELECT @iban, @account_id;
```

**Prerequisites:** the customer must have `kyc_status = 'approved'` and `is_active = 1`.

---

### `sp_execute_transfer`

Performs an atomic internal transfer between two accounts with balance validation, fee deduction, and audit logging.

```sql
CALL sp_execute_transfer(
    p_from_account_id := 1,
    p_to_account_id   := 2,
    p_amount          := 500.00,
    p_currency_code   := 'EUR',
    p_description     := 'Rent payment',
    p_reference       := 'INV-2024-001',
    p_end_to_end_id   := 'E2E-20240115-001',
    p_initiated_by    := 3,
    @txn_ref,
    @txn_id
);
SELECT @txn_ref, @txn_id;
```

---

### `sp_deposit`

Posts a deposit or external credit to an account.

```sql
CALL sp_deposit(
    p_account_id    := 1,
    p_amount        := 2000.00,
    p_currency_code := 'EUR',
    p_description   := 'Monthly salary',
    p_initiated_by  := 4,   -- teller ID
    @txn_ref,
    @txn_id
);
```

---

### `sp_close_account`

Closes an account. Requires balance to be exactly `0.00`.

```sql
CALL sp_close_account(
    p_account_id := 1,
    p_closed_by  := 1    -- admin user ID
);
```

---

## Security Notes

| Topic | Implementation |
|-------|----------------|
| **Passwords** | bcrypt (cost ≥ 12) — `password_hash` + `password_salt` columns |
| **Card PAN** | Only a bcrypt hash + last 4 digits stored; full PAN never persisted |
| **Card CVV** | bcrypt hash only — never stored in plain text |
| **PIN** | bcrypt hash only |
| **KYC Files** | SHA-256 `file_hash` column for integrity verification |
| **Sensitive columns** | Encrypt at application layer (AES-256) before insert where required |
| **Audit trail** | `audit_logs` is append-only; use DB-level triggers or application guards to prevent updates/deletes |
| **Sessions** | Short-lived `session_token` + longer-lived `refresh_token`; `expires_at` enforced |
| **Account locking** | `failed_login_attempts` + `locked_until` prevent brute force |
| **2FA** | `two_factor_enabled` / `two_factor_secret` columns; integrate TOTP (RFC 6238) |

> ⚠️ **Production Checklist**
> - Replace all placeholder password hashes in `04_seed.sql` before deployment
> - Enable MySQL `binlog_encryption` and `innodb_encrypt_tables`
> - Restrict DB user privileges: application account should not have DDL rights
> - Schedule nightly jobs for: interest calculation, fee posting, standing-order execution, exchange-rate updates
> - Implement row-level access control in the application layer

---

## SEPA Compliance

This schema supports the following EPC payment schemes:

| Scheme | Enum Value | Description |
|--------|-----------|-------------|
| SEPA Credit Transfer | `SCT` | Standard credit transfer, D+1 settlement |
| SEPA Instant Credit Transfer | `SCT_INST` | Real-time, 10-second limit, 24/7/365 |
| SEPA Direct Debit Core | `SDD_CORE` | Consumer direct debit with 8-week refund right |
| SEPA Direct Debit B2B | `SDD_B2B` | Business-to-business, no refund right |

Key fields aligned with **ISO 20022 pain.001 / pain.008 / camt.054**:
- `end_to_end_id` — end-to-end identification (max 35 chars)
- `mandate_id` — mandate identifier for SDD
- `creditor_scheme_id` — creditor identifier (CI)
- `purpose_code` — ISO purpose code (4 chars)
- `remittance_info` — unstructured remittance information (max 140 chars)
- `debtor_name` / `creditor_name` — max 140 chars (ISO 20022)
