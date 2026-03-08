-- =============================================================================
-- Professional Banking System — Seed Data
-- Engine: MySQL 8.0+
-- Run AFTER 01_schema.sql and 02_functions.sql
--
-- Passwords in this file are BCRYPT hashes for demonstration only.
-- DO NOT use these credentials in production.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- ROLES
-- ---------------------------------------------------------------------------
INSERT INTO roles (name, description) VALUES
    ('super_admin', 'Full system access — unrestricted'),
    ('admin',       'Bank administration — manages users, accounts, reports'),
    ('manager',     'Branch/department manager — approves large transactions'),
    ('teller',      'Front-office staff — deposits, withdrawals, basic queries'),
    ('customer',    'Retail or business banking customer');

-- ---------------------------------------------------------------------------
-- PERMISSIONS
-- ---------------------------------------------------------------------------
INSERT INTO permissions (name, description) VALUES
    ('users.view',                'View user profiles'),
    ('users.create',              'Create new user accounts'),
    ('users.edit',                'Edit existing user accounts'),
    ('users.delete',              'Deactivate / delete users'),
    ('users.kyc.approve',         'Approve or reject KYC documents'),
    ('accounts.view',             'View account details and balances'),
    ('accounts.create',           'Open new bank accounts'),
    ('accounts.close',            'Close bank accounts'),
    ('accounts.freeze',           'Freeze / unfreeze accounts'),
    ('transactions.view',         'View transaction history'),
    ('transactions.deposit',      'Post a deposit to an account'),
    ('transactions.withdraw',     'Post a withdrawal from an account'),
    ('transactions.transfer',     'Execute internal transfers'),
    ('transactions.sepa',         'Initiate SEPA credit/debit transfers'),
    ('transactions.approve',      'Approve high-value or flagged transactions'),
    ('transactions.reverse',      'Reverse completed transactions'),
    ('loans.view',                'View loan applications and schedules'),
    ('loans.create',              'Submit a loan application'),
    ('loans.approve',             'Approve or reject loan applications'),
    ('cards.view',                'View card details'),
    ('cards.issue',               'Issue new payment cards'),
    ('cards.block',               'Block or unblock cards'),
    ('reports.view',              'Access management reports and analytics'),
    ('reports.export',            'Export reports to CSV / PDF'),
    ('settings.fees',             'Manage fee schedules'),
    ('settings.exchange_rates',   'Manage FX exchange rates'),
    ('settings.system',           'System-level configuration'),
    ('support.view',              'View support tickets'),
    ('support.respond',           'Respond to support tickets'),
    ('support.close',             'Close / resolve support tickets'),
    ('audit.view',                'View audit log entries');

-- ---------------------------------------------------------------------------
-- ROLE_PERMISSIONS
-- ---------------------------------------------------------------------------
-- super_admin gets every permission
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM roles r
 CROSS JOIN permissions p
 WHERE r.name = 'super_admin';

-- admin gets almost everything except system settings
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM roles r
 CROSS JOIN permissions p
 WHERE r.name = 'admin'
   AND p.name NOT IN ('settings.system');

-- manager: transactional + approval, no system config
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM roles r
 CROSS JOIN permissions p
 WHERE r.name = 'manager'
   AND p.name IN (
       'users.view',
       'accounts.view', 'accounts.freeze',
       'transactions.view', 'transactions.deposit',
       'transactions.withdraw', 'transactions.transfer',
       'transactions.sepa', 'transactions.approve', 'transactions.reverse',
       'loans.view', 'loans.approve',
       'cards.view', 'cards.block',
       'reports.view', 'reports.export',
       'support.view', 'support.respond', 'support.close',
       'audit.view'
   );

-- teller: day-to-day front-office operations
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM roles r
 CROSS JOIN permissions p
 WHERE r.name = 'teller'
   AND p.name IN (
       'users.view',
       'accounts.view',
       'transactions.view', 'transactions.deposit',
       'transactions.withdraw', 'transactions.transfer',
       'cards.view',
       'support.view', 'support.respond'
   );

