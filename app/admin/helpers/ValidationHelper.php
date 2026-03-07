<?php
/**
 * BnkApp Admin — Validation Helper
 *
 * Standalone validation utilities used beyond the Controller's built-in
 * `validate()` method — for complex rules or reusable cross-field logic.
 */
declare(strict_types=1);

namespace BnkApp\Helpers;

class ValidationHelper
{
    /**
     * Validate a SEPA IBAN string.
     */
    public static function iban(string $iban): bool
    {
        return IbanHelper::validate($iban);
    }

    /**
     * Validate a BIC/SWIFT code (8 or 11 alphanumeric characters).
     * Format: AAAABBCC[DDD]
     *   AAAA = bank code (4 letters)
     *   BB   = country code (2 letters)
     *   CC   = location code (2 alphanumeric)
     *   DDD  = branch code (optional, 3 alphanumeric)
     */
    public static function bic(string $bic): bool
    {
        return (bool)preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', strtoupper(trim($bic)));
    }

    /**
     * Validate a monetary amount: must be a positive number with at most 2 decimal places.
     */
    public static function amount(mixed $amount): bool
    {
        if (!is_numeric($amount)) {
            return false;
        }
        $float = (float)$amount;
        if ($float <= 0) {
            return false;
        }
        // Check at most 2 decimal places
        return preg_match('/^\d+(\.\d{1,2})?$/', (string)$amount) === 1;
    }

    /**
     * Validate that a password meets the minimum strength policy:
     *   - At least 12 characters
     *   - Contains at least one uppercase letter
     *   - Contains at least one lowercase letter
     *   - Contains at least one digit
     *   - Contains at least one special character
     */
    public static function password(string $password): bool
    {
        if (mb_strlen($password) < 12) {
            return false;
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        if (!preg_match('/[\W_]/', $password)) {
            return false;
        }
        return true;
    }

    /**
     * Validate a date string in YYYY-MM-DD format.
     */
    public static function date(string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }

    /**
     * Validate an ISO 3166-1 alpha-2 country code (2 uppercase letters).
     */
    public static function countryCode(string $code): bool
    {
        return (bool)preg_match('/^[A-Z]{2}$/', strtoupper(trim($code)));
    }

    /**
     * Validate an ISO 4217 currency code (3 uppercase letters).
     */
    public static function currencyCode(string $code): bool
    {
        return (bool)preg_match('/^[A-Z]{3}$/', strtoupper(trim($code)));
    }

    /**
     * Validate a SEPA end-to-end ID (max 35 characters, no leading/trailing spaces).
     */
    public static function endToEndId(string $id): bool
    {
        $id = trim($id);
        return $id !== '' && mb_strlen($id) <= 35 && !preg_match('/^\s|\s$/', $id);
    }

    /**
     * Sanitise a string for safe storage: trim whitespace and remove null bytes.
     */
    public static function sanitiseString(string $value): string
    {
        return str_replace("\0", '', trim($value));
    }
}
