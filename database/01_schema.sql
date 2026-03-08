-- =============================================================================
-- Professional Banking System — Database Schema
-- Engine: MySQL 8.0+
-- Supports: SEPA (Single Euro Payments Area) with IBAN generation
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ---------------------------------------------------------------------------
-- 1. COUNTRIES
--    Stores SEPA-participating countries with their IBAN format rules.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS countries (
    id               SMALLINT     UNSIGNED NOT NULL AUTO_INCREMENT,
    iso_code         CHAR(2)      NOT NULL COMMENT 'ISO 3166-1 alpha-2 code (e.g. DE, FR)',
    name             VARCHAR(100) NOT NULL,
    currency_code    CHAR(3)      NOT NULL DEFAULT 'EUR',
    iban_length      TINYINT      UNSIGNED NOT NULL COMMENT 'Total IBAN character count for this country',
    bban_structure   VARCHAR(100)          COMMENT 'Regex or description of the BBAN format',
    is_sepa          TINYINT(1)   NOT NULL DEFAULT 1,
    is_active        TINYINT(1)   NOT NULL DEFAULT 1,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_countries_iso (iso_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='SEPA-participating countries with IBAN format metadata';

-- ---------------------------------------------------------------------------
-- 2. ROLES
--    Defines the permission roles assignable to system users.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id          TINYINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(50)  NOT NULL COMMENT 'e.g. super_admin, admin, manager, teller, customer',
    description TEXT,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. PERMISSIONS
--    Granular permission definitions.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS permissions (
    id          SMALLINT     UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL COMMENT 'e.g. accounts.view, transactions.approve',
    description TEXT,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 4. ROLE_PERMISSIONS  (many-to-many)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id       TINYINT   UNSIGNED NOT NULL,
    permission_id SMALLINT  UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY fk_rp_role       (role_id)       REFERENCES roles(id)       ON DELETE CASCADE,
    FOREIGN KEY fk_rp_permission (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 5. USERS
--    Central identity table for both customers and staff.
--    Admins/staff get an additional row in the `admins` table.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                       INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    role_id                  TINYINT      UNSIGNED NOT NULL,
    first_name               VARCHAR(100) NOT NULL,
    last_name                VARCHAR(100) NOT NULL,
    email                    VARCHAR(255) NOT NULL,
    phone                    VARCHAR(30),
    date_of_birth            DATE,
    national_id              VARCHAR(50)  COMMENT 'Passport / ID card number',
    address_line1            VARCHAR(255),
    address_line2            VARCHAR(255),
    city                     VARCHAR(100),
    postal_code              VARCHAR(20),
    country_id               SMALLINT     UNSIGNED,
    password_hash            VARCHAR(255) NOT NULL,
    password_salt            VARCHAR(64)  NOT NULL,
    is_active                TINYINT(1)   NOT NULL DEFAULT 1,
    is_email_verified        TINYINT(1)   NOT NULL DEFAULT 0,
    email_verification_token VARCHAR(255),
    password_reset_token     VARCHAR(255),
    password_reset_expires   TIMESTAMP    NULL DEFAULT NULL,
    last_login_at            TIMESTAMP    NULL DEFAULT NULL,
    failed_login_attempts    TINYINT      UNSIGNED NOT NULL DEFAULT 0,
    locked_until             TIMESTAMP    NULL DEFAULT NULL,
    two_factor_enabled       TINYINT(1)   NOT NULL DEFAULT 0,
    two_factor_secret        VARCHAR(255),
    kyc_status               ENUM('pending','in_review','approved','rejected') NOT NULL DEFAULT 'pending',
    kyc_submitted_at         TIMESTAMP    NULL DEFAULT NULL,
    kyc_approved_at          TIMESTAMP    NULL DEFAULT NULL,
    created_at               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    FOREIGN KEY fk_users_role    (role_id)    REFERENCES roles(id),
    FOREIGN KEY fk_users_country (country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='All system users: customers and staff alike';

-- ---------------------------------------------------------------------------
-- 6. ADMINS
--    Extra profile data for staff members (linked to users).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id                        INT         UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id                   INT         UNSIGNED NOT NULL,
    employee_id               VARCHAR(50) NOT NULL COMMENT 'Internal HR employee number',
    department                VARCHAR(100),
    access_level              ENUM('super_admin','admin','manager','teller') NOT NULL DEFAULT 'teller',
    can_approve_transactions  TINYINT(1)  NOT NULL DEFAULT 0,
    can_manage_users          TINYINT(1)  NOT NULL DEFAULT 0,
    can_view_reports          TINYINT(1)  NOT NULL DEFAULT 0,
    can_manage_loans          TINYINT(1)  NOT NULL DEFAULT 0,
    can_freeze_accounts       TINYINT(1)  NOT NULL DEFAULT 0,
    created_at                TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_user       (user_id),
    UNIQUE KEY uq_admins_employee   (employee_id),
    FOREIGN KEY fk_admins_user (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 7. BRANCHES
--    Physical or virtual bank branches.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS branches (
    id           SMALLINT     UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(255) NOT NULL,
    address      VARCHAR(255),
    city         VARCHAR(100),
    postal_code  VARCHAR(20),
    country_id   SMALLINT     UNSIGNED,
    bic          VARCHAR(11)  NOT NULL COMMENT 'BIC/SWIFT code for this branch',
    bank_code    VARCHAR(20)  NOT NULL COMMENT 'National bank sort code / routing number',
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_branches_country (country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 8. ACCOUNT_TYPES
--    Configurable product catalogue (checking, savings, business, …).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS account_types (
    id                        TINYINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    name                      VARCHAR(100) NOT NULL COMMENT 'e.g. Personal Checking, Savings, Business',
    code                      VARCHAR(20)  NOT NULL COMMENT 'Short code, e.g. CHK, SAV, BUS',
    description               TEXT,
    interest_rate             DECIMAL(6,4) NOT NULL DEFAULT 0.0000 COMMENT 'Annual interest rate (0.0250 = 2.50%)',
    min_balance               DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    max_balance               DECIMAL(15,2)          DEFAULT NULL COMMENT 'NULL = unlimited',
    daily_withdrawal_limit    DECIMAL(15,2)          DEFAULT NULL,
    monthly_transfer_limit    DECIMAL(15,2)          DEFAULT NULL,
    maintenance_fee           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fee_period                ENUM('monthly','quarterly','annually','none') NOT NULL DEFAULT 'none',
    overdraft_allowed         TINYINT(1)   NOT NULL DEFAULT 0,
    is_active                 TINYINT(1)   NOT NULL DEFAULT 1,
    created_at                TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_account_types_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 9. BANK_ACCOUNTS
--    Customer accounts; each account has exactly one IBAN (stored here and
--    also mirrored in iban_registry for audit purposes).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bank_accounts (
    id                INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           INT          UNSIGNED NOT NULL,
    account_type_id   TINYINT      UNSIGNED NOT NULL,
    branch_id         SMALLINT     UNSIGNED          DEFAULT NULL,
    account_number    VARCHAR(34)  NOT NULL COMMENT 'Internal account number (zero-padded)',
    iban              VARCHAR(34)  NOT NULL COMMENT 'SEPA IBAN — generated by fn_generate_iban()',
    bic               VARCHAR(11)  NOT NULL,
    currency_code     CHAR(3)      NOT NULL DEFAULT 'EUR',
    balance           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    available_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'balance minus holds',
    hold_amount       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status            ENUM('pending','active','inactive','frozen','closed') NOT NULL DEFAULT 'pending',
    is_primary        TINYINT(1)   NOT NULL DEFAULT 0,
    overdraft_enabled TINYINT(1)   NOT NULL DEFAULT 0,
    overdraft_limit   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    opened_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at         TIMESTAMP    NULL DEFAULT NULL,
    last_transaction_at TIMESTAMP  NULL DEFAULT NULL,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bank_accounts_number (account_number),
    UNIQUE KEY uq_bank_accounts_iban   (iban),
    FOREIGN KEY fk_ba_user         (user_id)         REFERENCES users(id),
    FOREIGN KEY fk_ba_account_type (account_type_id) REFERENCES account_types(id),
    FOREIGN KEY fk_ba_branch       (branch_id)       REFERENCES branches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Customer bank accounts with SEPA IBAN';

-- ---------------------------------------------------------------------------
-- 10. IBAN_REGISTRY
--     Immutable audit trail of every IBAN ever issued, including the
--     constituent parts used to derive it.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS iban_registry (
    id                  INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    bank_account_id     INT          UNSIGNED NOT NULL,
    country_code        CHAR(2)      NOT NULL,
    check_digits        CHAR(2)      NOT NULL,
    bank_code           VARCHAR(20)  NOT NULL COMMENT 'Part of BBAN: bank identifier',
    account_identifier  VARCHAR(30)  NOT NULL COMMENT 'Part of BBAN: account-specific portion',
    bban                VARCHAR(30)  NOT NULL COMMENT 'Basic Bank Account Number (concatenated)',
    full_iban           VARCHAR(34)  NOT NULL,
    generated_by        INT          UNSIGNED     COMMENT 'Admin or system user who triggered generation',
    generated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_iban_registry_account (bank_account_id),
    UNIQUE KEY uq_iban_registry_iban    (full_iban),
    FOREIGN KEY fk_ir_account (bank_account_id) REFERENCES bank_accounts(id),
    FOREIGN KEY fk_ir_user    (generated_by)    REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Immutable IBAN issuance audit trail';

-- ---------------------------------------------------------------------------
-- 11. TRANSACTION_CATEGORIES
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transaction_categories (
    id          SMALLINT     UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    icon        VARCHAR(50)           COMMENT 'Frontend icon identifier',
    is_system   TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_txn_cat_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 12. TRANSACTIONS
--     Core ledger — every money movement is recorded here.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transactions (
    id                  BIGINT       UNSIGNED NOT NULL AUTO_INCREMENT,
    transaction_ref     VARCHAR(64)  NOT NULL COMMENT 'UUID-based unique reference for this payment',
    from_account_id     INT          UNSIGNED         DEFAULT NULL COMMENT 'NULL for external credits',
    to_account_id       INT          UNSIGNED         DEFAULT NULL COMMENT 'NULL for external debits',
    transaction_type    ENUM(
        'deposit','withdrawal','internal_transfer',
        'sepa_credit_transfer','sepa_instant_transfer',
        'sepa_direct_debit','fee','interest',
        'loan_disbursement','loan_repayment','refund','reversal'
    ) NOT NULL,
    amount              DECIMAL(15,2) NOT NULL,
    currency_code       CHAR(3)      NOT NULL DEFAULT 'EUR',
    exchange_rate       DECIMAL(12,6) NOT NULL DEFAULT 1.000000,
    fee_amount          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    net_amount          DECIMAL(15,2) NOT NULL COMMENT 'amount - fee_amount, after FX',
    status              ENUM('pending','processing','completed','failed','cancelled','reversed') NOT NULL DEFAULT 'pending',
    category_id         SMALLINT     UNSIGNED         DEFAULT NULL,
    description         VARCHAR(500),
    reference           VARCHAR(255) COMMENT 'Remittance information / payment reference',
    end_to_end_id       VARCHAR(35)  COMMENT 'SEPA end-to-end identifier',
    mandate_id          VARCHAR(35)  COMMENT 'SEPA Direct Debit mandate identifier',
    creditor_scheme_id  VARCHAR(35)  COMMENT 'Creditor identifier for SDD',
    value_date          DATE,
    booking_date        DATE,
    initiated_by        INT          UNSIGNED         DEFAULT NULL,
    approved_by         INT          UNSIGNED         DEFAULT NULL,
    requires_approval   TINYINT(1)   NOT NULL DEFAULT 0,
    ip_address          VARCHAR(45),
    metadata            JSON,
    failure_reason      TEXT,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_transactions_ref (transaction_ref),
    FOREIGN KEY fk_txn_from     (from_account_id) REFERENCES bank_accounts(id),
    FOREIGN KEY fk_txn_to       (to_account_id)   REFERENCES bank_accounts(id),
    FOREIGN KEY fk_txn_category (category_id)     REFERENCES transaction_categories(id),
    FOREIGN KEY fk_txn_initiator(initiated_by)    REFERENCES users(id),
    FOREIGN KEY fk_txn_approver (approved_by)     REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Central ledger — all money movements';

-- ---------------------------------------------------------------------------
-- 13. SEPA_TRANSFERS
--     Extended SEPA-specific data for SCT / SCT Inst / SDD transactions.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sepa_transfers (
    id                             INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    transaction_id                 BIGINT       UNSIGNED NOT NULL,
    sepa_type                      ENUM('SCT','SCT_INST','SDD_CORE','SDD_B2B') NOT NULL
                                   COMMENT 'SCT=Credit Transfer, INST=Instant, SDD=Direct Debit',
    debtor_name                    VARCHAR(140) NOT NULL,
    debtor_iban                    VARCHAR(34)  NOT NULL,
    debtor_bic                     VARCHAR(11),
    debtor_address                 TEXT,
    creditor_name                  VARCHAR(140) NOT NULL,
    creditor_iban                  VARCHAR(34)  NOT NULL,
    creditor_bic                   VARCHAR(11),
    creditor_address               TEXT,
    remittance_info                VARCHAR(140) COMMENT 'Unstructured remittance info',
    purpose_code                   VARCHAR(4)   COMMENT 'ISO 20022 purpose code',
    payment_priority               ENUM('NORM','HIGH') NOT NULL DEFAULT 'NORM',
    requested_execution_date       DATE,
    settlement_date                DATE,
    interbank_settlement_amount    DECIMAL(15,2),
    status                         ENUM('pending','accepted','settlement_in_progress','settled','rejected') NOT NULL DEFAULT 'pending',
    rejection_reason               VARCHAR(255),
    created_at                     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sepa_txn (transaction_id),
    FOREIGN KEY fk_sepa_txn (transaction_id) REFERENCES transactions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='SEPA payment details (ISO 20022 / EPC rulebook)';

-- ---------------------------------------------------------------------------
-- 14. CARDS
--     Debit, credit and prepaid cards linked to bank accounts.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cards (
    id                       INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id               INT          UNSIGNED NOT NULL,
    card_number_hash         VARCHAR(255) NOT NULL COMMENT 'bcrypt hash of full PAN — never store plain',
    card_number_last4        CHAR(4)      NOT NULL,
    card_type                ENUM('debit','credit','prepaid') NOT NULL,
    card_network             ENUM('visa','mastercard','maestro','amex') NOT NULL DEFAULT 'visa',
    cardholder_name          VARCHAR(100) NOT NULL,
    expiry_month             TINYINT      UNSIGNED NOT NULL,
    expiry_year              SMALLINT     UNSIGNED NOT NULL,
    cvv_hash                 VARCHAR(255) NOT NULL COMMENT 'bcrypt hash — never stored plain',
    pin_hash                 VARCHAR(255) NOT NULL COMMENT 'bcrypt hash of 4-digit PIN',
    credit_limit             DECIMAL(15,2)         DEFAULT NULL COMMENT 'Only for credit cards',
    available_credit         DECIMAL(15,2)         DEFAULT NULL,
    status                   ENUM('inactive','active','blocked','expired','cancelled') NOT NULL DEFAULT 'inactive',
    is_contactless           TINYINT(1)   NOT NULL DEFAULT 1,
    daily_atm_limit          DECIMAL(10,2) NOT NULL DEFAULT 500.00,
    daily_pos_limit          DECIMAL(10,2) NOT NULL DEFAULT 2000.00,
    online_limit             DECIMAL(10,2) NOT NULL DEFAULT 1000.00,
    is_online_enabled        TINYINT(1)   NOT NULL DEFAULT 1,
    is_international_enabled TINYINT(1)   NOT NULL DEFAULT 0,
    issued_at                TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activated_at             TIMESTAMP    NULL DEFAULT NULL,
    expires_at               DATE         NOT NULL,
    last_used_at             TIMESTAMP    NULL DEFAULT NULL,
    blocked_at               TIMESTAMP    NULL DEFAULT NULL,
    blocked_reason           TEXT,
    created_at               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_cards_account (account_id) REFERENCES bank_accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Payment cards (PAN and CVV are stored hashed only)';

-- ---------------------------------------------------------------------------
-- 15. LOANS
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS loans (
    id                  INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             INT          UNSIGNED NOT NULL,
    account_id          INT          UNSIGNED NOT NULL COMMENT 'Disbursement / repayment account',
    loan_type           ENUM('personal','mortgage','auto','business','student','overdraft') NOT NULL,
    purpose             TEXT,
    principal_amount    DECIMAL(15,2) NOT NULL,
    outstanding_balance DECIMAL(15,2) NOT NULL,
    interest_rate       DECIMAL(6,4) NOT NULL COMMENT 'Annual rate, e.g. 0.0499 = 4.99%',
    term_months         SMALLINT     UNSIGNED NOT NULL,
    monthly_payment     DECIMAL(15,2) NOT NULL,
    origination_fee     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    late_fee            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status              ENUM('applied','in_review','approved','disbursed','active','defaulted','paid_off','rejected','cancelled') NOT NULL DEFAULT 'applied',
    approved_by         INT          UNSIGNED DEFAULT NULL,
    disbursed_at        TIMESTAMP    NULL DEFAULT NULL,
    start_date          DATE,
    end_date            DATE,
    next_payment_date   DATE,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_loans_user     (user_id)     REFERENCES users(id),
    FOREIGN KEY fk_loans_account  (account_id)  REFERENCES bank_accounts(id),
    FOREIGN KEY fk_loans_approver (approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 16. LOAN_PAYMENTS
--     Amortisation schedule and actual payment records.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS loan_payments (
    id                BIGINT       UNSIGNED NOT NULL AUTO_INCREMENT,
    loan_id           INT          UNSIGNED NOT NULL,
    transaction_id    BIGINT       UNSIGNED         DEFAULT NULL COMMENT 'Links to actual money movement',
    payment_number    SMALLINT     UNSIGNED NOT NULL COMMENT 'Instalment sequence number',
    due_date          DATE         NOT NULL,
    payment_date      DATE,
    scheduled_amount  DECIMAL(15,2) NOT NULL,
    principal_portion DECIMAL(15,2) NOT NULL,
    interest_portion  DECIMAL(15,2) NOT NULL,
    fee_portion       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_paid        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    remaining_balance DECIMAL(15,2) NOT NULL,
    is_late           TINYINT(1)   NOT NULL DEFAULT 0,
    late_fee_charged  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status            ENUM('scheduled','paid','partial','missed','waived') NOT NULL DEFAULT 'scheduled',
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lp_loan_number (loan_id, payment_number),
    FOREIGN KEY fk_lp_loan (loan_id)        REFERENCES loans(id),
    FOREIGN KEY fk_lp_txn  (transaction_id) REFERENCES transactions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 17. STANDING_ORDERS
--     Recurring SEPA Credit Transfers.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS standing_orders (
    id                    INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    from_account_id       INT          UNSIGNED NOT NULL,
    to_iban               VARCHAR(34)  NOT NULL,
    to_account_name       VARCHAR(140) NOT NULL,
    to_bic                VARCHAR(11),
    amount                DECIMAL(15,2) NOT NULL,
    currency_code         CHAR(3)      NOT NULL DEFAULT 'EUR',
    reference             VARCHAR(140),
    frequency             ENUM('daily','weekly','biweekly','monthly','quarterly','annually') NOT NULL,
    start_date            DATE         NOT NULL,
    end_date              DATE                  DEFAULT NULL,
    next_execution_date   DATE         NOT NULL,
    last_execution_date   DATE                  DEFAULT NULL,
    last_transaction_id   BIGINT       UNSIGNED DEFAULT NULL,
    status                ENUM('active','paused','completed','cancelled') NOT NULL DEFAULT 'active',
    created_by            INT          UNSIGNED NOT NULL,
    created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_so_account (from_account_id) REFERENCES bank_accounts(id),
    FOREIGN KEY fk_so_creator (created_by)      REFERENCES users(id),
    FOREIGN KEY fk_so_last_txn(last_transaction_id) REFERENCES transactions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Recurring standing order payments (SEPA SCT)';

-- ---------------------------------------------------------------------------
-- 18. SEPA_MANDATES
--     Direct Debit mandates (SDD CORE / SDD B2B).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sepa_mandates (
    id                    INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    mandate_id            VARCHAR(35)  NOT NULL COMMENT 'Unique mandate reference (assigned by creditor)',
    account_id            INT          UNSIGNED NOT NULL COMMENT 'Debtor account',
    creditor_name         VARCHAR(140) NOT NULL,
    creditor_iban         VARCHAR(34)  NOT NULL,
    creditor_bic          VARCHAR(11),
    creditor_scheme_id    VARCHAR(35)  NOT NULL COMMENT 'Creditor Identifier (CI)',
    mandate_type          ENUM('SDD_CORE','SDD_B2B') NOT NULL DEFAULT 'SDD_CORE',
    sequence_type         ENUM('OOFF','FRST','RCUR','FNAL') NOT NULL DEFAULT 'RCUR'
                          COMMENT 'One-off, First, Recurring, Final',
    max_amount            DECIMAL(15,2)          DEFAULT NULL COMMENT 'NULL = unlimited',
    signed_date           DATE         NOT NULL,
    expiry_date           DATE                   DEFAULT NULL,
    status                ENUM('active','suspended','cancelled') NOT NULL DEFAULT 'active',
    created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mandates_id (mandate_id),
    FOREIGN KEY fk_mandate_account (account_id) REFERENCES bank_accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='SEPA Direct Debit mandates';

-- ---------------------------------------------------------------------------
-- 19. BENEFICIARIES
--     Saved/trusted payees for faster future payments.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS beneficiaries (
    id                  INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             INT          UNSIGNED NOT NULL,
    nickname            VARCHAR(100),
    account_holder_name VARCHAR(140) NOT NULL,
    iban                VARCHAR(34)  NOT NULL,
    bic                 VARCHAR(11),
    bank_name           VARCHAR(255),
    currency_code       CHAR(3)      NOT NULL DEFAULT 'EUR',
    is_verified         TINYINT(1)   NOT NULL DEFAULT 0,
    is_trusted          TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Trusted = no extra auth required',
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_beneficiaries_user_iban (user_id, iban),
    FOREIGN KEY fk_beneficiaries_user (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 20. KYC_DOCUMENTS
--     Know Your Customer document uploads for identity verification.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kyc_documents (
    id               INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id          INT          UNSIGNED NOT NULL,
    document_type    ENUM('passport','national_id','drivers_license','residence_permit',
                          'utility_bill','bank_statement','selfie','proof_of_address') NOT NULL,
    file_path        VARCHAR(500) NOT NULL,
    file_hash        CHAR(64)     NOT NULL COMMENT 'SHA-256 of original file for integrity check',
    mime_type        VARCHAR(100),
    file_size_bytes  INT          UNSIGNED,
    status           ENUM('pending','in_review','approved','rejected') NOT NULL DEFAULT 'pending',
    rejection_reason TEXT,
    reviewed_by      INT          UNSIGNED DEFAULT NULL,
    reviewed_at      TIMESTAMP    NULL DEFAULT NULL,
    expiry_date      DATE                  DEFAULT NULL,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_kyc_user     (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY fk_kyc_reviewer (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 21. USER_SESSIONS
--     JWT / session token management with device fingerprinting.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_sessions (
    id                 INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id            INT          UNSIGNED NOT NULL,
    session_token      VARCHAR(255) NOT NULL,
    refresh_token      VARCHAR(255)          DEFAULT NULL,
    ip_address         VARCHAR(45),
    user_agent         TEXT,
    device_fingerprint VARCHAR(255),
    is_active          TINYINT(1)   NOT NULL DEFAULT 1,
    expires_at         TIMESTAMP    NOT NULL,
    created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_active_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sessions_token   (session_token),
    UNIQUE KEY uq_sessions_refresh (refresh_token),
    FOREIGN KEY fk_sessions_user (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 22. NOTIFICATIONS
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id         BIGINT       UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT          UNSIGNED NOT NULL,
    type       ENUM('transaction','security','account','loan','card','system','marketing','kyc') NOT NULL,
    title      VARCHAR(255) NOT NULL,
    message    TEXT         NOT NULL,
    channel    ENUM('email','sms','push','in_app') NOT NULL DEFAULT 'in_app',
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    read_at    TIMESTAMP    NULL DEFAULT NULL,
    metadata   JSON,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_notifications_user (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 23. FEE_SCHEDULES
--     Configurable fee matrix per account type and operation.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fee_schedules (
    id               SMALLINT     UNSIGNED NOT NULL AUTO_INCREMENT,
    name             VARCHAR(100) NOT NULL,
    fee_type         ENUM('transaction','maintenance','overdraft','card_issuance',
                          'wire_transfer','atm_withdrawal','fx_conversion','late_payment') NOT NULL,
    account_type_id  TINYINT      UNSIGNED DEFAULT NULL COMMENT 'NULL = applies to all types',
    fixed_amount     DECIMAL(10,2)         DEFAULT NULL,
    percentage       DECIMAL(6,4)          DEFAULT NULL COMMENT 'e.g. 0.0050 = 0.50%',
    min_fee          DECIMAL(10,2)         DEFAULT NULL,
    max_fee          DECIMAL(10,2)         DEFAULT NULL,
    currency_code    CHAR(3)      NOT NULL DEFAULT 'EUR',
    is_active        TINYINT(1)   NOT NULL DEFAULT 1,
    effective_from   DATE         NOT NULL,
    effective_to     DATE                  DEFAULT NULL,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_fees_account_type (account_type_id) REFERENCES account_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 24. EXCHANGE_RATES
--     Historical FX rates used at time of multi-currency transactions.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS exchange_rates (
    id               INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    base_currency    CHAR(3)      NOT NULL DEFAULT 'EUR',
    target_currency  CHAR(3)      NOT NULL,
    rate             DECIMAL(14,8) NOT NULL,
    bid_rate         DECIMAL(14,8)          DEFAULT NULL,
    ask_rate         DECIMAL(14,8)          DEFAULT NULL,
    source           VARCHAR(50)            COMMENT 'e.g. ECB, internal',
    effective_at     TIMESTAMP    NOT NULL,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_fx_pair_time (base_currency, target_currency, effective_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 25. SUPPORT_TICKETS
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS support_tickets (
    id           INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      INT          UNSIGNED NOT NULL,
    subject      VARCHAR(255) NOT NULL,
    category     ENUM('account','transaction','card','loan','technical','compliance','other') NOT NULL,
    priority     ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    status       ENUM('open','in_progress','waiting_on_customer','resolved','closed') NOT NULL DEFAULT 'open',
    assigned_to  INT          UNSIGNED DEFAULT NULL,
    resolved_at  TIMESTAMP    NULL DEFAULT NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_tickets_user     (user_id)     REFERENCES users(id),
    FOREIGN KEY fk_tickets_assignee (assigned_to) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 26. SUPPORT_TICKET_MESSAGES
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS support_ticket_messages (
    id          INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id   INT          UNSIGNED NOT NULL,
    sender_id   INT          UNSIGNED NOT NULL,
    message     TEXT         NOT NULL,
    attachments JSON,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_ticket_msg_ticket (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY fk_ticket_msg_sender (sender_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 27. AUDIT_LOGS
--     Immutable record of every sensitive operation in the system.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id             BIGINT       UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        INT          UNSIGNED         DEFAULT NULL COMMENT 'NULL for system-initiated events',
    action         VARCHAR(100) NOT NULL COMMENT 'e.g. USER_LOGIN, TRANSACTION_APPROVED',
    entity_type    VARCHAR(50)            COMMENT 'e.g. bank_account, transaction, user',
    entity_id      VARCHAR(50),
    old_values     JSON,
    new_values     JSON,
    ip_address     VARCHAR(45),
    user_agent     TEXT,
    status         ENUM('success','failure') NOT NULL DEFAULT 'success',
    failure_reason TEXT,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY fk_audit_user (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Immutable audit trail for all sensitive operations';

SET FOREIGN_KEY_CHECKS = 1;