-- customer: self-service only
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
  FROM roles r
 CROSS JOIN permissions p
 WHERE r.name = 'customer'
   AND p.name IN (
       'accounts.view',
       'transactions.view', 'transactions.transfer', 'transactions.sepa',
       'loans.view', 'loans.create',
       'cards.view',
       'support.view', 'support.respond'
   );

-- ---------------------------------------------------------------------------
-- SEPA COUNTRIES  (ISO 3166-1 + IBAN length per EPC rulebook 2024)
-- ---------------------------------------------------------------------------
INSERT INTO countries (iso_code, name, currency_code, iban_length, bban_structure, is_sepa) VALUES
    ('AT', 'Austria',          'EUR', 20, '5n 11n',              1),
    ('BE', 'Belgium',          'EUR', 16, '3n 7n 2n',            1),
    ('BG', 'Bulgaria',         'BGN', 22, '4a 4n 2n 8c',         1),
    ('CH', 'Switzerland',      'CHF', 21, '5n 12c',              1),
    ('CY', 'Cyprus',           'EUR', 28, '3n 5n 16c',           1),
    ('CZ', 'Czech Republic',   'CZK', 24, '4n 6n 10n',           1),
    ('DE', 'Germany',          'EUR', 22, '8n 10n',              1),
    ('DK', 'Denmark',          'DKK', 18, '4n 9n 1n',            1),
    ('EE', 'Estonia',          'EUR', 20, '2n 2n 11n 1n',        1),
    ('ES', 'Spain',            'EUR', 24, '4n 4n 1n 1n 10n',     1),
    ('FI', 'Finland',          'EUR', 18, '6n 7n 1n',            1),
    ('FR', 'France',           'EUR', 27, '5n 5n 11c 2n',        1),
    ('GB', 'United Kingdom',   'GBP', 22, '4a 6n 8n',            1),
    ('GR', 'Greece',           'EUR', 27, '3n 4n 16c',           1),
    ('HR', 'Croatia',          'EUR', 21, '7n 10n',              1),
    ('HU', 'Hungary',          'HUF', 28, '3n 4n 1n 15n 1n',     1),
    ('IE', 'Ireland',          'EUR', 22, '4a 6n 8n',            1),
    ('IS', 'Iceland',          'ISK', 26, '4n 2n 6n 10n',        1),
    ('IT', 'Italy',            'EUR', 27, '1a 5n 5n 12c',        1),
    ('LI', 'Liechtenstein',    'CHF', 21, '5n 12c',              1),
    ('LT', 'Lithuania',        'EUR', 20, '5n 11n',              1),
    ('LU', 'Luxembourg',       'EUR', 20, '3n 13c',              1),
    ('LV', 'Latvia',           'EUR', 21, '4a 13c',              1),
    ('MC', 'Monaco',           'EUR', 27, '5n 5n 11c 2n',        1),
    ('MT', 'Malta',            'EUR', 31, '4a 5n 18c',           1),
    ('NL', 'Netherlands',      'EUR', 18, '4a 10n',              1),
    ('NO', 'Norway',           'NOK', 15, '4n 6n 1n',            1),
    ('PL', 'Poland',           'PLN', 28, '8n 16n',              1),
    ('PT', 'Portugal',         'EUR', 25, '4n 4n 11n 2n',        1),
    ('RO', 'Romania',          'RON', 24, '4a 16c',              1),
    ('SE', 'Sweden',           'SEK', 24, '3n 16n 1n',           1),
    ('SI', 'Slovenia',         'EUR', 19, '5n 8n 2n',            1),
    ('SK', 'Slovakia',         'SKK', 24, '4n 6n 10n',           1),
    ('SM', 'San Marino',       'EUR', 27, '1a 5n 5n 12c',        1);

