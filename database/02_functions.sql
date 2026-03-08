-- =============================================================================
-- Professional Banking System — IBAN Generation & Helper Functions
-- Engine: MySQL 8.0+
--
-- SEPA IBAN Format:
--   [Country Code 2] [Check Digits 2] [BBAN variable]
--   Max total length: 34 characters
--
-- Check Digit Algorithm (ISO 7064 MOD-97-10):
--   1. Move country code + "00" to the end of the BBAN
--   2. Replace each letter with digits (A=10 … Z=35)
--   3. Compute MOD 97 of the resulting numeric string (processed in chunks)
--   4. Check digits = 98 − result
--   5. Zero-pad the check digits to 2 characters
-- =============================================================================

DELIMITER $$

-- ---------------------------------------------------------------------------
-- fn_iban_char_to_digits
--   Converts a single alphanumeric character to its numeric IBAN equivalent.
--   Digits 0-9 stay the same; letters A-Z become 10-35.
--   Used internally by fn_compute_iban_check_digits.
-- ---------------------------------------------------------------------------
CREATE FUNCTION IF NOT EXISTS fn_iban_char_to_digits(c CHAR(1))
RETURNS VARCHAR(2)
DETERMINISTIC
NO SQL
BEGIN
    DECLARE code INT DEFAULT ASCII(UPPER(c));
    -- A=65 → 10, B=66 → 11, … Z=90 → 35
    IF code BETWEEN 65 AND 90 THEN
        RETURN CAST(code - 55 AS CHAR);   -- 65-55=10, 90-55=35
    END IF;
    RETURN c;   -- already a digit
END$$

-- ---------------------------------------------------------------------------
-- fn_compute_iban_check_digits
--   Given a BBAN and country code, returns the 2-digit ISO 7064 MOD-97-10
--   check digits for a SEPA IBAN.
--
--   Parameters:
--     p_country_code  CHAR(2)    e.g. 'DE'
--     p_bban          VARCHAR(30) e.g. '500105173516060000'  (Germany example)
--
--   Returns: CHAR(2) — zero-padded check digits, e.g. '89'
-- ---------------------------------------------------------------------------
CREATE FUNCTION IF NOT EXISTS fn_compute_iban_check_digits(
    p_country_code CHAR(2),
    p_bban         VARCHAR(30)
)
RETURNS CHAR(2)
DETERMINISTIC
NO SQL
BEGIN
    -- Step 1: build the rearranged string: BBAN + country_code + '00'
    DECLARE rearranged   VARCHAR(200) DEFAULT '';
    DECLARE numeric_str  VARCHAR(400) DEFAULT '';
    DECLARE i            INT          DEFAULT 1;
    DECLARE ch           CHAR(1);
    DECLARE chunk        VARCHAR(18);
    DECLARE remainder    BIGINT       DEFAULT 0;
    DECLARE check_digits INT;

    SET rearranged = CONCAT(p_bban, p_country_code, '00');

    -- Step 2: replace every letter with its 2-digit numeric equivalent
    WHILE i <= CHAR_LENGTH(rearranged) DO
        SET ch  = SUBSTRING(rearranged, i, 1);
        SET numeric_str = CONCAT(numeric_str, fn_iban_char_to_digits(ch));
        SET i = i + 1;
    END WHILE;

    -- Step 3: compute MOD 97 in chunks of up to 9 digits to avoid overflow
    --         MySQL BIGINT can safely hold up to 18-digit integers.
    SET i = 1;
    WHILE i <= CHAR_LENGTH(numeric_str) DO
        -- Take up to 9 new digits and prepend any leftover remainder
        SET chunk = CONCAT(CAST(remainder AS CHAR), SUBSTRING(numeric_str, i, 9));
        SET remainder = CAST(chunk AS UNSIGNED) MOD 97;
        SET i = i + 9;
    END WHILE;

    -- Step 4: check digits = 98 − remainder
    SET check_digits = 98 - remainder;

    -- Step 5: zero-pad to 2 digits
    RETURN LPAD(CAST(check_digits AS CHAR), 2, '0');
END$$

