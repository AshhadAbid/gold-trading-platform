<?php

namespace App\Domain\Trading;

enum TradeSide: string
{
    case Buy = 'buy';
    case Sell = 'sell';
}
