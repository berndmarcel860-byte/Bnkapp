<?php
/**
 * BnkApp Admin — IBAN Helper
 *
 * PHP wrapper around the IBAN generation and validation logic
 * that mirrors the MySQL functions in 02_functions.sql.
 * Useful for server-side validation before calling the database.
 */
declare(strict_types=1);

namespace BnkApp\Helpers;

class IbanHelper
{
    /**
     * Generate a SEPA IBAN from its components using ISO 7064 MOD-97-10.
     *
     * @param string $countryCode   ISO 3166-1 alpha-2, e.g. 'DE'
     * @param string $bankCode      National bank identifier, zero-padded to required width
     * @param string $accountNumber Account-specific portion, zero-padded to required width
     *
     * @return string Full IBAN, e.g. 'DE89370400440532013000'
     */
    public static function generate(string $countryCode, string $bankCode, string $accountNumber): string
    {
        $countryCode = strtoupper($countryCode);
        $bban        = $bankCode . $accountNumber;
        $checkDigits = self::computeCheckDigits($countryCode, $bban);

        return $countryCode . $checkDigits . $bban;
    }

    /**
     * Validate any IBAN string using ISO 7064 MOD-97-10.
     *
     * @param string $iban  Raw IBAN (spaces are stripped automatically)
     * @return bool         True if valid, false otherwise
     */
    public static function validate(string $iban): bool
    {
        $iban = strtoupper(str_replace(' ', '', trim($iban)));

        if (strlen($iban) < 5) {
            return false;
        }

        // Rearrange: move first 4 characters to end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        // Convert letters to digits
        $numeric = self::lettersToDigits($rearranged);

        // MOD 97 check — valid IBANs yield remainder 1
        return self::mod97($numeric) === 1;
    }

    /**
     * Format an IBAN in the human-readable paper format with spaces every 4 chars.
     * e.g. 'DE89370400440532013000' → 'DE89 3704 0044 0532 0130 00'
     */
    public static function format(string $iban): string
    {
        $clean = strtoupper(str_replace(' ', '', trim($iban)));
        return implode(' ', str_split($clean, 4));
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * Compute 2-digit ISO 7064 check digits for a given country + BBAN.
     */
    private static function computeCheckDigits(string $countryCode, string $bban): string
    {
        // Rearrange: BBAN + country code + '00'
        $rearranged  = $bban . $countryCode . '00';
        $numeric     = self::lettersToDigits($rearranged);
        $remainder   = self::mod97($numeric);
        $checkDigits = 98 - $remainder;

        return str_pad((string)$checkDigits, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Replace every letter in a string with its numeric equivalent (A=10 … Z=35).
     */
    private static function lettersToDigits(string $str): string
    {
        $result = '';
        for ($i = 0, $len = strlen($str); $i < $len; $i++) {
            $char = $str[$i];
            if (ctype_alpha($char)) {
                $result .= (string)(ord(strtoupper($char)) - 55); // A=10
            } else {
                $result .= $char;
            }
        }
        return $result;
    }

    /**
     * Compute MOD 97 of a large integer expressed as a string.
     * Processes the string in 9-character chunks to avoid integer overflow
     * (PHP's float precision would fail on large integers).
     */
    private static function mod97(string $numericStr): int
    {
        $remainder = 0;
        $len       = strlen($numericStr);

        for ($i = 0; $i < $len; $i += 7) {
            $chunk     = (string)$remainder . substr($numericStr, $i, 7);
            $remainder = (int)$chunk % 97;
        }

        return $remainder;
    }
}
