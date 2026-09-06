<?php

return [
    'quote_ttl_seconds' => (int) env('TRADING_QUOTE_TTL', 75),
    'price_cache_seconds' => (int) env('TRADING_PRICE_CACHE_TTL', 300),
    'buy_markup' => (float) env('TRADING_BUY_MARKUP', 1.10),
    'sell_multiplier' => (float) env('TRADING_SELL_MULTIPLIER', 0.90),
    'buy_guardrail_pkr' => (float) env('TRADING_BUY_GUARDRAIL_PKR', 45000),
    'price_mode' => env('TRADING_PRICE_MODE', 'live'),
    'demo_price_pkr' => (float) env('TRADING_DEMO_PRICE_PKR', 39500),
];