-- ---------------------------------------------------------------------------
-- ACCOUNT TYPES
-- ---------------------------------------------------------------------------
INSERT INTO account_types (name, code, description, interest_rate, min_balance,
                           maintenance_fee, fee_period, overdraft_allowed) VALUES
    ('Personal Checking',
     'CHK',
     'Standard everyday current account for personal use. No minimum balance.',
     0.0000, 0.00, 0.00, 'none', 1),

    ('Personal Savings',
     'SAV',
     'Interest-bearing savings account with competitive rate.',
     0.0250, 0.00, 0.00, 'none', 0),

    ('Premium Checking',
     'PCHK',
     'Premium current account with dedicated relationship manager and higher limits.',
     0.0000, 5000.00, 9.99, 'monthly', 1),

    ('Business Checking',
     'BCHK',
     'Business current account with multi-user access and SEPA bulk payments.',
     0.0000, 0.00, 14.99, 'monthly', 1),

    ('Business Savings',
     'BSAV',
     'High-interest savings account for business cash reserves.',
     0.0300, 1000.00, 0.00, 'none', 0),

    ('Student Account',
     'STU',
     'Fee-free account for full-time students. No maintenance fee.',
     0.0100, 0.00, 0.00, 'none', 0),

    ('Youth Account',
     'YOUTH',
     'Account for customers under 18, managed jointly with a guardian.',
     0.0150, 0.00, 0.00, 'none', 0),

    ('Fixed-Term Deposit',
     'FTD',
     'Lock-in deposits for higher returns over a fixed term.',
     0.0400, 1000.00, 0.00, 'none', 0);

-- ---------------------------------------------------------------------------
-- BRANCHES  (example bank with multiple branches)
-- ---------------------------------------------------------------------------
INSERT INTO branches (name, address, city, postal_code, country_id, bic, bank_code) VALUES
    ('BnkApp Head Office',
     '1 Banking Plaza', 'Frankfurt', '60311',
     (SELECT id FROM countries WHERE iso_code = 'DE'),
     'BNKAPPDE', '50010517'),

    ('BnkApp Berlin Branch',
     '55 Unter den Linden', 'Berlin', '10117',
     (SELECT id FROM countries WHERE iso_code = 'DE'),
     'BNKAPPDE', '10020500'),

    ('BnkApp Amsterdam Branch',
     '10 Herengracht', 'Amsterdam', '1017 BZ',
     (SELECT id FROM countries WHERE iso_code = 'NL'),
     'BNKAPPNL', 'ABNA'),

    ('BnkApp Paris Branch',
     '25 Boulevard Haussmann', 'Paris', '75009',
     (SELECT id FROM countries WHERE iso_code = 'FR'),
     'BNKAPPFR', '30006'),

    ('BnkApp London Branch',
     '1 Lombard Street', 'London', 'EC3V 9AA',
     (SELECT id FROM countries WHERE iso_code = 'GB'),
     'BNKAPPGB', '608371');