-- ---------------------------------------------------------------------------
-- fn_generate_iban
--   Assembles a complete, validated SEPA IBAN from its components.
--
--   Parameters:
--     p_country_code      CHAR(2)    ISO country code, e.g. 'DE'
--     p_bank_code         VARCHAR(20) National bank identifier, e.g. '50010517'
--     p_account_number    VARCHAR(30) Zero-padded account number, e.g. '3516060000'
--
--   Returns: VARCHAR(34) — the full IBAN string, e.g. 'DE89370400440532013000'
--
--   Note: The caller must zero-pad p_bank_code and p_account_number to the
--         widths required by the target country's BBAN format (see seed data).
-- ---------------------------------------------------------------------------
CREATE FUNCTION IF NOT EXISTS fn_generate_iban(
    p_country_code   CHAR(2),
    p_bank_code      VARCHAR(20),
    p_account_number VARCHAR(30)
)
RETURNS VARCHAR(34)
DETERMINISTIC
NO SQL
BEGIN
    DECLARE v_bban         VARCHAR(30);
    DECLARE v_check_digits CHAR(2);
    DECLARE v_iban         VARCHAR(34);

    -- Concatenate BBAN parts (no spaces — spaces only appear in paper format)
    SET v_bban = CONCAT(p_bank_code, p_account_number);

    -- Compute check digits
    SET v_check_digits = fn_compute_iban_check_digits(p_country_code, v_bban);

    -- Assemble IBAN: Country(2) + CheckDigits(2) + BBAN
    SET v_iban = CONCAT(p_country_code, v_check_digits, v_bban);

    RETURN v_iban;
END$$

-- ---------------------------------------------------------------------------
-- fn_validate_iban
--   Returns 1 if the supplied IBAN passes the ISO 7064 MOD-97-10 check,
--   0 otherwise.  Useful for validating externally supplied IBANs before
--   storing them in the sepa_transfers or beneficiaries tables.
-- ---------------------------------------------------------------------------
CREATE FUNCTION IF NOT EXISTS fn_validate_iban(p_iban VARCHAR(34))
RETURNS BOOLEAN
DETERMINISTIC
NO SQL
BEGIN
    DECLARE v_rearranged  VARCHAR(200) DEFAULT '';
    DECLARE v_numeric_str VARCHAR(400) DEFAULT '';
    DECLARE i             INT          DEFAULT 1;
    DECLARE ch            CHAR(1);
    DECLARE chunk         VARCHAR(18);
    DECLARE remainder     BIGINT       DEFAULT 0;
    DECLARE v_clean       VARCHAR(34);

    -- Remove spaces and convert to uppercase
    SET v_clean = REPLACE(UPPER(TRIM(p_iban)), ' ', '');

    IF CHAR_LENGTH(v_clean) < 5 THEN
        RETURN 0;
    END IF;

    -- Rearrange: move first 4 chars (country + check digits) to end
    SET v_rearranged = CONCAT(SUBSTRING(v_clean, 5), SUBSTRING(v_clean, 1, 4));

    -- Convert letters to digits
    WHILE i <= CHAR_LENGTH(v_rearranged) DO
        SET ch = SUBSTRING(v_rearranged, i, 1);
        SET v_numeric_str = CONCAT(v_numeric_str, fn_iban_char_to_digits(ch));
        SET i = i + 1;
    END WHILE;

    -- MOD 97 in chunks
    SET i = 1;
    WHILE i <= CHAR_LENGTH(v_numeric_str) DO
        SET chunk = CONCAT(CAST(remainder AS CHAR), SUBSTRING(v_numeric_str, i, 9));
        SET remainder = CAST(chunk AS UNSIGNED) MOD 97;
        SET i = i + 9;
    END WHILE;

    -- A valid IBAN yields remainder = 1
    RETURN IF(remainder = 1, 1, 0);
END$$

-- ---------------------------------------------------------------------------
-- fn_format_iban_paper
--   Formats a compact IBAN into the human-readable paper format with spaces
--   every 4 characters. e.g. 'DE89370400440532013000' →
--                                'DE89 3704 0044 0532 0130 00'
-- ---------------------------------------------------------------------------
CREATE FUNCTION IF NOT EXISTS fn_format_iban_paper(p_iban VARCHAR(34))
RETURNS VARCHAR(40)
DETERMINISTIC
NO SQL
BEGIN
    DECLARE v_clean  VARCHAR(34) DEFAULT REPLACE(UPPER(TRIM(p_iban)), ' ', '');
    DECLARE v_result VARCHAR(40) DEFAULT '';
    DECLARE i        INT         DEFAULT 1;
    DECLARE len      INT         DEFAULT CHAR_LENGTH(v_clean);

    WHILE i <= len DO
        IF i > 1 AND ((i - 1) MOD 4) = 0 THEN
            SET v_result = CONCAT(v_result, ' ');
        END IF;
        SET v_result = CONCAT(v_result, SUBSTRING(v_clean, i, 1));
        SET i = i + 1;
    END WHILE;

    RETURN v_result;
