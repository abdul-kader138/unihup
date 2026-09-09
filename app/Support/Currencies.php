<?php

namespace App\Support;

/**
 * A curated shortlist of home currencies for international students applying
 * to Italy — code => display name. No exchange rates are stored here on
 * purpose: rates for several of these move fast and a stale table would
 * mislead. The Budget Planner asks the student for a current "X per €1" rate
 * instead and does the arithmetic from that.
 */
final class Currencies
{
    /** ISO 4217 code => name, alphabetised by name. */
    public const LIST = [
        'DZD' => 'Algerian dinar',
        'ARS' => 'Argentine peso',
        'AZN' => 'Azerbaijani manat',
        'BDT' => 'Bangladeshi taka',
        'BRL' => 'Brazilian real',
        'GBP' => 'British pound',
        'CAD' => 'Canadian dollar',
        'CNY' => 'Chinese yuan',
        'COP' => 'Colombian peso',
        'EGP' => 'Egyptian pound',
        'ETB' => 'Ethiopian birr',
        'GEL' => 'Georgian lari',
        'GHS' => 'Ghanaian cedi',
        'INR' => 'Indian rupee',
        'IDR' => 'Indonesian rupiah',
        'IRR' => 'Iranian rial',
        'JPY' => 'Japanese yen',
        'JOD' => 'Jordanian dinar',
        'KZT' => 'Kazakhstani tenge',
        'KES' => 'Kenyan shilling',
        'LBP' => 'Lebanese pound',
        'MYR' => 'Malaysian ringgit',
        'MXN' => 'Mexican peso',
        'MAD' => 'Moroccan dirham',
        'NPR' => 'Nepalese rupee',
        'NGN' => 'Nigerian naira',
        'PKR' => 'Pakistani rupee',
        'PHP' => 'Philippine peso',
        'RUB' => 'Russian rouble',
        'SAR' => 'Saudi riyal',
        'RSD' => 'Serbian dinar',
        'ZAR' => 'South African rand',
        'KRW' => 'South Korean won',
        'LKR' => 'Sri Lankan rupee',
        'TZS' => 'Tanzanian shilling',
        'THB' => 'Thai baht',
        'TND' => 'Tunisian dinar',
        'TRY' => 'Turkish lira',
        'UAH' => 'Ukrainian hryvnia',
        'AED' => 'UAE dirham',
        'USD' => 'US dollar',
        'UZS' => 'Uzbekistani soʻm',
        'VND' => 'Vietnamese dong',
    ];

    /** @return array<string, string> code => "Indian rupee (INR)" */
    public static function options(): array
    {
        $out = [];

        foreach (self::LIST as $code => $name) {
            $out[$code] = "{$name} ({$code})";
        }

        return $out;
    }

    public static function name(?string $code): ?string
    {
        return $code ? (self::LIST[$code] ?? $code) : null;
    }

    public static function isValid(?string $code): bool
    {
        return $code !== null && array_key_exists($code, self::LIST);
    }
}