-- ---------------------------------------------------------------------------
-- TRANSACTION CATEGORIES
-- ---------------------------------------------------------------------------
INSERT INTO transaction_categories (name, description, icon, is_system) VALUES
    ('Salary & Income',       'Employment and freelance income',            'briefcase',    0),
    ('Rent & Mortgage',       'Housing payments',                           'home',         0),
    ('Groceries',             'Supermarkets and food shopping',             'shopping-cart', 0),
    ('Utilities',             'Gas, electricity, water, internet',          'zap',          0),
    ('Transport',             'Public transport, fuel, taxis',              'car',          0),
    ('Healthcare',            'Medical, dental, pharmacy',                  'activity',     0),
    ('Dining & Restaurants',  'Restaurants, cafés, takeaway',               'coffee',       0),
    ('Entertainment',         'Cinema, streaming, events',                  'film',         0),
    ('Travel',                'Flights, hotels, holidays',                  'map-pin',      0),
    ('Education',             'Tuition, books, courses',                    'book',         0),
    ('Savings & Investments', 'Transfers to savings or investment accounts','trending-up',  0),
    ('Loan Repayment',        'Loan instalments',                           'credit-card',  1),
    ('Bank Fee',              'Maintenance, transaction and card fees',     'dollar-sign',  1),
    ('Interest Credit',       'Interest earned on deposits',                'percent',      1),
    ('Internal Transfer',     'Transfers between own accounts',             'repeat',       1),
    ('SEPA Transfer',         'SEPA credit transfer to/from another bank',  'send',         1),
    ('Direct Debit',          'SEPA Direct Debit collection',               'arrow-down',   1),
    ('ATM Withdrawal',        'Cash withdrawal from ATM',                   'credit-card',  0),
    ('Card Payment',          'Point-of-sale or online card payment',       'credit-card',  0),
    ('Refund',                'Merchant refund',                            'refresh-cw',   0),
    ('Other',                 'Uncategorised',                              'more-horizontal', 0);

-- ---------------------------------------------------------------------------
-- FEE SCHEDULES  (example rates — adjust as required)
-- ---------------------------------------------------------------------------
INSERT INTO fee_schedules
    (name, fee_type, account_type_id, fixed_amount, percentage, min_fee, max_fee,
     currency_code, is_active, effective_from)
VALUES
    -- ATM withdrawal fee for non-premium accounts
    ('Standard ATM Fee',       'atm_withdrawal', NULL,  2.00, NULL, NULL, NULL, 'EUR', 1, '2024-01-01'),
    -- Outgoing SEPA Credit Transfer
    ('SEPA Transfer Fee',      'wire_transfer',  NULL,  0.50, NULL, NULL, NULL, 'EUR', 1, '2024-01-01'),
    -- Overdraft daily charge — 0.05% of overdrawn amount per day
    ('Overdraft Fee',          'overdraft',      NULL,  NULL, 0.0005, 1.00, 25.00, 'EUR', 1, '2024-01-01'),
    -- FX conversion spread
    ('FX Conversion Fee',      'fx_conversion',  NULL,  NULL, 0.0200, 0.50, 500.00, 'EUR', 1, '2024-01-01'),
    -- Card issuance — free for Premium
    ('Standard Card Fee',      'card_issuance',  NULL,  5.00, NULL, NULL, NULL, 'EUR', 1, '2024-01-01'),
    -- Monthly maintenance for Premium Checking
    ('Premium Maintenance Fee','maintenance',
     (SELECT id FROM account_types WHERE code = 'PCHK'),
     9.99, NULL, NULL, NULL, 'EUR', 1, '2024-01-01'),
    -- Monthly maintenance for Business Checking
    ('Business Maintenance Fee','maintenance',
     (SELECT id FROM account_types WHERE code = 'BCHK'),
     14.99, NULL, NULL, NULL, 'EUR', 1, '2024-01-01'),
    -- Late loan payment fee
    ('Loan Late Payment Fee',  'late_payment',   NULL,  25.00, NULL, NULL, NULL, 'EUR', 1, '2024-01-01');

-- ---------------------------------------------------------------------------
-- EXCHANGE RATES  (snapshot — update via nightly job in production)
-- ---------------------------------------------------------------------------
INSERT INTO exchange_rates
    (base_currency, target_currency, rate, bid_rate, ask_rate, source, effective_at)
