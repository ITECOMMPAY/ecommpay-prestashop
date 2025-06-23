<?php

namespace Ecommpay;

if (!defined('_PS_VERSION_')) {
    exit;
}

class EcpCurrencyConverter
{
    private const NON_DECIMAL_CURRENCIES = [
        'BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW', 'PYG',
        'RWF', 'UGX', 'UYI', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];

    public static function transformCurrency(float $amount, string $currency): int
    {
        if (self::isCurrencyUsingDecimals($currency)) {
            return (int) ( round($amount * 100) );
        }

        return (int) ( round($amount) );
    }

    /**
     * @param string $currency
     *
     * @return bool
     */
    private static function isCurrencyUsingDecimals(string $currency): bool
    {
        return ! in_array(strtoupper($currency), self::NON_DECIMAL_CURRENCIES, true);
    }
}
