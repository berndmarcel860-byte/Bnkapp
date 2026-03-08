-- =============================================================================
-- Professional Banking System — Performance Indexes
-- Engine: MySQL 8.0+
-- Run AFTER 01_schema.sql
-- =============================================================================

-- ---------------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------------
CREATE INDEX idx_users_role        ON users (role_id);
CREATE INDEX idx_users_country     ON users (country_id);
CREATE INDEX idx_users_kyc_status  ON users (kyc_status);
CREATE INDEX idx_users_is_active   ON users (is_active);
CREATE INDEX idx_users_last_login  ON users (last_login_at);
CREATE INDEX idx_users_name        ON users (last_name, first_name);

-- ---------------------------------------------------------------------------
-- bank_accounts
-- ---------------------------------------------------------------------------
CREATE INDEX idx_ba_user_id        ON bank_accounts (user_id);
CREATE INDEX idx_ba_status         ON bank_accounts (status);
CREATE INDEX idx_ba_currency       ON bank_accounts (currency_code);
CREATE INDEX idx_ba_branch         ON bank_accounts (branch_id);
CREATE INDEX idx_ba_last_txn       ON bank_accounts (last_transaction_at);

-- ---------------------------------------------------------------------------
-- iban_registry
-- ---------------------------------------------------------------------------
CREATE INDEX idx_ir_country_code   ON iban_registry (country_code);

-- ---------------------------------------------------------------------------
-- transactions  (most performance-critical table)
-- ---------------------------------------------------------------------------
CREATE INDEX idx_txn_from_account  ON transactions (from_account_id);
CREATE INDEX idx_txn_to_account    ON transactions (to_account_id);
CREATE INDEX idx_txn_type          ON transactions (transaction_type);
CREATE INDEX idx_txn_status        ON transactions (status);
CREATE INDEX idx_txn_booking_date  ON transactions (booking_date);
CREATE INDEX idx_txn_value_date    ON transactions (value_date);
CREATE INDEX idx_txn_created_at    ON transactions (created_at);
CREATE INDEX idx_txn_initiated_by  ON transactions (initiated_by);
CREATE INDEX idx_txn_approved_by   ON transactions (approved_by);
-- Composite: fetch account statement sorted by date (most common query)
CREATE INDEX idx_txn_account_date
    ON transactions (from_account_id, booking_date DESC);
CREATE INDEX idx_txn_account_date_to
    ON transactions (to_account_id, booking_date DESC);

-- ---------------------------------------------------------------------------
-- sepa_transfers
-- ---------------------------------------------------------------------------
CREATE INDEX idx_sepa_type         ON sepa_transfers (sepa_type);
CREATE INDEX idx_sepa_status       ON sepa_transfers (status);
CREATE INDEX idx_sepa_debtor_iban  ON sepa_transfers (debtor_iban);
CREATE INDEX idx_sepa_creditor_iban ON sepa_transfers (creditor_iban);
CREATE INDEX idx_sepa_settlement   ON sepa_transfers (settlement_date);

-- ---------------------------------------------------------------------------
-- cards
-- ---------------------------------------------------------------------------
CREATE INDEX idx_cards_account     ON cards (account_id);
CREATE INDEX idx_cards_status      ON cards (status);
CREATE INDEX idx_cards_expires     ON cards (expires_at);

-- ---------------------------------------------------------------------------
-- loans
-- ---------------------------------------------------------------------------
CREATE INDEX idx_loans_user        ON loans (user_id);
CREATE INDEX idx_loans_account     ON loans (account_id);
CREATE INDEX idx_loans_status      ON loans (status);
CREATE INDEX idx_loans_next_pmt    ON loans (next_payment_date);

-- ---------------------------------------------------------------------------
-- loan_payments
-- ---------------------------------------------------------------------------
CREATE INDEX idx_lp_loan_status    ON loan_payments (loan_id, status);
CREATE INDEX idx_lp_due_date       ON loan_payments (due_date);

-- ---------------------------------------------------------------------------
-- standing_orders
-- ---------------------------------------------------------------------------
CREATE INDEX idx_so_account        ON standing_orders (from_account_id);
CREATE INDEX idx_so_status         ON standing_orders (status);
CREATE INDEX idx_so_next_exec      ON standing_orders (next_execution_date);

-- ---------------------------------------------------------------------------
-- sepa_mandates
-- ---------------------------------------------------------------------------
CREATE INDEX idx_mandates_account  ON sepa_mandates (account_id);
CREATE INDEX idx_mandates_status   ON sepa_mandates (status);

-- ---------------------------------------------------------------------------
-- beneficiaries
-- ---------------------------------------------------------------------------
CREATE INDEX idx_bene_user         ON beneficiaries (user_id);
CREATE INDEX idx_bene_iban         ON beneficiaries (iban);

-- ---------------------------------------------------------------------------
-- kyc_documents
-- ---------------------------------------------------------------------------
CREATE INDEX idx_kyc_user_status   ON kyc_documents (user_id, status);
CREATE INDEX idx_kyc_reviewed_by   ON kyc_documents (reviewed_by);

-- ---------------------------------------------------------------------------
-- user_sessions
-- ---------------------------------------------------------------------------
CREATE INDEX idx_sess_user         ON user_sessions (user_id);
CREATE INDEX idx_sess_expires      ON user_sessions (expires_at);
CREATE INDEX idx_sess_active       ON user_sessions (is_active);

-- ---------------------------------------------------------------------------
-- notifications
-- ---------------------------------------------------------------------------
CREATE INDEX idx_notif_user_unread ON notifications (user_id, is_read);
CREATE INDEX idx_notif_created     ON notifications (created_at);

-- ---------------------------------------------------------------------------
-- audit_logs  (append-only; range queries by date/action are common)
-- ---------------------------------------------------------------------------
CREATE INDEX idx_audit_user        ON audit_logs (user_id);
CREATE INDEX idx_audit_action      ON audit_logs (action);
CREATE INDEX idx_audit_entity      ON audit_logs (entity_type, entity_id);
CREATE INDEX idx_audit_created     ON audit_logs (created_at);

-- ---------------------------------------------------------------------------
-- support_tickets
-- ---------------------------------------------------------------------------
CREATE INDEX idx_tickets_user      ON support_tickets (user_id);
CREATE INDEX idx_tickets_status    ON support_tickets (status);
CREATE INDEX idx_tickets_assigned  ON support_tickets (assigned_to);

-- ---------------------------------------------------------------------------
-- exchange_rates
-- ---------------------------------------------------------------------------
CREATE INDEX idx_fx_pair_date
    ON exchange_rates (base_currency, target_currency, effective_at DESC);