VALUES
    ('EUR', 'USD', 1.08500000, 1.08490000, 1.08510000, 'ECB', '2024-01-01 16:00:00'),
    ('EUR', 'GBP', 0.85600000, 0.85590000, 0.85610000, 'ECB', '2024-01-01 16:00:00'),
    ('EUR', 'CHF', 0.93100000, 0.93090000, 0.93110000, 'ECB', '2024-01-01 16:00:00'),
    ('EUR', 'SEK', 11.3250000, 11.3240000, 11.3260000, 'ECB', '2024-01-01 16:00:00'),
    ('EUR', 'NOK', 11.5800000, 11.5790000, 11.5810000, 'ECB', '2024-01-01 16:00:00'),
    ('EUR', 'DKK', 7.46100000, 7.46090000, 7.46110000, 'ECB', '2024-01-01 16:00:00'),
    ('EUR', 'PLN', 4.30500000, 4.30490000, 4.30510000, 'ECB', '2024-01-01 16:00:00'),
    ('EUR', 'HUF', 380.550000, 380.54000,  380.56000,  'ECB', '2024-01-01 16:00:00'),
    ('EUR', 'CZK', 25.3100000, 25.3090000, 25.3110000, 'ECB', '2024-01-01 16:00:00'),
    ('EUR', 'RON', 4.97500000, 4.97490000, 4.97510000, 'ECB', '2024-01-01 16:00:00'),
    ('EUR', 'BGN', 1.95583000, 1.95583000, 1.95583000, 'ECB', '2024-01-01 16:00:00');

-- ---------------------------------------------------------------------------
-- SYSTEM USERS  (super admin + demo customer)
--
-- Passwords shown below are BCRYPT hashes of:
--   super_admin → 'Adm!n$ecure2024'  (CHANGE BEFORE PRODUCTION)
--   demo.customer → 'Cust0mer$ecure2024'
--
-- Salt values are embedded in the bcrypt hash itself; the password_salt column
-- is reserved for non-bcrypt schemes.  Both columns are populated here for
-- schema completeness.
-- ---------------------------------------------------------------------------

-- Super-admin user
INSERT INTO users (
    role_id, first_name, last_name, email,
    phone, date_of_birth, national_id,
    address_line1, city, postal_code, country_id,
    password_hash, password_salt,
    is_active, is_email_verified, kyc_status, kyc_approved_at
) VALUES (
    (SELECT id FROM roles WHERE name = 'super_admin'),
    'System', 'Administrator', 'admin@bnkapp.example',
    '+49301234560', '1980-01-01', 'SYSADM001',
    '1 Banking Plaza', 'Frankfurt', '60311',
    (SELECT id FROM countries WHERE iso_code = 'DE'),
    -- bcrypt hash of 'Adm!n$ecure2024' (cost 12)
    '$2b$12$exampleHashSuperAdminPlaceholderXXXXXXXXXXXXXXXXXXXXXXX',
    'system_generated_salt_placeholder',
    1, 1, 'approved', NOW()
);

-- Record admin-specific attributes
INSERT INTO admins (user_id, employee_id, department, access_level,
                    can_approve_transactions, can_manage_users,
                    can_view_reports, can_manage_loans, can_freeze_accounts)
VALUES (
    LAST_INSERT_ID(),
    'EMP-0001', 'IT & Operations', 'super_admin',
    1, 1, 1, 1, 1
);

-- Demo customer user (KYC approved so accounts can be opened)
INSERT INTO users (
    role_id, first_name, last_name, email,
    phone, date_of_birth, national_id,
    address_line1, city, postal_code, country_id,
    password_hash, password_salt,
    is_active, is_email_verified, kyc_status, kyc_approved_at
) VALUES (
    (SELECT id FROM roles WHERE name = 'customer'),
    'Jane', 'Doe', 'jane.doe@example.com',
    '+491701234567', '1990-06-15', 'DEX12345678',
    '42 Musterstrasse', 'Munich', '80333',
    (SELECT id FROM countries WHERE iso_code = 'DE'),
    -- bcrypt hash of 'Cust0mer$ecure2024' (cost 12)
    '$2b$12$exampleHashCustomerPlaceholderXXXXXXXXXXXXXXXXXXXXXXXXX',
    'customer_generated_salt_placeholder',
    1, 1, 'approved', NOW()
);

SET FOREIGN_KEY_CHECKS = 1;
