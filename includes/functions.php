<?php

function dmm_get_currencies($currency = null)
{
    $currencies = [
        'AED' => 2,
        'AUD' => 2,
        'BGN' => 2,
        'CAD' => 2,
        'CHF' => 2,
        'CZK' => 2,
        'DKK' => 2,
        'EUR' => 2,
        'GBP' => 2,
        'HKD' => 2,
        'HRK' => 2,
        'HUF' => 2,
        'ILS' => 2,
        'ISK' => 2,
        'JPY' => 0,
        'NOK' => 2,
        'NZD' => 2,
        'PHP' => 2,
        'PLN' => 2,
        'RON' => 2,
        'RUB' => 2,
        'SEK' => 2,
        'SGD' => 2,
        'USD' => 2,
        'ZAR' => 2,
    ];

    if ($currency && array_key_exists($currency, $currencies))
        return $currencies[$currency];

    return $currencies;
}

function dmm_get_currency_symbol($currency = 'EUR')
{
    switch ($currency)
    {
        case 'EUR':
            $symbol = '&euro;';
            break;
        case 'USD':
            $symbol = 'US$';
            break;
        case 'GBP':
            $symbol = '&pound;';
            break;
        case 'JPY':
            $symbol = '&yen;';
            break;
        default:
            $symbol = strtoupper($currency);
    }

    return $symbol;
}