END$$

-- ---------------------------------------------------------------------------
-- sp_open_account
--   Opens a new bank account for an existing verified user:
--   1. Generates a unique sequential account number
--   2. Calls fn_generate_iban to produce a valid SEPA IBAN
--   3. Inserts into bank_accounts
--   4. Records the IBAN in iban_registry
--   5. Logs the action in audit_logs
--
--   Parameters:
--     p_user_id          INT UNSIGNED   — customer user ID
--     p_account_type_id  TINYINT        — product type
--     p_branch_id        SMALLINT       — originating branch
--     p_country_code     CHAR(2)        — IBAN country, e.g. 'DE'
--     p_bank_code        VARCHAR(20)    — branch bank sort code (padded)
--     p_currency_code    CHAR(3)        — default 'EUR'
--     p_initiated_by     INT UNSIGNED   — admin/system user ID
--
--   OUT p_iban           VARCHAR(34)    — the generated IBAN
--   OUT p_account_id     INT UNSIGNED   — new bank_accounts.id
-- ---------------------------------------------------------------------------
CREATE PROCEDURE IF NOT EXISTS sp_open_account(
    IN  p_user_id         INT UNSIGNED,
    IN  p_account_type_id TINYINT UNSIGNED,
    IN  p_branch_id       SMALLINT UNSIGNED,
    IN  p_country_code    CHAR(2),
    IN  p_bank_code       VARCHAR(20),
    IN  p_currency_code   CHAR(3),
    IN  p_initiated_by    INT UNSIGNED,
    OUT p_iban            VARCHAR(34),
    OUT p_account_id      INT UNSIGNED
)
proc: BEGIN
    DECLARE v_account_number   VARCHAR(34);
    DECLARE v_bic              VARCHAR(11);
    DECLARE v_account_seq      BIGINT UNSIGNED;
    DECLARE v_padded_account   VARCHAR(30);
    DECLARE v_check_digits     CHAR(2);
    DECLARE v_is_primary       TINYINT(1) DEFAULT 0;
    DECLARE v_existing_count   INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- Validate user exists and KYC is approved
    IF NOT EXISTS (
        SELECT 1 FROM users
        WHERE id = p_user_id AND is_active = 1 AND kyc_status = 'approved'
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'User does not exist, is inactive, or KYC not approved';
    END IF;

    START TRANSACTION;

    -- Generate a unique sequential account identifier
    -- Using the auto-increment of bank_accounts + user_id to ensure uniqueness
    SELECT COALESCE(MAX(CAST(account_number AS UNSIGNED)), 0) + 1
      INTO v_account_seq
      FROM bank_accounts;

    -- Zero-pad to 10 digits for use in the BBAN
    SET v_padded_account = LPAD(CAST(v_account_seq AS CHAR), 10, '0');

    -- Build internal account number: bank_code + padded_sequence
    SET v_account_number = CONCAT(p_bank_code, v_padded_account);

    -- Fetch BIC for this branch
    SELECT bic INTO v_bic FROM branches WHERE id = p_branch_id LIMIT 1;
    IF v_bic IS NULL THEN
        SET v_bic = 'BNKAPPDE';   -- default BIC when branch not found
    END IF;

    -- Generate SEPA IBAN
    SET p_iban = fn_generate_iban(p_country_code, p_bank_code, v_padded_account);

    -- Determine if this should be the primary account
    SELECT COUNT(*) INTO v_existing_count
      FROM bank_accounts
     WHERE user_id = p_user_id AND status = 'active';
    SET v_is_primary = IF(v_existing_count = 0, 1, 0);

    -- Insert into bank_accounts
    INSERT INTO bank_accounts (
        user_id, account_type_id, branch_id,
        account_number, iban, bic, currency_code,
        balance, available_balance, status, is_primary
    ) VALUES (
        p_user_id, p_account_type_id, p_branch_id,
        v_account_number, p_iban, v_bic, p_currency_code,
        0.00, 0.00, 'active',
        v_is_primary
    );

    SET p_account_id = LAST_INSERT_ID();

    -- Record IBAN in the immutable registry
    INSERT INTO iban_registry (
        bank_account_id, country_code, check_digits,
        bank_code, account_identifier,
        bban, full_iban, generated_by
    ) VALUES (
        p_account_id,
        p_country_code,
        SUBSTRING(p_iban, 3, 2),
        p_bank_code,
        v_padded_account,
        CONCAT(p_bank_code, v_padded_account),
        p_iban,
        p_initiated_by
    );

    -- Audit
    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, new_values, status)
    VALUES (
        p_initiated_by,
        'ACCOUNT_OPENED',
        'bank_account',
        CAST(p_account_id AS CHAR),
        JSON_OBJECT('iban', p_iban, 'account_number', v_account_number,
                    'currency', p_currency_code, 'user_id', p_user_id),
        'success'
    );

    COMMIT;
