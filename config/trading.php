<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trading Fees
    |--------------------------------------------------------------------------
    |
    | Fee percentages for buy and sell transactions.
    | These fees are how the platform generates revenue.
    |
    */
    'fees' => [
        'buy_percentage' => env('TRADING_FEE_BUY_PERCENTAGE', 1.5),
        'sell_percentage' => env('TRADING_FEE_SELL_PERCENTAGE', 1.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum Transaction Amount
    |--------------------------------------------------------------------------
    |
    | Minimum transaction amount in kobo (1 Naira = 100 kobo).
    | Default: 100000 kobo = ₦1,000
    |
    */
    'minimum_transaction_amount' => env('MINIMUM_TRANSACTION_AMOUNT', 100000),

    /*
    |--------------------------------------------------------------------------
    | Supported Cryptocurrencies
    |--------------------------------------------------------------------------
    |
    | List of supported cryptocurrencies for trading.
    |
    */
    'supported_cryptos' => [
        'btc' => [
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'coingecko_id' => 'bitcoin',
        ],
        'eth' => [
            'name' => 'Ethereum',
            'symbol' => 'ETH',
            'coingecko_id' => 'ethereum',
        ],
        'usdt' => [
            'name' => 'Tether USD',
            'symbol' => 'USDT',
            'coingecko_id' => 'tether',
        ],
    ],
];
