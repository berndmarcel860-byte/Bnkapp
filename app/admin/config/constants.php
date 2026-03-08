<?php
/**
 * BnkApp Admin — Application Constants
 *
 * Global named constants used throughout the admin backend.
 * All values here are non-sensitive and safe to commit.
 */
declare(strict_types=1);

// ---------------------------------------------------------------------------
// Directory paths
// ---------------------------------------------------------------------------
define('ADMIN_ROOT',    dirname(__DIR__));
define('CONFIG_PATH',   ADMIN_ROOT . '/config');
define('CORE_PATH',     ADMIN_ROOT . '/core');
define('CONTROLLERS',   ADMIN_ROOT . '/controllers');
define('MODELS_PATH',   ADMIN_ROOT . '/models');
define('MIDDLEWARE',    ADMIN_ROOT . '/middleware');
define('HELPERS_PATH',  ADMIN_ROOT . '/helpers');
define('VIEWS_PATH',    ADMIN_ROOT . '/views');
define('STORAGE_PATH',  ADMIN_ROOT . '/storage');
define('LOGS_PATH',     STORAGE_PATH . '/logs');

// ---------------------------------------------------------------------------
// HTTP
// ---------------------------------------------------------------------------
define('HTTP_OK',                   200);
define('HTTP_CREATED',              201);
define('HTTP_NO_CONTENT',           204);
define('HTTP_BAD_REQUEST',          400);
define('HTTP_UNAUTHORIZED',         401);
define('HTTP_FORBIDDEN',            403);
define('HTTP_NOT_FOUND',            404);
define('HTTP_METHOD_NOT_ALLOWED',   405);
define('HTTP_UNPROCESSABLE_ENTITY', 422);
define('HTTP_TOO_MANY_REQUESTS',    429);
define('HTTP_SERVER_ERROR',         500);

// ---------------------------------------------------------------------------
// User roles  (must match roles.name in the database)
// ---------------------------------------------------------------------------
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN',       'admin');
define('ROLE_MANAGER',     'manager');
define('ROLE_TELLER',      'teller');
define('ROLE_CUSTOMER',    'customer');

// ---------------------------------------------------------------------------
// KYC statuses
// ---------------------------------------------------------------------------
define('KYC_PENDING',   'pending');
define('KYC_IN_REVIEW', 'in_review');
define('KYC_APPROVED',  'approved');
define('KYC_REJECTED',  'rejected');

// ---------------------------------------------------------------------------
// Account statuses
// ---------------------------------------------------------------------------
define('ACCOUNT_PENDING',  'pending');
define('ACCOUNT_ACTIVE',   'active');
define('ACCOUNT_INACTIVE', 'inactive');
define('ACCOUNT_FROZEN',   'frozen');
define('ACCOUNT_CLOSED',   'closed');

// ---------------------------------------------------------------------------
// Transaction statuses
// ---------------------------------------------------------------------------
define('TXN_PENDING',      'pending');
define('TXN_PROCESSING',   'processing');
define('TXN_UNDER_REVIEW', 'under_review');
define('TXN_COMPLETED',    'completed');
define('TXN_FAILED',       'failed');
define('TXN_CANCELLED',    'cancelled');
define('TXN_REVERSED',     'reversed');

// ---------------------------------------------------------------------------
// Loan statuses
// ---------------------------------------------------------------------------
define('LOAN_APPLIED',      'applied');
define('LOAN_IN_REVIEW',    'in_review');
define('LOAN_APPROVED',     'approved');
define('LOAN_DISBURSED',    'disbursed');
define('LOAN_ACTIVE',       'active');
define('LOAN_DEFAULTED',    'defaulted');
define('LOAN_PAID_OFF',     'paid_off');
define('LOAN_REJECTED',     'rejected');
define('LOAN_CANCELLED',    'cancelled');

// ---------------------------------------------------------------------------
// Default SEPA / banking values
// ---------------------------------------------------------------------------
define('DEFAULT_CURRENCY',    'EUR');
define('IBAN_MAX_LENGTH',     34);
define('BIC_MAX_LENGTH',      11);
define('SEPA_REMITTANCE_MAX', 140);