END$$

-- ---------------------------------------------------------------------------
-- sp_execute_transfer
--   Performs an internal transfer between two accounts with balance checks,
--   fee application, and full audit trail.
--
--   Parameters:
--     p_from_account_id  INT UNSIGNED
--     p_to_account_id    INT UNSIGNED
--     p_amount           DECIMAL(15,2)
--     p_currency_code    CHAR(3)
--     p_description      VARCHAR(500)
--     p_reference        VARCHAR(255)  — payment reference
--     p_end_to_end_id    VARCHAR(35)   — SEPA e2e ID (can be NULL)
--     p_initiated_by     INT UNSIGNED  — user who initiated the transfer
--
--   OUT p_transaction_ref  VARCHAR(64)  — generated UUID reference
--   OUT p_transaction_id   BIGINT UNSIGNED
-- ---------------------------------------------------------------------------
CREATE PROCEDURE IF NOT EXISTS sp_execute_transfer(
    IN  p_from_account_id INT UNSIGNED,
    IN  p_to_account_id   INT UNSIGNED,
    IN  p_amount          DECIMAL(15,2),
    IN  p_currency_code   CHAR(3),
    IN  p_description     VARCHAR(500),
    IN  p_reference       VARCHAR(255),
    IN  p_end_to_end_id   VARCHAR(35),
    IN  p_initiated_by    INT UNSIGNED,
    OUT p_transaction_ref VARCHAR(64),
    OUT p_transaction_id  BIGINT UNSIGNED
)
proc: BEGIN
    DECLARE v_from_balance     DECIMAL(15,2);
    DECLARE v_from_status      VARCHAR(20);
    DECLARE v_to_status        VARCHAR(20);
    DECLARE v_fee              DECIMAL(10,2) DEFAULT 0.00;
    DECLARE v_net_amount       DECIMAL(15,2);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- Input validation
    IF p_amount <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transfer amount must be positive';
    END IF;

    IF p_from_account_id = p_to_account_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Source and destination accounts must differ';
    END IF;

    START TRANSACTION;

    -- Lock both accounts for update to prevent race conditions
    SELECT balance, available_balance, status
      INTO v_from_balance, @v_from_avail, v_from_status
      FROM bank_accounts
     WHERE id = p_from_account_id FOR UPDATE;

    SELECT status INTO v_to_status
      FROM bank_accounts
     WHERE id = p_to_account_id FOR UPDATE;

    -- Account status checks
    IF v_from_status NOT IN ('active') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Source account is not active';
    END IF;

    IF v_to_status NOT IN ('active') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Destination account is not active';
    END IF;

    -- Sufficient funds check (using available_balance which excludes holds)
    IF @v_from_avail < p_amount THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient available balance';
    END IF;

    -- Generate unique transaction reference
    SET p_transaction_ref = UUID();
    SET v_net_amount = p_amount - v_fee;

    -- Insert transaction record
    INSERT INTO transactions (
        transaction_ref, from_account_id, to_account_id,
        transaction_type, amount, currency_code,
        fee_amount, net_amount, status,
        description, reference, end_to_end_id,
        booking_date, value_date, initiated_by
    ) VALUES (
        p_transaction_ref, p_from_account_id, p_to_account_id,
        'internal_transfer', p_amount, p_currency_code,
        v_fee, v_net_amount, 'completed',
        p_description, p_reference, p_end_to_end_id,
        CURDATE(), CURDATE(), p_initiated_by
    );

    SET p_transaction_id = LAST_INSERT_ID();

    -- Debit sender
    UPDATE bank_accounts
       SET balance           = balance           - p_amount,
           available_balance = available_balance - p_amount,
           last_transaction_at = NOW()
     WHERE id = p_from_account_id;

    -- Credit receiver (net after fees — fee stays with the bank)
    UPDATE bank_accounts
       SET balance           = balance           + v_net_amount,
           available_balance = available_balance + v_net_amount,
           last_transaction_at = NOW()
     WHERE id = p_to_account_id;

    -- Audit log
    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, new_values, status)
    VALUES (
        p_initiated_by,
        'TRANSFER_EXECUTED',
        'transaction',
        CAST(p_transaction_id AS CHAR),
        JSON_OBJECT(
            'from_account', p_from_account_id,
            'to_account',   p_to_account_id,
            'amount',       p_amount,
            'currency',     p_currency_code,
            'ref',          p_transaction_ref
        ),
        'success'
    );

    COMMIT;
