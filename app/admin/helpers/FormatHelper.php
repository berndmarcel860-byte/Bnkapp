<?php
/**
 * BnkApp Admin — Format Helper
 *
 * Presentation-layer formatting utilities for money, dates, and text.
 */
declare(strict_types=1);

namespace BnkApp\Helpers;

class FormatHelper
{
    /**
     * Format a monetary amount with currency symbol and locale formatting.
     * e.g. formatMoney(1234567.89, 'EUR') → '€1,234,567.89'
     */
    public static function money(float $amount, string $currency = 'EUR', string $locale = 'en_GB'): string
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, $currency);
    }

    /**
     * Format a UTC database timestamp to a human-readable date-time string.
     * e.g. formatDateTime('2024-03-15 14:32:00') → '15 Mar 2024, 14:32'
     */
    public static function dateTime(?string $timestamp, string $timezone = 'Europe/Berlin'): string
    {
        if ($timestamp === null || $timestamp === '') {
            return '—';
        }

        $dt = new \DateTimeImmutable($timestamp, new \DateTimeZone('UTC'));
        $dt = $dt->setTimezone(new \DateTimeZone($timezone));
        return $dt->format('d M Y, H:i');
    }

    /**
     * Format a date string (YYYY-MM-DD) to readable form.
     * e.g. formatDate('2024-03-15') → '15 Mar 2024'
     */
    public static function date(?string $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }
        return (new \DateTimeImmutable($date))->format('d M Y');
    }

    /**
     * Truncate a string to a maximum length and append an ellipsis if needed.
     */
    public static function truncate(string $text, int $maxLength = 50): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }
        return mb_substr($text, 0, $maxLength - 1) . '…';
    }

    /**
     * Convert a snake_case or underscored string to Title Case.
     * e.g. 'sepa_credit_transfer' → 'Sepa Credit Transfer'
     */
    public static function titleCase(string $string): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $string));
    }

    /**
     * Return an HTML badge class based on a status string.
     * Designed for use with Bootstrap 5 / Tailwind-style status badges.
     *
     * @return string CSS class suffix, e.g. 'success', 'danger', 'warning', 'secondary'
     */
    public static function statusBadge(string $status): string
    {
        return match ($status) {
            'active', 'approved', 'completed', 'paid_off', 'settled'
                => 'success',
            'pending', 'applied', 'in_review', 'processing', 'inactive', 'under_review'
                => 'warning',
            'frozen', 'blocked', 'defaulted', 'failed', 'rejected', 'cancelled'
                => 'danger',
            'closed', 'expired', 'reversed'
                => 'secondary',
            default
                => 'info',
        };
    }

    /**
     * Escape a string for safe HTML output.
     */
    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