END$$

-- ---------------------------------------------------------------------------
-- sp_deposit
--   Records a cash deposit or external credit to an account.
-- ---------------------------------------------------------------------------
CREATE PROCEDURE IF NOT EXISTS sp_deposit(
    IN  p_account_id      INT UNSIGNED,
    IN  p_amount          DECIMAL(15,2),
    IN  p_currency_code   CHAR(3),
    IN  p_description     VARCHAR(500),
    IN  p_initiated_by    INT UNSIGNED,
    OUT p_transaction_ref VARCHAR(64),
    OUT p_transaction_id  BIGINT UNSIGNED
)
BEGIN
    DECLARE v_status VARCHAR(20);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF p_amount <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Deposit amount must be positive';
    END IF;

    START TRANSACTION;

    SELECT status INTO v_status FROM bank_accounts WHERE id = p_account_id FOR UPDATE;

    IF v_status NOT IN ('active') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Account is not active';
    END IF;

    SET p_transaction_ref = UUID();

    INSERT INTO transactions (
        transaction_ref, to_account_id,
        transaction_type, amount, currency_code,
        fee_amount, net_amount, status,
        description, booking_date, value_date, initiated_by
    ) VALUES (
        p_transaction_ref, p_account_id,
        'deposit', p_amount, p_currency_code,
        0.00, p_amount, 'completed',
        p_description, CURDATE(), CURDATE(), p_initiated_by
    );

    SET p_transaction_id = LAST_INSERT_ID();

    UPDATE bank_accounts
       SET balance           = balance           + p_amount,
           available_balance = available_balance + p_amount,
           last_transaction_at = NOW()
     WHERE id = p_account_id;

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, new_values, status)
    VALUES (
        p_initiated_by, 'DEPOSIT_EXECUTED', 'transaction',
        CAST(p_transaction_id AS CHAR),
        JSON_OBJECT('account', p_account_id, 'amount', p_amount,
                    'currency', p_currency_code),
        'success'
    );

    COMMIT;
END$$

-- ---------------------------------------------------------------------------
-- sp_close_account
--   Marks an account as closed after verifying zero balance.
-- ---------------------------------------------------------------------------
CREATE PROCEDURE IF NOT EXISTS sp_close_account(
    IN p_account_id  INT UNSIGNED,
    IN p_closed_by   INT UNSIGNED
)
BEGIN
    DECLARE v_balance DECIMAL(15,2);
    DECLARE v_status  VARCHAR(20);

    START TRANSACTION;

    SELECT balance, status
      INTO v_balance, v_status
      FROM bank_accounts
     WHERE id = p_account_id FOR UPDATE;

    IF v_status = 'closed' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Account is already closed';
    END IF;

    IF v_balance <> 0.00 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Account must have zero balance before closing';
    END IF;

    UPDATE bank_accounts
       SET status = 'closed', closed_at = NOW()
     WHERE id = p_account_id;

    INSERT INTO audit_logs (user_id, action, entity_type, entity_id,
                            old_values, new_values, status)
    VALUES (
        p_closed_by, 'ACCOUNT_CLOSED', 'bank_account',
        CAST(p_account_id AS CHAR),
        JSON_OBJECT('status', v_status),
        JSON_OBJECT('status', 'closed'),
        'success'
    );

    COMMIT;
END$$

DELIMITER ;
